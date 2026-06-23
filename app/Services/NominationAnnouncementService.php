<?php

namespace App\Services;

use App\Jobs\SendNominationAnnouncementEmailJob;
use App\Models\TimelineLomba;

class NominationAnnouncementService
{
    public function isAnnouncementWindowOpen(TimelineLomba $timeline): bool
    {
        if ($timeline->fase_kunci !== 'pengumuman_nominasi') {
            return false;
        }

        if (!$timeline->aktif) {
            return false;
        }

        if ($timeline->is_tba) {
            return false;
        }

        if (!$timeline->mulai_pada) {
            return false;
        }

        $now = now();

        if ($timeline->mulai_pada->greaterThan($now)) {
            return false;
        }

        if ($timeline->selesai_pada && $timeline->selesai_pada->lessThan($now)) {
            return false;
        }

        return true;
    }

    public function isAnnouncementWindowOpenForEdition(int $edisiId): bool
    {
        $timeline = TimelineLomba::query()
            ->where('edisi_lomba_id', $edisiId)
            ->where('fase_kunci', 'pengumuman_nominasi')
            ->orderByRaw('CASE WHEN mulai_pada IS NULL THEN 1 ELSE 0 END')
            ->orderBy('mulai_pada')
            ->first();

        return $timeline ? $this->isAnnouncementWindowOpen($timeline) : false;
    }

    public function queueForTimeline(
        TimelineLomba $timeline,
        int $delayMinutes = 5,
        ?string $onlyEmail = null,
    ): bool
    {
        $timeline->loadMissing('edisi:id,status,aktif');

        if (!$timeline->edisi || ($timeline->edisi->status !== 'aktif' && !$timeline->edisi->aktif)) {
            return false;
        }

        if (!$this->isAnnouncementWindowOpen($timeline)) {
            return false;
        }

        if ($timeline->nominasi_email_queued_at || $timeline->nominasi_email_sent_at) {
            return false;
        }

        $updated = TimelineLomba::query()
            ->whereKey($timeline->id)
            ->whereNull('nominasi_email_queued_at')
            ->whereNull('nominasi_email_sent_at')
            ->update([
                'nominasi_email_queued_at' => now(),
                'updated_at' => now(),
            ]);

        if ($updated !== 1) {
            return false;
        }

        SendNominationAnnouncementEmailJob::dispatch($timeline->id, $onlyEmail)
            ->delay(now()->addMinutes($delayMinutes))
            ->onConnection('database')
            ->onQueue('emails');

        return true;
    }

    public function queuePendingActiveTimelines(int $delayMinutes = 5, ?string $onlyEmail = null): int
    {
        $timelines = TimelineLomba::query()
            ->with('edisi:id,status,aktif')
            ->where('fase_kunci', 'pengumuman_nominasi')
            ->where('aktif', true)
            ->whereNull('nominasi_email_queued_at')
            ->whereNull('nominasi_email_sent_at')
            ->whereNotNull('mulai_pada')
            ->where('mulai_pada', '<=', now())
            ->whereHas('edisi', function ($query) {
                $query->where('status', 'aktif')
                    ->orWhere('aktif', true);
            })
            ->where(function ($query) {
                $query->whereNull('selesai_pada')
                    ->orWhere('selesai_pada', '>=', now());
            })
            ->orderBy('edisi_lomba_id')
            ->orderByRaw('CASE WHEN mulai_pada IS NULL THEN 1 ELSE 0 END')
            ->orderBy('mulai_pada')
            ->get();

        $count = 0;
        foreach ($timelines as $timeline) {
            if ($this->queueForTimeline($timeline, $delayMinutes, $onlyEmail)) {
                $count++;
            }
        }

        return $count;
    }
}
