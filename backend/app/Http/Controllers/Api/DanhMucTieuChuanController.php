<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\LoaiTieuChuan;
use App\Models\ChiTietChiTieuKiemTra;
use App\Models\DanhMucTieuChuan;
use App\TrangThaiTieuChuan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DanhMucTieuChuanController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'trang_thai' => ['nullable', Rule::enum(TrangThaiTieuChuan::class)],
            'start' => ['nullable', 'integer', 'min:0'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ], $this->messages());

        $query = DanhMucTieuChuan::query()
            ->with('chiTiet')
            ->when($filters['q'] ?? null, function ($query, string $keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('ma_san_pham', 'like', "%{$keyword}%")
                        ->orWhere('ten_san_pham', 'like', "%{$keyword}%")
                        ->orWhere('phien_ban', 'like', "%{$keyword}%");
                });
            })
            ->when($filters['trang_thai'] ?? null, fn ($query, string $status) => $query->where('trang_thai', $status));

        $total = (clone $query)->count();
        $items = $query
            ->latest()
            ->skip($filters['start'] ?? 0)
            ->take($filters['limit'] ?? 10)
            ->get()
            ->map(fn (DanhMucTieuChuan $item) => $this->payload($item));

        return response()->json([
            'success' => true,
            'data' => $items,
            'total' => $total,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $item = DB::transaction(function () use ($data) {
            $details = $data['chi_tiet'] ?? [];
            unset($data['chi_tiet']);
            $item = DanhMucTieuChuan::create($data);
            $this->syncDetails($item, $details);

            return $item->load('chiTiet');
        });

        return response()->json([
            'success' => true,
            'message' => 'Đã thêm tiêu chuẩn kiểm tra.',
            'data' => $this->payload($item),
        ], 201);
    }

    public function update(Request $request, DanhMucTieuChuan $danh_muc_tieu_chuan)
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($danh_muc_tieu_chuan, $data) {
            $details = array_key_exists('chi_tiet', $data) ? $data['chi_tiet'] : null;
            unset($data['chi_tiet']);
            $danh_muc_tieu_chuan->update($data);
            if (is_array($details)) {
                $this->syncDetails($danh_muc_tieu_chuan, $details);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật tiêu chuẩn kiểm tra.',
            'data' => $this->payload($danh_muc_tieu_chuan->refresh()->load('chiTiet')),
        ]);
    }

    public function destroy(DanhMucTieuChuan $danh_muc_tieu_chuan)
    {
        $danh_muc_tieu_chuan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa tiêu chuẩn kiểm tra.',
        ]);
    }

    private function syncDetails(DanhMucTieuChuan $item, array $details): void
    {
        $item->chiTiet()->delete();
        foreach (array_values($details) as $index => $detail) {
            $item->chiTiet()->create([
                ...$detail,
                'thu_tu_hien_thi' => $index,
            ]);
        }
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'ma_san_pham' => ['required', 'string', 'max:50'],
            'ten_san_pham' => ['required', 'string', 'max:255'],
            'phien_ban' => ['nullable', 'string', 'max:20'],
            'trang_thai' => ['required', Rule::enum(TrangThaiTieuChuan::class)],
            'chi_tiet' => ['nullable', 'array'],
            'chi_tiet.*.hang_muc_kiem_tra' => ['required', 'string', 'max:255'],
            'chi_tiet.*.loai_tieu_chuan' => ['required', Rule::enum(LoaiTieuChuan::class)],
            'chi_tiet.*.gia_tri_dinh_muc' => ['nullable', 'numeric'],
            'chi_tiet.*.dung_sai_tren' => ['nullable', 'numeric'],
            'chi_tiet.*.dung_sai_duoi' => ['nullable', 'numeric'],
            'chi_tiet.*.tieu_chuan_mo_ta' => ['nullable', 'string'],
            'chi_tiet.*.don_vi_tinh' => ['nullable', 'string', 'max:20'],
            'chi_tiet.*.phuong_phap_kiem_tra' => ['nullable', 'string', 'max:100'],
            'chi_tiet.*.dung_cu_thiet_bi' => ['nullable', 'string', 'max:255'],
        ], $this->messages());

        $data['phien_ban'] = trim((string) ($data['phien_ban'] ?? '')) ?: 'v1.0';
        $data['chi_tiet'] = array_map(function (array $detail) {
            foreach (['gia_tri_dinh_muc', 'dung_sai_tren', 'dung_sai_duoi'] as $field) {
                if (! array_key_exists($field, $detail) || $detail[$field] === '' || $detail[$field] === null) {
                    $detail[$field] = null;
                }
            }
            foreach (['tieu_chuan_mo_ta', 'don_vi_tinh', 'phuong_phap_kiem_tra', 'dung_cu_thiet_bi'] as $field) {
                $detail[$field] = trim((string) ($detail[$field] ?? '')) ?: null;
            }

            return $detail;
        }, $data['chi_tiet'] ?? []);

        return $data;
    }

    private function messages(): array
    {
        return [
            'ma_san_pham.required' => 'Nhập mã sản phẩm.',
            'ten_san_pham.required' => 'Nhập tên sản phẩm.',
            'trang_thai.required' => 'Chọn trạng thái.',
            'trang_thai.enum' => 'Trạng thái không hợp lệ.',
            'chi_tiet.*.hang_muc_kiem_tra.required' => 'Nhập hạng mục kiểm tra.',
            'chi_tiet.*.loai_tieu_chuan.required' => 'Chọn loại tiêu chuẩn.',
            'chi_tiet.*.loai_tieu_chuan.enum' => 'Loại tiêu chuẩn không hợp lệ.',
            'chi_tiet.*.gia_tri_dinh_muc.numeric' => 'Giá trị định mức không hợp lệ.',
            'chi_tiet.*.dung_sai_tren.numeric' => 'Dung sai trên không hợp lệ.',
            'chi_tiet.*.dung_sai_duoi.numeric' => 'Dung sai dưới không hợp lệ.',
            'start.integer' => 'Vị trí bắt đầu không hợp lệ.',
            'start.min' => 'Vị trí bắt đầu không hợp lệ.',
            'limit.integer' => 'Số lượng mỗi trang không hợp lệ.',
            'limit.min' => 'Số lượng mỗi trang không hợp lệ.',
            'limit.max' => 'Số lượng mỗi trang tối đa 100.',
        ];
    }

    private function payload(DanhMucTieuChuan $item): array
    {
        $trangThai = $item->trang_thai instanceof TrangThaiTieuChuan ? $item->trang_thai->value : $item->trang_thai;

        return [
            'id' => $item->id,
            'ma_san_pham' => $item->ma_san_pham,
            'ten_san_pham' => $item->ten_san_pham,
            'phien_ban' => $item->phien_ban,
            'trang_thai' => $trangThai,
            'chi_tiet' => $item->chiTiet->map(function (ChiTietChiTieuKiemTra $detail) {
                $loai = $detail->loai_tieu_chuan instanceof LoaiTieuChuan ? $detail->loai_tieu_chuan->value : $detail->loai_tieu_chuan;

                return [
                    'id' => $detail->id,
                    'hang_muc_kiem_tra' => $detail->hang_muc_kiem_tra,
                    'loai_tieu_chuan' => $loai,
                    'gia_tri_dinh_muc' => $detail->gia_tri_dinh_muc,
                    'dung_sai_tren' => $detail->dung_sai_tren,
                    'dung_sai_duoi' => $detail->dung_sai_duoi,
                    'tieu_chuan_mo_ta' => $detail->tieu_chuan_mo_ta,
                    'don_vi_tinh' => $detail->don_vi_tinh,
                    'phuong_phap_kiem_tra' => $detail->phuong_phap_kiem_tra,
                    'dung_cu_thiet_bi' => $detail->dung_cu_thiet_bi,
                    'thu_tu_hien_thi' => $detail->thu_tu_hien_thi,
                ];
            })->values(),
        ];
    }
}
