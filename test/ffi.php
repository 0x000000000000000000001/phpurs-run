<?php

// Exercise the Reader/State interpreters with the actual Free continuation tree.
$exports = [];
$free = require dirname(__DIR__, 2) . '/phpurs-free/src/Control/Monad/Free.php';
$exports = [];
$reader = require dirname(__DIR__) . '/src/Run/Reader.php';
$exports = [];
$state = require dirname(__DIR__) . '/src/Run/State.php';

$checks = 0;
function same($label, $expected, $actual)
{
    global $checks;
    if ($expected !== $actual) {
        throw new Exception(
            $label .
                ': expected ' .
                var_export($expected, true) .
                ', got ' .
                var_export($actual, true),
        );
    }
    $checks++;
}

$pure = $free['pureImpl'];
$bind = $free['bindImpl'];
$lift = $free['liftF'];
$bindCurried = fn($computation) => fn($continuation) => $bind($computation, $continuation);
$mapVariant = fn($map) => fn($variant) => (object) [
    'type' => $variant->type,
    'value' => fn($reply) => $map(($variant->value)($reply)),
];
$runReader = fn($computation) => $reader['runReaderAtImpl'](
    $free['bindNodeClass'],
    $free['bindLeafClass'],
    $free['freeObjClass'],
    $bindCurried,
    $mapVariant,
    'reader',
    10,
    $computation,
);
$ask = fn() => $lift((object) ['type' => 'reader', 'value' => fn($environment) => $environment]);

// A continuation returning Pure must retain the rest of the bind tree.
$computation = $bind(
    $bind($pure(1), fn($value) => $pure($value + 1)),
    fn($value) => $pure($value * 10),
);
same('Reader pure chain', 20, $runReader($computation)->valueOrFa);
$stateResult = $state['runStateAtImpl'](
    $free['bindNodeClass'],
    $free['bindLeafClass'],
    $free['freeObjClass'],
    $bindCurried,
    $mapVariant,
    fn($state) => fn($value) => [$state, $value],
    'state',
    10,
    $computation,
);
same('State pure chain', [10, 20], $stateResult->valueOrFa);

// Reader effects still resume the pending continuation after reading the environment.
$computation = $bind($bind($pure(1), fn() => $ask()), fn($value) => $pure($value * 2));
same('Reader effect in a chain', 20, $runReader($computation)->valueOrFa);

// Inner continuations run before the pending outer continuation.
$computation = $bind(
    $bind($pure(1), fn($value) => $bind($pure($value + 1), fn($next) => $pure($next * 3))),
    fn($value) => $pure($value + 4),
);
same('Reader nested continuation tree', 10, $runReader($computation)->valueOrFa);

// Forward an unrelated effect, then resume Reader with its original environment.
$computation = $bind(
    $bind(
        $pure(1),
        fn($value) => $lift(
            (object) [
                'type' => 'external',
                'value' => fn($reply) => $value + $reply,
            ],
        ),
    ),
    fn($value) => $bind($ask(), fn($environment) => $pure($value + $environment)),
);
$current = $runReader($computation);
$handledEffects = [];
while (true) {
    $step = $free['resumePrime'](
        fn($variant) => fn($continuation) => ['effect', $variant, $continuation],
        fn($value) => ['pure', $value],
        $current,
    );
    if ($step[0] === 'pure') {
        same('Reader resumes after an external effect', 31, $step[1]);
        break;
    }
    $variant = $step[1];
    $handledEffects[] = $variant->type;
    if ($variant->type !== 'external') {
        throw new Exception('Reader leaked its own effect to the external handler');
    }
    $current = $step[2](($variant->value)(20));
}
same('External effect forwarded exactly once', ['external'], $handledEffects);

echo "$checks Run checks passed\n";
