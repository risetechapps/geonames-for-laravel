<?php

namespace RiseTechApps\Geonames\Features;

use ArrayAccess;
use JsonSerializable;

class City implements ArrayAccess, JsonSerializable
{
    public function __construct(protected array $data)
    {
    }

    public function getName(): ?string
    {
        return $this->data['name'] ?? null;
    }

    public function getLatitude(): ?string
    {
        return $this->data['latitude'] ?? null;
    }

    public function getLongitude(): ?string
    {
        return $this->data['longitude'] ?? null;
    }

    public function toArray(): array
    {
        return $this->data;
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
        throw new \RuntimeException('Cannot modify read-only city data');
    }

    public function offsetUnset($offset): void
    {
        throw new \RuntimeException('Cannot modify read-only city data');
    }

    // JsonSerializable implementation

    public function jsonSerialize(): array
    {
        return $this->data;
    }
}
