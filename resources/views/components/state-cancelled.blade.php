<div class="flex flex-col items-center gap-6 py-10 text-center">
    <div class="flex h-20 w-20 items-center justify-center rounded-full bg-red-50 dark:bg-red-900/20">
        <flux:icon.x-circle class="h-10 w-10 text-red-500" />
    </div>
    <div class="max-w-sm space-y-2">
        <flux:heading size="xl">{{ __('livewire-sumsub::messages.verification_rejected') }}</flux:heading>
        <flux:text class="text-zinc-500 dark:text-zinc-400">
            {{ __('livewire-sumsub::messages.verification_rejected_description') }}
        </flux:text>
    </div>
    <flux:button wire:click="startVerification" variant="primary" icon="arrow-path">
        {{ __('livewire-sumsub::messages.try_again') }}
    </flux:button>
</div>
