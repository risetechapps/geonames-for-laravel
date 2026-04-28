<?php

namespace RiseTechApps\Geonames\Features;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class States
{
    protected Country $country;

    /**
     * Agora o construtor exige uma instância da classe Country.
     */
    public function __construct(Country $country)
    {
        $this->country = $country;
    }

    /**
     * Retorna todos os estados do país injetado.
     */
    public function all(): Collection
    {
        $iso3 = strtoupper($this->country->getIso3());
        $cacheKey = "geonames.states.{$iso3}";

        $data = Cache::remember($cacheKey, 86400, function () use ($iso3) {
            $path = resource_path("geonames/json/{$iso3}/index.json");

            if (!File::exists($path)) {
                throw new \RuntimeException(
                    "States data not found for {$iso3} at: {$path}. " .
                    "Please run: php artisan geonames:install-data --countries={$iso3}"
                );
            }

            $data = json_decode(File::get($path), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException("Invalid JSON in states file for {$iso3}: " . json_last_error_msg());
            }

            return $data;
        });

        return collect($data);
    }

    /**
     * Busca um estado específico dentro do país injetado.
     * @throws Exception
     */
    public function find(string $code): State
    {
        $code = strtoupper($code);

        $response = $this->all()->first(function ($item) use ($code) {
            return strtoupper($item['iso2']) === $code || strtoupper($item['name']) === $code;
        });

        if($response){
            return new State($response['iso2'], new Country($response['country_iso3']));
        }

        throw new Exception("State not found");
    }

    /**
     * Busca estados por nome parcial (fuzzy search).
     *
     * @param string $search Termo de busca
     * @param int $limit Limite de resultados (0 = todos)
     * @return Collection<State>
     */
    public function search(string $search, int $limit = 0): Collection
    {
        $search = strtoupper($search);

        $results = $this->all()->filter(function ($item) use ($search) {
            return str_contains(strtoupper($item['name']), $search)
                || str_contains(strtoupper($item['iso2']), $search);
        })->map(function ($item) {
            return new State($item['iso2'], new Country($item['country_iso3']));
        });

        return $limit > 0 ? $results->take($limit) : $results;
    }

    /**
     * Retorna estados paginados.
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
            return new State($item['iso2'], new Country($item['country_iso3']));
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
