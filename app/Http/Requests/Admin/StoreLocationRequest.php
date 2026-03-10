<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreLocationRequest extends FormRequest
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
            'code'         => 'required|string|max:50|unique:locations,code',
            'max_capacity' => 'required|integer|min:1',
            'description'  => 'nullable|string|max:255'
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'Ya existe un estante con este código identificador.',
            'max_capacity.min' => 'La capacidad mínima debe ser de al menos 1 unidad.',
        ];
    }
}
