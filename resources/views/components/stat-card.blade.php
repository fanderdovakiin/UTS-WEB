@props(['icon', 'title', 'value', 'badge' => null, 'badgeTipe' => 'biru'])

<div {{ $attributes->merge(['class' => 'stat-card']) }}>
    <span class="stat-card__icon"><x-icon :name="$icon" :size="20" /></span>
    <div class="stat-card__body">
        <span class="stat-card__title">{{ $title }}</span>
        <span class="stat-card__value {{ mb_strlen((string) $value) > 16 ? 'stat-card__value--xs' : (mb_strlen((string) $value) > 12 ? 'stat-card__value--sm' : '') }}">{{ $value }}</span>
    </div>
    @if ($badge !== null && $badge !== '')
        <span class="stat-card__badge stat-card__badge--{{ $badgeTipe }}">{{ $badge }}</span>
    @endif
</div>
