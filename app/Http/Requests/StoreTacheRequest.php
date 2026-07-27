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
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('tag_id') && !$this->has('tag_ids')) {
            $tagId = $this->input('tag_id');
            $this->merge([
                'tag_ids' => $tagId ? [$tagId] : []
            ]);
        }
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
            'status' => ['nullable', 'in:done,not done'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['exists:tags,id'],
            'due_date' => ['required', 'date'],
            'projet_id' => ['required', 'exists:projets,id'],
            'parent_task_id' => ['nullable', 'exists:taches,id'],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'board_column' => ['nullable', 'string', 'max:255'],
        ];
    }
}
