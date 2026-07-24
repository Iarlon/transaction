# Transaction

Aplicação web multi-tenant para gerenciamento de transações financeiras. O sistema deve permitir que usuários previamente cadastrados façam login e gerenciem transações com isolamento de dados entre diferentes tenants.

> Este documento serve como visão geral do projeto e como contexto para pessoas e assistentes de IA. Antes de alterar o código, compare os requisitos abaixo com a implementação existente e não presuma que itens descritos como escopo já estejam concluídos.

## Objetivo

Disponibilizar um CRUD de transações autenticado, com upload de comprovantes e separação de dados por tenant. A aplicação deve permitir:

- autenticar usuários locais por e-mail e senha;
- listar e visualizar transações;
- cadastrar e editar transações;
- excluir transações por soft delete;
- armazenar um comprovante em PDF ou imagem;
- demonstrar o isolamento multi-tenant usando pelo menos dois tenants.

## Estado atual

O projeto possui o CRUD completo de transações, interface web com Inertia, autenticação web por sessão, autenticação de API por token Bearer e isolamento multi-tenant.

Recursos identificados no código atual:

- Laravel 13 e PHP 8.3 ou superior;
- React 19 com TypeScript;
- Inertia.js;
- Tailwind CSS 4;
- Laravel Fortify para autenticação web;
- Laravel Sanctum para autenticação da API;
- `stancl/tenancy` para arquitetura multi-tenant;
- configuração de PostgreSQL 16 via Docker Compose;
- layouts com menu lateral e componentes de interface;
- models `User`, `Tenant` e `Transaction` relacionados;
- enum `TransactionStatus` com os estados `processing`, `approved` e `denied`;
- tabela `transactions` com valor decimal, CPF, comprovante, status e soft delete;
- isolamento de `User` e `Transaction` por escopo global do tenant ativo;
- integridade referencial composta impedindo associar uma transação a um usuário de outro tenant;
- e-mail único dentro de cada tenant, permitindo o mesmo endereço em tenants diferentes;
- factories e dados de demonstração para dois tenants;
- testes da camada de domínio, autenticação, CRUD e isolamento multi-tenant;
- tenant inicializado pelo usuário autenticado, com suporte à seleção por domínio no login.

Observações importantes:

- o projeto utiliza banco compartilhado com `tenant_id`, cache e armazenamento de arquivos separados por tenant;
- o middleware `InitializeTenancyFromUser` inicializa o tenant a partir do usuário autenticado;
- o seeder fornece os tenants `empresa-a` e `empresa-b`, cada um com um usuário administrador e cinco transações;
- a interface web usa Inertia e a API independente utiliza JSON e tokens do Laravel Sanctum.

Credenciais locais criadas por `php artisan migrate --seed`:

| Tenant | Domínio | E-mail | Senha |
| --- | --- | --- | --- |
| `empresa-a` | `empresa-a.localhost` | `admin@empresa-a.test` | `password` |
| `empresa-b` | `empresa-b.localhost` | `admin@empresa-b.test` | `password` |

Essas credenciais são exclusivamente para desenvolvimento e demonstração.

## API autenticada

A API está disponível sob o prefixo `/api` e responde JSON. Para obter um token:

```http
POST /api/login
Accept: application/json
Content-Type: application/json

{
  "email": "admin@empresa-a.test",
  "password": "password",
  "device_name": "postman"
}
```

Envie o token retornado nas rotas protegidas:

```http
Authorization: Bearer SEU_TOKEN
Accept: application/json
```

Endpoints de autenticação:

| Método | Endpoint | Descrição |
| --- | --- | --- |
| `POST` | `/api/login` | Valida as credenciais e cria um token |
| `GET` | `/api/user` | Retorna o usuário autenticado e seu tenant |
| `POST` | `/api/logout` | Revoga o token utilizado na requisição |

O CRUD está disponível em `/api/transactions`. Listagem, visualização e exclusão usam JSON. Criação e atualização usam `multipart/form-data` quando houver documento, pois o comprovante é um arquivo; todas as respostas e erros permanecem em JSON.

## Requisitos funcionais

### 1. Login

- O acesso deve usar e-mail e senha.
- Os usuários devem estar previamente cadastrados no banco de dados.
- Não deve haver necessidade de cadastro público para utilizar o sistema.
- Credenciais inválidas devem produzir uma mensagem de erro clara, incluindo os casos de usuário inexistente ou senha incorreta.
- Após o login bem-sucedido, o usuário deve ser direcionado à lista de transações.

### 2. Lista de transações

A tela inicial após a autenticação deve apresentar as transações pertencentes ao tenant e ao escopo de acesso do usuário autenticado.

Cada item deve exibir, no mínimo:

- data e hora de criação;
- valor da transação em reais;
- status;
- menu de ações.

Ao clicar em uma transação, a aplicação deve abrir sua visualização detalhada em uma página ou modal.

O menu de ações, representado por um ícone de três pontos verticais, deve oferecer:

- **Ver:** abre os detalhes da transação;
- **Editar:** abre o formulário preenchido com os dados atuais;
- **Excluir:** solicita confirmação e, se confirmada, executa uma exclusão lógica com o recurso `SoftDeletes` do Laravel.

### 3. Visualização da transação

A página ou modal de visualização deve apresentar todos os campos da transação e permitir retornar à listagem. Quando houver um comprovante, o usuário deve conseguir acessá-lo de forma segura.

### 4. Cadastro de transação

O formulário deve permitir o preenchimento de todos os dados aplicáveis da transação. Após salvar com sucesso, o usuário deve retornar à listagem, onde a nova transação já estará disponível.

### 5. Edição de transação

O formulário de edição deve reutilizar, quando possível, a estrutura do cadastro, carregar os valores atuais e persistir apenas dados válidos. Ao concluir, a aplicação deve retornar à lista ou à visualização da transação atualizada.

### 6. Exclusão de transação

- A interface deve exigir confirmação antes da exclusão.
- A exclusão deve ser lógica, preservando o registro no banco por meio de `deleted_at`.
- Registros excluídos não devem aparecer nas consultas comuns do tenant.

## Modelo de transação

| Campo | Tipo sugerido | Regras |
| --- | --- | --- |
| `id` | Identificador incremental | Gerado pelo banco |
| `user_id` | Chave estrangeira | Usuário que criou a transação |
| `amount` | Decimal | Valor monetário positivo em reais; evitar `float` |
| `cpf` | String | CPF do portador; armazenar preferencialmente apenas dígitos e validar |
| `document_path` | String anulável | Caminho do comprovante em PDF ou imagem |
| `status` | Enum ou string controlada | `processing`, `approved` ou `denied` |
| `created_at` | Timestamp | Gerenciado pelo Laravel |
| `updated_at` | Timestamp | Gerenciado pelo Laravel |
| `deleted_at` | Timestamp anulável | Usado pelo soft delete |

Rótulos de status na interface:

| Valor interno sugerido | Rótulo |
| --- | --- |
| `processing` | Em processamento |
| `approved` | Aprovada |
| `denied` | Negada |

O projeto adota banco compartilhado com a coluna `tenant_id`. `User` e `Transaction` usam o escopo global de `BelongsToTenant`, fornecido por `stancl/tenancy`. A chave estrangeira composta (`tenant_id`, `user_id`) garante no banco que o criador e a transação pertençam ao mesmo tenant.

## Regras de negócio e segurança

- Todas as operações de transação exigem autenticação.
- O tenant deve ser resolvido no servidor, nunca aceito diretamente de um campo manipulável enviado pelo cliente.
- Consultas, validações de existência e downloads de documentos devem respeitar o tenant ativo.
- O usuário criador deve ser obtido da sessão autenticada, e não do corpo da requisição.
- Valores monetários devem usar decimal no banco e tratamento apropriado no backend.
- O CPF deve ser validado no backend.
- Uploads devem validar extensão, MIME type e tamanho máximo.
- Os arquivos enviados não devem ser executáveis e seu acesso não deve expor documentos de outros tenants.
- A API deve retornar erros de validação consistentes em JSON.
- Operações de criação, atualização e exclusão devem possuir testes automatizados, incluindo testes de isolamento entre tenants.

## Arquitetura esperada

- **Backend:** PHP 8.3+ e Laravel 13, responsável por autenticação, regras de negócio, persistência, uploads e endpoints.
- **Frontend:** React, TypeScript, Inertia.js e Tailwind CSS.
- **Banco de dados:** PostgreSQL no ambiente Docker fornecido ou outro banco compatível configurado localmente.
- **Autenticação:** Laravel Fortify para a sessão web e Laravel Sanctum para tokens da API.
- **Multi-tenancy:** `stancl/tenancy`, com no mínimo dois tenants configurados para demonstração.
- **Formato de dados:** endpoints do CRUD devem aceitar e responder JSON conforme o requisito da API, ainda que a navegação da interface utilize Inertia.

## Fluxo principal

1. Um usuário previamente cadastrado acessa o login.
2. O sistema valida e-mail e senha e determina o tenant do usuário ou do domínio.
3. O usuário é direcionado à lista de transações do tenant ativo.
4. O usuário visualiza, cria, edita ou exclui logicamente uma transação.
5. Toda operação é validada e isolada no tenant ativo.

## Ambiente local

### Pré-requisitos

- PHP 8.3 ou superior;
- Composer;
- Node.js e npm;
- Docker, caso seja utilizado o PostgreSQL disponibilizado pelo projeto.

### Instalação básica

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

No Windows PowerShell, substitua a cópia do arquivo por:

```powershell
Copy-Item .env.example .env
```

### Banco de dados

Para utilizar o PostgreSQL definido em `docker-compose.yaml`:

```bash
docker compose up -d
```

Configure o `.env` local sem versionar credenciais:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5433
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=secret
```

Em seguida:

```bash
php artisan migrate --seed
php artisan storage:link
```

### Executar a aplicação

O projeto fornece um script de desenvolvimento integrado:

```bash
composer run dev
```

Alternativamente, execute backend e frontend em terminais separados:

```bash
php artisan serve
npm run dev
```

## Verificações de qualidade

Para um roteiro detalhado de configuração, testes automatizados e validação manual, consulte [TESTING.md](TESTING.md).

```bash
composer test
npm run lint:check
npm run format:check
npm run types:check
npm run build
```

Os testes devem cobrir ao menos:

- login válido e inválido;
- listagem limitada ao tenant ativo;
- criação e validação dos campos;
- visualização e edição;
- soft delete;
- autorização de acesso ao comprovante;
- tentativa de acessar dados de outro tenant.

## Critérios de aceite

- Usuários pré-cadastrados conseguem entrar com e-mail e senha.
- Erros de autenticação são exibidos adequadamente.
- O login redireciona para a lista de transações.
- O CRUD completo funciona e respeita as validações.
- A exclusão utiliza soft delete.
- Comprovantes em PDF ou imagem podem ser enviados e acessados com segurança.
- Os dados de um tenant não são visíveis nem modificáveis por outro.
- Existem pelo menos dois tenants/instâncias utilizáveis na demonstração.
- A aplicação possui testes essenciais e pode ser executada seguindo este README.

## Entregáveis

- código desenvolvido e executável em ambiente local;
- repositório Git público;
- vídeo demonstrando:
  - login;
  - listagem, criação, visualização, edição e exclusão de transações;
  - upload ou acesso ao comprovante;
  - funcionamento de pelo menos dois tenants;
  - isolamento dos dados entre eles;
- envio por e-mail do link público do repositório.

## Orientações para assistentes de IA

Ao trabalhar neste repositório:

1. leia este arquivo e inspecione a implementação antes de propor mudanças;
2. trate a seção **Estado atual** como diagnóstico, e as demais seções como requisitos;
3. preserve o isolamento multi-tenant em models, queries, policies, validações, rotas e arquivos;
4. não confie em `tenant_id`, `user_id`, status ou caminhos de arquivo enviados pelo cliente;
5. mantenha valores monetários com precisão decimal;
6. prefira Form Requests, Resources, Policies e testes de feature idiomáticos do Laravel;
7. atualize este README quando decisões arquiteturais ou instruções de execução mudarem;
8. não declare uma funcionalidade concluída sem executar as verificações relevantes.

## Decisões implementadas

- o tenant é resolvido pelo domínio quando necessário e sempre confirmado pelo usuário autenticado;
- a visualização de transações utiliza página dedicada;
- os comprovantes são privados, separados por tenant e acessados por rota autorizada;
- são aceitos PDF, JPEG e PNG com limite de 10 MB;
- a API usa respostas JSON paginadas e autenticação Bearer;
- os tenants de demonstração são `empresa-a` e `empresa-b`.
