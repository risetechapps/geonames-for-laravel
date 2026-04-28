<?php

namespace RiseTechApps\Geonames\Features;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class Cities
{
    protected Country $country;
    protected State $state;

    public function __construct(State $state, Country $country)
    {
        $this->country = $country;
        $this->state = $state;
    }

    public function all(): Collection
    {
        $countryIso3 = strtoupper($this->country->getIso3());
        $stateIso2   = strtoupper($this->state->getIso2());

        $cacheKey = "geonames.cities.{$countryIso3}.{$stateIso2}";

        $citiesData = Cache::remember($cacheKey, 86400, function () use ($countryIso3, $stateIso2) {
            $path = resource_path("geonames/json/{$countryIso3}/{$stateIso2}/index.json");

            if (!File::exists($path)) {
                throw new \RuntimeException(
                    "Cities data not found for {$countryIso3}/{$stateIso2} at: {$path}. " .
                    "Please run: php artisan geonames:install-data --countries={$countryIso3}"
                );
            }

            $data = json_decode(File::get($path), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException("Invalid JSON in cities file for {$countryIso3}/{$stateIso2}: " . json_last_error_msg());
            }

            return $data;
        });

        return collect($citiesData);
    }

    /**
     * Busca cidades por nome parcial (fuzzy search).
     *
     * @param string $search Termo de busca
     * @param int $limit Limite de resultados (0 = todos)
     * @return Collection<City>
     */
    public function search(string $search, int $limit = 0): Collection
    {
        $search = strtoupper($search);

        $results = $this->all()->filter(function ($item) use ($search) {
            return str_contains(strtoupper($item['name']), $search);
        })->map(function ($item) {
            return new City($item);
        });

        return $limit > 0 ? $results->take($limit) : $results;
    }

    /**
     * Retorna cidades paginadas.
     *
     * @param int $perPage Itens por página
     * @param int $page Número da página (começa em 1)
     * @return array{data: Collection, total: int, per_page: int, current_page: int, last_page: int}
     */
    public function paginate(int $perPage = 50, int $page = 1): array
    {
        $all = $this->all();
        $total = $all->count();
        $lastPage = (int) ceil($total / $perPage);

        $items = $all->forPage($page, $perPage)->map(function ($item) {
            return new City($item);
        });

        return [
            'data' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $lastPage,
        ];
    }
}
