<?php

namespace App\Http\Requests;

use App\Models\Opportunity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOpportunityRequest extends FormRequest
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
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'lead_id' => 'nullable|exists:leads,lead_id',// If converted from a lead
            'contact_id' => 'nullable|exists:contacts,contact_id', // Added validation for contact_id
            'customer_id' => 'required|exists:customers,customer_id',
            'stage' => ['required', 'string', Rule::in(array_keys(Opportunity::$stages))],
            'amount' => 'nullable|numeric|min:0',
            'expected_close_date' => 'nullable|date',
            'probability' => 'nullable|integer|min:0|max:100',
            'assigned_to_user_id' => 'required|exists:users,user_id',
        ];
    }
}