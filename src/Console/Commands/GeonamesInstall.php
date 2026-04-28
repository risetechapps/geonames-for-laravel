<?php

namespace RiseTechApps\Geonames\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GeonamesInstall extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geonames:install
                            {--model= : Nome do model para adicionar a trait}
                            {--migration : Criar migration automaticamente}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Instala e configura o Geonames no projeto';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🌍 Instalando Geonames...');

        // Publica configuração
        $this->call('vendor:publish', [
            '--tag' => 'geonames-config',
            '--force' => true,
        ]);

        $this->info('✅ Configuração publicada');

        // Cria migration se solicitado
        if ($this->option('model')) {
            $this->createMigration($this->option('model'));
        } elseif ($this->option('migration')) {
            $this->createMigration($this->ask('Qual o nome do model?'));
        }

        // Pergunta sobre instalação de dados
        $this->info('');
        $this->info('📦 Dados Geográficos');

        if ($this->confirm('Deseja baixar os dados geográficos agora?', true)) {
            $this->call('geonames:install-data');
        } else {
            $this->info('');
            $this->info('💡 Você pode instalar os dados posteriormente com:');
            $this->info('   php artisan geonames:install-data');
        }

        $this->info('');
        $this->info('🎉 Geonames instalado com sucesso!');
        $this->info('');
        $this->info('Próximos passos:');
        $this->info('  1. Adicione `use HasGeonames;` ao seu Model');
        $this->info('  2. Execute `php artisan migrate`');
        $this->info('  3. Use `setLocation()` para definir localizações');
        $this->info('');

        return 0;
    }

    private function createMigration(string $model): void
    {
        $model = Str::studly($model);
        $table = Str::snake(Str::pluralStudly($model));

        $migrationName = "add_geonames_fields_to_{$table}_table";
        $migrationFile = database_path("migrations/" . date('Y_m_d_His') . "_{$migrationName}.php");

        $content = <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('{$table}', function (Blueprint \$table) {
            \$table->char('country_code', 2)->nullable()->comment('Código ISO2 do país');
            \$table->char('state_code', 2)->nullable()->comment('Código ISO2 do estado');
            \$table->string('city_name')->nullable()->comment('Nome da cidade');

            \$table->index('country_code');
            \$table->index('state_code');
            \$table->index('city_name');
        });
    }

    public function down(): void
    {
        Schema::table('{$table}', function (Blueprint \$table) {
            \$table->dropColumn(['country_code', 'state_code', 'city_name']);
        });
    }
};
PHP;

        file_put_contents($migrationFile, $content);

        $this->info("✅ Migration criada: " . basename($migrationFile));
    }
}
