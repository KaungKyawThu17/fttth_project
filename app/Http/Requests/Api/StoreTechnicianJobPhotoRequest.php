<?php

namespace App\Http\Requests\Api;

use App\Models\JobPhoto;
use App\Models\TechnicianJob;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTechnicianJobPhotoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $technicianJob = $this->route('technicianJob');

        return $technicianJob instanceof TechnicianJob
            && ($this->user()?->can('createForJob', [JobPhoto::class, $technicianJob]) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'photo' => ['required', 'image', 'max:5120'],
            'photo_type' => ['nullable', Rule::in(array_keys(JobPhoto::photoTypeOptions()))],
        ];
    }
}
