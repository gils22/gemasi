<?php

namespace App\Services;

use App\Jobs\SendNominationAnnouncementEmailJob;
use App\Models\TimelineLomba;

class NominationAnnouncementService
{
    public function queueForTimeline(
        TimelineLomba $timeline,
        int $delayMinutes = 5,
        ?string $onlyEmail = null,
    ): bool
    {
        if ($timeline->fase_kunci !== 'pengumuman_nominasi') {
            return false;
        }

        if (!$timeline->aktif) {
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
            ->where('fase_kunci', 'pengumuman_nominasi')
            ->where('aktif', true)
            ->whereNull('nominasi_email_queued_at')
            ->whereNull('nominasi_email_sent_at')
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
