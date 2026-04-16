<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use App\Models\Role;
use App\Models\Edition;
use App\Models\KategoriLomba;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Controllers\KategoriLombaController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\TimelineLombaController;
use App\Http\Controllers\GuidelineController;
use App\Http\Controllers\EdisiKonteksController;
use App\Http\Controllers\Peserta\KaryaController;
use App\Http\Controllers\Peserta\ArsipController;
use App\Http\Controllers\Peserta\DashboardController as PesertaDashboardController;
use App\Http\Controllers\Peserta\PameranController;
use App\Http\Controllers\Admin\PameranController as AdminPameranController;
use App\Http\Controllers\Admin\PemenangController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\PenjurianController;
use App\Http\Controllers\PenugasanJuriController;
use App\Http\Controllers\JuriDashboardController;
use App\Http\Controllers\JuriSubmissionController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LandingSettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\EdisiLombaController;

/*
|--------------------------------------------------------------------------
| LANDING & LOGIN
|--------------------------------------------------------------------------
*/

Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::get('/panduan', [LandingController::class, 'panduan'])->name('landing.panduan');
Route::get('/pameran', [LandingController::class, 'pameran'])->name('landing.pameran');
Route::get('/nominate', [LandingController::class, 'nominate'])->name('landing.nominate');
Route::get('/juara', [LandingController::class, 'juara'])->name('landing.juara');
Route::get('/juara/{id}', [LandingController::class, 'juaraDetail'])->name('landing.juara.detail');
Route::get('/juara/logo/{karya}', [LandingController::class, 'previewJuaraLogo'])->name('landing.juara.logo.preview');

Route::get('/login', function () {
    return Inertia::render('Auth/Login');
})->name('login');


/*
|--------------------------------------------------------------------------
| GOOGLE OAUTH (1 CALLBACK)
|--------------------------------------------------------------------------
*/

Route::get('/auth/google/peserta', function () {
    session(['google_auth_intent' => 'peserta']);
    return Socialite::driver('google')
        ->scopes(['openid', 'profile', 'email'])
        ->with([
            'hd' => 'students.amikom.ac.id',
            'prompt' => 'select_account',
        ])
        ->redirect();
});

Route::get('/auth/google/admin', function () {
    session(['google_auth_intent' => 'admin']);
    $driver = Socialite::driver('google')
        ->scopes(['openid', 'profile', 'email']);
    if (app()->environment('local')) {
        return $driver->with(['prompt' => 'select_account'])->redirect();
    }

    return $driver->with([
        'hd' => 'amikom.ac.id',
        'prompt' => 'select_account',
    ])->redirect();
});

Route::get('/auth/google/callback', function () {

    $googleUser = Socialite::driver('google')->stateless()->user();

    $email = $googleUser->getEmail();
    $intent = session('google_auth_intent', 'peserta');

    $avatar = $googleUser->getAvatar();

    // Optional: ubah ukuran jadi lebih besar
    $avatar = $avatar ? str_replace('=s96-c', '=s200-c', $avatar) : null;

    $existingUser = User::query()->where('email', $email)->first();
    if ($intent === 'admin') {
        $isAdminOrJuri = $existingUser?->roles()
            ->whereIn('name', ['admin', 'juri'])
            ->exists();

        if (!$isAdminOrJuri && !app()->environment('local')) {
            return redirect('/login')->with('error', 'Akun belum terdaftar sebagai admin atau juri.');
        }
    }
    $resolvedName = $googleUser->getName()
        ?: $existingUser?->name
        ?: Str::before($email, '@');
    $resolvedAvatar = $avatar
        ?: $existingUser?->avatar
        ?: ('https://ui-avatars.com/api/?name=' . urlencode($resolvedName) . '&background=2563eb&color=fff');

    $user = User::updateOrCreate(
        ['email' => $email],
        [
            'name' => $resolvedName,
            'google_id' => $googleUser->getId() ?: $existingUser?->google_id,
            'avatar' => $resolvedAvatar,
        ]
    );

    if ($intent === 'peserta' && !$user->roles()->exists()) {
        $role = Role::where('name', 'peserta')->first();
        if (!$role) {
            abort(500, 'Role peserta belum tersedia. Jalankan seeder roles.');
        }
        $user->roles()->attach($role->id);
    }

    if ($intent === 'admin' && app()->environment('local')) {
        $hasAdminOrJuri = $user->roles()->whereIn('name', ['admin', 'juri'])->exists();
        if (!$hasAdminOrJuri) {
            $adminRole = Role::where('name', 'admin')->first();
            if ($adminRole) {
                $user->roles()->attach($adminRole->id);
            }
        }
    }

    $tahunSekarang = (int) now()->format('Y');
    $edisiAktif = Edition::query()->where('status', 'aktif')->first()
        ?? Edition::query()->where('aktif', true)->first()
        ?? Edition::query()->where('tahun', $tahunSekarang)->first()
        ?? Edition::query()->orderByDesc('tahun')->first();

    if ($edisiAktif) {
        $roleIds = $user->roles()->pluck('roles.id');
        if ($roleIds->isNotEmpty()) {
            $payloadPivot = $roleIds->map(fn($roleId) => [
                'edisi_lomba_id' => $edisiAktif->id,
                'user_id' => $user->id,
                'role_id' => $roleId,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            DB::table('edisi_lomba_user_role')->upsert(
                $payloadPivot,
                ['edisi_lomba_id', 'user_id', 'role_id'],
                ['updated_at']
            );
        }

        session(['edisi_aktif_id' => $edisiAktif->id]);
    }

    Auth::login($user);
    return redirect('/redirect-role');
});

Route::post('/auth/form', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
    ]);

    $userByEmail = User::query()
        ->where('email', $credentials['email'])
        ->first();

    if (
        $userByEmail
        && $userByEmail->roles()->where('name', 'juri')->exists()
        && empty($userByEmail->password)
    ) {
        return back()->withErrors([
            'email' => 'Juri pertama kali harus login menggunakan Google terlebih dahulu, lalu buat password di menu Informasi Akun.',
        ])->setStatusCode(303);
    }

    if (!Auth::attempt($credentials, true)) {
        return back()->withErrors([
            'email' => 'Email atau password tidak sesuai.',
        ])->setStatusCode(303);
    }

    $request->session()->regenerate();

    $user = Auth::user();
    $hasAdminOrJuri = $user?->roles()->whereIn('name', ['admin', 'juri'])->exists();

    if (!$hasAdminOrJuri) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return back()->withErrors([
            'email' => 'Login form hanya untuk admin atau juri.',
        ])->setStatusCode(303);
    }

    $tahunSekarang = (int) now()->format('Y');
    $edisiAktif = Edition::query()->where('status', 'aktif')->first()
        ?? Edition::query()->where('aktif', true)->first()
        ?? Edition::query()->where('tahun', $tahunSekarang)->first()
        ?? Edition::query()->orderByDesc('tahun')->first();

    if ($edisiAktif) {
        session(['edisi_aktif_id' => $edisiAktif->id]);
    }

    return redirect('/redirect-role');
})->name('auth.form');

/*
|--------------------------------------------------------------------------
| REDIRECT BASED ON ROLE
|--------------------------------------------------------------------------
*/

Route::get('/redirect-role', function () {

    $user = auth()->user();

    if ($user->hasRole('admin')) {
        return redirect('/admin');
    }

    if ($user->hasRole('juri')) {
        if (empty($user->password)) {
            return redirect('/juri/akun?setup=1');
        }
        return redirect('/juri');
    }

    if ($user->hasRole('peserta')) {
        return redirect('/peserta');
    }

    abort(403);

})->middleware('auth');


/*
|--------------------------------------------------------------------------
| LOGOUT
|--------------------------------------------------------------------------
*/

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->middleware('auth');

Route::post('/konteks/edisi', [EdisiKonteksController::class, 'update'])
    ->middleware('auth')
    ->name('konteks.edisi');

/*
|--------------------------------------------------------------------------
| PESERTA
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:peserta'])
    ->prefix('peserta')
    ->group(function () {
        Route::get('/', [PesertaDashboardController::class, 'index'])
            ->name('peserta.dashboard');
        Route::get('/akun', [AccountController::class, 'show'])
            ->name('peserta.account');
        Route::get('/arsip', [ArsipController::class, 'index'])
            ->name('peserta.arsip');

        Route::get('/daftar-karya', [KaryaController::class, 'daftar'])
            ->name('peserta.daftar-karya');
        Route::get('/daftar-karya/form', [KaryaController::class, 'index'])
            ->name('peserta.daftar-karya.form');
        Route::post('/daftar-karya/tahap-1', [KaryaController::class, 'simpanTahapSatu'])
            ->name('peserta.daftar-karya.tahap1');
        Route::post('/daftar-karya/draft', [KaryaController::class, 'simpanDraftPerTahap'])
            ->name('peserta.daftar-karya.draft');
        Route::post('/daftar-karya', [KaryaController::class, 'store'])
            ->name('peserta.daftar-karya.store');
        Route::get('/daftar-karya/proposal/{karya}/preview', [KaryaController::class, 'previewProposal'])
            ->name('peserta.daftar-karya.proposal.preview');
        Route::get('/daftar-karya/lampiran/{lampiran}/preview', [KaryaController::class, 'previewLampiran'])
            ->name('peserta.daftar-karya.lampiran.preview');
        Route::delete('/daftar-karya/{karya}', [KaryaController::class, 'destroy'])
            ->name('peserta.daftar-karya.destroy');
        Route::get('/karya-terdaftar', function () {
            return redirect()->route('peserta.daftar-karya');
        })->name('peserta.karya-terdaftar');

        Route::get('/pameran-karya', [PameranController::class, 'index'])
            ->name('peserta.pameran');
        Route::patch('/pameran-karya/{karya}', [PameranController::class, 'update'])
            ->name('peserta.pameran.update');
        Route::get('/pameran-karya/{karya}/logo', [PameranController::class, 'previewLogo'])
            ->name('peserta.pameran.logo.preview');

        Route::get('/submission', function () {
            return redirect()->route('peserta.daftar-karya');
        })->name('peserta.submission');
    });

/*
|--------------------------------------------------------------------------
| ADMIN
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])
            ->name('admin.dashboard');
        Route::get('/akun', [AccountController::class, 'show'])
            ->name('admin.account');
        Route::patch('/akun/password', [AccountController::class, 'updatePassword'])
            ->name('admin.account.password');

        // FILTER VIEW
        Route::get('/peserta', [UserController::class, 'peserta'])
            ->name('admin.peserta');

        Route::get('/juri', [UserController::class, 'juri'])
            ->name('admin.juri');
        Route::get('/admin-juri', function () {
            return redirect()->route('admin.juri');
        })->name('admin.admin-juri');
        Route::get('/panitia', function () {
            return redirect()->route('admin.juri');
        })->name('admin.panitia');

        // EDISI LOMBA
        Route::get('/edisi-lomba', [EdisiLombaController::class, 'index'])
            ->name('admin.edisi.index');
        Route::post('/edisi-lomba', [EdisiLombaController::class, 'store'])
            ->name('admin.edisi.store');
        Route::put('/edisi-lomba/{edisi}', [EdisiLombaController::class, 'update'])
            ->name('admin.edisi.update');
        Route::post('/edisi-lomba/{edisi}/aktifkan', [EdisiLombaController::class, 'aktifkan'])
            ->name('admin.edisi.aktifkan');
        Route::post('/edisi-lomba/{edisi}/selesaikan', [EdisiLombaController::class, 'selesaikan'])
            ->name('admin.edisi.selesaikan');
        Route::delete('/edisi-lomba/bulk-delete', [EdisiLombaController::class, 'bulkDelete'])
            ->name('admin.edisi.bulkDelete');

        Route::get('/kategori', [KategoriLombaController::class, 'index'])
            ->name('admin.kategori.index');
        Route::post('/kategori', [KategoriLombaController::class, 'store'])
            ->name('admin.kategori.store');
        Route::put('/kategori/{kategori}', [KategoriLombaController::class, 'update'])
            ->name('admin.kategori.update');
        Route::patch('/kategori/{kategori}/toggle-aktif', [KategoriLombaController::class, 'toggleAktif'])
            ->name('admin.kategori.toggleAktif');
        Route::delete('/kategori/bulk-delete', [KategoriLombaController::class, 'bulkDelete'])
            ->name('admin.kategori.bulkDelete');
        Route::delete('/kategori/{kategori}', [KategoriLombaController::class, 'destroy'])
            ->name('admin.kategori.destroy');

        Route::get('/timeline', [TimelineLombaController::class, 'index'])
            ->name('admin.timeline.index');
        Route::post('/timeline', [TimelineLombaController::class, 'store'])
            ->name('admin.timeline.store');
        Route::put('/timeline/{timeline}', [TimelineLombaController::class, 'update'])
            ->name('admin.timeline.update');
        Route::patch('/timeline/{timeline}/toggle-aktif', [TimelineLombaController::class, 'toggleAktif'])
            ->name('admin.timeline.toggleAktif');
        Route::delete('/timeline/bulk-delete', [TimelineLombaController::class, 'bulkDelete'])
            ->name('admin.timeline.bulkDelete');
        Route::delete('/timeline/{timeline}', [TimelineLombaController::class, 'destroy'])
            ->name('admin.timeline.destroy');

        Route::get('/guideline', function () {
            return redirect()->route('admin.guideline.ketentuan');
        })->name('admin.guideline.index');
        Route::get('/guideline/ketentuan', [GuidelineController::class, 'ketentuan'])
            ->name('admin.guideline.ketentuan');
        Route::put('/guideline/ketentuan', [GuidelineController::class, 'updateKetentuan'])
            ->name('admin.guideline.ketentuan.update');
        Route::get('/guideline/bobot', [GuidelineController::class, 'bobot'])
            ->name('admin.guideline.bobot');
        Route::put('/guideline/bobot', [GuidelineController::class, 'updateBobot'])
            ->name('admin.guideline.bobot.update');
        Route::get('/guideline/template', [GuidelineController::class, 'template'])
            ->name('admin.guideline.template');
        Route::put('/guideline/template', [GuidelineController::class, 'updateTemplate'])
            ->name('admin.guideline.template.update');

        Route::get('/submission', [SubmissionController::class, 'index'])
            ->name('admin.submission.index');
        Route::get('/submission/karya', [SubmissionController::class, 'karya'])
            ->name('admin.submission.karya');
        Route::get('/submission/nominasi', [SubmissionController::class, 'nominasi'])
            ->name('admin.submission.nominasi');
        Route::delete('/submission/bulk-delete', [SubmissionController::class, 'bulkDestroy'])
            ->name('admin.submission.bulkDelete');
        Route::post('/submission/manual', [SubmissionController::class, 'storeManual'])
            ->name('admin.submission.manual');
        Route::patch('/submission/{karya}/lolos-nominasi', [SubmissionController::class, 'lolosNominasi'])
            ->name('admin.submission.lolosNominasi');
        Route::patch('/submission/{karya}/batalkan-nominasi', [SubmissionController::class, 'batalkanNominasi'])
            ->name('admin.submission.batalkanNominasi');
        Route::patch('/submission/{karya}/nilai-tahap-1', [SubmissionController::class, 'updateNilaiTahapSatu'])
            ->name('admin.submission.nilaiTahapSatu');
        Route::patch('/submission/{karya}/anggota-tim', [SubmissionController::class, 'updateAnggotaTim'])
            ->name('admin.submission.anggotaTim');
        Route::get('/submission/{karya}', [SubmissionController::class, 'show'])
            ->name('admin.submission.show');
        Route::delete('/submission/{karya}', [SubmissionController::class, 'destroy'])
            ->name('admin.submission.destroy');
        Route::get('/submission/lampiran/{lampiran}/preview', [SubmissionController::class, 'previewLampiran'])
            ->name('admin.submission.lampiran.preview');

        Route::get('/pameran-karya', [AdminPameranController::class, 'index'])
            ->name('admin.pameran.index');
        Route::patch('/pameran-karya/{karya}', [AdminPameranController::class, 'update'])
            ->name('admin.pameran.update');
        Route::get('/pameran-karya/{karya}/logo', [AdminPameranController::class, 'previewLogo'])
            ->name('admin.pameran.logo.preview');

        Route::get('/penjurian', function () {
            return redirect()->route('admin.penjurian.nominasi');
        })->name('admin.penjurian.index');
        Route::get('/penjurian/penugasan', [PenugasanJuriController::class, 'index'])
            ->name('admin.penjurian.penugasan');
        Route::post('/penjurian/penugasan', [PenugasanJuriController::class, 'update'])
            ->name('admin.penjurian.penugasan.update');
        Route::get('/penjurian/nominasi', [PenjurianController::class, 'nominasi'])
            ->name('admin.penjurian.nominasi');
        Route::get('/penjurian/detail/{karya}', [PenjurianController::class, 'detail'])
            ->name('admin.penjurian.detail');
        Route::get('/penjurian/nilai/{karya}', [PenjurianController::class, 'nilai'])
            ->name('admin.penjurian.nilai');
        Route::post('/penjurian/nilai/{karya}', [PenjurianController::class, 'simpanNilai'])
            ->name('admin.penjurian.nilai.simpan');
        Route::get('/penjurian/rekap', [PenjurianController::class, 'rekap'])
            ->name('admin.penjurian.rekap');
        Route::get('/penjurian/lampiran/{lampiran}/preview', [PenjurianController::class, 'previewLampiran'])
            ->name('admin.penjurian.lampiran.preview');

        Route::get('/pemenang', [PemenangController::class, 'index'])
            ->name('admin.pemenang.index');
        Route::post('/pemenang/tetapkan', [PemenangController::class, 'tetapkan'])
            ->name('admin.pemenang.tetapkan');

        Route::get('/landing', [LandingSettingController::class, 'index'])
            ->name('admin.landing.index');
        Route::put('/landing', [LandingSettingController::class, 'update'])
            ->name('admin.landing.update');

        // CRUD USER (SATU SUMBER)
        Route::post('/users', [UserController::class, 'store'])
            ->name('admin.users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])
            ->name('admin.users.update');
        Route::delete('/users/bulk-delete', [UserController::class, 'bulkDelete'])
            ->name('admin.users.bulkDelete');
    });

/*
|--------------------------------------------------------------------------
| JURI
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:juri'])
    ->prefix('juri')
    ->group(function () {
        Route::get('/', [JuriDashboardController::class, 'index'])
            ->name('juri.dashboard');
        Route::get('/akun', [AccountController::class, 'show'])
            ->name('juri.account');
        Route::patch('/akun/password', [AccountController::class, 'updatePassword'])
            ->name('juri.account.password');

        Route::get('/submission', [JuriSubmissionController::class, 'index'])
            ->name('juri.submission.index');
        Route::get('/submission/karya', [JuriSubmissionController::class, 'karya'])
            ->name('juri.submission.karya');
        Route::patch('/submission/{karya}/lolos-nominasi', [JuriSubmissionController::class, 'lolosNominasi'])
            ->name('juri.submission.lolosNominasi');
        Route::patch('/submission/{karya}/batalkan-nominasi', [JuriSubmissionController::class, 'batalkanNominasi'])
            ->name('juri.submission.batalkanNominasi');
        Route::patch('/submission/{karya}/nilai-tahap-1', [JuriSubmissionController::class, 'updateNilaiTahapSatu'])
            ->name('juri.submission.nilaiTahapSatu');
        Route::get('/submission/{karya}', [JuriSubmissionController::class, 'show'])
            ->name('juri.submission.show');
        Route::get('/submission/lampiran/{lampiran}/preview', [JuriSubmissionController::class, 'previewLampiran'])
            ->name('juri.submission.lampiran.preview');

        Route::get('/penjurian', function () {
            return redirect()->route('juri.penjurian.nominasi');
        })->name('juri.penjurian.index');
        Route::get('/penjurian/nominasi', [PenjurianController::class, 'nominasi'])
            ->name('juri.penjurian.nominasi');
        Route::get('/penjurian/detail/{karya}', [PenjurianController::class, 'detail'])
            ->name('juri.penjurian.detail');
        Route::get('/penjurian/nilai/{karya}', [PenjurianController::class, 'nilai'])
            ->name('juri.penjurian.nilai');
        Route::post('/penjurian/nilai/{karya}', [PenjurianController::class, 'simpanNilai'])
            ->name('juri.penjurian.nilai.simpan');
        Route::get('/penjurian/rekap', [PenjurianController::class, 'rekap'])
            ->name('juri.penjurian.rekap');
        Route::get('/penjurian/lampiran/{lampiran}/preview', [PenjurianController::class, 'previewLampiran'])
            ->name('juri.penjurian.lampiran.preview');
    });
