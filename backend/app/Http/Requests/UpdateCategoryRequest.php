<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'name' => 'required|string|max:100',
            'parent_id' => [
                'nullable',
                'exists:categories,id',
                function ($attribute, $value, $fail) use ($id) {
                    if ($value == $id) {
                        $fail('Không thể chọn chính danh mục này làm danh mục cha.');
                    }
                },
            ],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer'
        ];
    }
}
