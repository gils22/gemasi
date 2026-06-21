<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BobotPenilaianKategori;
use App\Models\Edition;
use App\Models\KategoriLomba;
use App\Models\KaryaPeserta;
use App\Models\PanduanLomba;
use App\Models\PenilaianTahapDua;
use App\Models\PenugasanJuriKategori;
use App\Models\PemenangArsip;
use App\Models\PemenangKarya;
use App\Models\TimelineLomba;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class EdisiLombaController extends Controller
{
    private function buatTimelineDefaultJikaBelumAda(Edition $edisi): void
    {
        $sudahAda = TimelineLomba::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->exists();

        if ($sudahAda) {
            return;
        }

        $defaultFase = [
            ['judul' => 'Opening GEMASI', 'fase_kunci' => 'opening'],
            ['judul' => 'Pendaftaran GEMASI', 'fase_kunci' => 'pendaftaran'],
            ['judul' => 'Penjurian Tahap 1', 'fase_kunci' => 'penjurian_tahap_1'],
            ['judul' => 'Pengumuman Nominasi', 'fase_kunci' => 'pengumuman_nominasi'],
            ['judul' => 'Penjurian Tahap 2', 'fase_kunci' => 'penjurian_tahap_2'],
            ['judul' => 'Pameran Karya', 'fase_kunci' => 'pameran_karya'],
            ['judul' => 'Awarding GEMASI', 'fase_kunci' => 'awarding'],
        ];

        $payload = array_map(function (array $fase) use ($edisi) {
            return [
                'edisi_lomba_id' => $edisi->id,
                'judul' => $fase['judul'],
                'tipe' => 'utama',
                'fase_kunci' => $fase['fase_kunci'],
                'mulai_pada' => null,
                'selesai_pada' => null,
                'is_tba' => true,
                'deskripsi' => null,
                'aktif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $defaultFase);

        TimelineLomba::query()->insert($payload);
    }

    private function hapusPaketEdisi(Edition $edisi): void
    {
        DB::transaction(function () use ($edisi) {
            KaryaPeserta::query()->where('edisi_lomba_id', $edisi->id)->delete();
            KategoriLomba::query()->where('edisi_lomba_id', $edisi->id)->delete();
            PanduanLomba::query()->where('edisi_lomba_id', $edisi->id)->delete();
            BobotPenilaianKategori::query()->where('edisi_lomba_id', $edisi->id)->delete();
            PenugasanJuriKategori::query()->where('edisi_lomba_id', $edisi->id)->delete();
            PenilaianTahapDua::query()->where('edisi_lomba_id', $edisi->id)->delete();
            PemenangKarya::query()->where('edisi_lomba_id', $edisi->id)->delete();
            PemenangArsip::query()->where('edisi_lomba_id', $edisi->id)->delete();
            TimelineLomba::query()->where('edisi_lomba_id', $edisi->id)->delete();

            DB::table('edisi_lomba_user_role')
                ->where('edisi_lomba_id', $edisi->id)
                ->delete();

            $edisi->delete();
        });
    }


    public function index()
    {
        $edisi = Edition::query()
            ->orderByDesc('tahun')
            ->get();

        return Inertia::render('Admin/Edisi/Index', [
            'daftarEdisi' => $edisi,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'tahun' => 'required|integer|min:2023|max:2100|unique:edisi_lomba,tahun',
            'status' => ['required', 'string', Rule::in(['draft', 'aktif', 'arsip'])],
            'mulai_pada' => 'nullable|date',
            'selesai_pada' => 'nullable|date|after_or_equal:mulai_pada',
        ]);

        if ($validated['status'] === 'aktif') {
            Edition::query()->update(['aktif' => false]);
            $validated['aktif'] = true;
        } else {
            $validated['aktif'] = false;
        }

        $edisi = Edition::query()->create($validated);
        $this->buatTimelineDefaultJikaBelumAda($edisi);

        return redirect()->back()->setStatusCode(303);
    }

    public function update(Request $request, Edition $edisi)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'tahun' => [
                'required',
                'integer',
                'min:2023',
                'max:2100',
                Rule::unique('edisi_lomba', 'tahun')->ignore($edisi->id),
            ],
            'status' => ['required', 'string', Rule::in(['draft', 'aktif', 'arsip'])],
            'mulai_pada' => 'nullable|date',
            'selesai_pada' => 'nullable|date|after_or_equal:mulai_pada',
        ]);

        if ($validated['status'] === 'aktif') {
            Edition::query()
                ->where('id', '!=', $edisi->id)
                ->update(['aktif' => false]);
            $validated['aktif'] = true;
        } else {
            $validated['aktif'] = false;
        }

        $edisi->update($validated);

        return redirect()->back()->setStatusCode(303);
    }

    public function aktifkan(Edition $edisi)
    {
        Edition::query()
            ->where('id', '!=', $edisi->id)
            ->update(['aktif' => false]);

        $edisi->update([
            'aktif' => true,
            'status' => 'aktif',
        ]);

        session(['edisi_aktif_id' => $edisi->id]);

        return redirect()->back()->setStatusCode(303);
    }

    public function selesaikan(Edition $edisi)
    {
        if ($edisi->status === 'arsip') {
            return redirect()->back()->setStatusCode(303);
        }

        $edisi->update([
            'status' => 'arsip',
            'aktif' => false,
        ]);

        if ((int) session('edisi_aktif_id') === (int) $edisi->id) {
            session()->forget('edisi_aktif_id');
        }

        return redirect()->back()->setStatusCode(303);
    }

    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:edisi_lomba,id',
            'force' => 'nullable|boolean',
        ]);

        $edisiList = Edition::query()
            ->whereIn('id', $validated['ids'])
            ->get();

        $force = (bool) ($validated['force'] ?? false);
        $blocked = [];
        $deleted = 0;

        foreach ($edisiList as $edisi) {
            if ($edisi->status === 'aktif' || (bool) $edisi->aktif) {
                $blocked[] = $edisi->nama;
                continue;
            }

            $hasRelated = KaryaPeserta::query()->where('edisi_lomba_id', $edisi->id)->exists()
                || KategoriLomba::query()->where('edisi_lomba_id', $edisi->id)->exists()
                || PanduanLomba::query()->where('edisi_lomba_id', $edisi->id)->exists()
                || BobotPenilaianKategori::query()->where('edisi_lomba_id', $edisi->id)->exists()
                || PenugasanJuriKategori::query()->where('edisi_lomba_id', $edisi->id)->exists()
                || PenilaianTahapDua::query()->where('edisi_lomba_id', $edisi->id)->exists()
                || PemenangKarya::query()->where('edisi_lomba_id', $edisi->id)->exists()
                || PemenangArsip::query()->where('edisi_lomba_id', $edisi->id)->exists();

            if ($hasRelated && !$force) {
                $blocked[] = $edisi->nama;
                continue;
            }

            if ($hasRelated && $force) {
                $this->hapusPaketEdisi($edisi);
            } else {
                TimelineLomba::query()
                    ->where('edisi_lomba_id', $edisi->id)
                    ->delete();

                DB::table('edisi_lomba_user_role')
                    ->where('edisi_lomba_id', $edisi->id)
                    ->delete();

                $edisi->delete();
            }
            $deleted++;
        }

        if (!empty($blocked)) {
            return back()
                ->withErrors([
                    'bulk' => 'Sebagian edisi tidak bisa dihapus karena masih aktif atau masih punya data: ' . implode(', ', $blocked),
                ])
                ->setStatusCode(303);
        }

        return redirect()->back()->with('success', "Berhasil menghapus {$deleted} edisi.");
    }

}
