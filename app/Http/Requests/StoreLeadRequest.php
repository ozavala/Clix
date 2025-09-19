<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Adjust authorization as needed (e.g., based on user role/permission)
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Consider making these statuses and sources configurable or from a DB table later
        $validStatuses = ['New', 'Contacted', 'Qualified', 'Proposal Sent', 'Negotiation', 'Won', 'Lost', 'On Hold'];
        $validSources = ['Website', 'Referral', 'Cold Call', 'Advertisement', 'Event', 'Other'];

        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'value' => 'nullable|numeric|min:0',
            'status' => 'required|string|in:' . implode(',', $validStatuses),
            'source' => 'nullable|string|in:' . implode(',', $validSources),
            'customer_id' => 'nullable|exists:customers,customer_id',
            'contact_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'assigned_to_user_id' => 'nullable|exists:users,user_id',
            'expected_close_date' => 'nullable|date',
            // created_by_user_id will be set automatically
        ];
    }
}