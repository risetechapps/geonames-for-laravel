<?php

namespace RiseTechApps\Geonames\Console\Commands;

use Illuminate\Console\Command;
use RiseTechApps\Geonames\Features\Countries;
use RiseTechApps\Geonames\Features\Country;
use RiseTechApps\Geonames\Features\Regions;
use Illuminate\Support\Facades\Cache;

class GeonamesCacheWarm extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geonames:cache-warm
                            {--country= : Código ISO3 específico do país para aquecer (opcional)}
                            {--all : Aquecer cache de todos os países}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pré-carrega o cache dos dados geográficos';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Aumenta limite de memória
        ini_set('memory_limit', '512M');

        // Desabilita Telescope (evita erro de memória)
        if (class_exists(\Laravel\Telescope\Telescope::class)) {
            \Laravel\Telescope\Telescope::stopRecording();
        }

        $this->info('Iniciando cache warming do Geonames...');

        // Aquecer regiões
        $this->info('Carregando regiões...');
        (new Regions())->all();
        $this->info('✓ Regiões carregadas');

        // Aquecer países
        $this->info('Carregando países...');
        $countries = (new Countries())->all();
        $this->info('✓ ' . $countries->count() . ' países carregados');

        if ($this->option('all')) {
            $this->warn('Atenção: Aquecer todos os países consome muita memória!');
            $this->info('Aquecendo estados de todos os países...');
            $bar = $this->output->createProgressBar($countries->count());

            $counter = 0;
            foreach ($countries as $countryData) {
                try {
                    $country = new Country($countryData['iso3']);
                    $country->states()->all();

                    // Libera memória a cada 10 países
                    if (++$counter % 10 === 0) {
                        gc_collect_cycles();
                    }
                } catch (\Exception $e) {
                    $this->warn("Erro ao carregar estados de {$countryData['iso3']}: {$e->getMessage()}");
                }
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
        } elseif ($this->option('country')) {
            $iso3 = strtoupper($this->option('country'));
            $this->info("Aquecendo estados de {$iso3}...");

            try {
                $country = new Country($iso3);
                $states = $country->states()->all();
                $this->info('✓ ' . $states->count() . ' estados carregados');

                $this->info("Aquecendo cidades...");
                $bar = $this->output->createProgressBar($states->count());

                $counter = 0;
                foreach ($states as $stateData) {
                    try {
                        $state = $country->state($stateData['iso2']);
                        if ($state->exists()) {
                            $state->cities()->all();
                        }

                        // Libera memória a cada 5 estados
                        if (++$counter % 5 === 0) {
                            gc_collect_cycles();
                        }
                    } catch (\Exception $e) {
                        // Silencia erros de estados sem cidades
                    }
                    $bar->advance();
                }

                $bar->finish();
                $this->newLine();
            } catch (\Exception $e) {
                $this->error("Erro: {$e->getMessage()}");
                return 1;
            }
        }

        $this->info('Cache warming concluído!');

        return 0;
    }
}
