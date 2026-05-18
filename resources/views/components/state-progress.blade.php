<div class="flex flex-col items-center gap-6 py-10 text-center">
    <div class="flex h-20 w-20 items-center justify-center rounded-full bg-yellow-50 dark:bg-yellow-900/20">
        <flux:icon.clock class="h-10 w-10 text-yellow-500" />
    </div>
    <div class="max-w-sm space-y-2">
        <flux:heading size="xl">{{ __('livewire-sumsub::messages.verification_in_progress') }}</flux:heading>
        <flux:text class="text-zinc-500 dark:text-zinc-400">
            {{ __('livewire-sumsub::messages.verification_in_progress_description') }}
        </flux:text>
    </div>
</div>
