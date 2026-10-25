<?php

namespace App\Http\Controllers;

use App\Models\KatalogLampu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class KatalogLampuController extends Controller
{
    public function index()
    {
        $katalog = KatalogLampu::latest()->paginate(10);
        return view('katalog-lampu.index', compact('katalog'));
    }

    public function create()
    {
        return view('katalog-lampu.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode' => 'required|string|max:50|unique:katalog_lampu,kode',
            'deskripsi' => 'required|string',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'harga_satuan' => 'required|numeric|min:0',
            'satuan' => 'required|in:pcs,set,mtr',
        ]);

        if ($request->hasFile('foto')) {
            $validated['foto'] = $request->file('foto')->store('katalog-lampu', 'public');
        }

        KatalogLampu::create($validated);

        return redirect()->route('katalog-lampu.index')
            ->with('success', 'Kode lampu berhasil ditambahkan.');
    }

    public function edit(KatalogLampu $katalogLampu)
    {
        return view('katalog-lampu.edit', compact('katalogLampu'));
    }

    public function update(Request $request, KatalogLampu $katalogLampu)
    {
        $validated = $request->validate([
            'kode' => 'required|string|max:50|unique:katalog_lampu,kode,' . $katalogLampu->id,
            'deskripsi' => 'required|string',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'harga_satuan' => 'required|numeric|min:0',
            'satuan' => 'required|in:pcs,set,mtr',
        ]);

        if ($request->hasFile('foto')) {
            if ($katalogLampu->foto) {
                Storage::disk('public')->delete($katalogLampu->foto);
            }
            $validated['foto'] = $request->file('foto')->store('katalog-lampu', 'public');
        }

        $katalogLampu->update($validated);

        return redirect()->route('katalog-lampu.index')
            ->with('success', 'Kode lampu berhasil diperbarui.');
    }

    public function destroy(KatalogLampu $katalogLampu)
    {
        if ($katalogLampu->foto) {
            Storage::disk('public')->delete($katalogLampu->foto);
        }
        $katalogLampu->delete();

        return redirect()->route('katalog-lampu.index')
            ->with('success', 'Kode lampu berhasil dihapus.');
    }
}