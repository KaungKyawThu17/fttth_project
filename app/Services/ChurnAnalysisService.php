<?php

namespace App\Services;

use App\Models\ChurnPrediction;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketComment;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ChurnAnalysisService
{
    public const DEFAULT_BATCH_SIZE = 15;

    /**
     * @return Collection<int, ChurnPrediction>
     *
     * @throws RequestException
     */
    public function predictAndSave(int $limit = self::DEFAULT_BATCH_SIZE): Collection
    {
        $customers = $this->customersForBatch($limit);
        $payloadCustomers = $customers
            ->map(fn (Customer $customer): array => $this->payloadForCustomer($customer))
            ->values();

        $response = Http::acceptJson()
            ->asJson()
            ->timeout(20)
            ->connectTimeout(5)
            ->retry(2, 500)
            ->post($this->endpoint(), [
                'customers' => $payloadCustomers->all(),
            ])
            ->throw();

        $predictions = $response->json('predictions');

        if (! is_array($predictions)) {
            throw new RuntimeException('The churn API response does not contain predictions.');
        }

        return $this->savePredictions(
            predictions: $predictions,
            customers: $customers->keyBy('customer_code'),
            payloadCustomers: $payloadCustomers->keyBy('customer_id'),
        );
    }

    /**
     * @return Collection<int, Customer>
     */
    protected function customersForBatch(int $limit): Collection
    {
        return Customer::query()
            ->with([
                'tickets.comments' => fn ($query) => $query->latestFirst(),
            ])
            ->withCount('tickets')
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @return array{
     *     customer_id: string,
     *     complaints: int,
     *     downtime_hours: int,
     *     resolution_time: int,
     *     duration_time: int,
     *     description: string
     * }
     */
    protected function payloadForCustomer(Customer $customer): array
    {
        /** @var Collection<int, Ticket> $tickets */
        $tickets = $customer->tickets;

        return [
            'customer_id' => $customer->customer_code,
            'complaints' => (int) $customer->tickets_count,
            'downtime_hours' => (int) round($this->downtimeHours($tickets)),
            'resolution_time' => (int) round($this->resolutionTime($tickets)),
            'duration_time' => random_int(1, 36),
            'description' => $this->description($customer),
        ];
    }

    /**
     * @param  Collection<int, Ticket>  $tickets
     */
    protected function downtimeHours(Collection $tickets): float
    {
        return round((float) $tickets
            ->map(fn (Ticket $ticket): ?float => $this->hoursBetween($ticket->created_at, $ticket->closed_at))
            ->filter(fn (?float $hours): bool => $hours !== null)
            ->sum(), 2);
    }

    /**
     * @param  Collection<int, Ticket>  $tickets
     */
    protected function resolutionTime(Collection $tickets): float
    {
        $resolvedHours = $tickets
            ->map(fn (Ticket $ticket): ?float => $this->hoursBetween($ticket->created_at, $ticket->resolved_at))
            ->filter(fn (?float $hours): bool => $hours !== null);

        if ($resolvedHours->isEmpty()) {
            return 0.0;
        }

        return round((float) $resolvedHours->avg(), 2);
    }

    protected function hoursBetween(?CarbonInterface $start, ?CarbonInterface $end): ?float
    {
        if (! $start || ! $end || $end->lessThan($start)) {
            return null;
        }

        return round($start->diffInMinutes($end) / 60, 2);
    }

    protected function description(Customer $customer): string
    {
        $latestComment = $customer->tickets
            ->flatMap(fn (Ticket $ticket): Collection => $ticket->comments)
            ->sortByDesc(fn (TicketComment $comment): int => $comment->created_at?->getTimestamp() ?? 0)
            ->first();

        if ($latestComment instanceof TicketComment && filled($latestComment->comment)) {
            return $latestComment->comment;
        }

        $latestTicket = $customer->tickets
            ->sortByDesc(fn (Ticket $ticket): int => $ticket->created_at?->getTimestamp() ?? 0)
            ->first();

        if ($latestTicket instanceof Ticket && filled($latestTicket->description)) {
            return $latestTicket->description;
        }

        return 'No customer comment available.';
    }

    /**
     * @param  array<int, mixed>  $predictions
     * @param  Collection<string, Customer>  $customers
     * @param  Collection<string, array<string, mixed>>  $payloadCustomers
     * @return Collection<int, ChurnPrediction>
     */
    protected function savePredictions(array $predictions, Collection $customers, Collection $payloadCustomers): Collection
    {
        $predictedAt = now();

        return collect($predictions)
            ->map(function (mixed $prediction) use ($customers, $payloadCustomers, $predictedAt): ?ChurnPrediction {
                if (! is_array($prediction)) {
                    return null;
                }

                $customerCode = (string) ($prediction['customer_id'] ?? '');
                $customer = $customers->get($customerCode);
                $payloadCustomer = $payloadCustomers->get($customerCode);

                if (! $customer instanceof Customer || ! is_array($payloadCustomer)) {
                    return null;
                }

                foreach (['churn_prediction', 'churn_probability', 'sentiment_label', 'sentiment_score'] as $key) {
                    if (! array_key_exists($key, $prediction)) {
                        return null;
                    }
                }

                return ChurnPrediction::query()->updateOrCreate(
                    ['customer_id' => $customer->getKey()],
                    [
                        'customer_code' => $customerCode,
                        'complaints' => (int) $payloadCustomer['complaints'],
                        'downtime_hours' => (float) $payloadCustomer['downtime_hours'],
                        'resolution_time' => (float) $payloadCustomer['resolution_time'],
                        'duration_time' => (int) $payloadCustomer['duration_time'],
                        'description' => (string) $payloadCustomer['description'],
                        'churn_prediction' => (int) $prediction['churn_prediction'] === 1,
                        'churn_probability' => (float) $prediction['churn_probability'],
                        'sentiment_label' => (string) $prediction['sentiment_label'],
                        'sentiment_score' => (float) $prediction['sentiment_score'],
                        'predicted_at' => $predictedAt,
                    ],
                );
            })
            ->filter()
            ->values();
    }

    protected function endpoint(): string
    {
        $endpoint = config('services.churn_analysis.batch_predict_url');

        if (! is_string($endpoint) || blank($endpoint)) {
            throw new RuntimeException('The churn analysis batch URL is not configured.');
        }

        return $endpoint;
    }
}
