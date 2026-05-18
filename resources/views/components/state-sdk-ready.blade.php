@php
    $containerId = config('livewire-sumsub.sdk.container_id', 'sumsub-websdk-container');
@endphp

<div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
    <div id="{{ $containerId }}" class="min-h-[600px] w-full"></div>
</div>
