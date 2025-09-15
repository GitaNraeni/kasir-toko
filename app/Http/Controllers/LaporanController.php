<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Penjualan;
use DB;

class LaporanController extends Controller
{
    public function index()
    {
        return view('laporan.form');
    }

    public function harian(Request $request)
{
    $penjualan = Penjualan::with(['detil.produk'])
        ->join('users', 'users.id', 'penjualans.user_id')
        ->join('pelanggans', 'pelanggans.id', 'penjualans.pelanggan_id')
        ->whereDate('tanggal', $request->tanggal)
        ->select('penjualans.*', 'pelanggans.name as nama_pelanggan', 'users.name as nama_kasir')
        ->orderBy('id')
        ->get();

    // Hitung total hanya untuk transaksi yang tidak batal
    $total = $penjualan->where('status', '!=', 'batal')->sum('subtotal');

    // Pajak (misal 10%)
    $pajakPersen = 10;
    $pajak = ($total * $pajakPersen) / 100;
    $grandTotal = $total + $pajak;

    return view('laporan.harian', [
        'penjualan'   => $penjualan,
        'total'       => $total,
        'pajak'       => $pajak,
        'grandTotal'  => $grandTotal,
        'pajakPersen' => $pajakPersen
    ]);
}

    public function bulanan(Request $request)
    {
        $penjualan = Penjualan::select(
            DB::raw("DATE_FORMAT(tanggal, '%d/%m/%Y') as tgl"),
            DB::raw("SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as sukses"),
            DB::raw("SUM(CASE WHEN status = 'batal' THEN 1 ELSE 0 END) as dibatalkan"),
            DB::raw("COUNT(id) as jumlah_transaksi"),
            DB::raw("SUM(CASE WHEN status = 'batal' THEN total ELSE 0 END) as total_dibatalkan"),
            DB::raw("SUM(total) as jumlah_total")
        )
            ->whereMonth('tanggal', $request->bulan)
            ->whereYear('tanggal', $request->tahun)
            ->groupBy('tgl')
            ->get();
        
        $nama_bulan = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei',
            'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];

        $bulan = isset($nama_bulan[$request->bulan - 1]) ? $nama_bulan[$request->bulan - 1] : null;

        return view('laporan.bulanan', [
            'penjualan' => $penjualan,
            'bulan' => $bulan,
            'tahun' => $request->tahun
        ]);
    }
}