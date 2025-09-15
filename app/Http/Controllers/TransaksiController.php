<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DetilPenjualan;
use App\Models\Pelanggan;
use App\Models\Penjualan;
use App\Models\Produk;
use App\Models\User;
use Cart;

class TransaksiController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $penjualans = Penjualan::join('users', 'users.id', 'penjualans.user_id')
        ->join('pelanggans', 'pelanggans.id', 'penjualans.pelanggan_id')
        ->select('penjualans.*', 'users.name as nama_kasir', 'pelanggans.name as nama_pelanggan')
        ->orderBy('id', 'desc')
        ->when($search, function ($q, $search) {
            return $q->where('nomor_transaksi', 'like', "%{$search}%");
        })
        ->paginate();

        if ($search) $penjualans->appends(['search' => $search]);
        return view('transaksi.index', [
            'penjualans' => $penjualans
        ]);
    }

    public function create(Request $request) {
        return view('transaksi.create', [
            'nama_kasir' => $request->user()->name,
            'tanggal' => date('d F Y')
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'pelanggan_id' =>  ['nullable', 'exists:pelanggans,id'],
            'cash' => ['required', 'numeric', 'gte:total_bayar']
        ], [], [
            'pelanggan_id' => 'pelanggan'
        ]);

        $user = $request->user();
        $lastPenjualan = Penjualan::orderBy('id', 'desc')->first();

        $cart = Cart::name($user->id);
        $cartDetails = $cart->getDetails();

        $total = $cartDetails->get('total');
        $kembalian = $request->cash - $total;

        $no = $lastPenjualan ? $lastPenjualan->id + 1 : 1;
        $no = sprintf("%04d", $no);

        $allItems = $cartDetails->get('items');

        $pelangganId = $request->pelanggan_id ?? 6;

        $errors = [];

        foreach ($allItems as $key => $value) {
        $item = $allItems->get($key);
        $produk = Produk::find($item->id);

    if ($produk && $produk->stok < $item->quantity) {
        $errors["stok_{$produk->id}"] = 
            "Stok {$produk->nama_produk} tidak mencukupi! 
            (tersedia: {$produk->stok}, diminta: {$item->quantity})";
    }
}

if (!empty($errors)) {
    return back()->withErrors($errors);
}


        if (count($errors) > 0) {
            // semua produk yang dibeli stoknya kurang
            if (count($errors) == count($allitems)) {
                return back()->withErrors(['stok' => ['Semua produk yang dibeli stoknya kurang!']]);
            }

            return back()->withErrors(['stok' => $errors]);
        }

            $penjualan = Penjualan::create([
            'user_id' => $user->id,
            'pelanggan_id' => $pelangganId,
            'nomor_transaksi' => date('Ymd') . $no,
            'tanggal' => date('Y-m-d H:i:s'),
            'total' => $total,
            'tunai' => $request->cash,
            'kembalian' => $kembalian,
            'pajak' => $cartDetails->get('tax_amount'),
            'subtotal' => $cartDetails->get('subtotal')
        ]);

            foreach ($allItems as $key => $value) {
            $item = $allItems->get($key);
            $produk = Produk::find($item->id); 

            DetilPenjualan::create([
                'penjualan_id' => $penjualan->id,
                'produk_id' => $item->id,
                'jumlah' => $item->quantity,
                'harga_produk' => $item->price,
                'subtotal' => $item->price * $item->quantity,
            ]);

        if ($produk) {
        $produk->stok -= $item->quantity;
        $produk->save();
        }
        }

        $cart->destroy();

        return redirect()->route('transaksi.show', ['transaksi' => $penjualan->id]);
    }
     
    public function show(Request $request, Penjualan $transaksi)
    {
        $pelanggan = Pelanggan::find($transaksi->pelanggan_id);
        $user = User::find($transaksi->user_id);
        $detilPenjualan = DetilPenjualan::join('produks', 'produks.id', 'detil_penjualans.produk_id')
           ->select('detil_penjualans.*', 'nama_produk')
           ->where('penjualan_id', $transaksi->id)
           ->get();

           return view('transaksi.invoice', [
            'penjualan' => $transaksi,
            'pelanggan' => $pelanggan,
            'user' => $user,
            'detilPenjualan' => $detilPenjualan
           ]);
    }

    public function destroy(Request $request, Penjualan $transaksi)
    {
        $detils = DetilPenjualan::where('penjualan_id', $transaksi->id)->get();

        foreach ($detils as $detil) {
        $produk = Produk::find($detil->produk_id);
        if ($produk) {
            $produk->stok += $detil->jumlah; // tambahin lagi stok sesuai jumlah
            $produk->save();
        }
    }
        $transaksi->update([
            'status'=>'batal'
        ]);

        return back()->with('destroy','success');
    }

    public function produk(Request $request)
    {
        $search = $request->search;
        $produks = Produk::select('id', 'kode_produk', 'nama_produk')
            ->when($search, function ($q, $search) {
            return $q->where('nama_produk', 'like', "%{$search}%");
            })
            ->orderBy('nama_produk')
            ->take(15)
            ->get();

        return response()->json($produks);
    }

    public function pelanggan(Request $request)
    {
        $search = $request->search;
        $pelanggans = Pelanggan::select('id', 'name')
            ->when($search, function ($q, $search) {
            return $q->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->take(15)
            ->get();

            return response()->json($pelanggans);
    }

    public function addPelanggan(Request $request)
    {
        $request->validate([
            'id' => ['required', 'exists:pelanggans,id']
        ]);
        $pelanggan = Pelanggan::find($request->id);

        $cart = Cart::name($request->user()->id);

        $cart->setExtraInfo([
            'pelanggan' => [
                'id' => $pelanggan->id,
                'name' => $pelanggan->name,
            ]
            ]);

            return response()->json(['message' => 'Berhasil.']);
    }

    public function cetak(Penjualan $transaksi)
    {
        $pelanggan = Pelanggan::find($transaksi->pelanggan_id);
        $user = User::find($transaksi->user_id);
        $detilPenjualan = DetilPenjualan::join('produks', 'produks.id', 'detil_penjualans.produk_id')
            ->select('detil_penjualans.*', 'nama_produk')
            ->where('penjualan_id', $transaksi->id)->get();

            return view('transaksi.cetak', [
                'penjualan' => $transaksi,
                'pelanggan' => $pelanggan,
                'user' => $user,
                'detilPenjualan' => $detilPenjualan
            ]);
    }
}