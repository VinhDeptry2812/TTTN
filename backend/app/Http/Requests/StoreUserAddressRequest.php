<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'receiver_name' => 'required|string|max:255',
            'receiver_phone' => 'required|string|max:15',
            'province_id' => 'nullable|integer',
            'district_id' => 'nullable|integer',
            'ward_id' => 'nullable|integer',
            'address_detail' => 'required|string|max:255',
            'is_default' => 'boolean',
            'type' => 'string|in:home,office,other'
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute không được để trống.',
            'in' => ':attribute không hợp lệ (home, office, other).',
        ];
    }

    public function attributes(): array
    {
        return [
            'receiver_name' => 'Tên người nhận',
            'receiver_phone' => 'Số điện thoại',
            'address_detail' => 'Địa chỉ chi tiết',
            'type' => 'Loại địa chỉ',
        ];
    }
}
