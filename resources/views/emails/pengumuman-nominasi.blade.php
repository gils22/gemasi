<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengumuman Nominasi GEMASI</title>
</head>

<body style="margin:0;background:#f8fafc;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">
        {{ $data['preheader'] ?? 'Karya Anda berhasil lolos nominasi pada GEMASI. Silakan cek detailnya di dashboard.' }}
    </div>

    <div style="max-width:640px;margin:0 auto;padding:32px 16px;">
        <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">
            <div style="padding:28px 32px;background:#4f46e5;border-bottom:1px solid #e0e7ff;">
                <p
                    style="margin:0 0 8px;font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#ffffff;">
                    GEMASI {{ $data['edition_year'] ?? '' }}
                </p>
                <h1 style="margin:0;font-size:26px;line-height:1.3;font-weight:700;color:#ffffff;">
                    Pengumuman Nominasi
                </h1>
                <p style="margin:10px 0 0;font-size:14px;line-height:1.7;color:#e2e8f0;">
                    Informasi hasil nominasi untuk karya yang didaftarkan.
                </p>
            </div>

            <div style="padding:32px;">
                <p style="margin:0 0 16px;font-size:15px;line-height:1.8;color:#1e293b;">
                    Halo <strong>{{ $data['recipient_name'] ?? 'Ketua Tim' }}</strong>,
                </p>

                <p style="margin:0 0 16px;font-size:15px;line-height:1.8;color:#1e293b;">
                    Karya <strong>{{ $data['work_name'] ?? '-' }}</strong> pada kategori
                    <strong>{{ $data['category_name'] ?? '-' }}</strong> telah dinyatakan lolos nominasi pada
                    GEMASI {{ $data['edition_year'] ?? '' }}.
                </p>

                <div
                    style="margin:24px 0;padding:16px 18px;border-radius:12px;background:#f8fafc;border:1px solid #e2e8f0;">
                    <p
                        style="margin:0 0 10px;font-size:13px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:.06em;">
                        Informasi Penting
                    </p>
                    <ul style="margin:0;padding-left:18px;font-size:14px;line-height:1.8;color:#334155;">
                        <li>Silakan cek informasi lanjutan pada dashboard.</li>
                        <li>Persiapkan kebutuhan Pameran Karya.</li>
                        <li>Persiapkan tahap Penjurian Akhir sesuai jadwal timeline.</li>
                    </ul>
                </div>

                <div style="margin:28px 0 8px;">
                    <a href="{{ $data['dashboard_url'] ?? route('login') }}"
                        style="display:inline-block;background:#4f46e5;color:#ffffff;text-decoration:none;padding:14px 22px;border-radius:10px;font-size:14px;font-weight:700;">
                        Buka Dashboard
                    </a>
                </div>

                <p style="margin:20px 0 0;font-size:13px;line-height:1.7;color:#64748b;">
                    Email ini dikirim otomatis oleh Sistem GEMASI. Jika Anda merasa ada kekeliruan, silakan hubungi
                    panitia.
                </p>
            </div>
        </div>
    </div>
</body>

</html>
