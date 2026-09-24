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
        Schema::create('phieu_kiem_tra_iqc', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lo_nguyen_vat_lieu_id')->constrained('lo_nguyen_vat_lieu');
            $table->string('ma_qr_barcode', 100)->nullable();
            $table->decimal('so_luong_lay_mau', 12, 4);
            $table->json('noi_dung_chi_tiet')->nullable();
            $table->string('nguoi_kiem_tra', 100);
            $table->dateTime('ngay_kiem_tra')->useCurrent();
            $table->enum('ket_luan', ['OK', 'NG']);
            $table->text('ghi_chu')->nullable();
            $table->text('hinh_anh_dinh_kem')->nullable();
            $table->timestamp('ngay_tao')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phieu_kiem_tra_iqc');
    }
};
