<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Danh mục gốc
        $livingRoomId = DB::table('categories')->insertGetId([
            'name' => 'Phòng khách',
            'slug' => Str::slug('Phòng khách'),
            'parent_id' => null,
            'description' => 'Không gian đón tiếp khách với sofa, bàn trà, kệ tivi...',
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $bedroomId = DB::table('categories')->insertGetId([
            'name' => 'Phòng ngủ',
            'slug' => Str::slug('Phòng ngủ'),
            'parent_id' => null,
            'description' => 'Không gian nghỉ ngơi với giường, tủ quần áo, bàn trang điểm...',
            'is_active' => true,
            'sort_order' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $kitchenId = DB::table('categories')->insertGetId([
            'name' => 'Bếp & Phòng ăn',
            'slug' => Str::slug('Bếp & Phòng ăn'),
            'parent_id' => null,
            'description' => 'Không gian ấm cúng cho bữa ăn gia đình...',
            'is_active' => true,
            'sort_order' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Danh mục con của Phòng khách
        DB::table('categories')->insert([
            [
                'name' => 'Sofa',
                'slug' => Str::slug('Sofa'),
                'parent_id' => $livingRoomId,
                'description' => 'Các loại ghế sofa phòng khách',
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bàn trà',
                'slug' => Str::slug('Bàn trà'),
                'parent_id' => $livingRoomId,
                'description' => 'Bàn trà sofa hiện đại',
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Danh mục con của Phòng ngủ
        DB::table('categories')->insert([
            [
                'name' => 'Giường ngủ',
                'slug' => Str::slug('Giường ngủ'),
                'parent_id' => $bedroomId,
                'description' => 'Giường ngủ gỗ tự nhiên, hiện đại',
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Tủ quần áo',
                'slug' => Str::slug('Tủ quần áo'),
                'parent_id' => $bedroomId,
                'description' => 'Tủ quần áo đa năng',
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}