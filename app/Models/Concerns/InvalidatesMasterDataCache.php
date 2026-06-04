<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Cache;

trait InvalidatesMasterDataCache
{
    protected static function bootInvalidatesMasterDataCache(): void
    {
        $forget = static function (): void {
            $keys = defined(static::class.'::MASTER_DATA_CACHE_KEYS')
                ? constant(static::class.'::MASTER_DATA_CACHE_KEYS')
                : [static::MASTER_DATA_CACHE_KEY];

            foreach ((array) $keys as $key) {
                Cache::forget($key);
            }
        };

        static::saved($forget);
        static::deleted($forget);
    }
}
