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
            $path = __DIR__ . "/../../resources/json/{$countryIso3}/{$stateIso2}/index.json";

            if (!File::exists($path)) {
                return [];
            }

            return json_decode(File::get($path), true);
        });

        return collect($citiesData);
    }
}
