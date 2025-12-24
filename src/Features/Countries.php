<?php

namespace RiseTechApps\Geonames\Features;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Collection;
class Countries
{
    protected int $cacheTtl = 86400; // 24 horas
    protected string $cacheKey = 'geonames.countries';

    /**
     * Retorna todos os países.
     */
    public function all(): Collection
    {
        return Cache::remember($this->cacheKey, $this->cacheTtl, function () {
            return $this->loadFromJson();
        });
    }

    /**
     * Carrega os dados do JSON.
     */
    protected function loadFromJson(): Collection
    {
        $path = __DIR__ . '/../../resources/json/countries.json';

        if (!File::exists($path)) {
            return collect([]);
        }

        return collect(json_decode(File::get($path), true));
    }

    /**
     * Limpa o cache.
     */
    public function flushCache(): bool
    {
        return Cache::forget($this->cacheKey);
    }
}
