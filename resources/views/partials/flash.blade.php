@if (session('success'))
    <div class="alert alert--sukses" role="status">
        <x-icon name="centang" />
        <span>{{ session('success') }}</span>
    </div>
@endif

@if (session('error'))
    <div class="alert alert--galat" role="alert">
        <x-icon name="peringatan" />
        <span>{{ session('error') }}</span>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert--galat" role="alert">
        <x-icon name="peringatan" />
        <div>
            <strong>Ada data yang perlu diperbaiki:</strong>
            <ul style="margin: 6px 0 0; padding-left: 18px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
