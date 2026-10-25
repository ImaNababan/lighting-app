<?php

namespace App\Http\Controllers;

use App\Models\Klien;
use Illuminate\Http\Request;

class KlienController extends Controller
{
    public function index()
    {
        $klien = Klien::latest()->paginate(10);
        return view('klien.index', compact('klien'));
    }

    /**
     * Endpoint AJAX untuk autocomplete klien (dipakai Select2 di form Upload Denah).
     */
    public function search(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $klien = Klien::when($q !== '', function ($query) use ($q) {
            $query->where('nama', 'like', "%{$q}%")
                ->orWhere('nomor_telepon', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%");
        })
            ->orderBy('nama')
            ->limit(20)
            ->get(['id', 'nama', 'nomor_telepon', 'email', 'alamat']);

        return response()->json([
            'results' => $klien->map(function ($k) {
                return [
                    'id' => $k->id,
                    'text' => $k->nama,
                    'nama' => $k->nama,
                    'nomor_telepon' => $k->nomor_telepon,
                    'email' => $k->email,
                    'alamat' => $k->alamat,
                ];
            }),
        ]);
    }

    public function create()
    {
        return view('klien.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'nomor_telepon' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'alamat' => 'nullable|string',
        ]);

        Klien::create($validated);

        return redirect()->route('klien.index')
            ->with('success', 'Klien berhasil ditambahkan.');
    }

    public function edit(Klien $klien)
    {
        return view('klien.edit', compact('klien'));
    }

    public function update(Request $request, Klien $klien)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'nomor_telepon' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'alamat' => 'nullable|string',
        ]);

        $klien->update($validated);

        return redirect()->route('klien.index')
            ->with('success', 'Klien berhasil diperbarui.');
    }

    public function destroy(Klien $klien)
    {
        $klien->delete();

        return redirect()->route('klien.index')
            ->with('success', 'Klien berhasil dihapus.');
    }
}