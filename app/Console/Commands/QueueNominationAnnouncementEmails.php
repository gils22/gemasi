<?php

namespace App\Console\Commands;

use App\Services\NominationAnnouncementService;
use Illuminate\Console\Command;

class QueueNominationAnnouncementEmails extends Command
{
    protected $signature = 'gemasi:queue-nomination-announcement-emails {--delay=5 : Delay dalam menit sebelum email dikirim} {--only-email= : Hanya kirim ke email tertentu untuk testing}';

    protected $description = 'Menjadwalkan email pengumuman nominasi untuk timeline aktif';

    public function handle(NominationAnnouncementService $service): int
    {
        $delayMinutes = max(0, (int) $this->option('delay'));
        $onlyEmail = trim((string) $this->option('only-email'));
        $onlyEmail = $onlyEmail !== '' ? $onlyEmail : null;
        $count = $service->queuePendingActiveTimelines($delayMinutes, $onlyEmail);

        $message = "{$count} timeline pengumuman nominasi telah dijadwalkan.";
        if ($onlyEmail) {
            $message .= " Mode test email: {$onlyEmail}.";
        }
        $this->info($message);

        return self::SUCCESS;
    }
}
