<?php

$exports = [];

$_runStateAtImpl = function (
    $bindNodeClass,
    $bindLeafClass,
    $freeObjClass,
    $bindImpl,
    $mapVariantF,
    $Tuple,
    $effectLabel,
    $state,
    $computation,
) {
    $runStateAtClosure = function ($state, $computation) use (
        &$runStateAtClosure,
        $effectLabel,
        $bindNodeClass,
        $bindLeafClass,
        $freeObjClass,
        $bindImpl,
        $mapVariantF,
        $Tuple,
    ) {
        $current = $computation;
        while (true) {
            if ($current->tag === 0) {
                // Pure
                // Visit the leftmost continuation, retaining right branches.
                $bindTree = $current->binds;
                $stack = [];
                $continuation = null;

                while ($bindTree !== null) {
                    if ($bindTree instanceof $bindLeafClass) {
                        $continuation = $bindTree->k;
                        break;
                    } elseif ($bindTree instanceof $bindNodeClass) {
                        $stack[] = $bindTree->right;
                        $bindTree = $bindTree->left;
                    }
                }

                if ($continuation === null) {
                    return new $freeObjClass(0, $Tuple($state)($current->valueOrFa), null);
                }

                $restBinds = null;
                foreach ($stack as $bindSubtree) {
                    if ($restBinds === null) {
                        $restBinds = $bindSubtree;
                    } else {
                        $restBinds = new $bindNodeClass($bindSubtree, $restBinds);
                    }
                }

                $next = $continuation($current->valueOrFa);

                if ($next->binds === null) {
                    $newBinds = $restBinds;
                } elseif ($restBinds === null) {
                    $newBinds = $next->binds;
                } else {
                    $newBinds = new $bindNodeClass($next->binds, $restBinds);
                }

                $current = new $freeObjClass($next->tag, $next->valueOrFa, $newBinds);
            } else {
                // Lift
                $variantF = $current->valueOrFa;
                if ($variantF->type === $effectLabel) {
                    // It is our state effect
                    $stateObj = $variantF->value;
                    $updateState = $stateObj->value0;
                    $readState = $stateObj->value1;
                    $state = $updateState($state);
                    $value = $readState($state);
                    $current = new $freeObjClass(0, $value, $current->binds);
                } else {
                    // Another effect

                    $binds = $current->binds;
                    $resume = function ($value) use ($freeObjClass, $binds) {
                        return new $freeObjClass(0, $value, $binds);
                    };

                    $mappedVariantF = $mapVariantF($resume)($variantF);
                    $lifted = new $freeObjClass(1, $mappedVariantF, null);

                    $nextLoop = function ($nextComputation) use ($runStateAtClosure, $state) {
                        return $runStateAtClosure($state, $nextComputation);
                    };

                    return $bindImpl($lifted)($nextLoop);
                }
            }
        }
    };

    return $runStateAtClosure($state, $computation);
};

$exports['runStateAtImpl'] = $_runStateAtImpl;
return $exports;
