<?php

namespace Tests\Feature;

use App\Models\ChurnPrediction;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketComment;
use App\Models\User;
use App\Services\ChurnAnalysisService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChurnAnalysisServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_sends_customer_payload_and_saves_predictions(): void
    {
        Carbon::setTestNow('2026-05-29 12:00:00');

        $endpoint = 'https://churn-analysis-ai.onrender.com/predict/batch';
        config(['services.churn_analysis.batch_predict_url' => $endpoint]);

        $firstCustomer = $this->createCustomer(1);
        $closedTicket = $this->createTicket(
            customer: $firstCustomer,
            status: Ticket::STATUS_CLOSED,
            createdAt: Carbon::parse('2026-05-01 08:00:00'),
            resolvedAt: Carbon::parse('2026-05-01 12:00:00'),
            closedAt: Carbon::parse('2026-05-01 13:00:00'),
        );
        $openTicket = $this->createTicket(
            customer: $firstCustomer,
            status: Ticket::STATUS_OPEN,
            createdAt: Carbon::parse('2026-05-02 08:00:00'),
        );
        $this->createComment($closedTicket, 'Old customer comment', Carbon::parse('2026-05-01 09:00:00'));
        $this->createComment($openTicket, 'Latest customer comment', Carbon::parse('2026-05-02 09:00:00'));

        for ($number = 2; $number <= 15; $number++) {
            $customer = $this->createCustomer($number);
            $ticket = $this->createTicket(
                customer: $customer,
                status: Ticket::STATUS_CLOSED,
                createdAt: Carbon::parse('2026-05-03 08:00:00')->addHours($number),
                resolvedAt: Carbon::parse('2026-05-03 10:00:00')->addHours($number),
                closedAt: Carbon::parse('2026-05-03 11:00:00')->addHours($number),
            );
            $this->createComment($ticket, "Customer {$number} comment", Carbon::parse('2026-05-03 09:00:00')->addHours($number));
        }

        $sentPayload = null;

        Http::fake([
            $endpoint => function (Request $request) use (&$sentPayload) {
                $sentPayload = $request->data();

                return Http::response([
                    'predictions' => collect($sentPayload['customers'])->map(fn (array $customer, int $index): array => [
                        'customer_id' => $customer['customer_id'],
                        'churn_prediction' => $index === 0 ? 1 : 0,
                        'churn_probability' => $index === 0 ? 0.91 : 0.04,
                        'sentiment_label' => $index === 0 ? 'negative' : 'neutral',
                        'sentiment_score' => $index === 0 ? -1 : 0,
                    ])->all(),
                ]);
            },
        ]);

        $savedPredictions = app(ChurnAnalysisService::class)->predictAndSave();

        Http::assertSentCount(1);
        $this->assertIsArray($sentPayload);
        $this->assertCount(15, $sentPayload['customers']);

        $firstPayload = $sentPayload['customers'][0];

        $this->assertSame($firstCustomer->customer_code, $firstPayload['customer_id']);
        $this->assertSame(2, $firstPayload['complaints']);
        $this->assertSame(5, $firstPayload['downtime_hours']);
        $this->assertSame(4, $firstPayload['resolution_time']);
        $this->assertGreaterThanOrEqual(1, $firstPayload['duration_time']);
        $this->assertLessThanOrEqual(36, $firstPayload['duration_time']);
        $this->assertSame('Latest customer comment', $firstPayload['description']);

        $this->assertCount(15, $savedPredictions);

        $savedPrediction = ChurnPrediction::query()
            ->where('customer_id', $firstCustomer->getKey())
            ->firstOrFail();

        $this->assertSame($firstCustomer->customer_code, $savedPrediction->customer_code);
        $this->assertSame(2, $savedPrediction->complaints);
        $this->assertSame(5.0, (float) $savedPrediction->downtime_hours);
        $this->assertSame(4.0, (float) $savedPrediction->resolution_time);
        $this->assertTrue($savedPrediction->churn_prediction);
        $this->assertSame(0.91, (float) $savedPrediction->churn_probability);
        $this->assertSame('negative', $savedPrediction->sentiment_label);
        $this->assertSame(-1.0, (float) $savedPrediction->sentiment_score);
        $this->assertTrue($savedPrediction->predicted_at->equalTo(now()));

        Carbon::setTestNow();
    }

    public function test_it_works_with_any_number_of_customers(): void
    {
        Carbon::setTestNow('2026-05-29 12:00:00');

        $endpoint = 'https://churn-analysis-ai.onrender.com/predict/batch';
        config(['services.churn_analysis.batch_predict_url' => $endpoint]);

        $customer = $this->createCustomer(1);
        $ticket = $this->createTicket(
            customer: $customer,
            status: Ticket::STATUS_CLOSED,
            createdAt: Carbon::parse('2026-05-01 08:00:00'),
            resolvedAt: Carbon::parse('2026-05-01 12:00:00'),
            closedAt: Carbon::parse('2026-05-01 13:00:00'),
        );
        $this->createComment($ticket, 'Test comment', Carbon::parse('2026-05-01 09:00:00'));

        Http::fake([
            $endpoint => fn (Request $request) => Http::response([
                'predictions' => [
                    [
                        'customer_id' => $customer->customer_code,
                        'churn_prediction' => 0,
                        'churn_probability' => 0.12,
                        'sentiment_label' => 'neutral',
                        'sentiment_score' => 0,
                    ],
                ],
            ]),
        ]);

        $savedPredictions = app(ChurnAnalysisService::class)->predictAndSave(limit: 1);

        Http::assertSentCount(1);
        $this->assertCount(1, $savedPredictions);
        $this->assertSame($customer->customer_code, $savedPredictions->first()->customer_code);

        Carbon::setTestNow();
    }

    public function test_command_runs_prediction_batch(): void
    {
        $endpoint = 'https://churn-analysis-ai.onrender.com/predict/batch';
        config(['services.churn_analysis.batch_predict_url' => $endpoint]);

        for ($number = 1; $number <= 15; $number++) {
            $customer = $this->createCustomer($number);
            $ticket = $this->createTicket(
                customer: $customer,
                status: Ticket::STATUS_OPEN,
                createdAt: Carbon::parse('2026-05-04 08:00:00')->addHours($number),
            );
            $this->createComment($ticket, "Command customer {$number} comment", Carbon::parse('2026-05-04 09:00:00')->addHours($number));
        }

        Http::fake([
            $endpoint => fn (Request $request) => Http::response([
                'predictions' => collect($request->data()['customers'])->map(fn (array $customer): array => [
                    'customer_id' => $customer['customer_id'],
                    'churn_prediction' => 0,
                    'churn_probability' => 0.04,
                    'sentiment_label' => 'neutral',
                    'sentiment_score' => 0,
                ])->all(),
            ]),
        ]);

        $this->artisan('churn:predict')
            ->expectsOutput('Saved 15 churn predictions.')
            ->assertExitCode(0);

        $this->assertSame(15, ChurnPrediction::query()->count());
    }

    public function test_admin_can_view_churn_prediction_report(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);
        $customer = $this->createCustomer(1);

        ChurnPrediction::query()->create([
            'customer_id' => $customer->getKey(),
            'customer_code' => $customer->customer_code,
            'complaints' => 3,
            'downtime_hours' => 7.5,
            'resolution_time' => 2.5,
            'duration_time' => 12,
            'description' => 'Unhappy with repeated outages.',
            'churn_prediction' => true,
            'churn_probability' => 0.72,
            'sentiment_label' => 'negative',
            'sentiment_score' => -0.8,
            'predicted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/admin/churn-predictions')
            ->assertOk()
            ->assertSee($customer->customer_code);
    }

    protected function createCustomer(int $number): Customer
    {
        return Customer::query()->create([
            'customer_code' => sprintf('CUST%03d', $number),
            'name' => "Customer {$number}",
            'status' => Customer::STATUS_ACTIVE,
        ]);
    }

    protected function createTicket(
        Customer $customer,
        string $status,
        Carbon $createdAt,
        ?Carbon $resolvedAt = null,
        ?Carbon $closedAt = null,
    ): Ticket {
        $ticket = Ticket::query()->create([
            'customer_id' => $customer->getKey(),
            'ticket_category_id' => $this->ticketCategory()->getKey(),
            'ticket_no' => sprintf('TKT-%s-%s', $customer->customer_code, $createdAt->format('YmdHis')),
            'subject' => 'Connection issue',
            'description' => 'Customer reported connection issue.',
            'priority' => Ticket::PRIORITY_MEDIUM,
            'status' => $status,
            'reported_at' => $createdAt,
            'resolved_at' => $resolvedAt,
            'closed_at' => $closedAt,
        ]);

        $ticket->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $closedAt ?? $resolvedAt ?? $createdAt,
        ])->save();

        return $ticket;
    }

    protected function createComment(Ticket $ticket, string $comment, Carbon $createdAt): TicketComment
    {
        $ticketComment = TicketComment::query()->create([
            'ticket_id' => $ticket->getKey(),
            'comment' => $comment,
        ]);

        $ticketComment->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->save();

        return $ticketComment;
    }

    protected function ticketCategory(): TicketCategory
    {
        return TicketCategory::query()->firstOrCreate(
            ['name' => 'Connection Issue'],
            ['is_active' => true],
        );
    }
}
