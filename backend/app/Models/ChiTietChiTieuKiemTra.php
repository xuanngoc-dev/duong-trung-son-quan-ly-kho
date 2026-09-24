<?php

namespace App\Models;

use App\LoaiTieuChuan;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tieu_chuan_id',
    'hang_muc_kiem_tra',
    'loai_tieu_chuan',
    'gia_tri_dinh_muc',
    'dung_sai_tren',
    'dung_sai_duoi',
    'tieu_chuan_mo_ta',
    'don_vi_tinh',
    'phuong_phap_kiem_tra',
    'dung_cu_thiet_bi',
    'thu_tu_hien_thi',
])]
class ChiTietChiTieuKiemTra extends Model
{
    protected $table = 'chi_tiet_chi_tieu_kiem_tra';

    protected function casts(): array
    {
        return [
            'loai_tieu_chuan' => LoaiTieuChuan::class,
            'gia_tri_dinh_muc' => 'decimal:4',
            'dung_sai_tren' => 'decimal:4',
            'dung_sai_duoi' => 'decimal:4',
            'thu_tu_hien_thi' => 'integer',
        ];
    }

    public function tieuChuan(): BelongsTo
    {
        return $this->belongsTo(DanhMucTieuChuan::class, 'tieu_chuan_id');
    }
}
