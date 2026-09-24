<?php

namespace Database\Seeders;

use App\LoNguyenVatLieuStatus;
use App\Models\DanhMucNguyenVatLieu;
use App\Models\LoNguyenVatLieu;
use App\Models\NhaCungCap;
use Illuminate\Database\Seeder;

class NguyenVatLieuMauSeeder extends Seeder
{
    public function run(): void
    {
        $materials = $this->seedMaterials();
        $this->seedLots($materials);
    }

    /**
     * @return list<DanhMucNguyenVatLieu>
     */
    private function seedMaterials(): array
    {
        $supplierIds = NhaCungCap::query()->orderBy('id')->pluck('id')->all();
        $names = [
            'Hạt nhựa PP', 'Hạt nhựa PE', 'Màng PE', 'Bao bì carton 3 lớp', 'Bao bì carton 5 lớp',
            'Mực in đen', 'Mực in xanh', 'Keo dán nóng', 'Băng keo trong', 'Nhãn decal',
            'Pallet gỗ', 'Màng co', 'Túi PE', 'Hạt màu trắng', 'Hạt màu đen',
            'Phụ gia chống UV', 'Chất chống tĩnh điện', 'Dây đai nhựa', 'Góc carton', 'Xốp lót',
            'Ống đồng', 'Tôn mạ kẽm', 'Nhôm tấm', 'Vít tự khoan', 'Bu lông M8',
            'Sơn lót', 'Sơn phủ', 'Dung môi', 'Chất tẩy rửa', 'Găng tay bảo hộ',
            'Linh kiện cảm biến', 'Bo mạch điều khiển', 'Dây điện 2.5mm', 'Cầu chì', 'Rơ le',
            'Gioăng cao su', 'Vòng bi', 'Dầu bôi trơn', 'Mỡ chịu nhiệt', 'Lưới lọc',
            'Vải không dệt', 'Chỉ may', 'Khóa kéo', 'Nút nhựa', 'Móc áo',
            'Tem truy xuất', 'Mã vạch cuộn', 'Thùng nhựa', 'Khay nhựa', 'Nắp chai',
        ];
        $units = ['kg', 'cái', 'm', 'cuộn', 'thùng', 'lít'];
        $created = [];

        foreach ($names as $index => $name) {
            $so = str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);
            $supplierId = $supplierIds !== [] ? $supplierIds[$index % count($supplierIds)] : null;

            $created[] = DanhMucNguyenVatLieu::query()->updateOrCreate(
                ['ma_nguyen_vat_lieu' => "NVL{$so}"],
                [
                    'ten_nguyen_vat_lieu' => $name,
                    'part_number' => 'PN-'.(1000 + $index),
                    'nha_cung_cap_id' => $supplierId,
                    'quy_cach_spec' => 'Quy cách mẫu '.($index + 1),
                    'don_vi_tinh' => $units[$index % count($units)],
                    'tieu_chuan_kiem_tra' => 'Kiểm ngoại quan, kích thước và chứng từ lô.',
                ],
            );
        }

        return $created;
    }

    /**
     * @param  list<DanhMucNguyenVatLieu>  $materials
     */
    private function seedLots(array $materials): void
    {
        $statuses = [
            LoNguyenVatLieuStatus::DaKiem,
            LoNguyenVatLieuStatus::Dat,
            LoNguyenVatLieuStatus::KhongDat,
            LoNguyenVatLieuStatus::DacCach,
            LoNguyenVatLieuStatus::TraHang,
        ];

        foreach (range(1, 50) as $index) {
            $material = $materials[($index - 1) % count($materials)];
            $so = str_pad((string) $index, 3, '0', STR_PAD_LEFT);
            $ngayNhap = now()->subDays(60 - $index)->toDateString();

            LoNguyenVatLieu::query()->updateOrCreate(
                [
                    'nguyen_vat_lieu_id' => $material->id,
                    'so_lo_batch' => "LO2026{$so}",
                ],
                [
                    'ngay_nhap' => $ngayNhap,
                    'so_luong' => 10 + ($index * 1.25),
                    'han_su_dung' => $index % 4 === 0 ? null : now()->addMonths(6 + ($index % 12))->toDateString(),
                    'trang_thai' => $statuses[$index % count($statuses)],
                    'ghi_chu' => $index % 5 === 0 ? 'Lô cần theo dõi thêm.' : 'Nhập kho theo phiếu mẫu.',
                ],
            );
        }
    }
}
