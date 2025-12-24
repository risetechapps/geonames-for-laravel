<?php

namespace RiseTechApps\Geonames\Features;

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
        return $this->all();
    }

    /**
     * Retorna todos os estados do país injetado.
     */
    public function all(): Collection
    {
        $iso3 = strtoupper($this->country->getIso3());
        $cacheKey = "geonames.states.{$iso3}";

        $data = Cache::remember($cacheKey, 86400, function () use ($iso3) {
            $path = __DIR__ . "/../../resources/json/{$iso3}/index.json";

            if (!File::exists($path)) {
                return [];
            }

            return json_decode(File::get($path), true);
        });

        return collect($data);
    }

    /**
     * Busca um estado específico dentro do país injetado.
     */
    public function find(string $code): ?array
    {
        $code = strtoupper($code);

        return $this->all()->first(function ($item) use ($code) {
            return strtoupper($item['iso2']) === $code || strtoupper($item['name']) === $code;
        });
    }
}
