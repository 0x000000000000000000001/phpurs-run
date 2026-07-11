<?php

$exports = [];

$_runReaderAtImpl = function($symStr, $e = null, $r = null) use (&$_runReaderAtImpl) {
    if (func_num_args() < 3) {
        $__args = func_get_args();
        return function(...$more) use ($__args, &$_runReaderAtImpl) {
            return $_runReaderAtImpl(...array_merge($__args, $more));
        };
    }
    
    $runReaderAtClosure = function($e_inner, $r_inner) use (&$runReaderAtClosure, $symStr) {
        $bindNodeClass = (array_key_exists('Control_Monad_Free_BindNode', $GLOBALS) ? $GLOBALS['Control_Monad_Free_BindNode'] : \Control\Monad\Free\phpurs_eval_thunk('Control_Monad_Free_BindNode'));
        $bindLeafClass = (array_key_exists('Control_Monad_Free_BindLeaf', $GLOBALS) ? $GLOBALS['Control_Monad_Free_BindLeaf'] : \Control\Monad\Free\phpurs_eval_thunk('Control_Monad_Free_BindLeaf'));
        $freeObjClass = (array_key_exists('Control_Monad_Free_FreeObj', $GLOBALS) ? $GLOBALS['Control_Monad_Free_FreeObj'] : \Control\Monad\Free\phpurs_eval_thunk('Control_Monad_Free_FreeObj'));

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
                    return new $freeObjClass(0, $f->valueOrFa, null);
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
                    // Reader
                    $k = $variantF->value;
                    $b = $k($e_inner);
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
                    
                    $nextLoop = function($x) use (&$runReaderAtClosure, $e_inner) {
                        return $runReaderAtClosure($e_inner, $x);
                    };
                    
                    return $bindImpl($lifted)($nextLoop);
                }
            }
        }
    };
    
    return $runReaderAtClosure($e, $r);
};

$exports['runReaderAtImpl'] = $_runReaderAtImpl;
return $exports;
