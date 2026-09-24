<?php

namespace Database\Seeders;

use App\Models\NhaCungCap;
use App\Models\User;
use App\NhaCungCapStatus;
use App\UserRole;
use App\UserStatus;
use Illuminate\Database\Seeder;

class DuLieuMauSeeder extends Seeder
{
    /**
     * Mật khẩu chung của tài khoản mẫu.
     */
    private const MAT_KHAU = '12345678';

    public function run(): void
    {
        $this->seedUsers();
        $this->seedNhaCungCap();
    }

    private function seedUsers(): void
    {
        $roles = [UserRole::Admin, UserRole::Leader, UserRole::Qc, UserRole::User];
        $names = [
            'Nguyễn Văn An', 'Trần Thị Bình', 'Lê Hoàng Cường', 'Phạm Minh Dũng',
            'Hoàng Thị Em', 'Vũ Quốc Phong', 'Đặng Thu Hà', 'Bùi Văn Giang',
            'Ngô Thị Hương', 'Đỗ Minh Khoa', 'Lý Thanh Lam', 'Mai Quốc Nam',
            'Phan Thị Oanh', 'Trịnh Văn Phúc', 'Đinh Thị Quỳnh', 'Hồ Minh Sơn',
            'Cao Thị Trang', 'Lương Văn Uy', 'Tô Thị Vân', 'Châu Đức Yên',
        ];

        foreach ($names as $index => $name) {
            $so = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
            User::query()->updateOrCreate(
                ['email' => "nv{$so}@duongtrungson.vn"],
                [
                    'name' => $name,
                    'password' => self::MAT_KHAU,
                    'role' => $roles[$index % count($roles)],
                    'trang_thai' => $index % 7 === 0 ? UserStatus::NgungHoatDong : UserStatus::HoatDong,
                ],
            );
        }
    }

    private function seedNhaCungCap(): void
    {
        $companies = [
            ['Công ty TNHH Bao bì Á Châu', 'Vietcombank', 'Hà Nội'],
            ['Công ty CP Nhựa Đại Việt', 'Techcombank', 'Hồ Chí Minh'],
            ['Công ty TNHH Hóa chất Minh Khang', 'BIDV', 'Hải Phòng'],
            ['Công ty CP Thép Hòa Phát Miền Nam', 'VietinBank', 'Đồng Nai'],
            ['Công ty TNHH Giấy Sài Gòn', 'ACB', 'Hồ Chí Minh'],
            ['Công ty CP Điện máy Phương Nam', 'MB Bank', 'Cần Thơ'],
            ['Công ty TNHH Vật tư Đông Á', 'Sacombank', 'Đà Nẵng'],
            ['Công ty CP Nông sản Tây Nguyên', 'Agribank', 'Đắk Lắk'],
            ['Công ty TNHH Cơ khí An Phát', 'VPBank', 'Bình Dương'],
            ['Công ty CP Dệt may Thành Công', 'Vietcombank', 'Hồ Chí Minh'],
            ['Công ty TNHH Bao bì Tân Tiến', 'Techcombank', 'Long An'],
            ['Công ty CP Hóa mỹ phẩm Lan Hương', 'BIDV', 'Hà Nội'],
            ['Công ty TNHH Thực phẩm Sạch Việt', 'ACB', 'Hồ Chí Minh'],
            ['Công ty CP Vận tải Biển Đông', 'MB Bank', 'Hải Phòng'],
            ['Công ty TNHH Nhôm kính Hoàng Gia', 'VietinBank', 'Đà Nẵng'],
            ['Công ty CP Sơn Á Đông', 'Sacombank', 'Bình Dương'],
            ['Công ty TNHH Pallet Nam Việt', 'Agribank', 'Đồng Nai'],
            ['Công ty CP Linh kiện điện tử Sao Mai', 'VPBank', 'Hồ Chí Minh'],
            ['Công ty TNHH Keo dán Đại Phát', 'Vietcombank', 'Hà Nội'],
            ['Công ty CP Nhãn mác In Nhanh', 'Techcombank', 'Hồ Chí Minh'],
        ];

        foreach ($companies as $index => $company) {
            [$ten, $nganHang, $tinh] = $company;
            $so = str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);
            $lienHe = ['Nguyễn Văn An', 'Trần Thị Bình', 'Lê Hoàng Cường', 'Phạm Minh Dũng'][$index % 4];

            NhaCungCap::query()->updateOrCreate(
                ['ma_nha_cung_cap' => "NCC{$so}"],
                [
                    'ten_nha_cung_cap' => $ten,
                    'email_nha_cung_cap' => "contact{$so}@nhacungcap.vn",
                    'sdt_nha_cung_cap' => '028'.str_pad((string) (3800000 + $index), 7, '0', STR_PAD_LEFT),
                    'ten_nguoi_lien_he' => $lienHe,
                    'email_nguoi_lien_he' => "lienhe{$so}@nhacungcap.vn",
                    'sdt_nguoi_lien_he' => '09'.str_pad((string) (10000000 + $index * 137), 8, '0', STR_PAD_LEFT),
                    'website' => "https://ncc{$so}.vn",
                    'dia_chi' => ($index + 12).' đường Số '.($index + 1).", {$tinh}",
                    'ma_so_thue' => '031'.str_pad((string) (2000000 + $index), 7, '0', STR_PAD_LEFT),
                    'so_tai_khoan' => '00'.str_pad((string) (8800000000 + $index), 10, '0', STR_PAD_LEFT),
                    'ngan_hang' => $nganHang,
                    'trang_thai' => $index % 5 === 0 ? NhaCungCapStatus::NgungSuDung : NhaCungCapStatus::DangSuDung,
                    'ghi_chu' => $index % 5 === 0 ? 'Tạm ngừng đặt hàng.' : 'Nhà cung cấp thường xuyên.',
                ],
            );
        }
    }
}
