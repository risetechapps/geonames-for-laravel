<?php

namespace RiseTechApps\Geonames\Features;

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
     * Busca um país pelo ISO2 (ex: BR).
     */
    public function findByIso2(string $iso2): ?array
    {
        return $this->all()->firstWhere('iso2', strtoupper($iso2));
    }

    /**
     * Filtra países por Região (Ex: ASIA, EUROPE).
     */
    public function byRegion(string $regionName): Collection
    {
        return $this->all()->where('region', strtoupper($regionName));
    }

    /**
     * Filtra países por Sub-região (Ex: SOUTHERN ASIA).
     */
    public function bySubregion(string $subregionName): Collection
    {
        return $this->all()->where('subregion', strtoupper($subregionName));
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
