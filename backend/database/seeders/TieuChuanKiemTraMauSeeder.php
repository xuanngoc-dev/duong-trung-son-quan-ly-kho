<?php

namespace Database\Seeders;

use App\LoaiTieuChuan;
use App\Models\DanhMucTieuChuan;
use App\TrangThaiTieuChuan;
use Illuminate\Database\Seeder;

class TieuChuanKiemTraMauSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [TrangThaiTieuChuan::Active, TrangThaiTieuChuan::Active, TrangThaiTieuChuan::Draft, TrangThaiTieuChuan::Inactive];
        $products = [
            'Hạt nhựa PP', 'Hạt nhựa PE', 'Màng PE', 'Bao bì carton 3 lớp', 'Bao bì carton 5 lớp',
            'Mực in đen', 'Keo dán nóng', 'Nhãn decal', 'Pallet gỗ', 'Túi PE',
            'Ống đồng', 'Tôn mạ kẽm', 'Vít tự khoan', 'Sơn phủ', 'Dây điện 2.5mm',
            'Gioăng cao su', 'Vải không dệt', 'Tem truy xuất', 'Thùng nhựa', 'Nắp chai',
        ];

        foreach ($products as $index => $name) {
            $so = str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);
            $item = DanhMucTieuChuan::query()->updateOrCreate(
                ['ma_san_pham' => "SP{$so}", 'phien_ban' => 'v1.0'],
                [
                    'ten_san_pham' => $name,
                    'trang_thai' => $statuses[$index % count($statuses)],
                ],
            );

            $item->chiTiet()->delete();
            foreach ($this->details($index) as $order => $detail) {
                $item->chiTiet()->create([...$detail, 'thu_tu_hien_thi' => $order]);
            }
        }
    }

    private function details(int $index): array
    {
        $size = 10 + ($index % 8) + (($index % 4) * 0.25);

        return [
            [
                'hang_muc_kiem_tra' => 'Kích thước',
                'loai_tieu_chuan' => LoaiTieuChuan::Numeric,
                'gia_tri_dinh_muc' => $size,
                'dung_sai_tren' => 0.2,
                'dung_sai_duoi' => 0.2,
                'tieu_chuan_mo_ta' => null,
                'don_vi_tinh' => 'mm',
                'phuong_phap_kiem_tra' => 'Đo',
                'dung_cu_thiet_bi' => 'Thước kẹp',
            ],
            [
                'hang_muc_kiem_tra' => 'Trọng lượng',
                'loai_tieu_chuan' => LoaiTieuChuan::Numeric,
                'gia_tri_dinh_muc' => 50 + $index,
                'dung_sai_tren' => 1.5,
                'dung_sai_duoi' => 1,
                'tieu_chuan_mo_ta' => null,
                'don_vi_tinh' => 'g',
                'phuong_phap_kiem_tra' => 'Cân',
                'dung_cu_thiet_bi' => 'Cân điện tử',
            ],
            [
                'hang_muc_kiem_tra' => 'Ngoại quan',
                'loai_tieu_chuan' => LoaiTieuChuan::Text,
                'gia_tri_dinh_muc' => null,
                'dung_sai_tren' => null,
                'dung_sai_duoi' => null,
                'tieu_chuan_mo_ta' => 'Không rách, không bẩn, không biến dạng.',
                'don_vi_tinh' => null,
                'phuong_phap_kiem_tra' => 'Visual',
                'dung_cu_thiet_bi' => 'Mắt thường',
            ],
        ];
    }
}
