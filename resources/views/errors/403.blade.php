@extends('layouts.app')

@section('judul', 'Akses Ditolak')

@section('content')
    <div class="card">
        <div class="card__body" style="text-align:center; padding: 48px 24px;">
            <div style="font-size: 44px; font-weight: 800; color: var(--app-primary, #0a6bc4);">403</div>
            <h1 style="margin: 8px 0 4px; font-size: 18px;">Akses Ditolak</h1>
            <p style="color: #64748b; max-width: 460px; margin: 0 auto 20px;">
                {{ $exception->getMessage() ?: 'Anda tidak memiliki hak akses untuk membuka halaman ini.' }}
            </p>
            <div style="display:flex; gap:10px; justify-content:center;">
                <a href="{{ route('dashboard') }}" class="btn btn--utama">Kembali ke Dashboard</a>
                <a href="javascript:history.back()" class="btn btn--garis">Halaman Sebelumnya</a>
            </div>
        </div>
    </div>
@endsection
