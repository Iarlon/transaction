# Guia de testes

Este guia explica como validar a base de `Tenant`, `User` e `Transaction` implementada no projeto.

Os comandos foram preparados para Windows com PowerShell e utilizam o PostgreSQL do `docker-compose.yaml`.

## 1. Pré-requisitos

Verifique se estão instalados:

- PHP 8.3 ou superior;
- Composer;
- Docker Desktop;
- Node.js e npm, caso também queira executar a interface.

Na raiz do projeto, confirme as versões:

```powershell
php --version
composer --version
docker --version
node --version
npm --version
```

## 2. Instalar as dependências

Caso ainda não tenha instalado as dependências:

```powershell
composer install
npm install
```

Crie o arquivo local de ambiente, se ele ainda não existir:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

Não substitua um `.env` existente que já possua configurações locais válidas.

## 3. Iniciar o PostgreSQL

Abra o Docker Desktop e aguarde o serviço ficar disponível. Em seguida:

```powershell
docker compose up -d
docker compose ps
```

O serviço `postgres` deve aparecer com o estado `Up`.

O `docker-compose.yaml` fornece:

| Configuração | Valor |
| --- | --- |
| Host | `127.0.0.1` |
| Porta | `5433` |
| Banco | `laravel` |
| Usuário | `laravel` |
| Senha | `secret` |

## 4. Configurar o ambiente

Configure as seguintes variáveis no `.env`:

```dotenv
APP_URL=http://localhost

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5433
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=secret
```

Depois, limpe o cache de configuração:

```powershell
php artisan config:clear
```

## 5. Criar as tabelas e dados de demonstração

Em um banco novo ou vazio, execute:

```powershell
php artisan migrate --seed
```

Esse comando deve:

- criar as tabelas `tenants`, `domains`, `users` e `transactions`;
- criar os tenants `acme` e `globex`;
- criar um administrador para cada tenant;
- criar cinco transações para cada administrador.

Credenciais criadas:

| Tenant | Domínio | E-mail | Senha |
| --- | --- | --- | --- |
| `acme` | `acme.localhost` | `admin@acme.test` | `password` |
| `globex` | `globex.localhost` | `admin@globex.test` | `password` |

### Recriar completamente o banco

Somente use o comando abaixo se puder apagar todos os dados do banco configurado no `.env`:

```powershell
php artisan migrate:fresh --seed
```

> `migrate:fresh` exclui todas as tabelas e todos os dados do banco selecionado.

## 6. Executar os testes automatizados em banco isolado

O `phpunit.xml` usa SQLite em memória por padrão. Caso seu PHP tenha `pdo_sqlite`, basta executar:

```powershell
php artisan test tests/Feature/Models/TransactionTest.php
```

Para verificar se a extensão está instalada:

```powershell
php -m | Select-String pdo_sqlite
```

Se nenhum resultado aparecer, use um banco PostgreSQL exclusivo para testes conforme os passos abaixo.

### 6.1. Criar o banco de testes

O comando precisa ser executado apenas na primeira vez:

```powershell
docker compose exec postgres createdb -U laravel transaction_testing
```

Se o comando informar que o banco já existe, prossiga normalmente.

### 6.2. Configurar as variáveis apenas no terminal atual

Não é necessário alterar o `.env`:

```powershell
$env:DB_CONNECTION = "pgsql"
$env:DB_HOST = "127.0.0.1"
$env:DB_PORT = "5433"
$env:DB_DATABASE = "transaction_testing"
$env:DB_USERNAME = "laravel"
$env:DB_PASSWORD = "secret"
```

### 6.3. Executar os testes do domínio

```powershell
php vendor/bin/phpunit tests/Feature/Models/TransactionTest.php
```

O resultado esperado é:

```text
Tests: 5, Assertions: 13
```

Os testes validam:

- cast do valor monetário para duas casas decimais;
- conversão do status para `TransactionStatus`;
- relacionamentos entre tenant, usuário e transação;
- escopo automático de usuários e transações pelo tenant ativo;
- bloqueio no banco de vínculos entre tenants diferentes;
- soft delete;
- possibilidade de repetir um e-mail em tenants diferentes.

## 7. Testar migrations e seeder no banco isolado

Com as variáveis de `transaction_testing` ainda definidas no PowerShell:

```powershell
php artisan migrate:fresh --seed --force
```

Esse comando é seguro apenas se `DB_DATABASE` estiver apontando para `transaction_testing`.

Confirme antes de executá-lo:

```powershell
Write-Output $env:DB_DATABASE
```

O resultado precisa ser:

```text
transaction_testing
```

## 8. Inspecionar os dados manualmente

Entre no PostgreSQL:

```powershell
docker compose exec postgres psql -U laravel -d laravel
```

Dentro do terminal do PostgreSQL, execute:

```sql
SELECT id FROM tenants;

SELECT tenant_id, name, email
FROM users
ORDER BY tenant_id;

SELECT tenant_id, user_id, amount, cpf, status, deleted_at
FROM transactions
ORDER BY tenant_id, created_at;
```

O resultado deve mostrar:

- dois tenants;
- um administrador em cada tenant;
- cinco transações para `acme`;
- cinco transações para `globex`;
- cada transação vinculada a um usuário do mesmo tenant.

Para sair do PostgreSQL:

```text
\q
```

## 9. Testar os models com Tinker

Abra o Tinker:

```powershell
php artisan tinker
```

### Consultar tenants e usuários

```php
App\Models\Tenant::query()->pluck('id');
App\Models\User::query()->get(['id', 'tenant_id', 'email']);
```

### Consultar uma transação

```php
$transaction = App\Models\Transaction::query()->first();
$transaction->amount;
$transaction->status;
$transaction->status->label();
$transaction->user;
$transaction->tenant;
```

O campo `status` deve ser uma instância de `App\Enums\TransactionStatus`, e `label()` deve retornar o texto em português.

### Validar o isolamento por tenant

Inicialize o tenant `acme`:

```php
$acme = App\Models\Tenant::query()->findOrFail('acme');
tenancy()->initialize($acme);
App\Models\User::query()->pluck('tenant_id')->unique();
App\Models\Transaction::query()->pluck('tenant_id')->unique();
```

As duas consultas devem retornar apenas `acme`.

Finalize o contexto:

```php
tenancy()->end();
```

Repita para `globex`:

```php
$globex = App\Models\Tenant::query()->findOrFail('globex');
tenancy()->initialize($globex);
App\Models\Transaction::query()->pluck('tenant_id')->unique();
tenancy()->end();
```

O resultado deve conter apenas `globex`.

Para sair:

```php
exit
```

## 10. Validar formatação e análise estática

Verificar a formatação PHP sem modificar arquivos:

```powershell
composer run lint:check
```

Executar a análise estática:

```powershell
composer run types:check
```

Executar as verificações completas disponíveis no projeto:

```powershell
composer test
npm run lint:check
npm run format:check
npm run types:check
npm run build
```

Alguns testes antigos da aplicação podem falhar devido à configuração ainda pendente das rotas centrais e de tenant. Para validar especificamente a implementação descrita neste documento, utilize `TransactionTest.php`.

## 11. Executar a aplicação

Para iniciar todos os serviços de desenvolvimento configurados pelo Laravel:

```powershell
composer run dev
```

Ou execute backend e frontend separadamente:

```powershell
php artisan serve
```

Em outro terminal:

```powershell
npm run dev
```

Neste estágio, os models, migrations, factories e seeders podem ser testados, mas as páginas e os endpoints do CRUD de transações ainda não foram implementados.

## Checklist

- [ ] PostgreSQL está em execução.
- [ ] O `.env` aponta para o banco correto.
- [ ] `php artisan migrate --seed` foi concluído.
- [ ] Os tenants `acme` e `globex` foram criados.
- [ ] Cada tenant possui um usuário e cinco transações.
- [ ] Os cinco testes de `TransactionTest.php` passaram.
- [ ] O valor da transação mantém duas casas decimais.
- [ ] O status é convertido para `TransactionStatus`.
- [ ] O soft delete funciona.
- [ ] O tenant ativo não enxerga dados de outro tenant.
