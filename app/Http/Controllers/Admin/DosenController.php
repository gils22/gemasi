<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class DosenController extends Controller
{
    public function index()
    {
        $dosen = Dosen::query()
            ->orderBy('nama')
            ->get(['id', 'nik', 'nama', 'email', 'bidang', 'aktif', 'updated_at']);

        return Inertia::render('Admin/Users/Dosen', [
            'dosen' => $dosen,
        ]);
    }

    public function store(Request $request)
    {
        $rules = [
            'nik' => ['required', 'string', 'max:50'],
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:dosen,email'],
            'bidang' => ['nullable', 'string', 'max:255'],
            'aktif' => ['required', 'boolean'],
        ];

        // Paksa pesan validasi menggunakan Bahasa Indonesia (untuk konsistensi UI).
        $messages = [
            'required' => 'Kolom :attribute wajib diisi.',
            'email' => 'Kolom :attribute harus berupa alamat email yang valid.',
            'unique' => 'Kolom :attribute sudah digunakan.',
            'max' => 'Kolom :attribute maksimal :max karakter.',
            'boolean' => 'Kolom :attribute tidak valid.',
        ];

        $attributes = [
            'nik' => 'NIK',
            'nama' => 'Nama',
            'email' => 'Email',
            'bidang' => 'Bidang',
            'aktif' => 'Status',
        ];

        $validated = $request->validate($rules, $messages, $attributes);

        Dosen::create($validated);

        return redirect()->back()->with('success', 'Data dosen berhasil ditambahkan.')->setStatusCode(303);
    }

    public function import(Request $request)
    {
        $validated = $request->validate([
            'file' => ['nullable', 'file', 'mimes:csv,txt', 'max:5120'],
            'rows' => ['nullable', 'array', 'min:1'],
            'rows.*.nik' => ['required_with:rows', 'string', 'max:50'],
            'rows.*.nama' => ['required_with:rows', 'string', 'max:255'],
            'rows.*.email' => ['required_with:rows', 'string', 'max:255'],
        ], [
            'rows.array' => 'Format data tidak valid.',
            'rows.min' => 'Tidak ada data yang bisa diimpor.',
            'rows.*.nik.required_with' => 'Kolom NIK wajib diisi.',
            'rows.*.nama.required_with' => 'Kolom Nama wajib diisi.',
            'rows.*.email.required_with' => 'Kolom Email wajib diisi.',
        ]);

        $hasRows = is_array($validated['rows'] ?? null) && !empty($validated['rows']);
        $hasFile = $request->hasFile('file');

        if (!$hasRows && !$hasFile) {
            return redirect()->back()->withErrors([
                'file' => 'Pilih file Excel atau CSV terlebih dahulu.',
            ])->setStatusCode(303);
        }

        // Import dari Excel (frontend parsing) akan mengirim payload rows.
        if ($hasRows) {
            $records = [];
            $errors = [];
            $seenEmails = [];

            $rowNum = 0;
            foreach (($validated['rows'] ?? []) as $row) {
                $rowNum++;
                $nik = trim((string) ($row['nik'] ?? ''));
                $nama = trim((string) ($row['nama'] ?? ''));
                $email = trim((string) ($row['email'] ?? ''));

                if ($nik === '' || $nama === '' || $email === '') {
                    $errors[] = "Baris {$rowNum}: NIK, Nama, dan Email wajib diisi.";
                    continue;
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Baris {$rowNum}: Format email tidak valid.";
                    continue;
                }

                $emailKey = strtolower($email);
                if (isset($seenEmails[$emailKey])) {
                    $errors[] = "Baris {$rowNum}: Email duplikat di file.";
                    continue;
                }
                $seenEmails[$emailKey] = true;

                $records[] = [
                    'nik' => $nik,
                    'nama' => $nama,
                    'email' => $email,
                    'bidang' => null,
                    'aktif' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($records) > 5000) {
                    $errors[] = 'Maksimal 5000 baris per import.';
                    break;
                }
            }

            if (empty($records)) {
                return redirect()->back()->withErrors([
                    'rows' => !empty($errors)
                        ? implode(' ', array_slice($errors, 0, 3))
                        : 'Tidak ada data yang bisa diimpor.',
                ])->setStatusCode(303);
            }

            if (!empty($errors)) {
                return redirect()->back()->withErrors([
                    'rows' => implode(' ', array_slice($errors, 0, 5)),
                ])->setStatusCode(303);
            }

            DB::transaction(function () use ($records) {
                Dosen::query()->upsert(
                    $records,
                    ['email'],
                    ['nik', 'nama', 'updated_at']
                );
            });

            return redirect()->back()->with('success', 'Import data dosen berhasil.')->setStatusCode(303);
        }

        $path = $request->file('file')?->getRealPath();
        if (!$path) {
            return redirect()->back()->withErrors([
                'file' => 'File tidak valid.',
            ])->setStatusCode(303);
        }

        $handle = fopen($path, 'r');
        if (!$handle) {
            return redirect()->back()->withErrors([
                'file' => 'Gagal membaca file.',
            ])->setStatusCode(303);
        }

        $readRow = function ($h) {
            $line = fgets($h);
            if ($line === false) return null;
            $line = trim($line);
            if ($line === '') return [];

            // Detect delimiter (comma vs semicolon) from the first non-empty line.
            $comma = substr_count($line, ',');
            $semi = substr_count($line, ';');
            $delimiter = $semi > $comma ? ';' : ',';
            return str_getcsv($line, $delimiter);
        };

        $first = $readRow($handle);
        if ($first === null) {
            fclose($handle);
            return redirect()->back()->withErrors([
                'file' => 'File kosong.',
            ])->setStatusCode(303);
        }

        $normalize = fn ($v) => strtolower(trim((string) $v));
        $header = array_map($normalize, $first);
        $hasHeader = in_array('email', $header, true) || in_array('nama', $header, true);

        // Only take: nik, nama, email. Ignore any other columns.
        $map = [
            'nik' => null,
            'nama' => null,
            'email' => null,
        ];

        if ($hasHeader) {
            foreach ($header as $i => $col) {
                if (array_key_exists($col, $map)) {
                    $map[$col] = $i;
                }
            }

            if ($map['nik'] === null || $map['nama'] === null || $map['email'] === null) {
                fclose($handle);
                return redirect()->back()->withErrors([
                    'file' => 'Header kolom wajib berisi: nik, nama, email.',
                ])->setStatusCode(303);
            }
        } else {
            // Default order: nik,nama,email (other columns ignored)
            $map['nik'] = 0;
            $map['nama'] = 1;
            $map['email'] = 2;
            // Use first row as data by rewinding.
            rewind($handle);
        }

        $records = [];
        $errors = [];
        $rowNum = 0;
        $seenEmails = [];

        // Determine delimiter again for fgetcsv reads.
        $delimiter = ',';
        $sampleLine = null;
        $pos = ftell($handle);
        rewind($handle);
        while (($sampleLine = fgets($handle)) !== false) {
            $sampleLine = trim($sampleLine);
            if ($sampleLine !== '') {
                $comma = substr_count($sampleLine, ',');
                $semi = substr_count($sampleLine, ';');
                $delimiter = $semi > $comma ? ';' : ',';
                break;
            }
        }
        fseek($handle, $pos);

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNum++;
            if (!is_array($row) || (count($row) === 1 && trim((string) $row[0]) === '')) {
                continue;
            }

            $get = function (string $key) use ($row, $map) {
                $idx = $map[$key];
                if ($idx === null) return null;
                return $row[$idx] ?? null;
            };

            $nama = trim((string) ($get('nama') ?? ''));
            $email = trim((string) ($get('email') ?? ''));
            $nik = trim((string) ($get('nik') ?? ''));
            if ($nik === '' || $nama === '' || $email === '') {
                $errors[] = "Baris {$rowNum}: NIK, Nama, dan Email wajib diisi.";
                continue;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Baris {$rowNum}: Format email tidak valid.";
                continue;
            }

            $emailKey = strtolower($email);
            if (isset($seenEmails[$emailKey])) {
                $errors[] = "Baris {$rowNum}: Email duplikat di file.";
                continue;
            }
            $seenEmails[$emailKey] = true;

            $records[] = [
                'nik' => $nik,
                'nama' => $nama,
                'email' => $email,
                // Only fields above are imported; keep other fields default / existing.
                'bidang' => null,
                'aktif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        fclose($handle);

        if (empty($records)) {
            return redirect()->back()->withErrors([
                'file' => !empty($errors) ? implode(' ', array_slice($errors, 0, 3)) : 'Tidak ada data yang bisa diimpor.',
            ])->setStatusCode(303);
        }

        if (!empty($errors)) {
            return redirect()->back()->withErrors([
                'file' => implode(' ', array_slice($errors, 0, 5)),
            ])->setStatusCode(303);
        }

        DB::transaction(function () use ($records) {
            Dosen::query()->upsert(
                $records,
                ['email'],
                // Only update imported fields.
                ['nik', 'nama', 'updated_at']
            );
        });

        return redirect()->back()->with('success', 'Import data dosen berhasil.')->setStatusCode(303);
    }

    public function update(Request $request, Dosen $dosen)
    {
        $rules = [
            'nik' => ['required', 'string', 'max:50'],
            'nama' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('dosen', 'email')->ignore($dosen->id),
            ],
            'bidang' => ['nullable', 'string', 'max:255'],
            'aktif' => ['required', 'boolean'],
        ];

        $messages = [
            'required' => 'Kolom :attribute wajib diisi.',
            'email' => 'Kolom :attribute harus berupa alamat email yang valid.',
            'unique' => 'Kolom :attribute sudah digunakan.',
            'max' => 'Kolom :attribute maksimal :max karakter.',
            'boolean' => 'Kolom :attribute tidak valid.',
        ];

        $attributes = [
            'nik' => 'NIK',
            'nama' => 'Nama',
            'email' => 'Email',
            'bidang' => 'Bidang',
            'aktif' => 'Status',
        ];

        $validated = $request->validate($rules, $messages, $attributes);

        $dosen->update($validated);

        return redirect()->back()->with('success', 'Data dosen berhasil diperbarui.')->setStatusCode(303);
    }

    public function toggleAktif(Request $request, Dosen $dosen)
    {
        $validated = $request->validate([
            'aktif' => ['required', 'boolean'],
        ], [
            'aktif.required' => 'Status wajib diisi.',
            'aktif.boolean' => 'Status tidak valid.',
        ]);

        $dosen->update([
            'aktif' => (bool) $validated['aktif'],
        ]);

        return redirect()->back()->with('success', 'Status dosen berhasil diperbarui.')->setStatusCode(303);
    }

    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:dosen,id'],
        ]);

        Dosen::query()->whereIn('id', $validated['ids'])->delete();

        return redirect()->back()->with('success', 'Data dosen berhasil dihapus.')->setStatusCode(303);
    }
}
