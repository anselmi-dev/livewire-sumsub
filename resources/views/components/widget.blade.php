@props([
    'sdkToken' => null,
])

<div
    {{ $attributes->class('w-full') }}
    x-data="sumsubWidget(@js($sdkToken))"
    x-init="init()"
    @sumsub:launch.window="launch($event.detail.token)"
    @sumsub:token-refreshed.window="resolveRefresh($event.detail.token)"
>
    {{ $slot }}
</div>
