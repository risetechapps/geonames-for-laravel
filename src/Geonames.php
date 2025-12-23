<?php

namespace RiseTechApps\Geonames;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class Geonames
{
    protected array $data = [];
    protected ?array $selected = null;
    protected string $locale;
    protected string $cacheKey = 'pkg_countries_enhanced_list';

    public function __construct(?string $code = null)
    {

        $this->locale = app()->getLocale();
        $this->loadData();

        if ($code) {
            $this->find($code);
        }
    }

    /**
     * Carrega os dados e indexa por ISO2 para acesso instantâneo
     */
    protected function loadData(): void
    {
        $this->data = Cache::remember($this->cacheKey, now()->addDays(30), function () {

            $url = 'https://raw.githubusercontent.com/.../countries.json';
            $json = Http::get($url)->json();

            return collect($json)->keyBy('iso2')->toArray();
        });
    }

    /**
     * Busca país por ISO2 ou ISO3
     */
    public function find(string $code): self
    {
        $code = strtoupper($code);

        if (isset($this->data[$code])) {
            $this->selected = $this->data[$code];
        } else {
            // Busca secundária por ISO3
            $this->selected = collect($this->data)->firstWhere('iso3', $code);
        }

        return $this;
    }

    /**
     * Altera o locale de tradução manualmente
     */
    public function setLocale(string $locale): self
    {
        $this->locale = $locale;
        return $this;
    }

    // --- Getters dos dados do JSON ---

    public function getName(): ?string
    {
        if (!$this->selected) return null;
        // Retorna tradução, ou o nome original (Inglês) caso não exista a tradução
        return $this->selected['translations'][$this->locale] ?? $this->selected['name'];
    }

    public function getId(): ?int { return $this->selected['id'] ?? null; }
    public function getIso2(): ?string { return $this->selected['iso2'] ?? null; }
    public function getIso3(): ?string { return $this->selected['iso3'] ?? null; }
    public function getPhoneCode(): ?string { return $this->selected['phone_code'] ?? null; }
    public function getCapital(): ?string { return $this->selected['capital'] ?? null; }
    public function getNative(): ?string { return $this->selected['native'] ?? null; }
    public function getRegion(): ?string { return $this->selected['region'] ?? null; }
    public function getSubregion(): ?string { return $this->selected['subregion'] ?? null; }
    public function getNationality(): ?string { return $this->selected['nationality'] ?? null; }
    public function getLanguage(): ?string { return $this->selected['language'] ?? null; }

    // --- Métodos de Validação com Regex ---

    public function validateZip(string $value): bool
    {
        return $this->validate($this->selected['zip_code_regex'] ?? null, $value);
    }

    public function validateCell(string $value): bool
    {
        return $this->validate($this->selected['cellphone_regex'] ?? null, $value);
    }

    public function validatePhone(string $value): bool
    {
        return $this->validate($this->selected['telephone_regex'] ?? null, $value);
    }

    protected function validate(?string $regex, string $value): bool
    {
        if (empty($regex)) return true; // Se não há regex, não bloqueia
        return (bool) preg_match($regex, $value);
    }

    /**
     * Retorna todos os países como uma Coleção Laravel
     */
    public function all(): Collection
    {
        return collect($this->data);
    }
}
