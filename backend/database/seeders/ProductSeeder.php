<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $sofaId = DB::table('categories')->where('name', 'Sofa')->first()->id;
        $bedId = DB::table('categories')->where('name', 'Giường ngủ')->first()->id;
        $teaTableId = DB::table('categories')->where('name', 'Bàn trà')->first()->id;

        // Sofa
        $p1 = DB::table('products')->insertGetId([
            'category_id' => $sofaId,
            'name' => 'Sofa Da Cao Cấp Italy',
            'slug' => Str::slug('Sofa Da Cao Cấp Italy'),
            'sku' => 'SOFA-ITA-001',
            'description' => 'Sofa da bò thật nhập khẩu từ Ý, khung gỗ sồi bền bỉ, mang lại sự sang trọng cho phòng khách.',
            'base_price' => 25000000,
            'sale_price' => 22000000,
            'material' => 'Da bò, Gỗ sồi',
            'brand' => 'Casa Italia',
            'is_featured' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_variants')->insert([
            'product_id' => $p1,
            'color' => 'Nâu cà phê',
            'size' => '2.8m x 1.7m',
            'price' => 22000000,
            'stock_quantity' => 5,
            'sku' => 'SOFA-ITA-BRN',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Giường
        $p2 = DB::table('products')->insertGetId([
            'category_id' => $bedId,
            'name' => 'Giường Ngủ Gỗ Gõ Đỏ',
            'slug' => Str::slug('Giường Ngủ Gỗ Gõ Đỏ'),
            'sku' => 'BED-RED-002',
            'description' => 'Giường ngủ được làm từ gỗ gõ đỏ tự nhiên 100%, thiết kế cổ điển bền đẹp theo thời gian.',
            'base_price' => 15000000,
            'sale_price' => 13500000,
            'material' => 'Gỗ Gõ Đỏ',
            'brand' => 'NoiThatViet',
            'is_featured' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_variants')->insert([
            'product_id' => $p2,
            'color' => 'Gỗ tự nhiên',
            'size' => '1.8m x 2m',
            'price' => 13500000,
            'stock_quantity' => 8,
            'sku' => 'BED-RED-18',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Bàn trà
        $p3 = DB::table('products')->insertGetId([
            'category_id' => $teaTableId,
            'name' => 'Bàn Trà Kim Cương Mặt Đá',
            'slug' => Str::slug('Bàn Trà Kim Cương Mặt Đá'),
            'sku' => 'TAB-DIA-003',
            'description' => 'Bàn trà với khung sắt sơn tĩnh điện mạ vàng và mặt đá ceramic chống thấm cao cấp.',
            'base_price' => 3500000,
            'sale_price' => null,
            'material' => 'Sắt mạ vàng, Đá Ceramic',
            'brand' => 'LuxHome',
            'is_featured' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_variants')->insert([
            'product_id' => $p3,
            'color' => 'Trắng/Vàng',
            'size' => 'D80cm',
            'price' => 3500000,
            'stock_quantity' => 15,
            'sku' => 'TAB-DIA-WHT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
