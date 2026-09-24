<?php

namespace App\Models;

use App\KetLuanIqc;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'lo_nguyen_vat_lieu_id',
    'ma_qr_barcode',
    'so_luong_lay_mau',
    'noi_dung_chi_tiet',
    'nguoi_kiem_tra',
    'ngay_kiem_tra',
    'ket_luan',
    'ghi_chu',
    'hinh_anh_dinh_kem',
])]
class PhieuKiemTraIqc extends Model
{
    public const CREATED_AT = 'ngay_tao';

    public const UPDATED_AT = null;

    protected $table = 'phieu_kiem_tra_iqc';

    protected function casts(): array
    {
        return [
            'so_luong_lay_mau' => 'decimal:4',
            'noi_dung_chi_tiet' => 'array',
            'hinh_anh_dinh_kem' => 'array',
            'ngay_kiem_tra' => 'datetime',
            'ket_luan' => KetLuanIqc::class,
        ];
    }

    public function loNguyenVatLieu(): BelongsTo
    {
        return $this->belongsTo(LoNguyenVatLieu::class, 'lo_nguyen_vat_lieu_id');
    }
}
