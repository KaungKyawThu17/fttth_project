<?php

namespace App\Http\Requests\Api;

use App\Models\TechnicianJob;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartTechnicianJobRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $technicianJob = $this->route('technicianJob');

        return $technicianJob instanceof TechnicianJob
            && ($this->user()?->can('start', $technicianJob) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'estimated_arrival_at' => ['required', Rule::date()->format('Y-m-d')],
        ];
    }
}
