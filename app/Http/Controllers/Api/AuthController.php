<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Role;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
        ]);

        $token = null;

        DB::transaction(function () use ($validated, &$token) {

            // Ambil role user (pastikan sudah ada di DB)
            $role = Role::where('name', 'user')->first();

            if (!$role) {
                abort(500, 'Role user tidak ditemukan');
            }

            $user = User::create([
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role_id' => $role->id,
                'total_points' => 0
            ]);

            UserProfile::create([
                'user_id' => $user->id,
                'name' => $validated['name'],
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;
        });

        return response()->json([
            'message' => 'Register berhasil',
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Email atau password salah'
            ], 401);
        }

        $user = User::where('email', $credentials['email'])->first();

        return response()->json([
            'message' => 'Login berhasil',
            'token' => $user->createToken('auth_token')->plainTextToken
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'name' => 'sometimes|string|max:150',
            'phone' => 'sometimes|string|max:20',
            'nik' => 'sometimes|string|max:20',
            'date_of_birth' => 'sometimes|date',
            'gender' => 'sometimes|in:male,female',
            'latitude' => 'sometimes|numeric|between:-90,90',
            'longitude' => 'sometimes|numeric|between:-180,180',
            'password' => 'sometimes|min:8|confirmed',
            'photo_profile' => 'sometimes|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        DB::transaction(function () use ($validated, $user, $request) {

            if (isset($validated['email'])) {
                $user->update([
                    'email' => $validated['email']
                ]);
            }

            if (isset($validated['password'])) {
                $user->update([
                    'password' => Hash::make($validated['password'])
                ]);
            }

            $profileData = collect($validated)->only([
                'name',
                'phone',
                'nik',
                'date_of_birth',
                'gender',
                'latitude',
                'longitude',
            ])->toArray();

            if ($request->hasFile('photo_profile')) {

                if ($user->profile && $user->profile->photo_profile) {
                    Storage::disk('public')->delete($user->profile->photo_profile);
                }

                $path = $request->file('photo_profile')
                    ->store('profile_photos', 'public');

                $profileData['photo_profile'] = $path;
            }

            // SELALU jalankan ini
            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                $profileData
            );
        });

        return response()->json([
            'message' => 'Profile berhasil diperbarui',
            'user' => $user->load(['profile', 'role'])
        ]);
    }


    public function me(Request $request)
    {
        $user = $request->user()->load(['profile', 'role']);

        return response()->json([
            'id' => $user->id,
            'email' => $user->email,
            'role' => $user->role?->name,
            'total_points' => $user->total_points,
            'profile' => $user->profile,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil'
        ]);
    }

    // ///UpdateRoleToInfluencer
    // public function applyInfluencer(Request $request)
    // {
    //     $user = $request->user();

    //     if ($user->influencerApplication()
    //         ->where('status', 'pending')
    //         ->exists()
    //     ) {
    //         return response()->json([
    //             'message' => 'Masih ada pengajuan yang diproses'
    //         ], 400);
    //     }

    //     $validated = $request->validate([
    //         'nik' => 'required|string|max:20',
    //         'screenshot' => 'required|image|max:2048',
    //         'answers' => 'required|array'
    //     ]);

    //     DB::transaction(function () use ($validated, $user, $request) {

    //         $path = $request->file('screenshot')
    //             ->store('influencer', 'public');

    //         $application = RoleUpgradeRequests::create([
    //             'user_id' => $user->id,
    //             'nik' => $validated['nik'],
    //             'screenshot_path' => $path,
    //         ]);

    //         foreach ($validated['answers'] as $questionId => $answer) {
    //             InfluencerAnswer::create([
    //                 'application_id' => $application->id,
    //                 'question_id' => $questionId,
    //                 'answer' => $answer,
    //             ]);
    //         }
    //     });

    //     return response()->json([
    //         'message' => 'Pengajuan berhasil dikirim'
    //     ]);
    // }
}
