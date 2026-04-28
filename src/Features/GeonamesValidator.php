<?php

namespace RiseTechApps\Geonames\Features;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class GeonamesValidator
{
    /**
     * Verifica se um país existe (por ISO2, ISO3 ou nome).
     *
     * @param string $country
     * @return bool
     */
    public static function isValidCountry(string $country): bool
    {
        try {
            $countries = (new Countries())->all();

            return $countries->contains(function ($item) use ($country) {
                return strtoupper($item['iso2']) === strtoupper($country)
                    || strtoupper($item['name']) === strtoupper($country)
                    || strtoupper($item['iso3']) === strtoupper($country);
            });
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Verifica se um estado existe em um país.
     *
     * @param string $state
     * @param string $country
     * @return bool
     */
    public static function isValidState(string $state, string $country): bool
    {
        try {
            if (!self::isValidCountry($country)) {
                return false;
            }

            $countryObj = new Country($country);
            $states = $countryObj->states()->all();

            return $states->contains(function ($item) use ($state) {
                return strtoupper($item['iso2']) === strtoupper($state)
                    || strtoupper($item['name']) === strtoupper($state);
            });
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Verifica se uma cidade existe em um estado/país.
     *
     * @param string $city
     * @param string $state
     * @param string $country
     * @return bool
     */
    public static function isValidCity(string $city, string $state, string $country): bool
    {
        try {
            if (!self::isValidState($state, $country)) {
                return false;
            }

            $countryObj = new Country($country);
            $stateObj = $countryObj->state($state);

            if (!$stateObj->exists()) {
                return false;
            }

            $cities = $stateObj->cities()->all();

            return $cities->contains(function ($item) use ($city) {
                return strtoupper($item['name']) === strtoupper($city);
            });
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Valida um código ISO2 de país.
     *
     * @param string $iso2
     * @return bool
     */
    public static function isValidCountryIso2(string $iso2): bool
    {
        if (strlen($iso2) !== 2) {
            return false;
        }

        try {
            $countries = (new Countries())->all();

            return $countries->contains(function ($item) use ($iso2) {
                return strtoupper($item['iso2']) === strtoupper($iso2);
            });
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Valida um código ISO3 de país.
     *
     * @param string $iso3
     * @return bool
     */
    public static function isValidCountryIso3(string $iso3): bool
    {
        if (strlen($iso3) !== 3) {
            return false;
        }

        try {
            $countries = (new Countries())->all();

            return $countries->contains(function ($item) use ($iso3) {
                return strtoupper($item['iso3']) === strtoupper($iso3);
            });
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Retorna uma lista de erros de validação para endereço completo.
     *
     * @param string|null $country
     * @param string|null $state
     * @param string|null $city
     * @return array<string>
     */
    public static function validateAddress(?string $country, ?string $state = null, ?string $city = null): array
    {
        $errors = [];

        if ($country && !self::isValidCountry($country)) {
            $errors[] = "País inválido: {$country}";
        }

        if ($state && $country) {
            if (!self::isValidState($state, $country)) {
                $errors[] = "Estado inválido: {$state} para o país {$country}";
            } elseif ($city && !self::isValidCity($city, $state, $country)) {
                $errors[] = "Cidade inválida: {$city} para o estado {$state}";
            }
        }

        return $errors;
    }
}
