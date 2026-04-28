# Trait HasGeonames - Exemplo de Uso

A trait `HasGeonames` permite que qualquer Model do Laravel gerencie localização geográfica de forma simples e poderosa.

## Instalação

### 1. Adicione a trait ao seu Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use RiseTechApps\Geonames\Traits\HasGeonames;

class User extends Model
{
    use HasGeonames;

    protected $fillable = [
        'name',
        'email',
        'country_code',
        'state_code',
        'city_name',
    ];
}
```

### 2. Crie a migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->char('country_code', 2)->nullable()->after('email');
            $table->char('state_code', 2)->nullable()->after('country_code');
            $table->string('city_name')->nullable()->after('state_code');
            
            $table->index('country_code');
            $table->index('state_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['country_code', 'state_code', 'city_name']);
        });
    }
};
```

## Exemplos de Uso

### Definir Localização

```php
$user = User::find(1);

// Definir localização completa (com validação automática)
$user->setLocation('BR', 'SP', 'São Paulo');
$user->save();

// Definir apenas país
$user->setLocation('US');
$user->save();

// Tentar definir localização inválida (lança exceção)
try {
    $user->setLocation('XX'); // País inválido
} catch (\InvalidArgumentException $e) {
    echo $e->getMessage(); // "País inválido: XX"
}
```

### Acessar Dados Geográficos

```php
$user = User::find(1);

// Obter objetos Country, State, City
$country = $user->country(); // Country|null
$state = $user->state();     // State|null
$city = $user->city();       // City|null

// Acessar propriedades diretamente
if ($country) {
    echo $country->getName();        // "Brazil"
    echo $country->getEmoji();       // "🇧🇷"
    echo $country->getPhoneCode();   // "55"
    echo $country->getTimezone();    // "America/Sao_Paulo"
}

if ($state) {
    echo $state->getName();          // "SÃO PAULO"
    echo $state->getIso2();          // "SP"
}

if ($city) {
    echo $city->getName();           // "São Paulo"
    echo $city->getLatitude();       // "-23.5475"
    echo $city->getLongitude();      // "-46.6361"
}
```

### Formatar Endereços

```php
$user->setLocation('BR', 'SP', 'São Paulo');

// Formato curto: "São Paulo, SP"
echo $user->getFullAddress('short');

// Formato médio: "São Paulo, SÃO PAULO, Brazil"
echo $user->getFullAddress('medium');

// Formato longo: "São Paulo, SÃO PAULO, Brazil 🇧🇷 (America/Sao_Paulo)"
echo $user->getFullAddress('long');
```

### Verificações

```php
$user = User::find(1);

// Verificar se tem localização (pelo menos país)
if ($user->hasLocation()) {
    echo "Usuário tem localização definida";
}

// Verificar se tem localização completa
if ($user->hasCompleteLocation()) {
    echo "Usuário tem país, estado e cidade definidos";
}

// Obter fuso horário
$timezone = $user->getTimezone(); // "America/Sao_Paulo"
```

### Scopes de Query

```php
// Buscar usuários de um país específico
$brazilianUsers = User::whereCountry('BR')->get();

// Buscar usuários de um estado
$spUsers = User::whereState('SP')->get();

// Buscar usuários de uma cidade específica
$usersFromSaoPaulo = User::whereCity('São Paulo')->get();

// Buscar usuários de uma região (ex: toda a Europa)
$europeanUsers = User::whereRegion('EUROPE')->get();

// Combinar scopes
$users = User::whereCountry('BR')
    ->whereState('SP')
    ->where('active', true)
    ->get();
```

### Distância entre Localizações

```php
$user1 = User::find(1); // São Paulo, SP
$user2 = User::find(2); // Rio de Janeiro, RJ

// Calcular distância em km
$distance = $user1->distanceTo($user2, 'km');
echo "Distância: {$distance} km"; // Aproximadamente 360km

// Calcular distância em milhas
$distance = $user1->distanceTo($user2, 'miles');
echo "Distância: {$distance} milhas";
```

### Limpar Localização

```php
$user->clearLocation();
$user->save();

// Agora country_code, state_code e city_name são null
```

## Uso em Formulários

```php
// Controller
public function edit(User $user)
{
    $countries = geonames()->countries()->all();
    
    return view('users.edit', compact('user', 'countries'));
}

// View Blade
<form method="POST" action="{{ route('users.update', $user) }}">
    @csrf
    @method('PUT')
    
    <select name="country_code" required>
        @foreach($countries as $country)
            <option value="{{ $country['iso2'] }}" 
                {{ $user->country_code === $country['iso2'] ? 'selected' : '' }}>
                {{ $country['emoji'] }} {{ $country['name'] }}
            </option>
        @endforeach
    </select>
    
    <input type="text" name="state_code" value="{{ $user->state_code }}" maxlength="2">
    <input type="text" name="city_name" value="{{ $user->city_name }}">
    
    <button type="submit">Atualizar</button>
</form>
```

## Eventos

A trait automaticamente normaliza os códigos para uppercase antes de salvar:

```php
$user->country_code = 'br'; // Será convertido para 'BR' ao salvar
$user->state_code = 'sp';   // Será convertido para 'SP' ao salvar
$user->save();
```
