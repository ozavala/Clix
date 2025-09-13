<?php

namespace App\Http\Requests;

use App\Models\CrmUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('crm_users', 'email')
                    ->where(fn ($q) => $q->where('tenant_id', $this->user()->tenant_id))
                    ->ignore($this->user()->user_id, 'user_id'),
            ],
            'locale' => ['required', 'string', 'max:5', Rule::in(['es', 'en'])],
        ];
    }
}
