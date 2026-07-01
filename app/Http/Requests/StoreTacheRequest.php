<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTacheRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['nullable', 'in:faible,moyen,élevé'],
            'status' => ['nullable', 'in:à faire,en cours,terminé'],
            'tag_id' => ['nullable', 'exists:tags,id'],
            'due_date' => ['required', 'date'],
            'projet_id' => ['required', 'exists:projets,id'],
            'parent_task_id' => ['nullable', 'exists:taches,id'],
        ];
    }
}
