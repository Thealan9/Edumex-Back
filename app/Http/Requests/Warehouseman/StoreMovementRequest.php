<?php

namespace App\Http\Requests\Warehouseman;

use Illuminate\Foundation\Http\FormRequest;

class StoreMovementRequest extends FormRequest
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
            'book_id'     => 'required|exists:books,id',
            'location_id' => 'required|exists:locations,id',
            'type'        => 'required|in:input,output,adjustment,return',
            'quantity'    => 'required|integer|min:1',
            'description' => 'required|string|max:255',
        ];
    }
}
