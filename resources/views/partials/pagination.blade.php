@php
    $halaman = $paginator->currentPage();
    $akhir = $paginator->lastPage();
    $awal = $paginator->firstItem();
    $akhirTampil = $paginator->lastItem();
    $total = $paginator->total();

    // Jendela angka halaman agar tombol tetap ringkas pada data banyak.
    $mulai = max(1, $halaman - 2);
    $selesai = min($akhir, $halaman + 2);
    $daftarHalaman = range($mulai, $selesai);
@endphp

<div class="pagination">
    <p class="pagination__info">
        Menampilkan {{ $awal ?? 0 }}–{{ $akhirTampil ?? 0 }} dari {{ $total }} data
    </p>

    <nav class="pagination__row" aria-label="Navigasi halaman">
        <a class="pagination__nav {{ $halaman <= 1 ? 'is-disabled' : '' }}"
           href="{{ $paginator->previousPageUrl() }}" aria-label="Halaman sebelumnya">&lsaquo;</a>

        @if ($mulai > 1)
            <a class="pagination__page" href="{{ $paginator->url(1) }}">1</a>
            @if ($mulai > 2)
                <span class="pagination__ellipsis">&hellip;</span>
            @endif
        @endif

        @foreach ($daftarHalaman as $halaman2)
            @if ($halaman2 === $halaman)
                <span class="pagination__page is-active" aria-current="page">{{ $halaman2 }}</span>
            @else
                <a class="pagination__page" href="{{ $paginator->url($halaman2) }}">{{ $halaman2 }}</a>
            @endif
        @endforeach

        @if ($selesai < $akhir)
            @if ($selesai < $akhir - 1)
                <span class="pagination__ellipsis">&hellip;</span>
            @endif
            <a class="pagination__page" href="{{ $paginator->url($akhir) }}">{{ $akhir }}</a>
        @endif

        <a class="pagination__nav {{ $halaman >= $akhir ? 'is-disabled' : '' }}"
           href="{{ $paginator->nextPageUrl() }}" aria-label="Halaman berikutnya">&rsaquo;</a>
    </nav>
</div>
