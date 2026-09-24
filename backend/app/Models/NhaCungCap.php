<?php

namespace App\Models;

use App\NhaCungCapStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'ten_nha_cung_cap',
    'ma_nha_cung_cap',
    'email_nha_cung_cap',
    'sdt_nha_cung_cap',
    'ten_nguoi_lien_he',
    'email_nguoi_lien_he',
    'sdt_nguoi_lien_he',
    'website',
    'dia_chi',
    'ma_so_thue',
    'so_tai_khoan',
    'ngan_hang',
    'trang_thai',
    'ghi_chu',
])]
class NhaCungCap extends Model
{
    protected $table = 'nha_cung_cap';

    protected function casts(): array
    {
        return [
            'trang_thai' => NhaCungCapStatus::class,
        ];
    }
}
