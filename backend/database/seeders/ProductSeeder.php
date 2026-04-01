<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Lấy ID category
        $categories = [
            'Sofa' => DB::table('categories')->where('name', 'Sofa')->value('id'),
            'Giường ngủ' => DB::table('categories')->where('name', 'Giường ngủ')->value('id'),
            'Bàn trà' => DB::table('categories')->where('name', 'Bàn trà')->value('id'),
        ];

        $brands = ['Casa Italia', 'NoiThatViet', 'LuxHome', 'ModernLife', 'WoodenArt'];

        $materials = [
            'Gỗ sồi',
            'Gỗ gõ đỏ',
            'Da bò thật',
            'Vải nỉ',
            'Sắt sơn tĩnh điện',
            'Đá Marble'
        ];

        $colors = ['Xám', 'Xanh Dương', 'Trắng', 'Đen', 'Vàng Sồi'];
        $sizes = ['80cm', '1m2', '1m6', '2m'];

        $productTypes = [
            'Sofa' => ['Sofa Góc L', 'Sofa Băng Decor', 'Sofa Giường', 'Sofa Đơn', 'Sofa Tân Cổ Điển'],
            'Giường ngủ' => ['Giường Gỗ Hiện Đại', 'Giường Bọc Da', 'Giường Tầng', 'Giường King Size', 'Giường Nhật'],
            'Bàn trà' => ['Bàn Trà Tròn', 'Bàn Trà Đôi', 'Bàn Trà Mặt Kính', 'Bàn Trà Gỗ', 'Bàn Trà Thông Minh'],
        ];

        for ($i = 1; $i <= 40; $i++) {

            // random category
            $categoryName = array_rand($productTypes);
            $categoryId = $categories[$categoryName];

            // random type
            $typeName = $productTypes[$categoryName][array_rand($productTypes[$categoryName])];

            $productName = $typeName . " Model " . Str::upper(Str::random(3)) . "-" . $i;

            $basePrice = rand(20, 100) * 500000;
            $salePrice = rand(0, 1) ? $basePrice * 0.9 : null;

            $prefixMap = [
                'Sofa' => 'SOF',
                'Giường ngủ' => 'BED',
                'Bàn trà' => 'TAB'
            ];

            $prefix = $prefixMap[$categoryName];

            $sku = $prefix . "-SKU-" . str_pad($i, 3, '0', STR_PAD_LEFT);

            // insert product
            $productId = DB::table('products')->insertGetId([
                'category_id' => $categoryId,
                'name' => $productName,
                'slug' => Str::slug($productName) . '-' . Str::random(5),
                'sku' => $sku,
                'description' => "Mô tả cho sản phẩm $productName. Thiết kế hiện đại, phù hợp cho không gian nội thất sang trọng.",
                'brand' => $brands[array_rand($brands)],
                'weight' => rand(10, 80),
                'base_price' => $basePrice,
                'sale_price' => $salePrice,
                'is_featured' => rand(0, 1),
                'is_active' => 1,
                'view_count' => rand(0, 100),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // tạo variant
            $variantCount = rand(1, 3);

            for ($j = 1; $j <= $variantCount; $j++) {

                DB::table('product_variants')->insert([
                    'product_id' => $productId,
                    'color' => $colors[array_rand($colors)],
                    'size' => $sizes[array_rand($sizes)],
                    'material' => $materials[array_rand($materials)],
                    'price' => $salePrice ?? $basePrice,
                    'stock_quantity' => rand(5, 50),
                    'sku' => $sku . "-VAR-" . $j,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info('Đã tạo xong 40 sản phẩm và variants!');
    }
}