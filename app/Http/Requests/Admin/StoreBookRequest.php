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
        return [
            'title'             => 'required|string|max:255',
            'isbn'              => 'required|string|unique:books,isbn',
            'level'             => 'required|in:A1,A2,B1,B2,C1,C2',
            'cost'              => 'required|numeric|min:0',
            'price_unit'        => 'required|numeric|min:0',
            'units_per_package' => 'required|integer|min:1',
            'price_package'     => 'nullable|numeric|min:0',
            'autor'             => 'required|string',
            'pages'             => 'required|integer',
            'year'              => 'required|integer',
            'edition'           => 'required|integer',
            'format'            => 'required|in:Bolsillo,Tapa Blanda,Tapa Dura',
            'size'              => 'required|string',
            'supplier'          => 'required|string',
            'description'       => 'required|string|max:255',
        ];
    }
}
