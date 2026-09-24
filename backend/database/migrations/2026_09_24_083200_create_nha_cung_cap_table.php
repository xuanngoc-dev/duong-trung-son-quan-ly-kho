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
        Schema::create('nha_cung_cap', function (Blueprint $table) {
            $table->id();
            $table->string('ten_nha_cung_cap');
            $table->string('ma_nha_cung_cap')->unique();
            $table->string('email_nha_cung_cap')->nullable();
            $table->string('sdt_nha_cung_cap', 20)->nullable();
            $table->string('ten_nguoi_lien_he')->nullable();
            $table->string('email_nguoi_lien_he')->nullable();
            $table->string('sdt_nguoi_lien_he', 20)->nullable();
            $table->string('website')->nullable();
            $table->string('dia_chi', 500)->nullable();
            $table->string('ma_so_thue', 30)->nullable();
            $table->string('so_tai_khoan', 50)->nullable();
            $table->string('ngan_hang')->nullable();
            $table->enum('trang_thai', ['dang_su_dung', 'ngung_su_dung'])->default('dang_su_dung');
            $table->text('ghi_chu')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nha_cung_cap');
    }
};
