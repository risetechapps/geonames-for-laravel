<?php

namespace RiseTechApps\Geonames\Console\Commands;

use Illuminate\Console\Command;
use RiseTechApps\Geonames\Features\Countries;
use RiseTechApps\Geonames\Features\Regions;
use Illuminate\Support\Facades\Cache;

class GeonamesCacheClear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geonames:cache-clear
                            {--tag= : Limpar apenas cache de uma tag específica (countries, states, cities, regions)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpa o cache dos dados geográficos';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tag = $this->option('tag');

        if ($tag) {
            $tag = strtolower($tag);

            match ($tag) {
                'countries' => $this->clearCountries(),
                'states' => $this->clearStates(),
                'cities' => $this->clearCities(),
                'regions' => $this->clearRegions(),
                default => $this->error("Tag inválida: {$tag}. Use: countries, states, cities, regions"),
            };

            return $tag === 'invalid' ? 1 : 0;
        }

        // Limpa todo o cache
        $this->info('Limpando todo o cache do Geonames...');

        (new Countries())->flushCache();
        $this->info('✓ Cache de países limpo');

        (new Regions())->flushCache();
        $this->info('✓ Cache de regiões limpo');

        // Limpa cache de estados e cidades (chaves dinâmicas)
        $this->clearPattern('geonames.states.*');
        $this->info('✓ Cache de estados limpo');

        $this->clearPattern('geonames.cities.*');
        $this->info('✓ Cache de cidades limpo');

        $this->info('Cache limpo com sucesso!');

        return 0;
    }

    private function clearCountries(): void
    {
        (new Countries())->flushCache();
        $this->info('Cache de países limpo');
    }

    private function clearRegions(): void
    {
        (new Regions())->flushCache();
        $this->info('Cache de regiões limpo');
    }

    private function clearStates(): void
    {
        $this->clearPattern('geonames.states.*');
        $this->info('Cache de estados limpo');
    }

    private function clearCities(): void
    {
        $this->clearPattern('geonames.cities.*');
        $this->info('Cache de cidades limpo');
    }

    private function clearPattern(string $pattern): void
    {
        // Nota: Alguns drivers de cache não suportam pattern matching
        // Esta é uma implementação básica
        if (Cache::getStore() instanceof \Illuminate\Cache\TaggableStore) {
            Cache::tags(['geonames'])->flush();
        }
    }
}
