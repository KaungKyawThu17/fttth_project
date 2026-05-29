<?php

namespace App\Http\Requests\Api;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
}
