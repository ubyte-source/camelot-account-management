<?php

error_reporting(0);

date_default_timezone_set('UTC');

if (!defined('BASE_ROOT')) {
    $baseroot = rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR);
    $baseroot = dirname($baseroot);
    define('BASE_ROOT', $baseroot . DIRECTORY_SEPARATOR);
    unset($baseroot);
}

defined('BASE_HANDLERS') or define('BASE_HANDLERS', BASE_ROOT . 'handlers' . DIRECTORY_SEPARATOR);

foreach ($_SERVER as $key => $value)
    if ('ENVIRONMENT' === substr($key, 0, 11))
        define($key, str_replace('\n', chr(10), $value));

defined('ENVIRONMENT_DEBUGGER') and error_reporting(E_ALL) or error_reporting(0);

array_walk_recursive($_POST, function (&$item) {
    if ($item === 'null')
        $item = null;
});
