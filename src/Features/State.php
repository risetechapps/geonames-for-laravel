<?php

namespace RiseTechApps\Geonames\Features;

use ArrayAccess;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use JsonSerializable;

class State implements ArrayAccess, JsonSerializable
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

    public function getId(): ?int
    {
        return $this->data['id'] ?? null;
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

    public function getCountryName(): ?string
    {
        return $this->data['country'] ?? null;
    }

    public function getCountryIso2(): ?string
    {
        return $this->data['country_iso2'] ?? null;
    }

    public function getCountryIso3(): ?string
    {
        return $this->data['country_iso3'] ?? null;
    }

    public function getCountryNative(): ?string
    {
        return $this->data['country_native'] ?? null;
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

    // ArrayAccess implementation

    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        throw new \RuntimeException('Cannot modify read-only state data');
    }

    public function offsetUnset($offset): void
    {
        throw new \RuntimeException('Cannot modify read-only state data');
    }

    // JsonSerializable implementation

    public function jsonSerialize(): array
    {
        return $this->data ?? [];
    }
}
