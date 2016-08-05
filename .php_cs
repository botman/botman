<?php

// Needed to get styleci-bridge loaded
require_once __DIR__.'/vendor/sllh/php-cs-fixer-styleci-bridge/autoload.php';

use SLLH\StyleCIBridge\ConfigBridge;

return ConfigBridge::create();