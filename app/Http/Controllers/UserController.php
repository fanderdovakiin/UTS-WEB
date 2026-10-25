<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->withCount(['sales', 'purchases'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = $request->string('q');
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->when($request->filled('role'), function ($query) use ($request) {
                $query->where('role', $request->input('role'));
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.form', ['user' => new User]);
    }

    public function store(UserRequest $request)
    {
        User::create($request->validated());

        return redirect()->route('users.index')
            ->with('success', 'Akun pengguna berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        return view('users.form', compact('user'));
    }

    public function update(UserRequest $request, User $user)
    {
        $data = $request->validated();

        // Turunkan role admin terakhir = aplikasi bisa kehilangan akses admin.
        if ($user->isAdmin()
            && ($data['role'] ?? $user->role) !== User::ROLE_ADMIN
            && $this->isLastAdmin($user)) {
            return back()
                ->withInput()
                ->with('error', 'Tidak dapat menurunkan role admin terakhir. Tambahkan admin lain terlebih dahulu.');
        }

        if (empty($data['password'])) {
            unset($data['password']); // Kosong = password tidak diganti.
        }

        $user->update($data);

        return redirect()->route('users.index')
            ->with('success', 'Akun pengguna berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun yang sedang digunakan.');
        }

        if ($this->isLastAdmin($user)) {
            return back()->with('error', 'Tidak dapat menghapus admin terakhir. Tambahkan admin lain terlebih dahulu.');
        }

        if ($user->sales()->exists() || $user->purchases()->exists()) {
            return back()->with('error', 'Akun "'.$user->name
                .'" tidak dapat dihapus karena sudah memiliki riwayat transaksi.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'Akun pengguna berhasil dihapus.');
    }

    private function isLastAdmin(User $user): bool
    {
        return $user->isAdmin()
            && User::where('role', User::ROLE_ADMIN)->whereKeyNot($user->id)->doesntExist();
    }
}
