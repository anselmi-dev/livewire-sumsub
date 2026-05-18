<?php

declare(strict_types=1);

namespace AnselmiDev\LivewireSumsub\Livewire;

use AnselmiDev\Sumsub\Contracts\KycRepositoryInterface;
use AnselmiDev\Sumsub\DataTypes\KycVerificationState;
use AnselmiDev\Sumsub\Services\SumsubService;
use Livewire\Attributes\On;
use Livewire\Attributes\Renderless;
use Livewire\Component;
use Throwable;

/**
 * Widget Livewire de verificación KYC (Sumsub Web SDK).
 *
 * @see KycVerificationState
 *
 * Usage: <livewire:livewire-sumsub.kyc-verification />
 */
class KycVerification extends Component
{
    public string $state = KycVerificationState::Idle->value;

    public string $reviewAnswer = '';

    public ?string $sdkToken = null;

    public ?string $errorMessage = null;

    public function mount(KycRepositoryInterface $repository): void
    {
        $user = auth()->user();

        if ($user === null) {
            return;
        }

        $applicant = $repository->findByUserId($user->getAuthIdentifier());

        if ($applicant === null) {
            return;
        }

        $this->reviewAnswer = $applicant->review_answer ?? '';
        $this->state        = $applicant->verificationState()->value;
    }

    public function startVerification(SumsubService $sumsub): void
    {
        $this->state        = KycVerificationState::Loading->value;
        $this->errorMessage = null;

        try {
            $result = $sumsub->generateSdkToken(auth()->user());

            $this->sdkToken = $result['token'];
            $this->state    = KycVerificationState::SdkReady->value;

            $this->dispatch('sumsub:launch', token: $this->sdkToken);
        } catch (Throwable $e) {
            $this->state        = KycVerificationState::Error->value;
            $this->errorMessage = __('livewire-sumsub::messages.start_verification_failed');
            report($e);
        }
    }

    #[Renderless]
    #[On('sumsub:refresh-token')]
    public function refreshSdkToken(SumsubService $sumsub): void
    {
        try {
            $result = $sumsub->generateSdkToken(auth()->user());

            $this->sdkToken = $result['token'];

            $this->dispatch('sumsub:token-refreshed', token: $this->sdkToken);
        } catch (Throwable $e) {
            report($e);
        }
    }

    #[On('sumsub:review-complete')]
    public function onReviewComplete(string $reviewAnswer): void
    {
        $this->reviewAnswer = $reviewAnswer;
        $this->sdkToken     = null;
        $this->state        = KycVerificationState::fromReviewAnswer($reviewAnswer)->value;
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire-sumsub::livewire.kyc-verification');
    }
}
