<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            // --- Thuộc tính chất liệu ---
            $table->string('wood_type', 100)->nullable()->after('color')
                ->comment('Loại gỗ: Gỗ sồi, Gỗ óc chó, Gỗ thông, MDF...');
            $table->string('upholstery', 100)->nullable()->after('wood_type')
                ->comment('Chất liệu bọc: Da bò, Vải nhung, Vải bố...');
            $table->string('finish', 100)->nullable()->after('upholstery')
                ->comment('Màu hoàn thiện bề mặt: Tự nhiên, Walnut, Trắng sữa...');

            // --- Kích thước chi tiết (cm) ---
            $table->decimal('width_cm', 7, 1)->nullable()->after('size')
                ->comment('Chiều rộng (cm)');
            $table->decimal('depth_cm', 7, 1)->nullable()->after('width_cm')
                ->comment('Chiều sâu / Chiều dài (cm)');
            $table->decimal('height_cm', 7, 1)->nullable()->after('depth_cm')
                ->comment('Chiều cao (cm)');
            $table->decimal('weight_kg', 7, 2)->nullable()->after('height_cm')
                ->comment('Trọng lượng (kg)');
            $table->string('seat_height_cm', 20)->nullable()->after('weight_kg')
                ->comment('Chiều cao chỗ ngồi - dành cho ghế, sofa (cm)');

            // --- Thông tin thêm ---
            $table->string('image_url')->nullable()->after('sku')
                ->comment('Ảnh riêng của biến thể này');
            $table->boolean('is_available')->default(true)->after('image_url')
                ->comment('Biến thể này còn hàng/còn sản xuất không?');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn([
                'wood_type',
                'upholstery',
                'finish',
                'width_cm',
                'depth_cm',
                'height_cm',
                'weight_kg',
                'seat_height_cm',
                'image_url',
                'is_available',
            ]);
        });
    }
};

