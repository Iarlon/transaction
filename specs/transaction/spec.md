# Lista e cadastro de transações

## Objetivo

Permitir que o usuário autenticado liste, visualize, cadastre, edite e exclua transações pertencentes ao seu tenant.

A listagem deve ser a primeira tela apresentada após o login. Todas as operações devem usar o usuário e o tenant da sessão, sem aceitar que o cliente escolha `user_id` ou `tenant_id`.

## Escopo

A funcionalidade deve contemplar:

- listagem paginada de transações em `/transactions`;
- exibição da data e hora de criação e do valor em duas colunas de informação;
- menu de ações com as opções Ver, Editar e Excluir;
- página de visualização com todos os dados da transação;
- formulário de cadastro e edição;
- upload de um documento PDF ou imagem;
- status Em processamento, Aprovada ou Negada;
- exclusão lógica com `SoftDeletes` do Laravel;
- isolamento dos registros por tenant;
- registro automático do usuário criador.

## Fora do escopo

Não fazem parte desta funcionalidade:

- exclusão física de transações ou documentos;
- restauração de transações excluídas;
- alteração do usuário criador ou da data de criação;
- seleção ou troca de tenant;
- consulta de transações de outro tenant;
- processamento automático do status;
- aprovação em múltiplas etapas;
- exportação de relatórios;
- cadastro de usuários ou tenants.

## Campos de uma transação

| Campo | Origem | Regra |
| --- | --- | --- |
| `id` | Sistema | Inteiro com auto incremento. |
| `tenant_id` | Sessão | Tenant do usuário autenticado; nunca informado pelo cliente. |
| `user_id` | Sessão | Usuário autenticado no cadastro; não pode ser alterado. |
| `amount` | Formulário | Valor monetário em reais, positivo, com no máximo duas casas decimais. |
| `cpf` | Formulário | CPF do portador, persistido somente com os 11 dígitos. |
| `document_path` | Upload | Caminho interno de um PDF ou imagem armazenado em disco privado. |
| `document_original_name` | Upload | Nome original usado apenas para apresentação e download. |
| `status` | Formulário | `processing`, `approved` ou `denied`. |
| `created_at` | Sistema | Data e hora automáticas do Laravel. |
| `updated_at` | Sistema | Data e hora automáticas do Laravel. |
| `deleted_at` | Sistema | Preenchido somente na exclusão lógica. |

Rótulos exibidos no front-end:

- `processing`: Em processamento;
- `approved`: Aprovada;
- `denied`: Negada.

## Endpoints

Todas as rotas exigem autenticação por sessão e um tenant válido.

### GET /api/transactions

Retorna somente transações não excluídas do tenant autenticado, ordenadas da mais recente para a mais antiga e paginadas.

Resposta de sucesso — `200 OK`:

```json
{
  "data": [
    {
      "id": 42,
      "created_at": "2026-07-22T14:30:00-03:00",
      "amount": "1250.90"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  }
}
```

### GET /api/transactions/{transaction}

Retorna os dados completos de uma transação do tenant autenticado.

Resposta de sucesso — `200 OK`:

```json
{
  "data": {
    "id": 42,
    "created_at": "2026-07-22T14:30:00-03:00",
    "amount": "1250.90",
    "cpf": "12345678909",
    "status": "processing",
    "document": {
      "name": "comprovante.pdf",
      "download_url": "/api/transactions/42/document"
    },
    "created_by": {
      "id": 7,
      "name": "Usuário"
    }
  }
}
```

### POST /api/transactions

Cria uma transação. A requisição deve usar `multipart/form-data`.

Campos:

```text
amount=1250.90
cpf=123.456.789-09
status=processing
document=<arquivo>
```

Resposta de sucesso — `201 Created`:

```json
{
  "message": "Transação cadastrada com sucesso.",
  "data": {
    "id": 42
  },
  "redirect_to": "/transactions"
}
```

### PUT /api/transactions/{transaction}

Atualiza `amount`, `cpf`, `status` e, opcionalmente, o documento. Como navegadores e servidores podem ter limitações com upload em `PUT`, o cliente pode enviar `POST` com `_method=PUT` e `multipart/form-data`.

Se nenhum novo documento for enviado, o documento atual deve ser mantido. `tenant_id`, `user_id`, `created_at` e `deleted_at` não podem ser alterados pelo cliente.

Resposta de sucesso — `200 OK`:

```json
{
  "message": "Transação atualizada com sucesso.",
  "data": {
    "id": 42
  },
  "redirect_to": "/transactions"
}
```

### DELETE /api/transactions/{transaction}

Efetua exclusão lógica. A confirmação acontece no front-end antes da chamada.

Resposta de sucesso — `200 OK`:

```json
{
  "message": "Transação excluída com sucesso."
}
```

### GET /api/transactions/{transaction}/document

Entrega o documento após validar autenticação e tenant. O arquivo não deve possuir URL pública direta.

## Respostas de erro

- `401 Unauthorized`: usuário não autenticado;
- `403 Forbidden`: usuário sem tenant válido;
- `404 Not Found`: transação inexistente, excluída ou pertencente a outro tenant;
- `422 Unprocessable Entity`: campos inválidos ou arquivo não aceito.

Exemplo de validação:

```json
{
  "message": "Os dados informados são inválidos.",
  "errors": {
    "cpf": ["O CPF informado é inválido."]
  }
}
```

## Requisitos funcionais

### TRX-FR-001

Após o login, o sistema deve redirecionar o usuário para `/transactions`.

### TRX-FR-002

A tela deve listar apenas transações não excluídas do tenant autenticado.

### TRX-FR-003

A listagem deve apresentar duas colunas de informação: data e hora da criação e valor da transação.

### TRX-FR-004

O valor deve ser exibido no formato monetário brasileiro e a data no fuso configurado pela aplicação.

### TRX-FR-005

Ao clicar em uma linha, o sistema deve abrir a página de visualização da transação, com opção de voltar à lista.

### TRX-FR-006

Cada linha deve possuir um botão de três pontos verticais, com rótulo acessível, que abra as ações Ver, Editar e Excluir.

### TRX-FR-007

Acionar Ver deve abrir a mesma visualização disponível ao clicar na linha.

### TRX-FR-008

Acionar Editar deve abrir o formulário preenchido com os dados atuais.

### TRX-FR-009

Acionar Excluir deve apresentar uma confirmação antes de enviar a solicitação.

### TRX-FR-010

Após a confirmação, o back-end deve executar exclusão lógica e a linha deve deixar de aparecer na lista.

### TRX-FR-011

O formulário deve permitir informar valor, CPF, documento e status.

### TRX-FR-012

O sistema deve validar os campos no back-end e apresentar os erros junto aos respectivos campos.

### TRX-FR-013

Ao cadastrar, o sistema deve atribuir automaticamente o usuário e o tenant autenticados.

### TRX-FR-014

Após salvar com sucesso, o usuário deve voltar à lista e visualizar a transação cadastrada ou atualizada.

### TRX-FR-015

A página de visualização deve exibir data e hora, valor, CPF formatado, documento, status e usuário criador.

### TRX-FR-016

O download do documento deve exigir a mesma autorização da visualização da transação.

### TRX-FR-017

A lista deve possuir estados de carregamento, vazia e erro, além de paginação.

## Regras de negócio

### TRX-BR-001

Toda transação deve pertencer a exatamente um tenant e a um usuário criador.

### TRX-BR-002

`tenant_id` e `user_id` devem ser obtidos exclusivamente da sessão autenticada no cadastro.

### TRX-BR-003

Um usuário pode operar somente transações do próprio tenant. Informar diretamente o ID de outro tenant deve resultar em `404 Not Found`.

### TRX-BR-004

O valor deve ser maior que zero, limitado à precisão definida no banco e armazenado como decimal, nunca como ponto flutuante.

### TRX-BR-005

O CPF deve ser validado, normalizado para 11 dígitos antes da persistência e formatado apenas na apresentação.

### TRX-BR-006

O documento é obrigatório no cadastro e opcional na edição. Devem ser aceitos PDF, JPEG e PNG, com tamanho máximo de 10 MB.

### TRX-BR-007

O nome fornecido pelo cliente não pode ser usado como caminho no servidor. O sistema deve gerar um nome seguro e armazenar o arquivo em disco privado.

### TRX-BR-008

Ao substituir um documento, o arquivo anterior só deve ser removido depois que a atualização for concluída com sucesso.

### TRX-BR-009

Os únicos status válidos são Em processamento, Aprovada e Negada.

### TRX-BR-010

Transações excluídas logicamente não devem aparecer na lista, ser visualizadas, editadas ou excluídas novamente pelos endpoints comuns.

### TRX-BR-011

A exclusão lógica não deve apagar o documento, preservando a integridade do registro para eventual auditoria ou restauração futura.

### TRX-BR-012

O usuário criador e a data de criação são imutáveis.

## Critérios de aceitação

### TRX-AC-001 — Listagem após o login

Dado que o usuário realizou login com sucesso  
Quando o redirecionamento for concluído  
Então a rota `/transactions` deve ser exibida  
E deve listar apenas as transações ativas do tenant autenticado.

### TRX-AC-002 — Lista em duas colunas

Dado que existem transações para o tenant  
Quando a lista for carregada  
Então cada linha deve apresentar a data e hora de criação e o valor em reais  
E deve possuir o menu de ações ao final.

### TRX-AC-003 — Visualização

Dado que uma transação aparece na lista  
Quando o usuário clicar na linha ou na ação Ver  
Então a página deve exibir todos os dados da transação  
E disponibilizar a opção de voltar à lista.

### TRX-AC-004 — Cadastro válido

Dado que o usuário preencheu campos válidos  
Quando salvar a transação  
Então o sistema deve criar o registro com o usuário e o tenant da sessão  
E redirecionar para a lista  
E apresentar a nova transação.

### TRX-AC-005 — Cadastro inválido

Dado que um ou mais campos são inválidos  
Quando o usuário tentar salvar  
Então o sistema deve retornar `422 Unprocessable Entity`  
E não deve criar a transação  
E o formulário deve apresentar os erros.

### TRX-AC-006 — Edição

Dado que a transação pertence ao tenant autenticado  
Quando o usuário editar os campos e salvar  
Então os dados permitidos devem ser atualizados  
E o usuário criador e a data de criação devem permanecer inalterados.

### TRX-AC-007 — Cancelamento da exclusão

Dado que o usuário acionou Excluir  
Quando cancelar a confirmação  
Então nenhuma requisição de exclusão deve ser enviada  
E a transação deve permanecer na lista.

### TRX-AC-008 — Exclusão confirmada

Dado que o usuário confirmou a exclusão  
Quando o back-end concluir a operação  
Então `deleted_at` deve ser preenchido  
E a transação deve deixar de aparecer na lista  
E o documento deve ser preservado.

### TRX-AC-009 — Isolamento entre tenants

Dado que uma transação pertence ao tenant B  
E o usuário autenticado pertence ao tenant A  
Quando tentar listar, visualizar, editar, excluir ou baixar o documento da transação  
Então o registro não deve ser exposto  
E acessos por ID devem retornar `404 Not Found`.

### TRX-AC-010 — Documento

Dado que o usuário selecionou um arquivo permitido com até 10 MB  
Quando salvar a transação  
Então o arquivo deve ser armazenado de forma privada  
E deve poder ser baixado somente por usuário autorizado.

### TRX-AC-011 — Estado vazio

Dado que o tenant não possui transações ativas  
Quando a lista for carregada  
Então o sistema deve informar que nenhuma transação foi encontrada  
E disponibilizar a ação de cadastrar uma transação.
