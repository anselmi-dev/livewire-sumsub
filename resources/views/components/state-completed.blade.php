<div class="flex flex-col items-center gap-6 py-10 text-center">
    <div class="flex h-20 w-20 items-center justify-center rounded-full bg-green-50 dark:bg-green-900/20">
        <flux:icon.check-badge class="h-10 w-10 text-green-500" />
    </div>
    <div class="max-w-sm space-y-2">
        <flux:heading size="xl">{{ __('livewire-sumsub::messages.identity_verified') }}</flux:heading>
        <flux:text class="text-zinc-500 dark:text-zinc-400">
            {{ __('livewire-sumsub::messages.identity_verified_description') }}
        </flux:text>
    </div>
    {{ $slot }}
</div>
