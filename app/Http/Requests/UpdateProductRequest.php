<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Adjust authorization as needed
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $productId = $this->route('product') ? $this->route('product')->product_id : null;

        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sku' => [
                'nullable', 'string', 'max:100',
                Rule::unique('products', 'sku')->ignore($productId, 'product_id'),
            ],
            'price' => 'required|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'quantity_on_hand' => 'required_if:is_service,false|integer|min:0',
            'is_service' => 'required|boolean',
            'is_active' => 'required|boolean',
            'features' => 'nullable|array',
            'features.*.feature_id' => 'required_with:features.*.value|exists:product_features,feature_id',
            'features.*.value' => 'required_with:features.*.feature_id|string|max:255',
            'inventory' => 'nullable|array',
            'inventory.*.quantity' => 'nullable|integer|min:0',
            'category_id' => 'nullable|integer|exists:product_categories,category_id',
        ];
    }
}