<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
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
        // 1. Si la petición solo contiene 'active', validamos solo eso (para el toggle)
        if ($this->has('active') && count($this->all()) === 1) {
            return [
                'active' => 'required|boolean'
            ];
        }

        // 2. Reglas base para Store y Update completo
        return [
            'title'             => 'required|string|max:255',
            'isbn'              => [
                'required',
                'string',
                \Illuminate\Validation\Rule::unique('books', 'isbn')->ignore($this->book)
            ],
            'level'             => 'required|in:A1,A2,B1,B2,C1,C2',
            'price_unit'        => 'required|numeric|min:1',
            'units_per_package' => 'required|integer|min:1',
            'price_package'     => 'required|numeric|min:1',
            'autor'             => 'required|string',
            'pages'             => 'required|integer',
            'stock_alert'       => 'required|integer|min:1',
            'year'              => 'required|integer',
            'edition'           => 'required|integer',
            'format'            => 'required|in:Bolsillo,Tapa Blanda,Tapa Dura',
            'size'              => 'required|string',
            'supplier'          => 'required|string',
            'description'       => 'required|string|max:1000',
            'category'          => 'required|in:General English,Grammar & Vocabulary,Exam Preparation,Business English,Readers,Teacher Resources',
            'active'            => 'nullable|boolean',
        ];
    }
}
