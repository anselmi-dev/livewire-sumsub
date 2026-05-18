<?php

declare(strict_types=1);

namespace AnselmiDev\LivewireSumsub\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'livewire-sumsub:install
                            {--force : Sobrescribir config publicada}';

    protected $description = 'Publica la configuración de livewire-sumsub y muestra las variables .env necesarias';

    public function handle(): int
    {
        $this->components->info('Instalando anselmi-dev/livewire-sumsub…');
        $this->components->info('(anselmi-dev/sumsub se instala automáticamente como dependencia)');

        $this->call('vendor:publish', [
            '--tag' => 'livewire-sumsub-config',
            '--force' => $this->option('force'),
        ]);

        $this->newLine();
        $this->components->warn('Añade estas variables a tu archivo .env:');
        $this->line('');
        $this->line('SUMSUB_APP_TOKEN=');
        $this->line('SUMSUB_SECRET_KEY=');
        $this->line('SUMSUB_BASE_URL=https://api.sumsub.com');
        $this->line('SUMSUB_WEBHOOK_SECRET=');
        $this->line('SUMSUB_DEFAULT_LEVEL=basic-kyc-level');
        $this->line('SUMSUB_WEBHOOK_ROUTE=webhooks/sumsub');
        $this->newLine();
        $this->components->info('Ejecuta las migraciones: php artisan migrate');
        $this->components->info('Usa el widget: <livewire:livewire-sumsub.kyc-verification />');
        $this->newLine();
        $this->components->info('Webhooks: excluye la ruta del CSRF si aplica (ver README).');
        $this->components->info('Eventos KYC: escucha ApplicantReviewed / ApplicantStatusChanged en tu app (ver README → Eventos).');

        return self::SUCCESS;
    }
}
