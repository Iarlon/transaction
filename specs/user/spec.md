Login e autenticação por tenant
Objetivo

Permitir que usuários previamente cadastrados realizem login utilizando e-mail e senha.

Cada usuário já possui um tenant associado no banco de dados. O tenant não deve ser informado ou selecionado durante o login.

Após a autenticação, o sistema deve criar uma sessão, identificar o tenant do usuário e permitir o acesso somente aos dados pertencentes a esse tenant.

Também deve ser criada uma tela de login em React.

Escopo

A funcionalidade deve contemplar:

tela de login em React;
campo de e-mail;
campo de senha;
envio das credenciais ao back-end;
validação das credenciais;
criação de sessão;
identificação automática do tenant;
proteção de rotas autenticadas;
autorização de acesso com base no tenant;
redirecionamento para a tela de transações após o login.
Fora do escopo

Não fazem parte desta funcionalidade:

cadastro de usuário;
cadastro de tenant;
seleção de tenant;
recuperação de senha;
autenticação com JWT;
autenticação em dois fatores;
tela de transações;
edição de usuário;
troca de tenant durante a sessão.
Endpoint
POST /api/login
Requisição
{
  "email": "usuario@email.com",
  "password": "senha"
}

O cliente não deve enviar:

{
  "tenant_id": 1,
  "user_id": 1
}

O usuário e o tenant devem ser identificados exclusivamente pelo back-end.

Resposta de sucesso
200 OK
{
  "message": "Login realizado com sucesso.",
  "data": {
    "user": {
      "id": 1,
      "name": "Usuário",
      "email": "usuario@email.com"
    },
    "tenant": {
      "id": 10,
      "name": "Empresa A"
    },
    "redirect_to": "/transactions"
  }
}

A sessão deve ser mantida por cookie.

O identificador interno da sessão não deve ser retornado.

Respostas de erro
Campos inválidos
422 Unprocessable Entity
Credenciais inválidas
401 Unauthorized
{
  "message": "E-mail ou senha inválidos."
}

A mensagem deve ser igual para:

usuário inexistente;
senha incorreta.
Usuário sem tenant válido
403 Forbidden
{
  "message": "Usuário sem acesso a um tenant válido."
}
Requisitos funcionais
AUTH-FR-001

O sistema deve disponibilizar uma tela de login com e-mail e senha.

AUTH-FR-002

O front-end deve enviar as credenciais para POST /api/login.

AUTH-FR-003

O back-end deve validar a presença e o formato dos campos.

AUTH-FR-004

O sistema deve localizar o usuário pelo e-mail informado.

AUTH-FR-005

O sistema deve validar a senha utilizando o hash armazenado.

AUTH-FR-006

O sistema deve identificar automaticamente o tenant associado ao usuário.

AUTH-FR-007

O sistema deve impedir o login caso o usuário não possua tenant válido.

AUTH-FR-008

Após a autenticação, o sistema deve criar uma sessão.

AUTH-FR-009

O identificador da sessão deve ser regenerado após o login.

AUTH-FR-010

O tenant autenticado deve estar disponível durante as próximas requisições.

AUTH-FR-011

Rotas protegidas devem exigir usuário autenticado.

AUTH-FR-012

Recursos pertencentes a um tenant não devem ser acessíveis por usuários de outro tenant.

AUTH-FR-013

Após o login, o front-end deve redirecionar o usuário para /transactions.

AUTH-FR-014

Em caso de falha, o front-end deve apresentar a mensagem retornada pelo back-end.

Regras de negócio
AUTH-BR-001

Todos os usuários devem estar previamente cadastrados.

AUTH-BR-002

Todos os tenants devem estar previamente cadastrados.

AUTH-BR-003

O tenant deve ser obtido pelo vínculo existente com o usuário.

AUTH-BR-004

O cliente não pode controlar o tenant da sessão.

AUTH-BR-005

O sistema não deve aceitar tenant_id como fonte de identificação do tenant.

AUTH-BR-006

A senha não deve ser salva na sessão ou registrada em logs.

AUTH-BR-007

A resposta não deve retornar senha ou hash da senha.

AUTH-BR-008

A sessão somente deve ser criada após a validação do usuário, da senha e do tenant.

AUTH-BR-009

Toda consulta a dados pertencentes a um tenant deve utilizar o tenant autenticado como filtro.

AUTH-BR-010

O usuário não pode acessar registros pertencentes a outro tenant, mesmo que informe diretamente o identificador do recurso.

Critérios de aceitação
AUTH-AC-001 — Login válido

Dado que o usuário está cadastrado
E possui tenant associado
Quando informar e-mail e senha corretos
Então o sistema deve criar a sessão
E retornar 200 OK.

AUTH-AC-002 — Identificação automática do tenant

Dado que o usuário pertence ao tenant A
Quando realizar login
Então o tenant A deve ser associado à sessão automaticamente.

AUTH-AC-003 — Credenciais inválidas

Dado que o e-mail ou a senha estão incorretos
Quando o login for solicitado
Então o sistema deve retornar 401 Unauthorized
E nenhuma sessão deve ser criada.

AUTH-AC-004 — Usuário sem tenant

Dado que as credenciais estão corretas
E o usuário não possui tenant associado
Quando realizar login
Então o sistema deve retornar 403 Forbidden.

AUTH-AC-005 — Tentativa de informar tenant

Dado que o usuário pertence ao tenant A
E envia o identificador do tenant B
Quando realizar login
Então o valor informado deve ser ignorado ou rejeitado
E o tenant B não deve ser utilizado.

AUTH-AC-006 — Proteção entre tenants

Dado que o usuário pertence ao tenant A
Quando tentar acessar um recurso do tenant B
Então o sistema deve retornar 403 Forbidden ou 404 Not Found.

AUTH-AC-007 — Redirecionamento

Dado que o login foi realizado com sucesso
Quando o front-end receber a resposta
Então deve redirecionar o usuário para /transactions.