<?php

namespace App\Http\Requests\Api;

use App\Models\TechnicianJob;
use Illuminate\Foundation\Http\FormRequest;

class CompleteTechnicianJobRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $technicianJob = $this->route('technicianJob');

        return $technicianJob instanceof TechnicianJob
            && ($this->user()?->can('complete', $technicianJob) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'comment' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
