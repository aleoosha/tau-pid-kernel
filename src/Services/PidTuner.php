<?php declare(strict_types=1);

namespace Aleoosha\TauPid\Kernel\Services;

use Aleoosha\Support\Types\FixedPoint;
use Aleoosha\TauPid\Contracts\PidTunerInterface;
use Aleoosha\TauPid\Contracts\DTO\PidSettings;
use Aleoosha\TauPid\Contracts\DTO\FixedPidResult;

final class PidTuner implements PidTunerInterface
{
    public function tune(PidSettings $currentSettings, FixedPoint $currentError, ?FixedPidResult $lastResult): PidSettings
    {
        if (!$lastResult) return $currentSettings;

        $kp = $lastResult->kp;
        $ki = $lastResult->ki;
        $lastError = $lastResult->lastError;

        // Detect Resonance: Error changed sign
        if ($this->hasChangedSign($currentError, $lastError)) {
            $kp = $kp->multiply(FixedPoint::fromFloat(0.90));
        }

        // Detect Stagnation: Error is significant but not moving
        if ($this->isStagnating($currentError, $lastError)) {
            $ki = $ki->add(FixedPoint::fromFloat(0.05));
        }

        // Clamping Kp between 0.1 and 2.0 of base value
        $kp = $this->clampSettings($kp, $currentSettings->kp
            ->multiply(FixedPoint::fromFloat(0.1)), $currentSettings->kp->multiply(FixedPoint::fromInt(2)));
        // Clamping Ki between 0 and 1.0
        $ki = $this->clampSettings($ki, new FixedPoint(0), FixedPoint::fromInt(1));

        return new PidSettings($kp, $ki, $currentSettings->kd, $currentSettings->antiWindup);
    }

    private function hasChangedSign(FixedPoint $curr, FixedPoint $last): bool
    {
        return ($curr->value > 0 && $last->value < 0) || ($curr->value < 0 && $last->value > 0);
    }

    private function isStagnating(FixedPoint $curr, FixedPoint $last): bool
    {
        $diff = abs($curr->value - $last->value);
        return abs($curr->value) > (0.1 * FixedPoint::SCALE) && $diff < (0.05 * FixedPoint::SCALE);
    }

    private function clampSettings(FixedPoint $val, FixedPoint $min, FixedPoint $max): FixedPoint
    {
        if ($val->isLessThan($min)) return $min;
        if ($val->isGreaterThan($max)) return $max;
        return $val;
    }
}
