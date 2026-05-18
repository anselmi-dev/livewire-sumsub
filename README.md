# anselmi-dev/livewire-sumsub

Widget Livewire completo para verificación KYC con [Sumsub](https://sumsub.com).

**Solo necesitas instalar este paquete.** Incluye automáticamente [`anselmi-dev/sumsub`](https://github.com/anselmi-dev/sumsub) (API, webhooks, base de datos) y sincroniza las credenciales desde un único archivo de configuración.

---

## Requisitos

| Paquete | Versión |
|---|---|
| PHP | ^8.3 |
| Laravel | ^11 \| ^12 \| ^13 |
| livewire/livewire | ^3.5 \| ^4 |
| livewire/flux | ^2 *(vistas por defecto)* |

---

## Instalación (un solo paquete)

### 1. Composer

```bash
composer require anselmi-dev/livewire-sumsub
```

Composer instala también `anselmi-dev/sumsub` (^1.0) en `vendor/` como dependencia transitiva.

**No hace falta** publicar `config/sumsub.php` ni duplicar credenciales: todo va en `config/livewire-sumsub.php` (sección `sumsub`) y en las variables `.env` de abajo.

Instalación desde GitHub (si aún no está en Packagist):

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/anselmi-dev/livewire-sumsub"
    }
]
```

Path repository (desarrollo local):

```json
"repositories": [
    {
        "type": "path",
        "url": "../packages/anselmi-dev/livewire-sumsub",
        "options": { "symlink": true }
    }
]
```

### 2. Configuración y credenciales Sumsub

```bash
php artisan livewire-sumsub:install
```

Publica `config/livewire-sumsub.php` y muestra las variables `.env` necesarias.

Añade a tu `.env`:

```env
# Credenciales Sumsub (panel → Developer Tools → App Tokens)
SUMSUB_APP_TOKEN=tu-app-token
SUMSUB_SECRET_KEY=tu-secret-key
SUMSUB_BASE_URL=https://api.sumsub.com
SUMSUB_WEBHOOK_SECRET=tu-webhook-secret
SUMSUB_DEFAULT_LEVEL=basic-kyc-level

# Webhook (ruta registrada automáticamente por anselmi-dev/sumsub)
SUMSUB_WEBHOOK_ROUTE=webhooks/sumsub
```

Los webhooks se encolan con la conexión y cola por defecto de tu app (`config/queue.php`). Para usar otra cola, configura el job en tu proyecto (p. ej. `$job->onQueue('sumsub')` en un listener o override del controlador publicado).

No necesitas publicar `config/sumsub.php`: al arrancar la app, este paquete copia la sección `sumsub` de `livewire-sumsub.php` hacia la config que usa el paquete base.

### 3. Base de datos

```bash
php artisan migrate
```

La migración `sumsub_applicants` la aporta `anselmi-dev/sumsub` (carga automática).

### 4. Vista en tu app

```blade
{{-- El layout debe incluir @stack('scripts') --}}
<livewire:livewire-sumsub.kyc-verification />
```

---

## Qué incluye cada capa

| Capa | Paquete | Responsabilidad |
|---|---|---|
| API Sumsub, webhooks, BD | `anselmi-dev/sumsub` *(dependencia)* | Cliente HTTP, `SumsubService`, eventos |
| Widget Livewire + UI | `anselmi-dev/livewire-sumsub` | Componente, Blade, Alpine, SDK |

---

## Personalizar vistas

```bash
php artisan vendor:publish --tag=livewire-sumsub-views
php artisan vendor:publish --tag=livewire-sumsub-translations
```

Componentes por estado: `x-livewire-sumsub::state-idle`, `state-loading`, `state-sdk-ready`, etc.

---

## Webhooks y CSRF

Sumsub enviará POST a `/webhooks/sumsub` (configurable con `SUMSUB_WEBHOOK_ROUTE`).

Excluye la ruta del middleware CSRF si aplica.

Asegúrate de tener un worker de colas activo (`php artisan queue:work`): el job `ProcessSumsubWebhook` se encola y **desde ahí** se disparan los eventos.

---

## Eventos: aplicar tu propia lógica de negocio

El widget Livewire solo cubre la UI. Cuando Sumsub notifica cambios por webhook, el paquete base (`anselmi-dev/sumsub`) actualiza `sumsub_applicants` y dispara eventos Laravel para que **tu aplicación** reaccione.

### Flujo

```
Sumsub → POST /webhooks/sumsub
      → ProcessSumsubWebhook (cola)
      → actualiza SumsubApplicant
      → ApplicantStatusChanged (siempre)
      → ApplicantReviewed (solo si hay reviewAnswer en el payload)
```

### Eventos disponibles

| Evento | Namespace | Cuándo se dispara | Uso típico en tu app |
|---|---|---|---|
| `ApplicantCreated` | `AnselmiDev\Sumsub\Events\ApplicantCreated` | Al crear un applicant en Sumsub vía `SumsubService::createApplicant()` | Inicializar estado KYC del usuario, logs, CRM |
| `ApplicantStatusChanged` | `AnselmiDev\Sumsub\Events\ApplicantStatusChanged` | Tras **cada** webhook que actualiza el registro | Sincronizar estado intermedio (`pending`, `queued`, etc.) |
| `ApplicantReviewed` | `AnselmiDev\Sumsub\Events\ApplicantReviewed` | Cuando el webhook trae `reviewResult.reviewAnswer` (`GREEN`, `RED`, `RETRY`) | Aprobar/rechazar usuario, emails, desbloquear inversión |

Propiedades comunes:

```php
$event->applicant;        // Modelo SumsubApplicant (user_id, review_status, review_answer, …)
$event->webhookPayload;  // array con el JSON crudo del webhook (ApplicantStatusChanged y ApplicantReviewed)

// Solo en ApplicantReviewed:
$event->reviewAnswer;    // 'GREEN' | 'RED' | 'RETRY'
$event->isApproved();
$event->isRejected();
$event->needsRetry();
```

Relación con el usuario de tu app:

```php
$user = $event->applicant->user; // BelongsTo según auth.providers.users.model
```

### Opción 1: listener en `AppServiceProvider`

```php
// app/Providers/AppServiceProvider.php

use AnselmiDev\Sumsub\Events\ApplicantReviewed;
use AnselmiDev\Sumsub\Events\ApplicantStatusChanged;
use Illuminate\Support\Facades\Event;

public function boot(): void
{
    Event::listen(ApplicantStatusChanged::class, function (ApplicantStatusChanged $event): void {
        // Ej.: guardar último estado en tu tabla users o kyc_profiles
        $event->applicant->user?->update([
            'kyc_review_status' => $event->applicant->review_status,
        ]);
    });

    Event::listen(ApplicantReviewed::class, function (ApplicantReviewed $event): void {
        $user = $event->applicant->user;

        if ($user === null) {
            return;
        }

        if ($event->isApproved()) {
            $user->update(['kyc_verified_at' => now()]);
            // Mail::to($user)->send(new KycApprovedMail($user));
            return;
        }

        if ($event->isRejected()) {
            $user->update(['kyc_verified_at' => null]);
            // notificar rechazo
            return;
        }

        if ($event->needsRetry()) {
            // el usuario debe volver a iniciar el flujo en el widget
        }
    });
}
```

### Opción 2: clase listener dedicada (recomendado)

```bash
php artisan make:listener SyncUserKycOnApplicantReviewed --event="AnselmiDev\Sumsub\Events\ApplicantReviewed"
```

```php
// app/Listeners/SyncUserKycOnApplicantReviewed.php

namespace App\Listeners;

use AnselmiDev\Sumsub\Events\ApplicantReviewed;
use Illuminate\Contracts\Queue\ShouldQueue;

class SyncUserKycOnApplicantReviewed implements ShouldQueue
{
    public function handle(ApplicantReviewed $event): void
    {
        $user = $event->applicant->user;

        if ($user === null) {
            return;
        }

        if ($event->isApproved()) {
            $user->update(['kyc_verified_at' => now()]);

            return;
        }

        if ($event->isRejected()) {
            $user->update(['kyc_verified_at' => null]);
        }
    }
}
```

Regístralo en `app/Providers/AppServiceProvider.php` o en `bootstrap/app.php` (Laravel 11+):

```php
use AnselmiDev\Sumsub\Events\ApplicantReviewed;
use App\Listeners\SyncUserKycOnApplicantReviewed;
use Illuminate\Support\Facades\Event;

Event::listen(ApplicantReviewed::class, SyncUserKycOnApplicantReviewed::class);
```

Si el listener implementa `ShouldQueue`, se encolará **después** del job del webhook, usando la cola por defecto de tu proyecto (`config/queue.php`).

### Opción 3: descubrimiento automático de listeners

Coloca listeners en `app/Listeners` y deja activo el descubrimiento de eventos de Laravel; implementa `handle(ApplicantReviewed $event)` en una clase cuyo nombre siga la convención de tu app.

### Qué evento elegir

| Necesitas… | Escucha |
|---|---|
| Reaccionar solo al resultado final (aprobado / rechazado / reintentar) | `ApplicantReviewed` |
| Cada cambio de estado del applicant (incl. `pending`, `onHold`, etc.) | `ApplicantStatusChanged` |
| Saber cuándo se creó el applicant por primera vez | `ApplicantCreated` |

`ApplicantReviewed` es el más habitual para reglas de negocio (habilitar inversión, marcar `kyc_verified_at`, enviar emails).

### Probar en local

```bash
# Simula un webhook GREEN (paquete sumsub, solo entornos no productivos)
php artisan sumsub:simulate-webhook --answer=GREEN --sync
```

Con `--sync` el job corre al instante y tus listeners deberían ejecutarse igual que en producción (salvo que el listener también esté en cola: entonces necesitas `queue:work`).

---

## Extender el componente Livewire

```php
namespace App\Livewire\Kyc;

use AnselmiDev\LivewireSumsub\Livewire\KycVerification as BaseKycVerification;

class KycVerification extends BaseKycVerification {}
```

---

## Arquitectura

Ver [ARCHITECTURE.md](./ARCHITECTURE.md).

---

## Licencia

MIT
