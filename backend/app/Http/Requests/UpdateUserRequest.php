<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Admin middleware đã check, nên ở đây trả về true
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'string|max:255',
            'phone' => 'nullable|string|max:15',
            'gender' => 'nullable|in:male,female,other',
            'birthday' => 'nullable|date',
            'is_active' => 'boolean'
        ];
    }
}
