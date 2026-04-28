<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Define o tempo de cache (em segundos) para os dados geográficos.
    | Padrão: 86400 (24 horas)
    |
    */
    'cache_ttl' => env('GEONAMES_CACHE_TTL', 86400),

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | Prefixo usado nas chaves de cache.
    |
    */
    'cache_prefix' => env('GEONAMES_CACHE_PREFIX', 'geonames'),

    /*
    |--------------------------------------------------------------------------
    | Default Language
    |--------------------------------------------------------------------------
    |
    | Idioma padrão para traduções.
    | Valores aceitos: 'en', 'pt-BR', 'es', 'fr', etc.
    |
    */
    'default_language' => env('GEONAMES_DEFAULT_LANGUAGE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | Configurações padrão de paginação.
    |
    */
    'pagination' => [
        'per_page' => env('GEONAMES_PER_PAGE', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Source Configuration
    |--------------------------------------------------------------------------
    |
    | URL para download dos dados geográficos.
    | Suporta: GitHub raw, GitHub Releases, CDN, ou servidor próprio.
    |
    | Exemplos:
    | - GitHub Raw: https://raw.githubusercontent.com/user/repo/branch/json/
    | - GitHub Releases: https://github.com/user/repo/releases/download/v1.0.0/
    | - CDN: https://cdn.exemplo.com/geonames/
    |
    */
    'data_source' => [
        'url' => env('GEONAMES_DATA_URL', 'https://raw.githubusercontent.com/risetechapps/geonames-database/main/json/'),
        'verify_ssl' => env('GEONAMES_VERIFY_SSL', true),
        'timeout' => env('GEONAMES_DOWNLOAD_TIMEOUT', 300),
        'branch' => env('GEONAMES_DATA_BRANCH', 'main'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Available Countries
    |--------------------------------------------------------------------------
    |
    | Lista de países disponíveis para instalação.
    | Use 'all' para instalar todos ou especifique códigos ISO3.
    |
    */
    'available_countries' => [
        'BRA', 'USA', 'CAN', 'ARG', 'CHL', 'COL', 'MEX',
        'GBR', 'DEU', 'FRA', 'ITA', 'ESP', 'PRT',
        'AUS', 'JPN', 'CHN', 'IND',
    ],
];
