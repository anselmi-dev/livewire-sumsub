# Arquitectura — livewire-sumsub

Capa de presentación Livewire sobre `anselmi-dev/sumsub`. No duplica lógica de dominio: estados y API viven en el paquete base.

---

## Stack

| Herramienta | Rol |
|---|---|
| `anselmi-dev/sumsub` | API Sumsub, repositorio, `KycVerificationState` |
| `spatie/laravel-package-tools` | Service Provider, publicación de assets |
| `livewire/livewire` | Componente reactivo |
| Tailwind + Alpine | Estilos y Sumsub Web SDK en el cliente |
| `livewire/flux` | UI por defecto en las vistas (opcional tras publicar) |

---

## Estructura

```
config/
└── livewire-sumsub.php

resources/
├── lang/
│   ├── es/messages.php
│   └── en/messages.php
└── views/
    ├── components/          # x-livewire-sumsub::*
    │   ├── widget.blade.php
    │   ├── state-*.blade.php
    │   └── scripts.blade.php
    └── livewire/
        └── kyc-verification.blade.php

src/
├── Livewire/
│   └── KycVerification.php
└── LivewireSumsubServiceProvider.php

tests/
└── TestCase.php
```

---

## Instalación única

El usuario solo ejecuta `composer require anselmi-dev/livewire-sumsub`.

- `anselmi-dev/sumsub` entra como **dependencia de Composer** (no hace falta require manual).
- Las credenciales Sumsub viven en `config/livewire-sumsub.php` → clave `sumsub`.
- `SyncsSumsubConfig` propaga esos valores a `config('sumsub')` en `register` y `boot`.

## Responsabilidades

| Capa | Ubicación | Qué hace |
|---|---|---|
| Dominio KYC | `anselmi-dev/sumsub` *(dependencia)* | Applicants, tokens, webhooks, `KycVerificationState` |
| Config unificada | `config/livewire-sumsub.php` | Credenciales `.env` + opciones del widget |
| Orquestación UI | `Livewire\KycVerification` | Transiciones Loading / SdkReady / Error, eventos SDK |
| Presentación | `resources/views/components/*` | Markup Tailwind/Flux por estado |
| Cliente | `scripts.blade.php` | Alpine `sumsubWidget()`, carga Web SDK |

---

## Publicación

| Tag | Destino |
|---|---|
| `livewire-sumsub-config` | `config/livewire-sumsub.php` |
| `livewire-sumsub-views` | `resources/views/vendor/livewire-sumsub/` |
| `livewire-sumsub-translations` | `lang/vendor/livewire-sumsub/` |

---

## Eventos (paquete `anselmi-dev/sumsub`)

La lógica de negocio del host **no** va en este paquete. Tras cada webhook, `ProcessSumsubWebhook` dispara:

- `ApplicantStatusChanged` — cada actualización
- `ApplicantReviewed` — cuando hay `reviewAnswer` (`GREEN` / `RED` / `RETRY`)

Guía completa con ejemplos de listeners: [README.md § Eventos](./README.md#eventos-aplicar-tu-propia-lógica-de-negocio).

---

## Checklist PR

- [ ] `composer test` en el paquete
- [ ] Vistas publicables siguen usando `__('livewire-sumsub::messages.*')`
- [ ] Sin lógica de negocio Sumsub duplicada (usar `SumsubService` / `KycVerificationState`)
