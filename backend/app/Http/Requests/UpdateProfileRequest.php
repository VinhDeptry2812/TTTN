<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'string|max:100',
            'phone' => 'nullable|string|max:15',
            'gender' => 'nullable|in:male,female,other',
            'birthday' => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'in' => 'Giới tính không hợp lệ.',
            'date' => 'Ngày sinh không đúng định dạng ngày tháng.',
            'max' => ':attribute quá dài.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Họ tên',
            'phone' => 'Số điện thoại',
        ];
    }
}
