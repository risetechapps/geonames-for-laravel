<?php

namespace RiseTechApps\Geonames\Features;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Collection;

class Regions
{
    /**
     * O tempo de cache em segundos (ex: 1 dia).
     */
    private int $cacheTtl = 86400;

    /**
     * Chave do cache para as regiões.
     */
    private string $cacheKey = 'geonames.regions';

    /**
     * Retorna todas as regiões.
     * * @return Collection
     */
    public function all(): Collection
    {
        return Cache::remember($this->cacheKey, $this->cacheTtl, function () {
            return $this->loadFromJson();
        });
    }

    /**
     * Busca uma região específica pelo ID.
     * * @param int $id
     * @return Region|null
     */
    public function find(int $id): ?Region
    {
        $data = $this->all()->firstWhere('id', $id);

        return $data ? new Region($data) : null;
    }

    /**
     * Busca uma região específica pelo nome.
     *
     * @param string $name
     * @return Region|null
     */
    public function findByName(string $name): ?Region
    {
        $data = $this->all()->first(function ($item) use ($name) {
            return strtoupper($item['name']) === strtoupper($name);
        });

        return $data ? new Region($data) : null;
    }

    /**
     * Carrega os dados do arquivo JSON local.
     * * @return Collection
     *
     * @throws FileNotFoundException
     */
    protected function loadFromJson(): Collection
    {
        $path = resource_path('geonames/json/regions.json');

        if (!File::exists($path)) {
            throw new \RuntimeException(
                "Regions data not found at: {$path}. " .
                "Please run: php artisan geonames:install-data"
            );
        }

        $data = json_decode(File::get($path), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON in regions file: ' . json_last_error_msg());
        }

        return collect($data);
    }

    /**
     * Limpa o cache das regiões (útil após updates).
     */
    public function flushCache(): bool
    {
        return Cache::forget($this->cacheKey);
    }
}
