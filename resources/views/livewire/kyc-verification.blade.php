{{--
    Sumsub KYC verification widget (Livewire).

    Publish views:
      php artisan vendor:publish --tag=livewire-sumsub-views

    Customise Blade components:
      resources/views/vendor/livewire-sumsub/components/
--}}
<x-livewire-sumsub::widget :sdk-token="$sdkToken">
    @if ($state === \AnselmiDev\Sumsub\DataTypes\KycVerificationState::Idle->value)
        <x-livewire-sumsub::state-idle />
    @endif

    @if ($state === \AnselmiDev\Sumsub\DataTypes\KycVerificationState::Loading->value)
        <x-livewire-sumsub::state-loading />
    @endif

    @if ($state === \AnselmiDev\Sumsub\DataTypes\KycVerificationState::SdkReady->value)
        <x-livewire-sumsub::state-sdk-ready />
    @endif

    @if ($state === \AnselmiDev\Sumsub\DataTypes\KycVerificationState::Progress->value)
        <x-livewire-sumsub::state-progress />
    @endif

    @if ($state === \AnselmiDev\Sumsub\DataTypes\KycVerificationState::Completed->value)
        <x-livewire-sumsub::state-completed />
    @endif

    @if ($state === \AnselmiDev\Sumsub\DataTypes\KycVerificationState::Cancelled->value)
        <x-livewire-sumsub::state-cancelled />
    @endif

    @if ($state === \AnselmiDev\Sumsub\DataTypes\KycVerificationState::Error->value)
        <x-livewire-sumsub::state-error :message="$errorMessage" />
    @endif
</x-livewire-sumsub::widget>

<x-livewire-sumsub::scripts />
