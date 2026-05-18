<div class="flex flex-col items-center gap-6 py-10 text-center">
    <div class="flex h-20 w-20 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
        <flux:icon.shield-check class="h-10 w-10 text-zinc-400" />
    </div>

    <div class="max-w-sm space-y-2">
        <flux:heading size="xl">{{ __('livewire-sumsub::messages.verify_identity') }}</flux:heading>
        <flux:text class="text-zinc-500 dark:text-zinc-400">
            {{ __('livewire-sumsub::messages.verify_identity_description') }}
        </flux:text>
    </div>

    <ul class="space-y-2 text-left text-sm text-zinc-600 dark:text-zinc-400">
        <li class="flex items-center gap-2">
            <flux:icon.check-circle class="h-4 w-4 shrink-0 text-green-500" />
            {{ __('livewire-sumsub::messages.requirement_id') }}
        </li>
        <li class="flex items-center gap-2">
            <flux:icon.check-circle class="h-4 w-4 shrink-0 text-green-500" />
            {{ __('livewire-sumsub::messages.requirement_selfie') }}
        </li>
        <li class="flex items-center gap-2">
            <flux:icon.check-circle class="h-4 w-4 shrink-0 text-green-500" />
            {{ __('livewire-sumsub::messages.requirement_online') }}
        </li>
    </ul>

    <flux:button wire:click="startVerification" variant="primary" icon="arrow-right">
        {{ __('livewire-sumsub::messages.start_verification') }}
    </flux:button>
</div>
