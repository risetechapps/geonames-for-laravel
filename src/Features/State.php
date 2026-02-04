<?php

namespace RiseTechApps\Geonames\Features;

use Exception;
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
        $this->data = $this->find($stateIdentifier);
    }


    protected function find(string $stateIdentifier)
    {

        $states = (new States($this->country))->all();

        return $states->first(function ($item) use ($stateIdentifier) {
            return strtoupper($item['iso2']) === $stateIdentifier || strtoupper($item['name']) === $stateIdentifier;
        });
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

    public function cities(): Cities
    {
        return new Cities($this, $this->country);
    }

    /**
     * @throws Exception
     */
    public function city(string $cityIdentifier): City
    {
        $cities = $this->cities();

        $response = $cities->all()->first(function ($item) use ($cityIdentifier) {
            return strtoupper($item['name']) === strtoupper($cityIdentifier);
        });

        if($response){
            return new City((array) $response);
        }

        throw new Exception("City not found");
    }
}
