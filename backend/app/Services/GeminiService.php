<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $apiKey;
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3-flash-preview:generateContent';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key');
    }

    public function generateDescription($productName, $category = '', $material = '')
    {
        $prompt = "Bạn là một chuyên gia viết nội dung quảng cáo nội thất cao cấp. 
                   Hãy viết một mô tả sản phẩm hấp dẫn, sang trọng cho sản phẩm sau:
                   Tên: $productName
                   Danh mục: $category
                   Chất liệu: $material

                   QUY ĐỊNH ĐỊNH DẠNG (BẮT BUỘC):
                   1. PHẢI chia nội dung thành ít nhất 3-4 đoạn văn rõ ràng.
                   2. Giữa các đoạn văn PHẢI cách nhau bởi 1 dòng trống (dùng 2 lần xuống dòng).
                   3. Mỗi gạch đầu dòng (-) PHẢI nằm trên một dòng riêng biệt.
                   4. In đậm các tiêu đề mục hoặc từ khóa quan trọng bằng dấu ** (ví dụ: **Chất liệu**).
                   5. Tuyệt đối KHÔNG viết tất cả nội dung thành một khối văn bản duy nhất.
                   6. Ngôn ngữ tiếng Việt chuyên nghiệp, sang trọng. Chỉ trả về nội dung mô tả sản phẩm.";

        $response = Http::withoutVerifying()->post("{$this->baseUrl}?key={$this->apiKey}", [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ]);

        if ($response->successful()) {
            return $response->json('candidates.0.content.parts.0.text');
        }

        Log::error('Gemini API Error:', [
            'status' => $response->status(),
            'body' => $response->json(),
        ]);

        return null;
    }
}
