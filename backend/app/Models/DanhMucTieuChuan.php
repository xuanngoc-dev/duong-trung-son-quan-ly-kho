<?php

namespace App\Models;

use App\TrangThaiTieuChuan;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'ma_san_pham',
    'ten_san_pham',
    'phien_ban',
    'trang_thai',
])]
class DanhMucTieuChuan extends Model
{
    protected $table = 'danh_muc_tieu_chuan';

    protected function casts(): array
    {
        return [
            'trang_thai' => TrangThaiTieuChuan::class,
        ];
    }

    public function chiTiet(): HasMany
    {
        return $this->hasMany(ChiTietChiTieuKiemTra::class, 'tieu_chuan_id')->orderBy('thu_tu_hien_thi')->orderBy('id');
    }
}
