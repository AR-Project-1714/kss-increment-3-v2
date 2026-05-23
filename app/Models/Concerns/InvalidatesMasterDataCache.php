<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Cache;

trait InvalidatesMasterDataCache
{
    protected static function bootInvalidatesMasterDataCache(): void
    {
        $forget = static fn () => Cache::forget(static::MASTER_DATA_CACHE_KEY);

        static::saved($forget);
        static::deleted($forget);
    }
}
