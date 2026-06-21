<?php

namespace App\Jobs;

use App\Mail\PengumumanNominasiMail;
use App\Models\KaryaPeserta;
use App\Models\TimelineLomba;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendNominationAnnouncementEmailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $timelineId,
        public ?string $onlyEmail = null,
    )
    {
    }

    public function handle(): void
    {
        $timeline = TimelineLomba::query()
            ->with('edisi:id,nama,tahun')
            ->find($this->timelineId);

        if (!$timeline || $timeline->fase_kunci !== 'pengumuman_nominasi' || !$timeline->aktif) {
            return;
        }

        if ($timeline->nominasi_email_sent_at) {
            return;
        }

        $karyaList = KaryaPeserta::query()
            ->with('peserta:id,name,email')
            ->where('edisi_lomba_id', $timeline->edisi_lomba_id)
            ->where('status', 'submitted')
            ->where('lolos_nominasi', true)
            ->orderBy('nama_karya')
            ->get();

        foreach ($karyaList as $karya) {
            $recipient = $this->resolveKetuaRecipient($karya);
            if (!$recipient['email']) {
                continue;
            }

            if ($this->onlyEmail && strtolower(trim($recipient['email'])) !== strtolower(trim($this->onlyEmail))) {
                continue;
            }

            Mail::to($recipient['email'])->send(new PengumumanNominasiMail([
                'recipient_name' => $recipient['name'],
                'edition_name' => $timeline->edisi?->nama ?? 'GEMASI',
                'edition_year' => $timeline->edisi?->tahun,
                'timeline_title' => $timeline->judul,
                'work_name' => $karya->nama_karya,
                'category_name' => $karya->nama_kategori,
                'dashboard_url' => url('/login'),
            ]));
        }

        $timeline->forceFill([
            'nominasi_email_sent_at' => now(),
        ])->save();
    }

    private function resolveKetuaRecipient(KaryaPeserta $karya): array
    {
        $anggota = collect($karya->anggota_tim ?? []);
        $ketua = $anggota->first(function ($item) {
            return is_array($item) && (($item['peran'] ?? null) === 'ketua');
        });

        $name = trim((string) ($ketua['nama'] ?? ''));
        $email = trim((string) ($ketua['email'] ?? ''));

        if ($email === '') {
            $email = (string) ($karya->peserta?->email ?? '');
        }

        if ($name === '') {
            $name = (string) ($karya->peserta?->name ?? 'Ketua Tim');
        }

        return [
            'name' => $name,
            'email' => $email,
        ];
    }
}
