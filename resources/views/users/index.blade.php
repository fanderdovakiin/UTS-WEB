@extends('layouts.app')

@section('judul', 'Pengguna')

@section('content')
    <x-page-title title="Manajemen Pengguna &amp; Role"
                  subtitle="Hanya Admin/Owner yang dapat mengelola akun. Role menentukan hak akses di server.">
        <x-slot:actions>
            <a href="{{ route('users.create') }}" class="btn btn--utama">
                <x-icon name="plus" /> Tambah Akun
            </a>
        </x-slot:actions>
    </x-page-title>

    <div class="tablelist">
        <form method="GET" action="{{ route('users.index') }}" class="tablelist__toolbar">
            <div class="search-box">
                <x-icon name="cari" />
                <input type="search" name="q" value="{{ request('q') }}" class="input"
                       placeholder="Cari nama atau email…" aria-label="Cari pengguna">
            </div>

            <div class="tablelist__filters">
                <select name="role" class="select" aria-label="Saring role">
                    <option value="">Semua role</option>
                    <option value="admin" @selected(request('role') === 'admin')>Admin</option>
                    <option value="staff" @selected(request('role') === 'staff')>Staff</option>
                </select>
                <button type="submit" class="btn btn--netral">Terapkan</button>
                @if (request()->hasAny(['q', 'role']))
                    <a href="{{ route('users.index') }}" class="btn btn--garis">Atur ulang</a>
                @endif
            </div>
        </form>

        <div class="tablelist__body">
            @if ($users->isEmpty())
                <div class="empty-state">
                    <span class="empty-state__icon"><x-icon name="pengguna" :size="20" /></span>
                    <p class="empty-state__title">Pengguna tidak ditemukan</p>
                    <p class="empty-state__text">Tidak ada akun yang cocok dengan pencarian/filter saat ini.</p>
                </div>
            @else
                <div class="tablelist__scroll">
                    <table class="tablelist__table">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th style="width:240px">Email</th>
                                <th style="width:110px">Role</th>
                                <th style="width:130px">Transaksi</th>
                                <th class="tengah" style="width:170px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $pengguna)
                                <tr>
                                    <td class="tablelist__nomor">
                                        {{ $pengguna->name }}
                                        @if ($pengguna->id === auth()->id())
                                            <span class="badge badge--biru">Anda</span>
                                        @endif
                                    </td>
                                    <td style="color:var(--app-text-muted)">{{ $pengguna->email }}</td>
                                    <td>
                                        @if ($pengguna->isAdmin())
                                            <span class="badge badge--hijau">Admin</span>
                                        @else
                                            <span class="badge badge--abu">Staff</span>
                                        @endif
                                    </td>
                                    <td class="kanan" style="text-align:right">
                                        {{ $pengguna->sales_count + $pengguna->purchases_count }}
                                    </td>
                                    <td class="tengah">
                                        <div class="tablelist__aksi">
                                            <a href="{{ route('users.edit', $pengguna) }}" class="btn btn--netral btn--kecil">
                                                <x-icon name="ubah" :size="14" /> Ubah
                                            </a>
                                            @if ($pengguna->id !== auth()->id())
                                                <form method="POST" action="{{ route('users.destroy', $pengguna) }}"
                                                      data-konfirmasi="Hapus akun &quot;{{ $pengguna->name }}&quot;? Akun dengan riwayat transaksi tidak dapat dihapus.">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn--tipis btn--kecil">
                                                        <x-icon name="hapus" :size="14" /> Hapus
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        @if ($users->hasPages())
            <div class="tablelist__footer">
                {{ $users->links() }}
            </div>
        @endif
    </div>
@endsection
