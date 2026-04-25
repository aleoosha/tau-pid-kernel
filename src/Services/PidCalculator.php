<?php declare(strict_types=1);

namespace Aleoosha\TauPid\Kernel\Services;

use Aleoosha\Support\Types\FixedPoint;
use Aleoosha\TauPid\Contracts\PidCalculatorInterface;
use Aleoosha\TauPid\Contracts\DTO\PidSettings;
use Aleoosha\TauPid\Contracts\DTO\FixedPidResult;

final class PidCalculator implements PidCalculatorInterface
{
    public function calculate(
        FixedPoint $error,
        int $deltaTimeMs,
        ?FixedPidResult $previousState,
        PidSettings $settings
    ): FixedPidResult {
        // Use 1 second as base if delta is zero or missing
        $dt = $deltaTimeMs > 0 ? FixedPoint::fromFloat($deltaTimeMs / 1000) : FixedPoint::fromInt(1);
        
        $prevIntegral = $previousState?->integral ?? new FixedPoint(0);
        $prevError = $previousState?->lastError ?? new FixedPoint(0);

        // 1. Proportional: P = Kp * error
        $pTerm = $settings->kp->multiply($error);

        // 2. Integral: I = I_prev + (Ki * error * dt)
        $iStep = $settings->ki->multiply($error)->multiply($dt);
        $integral = $prevIntegral->add($iStep);

        // Anti-windup clamping
        $integral = $this->clamp($integral, $settings->antiWindup);

        // 3. Derivative: D = Kd * (error - prevError) / dt
        $dTerm = new FixedPoint(0);
        if ($deltaTimeMs > 0) {
            $dTerm = $error->subtract($prevError)->divide($dt)->multiply($settings->kd);
        }

        // Output = (P + I + D) * 100 (matching your original logic)
        $multiplier = FixedPoint::fromInt(100);
        $output = $pOut = $pTerm->add($integral)->add($dTerm)->multiply($multiplier);

        return new FixedPidResult(
            output: $this->clamp($output, FixedPoint::fromInt(100)),
            lastError: $error,
            integral: $integral,
            timestampMs: (int)(microtime(true) * 1000),
            kp: $settings->kp,
            ki: $settings->ki,
            kd: $settings->kd
        );
    }

    private function clamp(FixedPoint $val, FixedPoint $limit): FixedPoint
    {
        $zero = new FixedPoint(0);
        if ($val->isLessThan($zero)) return $zero;
        if ($val->isGreaterThan($limit)) return $limit;
        return $val;
    }

    public function reset(): void {}
}
