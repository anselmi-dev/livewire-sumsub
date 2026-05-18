<?php

declare(strict_types=1);

use AnselmiDev\Sumsub\DataTypes\KycVerificationState;
use AnselmiDev\Sumsub\Models\SumsubApplicant;

it('maps review answers to widget states', function (): void {
    expect(KycVerificationState::fromReviewAnswer('GREEN')->value)->toBe('completed')
        ->and(KycVerificationState::fromReviewAnswer('RED')->value)->toBe('cancelled')
        ->and(KycVerificationState::fromReviewAnswer('RETRY')->value)->toBe('idle')
        ->and(KycVerificationState::fromReviewAnswer('pending')->value)->toBe('progress');
});

it('maps applicant review fields to widget states', function (): void {
    $approved = new SumsubApplicant([
        'review_status' => 'completed',
        'review_answer' => SumsubApplicant::REVIEW_ANSWER_GREEN,
    ]);

    expect($approved->verificationState())->toBe(KycVerificationState::Completed);
});
