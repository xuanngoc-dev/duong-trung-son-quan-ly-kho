<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TongQuanController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'tu_ngay' => ['nullable', 'date'],
            'den_ngay' => ['nullable', 'date'],
        ], [
            'tu_ngay.date' => 'Từ ngày không hợp lệ.',
            'den_ngay.date' => 'Đến ngày không hợp lệ.',
        ]);

        $rows = DB::table('phieu_kiem_tra_iqc as phieu')
            ->leftJoin('lo_nguyen_vat_lieu as lo', 'lo.id', '=', 'phieu.lo_nguyen_vat_lieu_id')
            ->leftJoin('danh_muc_nguyen_vat_lieu as nvl', 'nvl.id', '=', 'lo.nguyen_vat_lieu_id')
            ->leftJoin('nha_cung_cap as ncc', 'ncc.id', '=', 'nvl.nha_cung_cap_id')
            ->when($filters['tu_ngay'] ?? null, fn ($query, string $date) => $query->where('phieu.ngay_kiem_tra', '>=', $date.' 00:00:00'))
            ->when($filters['den_ngay'] ?? null, fn ($query, string $date) => $query->where('phieu.ngay_kiem_tra', '<=', $date.' 23:59:59'))
            ->groupBy('ncc.id', 'ncc.ma_nha_cung_cap', 'ncc.ten_nha_cung_cap')
            ->selectRaw("COALESCE(ncc.ten_nha_cung_cap, 'Chưa gắn nhà cung cấp') as ten")
            ->selectRaw("COALESCE(ncc.ma_nha_cung_cap, '') as ma")
            ->selectRaw("SUM(CASE WHEN phieu.ket_luan = 'OK' THEN 1 ELSE 0 END) as ok_count")
            ->selectRaw("SUM(CASE WHEN phieu.ket_luan = 'NG' THEN 1 ELSE 0 END) as ng_count")
            ->get()
            ->map(function ($row) {
                $ok = (int) $row->ok_count;
                $ng = (int) $row->ng_count;
                $tong = $ok + $ng;

                return [
                    'ten' => $row->ten,
                    'ma' => $row->ma,
                    'ok' => $ok,
                    'ng' => $ng,
                    'tong' => $tong,
                    'ty_le_ng' => $tong > 0 ? round($ng * 100 / $tong, 1) : 0,
                ];
            })
            ->filter(fn (array $row) => $row['tong'] > 0)
            ->values();

        $ok = (int) $rows->sum('ok');
        $ng = (int) $rows->sum('ng');
        $tong = $ok + $ng;
        $itLoi = $rows->sortBy([['ng', 'asc'], ['ty_le_ng', 'asc'], ['tong', 'desc']])->first();
        $nhieuLoi = $rows->sortBy([['ng', 'desc'], ['ty_le_ng', 'desc'], ['tong', 'desc']])->first();

        return response()->json([
            'success' => true,
            'data' => [
                'tong' => $tong,
                'ok' => $ok,
                'ng' => $ng,
                'ty_le_ok' => $tong > 0 ? round($ok * 100 / $tong, 1) : 0,
                'ty_le_ng' => $tong > 0 ? round($ng * 100 / $tong, 1) : 0,
                'it_loi' => $itLoi,
                'nhieu_loi' => $nhieuLoi,
                'nha_cung_cap' => $rows->sortByDesc('tong')->values(),
            ],
        ]);
    }
}
