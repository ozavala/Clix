<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCrmUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Set to true if authorization is handled by middleware or policies
        // For now, let's assume any authenticated user can update (adjust as needed)
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('user') ? $this->route('user')->user_id : null;

        return [
            'username' => [
                'required', 'string', 'max:100',
                Rule::unique('users', 'username')->ignore($userId, 'user_id'),
            ],
            'full_name' => 'required|string|max:255',
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($userId, 'user_id'),
            ],
            'password' => 'nullable|string|min:8|confirmed', // Password is optional on update
            'roles' => 'nullable|array',
            'roles.*' => 'integer|exists:user_roles,role_id',
        ];
    }
}
