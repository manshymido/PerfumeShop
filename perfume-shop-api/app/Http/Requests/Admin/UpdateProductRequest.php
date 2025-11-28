<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
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
        $productId = $this->route('id');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'sku' => ['sometimes', 'string', 'unique:products,sku,' . $productId],
            'brand' => ['nullable', 'string', 'max:255'],
            'fragrance_notes' => ['nullable', 'string'],
            'size' => ['nullable', 'string'],
            'category_id' => ['sometimes', 'exists:categories,id'],
        ];
    }
}

