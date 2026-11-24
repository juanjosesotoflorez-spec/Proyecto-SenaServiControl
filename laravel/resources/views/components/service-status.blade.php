@props(['status'])

@php($serviceStatus = $status instanceof \App\Enums\EstadoServicioEnum
    ? $status
    : \App\Enums\EstadoServicioEnum::tryFrom((string) $status))

<span {{ $attributes->class([
    'badge',
    'rounded-pill',
    $serviceStatus?->badgeClass() ?? 'bg-secondary',
    match ($serviceStatus) {
        \App\Enums\EstadoServicioEnum::Pendiente => 'status-pending',
        \App\Enums\EstadoServicioEnum::EnProceso => 'status-progress',
        \App\Enums\EstadoServicioEnum::Finalizado => 'status-finished',
        \App\Enums\EstadoServicioEnum::Entregado => 'status-delivered',
        default => '',
    },
]) }}>{{ $serviceStatus?->value ?? $status }}</span>