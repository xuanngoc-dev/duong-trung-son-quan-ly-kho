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
        Schema::create('lo_nguyen_vat_lieu', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nguyen_vat_lieu_id')->constrained('danh_muc_nguyen_vat_lieu');
            $table->string('so_lo_batch', 100);
            $table->date('ngay_nhap');
            $table->decimal('so_luong', 12, 4);
            $table->date('han_su_dung')->nullable();
            $table->enum('trang_thai', ['DA_KIEM', 'DAT', 'KHONG_DAT', 'DAC_CACH', 'TRA_HANG'])
                ->default('DA_KIEM');
            $table->text('ghi_chu')->nullable();
            $table->timestamp('ngay_tao')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lo_nguyen_vat_lieu');
    }
};
