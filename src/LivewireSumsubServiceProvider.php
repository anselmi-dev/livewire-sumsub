<?php

declare(strict_types=1);

namespace AnselmiDev\LivewireSumsub;

use AnselmiDev\LivewireSumsub\Console\InstallCommand;
use AnselmiDev\LivewireSumsub\Livewire\KycVerification;
use AnselmiDev\LivewireSumsub\Support\SyncsSumsubConfig;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LivewireSumsubServiceProvider extends PackageServiceProvider
{
    use SyncsSumsubConfig;

    public function configurePackage(Package $package): void
    {
        $package
            ->name('livewire-sumsub')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasCommand(InstallCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->syncSumsubConfiguration();
    }

    public function packageBooted(): void
    {
        $this->syncSumsubConfiguration();
        $this->registerLivewireComponent();
    }

    private function registerLivewireComponent(): void
    {
        if (! class_exists(Livewire::class)) {
            return;
        }

        Livewire::component(
            config('livewire-sumsub.component_tag', 'livewire-sumsub.kyc-verification'),
            KycVerification::class,
        );
    }
}
