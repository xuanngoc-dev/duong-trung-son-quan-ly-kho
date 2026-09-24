<?php

namespace Database\Seeders;

use App\KetLuanIqc;
use App\Models\LoNguyenVatLieu;
use App\Models\PhieuKiemTraIqc;
use Illuminate\Database\Seeder;

class PhieuKiemTraIqcMauSeeder extends Seeder
{
    public function run(): void
    {
        $lots = LoNguyenVatLieu::query()->orderBy('id')->get();
        if ($lots->isEmpty()) {
            $this->call(NguyenVatLieuMauSeeder::class);
            $lots = LoNguyenVatLieu::query()->orderBy('id')->get();
        }

        $inspectors = [
            'Lê Hoàng Cường',
            'Hoàng Thị Em',
            'Ngô Thị Hương',
            'Phan Thị Oanh',
            'Đinh Thị Quỳnh',
        ];
        $hangMuc = ['Ngoại quan', 'Kích thước', 'Chứng từ lô'];

        foreach (range(1, 50) as $index) {
            $lot = $lots[($index - 1) % $lots->count()];
            $so = str_pad((string) $index, 3, '0', STR_PAD_LEFT);
            $khongDat = $index % 5 === 0;
            $ngayNhap = $lot->ngay_nhap?->copy() ?? now()->subDays(30);
            $ngayKiem = $ngayNhap->addDay()->setTime(8 + ($index % 8), ($index * 7) % 60);

            $details = [];
            foreach ($hangMuc as $thuTu => $ten) {
                $hangMucKhongDat = $khongDat && $thuTu === 1;
                $details[] = [
                    'hang_muc' => $ten,
                    'ket_qua' => $hangMucKhongDat ? 'NG' : 'OK',
                    'ghi_chu' => $hangMucKhongDat ? 'Lệch so với quy cách.' : '',
                ];
            }

            PhieuKiemTraIqc::query()->updateOrCreate(
                ['ma_qr_barcode' => "IQC2026{$so}"],
                [
                    'lo_nguyen_vat_lieu_id' => $lot->id,
                    'so_luong_lay_mau' => 1 + ($index % 5) * 0.5,
                    'noi_dung_chi_tiet' => $details,
                    'nguoi_kiem_tra' => $inspectors[$index % count($inspectors)],
                    'ngay_kiem_tra' => $ngayKiem,
                    'ket_luan' => $khongDat ? KetLuanIqc::Ng : KetLuanIqc::Ok,
                    'ghi_chu' => $khongDat ? 'Lô không đạt, cần xử lý.' : 'Đạt tiêu chuẩn nhập kho.',
                    'hinh_anh_dinh_kem' => null,
                ],
            );
        }
    }
}
