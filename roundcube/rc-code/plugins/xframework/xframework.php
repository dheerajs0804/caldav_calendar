<?php
/**
 * Roundcube Plus Framework plugin.
 *
 * Copyright 2016, Tecorama LLC.
 *
 * @license Commercial. See the LICENSE file for details.
 */

if (!function_exists("dd")) {
    function dd($var)
    {
        var_dump($var);
        exit;
    }
}

class xframework extends rcube_plugin
{
    /**
     * @codeCoverageIgnore
     */
    public function init()
    {
    }
}
