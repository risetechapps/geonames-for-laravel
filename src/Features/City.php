<?php

namespace RiseTechApps\Geonames\Features;

class City
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
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
}
