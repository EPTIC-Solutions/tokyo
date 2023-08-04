<?php

use Illuminate\Container\Container;

if (!function_exists('container')) {
    function container(string $key = null)
    {
        return $key ?
            Container::getInstance()->get($key) :
            Container::getInstance();
    }
}

if (!function_exists('config')) {
    function config($key, $default = null)
    {
        $explode = explode('.', $key);

        $config = container('config');

        foreach ($explode as $key) {
            if (!isset($config[$key])) {
                return $default;
            }

            $config = $config[$key];
        }

        return $config;
    }
}
