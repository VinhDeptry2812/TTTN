<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            // Dùng 'sometimes' để các trường không bắt buộc phải gửi hết
            'name' => "sometimes|required|string|max:255|unique:products,name,{$productId}",
            'category_id' => 'sometimes|required|integer|exists:categories,id',
            'base_price' => 'sometimes|required|numeric|min:0',
            'sale_price' => 'nullable|numeric|lt:base_price|min:0',
            'sku' => "sometimes|required|string|max:100|unique:products,sku,{$productId}",
            'material' => 'nullable|string|max:100',
            'brand' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'is_active' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Tên sản phẩm này đã được sử dụng.',
            'category_id.exists' => 'Danh mục không hợp lệ.',
            'base_price.numeric' => 'Giá sản phẩm phải là số.',
            'sale_price.lt' => 'Giá khuyến mãi phải nhỏ hơn giá gốc.',
            'sku.unique' => 'Mã SKU này đã được sử dụng.',
            'image.image' => 'File tải lên phải là một hình ảnh.',
            'image.max' => 'Ảnh không được vượt quá 2MB.',
        ];
    }
}
