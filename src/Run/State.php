<?php

$exports = [];

$_runStateAtImpl = function($symStr, $s = null, $r = null) use (&$_runStateAtImpl) {
    if (func_num_args() < 3) {
        $__args = func_get_args();
        return function(...$more) use ($__args, &$_runStateAtImpl) {
            return $_runStateAtImpl(...array_merge($__args, $more));
        };
    }
    
    $runStateAtClosure = function($s_inner, $r_inner) use (&$runStateAtClosure, $symStr) {
        $bindNodeClass = \Control\Monad\Free\BindNode::class;
        $bindLeafClass = \Control\Monad\Free\BindLeaf::class;
        $freeObjClass = \Control\Monad\Free\FreeObj::class;
        $Tuple = (array_key_exists('Data_Tuple_Tuple', $GLOBALS) ? $GLOBALS['Data_Tuple_Tuple'] : \Data\Tuple\phpurs_eval_thunk('Data_Tuple_Tuple'));

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
                    $t = $stateObj->v0;
                    $k = $stateObj->v1;
                    $s_inner = $t($s_inner);
                    $b = $k($s_inner);
                    $f = new $freeObjClass(0, $b, $f->binds);
                } else {
                    // Another effect
                    $mapVariantF = ((array_key_exists('Data_Functor_Variant_functorVariantF', $GLOBALS) ? $GLOBALS['Data_Functor_Variant_functorVariantF'] : \Data\Functor\Variant\phpurs_eval_thunk('Data_Functor_Variant_functorVariantF')))->map;
                    $bindImpl = (array_key_exists('Control_Monad_Free_bindImpl', $GLOBALS) ? $GLOBALS['Control_Monad_Free_bindImpl'] : \Control\Monad\Free\phpurs_eval_thunk('Control_Monad_Free_bindImpl'));
                    
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
