<?php

$exports = [];

// The JavaScript FFI schedules with setTimeout; here the event loop owns it.
$exports['setTimeout'] = function($ms, $eff) {
    return function() use ($ms, $eff) {
        \Revolt\EventLoop::delay($ms / 1000, function() use ($eff) { $eff($GLOBALS['Data_Unit_unit']); });
    };
};

return $exports;
