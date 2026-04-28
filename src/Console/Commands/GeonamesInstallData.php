<?php

namespace RiseTechApps\Geonames\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class GeonamesInstallData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geonames:install-data
                            {--countries=all : Países para instalar (ISO3 separados por vírgula ou "all")}
                            {--force : Sobrescreve dados existentes}
                            {--dry-run : Simula sem baixar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Baixa e instala os dados geográficos do Geonames';

    /**
     * URL base para download dos dados.
     */
    protected string $baseUrl;

    /**
     * Diretório de destino dos dados.
     */
    protected string $dataPath;

    /**
     * Estatísticas de download.
     */
    protected array $stats = [
        'downloaded' => 0,
        'errors' => 0,
        'skipped' => 0,
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Aumenta limite de memória para o comando
        ini_set('memory_limit', '512M');

        // Desabilita Telescope durante o download (evita erro de memória)
        if (class_exists(\Laravel\Telescope\Telescope::class)) {
            \Laravel\Telescope\Telescope::stopRecording();
        }

        $this->baseUrl = rtrim(config('geonames.data_source.url',
            'https://raw.githubusercontent.com/risetechapps/geonames-database/main/json/'), '/');

        $this->dataPath = resource_path('geonames/json');

        $this->info('🌍 Geonames - Instalação de Dados');
        $this->info(str_repeat('=', 60));
        $this->info('Fonte: ' . $this->baseUrl);
        $this->info('Destino: ' . $this->dataPath);
        $this->info('');

        // Verifica dry-run
        if ($this->option('dry-run')) {
            $this->warn('⚠️  Modo DRY-RUN: Simulando operações...');
            $this->info('');
        }

        // Cria diretório se necessário
        if (!$this->option('dry-run')) {
            $this->ensureDirectoryExists();
        }

        // Baixa arquivos base
        $success = $this->downloadBaseFiles();

        if (!$success) {
            $this->error('❌ Falha ao baixar arquivos base.');
            return 1;
        }

        // Baixa países solicitados
        $countries = $this->getRequestedCountries();
        $this->downloadCountries($countries);

        // Limpa cache
        if (!$this->option('dry-run')) {
            Cache::flush();
            $this->info('');
            $this->info('🔄 Cache limpo');
        }

        // Resumo
        $this->showSummary();

        return 0;
    }

    /**
     * Cria a estrutura de diretórios.
     */
    protected function ensureDirectoryExists(): void
    {
        if (!File::exists($this->dataPath)) {
            File::makeDirectory($this->dataPath, 0755, true);
            $this->info("📁 Diretório criado: {$this->dataPath}");
        }
    }

    /**
     * Baixa os arquivos base (regiões e países).
     */
    protected function downloadBaseFiles(): bool
    {
        $this->info('📦 Baixando arquivos base...');

        $files = [
            'regions.json' => 'Regiões mundiais',
            'countries.json' => 'Países',
        ];

        $success = true;

        foreach ($files as $file => $description) {
            $url = "{$this->baseUrl}/{$file}";
            $destination = "{$this->dataPath}/{$file}";

            if ($this->option('force') || !File::exists($destination)) {
                if ($this->downloadFile($url, $destination, $description)) {
                    $this->stats['downloaded']++;
                } else {
                    $this->stats['errors']++;
                    $success = false;
                }
            } else {
                $this->info("  ⏭️  {$description} (já existe)");
                $this->stats['skipped']++;
            }
        }

        $this->info('');
        return $success;
    }

    /**
     * Retorna lista de países solicitados.
     *
     * @return array<string>
     */
    protected function getRequestedCountries(): array
    {
        $option = $this->option('countries');

        if ($option === 'all') {
            // Lê do countries.json se existir, senão usa lista padrão
            $countriesPath = $this->dataPath . '/countries.json';

            if (File::exists($countriesPath)) {
                $data = json_decode(File::get($countriesPath), true);
                return collect($data)->pluck('iso3')->toArray();
            }

            return config('geonames.available_countries', ['BRA', 'USA']);
        }

        return array_map('strtoupper', explode(',', $option));
    }

    /**
     * Baixa dados dos países especificados.
     *
     * @param array<string> $countries
     */
    protected function downloadCountries(array $countries): void
    {
        $this->info('🌎 Baixando dados dos países...');
        $this->info('Total: ' . count($countries) . ' países');
        $this->info('');

        $bar = $this->output->createProgressBar(count($countries));

        foreach ($countries as $iso3) {
            $this->downloadCountryData($iso3);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    /**
     * Baixa dados de um país específico (estados e cidades).
     */
    protected function downloadCountryData(string $iso3): void
    {
        $iso3 = strtoupper($iso3);
        $countryDir = "{$this->dataPath}/{$iso3}";

        // Cria diretório do país
        if (!$this->option('dry-run') && !File::exists($countryDir)) {
            File::makeDirectory($countryDir, 0755, true);
        }

        // Baixa index.json do país (estados)
        $statesUrl = "{$this->baseUrl}/{$iso3}/index.json";
        $statesFile = "{$countryDir}/index.json";

        if ($this->option('force') || !File::exists($statesFile)) {
            if ($this->downloadFile($statesUrl, $statesFile, "{$iso3}/index.json", true)) {
                $this->stats['downloaded']++;

                // Se baixou com sucesso, baixa cidades de cada estado
                if (!$this->option('dry-run')) {
                    $this->downloadCitiesForCountry($iso3, $statesFile);
                }
            } else {
                $this->stats['errors']++;
            }
        } else {
            $this->stats['skipped']++;
            // Mesmo se existir, tenta baixar cidades se faltarem
            if (!$this->option('dry-run')) {
                $this->downloadCitiesForCountry($iso3, $statesFile);
            }
        }
    }

    /**
     * Baixa cidades para um país baseado no arquivo de estados.
     */
    protected function downloadCitiesForCountry(string $iso3, string $statesFile): void
    {
        if (!File::exists($statesFile)) {
            return;
        }

        $states = json_decode(File::get($statesFile), true);

        if (!is_array($states)) {
            return;
        }

        foreach ($states as $state) {
            $stateIso2 = $state['iso2'] ?? null;

            if (!$stateIso2) {
                continue;
            }

            $stateIso2 = strtoupper($stateIso2);
            $citiesUrl = "{$this->baseUrl}/{$iso3}/{$stateIso2}/index.json";
            $citiesDir = "{$this->dataPath}/{$iso3}/{$stateIso2}";
            $citiesFile = "{$citiesDir}/index.json";

            if ($this->option('force') || !File::exists($citiesFile)) {
                // Cria diretório do estado
                if (!File::exists($citiesDir)) {
                    File::makeDirectory($citiesDir, 0755, true);
                }

                if ($this->downloadFile($citiesUrl, $citiesFile, "{$iso3}/{$stateIso2}/index.json", true)) {
                    $this->stats['downloaded']++;
                } else {
                    // Não conta erro se arquivo não existe (estado sem cidades)
                    $this->stats['skipped']++;
                }
            } else {
                $this->stats['skipped']++;
            }
        }
    }

    /**
     * Baixa um arquivo da URL.
     */
    protected function downloadFile(string $url, string $destination, string $description, bool $silent = false): bool
    {
        if ($this->option('dry-run')) {
            if (!$silent) {
                $this->info("  [DRY-RUN] {$url}");
            }
            return true;
        }

        try {
            $response = Http::timeout(config('geonames.data_source.timeout', 300))
                ->withOptions([
                    'verify' => config('geonames.data_source.verify_ssl', true),
                ])
                ->get($url);

            if ($response->successful()) {
                File::put($destination, $response->body());
                return true;
            }

            // 404 é aceitável (estado sem cidades, por exemplo)
            if ($response->status() === 404) {
                return false;
            }

            if (!$silent) {
                $this->warn("  ⚠️  HTTP {$response->status()}: {$description}");
            }
        } catch (\Exception $e) {
            if (!$silent) {
                $this->warn("  ✗ {$description}: {$e->getMessage()}");
            }
        }

        return false;
    }

    /**
     * Mostra resumo da instalação.
     */
    protected function showSummary(): void
    {
        $this->info('');
        $this->info(str_repeat('=', 60));
        $this->info('📊 Resumo da Instalação');
        $this->info(str_repeat('=', 60));
        $this->info(sprintf("✅ Arquivos baixados: %d", $this->stats['downloaded']));
        $this->info(sprintf("⏭️  Arquivos ignorados: %d", $this->stats['skipped']));
        $this->info(sprintf("❌ Erros: %d", $this->stats['errors']));
        $this->info('');

        if ($this->option('dry-run')) {
            $this->info('💡 Modo dry-run: nenhum arquivo foi realmente baixado.');
            $this->info('   Execute sem --dry-run para baixar os arquivos.');
        } else {
            $this->info('✅ Instalação concluída!');
            $this->info('');
            $this->info('💡 Próximos passos:');
            $this->info('   php artisan geonames:cache-warm --all');
        }

        $this->info('');
    }
}
