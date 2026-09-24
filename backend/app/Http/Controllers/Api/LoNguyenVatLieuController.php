<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\LoNguyenVatLieuStatus;
use App\Models\LoNguyenVatLieu;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LoNguyenVatLieuController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'trang_thai' => ['nullable', Rule::enum(LoNguyenVatLieuStatus::class)],
            'nguyen_vat_lieu_id' => ['nullable', 'integer', Rule::exists('danh_muc_nguyen_vat_lieu', 'id')],
            'start' => ['nullable', 'integer', 'min:0'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ], $this->messages());

        $query = LoNguyenVatLieu::query()
            ->with('nguyenVatLieu:id,ma_nguyen_vat_lieu,ten_nguyen_vat_lieu,don_vi_tinh')
            ->when($filters['q'] ?? null, function ($query, string $keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('so_lo_batch', 'like', "%{$keyword}%")
                        ->orWhereHas('nguyenVatLieu', function ($query) use ($keyword) {
                            $query->where('ma_nguyen_vat_lieu', 'like', "%{$keyword}%")
                                ->orWhere('ten_nguyen_vat_lieu', 'like', "%{$keyword}%");
                        });
                });
            })
            ->when($filters['trang_thai'] ?? null, fn ($query, string $status) => $query->where('trang_thai', $status))
            ->when($filters['nguyen_vat_lieu_id'] ?? null, fn ($query, int $id) => $query->where('nguyen_vat_lieu_id', $id));

        $total = (clone $query)->count();
        $items = $query
            ->latest('ngay_tao')
            ->skip($filters['start'] ?? 0)
            ->take($filters['limit'] ?? 10)
            ->get()
            ->map(fn (LoNguyenVatLieu $item) => $this->payload($item));

        return response()->json([
            'success' => true,
            'data' => $items,
            'total' => $total,
        ]);
    }

    public function store(Request $request)
    {
        $item = LoNguyenVatLieu::create($this->validated($request));

        return response()->json([
            'success' => true,
            'message' => 'Đã thêm lô nguyên vật liệu.',
            'data' => $this->payload($item->load('nguyenVatLieu:id,ma_nguyen_vat_lieu,ten_nguyen_vat_lieu,don_vi_tinh')),
        ], 201);
    }

    public function update(Request $request, LoNguyenVatLieu $lo_nguyen_vat_lieu)
    {
        $lo_nguyen_vat_lieu->update($this->validated($request));

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật lô nguyên vật liệu.',
            'data' => $this->payload($lo_nguyen_vat_lieu->refresh()->load('nguyenVatLieu:id,ma_nguyen_vat_lieu,ten_nguyen_vat_lieu,don_vi_tinh')),
        ]);
    }

    public function destroy(LoNguyenVatLieu $lo_nguyen_vat_lieu)
    {
        try {
            $lo_nguyen_vat_lieu->delete();
        } catch (QueryException) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa vì còn phiếu kiểm tra IQC gắn với lô này.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa lô nguyên vật liệu.',
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nguyen_vat_lieu_id' => ['required', 'integer', Rule::exists('danh_muc_nguyen_vat_lieu', 'id')],
            'so_lo_batch' => ['required', 'string', 'max:100'],
            'ngay_nhap' => ['required', 'date'],
            'so_luong' => ['required', 'numeric', 'min:0', 'decimal:0,4'],
            'han_su_dung' => ['nullable', 'date'],
            'trang_thai' => ['required', Rule::enum(LoNguyenVatLieuStatus::class)],
            'ghi_chu' => ['nullable', 'string', 'max:2000'],
        ], $this->messages());
    }

    private function messages(): array
    {
        return [
            'nguyen_vat_lieu_id.required' => 'Chọn nguyên vật liệu.',
            'nguyen_vat_lieu_id.exists' => 'Nguyên vật liệu không tồn tại.',
            'so_lo_batch.required' => 'Nhập số lô.',
            'ngay_nhap.required' => 'Chọn ngày nhập.',
            'ngay_nhap.date' => 'Ngày nhập không hợp lệ.',
            'so_luong.required' => 'Nhập số lượng.',
            'so_luong.numeric' => 'Số lượng không hợp lệ.',
            'so_luong.min' => 'Số lượng không được âm.',
            'so_luong.decimal' => 'Số lượng tối đa 4 chữ số thập phân.',
            'han_su_dung.date' => 'Hạn sử dụng không hợp lệ.',
            'trang_thai.required' => 'Chọn trạng thái.',
            'trang_thai.enum' => 'Trạng thái không hợp lệ.',
            'start.integer' => 'Vị trí bắt đầu không hợp lệ.',
            'start.min' => 'Vị trí bắt đầu không hợp lệ.',
            'limit.integer' => 'Số lượng mỗi trang không hợp lệ.',
            'limit.min' => 'Số lượng mỗi trang không hợp lệ.',
            'limit.max' => 'Số lượng mỗi trang tối đa 100.',
        ];
    }

    private function payload(LoNguyenVatLieu $item): array
    {
        $status = $item->trang_thai instanceof LoNguyenVatLieuStatus ? $item->trang_thai->value : $item->trang_thai;

        return [
            'id' => $item->id,
            'nguyen_vat_lieu_id' => $item->nguyen_vat_lieu_id,
            'nguyen_vat_lieu' => $item->nguyenVatLieu ? [
                'id' => $item->nguyenVatLieu->id,
                'ma_nguyen_vat_lieu' => $item->nguyenVatLieu->ma_nguyen_vat_lieu,
                'ten_nguyen_vat_lieu' => $item->nguyenVatLieu->ten_nguyen_vat_lieu,
                'don_vi_tinh' => $item->nguyenVatLieu->don_vi_tinh,
            ] : null,
            'so_lo_batch' => $item->so_lo_batch,
            'ngay_nhap' => $item->ngay_nhap?->toDateString(),
            'so_luong' => $item->so_luong,
            'han_su_dung' => $item->han_su_dung?->toDateString(),
            'trang_thai' => $status,
            'ghi_chu' => $item->ghi_chu,
            'ngay_tao' => $item->ngay_tao,
        ];
    }
}
