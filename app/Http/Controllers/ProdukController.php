<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\Http\Request;

class ProdukController extends Controller
{

    public function index(Request $request)
    {
    $search = $request->search;

    $produks = Produk::join('kategoris', 'kategoris.id', 'produks.kategori_id')
        ->orderBy('produks.id')
        ->select('produks.*', 'nama_kategori')
        ->when($search, function ($q, $search) {
            return $q->where(function ($query) use ($search) {
                $query->where('produks.nama_produk', 'like', "%{$search}%")
                    ->orWhere('produks.kode_produk', 'like', "%{$search}%")
                    ->orWhere('kategoris.nama_kategori', 'like', "%{$search}%");
            });
        })
        ->paginate(10);

    if ($search) $produks->appends(['search' => $search]);

    return view('produk.index', [
        'produks' => $produks
    ]);
}

    public function create()
    {
        $dataKategori = Kategori::orderBy('nama_kategori')->get();

        $kategoris = [
            ['','Pilih Kategori:']
        ];

        foreach ($dataKategori as $kategori) {
            $kategoris[] = [$kategori->id, $kategori->nama_kategori];
        }

        return view('produk.create', [
            'kategoris' => $kategoris
        ]);
    }

    public function store(Request $request)
{
    $request->validate([
        'kode_produk' => ['required', 'max:250', 'unique:produks'],
        'nama_produk' => ['required', 'max:150'],
        'harga_produk' => ['required', 'numeric'], // harga modal
        'harga_jual' => ['required', 'numeric'],   // harga normal
        'diskon' => ['required', 'between:0,100'],
        'kategori_id' => ['required', 'exists:kategoris,id'],
    ]);

    // hitung harga setelah diskon
    $harga = $request->harga_jual - ($request->harga_jual * $request->diskon / 100);

    // simpan data produk
    $produk = Produk::create([
        'kode_produk' => $request->kode_produk,
        'nama_produk' => $request->nama_produk,
        'harga_produk' => $request->harga_produk,
        'harga_jual' => $request->harga_jual,
        'diskon' => $request->diskon,
        'kategori_id' => $request->kategori_id,
        'stok' => $request->stok ?? 0,
        'harga' => $harga, // 👈 WAJIB ditambah
    ]);

    return redirect()->route('produk.index')->with('store', 'success');
}

    public function show(Produk $produk)
    {
        abort(404);
    }

    public function edit(Produk $produk)
    {
        $dataKategori = Kategori::orderBy('nama_kategori')->get();

        $kategoris =[
            ['', 'Pilih Kategori:']
        ];

        foreach ($dataKategori as $kategori) {
            $kategoris[] = [$kategori->id, $kategori->nama_kategori];
        }

        return view('produk.edit', [
            'produk' => $produk,
            'kategoris' => $kategoris,
        ]);
    }

    public function update(Request $request, Produk $produk)
{
    $request->validate([
        'kode_produk' => ['required', 'max:250', 'unique:produks,kode_produk,' .$produk->id],
        'nama_produk' => ['required', 'max:150'],
        'harga_produk' => ['required', 'numeric'],
        'harga_jual' => ['required', 'numeric'],
        'diskon'=>['required','between:0,100'],
        'kategori_id' => ['required', 'exists:kategoris,id'],
    ]);

    $harga = $request->harga_jual - ($request->harga_jual * $request->diskon / 100);

    $produk->update([
        'kode_produk' => $request->kode_produk,
        'nama_produk' => $request->nama_produk,
        'kategori_id' => $request->kategori_id,
        'harga_produk'=> $request->harga_produk,
        'harga_jual'  => $request->harga_jual,
        'diskon'      => $request->diskon,
        'harga'       => $harga,
        // stok tidak ikut diupdate
    ]);

    return redirect()->route('produk.index')->with('update', 'success');
}

    public function destroy(Produk $produk)
    {
        if ($produk->detilPenjualans()->count() > 0) {
            return back()->with('error', '❌ Produk tidak bisa dihapus karena sudah pernah dipakai di transaksi!');
        }

        $produk->delete();
        return back()->with('destroy', 'success');
    }
}