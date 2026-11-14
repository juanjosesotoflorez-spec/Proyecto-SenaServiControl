@props(['status'])

@php($serviceStatus = $status instanceof \App\Enums\EstadoServicioEnum
    ? $status
    : \App\Enums\EstadoServicioEnum::tryFrom((string) $status))

<span {{ $attributes->class(['badge', 'rounded-pill', $serviceStatus?->badgeClass() ?? 'bg-secondary']) }}>{{ $serviceStatus?->value ?? $status }}</span>