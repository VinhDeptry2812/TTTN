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
        $livingRoom = [
            'name' => 'Phòng khách',
            'slug' => Str::slug('Phòng khách'),
            'parent_id' => null,
            'description' => 'Không gian đón tiếp khách với sofa, bàn trà, kệ tivi...',
            'is_active' => true,
            'sort_order' => 1,
            'updated_at' => now(),
        ];
        DB::table('categories')->updateOrInsert(['slug' => $livingRoom['slug']], array_merge($livingRoom, ['created_at' => now()]));
        $livingRoomId = DB::table('categories')->where('slug', $livingRoom['slug'])->value('id');

        $bedroom = [
            'name' => 'Phòng ngủ',
            'slug' => Str::slug('Phòng ngủ'),
            'parent_id' => null,
            'description' => 'Không gian nghỉ ngơi với giường, tủ quần áo, bàn trang điểm...',
            'is_active' => true,
            'sort_order' => 2,
            'updated_at' => now(),
        ];
        DB::table('categories')->updateOrInsert(['slug' => $bedroom['slug']], array_merge($bedroom, ['created_at' => now()]));
        $bedroomId = DB::table('categories')->where('slug', $bedroom['slug'])->value('id');

        $kitchen = [
            'name' => 'Bếp & Phòng ăn',
            'slug' => Str::slug('Bếp & Phòng ăn'),
            'parent_id' => null,
            'description' => 'Không gian ấm cúng cho bữa ăn gia đình...',
            'is_active' => true,
            'sort_order' => 3,
            'updated_at' => now(),
        ];
        DB::table('categories')->updateOrInsert(['slug' => $kitchen['slug']], array_merge($kitchen, ['created_at' => now()]));

        // Danh mục con của Phòng khách
        $subCategories = [
            [
                'name' => 'Sofa',
                'slug' => Str::slug('Sofa'),
                'parent_id' => $livingRoomId,
                'description' => 'Các loại ghế sofa phòng khách',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Bàn trà',
                'slug' => Str::slug('Bàn trà'),
                'parent_id' => $livingRoomId,
                'description' => 'Bàn trà sofa hiện đại',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Giường ngủ',
                'slug' => Str::slug('Giường ngủ'),
                'parent_id' => $bedroomId,
                'description' => 'Giường ngủ gỗ tự nhiên, hiện đại',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Tủ quần áo',
                'slug' => Str::slug('Tủ quần áo'),
                'parent_id' => $bedroomId,
                'description' => 'Tủ quần áo đa năng',
                'is_active' => true,
                'sort_order' => 2,
            ],
        ];

        foreach ($subCategories as $sub) {
            DB::table('categories')->updateOrInsert(
                ['slug' => $sub['slug']],
                array_merge($sub, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}