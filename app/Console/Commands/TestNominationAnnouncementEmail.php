<?php

namespace App\Console\Commands;

use App\Mail\PengumumanNominasiMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestNominationAnnouncementEmail extends Command
{
    protected $signature = 'gemasi:test-nomination-email {email : Alamat email tujuan} {--name=Ketua Tim : Nama penerima di email}';

    protected $description = 'Mengirim email pengumuman nominasi test langsung ke satu alamat email';

    public function handle(): int
    {
        $email = trim((string) $this->argument('email'));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Email tujuan tidak valid.');
            return self::FAILURE;
        }

        $name = trim((string) $this->option('name')) ?: 'Ketua Tim';

        Mail::to($email)->send(new PengumumanNominasiMail([
            'recipient_name' => $name,
            'edition_name' => 'GEMASI 2026',
            'edition_year' => 2026,
            'timeline_title' => 'Pengumuman Nominasi',
            'work_name' => 'Uji Kirim Email',
            'category_name' => 'Testing',
            'dashboard_url' => url('/login'),
        ]));

        $this->info("Email test berhasil dikirim ke {$email}.");

        return self::SUCCESS;
    }
}
