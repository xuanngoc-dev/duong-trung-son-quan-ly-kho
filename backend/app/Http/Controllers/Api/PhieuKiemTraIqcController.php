<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\KetLuanIqc;
use App\Models\PhieuKiemTraIqc;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PhieuKiemTraIqcController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'ket_luan' => ['nullable', Rule::enum(KetLuanIqc::class)],
            'lo_nguyen_vat_lieu_id' => ['nullable', 'integer', Rule::exists('lo_nguyen_vat_lieu', 'id')],
            'nha_cung_cap_id' => ['nullable', 'array'],
            'nha_cung_cap_id.*' => ['integer', Rule::exists('nha_cung_cap', 'id')],
            'tu_ngay' => ['nullable', 'date'],
            'den_ngay' => ['nullable', 'date'],
            'start' => ['nullable', 'integer', 'min:0'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ], $this->messages());

        $query = PhieuKiemTraIqc::query()
            ->with('loNguyenVatLieu.nguyenVatLieu:id,ma_nguyen_vat_lieu,ten_nguyen_vat_lieu,don_vi_tinh')
            ->when($filters['q'] ?? null, function ($query, string $keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('ma_qr_barcode', 'like', "%{$keyword}%")
                        ->orWhere('nguoi_kiem_tra', 'like', "%{$keyword}%")
                        ->orWhereHas('loNguyenVatLieu', function ($query) use ($keyword) {
                            $query->where('so_lo_batch', 'like', "%{$keyword}%");
                        });
                });
            })
            ->when($filters['ket_luan'] ?? null, fn ($query, string $ketLuan) => $query->where('ket_luan', $ketLuan))
            ->when($filters['lo_nguyen_vat_lieu_id'] ?? null, fn ($query, int $id) => $query->where('lo_nguyen_vat_lieu_id', $id))
            ->when($filters['nha_cung_cap_id'] ?? null, function ($query, array $ids) {
                $query->whereHas('loNguyenVatLieu.nguyenVatLieu', fn ($query) => $query->whereIn('nha_cung_cap_id', $ids));
            })
            ->when($filters['tu_ngay'] ?? null, fn ($query, string $date) => $query->where('ngay_kiem_tra', '>=', $date.' 00:00:00'))
            ->when($filters['den_ngay'] ?? null, fn ($query, string $date) => $query->where('ngay_kiem_tra', '<=', $date.' 23:59:59'));

        $total = (clone $query)->count();
        $items = $query
            ->latest('ngay_tao')
            ->skip($filters['start'] ?? 0)
            ->take($filters['limit'] ?? 10)
            ->get()
            ->map(fn (PhieuKiemTraIqc $item) => $this->payload($item));

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

        $path = $request->file('file')->store('phieu-kiem-tra-iqc', 'public');

        return response()->json([
            'success' => true,
            'pathFile' => '/storage/'.$path,
        ]);
    }

    public function store(Request $request)
    {
        $item = PhieuKiemTraIqc::create($this->validated($request));

        return response()->json([
            'success' => true,
            'message' => 'Đã thêm phiếu kiểm tra IQC.',
            'data' => $this->payload($item->load('loNguyenVatLieu.nguyenVatLieu:id,ma_nguyen_vat_lieu,ten_nguyen_vat_lieu,don_vi_tinh')),
        ], 201);
    }

    public function update(Request $request, PhieuKiemTraIqc $phieu_kiem_tra_iqc)
    {
        $phieu_kiem_tra_iqc->update($this->validated($request));

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật phiếu kiểm tra IQC.',
            'data' => $this->payload($phieu_kiem_tra_iqc->refresh()->load('loNguyenVatLieu.nguyenVatLieu:id,ma_nguyen_vat_lieu,ten_nguyen_vat_lieu,don_vi_tinh')),
        ]);
    }

    public function destroy(PhieuKiemTraIqc $phieu_kiem_tra_iqc)
    {
        $phieu_kiem_tra_iqc->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa phiếu kiểm tra IQC.',
        ]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'lo_nguyen_vat_lieu_id' => ['required', 'integer', Rule::exists('lo_nguyen_vat_lieu', 'id')],
            'ma_qr_barcode' => ['nullable', 'string', 'max:100'],
            'so_luong_lay_mau' => ['required', 'numeric', 'min:0', 'decimal:0,4'],
            'noi_dung_chi_tiet' => ['nullable', 'array'],
            'noi_dung_chi_tiet.*.hang_muc' => ['required', 'string', 'max:255'],
            'noi_dung_chi_tiet.*.ket_qua' => ['required', Rule::in(['OK', 'NG'])],
            'noi_dung_chi_tiet.*.ghi_chu' => ['nullable', 'string', 'max:500'],
            'nguoi_kiem_tra' => ['required', 'string', 'max:100'],
            'ngay_kiem_tra' => ['required', 'date'],
            'ket_luan' => ['required', Rule::enum(KetLuanIqc::class)],
            'ghi_chu' => ['nullable', 'string', 'max:2000'],
            'hinh_anh_dinh_kem' => ['nullable', 'array', 'max:20'],
            'hinh_anh_dinh_kem.*' => ['required', 'string', 'max:2048', function (string $attribute, mixed $value, \Closure $fail) {
                if (str_starts_with($value, '/storage/')) {
                    return;
                }
                if (! filter_var($value, FILTER_VALIDATE_URL)) {
                    $fail('Link hình ảnh không hợp lệ.');
                }
            }],
        ], $this->messages());

        $data['noi_dung_chi_tiet'] = array_values($data['noi_dung_chi_tiet'] ?? []);
        $data['hinh_anh_dinh_kem'] = array_values(array_filter(
            $data['hinh_anh_dinh_kem'] ?? [],
            fn ($path) => trim((string) $path) !== '',
        ));

        return $data;
    }

    private function messages(): array
    {
        return [
            'lo_nguyen_vat_lieu_id.required' => 'Chọn lô nguyên vật liệu.',
            'lo_nguyen_vat_lieu_id.exists' => 'Lô nguyên vật liệu không tồn tại.',
            'so_luong_lay_mau.required' => 'Nhập số lượng lấy mẫu.',
            'so_luong_lay_mau.numeric' => 'Số lượng lấy mẫu không hợp lệ.',
            'so_luong_lay_mau.min' => 'Số lượng lấy mẫu không được âm.',
            'so_luong_lay_mau.decimal' => 'Số lượng lấy mẫu tối đa 4 chữ số thập phân.',
            'noi_dung_chi_tiet.*.hang_muc.required' => 'Nhập hạng mục kiểm tra.',
            'noi_dung_chi_tiet.*.ket_qua.required' => 'Chọn kết quả hạng mục.',
            'noi_dung_chi_tiet.*.ket_qua.in' => 'Kết quả hạng mục chỉ nhận OK hoặc NG.',
            'nguoi_kiem_tra.required' => 'Nhập người kiểm tra.',
            'ngay_kiem_tra.required' => 'Chọn ngày kiểm tra.',
            'ngay_kiem_tra.date' => 'Ngày kiểm tra không hợp lệ.',
            'ket_luan.required' => 'Chọn kết luận.',
            'ket_luan.enum' => 'Kết luận chỉ nhận OK hoặc NG.',
            'nha_cung_cap_id.array' => 'Nhà cung cấp không hợp lệ.',
            'nha_cung_cap_id.*.integer' => 'Nhà cung cấp không hợp lệ.',
            'nha_cung_cap_id.*.exists' => 'Nhà cung cấp không tồn tại.',
            'tu_ngay.date' => 'Từ ngày kiểm tra không hợp lệ.',
            'den_ngay.date' => 'Đến ngày kiểm tra không hợp lệ.',
            'hinh_anh_dinh_kem.array' => 'Danh sách hình ảnh không hợp lệ.',
            'hinh_anh_dinh_kem.max' => 'Tối đa 20 hình ảnh.',
            'hinh_anh_dinh_kem.*.required' => 'Nhập đường dẫn hình ảnh.',
            'hinh_anh_dinh_kem.*.max' => 'Đường dẫn hình ảnh tối đa 2048 ký tự.',
            'start.integer' => 'Vị trí bắt đầu không hợp lệ.',
            'start.min' => 'Vị trí bắt đầu không hợp lệ.',
            'limit.integer' => 'Số lượng mỗi trang không hợp lệ.',
            'limit.min' => 'Số lượng mỗi trang không hợp lệ.',
            'limit.max' => 'Số lượng mỗi trang tối đa 100.',
        ];
    }

    private function payload(PhieuKiemTraIqc $item): array
    {
        $lot = $item->loNguyenVatLieu;
        $material = $lot?->nguyenVatLieu;
        $ketLuan = $item->ket_luan instanceof KetLuanIqc ? $item->ket_luan->value : $item->ket_luan;

        return [
            'id' => $item->id,
            'lo_nguyen_vat_lieu_id' => $item->lo_nguyen_vat_lieu_id,
            'lo_nguyen_vat_lieu' => $lot ? [
                'id' => $lot->id,
                'so_lo_batch' => $lot->so_lo_batch,
                'nguyen_vat_lieu' => $material ? [
                    'id' => $material->id,
                    'ma_nguyen_vat_lieu' => $material->ma_nguyen_vat_lieu,
                    'ten_nguyen_vat_lieu' => $material->ten_nguyen_vat_lieu,
                    'don_vi_tinh' => $material->don_vi_tinh,
                ] : null,
            ] : null,
            'ma_qr_barcode' => $item->ma_qr_barcode,
            'so_luong_lay_mau' => $item->so_luong_lay_mau,
            'noi_dung_chi_tiet' => $item->noi_dung_chi_tiet ?? [],
            'nguoi_kiem_tra' => $item->nguoi_kiem_tra,
            'ngay_kiem_tra' => $item->ngay_kiem_tra?->format('Y-m-d H:i:s'),
            'ket_luan' => $ketLuan,
            'ghi_chu' => $item->ghi_chu,
            'hinh_anh_dinh_kem' => $item->hinh_anh_dinh_kem ?? [],
            'ngay_tao' => $item->ngay_tao,
        ];
    }
}
