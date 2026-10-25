@props(['user', 'size' => 40])

@php
    $warna = [
        'admin' => '#1572e8',
        'manajer' => '#31ce36',
        'drafter' => '#6861ce',
    ][$user->role] ?? '#6c757d';
@endphp

<div {{ $attributes->merge(['class' => 'd-inline-flex align-items-center justify-content-center rounded-circle text-white fw-bold flex-shrink-0']) }}
    style="width: {{ $size }}px; height: {{ $size }}px; background: {{ $warna }}; font-size: {{ round($size * 0.4) }}px;">
    {{ $user->inisial() }}
</div>