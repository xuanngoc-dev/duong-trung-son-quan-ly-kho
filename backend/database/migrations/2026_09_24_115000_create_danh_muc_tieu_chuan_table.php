<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('danh_muc_tieu_chuan', function (Blueprint $table) {
            $table->id();
            $table->string('ma_san_pham', 50)->index()->comment('Mã sản phẩm / linh kiện');
            $table->string('ten_san_pham', 255)->comment('Tên sản phẩm / linh kiện');
            $table->string('phien_ban', 20)->default('v1.0')->comment('Phiên bản tiêu chuẩn');
            $table->string('trang_thai', 20)->default('Active')->comment('Trạng thái: Active/Inactive/Draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('danh_muc_tieu_chuan');
    }
};
