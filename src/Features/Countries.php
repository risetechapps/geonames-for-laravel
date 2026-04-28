<?php

namespace RiseTechApps\Geonames\Features;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Traversable;

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
        $path = resource_path('geonames/json/countries.json');

        if (!File::exists($path)) {
            throw new \RuntimeException(
                "Countries data not found at: {$path}. " .
                "Please run: php artisan geonames:install-data"
            );
        }

        $data = json_decode(File::get($path), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON in countries file: ' . json_last_error_msg());
        }

        return collect($data);
    }

    /**
     * @throws Exception
     */
    public function find(string $country): Country
    {

        $response = $this->all()->filter(function ($c) use ($country) {
            return $c['name'] == $country
                || $c['iso3'] == $country
                || $c['iso2'] == $country;
        })->first();

        if($response){
            return new Country($response['iso3']);
        }

        throw new Exception('Country not found');
    }

    /**
     * Limpa o cache.
     */
    public function flushCache(): bool
    {
        return Cache::forget($this->cacheKey);
    }
}
