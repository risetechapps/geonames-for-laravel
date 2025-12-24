<?php

namespace RiseTechApps\Geonames\Features;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class State
{
    protected ?array $data = null;
    protected Country $country;

    public function __construct(string $stateIdentifier, Country $country)
    {
        $this->country = $country;
        $this->data = (new States($country))->find($stateIdentifier);
    }

    public function exists(): bool
    {
        return !is_null($this->data);
    }

    public function getName(): ?string
    {
        return $this->data['name'] ?? null;
    }

    public function getIso2(): ?string
    {
        return $this->data['iso2'] ?? null;
    }

    public function getTimezone(): ?string
    {
        return $this->data['timezone'] ?? null;
    }

    public function getCountry(): Country
    {
        return $this->country;
    }

    public function getCities(): Collection
    {
        if (!$this->exists()) return collect([]);

        $countryIso3 = strtoupper($this->country->getIso3());
        $stateIso2   = strtoupper($this->getIso2());

        $cacheKey = "geonames.cities.{$countryIso3}.{$stateIso2}";

        $citiesData = Cache::remember($cacheKey, 86400, function () use ($countryIso3, $stateIso2) {
            // Caminho exato: BRA/SP/index.json
            $path = __DIR__ . "/../../resources/json/{$countryIso3}/{$stateIso2}/index.json";

            if (!File::exists($path)) {
                return [];
            }

            return json_decode(File::get($path), true);
        });

        return collect($citiesData)->map(fn($city) => new City($city));
    }
}
