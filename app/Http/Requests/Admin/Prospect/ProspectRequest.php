<?php

namespace App\Http\Requests\Admin\Prospect;

use App\Models\SmartLink\Prospect;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProspectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:180'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'website' => ['nullable', 'string', 'max:200'],
            'email' => ['nullable', 'email', 'max:180'],
            'phone' => ['nullable', 'string', 'max:30'],
            'industry' => ['nullable', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:150'],
            'offer' => ['nullable', 'string', 'max:200'],
            'personalized_message' => ['nullable', 'string', 'max:5000'],
            'salesperson_id' => ['nullable', 'exists:users,id'],
            'status' => ['nullable', Rule::in(Prospect::STATUSES)],
            'template_id' => ['required', 'exists:smart_page_templates,id'],
        ];
    }
}
