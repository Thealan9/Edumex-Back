<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreEbookRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->has('active') && count($this->all()) === 1) {
            return [
                'active' => 'required|boolean'
            ];
        }

        return [
            'title' => 'required|string|max:255',
            'isbn' => 'required|string|unique:ebooks,isbn,' . $this->ebook?->id,
            'level' => 'required|in:A1,A2,B1,B2,C1,C2',
            'category' => 'required|in:General English,Grammar & Vocabulary,Exam Preparation,Business English,Readers,Teacher Resources',
            'price' => 'required|numeric|min:0',
            'description' => 'required|string|max:1000',
            'autor' => 'required|string',
            'active' => 'nullable|boolean',
            'pages' => 'required|integer|min:1',
            'year' => 'required|integer',
            'edition' => 'required|integer|min:1',
            'supplier' => 'required|string',
        ];
    }
}
