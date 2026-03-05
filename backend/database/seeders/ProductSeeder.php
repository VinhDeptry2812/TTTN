<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Lấy ID của các category hiện có
        $categories = [
            'Sofa' => DB::table('categories')->where('name', 'Sofa')->value('id'),
            'Giường ngủ' => DB::table('categories')->where('name', 'Giường ngủ')->value('id'),
            'Bàn trà' => DB::table('categories')->where('name', 'Bàn trà')->value('id'),
        ];

        // 2. Dữ liệu thô để trộn ngẫu nhiên
        $brands = ['Casa Italia', 'NoiThatViet', 'LuxHome', 'ModernLife', 'WoodenArt'];
        $materials = ['Gỗ sồi', 'Gỗ gõ đỏ', 'Da bò thật', 'Vải nỉ', 'Sắt sơn tĩnh điện', 'Đá Marble'];
        
        $productTypes = [
            'Sofa' => ['Sofa Góc L', 'Sofa Băng Decor', 'Sofa Giường Thông Minh', 'Sofa Đơn Thư Giãn', 'Sofa Tân Cổ Điển'],
            'Giường ngủ' => ['Giường Gỗ Hiện Đại', 'Giường Bọc Da', 'Giường Tầng Trẻ Em', 'Giường King Size', 'Giường Nhật Bản'],
            'Bàn trà' => ['Bàn Trà Tròn', 'Bàn Trà Đôi', 'Bàn Trà Mặt Kính', 'Bàn Trà Gỗ Nguyên Khối', 'Bàn Trà Thông Minh'],
        ];

        // 3. Vòng lặp tạo 40 sản phẩm
        for ($i = 1; $i <= 40; $i++) {
            // Chọn ngẫu nhiên một loại danh mục
            $categoryName = array_rand($productTypes);
            $categoryId = $categories[$categoryName];
            
            // Tạo tên sản phẩm ngẫu nhiên từ danh sách loại sản phẩm
            $typeName = $productTypes[$categoryName][array_rand($productTypes[$categoryName])];
            $productName = $typeName . " Model " . Str::upper(Str::random(3)) . "-" . $i;
            
            $basePrice = rand(20, 100) * 500000; // Giá từ 10tr đến 50tr
            $salePrice = rand(0, 1) ? $basePrice * 0.9 : null; // 50% cơ hội có giảm giá 10%
            $sku = Str::upper(Str::substr($categoryName, 0, 3)) . "-SKU-" . str_pad($i, 3, '0', STR_PAD_LEFT);

            // Insert vào bảng products
            $productId = DB::table('products')->insertGetId([
                'category_id' => $categoryId,
                'name' => $productName,
                'slug' => Str::slug($productName) . '-' . time() . $i,
                'sku' => $sku,
                'description' => "Mô tả cho sản phẩm $productName: Chất lượng cao cấp, thiết kế hiện đại phù hợp cho mọi không gian nội thất.",
                'base_price' => $basePrice,
                'sale_price' => $salePrice,
                'material' => $materials[array_rand($materials)],
                'brand' => $brands[array_rand($brands)],
                'is_featured' => (bool)rand(0, 1),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 4. Tạo 1-2 biến thể (variant) cho mỗi sản phẩm
            $colors = ['Xám', 'Xanh Dương', 'Trắng', 'Đen', 'Vàng Sồi'];
            $sizes = ['S', 'M', 'L', 'Standard'];

            for ($j = 1; $j <= rand(1, 2); $j++) {
                DB::table('product_variants')->insert([
                    'product_id' => $productId,
                    'sku' => $sku . "-VAR-" . $j,
                    'color' => $colors[array_rand($colors)],
                    'size' => $sizes[array_rand($sizes)],
                    'price' => $salePrice ?? $basePrice,
                    'stock_quantity' => rand(5, 50),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info('Đã tạo xong 40 sản phẩm mẫu thành công!');
    }
}