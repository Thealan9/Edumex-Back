<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreDiscountRequest extends FormRequest
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
            'min_quantity'        => 'required|integer|min:1',
            'max_quantity'        => 'nullable|integer|gt:min_quantity',
            'discount_percentage' => 'required|numeric|min:0|max:100',
            'is_institutional'    => 'required|boolean', // Si es solo para colegios/instituciones
        ];
    }
}
