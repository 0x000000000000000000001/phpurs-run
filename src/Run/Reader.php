<?php

$exports = [];

$_runReaderAtImpl = function($bindNodeClass, $bindLeafClass, $freeObjClass, $bindImpl, $mapVariantF, $symStr, $e, $r) use (&$_runReaderAtImpl) {
    
    $runReaderAtClosure = function($e_inner, $r_inner) use (&$runReaderAtClosure, $symStr, $bindNodeClass, $bindLeafClass, $freeObjClass, $bindImpl, $mapVariantF) {

        $f = $r_inner;
        while (true) {
            if ($f->tag === 0) { // Pure
                $curr = $f->binds;
                $stack = [];
                $first;
                
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
                    return new $freeObjClass(0, $f->valueOrFa, null);
                }

                $restBinds;
                foreach ($stack as $st) {
                    if ($restBinds === null) {
                        $restBinds = $st;
                    } else {
                        $restBinds = new $bindNodeClass($st, $restBinds);
                    }
                }

                $f2 = $first($f->valueOrFa);
                
                $newBinds;
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
                    // Reader
                    $k = $variantF->value;
                    $b = $k($e_inner);
                    $f = new $freeObjClass(0, $b, $f->binds);
                } else {
                    // Another effect
                    
                    $binds = $f->binds;
                    $cont = function($b) use ($freeObjClass, $binds) {
                        return new $freeObjClass(0, $b, $binds);
                    };
                    
                    $mappedVariantF = ($mapVariantF)($cont)($variantF);
                    $lifted = new $freeObjClass(1, $mappedVariantF, null);
                    
                    $nextLoop = function($x) use ($runReaderAtClosure, $e_inner, $freeObjClass) { return $runReaderAtClosure($e_inner, new $freeObjClass(0, $x, null)); };
                    
                    return $bindImpl($lifted)($nextLoop);
                }
            }
        }
    };
    
    return $runReaderAtClosure($e, $r);
};

$exports['runReaderAtImpl'] = $_runReaderAtImpl;
return $exports;
