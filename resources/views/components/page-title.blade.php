@props(['title', 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'pagetitle']) }}>
    <div>
        <h1 class="pagetitle__title">{{ $title }}</h1>
        @if ($subtitle)
            <p class="pagetitle__subtitle">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="pagetitle__action">{{ $actions }}</div>
    @endisset
</div>
