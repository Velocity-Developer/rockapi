<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules;

class UsersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // get user
        $per_page = $request->input('per_page', 20);

        $query = User::query();

        $keyword = $request->input('keyword');
        if ($keyword) {
            $query->where('name', 'LIKE', '%'.$keyword.'%')
                ->orWhere('username', 'LIKE', '%'.$keyword.'%')
                ->orWhere('email', 'LIKE', '%'.$keyword.'%');
        }

        // filter by role
        $role = $request->input('role');
        if ($role) {
            $query->whereHas('roles', function ($query) use ($role) {
                $query->where('name', $role);
            });
        }

        $users = $query->paginate($per_page);
        $users->withPath('/users');

        return response()->json($users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|min:3',
            'username' => 'required|min:3',
            'email' => 'required|email',
            'hp' => 'nullable',
            'alamat' => 'nullable|string',
            'tgl_masuk' => 'nullable|string',
            'status' => 'required',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|min:2',
        ]);

        // buat user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'status' => $request->status,
            'username' => $request->username,
            'hp' => $request->hp,
            'alamat' => $request->alamat,
            'tgl_masuk' => $request->tgl_masuk,
            'password' => bcrypt($request->password),
        ]);
        $user->assignRole($request->role);

        return response()->json($user);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $user = User::find($id);

        return response()->json($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
        $request->validate([
            'name' => 'required|min:3',
            'username' => 'nullable|min:3',
            'email' => 'required|email',
            'hp' => 'nullable',
            'alamat' => 'nullable|string',
            'tgl_masuk' => 'nullable|string',
            'status' => 'required',
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|min:2',
        ]);

        $user = User::find($id);
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'status' => $request->status,
            'username' => $request->username ?? $user->username,
            'hp' => $request->hp,
            'alamat' => $request->alamat,
            'tgl_masuk' => $request->tgl_masuk,
        ]);

        // jika ada password baru
        if ($request->password) {
            $user->update([
                'password' => bcrypt($request->password),
            ]);
        }

        // hapus role lama
        if ($user->user_roles) {
            foreach ($user->user_roles as $role) {
                $user->removeRole($role);
            }
        }

        $user->assignRole($request->role);

        return response()->json($user);
    }

    /**
     * Update password user.
     */
    public function updatePassword(Request $request, string $id)
    {
        //
        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::find($id);

        $user->update([
            'password' => bcrypt($request->password),
        ]);

        return response()->json($user);
    }

    /**
     * Update avatar user.
     */
    public function updateAvatar(Request $request, string $id)
    {
        //
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp,svg,gif|max:1048',
        ]);

        $user = User::find($id);

        if ($request->hasFile('image')) {

            // hapus avatar lama
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $file = $request->file('image');
            $path = $file->store('avatar', 'public');
            $user->update([
                'avatar' => $path,
            ]);

            return response()->json($user);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // hapus user
        $user = User::find($id);
        $user->delete();
    }

    /**
     * Search users by keyword
     */
    public function search(string $keyword)
    {
        // Validasi keyword minimal 3 karakter
        if (strlen($keyword) < 3) {
            return response()->json([
                'message' => 'Keyword pencarian harus minimal 3 karakter',
            ], 400);
        }

        try {
            $users = User::where('name', 'LIKE', '%'.$keyword.'%')
                ->orWhere('username', 'LIKE', '%'.$keyword.'%')
                ->orWhere('email', 'LIKE', '%'.$keyword.'%')
                ->select('id', 'name', 'username', 'email', 'status', 'hp', 'alamat')
                ->limit(20)
                ->get();

            if ($users->isEmpty()) {
                return response()->json([
                    'message' => 'Tidak ada user yang ditemukan dengan keyword: '.$keyword,
                ], 404);
            }

            return response()->json($users);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan saat mencari user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
