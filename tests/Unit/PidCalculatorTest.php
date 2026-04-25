<?php declare(strict_types=1);

namespace Aleoosha\TauPid\Tests\Unit;

use Aleoosha\Support\Types\FixedPoint;
use Aleoosha\TauPid\Contracts\DTO\PidSettings;
use Aleoosha\TauPid\Kernel\Services\PidCalculator;

beforeEach(function () {
    $this->calculator = new PidCalculator();
    $this->settings = new PidSettings(
        kp: FixedPoint::fromFloat(0.5),
        ki: FixedPoint::fromFloat(0.1),
        kd: FixedPoint::fromFloat(0.1),
        antiWindup: FixedPoint::fromInt(1) // Anti-windup scaled to 1.0
    );
});

test('it calculates proportional output correctly', function () {
    $pOnlySettings = new PidSettings(
        kp: FixedPoint::fromFloat(0.5),
        ki: FixedPoint::fromInt(0),
        kd: FixedPoint::fromInt(0),
        antiWindup: FixedPoint::fromInt(1)
    );

    $error = FixedPoint::fromFloat(0.1); // 10% error
    $result = $this->calculator->calculate($error, 1000, null, $pOnlySettings);
    
    // Result: 0.5 * 0.1 = 0.05 (5%)
    expect($result->output->toFloat())->toBe(0.05);
});

test('it accumulates integral part over time', function () {
    $error = FixedPoint::fromFloat(0.1);
    
    $res1 = $this->calculator->calculate($error, 1000, null, $this->settings);
    $res2 = $this->calculator->calculate($error, 1000, $res1, $this->settings);
    
    // Integral: 0.1 * 0.1 * 1s = 0.01 per step. Total = 0.02
    expect($res2->integral->toFloat())->toBe(0.02);
});

test('it clamps output to maximum 1.0', function () {
    $hugeError = FixedPoint::fromInt(10); // Huge error
    
    $result = $this->calculator->calculate($hugeError, 1000, null, $this->settings);
    
    // In the new architecture, max output is always 1.0 (100%)
    expect($result->output->toFloat())->toBe(1.0);
});
