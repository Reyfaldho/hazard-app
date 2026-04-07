<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mail\VerifyEmailMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    // POST /api/register
    public function register(Request $request)
    {
        $request->validate([
            'nik'          => 'required|string|max:16|unique:users',
            'employee_id'  => 'nullable|string|max:20|unique:users',
            'full_name'    => 'required|string|max:100',
            'email'        => 'required|email|unique:users',
            'password'     => 'required|min:6',
            'phone_number' => 'nullable|string|max:20',
            'position'     => 'nullable|string|max:100',
            'department'   => 'nullable|string|max:100',
        ]);

        $verificationToken = Str::random(64);

        // Auto-generate employee_id jika tidak dikirim
        $employeeId = $request->employee_id
            ?? 'USR-' . strtoupper(Str::random(4)) . '-' . now()->format('ymd');

        $user = User::create([
            'nik'                       => $request->nik,
            'employee_id'               => $employeeId,
            'full_name'                 => $request->full_name,
            'email'                     => $request->email,
            'password_hash'             => Hash::make($request->password),
            'phone_number'              => $request->phone_number,
            'position'                  => $request->position,
            'department'                => $request->department,
            'role'                      => 'user',
            'email_verification_token'  => $verificationToken,
        ]);

        // Kirim link verifikasi ke email
        $verificationUrl = url("/api/email/verify/{$user->id}/{$verificationToken}");
        Mail::to($user->email)->send(new VerifyEmailMail($verificationUrl, $user->full_name));

        return response()->json([
            'status'  => 'success',
            'message' => 'Registrasi berhasil. Link verifikasi telah dikirim ke email Anda. Silakan cek inbox dan verifikasi sebelum login.',
            'data'    => ['email' => $user->email],
        ], 201);
    }

    // GET /api/email/verify/{id}/{token}
    // Dibuka melalui browser dari link email
    public function verifyEmail($id, $token)
    {
        $user = User::find($id);

        if (! $user || $user->email_verification_token !== $token) {
            return response()->view('auth.email-verify-result', [
                'success' => false,
                'message' => 'Link verifikasi tidak valid atau sudah digunakan.',
            ], 422);
        }

        if ($user->email_verified_at) {
            return response()->view('auth.email-verify-result', [
                'success' => true,
                'message' => 'Email Anda sudah diverifikasi sebelumnya. Silakan login di aplikasi.',
            ]);
        }

        $user->update([
            'email_verified_at'         => now(),
            'email_verification_token'  => null,
        ]);

        return response()->view('auth.email-verify-result', [
            'success' => true,
            'message' => 'Email berhasil diverifikasi! Silakan kembali ke aplikasi dan login.',
        ]);
    }

    // POST /api/email/resend
    // Body: { email }
    public function resendVerification(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Email tidak ditemukan.',
            ], 404);
        }

        if ($user->email_verified_at) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Email sudah terverifikasi. Silakan login.',
            ]);
        }

        $verificationToken = Str::random(64);
        $user->update(['email_verification_token' => $verificationToken]);

        $verificationUrl = url("/api/email/verify/{$user->id}/{$verificationToken}");
        Mail::to($user->email)->send(new VerifyEmailMail($verificationUrl, $user->full_name));

        return response()->json([
            'status'  => 'success',
            'message' => 'Link verifikasi baru telah dikirim ke email Anda.',
        ]);
    }

    // POST /api/login
    // Field 'login' bisa diisi NIK, employee_id, atau email
    public function login(Request $request)
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->login)
            ->orWhere('nik', $request->login)
            ->orWhere('employee_id', $request->login)
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password_hash)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kredensial tidak valid. Periksa kembali NIK/Employee ID/Email dan password Anda.',
            ], 401);
        }

        if (! $user->is_active) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akun Anda tidak aktif. Silakan hubungi administrator.',
            ], 403);
        }

        // Blokir login jika email belum diverifikasi
        if (! $user->email_verified_at) {
            return response()->json([
                'status'  => 'error',
                'code'    => 'email_not_verified',
                'message' => 'Email Anda belum diverifikasi. Silakan cek inbox email Anda dan klik link verifikasi.',
                'data'    => ['email' => $user->email],
            ], 403);
        }

        $user->tokens()->delete();
        $token = $user->createToken('mobile-token')->plainTextToken;

        return response()->json([
            'status'  => 'success',
            'message' => 'Login berhasil',
            'token'   => $token,
            'data'    => $this->formatUser($user),
        ]);
    }

    // GET /api/me
    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    // POST /api/logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Logout berhasil.',
        ]);
    }

    private function formatUser(User $user): array
    {
        return [
            'id'             => $user->id,
            'nik'            => $user->nik,
            'employee_id'    => $user->employee_id,
            'full_name'      => $user->full_name,
            'email'          => $user->email,
            'email_verified' => ! is_null($user->email_verified_at),
            'phone_number'   => $user->phone_number,
            'position'       => $user->position,
            'department'     => $user->department,
            'profile_photo'  => $user->profile_photo
                ? asset('storage/' . $user->profile_photo)
                : null,
            'role'           => $user->role,
            'is_active'      => $user->is_active,
        ];
    }
}