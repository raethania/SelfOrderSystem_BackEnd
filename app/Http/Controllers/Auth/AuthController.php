<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;

/**
 * Class AuthController
 *
 * Mengelola proses autentikasi pengguna meliputi registrasi,
 * login, dan logout menggunakan Laravel Sanctum token-based auth.
 *
 * @package App\Http\Controllers\Auth
 */
class AuthController extends Controller
{
    /**
     * Registrasi pengguna baru.
     *
     * Membuat akun user baru dengan role default 'customer',
     * lalu mengembalikan data user beserta access token.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @bodyParam  name      string  required  Nama lengkap pengguna. Max: 100 karakter.
     * @bodyParam  email     string  required  Alamat email unik. Max: 150 karakter.
     * @bodyParam  password  string  required  Password minimal 8 karakter, harus dikonfirmasi (password_confirmation).
     *
     * @return \Illuminate\Http\JsonResponse  201 — Data user dan token berhasil dibuat.
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|string|email|max:150|unique:users',
            'password' => 'required|string|min:8|max:255|confirmed',
        ]);

        // Create new customer user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'customer',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse('User registered successfully', [
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * Login pengguna.
     *
     * Memverifikasi kredensial email dan password,
     * lalu mengembalikan data user beserta access token jika valid.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @bodyParam  email     string  required  Alamat email terdaftar.
     * @bodyParam  password  string  required  Password akun.
     *
     * @return \Illuminate\Http\JsonResponse  200 — Login berhasil, data user dan token.
     *                                        422 — Email atau password salah.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        // Verify credentials
        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->errorResponse('Validasi gagal.', ['email' => ['Email atau password salah.']], 422);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse('Login successful', [
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Logout pengguna.
     *
     * Menghapus access token yang sedang digunakan sehingga
     * token tersebut tidak dapat dipakai lagi untuk autentikasi.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\JsonResponse  200 — Logout berhasil.
     *
     * @authenticated
     */
    public function logout(Request $request)
    {
        // Revoke current token
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse('Logged out successfully');
    }
}
