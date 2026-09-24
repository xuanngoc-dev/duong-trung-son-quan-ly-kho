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
        Schema::table('danh_muc_nguyen_vat_lieu', function (Blueprint $table) {
            $table->string('hinh_anh', 2048)->nullable()->after('tieu_chuan_kiem_tra');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('danh_muc_nguyen_vat_lieu', function (Blueprint $table) {
            $table->dropColumn('hinh_anh');
        });
    }
};
