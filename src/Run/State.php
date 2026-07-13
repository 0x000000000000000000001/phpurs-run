<?php

$exports = [];

$_runStateAtImpl = function($bindNodeClass = null, $bindLeafClass = null, $freeObjClass = null, $bindImpl = null, $mapVariantF = null, $Tuple = null, $symStr = null, $s = null, $r = null) use (&$_runStateAtImpl) {
    if (func_num_args() < 9) {
        $__args = func_get_args();
        return function(...$more) use ($__args, &$_runStateAtImpl) {
            return $_runStateAtImpl(...array_merge($__args, $more));
        };
    }
    
    $runStateAtClosure = function($s_inner, $r_inner) use (&$runStateAtClosure, $symStr, $bindNodeClass, $bindLeafClass, $freeObjClass, $bindImpl, $mapVariantF, $Tuple) {

        $f = $r_inner;
        while (true) {
            if ($f->tag === 0) { // Pure
                $curr = $f->binds;
                $stack = [];
                $first = null;
                
                while ($curr !== null) {
                    if ($curr instanceof $bindLeafClass) {
                        $first = $curr->k;
                        break;
                    } else if ($curr instanceof $bindNodeClass) {
                        $stack[] = $curr->right;
                        $curr = $curr->left;
                    }
                }
                
                if ($first === null) {
                    return new $freeObjClass(0, $Tuple($s_inner)($f->valueOrFa), null);
                }

                $restBinds = null;
                foreach ($stack as $st) {
                    if ($restBinds === null) {
                        $restBinds = $st;
                    } else {
                        $restBinds = new $bindNodeClass($st, $restBinds);
                    }
                }

                $f2 = $first($f->valueOrFa);
                
                $newBinds = null;
                if ($f2->binds === null) {
                    $newBinds = $restBinds;
                } else if ($restBinds === null) {
                    $newBinds = $f2->binds;
                } else {
                    $newBinds = new $bindNodeClass($f2->binds, $restBinds);
                }
                
                $f = new $freeObjClass($f2->tag, $f2->valueOrFa, $newBinds);
            } else {
                // Lift
                $variantF = $f->valueOrFa;
                if ($variantF->type === $symStr) {
                    // It is our state effect
                    $stateObj = $variantF->value;
                    $t = $stateObj->value0;
                    $k = $stateObj->value1;
                    $s_inner = $t($s_inner);
                    $b = $k($s_inner);
                    $f = new $freeObjClass(0, $b, $f->binds);
                } else {
                    // Another effect
                    
                    $binds = $f->binds;
                    $cont = function($b) use ($freeObjClass, $binds) {
                        return new $freeObjClass(0, $b, $binds);
                    };
                    
                    $mappedVariantF = ($mapVariantF)($cont)($variantF);
                    $lifted = new $freeObjClass(1, $mappedVariantF, null);
                    
                    $nextLoop = function($x) use (&$runStateAtClosure, $s_inner) {
                        return $runStateAtClosure($s_inner, $x);
                    };
                    
                    return $bindImpl($lifted)($nextLoop);
                }
            }
        }
    };
    
    return $runStateAtClosure($s, $r);
};

$exports['runStateAtImpl'] = $_runStateAtImpl;
return $exports;
