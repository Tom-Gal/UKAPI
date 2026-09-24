<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies;

final class TrustConfiguredProxies extends TrustProxies
{
    /** @return string|null */
    protected function proxies()
    {
        $proxies = config('app.trusted_proxies');

        return is_string($proxies) && trim($proxies) !== '' ? $proxies : null;
    }
}
