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
     * @return array|null
     */
    public function find(int $id): ?array
    {
        return $this->all()->firstWhere('id', $id);
    }

    /**
     * Carrega os dados do arquivo JSON local.
     * * @return Collection
     *
     * @throws FileNotFoundException
     */
    protected function loadFromJson(): Collection
    {
        // Caminho relativo ao diretório do pacote
        $path = __DIR__ . '/../../resources/json/regions.json';

        if (!File::exists($path)) {
            return collect([]);
        }

        $data = json_decode(File::get($path), true);

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
