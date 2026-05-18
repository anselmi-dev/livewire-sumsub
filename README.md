# anselmi-dev/livewire-sumsub

Complete Livewire widget for KYC verification with [Sumsub](https://sumsub.com).

**You only need to install this package.** It automatically includes [`anselmi-dev/sumsub`](https://github.com/anselmi-dev/sumsub) (API, webhooks, database) and syncs credentials from a single configuration file.

---

## Requirements

| Package | Version |
|---|---|
| PHP | ^8.3 |
| Laravel | ^11 \| ^12 \| ^13 |
| livewire/livewire | ^3.5 \| ^4 |
| livewire/flux | ^2 *(default views)* |

---

## Installation (single package)

### 1. Composer

```bash
composer require anselmi-dev/livewire-sumsub:^1.0
```

### 2. Configuration and Sumsub credentials

```bash
php artisan livewire-sumsub:install
```

Publishes `config/livewire-sumsub.php` and shows the required `.env` variables.

Add to your `.env`:

```env
# Sumsub credentials (dashboard → Developer Tools → App Tokens)
SUMSUB_APP_TOKEN=your-app-token
SUMSUB_SECRET_KEY=your-secret-key
SUMSUB_BASE_URL=https://api.sumsub.com
SUMSUB_WEBHOOK_SECRET=your-webhook-secret
SUMSUB_DEFAULT_LEVEL=basic-kyc-level

# Webhook (route registered automatically by anselmi-dev/sumsub)
SUMSUB_WEBHOOK_ROUTE=webhooks/sumsub
```

Webhooks are queued using your app's default queue connection (`config/queue.php`). To use a different queue, configure the job in your project (e.g. `$job->onQueue('sumsub')` in a listener or published controller override).

You do not need to publish `config/sumsub.php`: on boot, this package copies the `sumsub` section from `livewire-sumsub.php` into the config used by the base package.

### 3. Database

```bash
php artisan migrate
```

The `sumsub_applicants` migration is provided by `anselmi-dev/sumsub` (auto-loaded).

### 4. View in your app

```blade
{{-- Layout must include @stack('scripts') --}}
<livewire:livewire-sumsub.kyc-verification />
```

SaaS / multi-tenant:

```blade
<livewire:livewire-sumsub.kyc-verification :tenant-id="$tenant->id" />
```

---

## What each layer includes

| Layer | Package | Responsibility |
|---|---|---|
| Sumsub API, webhooks, DB | `anselmi-dev/sumsub` *(dependency)* | HTTP client, `SumsubService`, events |
| Livewire widget + UI | `anselmi-dev/livewire-sumsub` | Component, Blade, Alpine, SDK |

---

## Customize views

```bash
php artisan vendor:publish --tag=livewire-sumsub-views
php artisan vendor:publish --tag=livewire-sumsub-translations
```

Per-state components: `x-livewire-sumsub::state-idle`, `state-loading`, `state-sdk-ready`, etc.

---

## Webhooks and CSRF

Sumsub will send POST requests to `/webhooks/sumsub` (configurable via `SUMSUB_WEBHOOK_ROUTE`).

Exclude the route from CSRF middleware if applicable:

```php
// bootstrap/app.php
$middleware->validateCsrfTokens(except: [
    'webhooks/sumsub',
]);
```

Make sure a queue worker is running (`php artisan queue:work`): the `ProcessSumsubWebhook` job is queued and **events are dispatched from there**.

---

## Events: apply your own business logic

The Livewire widget only covers the UI. When Sumsub notifies changes via webhook, the base package (`anselmi-dev/sumsub`) updates `sumsub_applicants` and dispatches Laravel events so **your application** can react.

### Flow

```
Sumsub → POST /webhooks/sumsub
      → ProcessSumsubWebhook (queue)
      → updates SumsubApplicant
      → ApplicantStatusChanged (always)
      → ApplicantReviewed (only when reviewAnswer is in the payload)
```

### Available events

| Event | Namespace | When it fires | Typical use in your app |
|---|---|---|---|
| `ApplicantCreated` | `AnselmiDev\Sumsub\Events\ApplicantCreated` | When creating an applicant via `SumsubService::createApplicant()` | Initialize user KYC state, logs, CRM |
| `ApplicantStatusChanged` | `AnselmiDev\Sumsub\Events\ApplicantStatusChanged` | After **every** webhook that updates the record | Sync intermediate state (`pending`, `queued`, etc.) |
| `ApplicantReviewed` | `AnselmiDev\Sumsub\Events\ApplicantReviewed` | When the webhook includes `reviewResult.reviewAnswer` (`GREEN`, `RED`, `RETRY`) | Approve/reject user, emails, unlock investing |

Common properties:

```php
$event->applicant;        // SumsubApplicant model (user_id, review_status, review_answer, …)
$event->webhookPayload;  // raw webhook JSON array (ApplicantStatusChanged and ApplicantReviewed)

// ApplicantReviewed only:
$event->reviewAnswer;    // 'GREEN' | 'RED' | 'RETRY'
$event->isApproved();
$event->isRejected();
$event->needsRetry();
```

Relation to your app user:

```php
$user = $event->applicant->user; // BelongsTo per auth.providers.users.model
```

### Option 1: listener in `AppServiceProvider`

```php
// app/Providers/AppServiceProvider.php

use AnselmiDev\Sumsub\Events\ApplicantReviewed;
use AnselmiDev\Sumsub\Events\ApplicantStatusChanged;
use Illuminate\Support\Facades\Event;

public function boot(): void
{
    Event::listen(ApplicantStatusChanged::class, function (ApplicantStatusChanged $event): void {
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
            return;
        }

        if ($event->isRejected()) {
            $user->update(['kyc_verified_at' => null]);
            return;
        }

        if ($event->needsRetry()) {
            // user must restart the flow in the widget
        }
    });
}
```

### Option 2: dedicated listener class (recommended)

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

Register it in `AppServiceProvider` or `bootstrap/app.php` (Laravel 11+):

```php
use AnselmiDev\Sumsub\Events\ApplicantReviewed;
use App\Listeners\SyncUserKycOnApplicantReviewed;
use Illuminate\Support\Facades\Event;

Event::listen(ApplicantReviewed::class, SyncUserKycOnApplicantReviewed::class);
```

If the listener implements `ShouldQueue`, it will be queued **after** the webhook job, using your project's default queue (`config/queue.php`).

### Option 3: automatic listener discovery

Place listeners in `app/Listeners` with Laravel's event discovery enabled; implement `handle(ApplicantReviewed $event)` following your app's naming conventions.

### Which event to choose

| You need to… | Listen to |
|---|---|
| React only to the final result (approved / rejected / retry) | `ApplicantReviewed` |
| Every applicant status change (including `pending`, `onHold`, etc.) | `ApplicantStatusChanged` |
| Know when the applicant was first created | `ApplicantCreated` |

`ApplicantReviewed` is the most common for business rules (enable investing, set `kyc_verified_at`, send emails).

### Test locally

```bash
# Simulate a GREEN webhook (sumsub package, non-production only)
php artisan sumsub:simulate-webhook --answer=GREEN --sync
```

With `--sync` the job runs immediately and your listeners should behave like in production (unless the listener is also queued — then you need `queue:work`).

---

## Extend the Livewire component

```php
namespace App\Livewire\Kyc;

use AnselmiDev\LivewireSumsub\Livewire\KycVerification as BaseKycVerification;

class KycVerification extends BaseKycVerification {}
```

---

## License

MIT
