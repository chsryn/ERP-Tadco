<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $users = User::query()
            ->with('roles')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('users.index', compact('users', 'search'));
    }

    public function create()
    {
        $roles = Role::orderBy('name')->get();
        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed|min:6',
            'role' => 'required|exists:roles,name',
            'is_active' => 'required|boolean',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_active' => $validated['is_active'],
        ]);

        $user->assignRole($validated['role']);

        return redirect()
            ->route('users.index')
            ->with('success', 'User berhasil ditambahkan.');
    }

    public function show(User $user)
    {
        $user->load('roles');
        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $roles = Role::orderBy('name')->get();
        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        // Setup validasi dasar
        $rules = [
            'name' => 'required|max:100',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id),
            ],
            'role' => 'required|exists:roles,name',
            'is_active' => 'required|boolean',
        ];

        // Jika password diisi, maka validasi password dijalankan
        if ($request->filled('password')) {
            $rules['password'] = 'required|confirmed|min:6';
        }

        $validated = $request->validate($rules);

        // Data yang pasti diupdate
        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_active' => $validated['is_active'],
        ];

        // Jika password diisi, enkripsi dan masukkan ke data update
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        // Update Role
        $user->syncRoles([$validated['role']]);

        return redirect()
            ->route('users.index')
            ->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if (auth()->id() == $user->id) {
            return back()->withErrors([
                'user' => 'Anda tidak dapat menghapus akun sendiri.',
            ]);
        }
        User::destroy($user->id);

        return redirect()
            ->route('users.index')
            ->with('success', 'User berhasil dihapus.');
    }
}
