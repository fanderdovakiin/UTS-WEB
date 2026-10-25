@props(['value', 'prefix' => true])

@if ($prefix)Rp @endif{{ number_format((float) $value, 0, ',', '.') }}
