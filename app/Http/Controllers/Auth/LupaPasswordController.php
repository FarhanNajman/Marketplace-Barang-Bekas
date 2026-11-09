<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OtpLupaPassword;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class LupaPasswordController extends Controller
{
    /**
     * Masa berlaku OTP dalam menit.
     */
    public const OTP_EXPIRY_MINUTES = 10;

    /**
     * Tampilkan halaman pilihan metode pemulihan password (No HP atau Email).
     */
    public function showRequestForm(): View
    {
        return view('auth.lupa-password');
    }

    /**
     * Kirim OTP sekali pakai yang memiliki masa kedaluwarsa ke nomor HP terverifikasi.
     * Jika email belum ditambahkan atau belum diverifikasi, pengguna tetap dapat
     * memulihkan akun melalui nomor HP terverifikasi.
     */
    public function sendPhoneOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'no_hp' => ['required', 'string', 'max:20'],
        ], [
            'no_hp.required' => 'Nomor HP wajib diisi.',
        ]);

        $noHp = trim($validated['no_hp']);

        $user = User::where('no_hp', $noHp)->first();

        if (! $user || ! $user->hasVerifiedPhone()) {
            return back()
                ->withInput($request->only('no_hp'))
                ->withErrors([
                    'no_hp' => 'Nomor HP tidak terdaftar atau belum terverifikasi.',
                ]);
        }

        // Batalkan OTP aktif sebelumnya untuk pengguna ini agar hanya OTP terbaru yang berlaku
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
            ->route('password.phone.verify.form', ['no_hp' => $user->no_hp])
            ->with('status', 'Kode OTP pemulihan password telah dikirim ke nomor HP terverifikasi Anda.');

        // Mode OTP demo hanya aktif saat APP_ENV=local; jangan tampilkan OTP di production.
        if (App::environment('local')) {
            $redirect->with('demo_otp', $otpCode);
        }

        return $redirect;
    }

    /**
     * Tampilkan form verifikasi OTP dan input password baru untuk pemulihan via Nomor HP.
     */
    public function showVerifyOtpForm(Request $request): View
    {
        return view('auth.reset-password-otp', [
            'no_hp' => $request->query('no_hp', old('no_hp', '')),
        ]);
    }

    /**
     * Verifikasi OTP sekali pakai (dan belum kedaluwarsa) lalu perbarui password pengguna.
     */
    public function resetWithPhoneOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'no_hp' => ['required', 'string', 'max:20'],
            'otp' => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'confirmed', PasswordRule::min(8)],
        ], [
            'no_hp.required' => 'Nomor HP wajib diisi.',
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.size' => 'Kode OTP harus terdiri dari 6 digit.',
            'password.required' => 'Password baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal harus 8 karakter.',
        ]);

        $noHp = trim($validated['no_hp']);

        $user = User::where('no_hp', $noHp)->first();

        if (! $user || ! $user->hasVerifiedPhone()) {
            return back()
                ->withInput($request->only('no_hp'))
                ->withErrors([
                    'no_hp' => 'Nomor HP tidak terdaftar atau belum terverifikasi.',
                ]);
        }

        $otpRecord = OtpLupaPassword::where('user_id', $user->id)
            ->where('no_hp', $user->no_hp)
            ->latest('id')
            ->first();

        if (! $otpRecord || $otpRecord->isUsed()) {
            return back()
                ->withInput($request->only('no_hp'))
                ->withErrors([
                    'otp' => 'Kode OTP tidak valid atau sudah pernah digunakan.',
                ]);
        }

        if ($otpRecord->isExpired()) {
            return back()
                ->withInput($request->only('no_hp'))
                ->withErrors([
                    'otp' => 'Kode OTP sudah kedaluwarsa. Silakan minta kode OTP baru.',
                ]);
        }

        if (! Hash::check($validated['otp'], $otpRecord->kode_otp_hash)) {
            return back()
                ->withInput($request->only('no_hp'))
                ->withErrors([
                    'otp' => 'Kode OTP yang Anda masukkan salah.',
                ]);
        }

        // Tandai OTP telah digunakan (berlaku sekali pakai)
        $otpRecord->update([
            'used_at' => now(),
        ]);

        // Perbarui password pengguna
        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'remember_token' => Str::random(60),
        ])->save();

        return redirect()
            ->route('login')
            ->with('status', 'Password berhasil diatur ulang melalui nomor HP. Silakan masuk dengan password baru Anda.');
    }

    /**
     * Kirim tautan reset password ke email terverifikasi.
     * Jika email belum ditambahkan atau belum diverifikasi, arahkan/informasikan
     * bahwa pemulihan dapat dilakukan melalui nomor HP terverifikasi.
     */
    public function sendEmailResetLink(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
        ]);

        $email = trim($validated['email']);
        $user = User::where('email', $email)->first();

        if (! $user || ! $user->hasVerifiedEmail()) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'Email tidak terdaftar atau belum terverifikasi. Jika Anda belum menambahkan atau memverifikasi email, silakan pulihkan akun melalui Nomor HP terverifikasi.',
                ]);
        }

        $status = Password::broker()->sendResetLink(['email' => $user->email]);

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', 'Tautan reset password telah dikirim ke alamat email terverifikasi Anda.');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors([
                'email' => 'Gagal mengirim tautan reset password. Silakan coba beberapa saat lagi.',
            ]);
    }

    /**
     * Tampilkan form reset password melalui tautan email.
     */
    public function showResetEmailForm(Request $request, string $token): View
    {
        return view('auth.reset-password-email', [
            'token' => $token,
            'email' => $request->query('email', old('email', '')),
        ]);
    }

    /**
     * Proses reset password melalui tautan email terverifikasi.
     */
    public function resetWithEmailToken(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'confirmed', PasswordRule::min(8)],
        ], [
            'token.required' => 'Token reset password tidak valid.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'password.required' => 'Password baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal harus 8 karakter.',
        ]);

        $user = User::where('email', $request->input('email'))->first();

        if (! $user || ! $user->hasVerifiedEmail()) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'Email tidak terdaftar atau belum terverifikasi.',
                ]);
        }

        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()
                ->route('login')
                ->with('status', 'Password berhasil diatur ulang melalui tautan email. Silakan masuk dengan password baru Anda.');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors([
                'email' => 'Token reset password tidak valid atau sudah kedaluwarsa.',
            ]);
    }
}

