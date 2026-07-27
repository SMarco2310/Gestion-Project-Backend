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
            'title' => ['sometimes', 'string', 'max:255'],
            'reference_code' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'priority' => ['sometimes', 'in:faible,moyen,élevé'],
            'status' => ['sometimes', 'in:done,not done'],
            'tag_ids' => ['sometimes', 'nullable', 'array'],
            'tag_ids.*' => ['exists:tags,id'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'projet_id' => ['sometimes', 'nullable', 'exists:projets,id'],
            'banner_image' => ['sometimes', 'nullable', 'string'],
            'assignee_id' => ['sometimes', 'nullable', 'exists:users,id'],
            'board_column' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
