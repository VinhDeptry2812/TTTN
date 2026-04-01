<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiVisionService
{
  protected $apiKey;
  protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3-flash-preview:generateContent';

  public function __construct()
  {
    $this->apiKey = config('services.gemini.key');
  }

  /**
   * Nhận diện thông tin sản phẩm từ hình ảnh
   * 
   * @param string $imagePath Đường dẫn vật lý của file ảnh hoặc nội dung file
   * @param string $mimeType Định dạng ảnh (image/jpeg, image/png...)
   * @return array|null
   */
  public function identifyProductFromImage($imagePath, $mimeType = 'image/jpeg')
  {
    $isLocal = app()->environment('local');

    // Đọc ảnh và chuyển sang Base64
    $imageData = base64_encode(file_get_contents($imagePath));

    $prompt = "Bạn là chuyên gia phân tích sản phẩm nội thất cao cấp. Hãy phân tích hình ảnh này.
                   BƯỚC 1: Xác định xem vật thể chính trong ảnh có phải là đồ nội thất hay không.
                   BƯỚC 2: Trả về kết quả dưới dạng JSON với cấu trúc sau:
                   - Nếu là đồ nội thất (is_furniture: true):
                     {
                         \"is_furniture\": true,
                         \"name\": \"Tên mẫu sản phẩm chung (ví dụ: Sofa Ý Luxury)\",
                         \"category\": \"Danh mục (Ghế, Bàn, Sofa...)\",
                         \"style\": \"Phong cách thiết kế (Hiện đại, Cổ điển...)\",
                         \"description_raw\": \"Mô tả đầy đủ nhưng không quá dài về đặc điểm của mẫu này\"
                         \"material\": \"Chất liệu cụ thể thấy trong ảnh\",
                         \"color\": \"Màu sắc cụ thể thấy trong ảnh\",
                         \"weight_kg\": \"Trọng lượng của mẫu này(kg)\",
                         \"dimensions\": \"Kích thước của mẫu này\",
                         \"finish\": \"màu hoàn thiện bề mặt của mẫu này\",
                         \"size\": \"kích thước của mẫu này (Ví dụ: 1m2x2m, 80cm,...)\",
                         \"width_cm\": \"Chiều rộng của mẫu này(cm)\",
                         \"depth_cm\": \"Chiều sâu/Chiều dài (cm)\",
                         \"height_cm\": \"Chiều cao (cm)\",
                         \"seat_height_cm\": \"Chiều cao mặt ghế (cm)\",
                         \"price\": \"Giá của mẫu này(VND)\",
                     }
                   - Nếu KHÔNG phải đồ nội thất:
                     {
                       \"is_furniture\": false,
                       \"error\": \"Thông báo lỗi bằng tiếng Việt\"
                     }
                   Chỉ trả về JSON, không thêm lời dẫn.";

    $response = Http::when($isLocal, function ($http) {
      return $http->withoutVerifying();
    })->post("{$this->baseUrl}?key={$this->apiKey}", [
          'contents' => [
            [
              'parts' => [
                ['text' => $prompt],
                [
                  'inline_data' => [
                    'mime_type' => $mimeType,
                    'data' => $imageData
                  ]
                ]
              ]
            ]
          ]
        ]);

    if ($response->successful()) {
      $text = $response->json('candidates.0.content.parts.0.text');
      // Làm sạch code block markdown nếu có
      $jsonStr = str_replace(['```json', '```'], '', $text);
      return json_decode(trim($jsonStr), true);
    }

    Log::error('Gemini Vision API Error:', [
      'status' => $response->status(),
      'body' => $response->json(),
    ]);

    return null;
  }
}
