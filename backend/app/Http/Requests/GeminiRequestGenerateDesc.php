<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class GeminiRequestGenerateDesc extends FormRequest
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
           'name' => 'required|string|max:255',
           'category' => 'required|string|max:255',
           'material' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên sản phẩm là bắt buộc.',
            'category.required' => 'Danh mục sản phẩm là bắt buộc.',
            'material.required' => 'Chất liệu sản phẩm là bắt buộc.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Tên sản phẩm',
            'category' => 'Danh mục sản phẩm',
            'material' => 'Chất liệu sản phẩm',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Lỗi validation dữ liệu',
            'errors' => $validator->errors()
        ], 422));
    }
}
