@extends('layouts.laporan',['title'=>'Laporan Bulanan'])
@section('content')

<h1 class="text-center">Laporan Bulanan</h1>

<p>Bulan : {{ $bulan }} {{ $tahun }}</p>

<table class="table table-bordered table-sm">
    <thead>
        <tr>
            <th>No</th>
            <th>Tanggal</th>
            <th>Sukses</th>
            <th>Dibatalkan</th>
            <th>Jumlah Transaksi</th>
            <th>Total Dibatalkan</th>
            <th>Total</th>
        </tr>
    </thead>

    <tbody>
        @foreach ($penjualan as $key => $row)
            <tr>
                <td>{{ $key + 1 }}</td>
                <td>{{ $row->tgl }}</td>
                <td>{{ $row->sukses }}</td>
                <td>{{ $row->dibatalkan }}</td>
                <td>{{ $row->jumlah_transaksi }}</td>
                <td>Rp {{ number_format($row->total_dibatalkan, 0, ',', '.') }}</td>
                <td>Rp {{ number_format($row->jumlah_total, 0, ',', '.') }}</td>
            </tr>
        @endforeach
    </tbody>

    <tfoot>
        <tr>
            <th colspan="2">Jumlah Total</th>
            <th>{{ $penjualan->sum('sukses') }}</th>
            <th>{{ $penjualan->sum('dibatalkan') }}</th>
            <th>{{ $penjualan->sum('jumlah_transaksi') }}</th>
            <th>Rp {{ number_format($penjualan->sum('total_dibatalkan'), 0, ',', '.') }}</th>
            <th>Rp {{ number_format($penjualan->sum('jumlah_total'), 0, ',', '.') }}</th>
        </tr>
    </tfoot>
</table>
@endsection