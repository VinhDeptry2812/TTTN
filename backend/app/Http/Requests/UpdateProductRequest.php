<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('id');
        return [
            // 'sometimes|required' có nghĩa là: 
            // - Nếu không gửi trường này lên Bỏ qua, giữ giá trị cũ.
            // - Nếu có gửi lên: Bắt buộc không được để trống (không được để "").
            'name' => "sometimes|required|string|max:255|unique:products,name,{$productId}",
            'category_id' => 'sometimes|required|integer|exists:categories,id',
            'base_price' => 'sometimes|required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'sku' => "sometimes|required|string|max:100|unique:products,sku,{$productId}",
            'material' => 'nullable|string|max:100',
            'brand' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'is_active' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        // Bắt buộc trả về JSON 422 thay vì redirect về HTML Swagger
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Lỗi validation dữ liệu',
            'errors' => $validator->errors()
        ], 422));
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute không được để trống khi cập nhật.',
            'unique' => ':attribute này đã được sử dụng.',
            'exists' => ':attribute không hợp lệ.',
            'numeric' => ':attribute phải là số.',
            'image' => ':attribute phải là một hình ảnh.',
            'max' => ':attribute không được vượt quá :max.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Tên sản phẩm',
            'category_id' => 'Danh mục',
            'base_price' => 'Giá sản phẩm',
            'sku' => 'Mã SKU',
            'image' => 'Ảnh sản phẩm',
        ];
    }
}
