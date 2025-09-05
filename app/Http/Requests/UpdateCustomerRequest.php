<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled by the controller's Gate::authorize method.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $customerId = $this->route('customer')->customer_id;

        return [
            'type' => ['required', Rule::in(['Person', 'Company'])],
            'first_name' => ['required_if:type,Person', 'nullable', 'string', 'max:100'],
            'last_name' => ['required_if:type,Person', 'nullable', 'string', 'max:100'],
            'company_name' => ['required_if:type,Company', 'nullable', 'string', 'max:255'],
            'legal_id' => ['required', 'string', 'max:100', Rule::unique('customers', 'legal_id')->ignore($customerId, 'customer_id')],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('customers', 'email')->ignore($customerId, 'customer_id')],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'string', Rule::in(['Active', 'Inactive', 'Lead', 'Prospect'])],
            'addresses' => ['nullable', 'array'],
            'addresses.*.address_id' => ['nullable', 'integer', 'exists:addresses,address_id'],
            'addresses.*.address_type' => ['nullable', 'string', 'max:255'],
            'addresses.*.street_address_line_1' => ['nullable', 'string', 'max:255'],
            'addresses.*.street_address_line_2' => ['nullable', 'string', 'max:255'],
            'addresses.*.city' => ['nullable', 'string', 'max:255'],
            'addresses.*.state_province' => ['nullable', 'string', 'max:255'],
            'addresses.*.postal_code' => ['nullable', 'string', 'max:20'],
            'addresses.*.country_code' => ['nullable', 'string', 'max:3'],
            'addresses.*.is_primary' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->input('type') === 'Person') {
            $this->merge([
                'company_name' => null,
            ]);
        }

        if ($this->input('type') === 'Company') {
            $this->merge([
                'first_name' => null,
                'last_name' => null,
            ]);
        }
    }
}