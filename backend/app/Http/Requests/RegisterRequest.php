<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email:rfc,dns|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute không được để trống.',
            'email' => ':attribute không đúng định dạng.',
            'min' => ':attribute phải có ít nhất :min ký tự.',
            'unique' => ':attribute đã tồn tại trong hệ thống.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Họ tên',
            'email' => 'Email',
            'password' => 'Mật khẩu',
        ];
    }
}
