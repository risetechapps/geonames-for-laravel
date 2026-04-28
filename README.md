# Geonames - RiseTech Apps

O **Geonames** é um pacote Laravel projetado para gerenciar dados geográficos globais (Regiões, Países, Estados e Cidades) com foco em **ultra-performance** e arquitetura multi-tenant.

A arquitetura do pacote utiliza um carregamento em árvore (Tree-loading), segmentando os dados em arquivos JSON específicos por região. Isso garante que apenas o conteúdo geográfico necessário seja carregado na memória, economizando recursos do servidor.

---

## 📥 Instalação

### 1. Instale o pacote via Composer

```bash
composer require risetechapps/geonames-for-laravel
```

### 2. Execute o instalador

```bash
php artisan geonames:install
```

Este comando irá:
- ✅ Publicar o arquivo de configuração
- ✅ Perguntar se deseja criar migration para Models
- ✅ **Baixar os dados geográficos** (regiões, países, estados, cidades)

### 3. Ou baixe os dados manualmente

Se preferir baixar os dados separadamente:

```bash
# Baixar todos os países disponíveis (⚠️ consome memória e disco)
php artisan geonames:install-data

# Recomendado: Baixar países específicos (códigos ISO3)
php artisan geonames:install-data --countries=BRA
php artisan geonames:install-data --countries=BRA,USA,ARG,DEU

# Forçar reinstalação (sobrescreve existentes)
php artisan geonames:install-data --force

# Simular sem baixar (ver o que seria baixado)
php artisan geonames:install-data --dry-run
```

> **💡 Dica:** Baixar todos os 250 países consome ~100MB de disco e requer ~512MB de RAM. 
> Para a maioria das aplicações, baixar apenas os países necessários é suficiente.

### Estrutura de Dados Baixados

Após a instalação, os dados são organizados assim:

```
resources/geonames/json/
├── regions.json          # Regiões mundiais
├── countries.json        # Dados dos países
├── BRA/                  # Pasta do Brasil (ISO3)
│   ├── index.json        # Estados do Brasil
│   ├── SP/               # Pasta de São Paulo (ISO2)
│   │   └── index.json    # Cidades de São Paulo
│   ├── RJ/               # Pasta do Rio de Janeiro
│   │   └── index.json    # Cidades do Rio
│   └── ...
├── USA/                  # Pasta dos EUA
│   ├── index.json        # Estados americanos
│   └── ...
└── ...
```

> **Nota:** Os dados são armazenados em `resources/geonames/json/` para evitar conflitos com outros pacotes.

### Configuração (.env)

```env
# URL dos dados (opcional, usa padrão se não definido)
GEONAMES_DATA_URL=https://github.com/risetechapps/geonames-data/releases/latest/download/

# Configurações de cache
GEONAMES_CACHE_TTL=86400
GEONAMES_CACHE_PREFIX=geonames
GEONAMES_DEFAULT_LANGUAGE=pt-BR
```

---

## 🛠 Estrutura de Pastas

Para que o pacote funcione corretamente, os dados JSON devem seguir esta hierarquia no diretório de recursos:
`resources/geonames/json/{COUNTRY_ISO3}/{STATE_ISO2}/index.json`

---

## 📖 Como Usar

### 1. País (Country)
Instancie um país usando o código ISO2 ou o Nome. A busca é case-insensitive.

```php
use RiseTechApps\Geonames\Features\Country;

$country = new Country('BR');

if ($country->exists()) {
    echo $country->getName();           // Brazil
    echo $country->getEmoji();          // 🇧🇷
    echo $country->getPhoneCode();      // 55
    echo $country->getTimezone();       // America/Sao_Paulo
}
```

### 2. Estados de um País (States)
Para obter todos os estados de um país, use o método `states()` e depois `all()`:

```php
$country = new Country('BR');

// ⚠️ $country->states() retorna um objeto States (repositório)
// Para obter os dados, use ->all()
$states = $country->states()->all();

foreach ($states as $state) {
    echo $state->getName();    // ACRE, ALAGOAS, AMAZONAS...
    echo $state->getIso2();    // AC, AL, AM...
}

// Outros métodos disponíveis
$country->states()->first();              // Primeiro estado
$country->states()->count();              // Total de estados (27)
$country->states()->search('Sao');       // Busca por nome
$country->states()->paginate(20, 1);     // Paginação
```

### 3. Estado Específico (State)
Para buscar um estado específico:

```php
use RiseTechApps\Geonames\Features\State;

$country = new Country('BR');
$state = $country->state('SP');  // ou new State('SP', $country)

if ($state->exists()) {
    echo $state->getName();    // SÃO PAULO
    echo $state->getIso2();    // SP
}
```

### 4. Cidades (Cities)
As cidades são carregadas sob demanda através do estado:

```php
$country = new Country('BR');
$state = $country->state('SP');

// ⚠️ $state->cities() retorna um objeto Cities (repositório)
// Para obter os dados, use ->all()
$cities = $state->cities()->all();

foreach ($cities as $city) {
    echo $city->getName();       // SÃO PAULO, CAMPINAS...
    echo $city->getLatitude();   // -23.5475
    echo $city->getLongitude();  // -46.6361
}

// Outros métodos disponíveis
$state->cities()->first();                // Primeira cidade
$state->cities()->count();                // Total de cidades
$state->cities()->search('Santo');      // Busca por nome
$state->cities()->paginate(50, 1);      // Paginação
```

### 5. Uso com Helper Global

```php
// Acessar via helper geonames()
$country = geonames()->country('BR');

// Listar todos os países
$countries = geonames()->countries()->all();

// Listar regiões
$regions = geonames()->regioes()->all();
```

---

## 🧬 API de Métodos

### Objeto `Country`
#### Propriedades
- `getName()`: Retorna o nome internacional.
- `getNative()`: Retorna o nome nativo do país.
- `getIso2()` / `getIso3()`: Retorna os códigos padrão ISO.
- `getPhoneCode()`: Retorna o DDI (ex: 55).
- `getCurrencySymbol()`: Retorna o símbolo da moeda (ex: R$).
- `getEmoji()`: Retorna a bandeira do país em formato Emoji.
- `getTimezone()`: Retorna o fuso horário principal.

#### Relacionamentos
- `states()`: Retorna objeto `States` (repositório).
  - **Use** `$country->states()->all()` para obter Collection.
- `state('SP')`: Retorna um `State` específico.

### Objeto `States` (Repositório)
- `all()`: Retorna Collection de todos os estados.
- `first()`: Retorna o primeiro estado.
- `count()`: Retorna o total de estados.
- `search('termo')`: Busca por nome parcial.
- `paginate(20, 1)`: Retorna array paginado.
- `find('SP')`: Busca estado por código/nome.

### Objeto `State`
#### Propriedades
- `getName()`: Nome completo do estado.
- `getIso2()`: Sigla/UF (ex: AC, SP, RJ).
- `getCountry()`: Retorna o objeto `Country` pai.

#### Relacionamentos
- `cities()`: Retorna objeto `Cities` (repositório).
  - **Use** `$state->cities()->all()` para obter Collection.
- `city('São Paulo')`: Retorna uma `City` específica.

### Objeto `Cities` (Repositório)
- `all()`: Retorna Collection de todas as cidades.
- `first()`: Retorna a primeira cidade.
- `count()`: Retorna o total de cidades.
- `search('termo')`: Busca por nome parcial.
- `paginate(50, 1)`: Retorna array paginado.

### Objeto `City`
- `getName()`: Nome da cidade.
- `getLatitude()` / `getLongitude()`: Coordenadas geográficas.
- `toArray()`: Retorna array com todos os dados.

### Objeto `Geonames` (Helper)
```php
$geonames = geonames();
$geonames->countries()->all();    // Todos os países
$geonames->country('BR');          // País específico
$geonames->regioes()->all();      // Todas as regiões
```

---

## ⚠️ Padrão de Repositório (Lazy Loading)

O Geonames utiliza um padrão de **repositório** para carregamento lazy de dados:

```php
// ❌ Incorreto: Não retorna dados diretamente
$country->states();           // Retorna objeto States
$state->cities();              // Retorna objeto Cities

// ✅ Correto: Chame ->all() para obter os dados
$country->states()->all();     // Retorna Collection de State
$state->cities()->all();       // Retorna Collection de City

// ✅ Também funciona: Busca específica
$country->state('SP');         // Retorna objeto State
$state->city('São Paulo');     // Retorna objeto City
```

**Por quê?** Isso permite carregar dados sob demanda, com cache e paginação eficientes.

---

## ⚡ Performance e Cache
O Geonames utiliza o driver de cache nativo do Laravel para armazenar os dados processados por 24 horas (86400 segundos). O cache é segmentado por chaves únicas baseadas na hierarquia geográfica (ex: `geonames.cities.BRA.SP`), o que otimiza drasticamente o tempo de resposta e evita I/O de disco desnecessário.

### Comandos Artisan

```bash
# Instalação completa (baixa dados + publica config)
php artisan geonames:install

# Apenas download de dados
php artisan geonames:install-data --countries=BRA,USA

# Pré-aquecer cache (recomendado em produção)
php artisan geonames:cache-warm --country=BRA

# Limpar cache
php artisan geonames:cache-clear

# Benchmark de performance
php artisan geonames:benchmark
```

> **⚠️ Atenção:** Comandos que processam todos os países (`--all`) consomem ~512MB de RAM. 
> Use `--country=XX` para processar países específicos e economizar memória.

---

## 🛠️ Requisitos

| Dependência | Versão mínima |
|--------------|----------------|
| PHP | 8.3 |
| Laravel | 12.x |
---

## 🧑‍💻 Autor

**Rise Tech**  
📧 apps@risetech.com.br  
🌐 https://risetech.com.br  
💼 https://github.com/risetechapps

---

## 🧩 Trait HasGeonames (Para Models)

Adicione suporte geográfico a qualquer Model do Laravel.

### Instalação

```php
// No seu Model
use RiseTechApps\Geonames\Traits\HasGeonames;

class User extends Model
{
    use HasGeonames;
}
```

### Uso Básico

```php
// Definir localização (com validação automática)
$user->setLocation('BR', 'SP', 'São Paulo');
$user->save();

// Acessar objetos
$user->country(); // Retorna Country|null
$user->state();   // Retorna State|null
$user->city();    // Retorna City|null

// Formatar endereço
$user->getFullAddress('short');  // "São Paulo, SP"
$user->getFullAddress('medium'); // "São Paulo, SÃO PAULO, Brazil"
$user->getFullAddress('long');   // "São Paulo, SÃO PAULO, Brazil 🇧🇷 (America/Sao_Paulo)"

// Verificações
$user->hasLocation();         // Tem pelo menos país?
$user->hasCompleteLocation(); // Tem país, estado e cidade?
$user->getTimezone();         // Fuso horário

// Queries
User::whereCountry('BR')->get();
User::whereState('SP')->get();
User::whereRegion('EUROPE')->get();

// Distância entre usuários
$distance = $user1->distanceTo($user2, 'km');
```

### Comando de Instalação

```bash
# Instalação completa
php artisan geonames:install

# Com migration para um model específico
php artisan geonames:install --model=User --migration
```

Veja a [documentação completa](docs/has-geonames-examples.md) para mais exemplos.

---

## 🪪 Licença
Distribuído sob a licença MIT. RiseTech Apps © 2025.
