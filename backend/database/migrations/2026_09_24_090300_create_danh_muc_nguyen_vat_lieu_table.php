<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('danh_muc_nguyen_vat_lieu', function (Blueprint $table) {
            $table->id();
            $table->string('ma_nguyen_vat_lieu', 50)->unique();
            $table->string('ten_nguyen_vat_lieu');
            $table->string('part_number', 100)->nullable();
            $table->foreignId('nha_cung_cap_id')->nullable()->constrained('nha_cung_cap')->nullOnDelete();
            $table->text('quy_cach_spec')->nullable();
            $table->string('don_vi_tinh', 20);
            $table->text('tieu_chuan_kiem_tra')->nullable();
            $table->timestamp('ngay_tao')->useCurrent();
            $table->timestamp('ngay_cap_nhat')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('danh_muc_nguyen_vat_lieu');
    }
};
