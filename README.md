# Tau PID Kernel 🧠

A high-precision mathematical engine for PID (Proportional-Integral-Derivative) control based on **Fixed-Point arithmetic**. Designed for real-time load regulation and automated coefficient adaptation (Self-Tuning) in high-load environments.

Part of the **HiveMind** ecosystem.

## Features

- **Fixed-Point Precision**: Performs all calculations using integers (scaled by 1000) to avoid floating-point inaccuracies and ensure consistency across environments.
- **Adaptive Tuning**: Built-in tuner detects system resonance (oscillations) and stagnation to dynamically adjust `Kp` and `Ki` gains.
- **Anti-Windup Protection**: Prevents integral saturation using configurable clamping limits.
- **Zero Dependencies**: The kernel is completely standalone, relying only on `support-types` and `tau-pid-contracts`.

## Installation

```bash
composer require aleoosha/tau-pid-kernel
```

## Core Components

### PidCalculator
The main computational unit that processes error signals into control output.

```php
use Aleoosha\TauPid\Kernel\Services\PidCalculator;
use Aleoosha\Support\Types\FixedPoint;

$calculator = new PidCalculator();

$result = $calculator->calculate(
    error: FixedPoint::fromFloat(0.15), // 15% error signal
    deltaTimeMs: 1000,                  // Time elapsed since last cycle
    previousState: $lastResult,         // Previous FixedPidResult DTO
    settings: $settings                 // Current PidSettings (Kp, Ki, Kd)
);

echo $result->output->toFloat(); // Control signal (0.0 to 1.0)
```

### PidTuner
An adaptive logic layer that evolves PID coefficients based on historical performance.

```php
use Aleoosha\TauPid\Kernel\Services\PidTuner;

$tuner = new PidTuner();

// Automatically reduces Kp if system oscillation is detected (sign change in error)
// Increases Ki if the system is stuck in steady-state error
$newSettings = $tuner->tune(
    $baseSettings, 
    $currentError, 
    $lastResult
);
```

## Math Logic

- **Normalization**: The kernel expects signals in a normalized range (0.0 to 1.0+).
- **Hard Clamping**: Final output is strictly clamped to the `[0, 1]` range (0% to 100% shedding).
- **Time Invariance**: Calculations use `deltaTimeMs` to ensure consistent behavior regardless of the execution frequency.

## Testing

The kernel includes a comprehensive **Pest** test suite to verify mathematical accuracy and tuning responsiveness:

```bash
./vendor/bin/pest
```

## Architecture Role

In the **HiveMind** ecosystem, this library acts as the **Execution Layer (Kernel)**. It contains no infrastructure dependencies (like Redis or Laravel) and focuses purely on Control Theory (TAU).

---

**Next step:** Do you want me to prepare a similar English README for the **DSS Core** or the **Main Laravel Bridge**?
