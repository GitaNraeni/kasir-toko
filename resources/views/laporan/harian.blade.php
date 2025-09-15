@extends('layouts.laporan',['title'=>'Laporan Harian'])
@section('content')

<h1 class="text-center">Laporan Harian</h1>

<p>Tanggal : {{ date('d/m/Y', strtotime(request()->tanggal)) }}</p>

<table class="table table-bordered table-sm">
    <thead>
    <tr>
        <th>No</th>
        <th>No. Transaksi</th>
        <th>Produk Dibeli</th> {{-- Tambahan kolom --}}
        <th>Nama Pelanggan</th>
        <th>Kasir</th>
        <th>Status</th>
        <th>Waktu</th>
        <th>Total</th>
    </tr>
    </thead>

    <tbody>
    @foreach ($penjualan as $key => $row)
    <tr>
        <td>{{ $key + 1 }}</td>
        <td>{{ $row->nomor_transaksi }}</td>
        <td>
            @if($row->detil && $row->detil->count() > 0)
            @foreach($row->detil as $detil)
                <span class="badge badge-info">{{ $detil->produk->nama_produk }}</span>
            @endforeach
            @else
            <em>-</em>
            @endif
        </td>
        <td>{{ $row->nama_pelanggan }}</td>
        <td>{{ $row->nama_kasir }}</td>
        <td>{{ ucwords($row->status) }}</td>
        <td>{{ date('H:i:s', strtotime($row->tanggal)) }}</td>
        <td>
        @if($row->status == 'batal')
            (Rp {{ number_format($row->total, 0, ',', '.') }})
        @else
            Rp {{ number_format($row->total, 0, ',', '.') }}
        @endif
        </td>
    </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr>
        <th colspan="7">Jumlah Total</th>
    <th>{{ number_format($total, 0, ',', '.') }}</th>
    </tr>
    <tr>
        <th colspan="7">Pajak ({{ $pajakPersen }}%)</th>
        <th>{{ number_format($pajak, 0, ',', '.') }}</th>
    </tr>
    <tr>
        <th colspan="7">Total + Pajak</th>
        <th>{{ number_format($grandTotal, 0, ',', '.') }}</th>
    </tr>
</table>