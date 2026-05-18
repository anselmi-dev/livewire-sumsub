<?php

declare(strict_types=1);

namespace AnselmiDev\LivewireSumsub\Support;

/**
 * Propaga la sección livewire-sumsub.sumsub hacia config('sumsub')
 * para que el paquete base anselmi-dev/sumsub funcione sin publicar su config.
 */
trait SyncsSumsubConfig
{
    protected function syncSumsubConfiguration(): void
    {
        /** @var array<string, mixed> $sumsub */
        $sumsub = config('livewire-sumsub.sumsub', []);

        if ($sumsub === []) {
            return;
        }

        config([
            'sumsub' => array_merge(config('sumsub', []), $sumsub),
        ]);
    }
}
