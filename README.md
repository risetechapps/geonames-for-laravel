# Geonames - RiseTech Apps

O **Geonames** é um pacote Laravel projetado para gerenciar dados geográficos globais (Regiões, Países, Estados e Cidades) com foco em **ultra-performance** e arquitetura multi-tenant.

A arquitetura do pacote utiliza um carregamento em árvore (Tree-loading), segmentando os dados em arquivos JSON específicos por região. Isso garante que apenas o conteúdo geográfico necessário seja carregado na memória, economizando recursos do servidor.

---

## 🛠 Estrutura de Pastas

Para que o pacote funcione corretamente, os dados JSON devem seguir esta hierarquia no diretório de recursos:
`resources/json/{COUNTRY_ISO3}/{STATE_ISO2}/index.json`

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

### 2. Estado (State)
O estado requer uma instância obrigatória de `Country`, garantindo a integridade da hierarquia e a localização correta dos arquivos.

```php
use RiseTechApps\Geonames\Features\State;

$country = new Country('BR');
$state = new State('SP', $country);

if ($state->exists()) {
    echo $state->getName();    // SÃO PAULO
    echo $state->getIso2();    // SP
}
```

### 3. Cidades (Cities)
As cidades são carregadas sob demanda através de um método no objeto `State`, que lê o arquivo `index.json` da pasta do estado correspondente.

```php
$country = new Country('BR');
$state = new State('SP', $country);

// Retorna uma Collection de objetos City
$cities = $state->getCities();

foreach ($cities as $city) {
    echo $city->getName();
    echo $city->getLatitude();
}
```

---

## 🧬 API de Métodos

### Objeto `Country`
- `getName()`: Retorna o nome internacional.
- `getNative()`: Retorna o nome nativo do país.
- `getIso2()` / `getIso3()`: Retorna os códigos padrão ISO.
- `getPhoneCode()`: Retorna o DDI (ex: 55).
- `getCurrencySymbol()`: Retorna o símbolo da moeda (ex: R$).
- `getEmoji()`: Retorna a bandeira do país em formato Emoji.
- `getTimezone()`: Retorna o fuso horário principal (primeiro do array).

### Objeto `State`
- `getName()`: Nome completo do estado.
- `getIso2()`: Sigla/UF (ex: AC, SP, RJ).
- `getCities()`: Retorna uma `Collection` de objetos `City`.
- `getCountry()`: Retorna a instância do objeto `Country` pai.

### Objeto `City`
- `getName()`: Nome da cidade.
- `getLatitude()` / `getLongitude()`: Coordenadas geográficas decimais.

---

## ⚡ Performance e Cache
O Geonames utiliza o driver de cache nativo do Laravel para armazenar os dados processados por 24 horas (86400 segundos). O cache é segmentado por chaves únicas baseadas na hierarquia geográfica (ex: `geonames.cities.BRA.SP`), o que otimiza drasticamente o tempo de resposta e evita I/O de disco desnecessário.

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

## 🪪 Licença
Distribuído sob a licença MIT. RiseTech Apps © 2025.
