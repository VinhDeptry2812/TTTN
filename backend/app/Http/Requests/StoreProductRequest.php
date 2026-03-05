<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Xác định xem người dùng nào được quyền gửi Request này.
     */
    public function authorize(): bool
    {
        // Bất cứ ai đã pass Middleware đều được phép (Admin)
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:products,name',
            'category_id' => 'required|integer|exists:categories,id',
            'base_price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|lt:base_price|min:0',
            'sku' => 'required|string|max:100|unique:products,sku',
            'material' => 'nullable|string|max:100',
            'brand' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            // Rule quan trọng cho File ảnh
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'is_active' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên sản phẩm không được để trống.',
            'name.unique' => 'Tên sản phẩm này đã tồn tại.',
            'category_id.required' => 'Chưa chọn danh mục sản phẩm.',
            'category_id.exists' => 'Danh mục đã chọn không hợp lệ.',
            'base_price.required' => 'Giá sản phẩm không được để trống.',
            'base_price.numeric' => 'Giá sản phẩm phải là số.',
            'sale_price.lt' => 'Giá khuyến mãi phải nhỏ hơn giá gốc.',
            'sku.required' => 'Mã SKU không được để trống.',
            'sku.unique' => 'Mã SKU này đã được sử dụng.',
            'image.image' => 'File tải lên phải là một hình ảnh.',
            'image.mimes' => 'Hình ảnh phải có định dạng jpeg, png, jpg hoặc webp.',
            'image.max' => 'Bộ nhớ hình ảnh không được vượt quá 2MB.',
        ];
    }
}
