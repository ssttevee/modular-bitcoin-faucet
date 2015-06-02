<?php

$module = new \AllTheSatoshi\Util\Module(__DIR__, "2048", "NumbersFaucet");
$module->slug = "twenty-forty-eight";
$module->useWebSocket = true;
return $module;