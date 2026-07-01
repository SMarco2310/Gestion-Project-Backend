<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTacheRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'reference_code' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'priority' => ['sometimes', 'in:faible,moyen,élevé'],
            'status' => ['sometimes', 'in:à faire,en cours,terminé'],
            'tag_id' => ['sometimes', 'nullable', 'exists:tags,id'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'projet_id' => ['sometimes', 'nullable', 'exists:projets,id'],
            'banner_image' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
