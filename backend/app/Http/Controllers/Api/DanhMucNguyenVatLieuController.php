<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DanhMucNguyenVatLieu;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DanhMucNguyenVatLieuController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'nha_cung_cap_id' => ['nullable', 'integer', Rule::exists('nha_cung_cap', 'id')],
            'start' => ['nullable', 'integer', 'min:0'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ], $this->messages());

        $query = DanhMucNguyenVatLieu::query()
            ->with('nhaCungCap:id,ma_nha_cung_cap,ten_nha_cung_cap')
            ->when($filters['q'] ?? null, function ($query, string $keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('ma_nguyen_vat_lieu', 'like', "%{$keyword}%")
                        ->orWhere('ten_nguyen_vat_lieu', 'like', "%{$keyword}%")
                        ->orWhere('part_number', 'like', "%{$keyword}%");
                });
            })
            ->when($filters['nha_cung_cap_id'] ?? null, fn ($query, int $id) => $query->where('nha_cung_cap_id', $id));

        $total = (clone $query)->count();
        $items = $query
            ->latest('ngay_tao')
            ->skip($filters['start'] ?? 0)
            ->take($filters['limit'] ?? 10)
            ->get()
            ->map(fn (DanhMucNguyenVatLieu $item) => $this->payload($item));

        return response()->json([
            'success' => true,
            'data' => $items,
            'total' => $total,
        ]);
    }

    public function uploadImage(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'image', 'max:5120'],
        ], [
            'file.required' => 'Chọn hình ảnh.',
            'file.image' => 'File phải là hình ảnh.',
            'file.max' => 'Hình ảnh tối đa 5MB.',
        ]);

        $path = $request->file('file')->store('nguyen-vat-lieu', 'public');

        return response()->json([
            'success' => true,
            'pathFile' => '/storage/'.$path,
        ]);
    }

    public function store(Request $request)
    {
        $item = DanhMucNguyenVatLieu::create($this->validated($request));

        return response()->json([
            'success' => true,
            'message' => 'Đã thêm nguyên vật liệu.',
            'data' => $this->payload($item->load('nhaCungCap:id,ma_nha_cung_cap,ten_nha_cung_cap')),
        ], 201);
    }

    public function update(Request $request, DanhMucNguyenVatLieu $danh_muc_nguyen_vat_lieu)
    {
        $danh_muc_nguyen_vat_lieu->update($this->validated($request, $danh_muc_nguyen_vat_lieu));

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật nguyên vật liệu.',
            'data' => $this->payload($danh_muc_nguyen_vat_lieu->refresh()->load('nhaCungCap:id,ma_nha_cung_cap,ten_nha_cung_cap')),
        ]);
    }

    public function destroy(DanhMucNguyenVatLieu $danh_muc_nguyen_vat_lieu)
    {
        try {
            $danh_muc_nguyen_vat_lieu->delete();
        } catch (QueryException) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa vì còn lô nguyên vật liệu đang gắn với danh mục này.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa nguyên vật liệu.',
        ]);
    }

    private function validated(Request $request, ?DanhMucNguyenVatLieu $item = null): array
    {
        return $request->validate([
            'ma_nguyen_vat_lieu' => [
                'required',
                'string',
                'max:50',
                Rule::unique('danh_muc_nguyen_vat_lieu', 'ma_nguyen_vat_lieu')->ignore($item),
            ],
            'ten_nguyen_vat_lieu' => ['required', 'string', 'max:255'],
            'part_number' => ['nullable', 'string', 'max:100'],
            'nha_cung_cap_id' => ['nullable', 'integer', Rule::exists('nha_cung_cap', 'id')],
            'quy_cach_spec' => ['nullable', 'string', 'max:2000'],
            'don_vi_tinh' => ['required', 'string', 'max:20'],
            'tieu_chuan_kiem_tra' => ['nullable', 'string', 'max:2000'],
            'hinh_anh' => ['nullable', 'string', 'max:2048', function (string $attribute, mixed $value, \Closure $fail) {
                if ($value === null || $value === '') {
                    return;
                }
                if (str_starts_with($value, '/storage/')) {
                    return;
                }
                if (! filter_var($value, FILTER_VALIDATE_URL)) {
                    $fail('Link hình ảnh không hợp lệ.');
                }
            }],
        ], $this->messages());
    }

    private function messages(): array
    {
        return [
            'ma_nguyen_vat_lieu.required' => 'Nhập mã nguyên vật liệu.',
            'ma_nguyen_vat_lieu.unique' => 'Mã nguyên vật liệu đã được sử dụng.',
            'ten_nguyen_vat_lieu.required' => 'Nhập tên nguyên vật liệu.',
            'don_vi_tinh.required' => 'Nhập đơn vị tính.',
            'nha_cung_cap_id.exists' => 'Nhà cung cấp không tồn tại.',
            'start.integer' => 'Vị trí bắt đầu không hợp lệ.',
            'start.min' => 'Vị trí bắt đầu không hợp lệ.',
            'limit.integer' => 'Số lượng mỗi trang không hợp lệ.',
            'limit.min' => 'Số lượng mỗi trang không hợp lệ.',
            'limit.max' => 'Số lượng mỗi trang tối đa 100.',
        ];
    }

    private function payload(DanhMucNguyenVatLieu $item): array
    {
        return [
            'id' => $item->id,
            'ma_nguyen_vat_lieu' => $item->ma_nguyen_vat_lieu,
            'ten_nguyen_vat_lieu' => $item->ten_nguyen_vat_lieu,
            'part_number' => $item->part_number,
            'nha_cung_cap_id' => $item->nha_cung_cap_id,
            'nha_cung_cap' => $item->nhaCungCap ? [
                'id' => $item->nhaCungCap->id,
                'ma_nha_cung_cap' => $item->nhaCungCap->ma_nha_cung_cap,
                'ten_nha_cung_cap' => $item->nhaCungCap->ten_nha_cung_cap,
            ] : null,
            'quy_cach_spec' => $item->quy_cach_spec,
            'don_vi_tinh' => $item->don_vi_tinh,
            'tieu_chuan_kiem_tra' => $item->tieu_chuan_kiem_tra,
            'hinh_anh' => $item->hinh_anh,
            'ngay_tao' => $item->ngay_tao,
            'ngay_cap_nhat' => $item->ngay_cap_nhat,
        ];
    }
}
