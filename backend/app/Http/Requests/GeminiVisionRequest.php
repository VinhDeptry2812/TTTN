<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GeminiVisionRequest extends FormRequest
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
        return [
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120', // Tối đa 5MB
        ];
    }

    /**
     * Custom messages for validation errors.
     */
    public function messages()
    {
        return [
            'image.required' => 'Vui lòng cung cấp hình ảnh sản phẩm.',
            'image.image' => 'File tải lên phải là định dạng hình ảnh.',
            'image.mimes' => 'Hình ảnh chỉ hỗ trợ các định dạng: jpeg, png, jpg, webp.',
            'image.max' => 'Kích thước hình ảnh không được vượt quá 5MB.',
        ];
    }
}
