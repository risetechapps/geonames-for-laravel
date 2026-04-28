<?php

namespace RiseTechApps\Geonames\Console\Commands;

use Illuminate\Console\Command;
use RiseTechApps\Geonames\Features\Countries;
use RiseTechApps\Geonames\Features\Country;
use RiseTechApps\Geonames\Features\GeonamesValidator;

class GeonamesBenchmark extends Command
{
    protected $signature = 'geonames:benchmark
                            {--country=BRA : Código ISO3 do país para testar}';

    protected $description = 'Executa benchmark de performance do Geonames';

    public function handle(): int
    {
        $this->info('🚀 Iniciando Benchmark do Geonames');
        $this->info(str_repeat('=', 60));

        $countryCode = $this->option('country');

        try {
            $country = new Country($countryCode);
        } catch (\Exception $e) {
            $this->error("País não encontrado: {$countryCode}");
            return 1;
        }

        // Benchmark 1: Carregamento
        $this->benchmarkLoading($country);

        // Benchmark 2: Busca linear (JSON)
        $this->benchmarkLinearSearch($country);

        // Benchmark 3: Array indexado
        $this->benchmarkIndexedSearch($country);

        // Benchmark 4: Validação
        $this->benchmarkValidation();

        // Benchmark 5: Memória
        $this->benchmarkMemory($country);

        // Resumo
        $this->showSummary($country);

        return 0;
    }

    private function benchmarkLoading(Country $country): void
    {
        $this->info("\n📦 Benchmark 1: Carregamento");
        $this->info(str_repeat('-', 60));

        // Limpar cache para teste real
        \Illuminate\Support\Facades\Cache::flush();
        $this->info("Cache limpo para teste...");

        // Teste 1: Carregar países (sem cache)
        $start = microtime(true);
        $memoryStart = memory_get_usage(true);

        $countries = (new Countries())->all();

        $time = (microtime(true) - $start) * 1000;
        $memory = (memory_get_usage(true) - $memoryStart) / 1024 / 1024;

        $this->info(sprintf("Países (primeira carga):     %6.2f ms | %6.2f MB", $time, $memory));
        $this->info("  ↳ Total de países: " . $countries->count());

        // Teste 2: Carregar países (com cache)
        $start = microtime(true);
        (new Countries())->all();
        $time = (microtime(true) - $start) * 1000;
        $this->info(sprintf("Países (cache):              %6.2f ms | cache hit", $time));

        // Teste 3: Carregar estados
        $start = microtime(true);
        $memoryStart = memory_get_usage(true);

        $states = $country->states()->all();

        $time = (microtime(true) - $start) * 1000;
        $memory = (memory_get_usage(true) - $memoryStart) / 1024 / 1024;

        $this->info(sprintf("Estados de {$country->getIso3()}:        %6.2f ms | %6.2f MB", $time, $memory));
        $this->info("  ↳ Total de estados: " . $states->count());

        // Teste 4: Carregar cidades de um estado
        $state = $country->state($states->first()['iso2']);

        $start = microtime(true);
        $memoryStart = memory_get_usage(true);

        $cities = $state->cities()->all();

        $time = (microtime(true) - $start) * 1000;
        $memory = (memory_get_usage(true) - $memoryStart) / 1024 / 1024;

        $this->info(sprintf("Cidades de {$state->getIso2()}:         %6.2f ms | %6.2f MB", $time, $memory));
        $this->info("  ↳ Total de cidades: " . $cities->count());
    }

    private function benchmarkLinearSearch(Country $country): void
    {
        $this->info("\n🔍 Benchmark 2: Busca Linear (JSON)");
        $this->info(str_repeat('-', 60));

        $states = $country->states()->all();
        $state = $country->state($states->first()['iso2']);
        $cities = $state->cities()->all();

        // Busca 1: Estado por nome
        $searchTerm = substr($states->first()['name'], 0, 3);

        $start = microtime(true);
        $results = $country->states()->search($searchTerm);
        $time = (microtime(true) - $start) * 1000;

        $this->info(sprintf("Busca estado '{$searchTerm}*':     %6.2f ms | %d resultados", $time, $results->count()));

        // Busca 2: Cidade por nome
        $searchTerm = substr($cities->first()['name'], 0, 3);

        $start = microtime(true);
        $results = $state->cities()->search($searchTerm);
        $time = (microtime(true) - $start) * 1000;

        $this->info(sprintf("Busca cidade '{$searchTerm}*':     %6.2f ms | %d resultados", $time, $results->count()));

        // Busca 3: 100 buscas sequenciais
        $start = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            $state->cities()->search('Sao');
        }
        $time = (microtime(true) - $start) * 1000;

        $this->info(sprintf("100 buscas 'Sao':              %6.2f ms | %6.2f ms/media", $time, $time / 100));
    }

    private function benchmarkIndexedSearch(Country $country): void
    {
        $this->info("\n📚 Benchmark 3: Busca Indexada (Array)");
        $this->info(str_repeat('-', 60));

        $states = $country->states()->all();
        $state = $country->state($states->first()['iso2']);
        $cities = $state->cities()->all()->toArray();

        // Criar índice manual
        $start = microtime(true);
        $index = [];
        foreach ($cities as $city) {
            $firstChar = strtoupper(substr($city['name'], 0, 1));
            $index[$firstChar][] = $city;
        }
        $indexTime = (microtime(true) - $start) * 1000;

        $this->info(sprintf("Criar índice:                %6.2f ms", $indexTime));

        // Buscar usando índice
        $start = microtime(true);
        $results = $index['S'] ?? [];
        $time = (microtime(true) - $start) * 1000;

        $this->info(sprintf("Busca índice 'S':            %6.2f ms | %d resultados", $time, count($results)));

        // Comparativo
        $linearStart = microtime(true);
        $linearResults = array_filter($cities, fn($c) => str_starts_with(strtoupper($c['name']), 'S'));
        $linearTime = (microtime(true) - $linearStart) * 1000;

        $this->info(sprintf("Busca linear 'S':           %6.2f ms | %d resultados", $linearTime, count($linearResults)));

        if ($linearTime > 0) {
            $speedup = $linearTime / ($time ?: 0.001);
            $this->info(sprintf("Speedup índice vs linear:   %6.2fx mais rápido", $speedup));
        }
    }

    private function benchmarkValidation(): void
    {
        $this->info("\n✅ Benchmark 4: Validação");
        $this->info(str_repeat('-', 60));

        // Validação 1: País válido (100x)
        $start = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            GeonamesValidator::isValidCountry('BR');
        }
        $time = (microtime(true) - $start) * 1000;
        $this->info(sprintf("100x isValidCountry('BR'):   %6.2f ms | %6.2f ms/op", $time, $time / 100));

        // Validação 2: Endereço completo
        $start = microtime(true);
        GeonamesValidator::validateAddress('BR', 'SP', 'São Paulo');
        $time = (microtime(true) - $start) * 1000;
        $this->info(sprintf("validateAddress completo:    %6.2f ms", $time));

        // Validação 3: Endereço inválido
        $start = microtime(true);
        GeonamesValidator::validateAddress('XX', 'YY', 'Invalid');
        $time = (microtime(true) - $start) * 1000;
        $this->info(sprintf("validateAddress inválido:    %6.2f ms", $time));
    }

    private function benchmarkMemory(Country $country): void
    {
        $this->info("\n💾 Benchmark 5: Consumo de Memória");
        $this->info(str_repeat('-', 60));

        // Reset
        \Illuminate\Support\Facades\Cache::flush();
        gc_collect_cycles();

        $baseline = memory_get_usage(true) / 1024 / 1024;
        $this->info(sprintf("Baseline:                    %6.2f MB", $baseline));

        // Carregar tudo
        $countries = (new Countries())->all();
        $states = $country->states()->all();

        $totalCities = 0;
        foreach ($states as $stateData) {
            $state = $country->state($stateData['iso2']);
            if ($state->exists()) {
                $totalCities += $state->cities()->all()->count();
            }
        }

        $totalMemory = memory_get_usage(true) / 1024 / 1024;
        $usedMemory = $totalMemory - $baseline;

        $this->info(sprintf("Após carregar tudo:          %6.2f MB", $totalMemory));
        $this->info(sprintf("Memória usada:               %6.2f MB", $usedMemory));
        $this->info("  ↳ Países: " . $countries->count());
        $this->info("  ↳ Estados de {$country->getIso3()}: " . $states->count());
        $this->info("  ↳ Cidades carregadas: " . $totalCities);

        if ($totalCities > 0) {
            $perCity = ($usedMemory * 1024 * 1024) / $totalCities;
            $this->info(sprintf("Média por registro:          %6.2f bytes", $perCity));
        }
    }

    private function showSummary(Country $country): void
    {
        $this->info("\n" . str_repeat('=', 60));
        $this->info("📋 RESUMO");
        $this->info(str_repeat('=', 60));

        $this->info("");
        $this->info("✅ JSON está performático para:");
        $this->info("   • Leitura frequente com cache");
        $this->info("   • Dados estáticos (países/estados)");
        $this->info("   • Volumes < 100k registros");
        $this->info("");
        $this->info("⚠️  Considere mudar se:");
        $this->info("   • Buscas frequentes em >500k cidades");
        $this->info("   • Necessidade de indexação complexa");
        $this->info("   • Queries geoespaciais (distância, raio)");
        $this->info("");
        $this->info("💡 Dica: Execute 'geonames:cache-warm --all'");
        $this->info("   para melhorar performance em produção.");
        $this->info("");
    }
}
