<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chi_tiet_chi_tieu_kiem_tra', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tieu_chuan_id')
                ->constrained('danh_muc_tieu_chuan')
                ->cascadeOnDelete()
                ->comment('Mã ID tiêu chuẩn gốc');
            $table->string('hang_muc_kiem_tra', 255)->comment('Hạng mục: Kích thước, Trọng lượng,...');
            $table->enum('loai_tieu_chuan', ['NUMERIC', 'TEXT'])->default('NUMERIC')->comment('Loại: Số hoặc Văn bản');
            $table->decimal('gia_tri_dinh_muc', 10, 4)->nullable()->comment('Giá trị định mức');
            $table->decimal('dung_sai_tren', 10, 4)->nullable()->comment('Dung sai trên (+)');
            $table->decimal('dung_sai_duoi', 10, 4)->nullable()->comment('Dung sai dưới (-)');
            $table->text('tieu_chuan_mo_ta')->nullable()->comment('Tiêu chuẩn mô tả (Ngoại quan)');
            $table->string('don_vi_tinh', 20)->nullable()->comment('Đơn vị: mm, g, pcs,...');
            $table->string('phuong_phap_kiem_tra', 100)->nullable()->comment('Phương pháp: Đo, Cân, Visual,...');
            $table->string('dung_cu_thiet_bi', 255)->nullable()->comment('Dụng cụ: Thước đo, Cân điện tử,...');
            $table->integer('thu_tu_hien_thi')->default(0)->comment('Thứ tự hiển thị trên giao diện');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chi_tiet_chi_tieu_kiem_tra');
    }
};
