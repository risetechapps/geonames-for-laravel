<?php

namespace RiseTechApps\Geonames\Features;

use Illuminate\Support\Collection;

class Region
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getId(): int
    {
        return (int)($this->data['id'] ?? 0);
    }

    public function getName(): ?string
    {
        return $this->data['name'] ?? null;
    }

    /**
     * Retorna todos os países desta região.
     *
     * @return Collection<Country>
     */
    public function countries(): Collection
    {
        $regionName = $this->getName();

        return (new Countries())->all()
            ->filter(function ($country) use ($regionName) {
                return ($country['region'] ?? '') === $regionName;
            })
            ->map(function ($country) {
                return new Country($country['iso3']);
            });
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
