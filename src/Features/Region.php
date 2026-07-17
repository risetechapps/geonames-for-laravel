<?php

namespace RiseTechApps\Geonames\Features;

use Illuminate\Support\Collection;

class Region
{
    public function __construct(protected array $data)
    {

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

        return new Countries()->all()
            ->filter(fn($country) => ($country['region'] ?? '') === $regionName)
            ->map(fn($country) => new Country($country['iso3']));
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
