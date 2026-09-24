<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NhaCungCap;
use App\NhaCungCapStatus;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NhaCungCapController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'trang_thai' => ['nullable', Rule::enum(NhaCungCapStatus::class)],
            'start' => ['nullable', 'integer', 'min:0'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ], $this->messages());

        $query = NhaCungCap::query()
            ->when($filters['q'] ?? null, function ($query, string $keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('ten_nha_cung_cap', 'like', "%{$keyword}%")
                        ->orWhere('ma_nha_cung_cap', 'like', "%{$keyword}%")
                        ->orWhere('email_nha_cung_cap', 'like', "%{$keyword}%")
                        ->orWhere('sdt_nha_cung_cap', 'like', "%{$keyword}%")
                        ->orWhere('ten_nguoi_lien_he', 'like', "%{$keyword}%");
                });
            })
            ->when($filters['trang_thai'] ?? null, fn ($query, string $status) => $query->where('trang_thai', $status));

        $total = (clone $query)->count();
        $items = $query
            ->latest()
            ->skip($filters['start'] ?? 0)
            ->take($filters['limit'] ?? 10)
            ->get()
            ->map(fn (NhaCungCap $item) => $this->payload($item));

        return response()->json([
            'success' => true,
            'data' => $items,
            'total' => $total,
        ]);
    }

    public function store(Request $request)
    {
        $item = NhaCungCap::create($this->validated($request));

        return response()->json([
            'success' => true,
            'message' => 'Đã thêm nhà cung cấp.',
            'data' => $this->payload($item),
        ], 201);
    }

    public function update(Request $request, NhaCungCap $nha_cung_cap)
    {
        $nha_cung_cap->update($this->validated($request, $nha_cung_cap));

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật nhà cung cấp.',
            'data' => $this->payload($nha_cung_cap->refresh()),
        ]);
    }

    public function destroy(NhaCungCap $nha_cung_cap)
    {
        $nha_cung_cap->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa nhà cung cấp.',
        ]);
    }

    private function validated(Request $request, ?NhaCungCap $nhaCungCap = null): array
    {
        return $request->validate([
            'ten_nha_cung_cap' => ['required', 'string', 'max:255'],
            'ma_nha_cung_cap' => [
                'required',
                'string',
                'max:255',
                Rule::unique('nha_cung_cap', 'ma_nha_cung_cap')->ignore($nhaCungCap),
            ],
            'email_nha_cung_cap' => ['nullable', 'email', 'max:255'],
            'sdt_nha_cung_cap' => ['nullable', 'string', 'max:20'],
            'ten_nguoi_lien_he' => ['nullable', 'string', 'max:255'],
            'email_nguoi_lien_he' => ['nullable', 'email', 'max:255'],
            'sdt_nguoi_lien_he' => ['nullable', 'string', 'max:20'],
            'website' => ['nullable', 'string', 'max:255'],
            'dia_chi' => ['nullable', 'string', 'max:500'],
            'ma_so_thue' => ['nullable', 'string', 'max:30'],
            'so_tai_khoan' => ['nullable', 'string', 'max:50'],
            'ngan_hang' => ['nullable', 'string', 'max:255'],
            'trang_thai' => ['required', Rule::enum(NhaCungCapStatus::class)],
            'ghi_chu' => ['nullable', 'string', 'max:2000'],
        ], $this->messages());
    }

    private function messages(): array
    {
        return [
            'ten_nha_cung_cap.required' => 'Nhập tên nhà cung cấp.',
            'ma_nha_cung_cap.required' => 'Nhập mã nhà cung cấp.',
            'ma_nha_cung_cap.unique' => 'Mã nhà cung cấp đã được sử dụng.',
            'email_nha_cung_cap.email' => 'Email nhà cung cấp không hợp lệ.',
            'email_nguoi_lien_he.email' => 'Email người liên hệ không hợp lệ.',
            'trang_thai.required' => 'Chọn trạng thái.',
            'trang_thai.enum' => 'Trạng thái không hợp lệ.',
            'start.integer' => 'Vị trí bắt đầu không hợp lệ.',
            'start.min' => 'Vị trí bắt đầu không hợp lệ.',
            'limit.integer' => 'Số lượng mỗi trang không hợp lệ.',
            'limit.min' => 'Số lượng mỗi trang không hợp lệ.',
            'limit.max' => 'Số lượng mỗi trang tối đa 100.',
        ];
    }

    private function payload(NhaCungCap $item): array
    {
        return [
            'id' => $item->id,
            'ten_nha_cung_cap' => $item->ten_nha_cung_cap,
            'ma_nha_cung_cap' => $item->ma_nha_cung_cap,
            'email_nha_cung_cap' => $item->email_nha_cung_cap,
            'sdt_nha_cung_cap' => $item->sdt_nha_cung_cap,
            'ten_nguoi_lien_he' => $item->ten_nguoi_lien_he,
            'email_nguoi_lien_he' => $item->email_nguoi_lien_he,
            'sdt_nguoi_lien_he' => $item->sdt_nguoi_lien_he,
            'website' => $item->website,
            'dia_chi' => $item->dia_chi,
            'ma_so_thue' => $item->ma_so_thue,
            'so_tai_khoan' => $item->so_tai_khoan,
            'ngan_hang' => $item->ngan_hang,
            'trang_thai' => $item->trang_thai instanceof NhaCungCapStatus ? $item->trang_thai->value : $item->trang_thai,
            'ghi_chu' => $item->ghi_chu,
            'created_at' => $item->created_at,
        ];
    }
}
