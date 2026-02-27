<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TenantUpdateRequest extends FormRequest
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
        $tenant = $this->route('tenant');
        $currentDomainId = $tenant->domains()->first()?->id;

        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'domain' => [
                'required',
                'string',
                'max:255',
                Rule::unique('domains', 'domain')->ignore($currentDomainId),
            ],
            'description' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_id.required' => 'Tenant ID is required.',
            'tenant_id.unique' => 'Tenant ID is already taken.',
            'name.required' => 'Tenant name is required.',
            'email.required' => 'Tenant email is required.',
            'email.email' => 'Please enter a valid email address.',
            'domain.required' => 'Domain is required.',
            'domain.unique' => 'Domain is already taken.',
        ];
    }
}
