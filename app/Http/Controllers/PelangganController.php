<?php

namespace App\Http\Controllers;

use App\Models\Pelanggan;
use Illuminate\Http\Request;

class PelangganController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $pelanggans = Pelanggan::orderBy('id')
            ->when($search, function ($q, $search) {
                return $q->where('name', 'like', "%{$search}%");
            })
            ->paginate();

        if ($search) $pelanggans->appends(['search' => $search]);

        return view('pelanggan.index', [
            'pelanggans' => $pelanggans
        ]);
    }

    public function create()
    {
        return view('pelanggan.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'max:100'],
            'alamat' => ['nullable', 'max:500'],
            'nomor_tlp' => ['numeric', 'nullable', 'min_digits:10', 'max_digits:14']
        ]);

        Pelanggan::create($request->all());

        return redirect()->route('pelanggan.index')->with('store', 'success');
    }

    public function show(Pelanggan $pelanggan)
    {
        abort(404);
    }

    public function edit(Pelanggan $pelanggan)
    {
        return view('pelanggan.edit', [
            'pelanggan' => $pelanggan
        ]);
    }

    public function update(Request $request, Pelanggan $pelanggan)
    {
        $request->validate([
            'name' => ['required', 'max:100'],
            'alamat' => ['nullable', 'max:500'],
            'nomor_tlp' => ['numeric', 'nullable', 'min_digits:10', 'max_digits:14']
        ]);

        $pelanggan->update($request->all());

        return redirect()->route('pelanggan.index')->with('update', 'success');
    }

    public function destroy(Pelanggan $pelanggan)
    {
        $pelanggan->delete();

        return back()->with('destroy', 'success');
    }
}
