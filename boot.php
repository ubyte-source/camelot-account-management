<?php

require_once 'base.php';

defined('APPLICATIONS') or define('APPLICATIONS',  BASE_ROOT . 'applications' . DIRECTORY_SEPARATOR);

require_once 'vendor/autoload.php';

use Knight\armor\Output;

set_exception_handler(function ($exception) {
    defined('ENVIRONMENT_DEBUGGER') and print_r($exception);
    Output::print(false);
});
