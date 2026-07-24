# Design técnico — Gestão de transações

## Visão geral

A solução utilizará o Laravel para persistência, validação, autorização, upload e exclusão lógica, e React com Inertia para as telas.

```text
React / Inertia
  ↓
Rotas autenticadas de transações
  ↓
Requests + TransactionController
  ↓
Escopo do tenant + TransactionPolicy
  ↓
Banco de dados e storage privado
```

O tenant e o usuário criador sempre serão derivados do usuário autenticado. O front-end não será fonte confiável para esses identificadores.

## Arquitetura proposta

### Front-end

```text
resources/js/
├── pages/transactions/
│   ├── index.tsx
│   ├── show.tsx
│   ├── create.tsx
│   └── edit.tsx
├── components/transactions/
│   ├── transaction-list.tsx
│   ├── transaction-actions.tsx
│   ├── transaction-form.tsx
│   └── delete-transaction-dialog.tsx
└── types/
    └── transaction.ts
```

### Back-end

```text
app/
├── Enums/
│   └── TransactionStatus.php
├── Http/
│   ├── Controllers/
│   │   └── TransactionController.php
│   ├── Requests/
│   │   ├── StoreTransactionRequest.php
│   │   └── UpdateTransactionRequest.php
│   └── Resources/
│       └── TransactionResource.php
├── Models/
│   └── Transaction.php
└── Policies/
    └── TransactionPolicy.php
```

## Modelo de dados

Tabela `transactions`:

```text
id                      bigint unsigned, primary key
tenant_id               bigint unsigned, foreign key, index
user_id                 bigint unsigned, foreign key, index
amount                  decimal(15, 2)
cpf                     char(11)
document_path           string
document_original_name  string
document_mime_type      string
status                  string, index
created_at              timestamp
updated_at              timestamp
deleted_at              timestamp nullable, index
```

Relacionamentos:

```text
Tenant 1 ─── N Transactions
User   1 ─── N Transactions
```

O status será representado por um enum com os valores:

```php
enum TransactionStatus: string
{
    case Processing = 'processing';
    case Approved = 'approved';
    case Denied = 'denied';
}
```

O model deve usar `SoftDeletes`, casts para `amount` e `status`, e relacionamentos `tenant()` e `creator()`.

## Rotas

As rotas devem ficar dentro dos middlewares de autenticação e inicialização do tenant.

```php
Route::middleware(['auth', InitializeTenancyFromUser::class])
    ->group(function () {
        Route::apiResource('transactions', TransactionController::class);
        Route::get(
            'transactions/{transaction}/document',
            [TransactionController::class, 'document'],
        )->name('transactions.document');
    });
```

Se a aplicação usar rotas web do Inertia para renderização, elas devem aplicar os mesmos middlewares e regras. A API descrita na especificação permanece o contrato conceitual das operações.

## Isolamento por tenant

A busca do recurso deve incluir o tenant antes de retornar o model:

```php
$transaction = Transaction::query()
    ->whereBelongsTo($request->user()->tenant)
    ->findOrFail($id);
```

Não utilizar `Transaction::findOrFail($id)` isoladamente. Route model binding sem escopo também não é suficiente.

A aplicação pode centralizar o filtro em um binding customizado ou em um serviço de consulta, desde que testes garantam o comportamento. A `TransactionPolicy` deve repetir a comparação entre `user.tenant_id` e `transaction.tenant_id` como defesa adicional.

O retorno `404` para registros de outro tenant evita revelar a existência do recurso.

## Validação

### StoreTransactionRequest

```php
return [
    'amount' => ['required', 'decimal:0,2', 'gt:0', 'max:9999999999999.99'],
    'cpf' => ['required', 'string', new Cpf],
    'status' => ['required', Rule::enum(TransactionStatus::class)],
    'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
];
```

### UpdateTransactionRequest

Usa as mesmas regras, com `document` opcional. A preparação para validação deve remover pontuação do CPF. Campos como `tenant_id`, `user_id`, `created_at` e `deleted_at` não devem fazer parte dos dados atribuíveis em massa.

A validação do CPF deve conferir quantidade de dígitos e dígitos verificadores, rejeitando sequências repetidas.

## Cadastro

Fluxo proposto:

1. validar os dados e o arquivo;
2. obter usuário e tenant da requisição autenticada;
3. gerar nome aleatório e salvar o documento no disco privado;
4. criar a transação dentro de uma transação de banco;
5. se o banco falhar, remover o arquivo recém-gravado;
6. retornar sucesso e redirecionar para `/transactions`.

Exemplo conceitual:

```php
$path = $request->file('document')->store(
    "tenants/{$request->user()->tenant_id}/transactions",
    'local',
);

try {
    $transaction = DB::transaction(fn () => Transaction::create([
        'tenant_id' => $request->user()->tenant_id,
        'user_id' => $request->user()->id,
        'amount' => $request->validated('amount'),
        'cpf' => only_digits($request->validated('cpf')),
        'status' => $request->validated('status'),
        'document_path' => $path,
        'document_original_name' => $request->file('document')->getClientOriginalName(),
        'document_mime_type' => $request->file('document')->getMimeType(),
    ]));
} catch (Throwable $exception) {
    Storage::disk('local')->delete($path);
    throw $exception;
}
```

O nome original nunca deve formar o caminho físico.

## Edição e substituição do documento

Na edição, o documento atual é mantido quando nenhum novo arquivo é enviado. Quando houver substituição:

1. validar e armazenar o novo arquivo;
2. atualizar o registro dentro de uma transação de banco;
3. remover o arquivo novo se a atualização falhar;
4. após o commit, remover o arquivo anterior.

O `user_id`, o `tenant_id` e o `created_at` permanecem inalterados.

## Exclusão lógica

O model deve usar:

```php
use SoftDeletes;
```

O endpoint `destroy` chama `$transaction->delete()`. A consulta comum do Eloquent exclui registros com `deleted_at` preenchido. O arquivo é preservado para manter consistência com a possibilidade de auditoria ou restauração futura.

O diálogo de confirmação pertence ao front-end. O back-end não deve depender dessa confirmação para garantir autorização.

## Download seguro

O arquivo deve ficar no disco privado. O endpoint de documento deve:

1. buscar a transação com escopo de tenant e sem registros excluídos;
2. autorizar a ação `view`;
3. verificar a existência do arquivo;
4. responder com download usando o nome original e o MIME type persistido.

Nenhum caminho interno deve aparecer na resposta da API.

## Listagem

A consulta deve selecionar somente os campos necessários, ordenar por `created_at desc` e `id desc` para desempate e paginar:

```php
Transaction::query()
    ->where('tenant_id', $request->user()->tenant_id)
    ->latest('created_at')
    ->latest('id')
    ->paginate(15);
```

A tabela possui duas colunas de dados — Data e hora e Valor — mais a célula sem cabeçalho destinada ao menu de ações. O botão do menu deve impedir a propagação do clique para não abrir a visualização involuntariamente.

## Front-end React

### Lista

A página `/transactions` deve conter:

- título e botão Nova transação;
- tabela responsiva com data/hora e valor;
- botão de três pontos com `aria-label="Ações da transação"`;
- menu Ver, Editar e Excluir com navegação por teclado;
- paginação;
- skeleton ou indicador de carregamento;
- mensagem e chamada para cadastro no estado vazio;
- mensagem de erro com opção de tentar novamente.

Datas devem ser formatadas com `Intl.DateTimeFormat('pt-BR', ...)` e valores com `Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' })`.

### Formulário

O mesmo componente atende cadastro e edição. Deve conter:

- valor com rótulo explícito e tratamento monetário sem perda de precisão;
- CPF com máscara apenas visual;
- seletor de status;
- upload de documento com tipos aceitos e limite informado;
- nome e link do arquivo atual na edição;
- botão Salvar com estado de processamento;
- botão Cancelar/Voltar.

Erros `422` devem aparecer próximos aos campos. Um envio em andamento não pode ser repetido.

### Visualização

A página deve exibir todos os campos públicos e oferecer Voltar e Editar. O CPF deve ser formatado e o documento deve ser acessado pelo endpoint autorizado.

### Exclusão

O diálogo deve identificar a transação, explicar que ela sairá da lista e oferecer Cancelar e Excluir. O foco deve ser preso ao diálogo e devolvido ao acionador ao fechar.

## Segurança e privacidade

- Não confiar em IDs de tenant ou usuário vindos do cliente.
- Não expor o caminho físico do documento.
- Validar conteúdo, extensão e tamanho do arquivo no servidor.
- Escapar o nome original ao produzir o cabeçalho de download.
- Não registrar CPF completo ou conteúdo do documento em logs.
- Aplicar autenticação, tenant e Policy em leitura e escrita.
- Utilizar proteção CSRF nas operações mutáveis baseadas em sessão.
- Considerar criptografia do CPF em repouso conforme os requisitos de segurança e privacidade do ambiente.

## Testes de back-end

Criar testes para:

- listagem ordenada e paginada;
- filtro de registros excluídos;
- visualização e cadastro válidos;
- normalização e validação do CPF;
- precisão e validação do valor;
- todos os status permitidos e rejeição de status inválido;
- arquivo obrigatório no cadastro e opcional na edição;
- tipo e tamanho do documento;
- armazenamento privado e download autorizado;
- substituição segura do documento;
- usuário e tenant obtidos da sessão;
- imutabilidade de campos do sistema;
- edição e soft delete;
- preservação do arquivo após soft delete;
- resposta `404` em todas as operações entre tenants;
- resposta adequada para usuário não autenticado.

Usar `Storage::fake()` para evitar arquivos reais nos testes.

## Testes de front-end

Criar testes para:

- renderização e formatação da lista;
- clique na linha e ação Ver;
- abertura e navegação por teclado do menu;
- formulário de cadastro e edição;
- estados de processamento e erros de validação;
- estado vazio e paginação;
- confirmação, cancelamento e sucesso da exclusão;
- ausência de `tenant_id` e `user_id` no payload;
- link de download autorizado.

## Decisões técnicas

- usar `decimal(15, 2)` para valores monetários;
- persistir CPF normalizado como texto de 11 caracteres;
- usar enum para status;
- armazenar documento em disco privado;
- usar uma tabela de transações com metadados do arquivo, sem tabela adicional;
- usar `SoftDeletes` e preservar o documento na exclusão;
- usar página dedicada para visualização, com opção de voltar;
- usar paginação no servidor;
- filtrar toda consulta pelo tenant e usar Policy como camada adicional;
- obter usuário criador e tenant exclusivamente da sessão.
