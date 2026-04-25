<?php

use Aleoosha\Support\Types\FixedPoint;
use Aleoosha\TauPid\Contracts\DTO\PidSettings;
use Aleoosha\TauPid\Contracts\DTO\FixedPidResult;
use Aleoosha\TauPid\Kernel\Services\PidTuner;

test('it reduces Kp when error changes sign (resonance detection)', function () {
    $tuner = new PidTuner();
    $baseSettings = new PidSettings(
        kp: FixedPoint::fromFloat(1.0),
        ki: FixedPoint::fromFloat(0.1),
        kd: FixedPoint::fromFloat(0.1),
        antiWindup: FixedPoint::fromInt(100)
    );

    // Имитируем прошлое состояние: ошибка была +0.1
    $lastResult = new FixedPidResult(
        output: FixedPoint::fromInt(0),
        lastError: FixedPoint::fromFloat(0.1),
        integral: FixedPoint::fromInt(0),
        timestampMs: time() * 1000,
        kp: FixedPoint::fromFloat(1.0),
        ki: FixedPoint::fromFloat(0.1),
        kd: FixedPoint::fromFloat(0.1)
    );

    // Текущая ошибка сменила знак: -0.1
    $currentError = FixedPoint::fromFloat(-0.1);
    
    $newSettings = $tuner->tune($baseSettings, $currentError, $lastResult);

    // Kp должен уменьшиться (1.0 * 0.9 = 0.9)
    expect($newSettings->kp->toFloat())->toBeLessThan(1.0);
    expect($newSettings->kp->toFloat())->toBe(0.9);
});
