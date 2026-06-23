<?php

namespace App\Http\Controllers;

use App\Models\Edition;
use App\Models\TimelineLomba;
use App\Services\NominationAnnouncementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TimelineLombaController extends Controller
{
    private const TAHAP_UTAMA_URUTAN = [
        'opening' => 1,
        'pendaftaran' => 2,
        'penjurian_tahap_1' => 3,
        'pengumuman_nominasi' => 4,
        'pameran_karya' => 5,
        'penjurian_tahap_2' => 6,
        'awarding' => 7,
    ];

    private function timelineOrderClause(): string
    {
        $case = "CASE";
        foreach (self::TAHAP_UTAMA_URUTAN as $fase => $urutan) {
            $case .= " WHEN fase_kunci = '{$fase}' THEN {$urutan}";
        }

        return $case . " ELSE 99 END";
    }

    private function resolveEdisiKonteks(Request $request): Edition
    {
        $selectedId = (int) session('edisi_aktif_id');
        if ($selectedId > 0) {
            $bySession = Edition::query()->find($selectedId);
            if ($bySession) {
                return $bySession;
            }
        }

        return $this->resolveEdisiBerjalanOrFail();
    }

    private function buatDefaultTimelineJikaBelumAda(Edition $edisi): void
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

    private function resolveEdisiBerjalanOrFail(): Edition
    {
        $tahunSekarang = (int) now()->format('Y');

        $edisi = Edition::query()->where('status', 'aktif')->first();
        if (!$edisi) {
            $edisi = Edition::query()->where('aktif', true)->first();
        }
        if (!$edisi) {
            $edisi = Edition::query()->where('tahun', $tahunSekarang)->first();
        }
        if (!$edisi) {
            $edisi = Edition::query()->orderByDesc('tahun')->first();
        }

        abort_if(!$edisi, 500, 'Edisi lomba belum tersedia.');

        return $edisi;
    }

    private function ensureTimelineDalamEdisi(TimelineLomba $timeline, Edition $edisi): void
    {
        abort_if((int) $timeline->edisi_lomba_id !== (int) $edisi->id, 404);
    }

    public function index(Request $request)
    {
        $edisi = $this->resolveEdisiKonteks($request);
        $this->buatDefaultTimelineJikaBelumAda($edisi);
        $isAdmin = $request->user()?->hasRole('admin') === true;
        $basePath = '/admin';
        $daftarEdisi = Edition::query()
            ->orderByDesc('tahun')
            ->get(['id', 'nama', 'tahun', 'status', 'aktif']);

        $timeline = TimelineLomba::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->orderByRaw($this->timelineOrderClause())
            ->orderByRaw('CASE WHEN mulai_pada IS NULL THEN 1 ELSE 0 END')
            ->orderBy('mulai_pada')
            ->orderBy('id')
            ->get();

        return Inertia::render('Timeline/Index', [
            'timeline' => $timeline,
            'edisiAktif' => [
                'id' => $edisi->id,
                'nama' => $edisi->nama,
                'tahun' => $edisi->tahun,
                'status' => $edisi->status,
                'aktif' => (bool) $edisi->aktif,
            ],
            // Admin tetap bisa mengelola data walaupun edisi yang dipilih berstatus arsip/draft.
            'modeArsip' => !$isAdmin && $edisi->status === 'arsip',
            'isEditable' => $isAdmin || $edisi->status === 'aktif',
            'isAdmin' => $isAdmin,
            'basePath' => $basePath,
            'daftarEdisi' => $daftarEdisi,
            'faseUtama' => [
                ['key' => 'opening', 'label' => 'Opening GEMASI'],
                ['key' => 'pendaftaran', 'label' => 'Pendaftaran GEMASI'],
                ['key' => 'penjurian_tahap_1', 'label' => 'Penjurian Tahap 1'],
                ['key' => 'pengumuman_nominasi', 'label' => 'Pengumuman Nominasi'],
                ['key' => 'penjurian_tahap_2', 'label' => 'Penjurian Tahap 2'],
                ['key' => 'pameran_karya', 'label' => 'Pameran Karya'],
                ['key' => 'awarding', 'label' => 'Awarding GEMASI'],
            ],
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()?->hasRole('admin'), 403);

        $edisi = $this->resolveEdisiKonteks($request);

        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'tipe' => ['required', Rule::in(['utama', 'tambahan'])],
            'fase_kunci' => [
                'required_if:tipe,utama',
                'nullable',
                Rule::in(['opening', 'pendaftaran', 'penjurian_tahap_1', 'pengumuman_nominasi', 'penjurian_tahap_2', 'pameran_karya', 'awarding']),
            ],
            'mulai_pada' => 'nullable|date_format:Y-m-d\TH:i',
            'selesai_pada' => 'nullable|date_format:Y-m-d\TH:i|after_or_equal:mulai_pada',
            'is_tba' => 'nullable|boolean',
            'deskripsi' => 'nullable|string|max:2000',
            'aktif' => 'nullable|boolean',
        ]);

        $isTba = (bool) ($validated['is_tba'] ?? false);
        if ($isTba) {
            $validated['mulai_pada'] = null;
            $validated['selesai_pada'] = null;
        }

        $timelineBaru = null;
        DB::transaction(function () use ($edisi, $validated, $isTba, &$timelineBaru) {
            $timelineBaru = TimelineLomba::query()->create([
                'edisi_lomba_id' => $edisi->id,
                'judul' => trim($validated['judul']),
                'tipe' => $validated['tipe'],
                'fase_kunci' => $validated['fase_kunci'] ?? null,
                'mulai_pada' => $validated['mulai_pada'] ?? null,
                'selesai_pada' => $validated['selesai_pada'] ?? null,
                'is_tba' => $isTba,
                'deskripsi' => $validated['deskripsi'] ?? null,
                'aktif' => (bool) ($validated['aktif'] ?? true),
            ]);
        });

        if ($timelineBaru?->aktif) {
            app(NominationAnnouncementService::class)->queueForTimeline($timelineBaru);
        }

        return redirect()->back()->setStatusCode(303);
    }

    public function update(Request $request, TimelineLomba $timeline)
    {
        abort_unless($request->user()?->hasRole('admin'), 403);

        $edisi = $this->resolveEdisiKonteks($request);
        $this->ensureTimelineDalamEdisi($timeline, $edisi);

        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'tipe' => ['required', Rule::in(['utama', 'tambahan'])],
            'fase_kunci' => [
                'required_if:tipe,utama',
                'nullable',
                Rule::in(['opening', 'pendaftaran', 'penjurian_tahap_1', 'pengumuman_nominasi', 'penjurian_tahap_2', 'pameran_karya', 'awarding']),
            ],
            'mulai_pada' => 'nullable|date_format:Y-m-d\TH:i',
            'selesai_pada' => 'nullable|date_format:Y-m-d\TH:i|after_or_equal:mulai_pada',
            'is_tba' => 'nullable|boolean',
            'deskripsi' => 'nullable|string|max:2000',
            'aktif' => 'nullable|boolean',
        ]);

        $isTba = (bool) ($validated['is_tba'] ?? false);
        if ($isTba) {
            $validated['mulai_pada'] = null;
            $validated['selesai_pada'] = null;
        }

        DB::transaction(function () use ($timeline, $validated, $isTba) {
            $timeline->update([
                'judul' => trim($validated['judul']),
                'tipe' => $validated['tipe'],
                'fase_kunci' => $validated['fase_kunci'] ?? null,
                'mulai_pada' => $validated['mulai_pada'] ?? null,
                'selesai_pada' => $validated['selesai_pada'] ?? null,
                'is_tba' => $isTba,
                'deskripsi' => $validated['deskripsi'] ?? null,
                'aktif' => (bool) ($validated['aktif'] ?? true),
            ]);
        });

        if ($timeline->aktif) {
            app(NominationAnnouncementService::class)->queueForTimeline($timeline);
        }

        return redirect()->back()->setStatusCode(303);
    }

    public function destroy(Request $request, TimelineLomba $timeline)
    {
        abort_unless($request->user()?->hasRole('admin'), 403);

        $edisi = $this->resolveEdisiKonteks($request);
        $this->ensureTimelineDalamEdisi($timeline, $edisi);

        DB::transaction(function () use ($timeline, $edisi) {
            $timeline->delete();
        });

        return redirect()->back()->setStatusCode(303);
    }

    public function toggleAktif(Request $request, TimelineLomba $timeline)
    {
        abort_unless($request->user()?->hasRole('admin'), 403);

        $edisi = $this->resolveEdisiKonteks($request);
        $this->ensureTimelineDalamEdisi($timeline, $edisi);

        $validated = $request->validate([
            'aktif' => 'required|boolean',
        ]);

        $timeline->update([
            'aktif' => (bool) $validated['aktif'],
        ]);

        if ($timeline->aktif) {
            app(NominationAnnouncementService::class)->queueForTimeline($timeline);
        }

        return redirect()->back()->setStatusCode(303);
    }

    public function bulkDelete(Request $request)
    {
        abort_unless($request->user()?->hasRole('admin'), 403);

        $edisi = $this->resolveEdisiKonteks($request);

        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:timeline_lomba,id',
        ]);

        TimelineLomba::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->whereIn('id', $validated['ids'])
            ->delete();

        return redirect()->back()->setStatusCode(303);
    }
}
