<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('receiver_name', 100);
            $table->string('receiver_phone', 15);
            $table->string('province', 100)->comment('Tỉnh/Thành phố');
            $table->string('district', 100)->comment('Quận/Huyện');
            $table->string('ward', 100)->comment('Phường/Xã');
            $table->string('address_detail', 255)->comment('Số nhà, tên đường...');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
