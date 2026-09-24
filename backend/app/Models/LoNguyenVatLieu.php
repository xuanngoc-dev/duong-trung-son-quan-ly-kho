<?php

namespace App\Models;

use App\LoNguyenVatLieuStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'nguyen_vat_lieu_id',
    'so_lo_batch',
    'ngay_nhap',
    'so_luong',
    'han_su_dung',
    'trang_thai',
    'ghi_chu',
])]
class LoNguyenVatLieu extends Model
{
    public const CREATED_AT = 'ngay_tao';

    public const UPDATED_AT = null;

    protected $table = 'lo_nguyen_vat_lieu';

    protected function casts(): array
    {
        return [
            'ngay_nhap' => 'date',
            'han_su_dung' => 'date',
            'so_luong' => 'decimal:4',
            'trang_thai' => LoNguyenVatLieuStatus::class,
        ];
    }

    public function nguyenVatLieu(): BelongsTo
    {
        return $this->belongsTo(DanhMucNguyenVatLieu::class, 'nguyen_vat_lieu_id');
    }

    public function phieuKiemTraIqc(): HasMany
    {
        return $this->hasMany(PhieuKiemTraIqc::class, 'lo_nguyen_vat_lieu_id');
    }
}
