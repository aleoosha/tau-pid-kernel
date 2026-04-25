<?php

use Aleoosha\Support\Types\FixedPoint;
use Aleoosha\TauPid\Contracts\DTO\PidSettings;
use Aleoosha\TauPid\Kernel\Services\PidCalculator;

beforeEach(function () {
    $this->calculator = new PidCalculator();
    $this->settings = new PidSettings(
        kp: FixedPoint::fromFloat(0.5),
        ki: FixedPoint::fromFloat(0.1),
        kd: FixedPoint::fromFloat(0.1),
        antiWindup: FixedPoint::fromInt(100)
    );
});

test('it calculates proportional output correctly', function () {
    // Создаем настройки, где работают ТОЛЬКО пропорции
    $pOnlySettings = new PidSettings(
        kp: FixedPoint::fromFloat(0.5),
        ki: FixedPoint::fromFloat(0.0), // Выключаем интеграл
        kd: FixedPoint::fromFloat(0.0), // Выключаем дифференциал
        antiWindup: FixedPoint::fromInt(100)
    );

    $error = FixedPoint::fromFloat(0.1); 
    $result = $this->calculator->calculate($error, 1000, null, $pOnlySettings);
    
    expect($result->output->toFloat())->toBe(5.0);
});

test('it accumulates integral part over time', function () {
    $error = FixedPoint::fromFloat(0.1);
    
    // Первый такт
    $res1 = $this->calculator->calculate($error, 1000, null, $this->settings);
    // Второй такт (прошло еще 1000мс)
    $res2 = $this->calculator->calculate($error, 1000, $res1, $this->settings);
    
    // Интеграл должен вырасти: I = 0 + (0.1 * 0.1 * 1) = 0.01. На 2 такте еще +0.01 = 0.02
    expect($res2->integral->toFloat())->toBe(0.02);
});

test('it clamps output to maximum 100', function () {
    $hugeError = FixedPoint::fromInt(10); // Огромная ошибка
    
    $result = $this->calculator->calculate($hugeError, 1000, null, $this->settings);
    
    expect($result->output->toFloat())->toBe(100.0);
});
