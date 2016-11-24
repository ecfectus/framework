<?php

function env($key, $default = null)
{
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }
    switch (strtolower($value)) {
        case 'true':
        case '(true)':
            return true;
        case 'false':
        case '(false)':
            return false;
        case 'empty':
        case '(empty)':
            return '';
        case 'null':
        case '(null)':
            return;
    }

    return trim($value, '"');
}

/**
 * Get the available container instance.
 *
 * @param  string  $make
 * @param  array   $parameters
 * @return mixed|\Illuminate\Foundation\Application
 */
function app($id = null)
{
    if (is_null($id)) {
        return \Ecfectus\Framework\Application::getInstance();
    }
    return \Ecfectus\Framework\Application::getInstance()->get($id);
}

/**
 * Get the path to the application folder.
 *
 * @param  string  $path
 * @return string
 */
function app_path($path = '')
{
    return app('path').($path ? DIRECTORY_SEPARATOR.$path : $path);
}

/**
 * Get the path to the config folder.
 *
 * @param  string  $path
 * @return string
 */
function config_path($path = '')
{
    return app('path.config').($path ? DIRECTORY_SEPARATOR.$path : $path);
}