<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBillRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Or add specific authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'tenant_id' => 'nullable|exists:tenants,tenant_id',
            'purchase_order_id' => 'nullable|exists:purchase_orders,purchase_order_id',
            'supplier_id' => 'required|exists:suppliers,supplier_id',
            'bill_number' => 'required|string|max:255',
            'bill_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:bill_date',
            'tax_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.purchase_order_item_id' => 'nullable|exists:purchase_order_items,purchase_order_item_id',
            'items.*.product_id' => 'nullable|exists:products,product_id',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.item_description' => 'nullable|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ];
    }
}