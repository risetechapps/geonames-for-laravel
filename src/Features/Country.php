<?php

namespace RiseTechApps\Geonames\Features;

use ArrayAccess;
use Exception;
use JsonSerializable;

class Country implements ArrayAccess, JsonSerializable
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

    public function getCurrencyName(): ?string
    {
        return $this->data['currency_name'] ?? null;
    }

    public function getCurrencySymbol(): ?string
    {
        return $this->data['currency_symbol'] ?? null;
    }

    public function getTld(): ?string
    {
        return $this->data['tld'] ?? null;
    }

    public function getEmoji(): ?string
    {
        return $this->data['emoji'] ?? null;
    }

    public function getEmojiU(): ?string
    {
        return $this->data['emojiU'] ?? null;
    }

    public function getNative(): ?string
    {
        return $this->data['native'] ?? null;
    }

    /**
     * Retorna o fuso horário principal (primeiro da lista).
     */
    public function getTimezone(): ?string
    {
        return $this->data['timezones'][0]['zoneName'] ?? null;
    }

    /**
     * Retorna todos os fusos horários do país.
     *
     * @return array<array{zoneName: string, gmtOffset: int, gmtOffsetName: string, abbreviation: string, tzName: string}>
     */
    public function getTimezones(): array
    {
        return $this->data['timezones'] ?? [];
    }

    public function getLatitude(): ?string
    {
        return $this->data['latitude'] ?? null;
    }

    public function getLongitude(): ?string
    {
        return $this->data['longitude'] ?? null;
    }

    /**
     * Retorna as coordenadas do país.
     *
     * @return array{latitude: string|null, longitude: string|null}
     */
    public function getCoordinates(): array
    {
        return [
            'latitude' => $this->getLatitude(),
            'longitude' => $this->getLongitude(),
        ];
    }

    /**
     * Retorna documentos de identificação do país.
     *
     * @return array<array{type: string, name: string, person_type: string, format: string, regex: string, length: int, numeric_only: bool, example: string}>
     */
    public function getDocuments(): array
    {
        return $this->data['documents'] ?? [];
    }

    /**
     * Busca um tipo específico de documento.
     *
     * @param string $type Tipo do documento (ex: 'Tazkira', 'CPF', 'Passport')
     * @return array|null
     */
    public function getDocument(string $type): ?array
    {
        $documents = $this->getDocuments();

        foreach ($documents as $document) {
            if (strtoupper($document['type']) === strtoupper($type)) {
                return $document;
            }
        }

        return null;
    }

    public function getTranslation(string $lang): ?string
    {
        return $this->data['translations'][$lang] ?? null;
    }

    /**
     * Retorna o nome do país em um idioma específico ou no idioma padrão.
     *
     * @param string|null $lang Código do idioma (ex: 'pt-BR', 'es', 'en')
     * @return string|null
     */
    public function getNameLocalized(?string $lang = null): ?string
    {
        $lang = $lang ?? config('geonames.default_language', 'en');

        return $this->getTranslation($lang) ?? $this->getName();
    }

    /**
     * Formata um número de telefone segundo o formato do país.
     *
     * @param string $phone Número de telefone (apenas dígitos)
     * @return string|null
     */
    public function formatPhone(string $phone): ?string
    {
        $formats = $this->data['telephone_format'] ?? [];

        if (empty($formats)) {
            return $phone;
        }

        // Remove caracteres não numéricos
        $digits = preg_replace('/\D/', '', $phone);

        // Usa o primeiro formato disponível
        $format = $formats[0];

        // Substitui # pelos dígitos
        $result = '';
        $digitIndex = 0;
        for ($i = 0; $i < strlen($format); $i++) {
            if ($format[$i] === '#' && $digitIndex < strlen($digits)) {
                $result .= $digits[$digitIndex];
                $digitIndex++;
            } else {
                $result .= $format[$i];
            }
        }

        return $result;
    }

    /**
     * Valida um número de telefone segundo o regex do país.
     *
     * @param string $phone
     * @return bool
     */
    public function isValidPhone(string $phone): bool
    {
        $regexes = $this->data['telephone_regex'] ?? [];

        if (empty($regexes)) {
            return true;
        }

        foreach ($regexes as $regex) {
            if (preg_match('/' . $regex . '/', $phone)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Formata um CEP/ZipCode segundo o formato do país.
     *
     * @param string $zipCode
     * @return string|null
     */
    public function formatZipCode(string $zipCode): ?string
    {
        $formats = $this->data['zip_code_format'] ?? [];

        if (empty($formats)) {
            return $zipCode;
        }

        $digits = preg_replace('/\D/', '', $zipCode);
        $format = $formats[0];

        $result = '';
        $digitIndex = 0;
        for ($i = 0; $i < strlen($format); $i++) {
            if ($format[$i] === '#' && $digitIndex < strlen($digits)) {
                $result .= $digits[$digitIndex];
                $digitIndex++;
            } else {
                $result .= $format[$i];
            }
        }

        return $result;
    }

    /**
     * Valida um CEP/ZipCode segundo o regex do país.
     *
     * @param string $zipCode
     * @return bool
     */
    public function isValidZipCode(string $zipCode): bool
    {
        $regexes = $this->data['zip_code_regex'] ?? [];

        if (empty($regexes)) {
            return true;
        }

        foreach ($regexes as $regex) {
            if (preg_match('/' . $regex . '/', $zipCode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Formata um número de celular segundo o formato do país.
     *
     * @param string $cellPhone
     * @return string|null
     */
    public function formatCellPhone(string $cellPhone): ?string
    {
        $formats = $this->data['cellphone_format'] ?? [];

        if (empty($formats)) {
            return $cellPhone;
        }

        $digits = preg_replace('/\D/', '', $cellPhone);
        $format = $formats[0];

        $result = '';
        $digitIndex = 0;
        for ($i = 0; $i < strlen($format); $i++) {
            if ($format[$i] === '#' && $digitIndex < strlen($digits)) {
                $result .= $digits[$digitIndex];
                $digitIndex++;
            } else {
                $result .= $format[$i];
            }
        }

        return $result;
    }

    /**
     * Retorna o emoji da bandeira do país (alias de getEmoji).
     *
     * @return string|null
     */
    public function getFlag(): ?string
    {
        return $this->getEmoji();
    }

    public function getRegion(): ?string
    {
        return $this->data['region'] ?? null;
    }

    public function getSubregion(): ?string
    {
        return $this->data['subregion'] ?? null;
    }

    public function getNationality(): ?string
    {
        return $this->data['nationality'] ?? null;
    }

    public function getLanguage(): string
    {
        return $this->data['language_code'] ?? 'en-US';
    }

    /**
     * Retorna lista de idiomas suportados para tradução.
     *
     * @return array<string>
     */
    public function getAvailableTranslations(): array
    {
        return array_keys($this->data['translations'] ?? []);
    }

    public function exists(): bool
    {
        return !empty($this->data);
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
        throw new \RuntimeException('Cannot modify read-only country data');
    }

    public function offsetUnset($offset): void
    {
        throw new \RuntimeException('Cannot modify read-only country data');
    }

    // JsonSerializable implementation

    public function jsonSerialize(): array
    {
        return $this->data;
    }
}
