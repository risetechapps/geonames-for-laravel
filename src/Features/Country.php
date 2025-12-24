<?php

namespace RiseTechApps\Geonames\Features;

use Exception;

class Country
{
    protected string $country;
    protected array $data = [];

    /**
     * @throws Exception
     */
    public function __construct(string $country)
    {
        $this->country = $country;
        $this->find($country);
    }

    /**
     * @throws Exception
     */
    protected function find(string $country): void
    {
        $countries = (new Countries())->all();

        $result = $countries->first(function ($item) use ($country) {
            return strtoupper($item['iso2']) === strtoupper($country) ||
                strtoupper($item['name']) === strtoupper($country)
                || strtoupper($item['iso3']) === strtoupper($country);
        });

        if ($result) {
            $this->data = $result;
        } else {
            throw new Exception('Country not found');
        }
    }

    public function getId(): int
    {
        return (int)($this->data['id'] ?? 0);
    }

    public function getName(): ?string
    {
        return $this->data['name'] ?? null;
    }

    public function getIso2(): ?string
    {
        return $this->data['iso2'] ?? null;
    }

    public function getIso3(): ?string
    {
        return $this->data['iso3'] ?? null;
    }

    public function getPhoneCode(): ?string
    {
        return $this->data['phonecode'] ?? null;
    }

    public function getCapital(): ?string
    {
        return $this->data['capital'] ?? null;
    }

    public function getCurrency(): ?string
    {
        return $this->data['currency'] ?? null;
    }

    public function getCurrencySymbol(): ?string
    {
        return $this->data['currency_symbol'] ?? null;
    }

    public function getEmoji(): ?string
    {
        return $this->data['emoji'] ?? null;
    }

    public function getNative(): ?string
    {
        return $this->data['native'] ?? null;
    }

    public function getTimezone(): ?string
    {
        return $this->data['timezones'][0]['zoneName'] ?? null;
    }

    public function getTranslation(string $lang): ?string
    {
        return $this->data['translations'][$lang] ?? null;
    }

    public function getRegion()
    {
        return $this->data['region'] ?? null;
    }

    public function getLanguage(): string
    {
        return $this->data['language_code'] ?? 'en-US';
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function states(): States
    {
        return new States($this);
    }

    public function state(string $code): ?State
    {
        return (new State($code, $this));
    }

}
