<?php

namespace App\Http\Requests\Api;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCustomerTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User
            && $user->isCustomer()
            && $user->customerProfileId() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ticket_category_id' => [
                'required',
                Rule::exists('ticket_categories', 'id')->where('is_active', true),
            ],
            'subject' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:5000'],
            'priority' => ['nullable', Rule::in(array_keys(Ticket::priorityOptions()))],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->user();

            if (! $user instanceof User) {
                return;
            }

            $customerId = $user->customerProfileId();

            if ($customerId === null) {
                return;
            }

            $hasActiveTicket = Ticket::query()
                ->where('customer_id', $customerId)
                ->whereIn('status', [
                    Ticket::STATUS_OPEN,
                    Ticket::STATUS_ASSIGNED,
                    Ticket::STATUS_IN_PROGRESS,
                ])
                ->exists();

            if ($hasActiveTicket) {
                $validator->errors()->add(
                    'ticket',
                    'You already have an active ticket. Please wait until it is resolved before creating a new one.',
                );
            }
        });
    }
}
