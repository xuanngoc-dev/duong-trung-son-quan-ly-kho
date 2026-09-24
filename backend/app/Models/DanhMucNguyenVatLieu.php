<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'ma_nguyen_vat_lieu',
    'ten_nguyen_vat_lieu',
    'part_number',
    'nha_cung_cap_id',
    'quy_cach_spec',
    'don_vi_tinh',
    'tieu_chuan_kiem_tra',
    'hinh_anh',
])]
class DanhMucNguyenVatLieu extends Model
{
    public const CREATED_AT = 'ngay_tao';

    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $table = 'danh_muc_nguyen_vat_lieu';

    public function nhaCungCap(): BelongsTo
    {
        return $this->belongsTo(NhaCungCap::class, 'nha_cung_cap_id');
    }

    public function loNguyenVatLieu(): HasMany
    {
        return $this->hasMany(LoNguyenVatLieu::class, 'nguyen_vat_lieu_id');
    }
}
