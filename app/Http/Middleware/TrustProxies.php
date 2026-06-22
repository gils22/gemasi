<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;


class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     * Set to '*' to trust all proxies (useful in containerized environments).
     *
     * @var array|string|null
     */
    protected $proxies = '*';

    /**
     * The headers that should be used to detect proxies.
     * Will be resolved at runtime to avoid referencing undefined class constants
     * when using certain framework versions.
     *
     * @var int
     */
    protected $headers = 0;

    public function __construct()
    {
        // Prefer framework-provided constant if available.
        if (defined('Illuminate\\Http\\Request::HEADER_X_FORWARDED_ALL')) {
            $this->headers = \Illuminate\Http\Request::HEADER_X_FORWARDED_ALL;
            return;
        }

        if (defined('Symfony\\Component\\HttpFoundation\\Request::HEADER_X_FORWARDED_ALL')) {
            $this->headers = \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_ALL;
            return;
        }

        // Fallback: combine individual X-Forwarded constants if available.
        $flags = 0;
        if (defined('Symfony\\Component\\HttpFoundation\\Request::HEADER_X_FORWARDED_FOR')) {
            $flags |= \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_FOR;
        }
        if (defined('Symfony\\Component\\HttpFoundation\\Request::HEADER_X_FORWARDED_HOST')) {
            $flags |= \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_HOST;
        }
        if (defined('Symfony\\Component\\HttpFoundation\\Request::HEADER_X_FORWARDED_PORT')) {
            $flags |= \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_PORT;
        }
        if (defined('Symfony\\Component\\HttpFoundation\\Request::HEADER_X_FORWARDED_PROTO')) {
            $flags |= \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_PROTO;
        }

        $this->headers = $flags ?: 0;
    }
}
