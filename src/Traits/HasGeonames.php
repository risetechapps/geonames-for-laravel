<?php

namespace RiseTechApps\Geonames\Traits;

use RiseTechApps\Geonames\Features\City;
use RiseTechApps\Geonames\Features\Country;
use RiseTechApps\Geonames\Features\State;

/**
 * Trait para Models que possuem localização geográfica.
 *
 * @property string|null $country_code Código ISO2 do país
 * @property string|null $state_code Código ISO2 do estado
 * @property string|null $city_name Nome da cidade
 */
trait HasGeonames
{
    /**
     * Boot do trait - configura eventos.
     */
    public static function bootHasGeonames(): void
    {
        static::saving(function ($model) {
            // Normaliza códigos para uppercase
            if ($model->getAttribute('country_code')) {
                $model->setAttribute('country_code', strtoupper($model->getAttribute('country_code')));
            }
            if ($model->getAttribute('state_code')) {
                $model->setAttribute('state_code', strtoupper($model->getAttribute('state_code')));
            }
        });
    }

    /**
     * Inicializa o trait no modelo.
     */
    public function initializeHasGeonames(): void
    {
        $this->mergeCasts([
            'country_code' => 'string',
            'state_code' => 'string',
            'city_name' => 'string',
        ]);
    }

    /**
     * Retorna o objeto Country associado.
     *
     * @return Country|null
     */
    public function country(): ?Country
    {
        $code = $this->getAttribute('country_code');

        if (!$code) {
            return null;
        }

        try {
            return new Country($code);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Retorna o objeto State associado.
     *
     * @return State|null
     */
    public function state(): ?State
    {
        $country = $this->country();
        $stateCode = $this->getAttribute('state_code');

        if (!$country || !$stateCode) {
            return null;
        }

        try {
            $state = $country->state($stateCode);
            return $state->exists() ? $state : null;
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Retorna o objeto City associado.
     *
     * @return City|null
     */
    public function city(): ?City
    {
        $state = $this->state();
        $cityName = $this->getAttribute('city_name');

        if (!$state || !$cityName) {
            return null;
        }

        try {
            return $state->city($cityName);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Define a localização completa.
     *
     * @param string $countryCode ISO2 do país
     * @param string|null $stateCode ISO2 do estado
     * @param string|null $cityName Nome da cidade
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setLocation(string $countryCode, ?string $stateCode = null, ?string $cityName = null): static
    {
        // Valida país
        try {
            $country = new Country($countryCode);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException("País inválido: {$countryCode}");
        }

        // Valida estado se fornecido
        if ($stateCode) {
            try {
                $state = $country->state($stateCode);
                if (!$state->exists()) {
                    throw new \InvalidArgumentException("Estado inválido: {$stateCode}");
                }
            } catch (\Exception $e) {
                throw new \InvalidArgumentException("Estado inválido: {$stateCode}");
            }

            // Valida cidade se fornecido
            if ($cityName) {
                try {
                    $state->city($cityName);
                } catch (\Exception) {
                    throw new \InvalidArgumentException("Cidade inválida: {$cityName}");
                }
            }
        }

        $this->setAttribute('country_code', $countryCode);
        $this->setAttribute('state_code', $stateCode);
        $this->setAttribute('city_name', $cityName);

        return $this;
    }

    /**
     * Retorna o endereço completo formatado.
     *
     * @param string $format Formato do endereço (short, medium, long)
     * @return string
     */
    public function getFullAddress(string $format = 'medium'): string
    {
        $city = $this->getAttribute('city_name');
        $state = $this->state();
        $country = $this->country();

        return match ($format) {
            'short' => $this->formatShortAddress($city, $state),
            'long' => $this->formatLongAddress($city, $state, $country),
            default => $this->formatMediumAddress($city, $state, $country),
        };
    }

    /**
     * Formato curto: "São Paulo, SP"
     */
    private function formatShortAddress(?string $city, ?State $state): string
    {
        $parts = [];

        if ($city) {
            $parts[] = $city;
        }

        if ($state) {
            $parts[] = $state->getIso2();
        }

        return implode(', ', $parts);
    }

    /**
     * Formato médio: "São Paulo, São Paulo, Brazil"
     */
    private function formatMediumAddress(?string $city, ?State $state, ?Country $country): string
    {
        $parts = [];

        if ($city) {
            $parts[] = $city;
        }

        if ($state) {
            $parts[] = $state->getName();
        }

        if ($country) {
            $parts[] = $country->getName();
        }

        return implode(', ', $parts);
    }

    /**
     * Formato longo: "São Paulo, São Paulo, Brazil 🇧🇷 (GMT-3)"
     */
    private function formatLongAddress(?string $city, ?State $state, ?Country $country): string
    {
        $parts = [];

        if ($city) {
            $parts[] = $city;
        }

        if ($state) {
            $parts[] = $state->getName();
        }

        if ($country) {
            $address = implode(', ', $parts);
            $flag = $country->getFlag() ?? '';
            $timezone = $country->getTimezone() ?? '';

            return trim("{$address}, {$country->getName()} {$flag} ({$timezone})");
        }

        return implode(', ', $parts);
    }

    /**
     * Retorna o fuso horário do local.
     *
     * @return string|null
     */
    public function getTimezone(): ?string
    {
        // Prioridade: estado > país
        $state = $this->state();
        if ($state && $state->getTimezone()) {
            return $state->getTimezone();
        }

        $country = $this->country();
        if ($country) {
            return $country->getTimezone();
        }

        return null;
    }

    /**
     * Verifica se a localização está completa.
     *
     * @return bool
     */
    public function hasCompleteLocation(): bool
    {
        return $this->getAttribute('country_code')
            && $this->getAttribute('state_code')
            && $this->getAttribute('city_name');
    }

    /**
     * Verifica se tem pelo menos país definido.
     *
     * @return bool
     */
    public function hasLocation(): bool
    {
        return (bool) $this->getAttribute('country_code');
    }

    /**
     * Limpa a localização.
     *
     * @return $this
     */
    public function clearLocation(): static
    {
        $this->setAttribute('country_code', null);
        $this->setAttribute('state_code', null);
        $this->setAttribute('city_name', null);

        return $this;
    }

    /**
     * Scope: Filtra por país.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $countryCode
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereCountry($query, string $countryCode): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('country_code', strtoupper($countryCode));
    }

    /**
     * Scope: Filtra por estado.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $stateCode
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereState($query, string $stateCode): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('state_code', strtoupper($stateCode));
    }

    /**
     * Scope: Filtra por cidade.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $cityName
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereCity($query, string $cityName): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('city_name', $cityName);
    }

    /**
     * Scope: Filtra por região.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $regionName
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereRegion($query, string $regionName): \Illuminate\Database\Eloquent\Builder
    {
        // Obtém todos os países da região
        $region = (new \RiseTechApps\Geonames\Features\Regions())->findByName($regionName);

        if (!$region) {
            return $query->whereRaw('1 = 0'); // Nenhum resultado
        }

        $countryCodes = $region->countries()
            ->map(fn($c) => $c->getIso2())
            ->toArray();

        return $query->whereIn('country_code', $countryCodes);
    }

    /**
     * Retorna coordenadas da cidade (se disponível).
     *
     * @return array{latitude: float|null, longitude: float|null}|null
     */
    public function getCoordinates(): ?array
    {
        $city = $this->city();

        if (!$city) {
            return null;
        }

        $lat = $city->getLatitude();
        $lng = $city->getLongitude();

        if (!$lat || !$lng) {
            return null;
        }

        return [
            'latitude' => (float) $lat,
            'longitude' => (float) $lng,
        ];
    }

    /**
     * Retorna a distância em linha reta até outro modelo.
     *
     * @param static $other
     * @param string $unit 'km' ou 'miles'
     * @return float|null
     */
    public function distanceTo($other, string $unit = 'km'): ?float
    {
        $coords1 = $this->getCoordinates();
        $coords2 = $other->getCoordinates();

        if (!$coords1 || !$coords2) {
            return null;
        }

        // Fórmula de Haversine
        $lat1 = deg2rad($coords1['latitude']);
        $lat2 = deg2rad($coords2['latitude']);
        $deltaLat = deg2rad($coords2['latitude'] - $coords1['latitude']);
        $deltaLng = deg2rad($coords2['longitude'] - $coords1['longitude']);

        $a = sin($deltaLat / 2) ** 2 +
             cos($lat1) * cos($lat2) * sin($deltaLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $earthRadius = $unit === 'miles' ? 3959 : 6371; // miles ou km

        return $earthRadius * $c;
    }
}
