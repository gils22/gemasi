<?php

namespace App\Services;

use App\Models\Edition;

class EditionStatusSynchronizer
{
    public function archiveExpiredEditions(): void
    {
        Edition::query()
            ->where('status', 'aktif')
            ->whereNotNull('selesai_pada')
            ->where('selesai_pada', '<=', now())
            ->update([
                'status' => 'arsip',
                'aktif' => false,
            ]);
    }
}
