<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('phieu_kiem_tra_iqc')
            ->whereNotNull('hinh_anh_dinh_kem')
            ->get(['id', 'hinh_anh_dinh_kem']);

        foreach ($rows as $row) {
            $decoded = json_decode($row->hinh_anh_dinh_kem, true);
            if (is_array($decoded)) {
                continue;
            }

            $path = trim((string) $row->hinh_anh_dinh_kem);
            DB::table('phieu_kiem_tra_iqc')->where('id', $row->id)->update([
                'hinh_anh_dinh_kem' => $path === '' ? null : json_encode([$path]),
            ]);
        }

        DB::statement('ALTER TABLE phieu_kiem_tra_iqc MODIFY hinh_anh_dinh_kem JSON NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE phieu_kiem_tra_iqc MODIFY hinh_anh_dinh_kem TEXT NULL');
    }
};
