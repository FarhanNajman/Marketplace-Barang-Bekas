<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OtpLupaPassword;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Masa berlaku OTP registrasi dalam menit.
     */
    public const OTP_EXPIRY_MINUTES = 10;

    /**
     * Tampilkan halaman registrasi akun baru.
     */
    public function showRegisterForm(): View
    {
        return view('auth.register');
    }

    /**
     * Proses registrasi pengguna baru.
     * Email bersifat opsional; Nomor HP wajib diisi dan harus diverifikasi dengan kode OTP.
     */
    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
            'no_hp' => ['required', 'string', 'max:20', 'unique:users,no_hp'],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', PasswordRule::min(8)],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.alpha_dash' => 'Username hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
            'username.unique' => 'Username sudah digunakan.',
            'no_hp.required' => 'Nomor HP wajib diisi.',
            'no_hp.unique' => 'Nomor HP sudah terdaftar.',
            'email.email' => 'Format alamat email tidak valid.',
            'email.unique' => 'Alamat email sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal harus 8 karakter.',
        ]);

        $email = ! empty($validated['email']) ? trim($validated['email']) : null;

        $user = User::create([
            'name' => trim($validated['name']),
            'username' => trim($validated['username']),
            'no_hp' => trim($validated['no_hp']),
            'email' => $email,
            'no_hp_verified_at' => null,
            'email_verified_at' => $email ? now() : null,
            'password' => Hash::make($validated['password']),
        ]);

        return $this->issuePhoneVerificationOtp(
            $user,
            'Akun berhasil dibuat! Silakan masukkan kode OTP yang dikirim ke nomor HP Anda untuk mengaktifkan akun.'
        );
    }

    /**
     * Tampilkan halaman verifikasi OTP Nomor HP saat registrasi.
     */
    public function showVerifyPhoneForm(Request $request): View
    {
        return view('auth.verifikasi-otp-register', [
            'no_hp' => $request->query('no_hp', old('no_hp', '')),
        ]);
    }

    /**
     * Verifikasi kode OTP Nomor HP untuk mengaktifkan akun dan masuk.
     */
    public function verifyPhoneOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'no_hp' => ['required', 'string', 'max:20'],
            'otp' => ['required', 'string', 'size:6'],
        ], [
            'no_hp.required' => 'Nomor HP wajib diisi.',
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.size' => 'Kode OTP harus terdiri dari 6 digit.',
        ]);

        $noHp = trim($validated['no_hp']);
        $user = User::where('no_hp', $noHp)->first();

        if (! $user) {
            return back()
                ->withInput($request->only('no_hp'))
                ->withErrors(['no_hp' => 'Nomor HP tidak ditemukan.']);
        }

        if ($user->hasVerifiedPhone()) {
            Auth::login($user);
            $request->session()->regenerate();

            return redirect()
                ->route('beranda')
                ->with('status', 'Nomor HP Anda sudah terverifikasi. Selamat datang, ' . $user->name . '!');
        }

        $otpRecord = OtpLupaPassword::where('user_id', $user->id)
            ->where('no_hp', $user->no_hp)
            ->latest('id')
            ->first();

        if (! $otpRecord || $otpRecord->isUsed()) {
            return back()
                ->withInput($request->only('no_hp'))
                ->withErrors(['otp' => 'Kode OTP tidak valid atau sudah pernah digunakan.']);
        }

        if ($otpRecord->isExpired()) {
            return back()
                ->withInput($request->only('no_hp'))
                ->withErrors(['otp' => 'Kode OTP sudah kedaluwarsa. Silakan klik tombol Kirim Ulang OTP.']);
        }

        if (! Hash::check($validated['otp'], $otpRecord->kode_otp_hash)) {
            return back()
                ->withInput($request->only('no_hp'))
                ->withErrors(['otp' => 'Kode OTP yang Anda masukkan salah.']);
        }

        // Tandai OTP sudah digunakan & verifikasi nomor HP pengguna
        $otpRecord->update(['used_at' => now()]);
        $user->forceFill(['no_hp_verified_at' => now()])->save();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('beranda')
            ->with('status', 'Nomor HP berhasil diverifikasi! Selamat datang, ' . $user->name . '.');
    }

    /**
     * Kirim ulang kode OTP verifikasi Nomor HP.
     */
    public function resendPhoneOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'no_hp' => ['required', 'string', 'max:20'],
        ], [
            'no_hp.required' => 'Nomor HP wajib diisi.',
        ]);

        $noHp = trim($validated['no_hp']);
        $user = User::where('no_hp', $noHp)->first();

        if (! $user) {
            return back()
                ->withInput($request->only('no_hp'))
                ->withErrors(['no_hp' => 'Nomor HP tidak ditemukan.']);
        }

        if ($user->hasVerifiedPhone()) {
            return redirect()
                ->route('login')
                ->with('status', 'Nomor HP tersebut sudah terverifikasi. Silakan langsung masuk.');
        }

        return $this->issuePhoneVerificationOtp(
            $user,
            'Kode OTP baru telah dikirim ke nomor HP Anda.'
        );
    }

    /**
     * Tampilkan halaman login.
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Proses login pengguna menggunakan Username, Nomor HP, atau Email.
     */
    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'identitas' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'identitas.required' => 'Username, Nomor HP, atau Email wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $identitas = trim($validated['identitas']);
        $remember = $request->boolean('remember');

        $user = User::where('username', $identitas)
            ->orWhere('no_hp', $identitas)
            ->orWhere(function ($query) use ($identitas) {
                $query->whereNotNull('email')->where('email', $identitas);
            })
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return back()
                ->withInput($request->only('identitas', 'remember'))
                ->withErrors([
                    'identitas' => 'Kredensial yang Anda masukkan tidak cocok dengan data kami.',
                ]);
        }

        // Jika nomor HP belum diverifikasi, kirim OTP dan arahkan ke halaman verifikasi
        if (! $user->hasVerifiedPhone()) {
            return $this->issuePhoneVerificationOtp(
                $user,
                'Nomor HP Anda belum diverifikasi. Silakan masukkan kode OTP berikut untuk mengaktifkan akun.'
            );
        }

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()
            ->intended(route('beranda'))
            ->with('status', 'Berhasil masuk sebagai ' . $user->name . '.');
    }

    /**
     * Proses logout pengguna.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('status', 'Anda telah keluar dari akun.');
    }

    /**
     * Buat OTP 6 digit baru (sekali pakai, kedaluwarsa 10 menit) dan arahkan ke halaman verifikasi.
     * Mode OTP Demo hanya aktif saat APP_ENV=local.
     */
    protected function issuePhoneVerificationOtp(User $user, string $statusMessage): RedirectResponse
    {
        OtpLupaPassword::where('user_id', $user->id)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $otpCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OtpLupaPassword::create([
            'user_id' => $user->id,
            'no_hp' => $user->no_hp,
            'kode_otp_hash' => Hash::make($otpCode),
            'expires_at' => now()->addMinutes(self::OTP_EXPIRY_MINUTES),
        ]);

        $redirect = redirect()
            ->route('register.verify.form', ['no_hp' => $user->no_hp])
            ->with('status', $statusMessage);

        if (App::environment('local')) {
            $redirect->with('demo_otp', $otpCode);
        }

        return $redirect;
    }
}
