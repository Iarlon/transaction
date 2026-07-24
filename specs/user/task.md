Atividade — Implementar login com autenticação e tenant
Objetivo da atividade

Implementar a funcionalidade completa de login, contemplando:

back-end em Laravel;
autenticação por sessão;
identificação automática do tenant;
autorização e isolamento por tenant;
tela de login em React;
integração entre front-end e back-end;
testes automatizados.
T001 — Preparar relacionamento entre usuário e tenant

Confirmar que users possui tenant_id

Criar relacionamento User belongsTo Tenant

Criar relacionamento Tenant hasMany Users

Garantir que todos os usuários de teste possuam tenant

Garantir que o tenant possua indicador de ativo

Resultado esperado

O tenant de um usuário deve poder ser obtido por:

$user->tenant;
T002 — Criar validação do login

Criar LoginRequest

Validar e-mail obrigatório

Validar formato do e-mail

Validar senha obrigatória

Normalizar o e-mail

Garantir que tenant_id não seja utilizado

Resultado esperado

Requisições inválidas devem retornar 422.

T003 — Implementar endpoint de login

Criar AuthController

Criar rota POST /api/login

Validar credenciais com Laravel

Retornar mensagem genérica para erro

Obter tenant pelo usuário

Validar tenant ativo

Criar sessão

Regenerar sessão

Armazenar contexto do tenant

Retornar usuário, tenant e redirect_to

Resultado esperado

Credenciais válidas devem retornar 200 e criar uma sessão autenticada.

T004 — Implementar middleware de tenant

Criar InitializeTenantFromUser

Exigir usuário autenticado

Obter tenant pelo usuário

Validar tenant ativo

Disponibilizar tenant na requisição

Rejeitar ausência de tenant

Adicionar middleware às rotas privadas

Resultado esperado

Rotas protegidas devem possuir usuário e tenant válidos.

T005 — Implementar autorização por tenant

Garantir que entidades privadas possuam tenant_id

Filtrar consultas pelo tenant autenticado

Criar Policy para transações

Impedir consulta de registros de outro tenant

Retornar 403 ou 404 para acesso indevido

Não confiar em tenant_id enviado pelo cliente

Resultado esperado

Um usuário do tenant A não deve acessar registros do tenant B.

T006 — Criar tela de login em React

Criar LoginPage

Criar campo de e-mail

Criar campo de senha

Criar botão de login

Criar estado de carregamento

Criar apresentação de erros

Desabilitar botão durante envio

Redirecionar para /transactions

Resultado esperado

O usuário deve conseguir enviar as credenciais e navegar após o login.

T007 — Criar serviço de autenticação no React

Criar authService

Configurar cliente HTTP

Habilitar withCredentials

Obter cookie CSRF quando necessário

Enviar somente e-mail e senha

Tratar respostas de erro

Resultado esperado

O cookie de sessão deve ser recebido e enviado nas próximas requisições.

T008 — Configurar sessão, CORS e CSRF

Configurar origem permitida do React

Habilitar credenciais no CORS

Configurar domínio da sessão

Configurar HttpOnly

Configurar Secure em produção

Configurar SameSite

Validar proteção CSRF

Resultado esperado

React e Laravel devem compartilhar a sessão corretamente.

T009 — Criar testes do back-end

Testar login válido

Testar usuário inexistente

Testar senha incorreta

Testar campos inválidos

Testar usuário sem tenant

Testar tenant inativo

Testar sessão criada

Testar sessão regenerada

Testar rota protegida

Testar isolamento entre tenants

Testar tentativa de informar outro tenant

Resultado esperado

Todos os cenários de autenticação e isolamento devem possuir cobertura automatizada.

T010 — Criar testes do React

Testar renderização

Testar preenchimento dos campos

Testar envio das credenciais

Testar estado de carregamento

Testar mensagem de erro

Testar redirecionamento

Testar que tenant_id não é enviado

Resultado esperado

O fluxo principal da tela deve estar validado por testes automatizados.

Definição de pronto

A atividade será considerada concluída quando:

o login funcionar com usuários existentes;
o tenant for identificado automaticamente;
a autenticação utilizar sessão;
a sessão for regenerada;
rotas privadas exigirem autenticação;
consultas estiverem isoladas por tenant;
usuários não acessarem dados de outros tenants;
a tela React estiver integrada ao endpoint;
o usuário for redirecionado para /transactions;
testes de back-end estiverem passando;
testes de front-end estiverem passando;
nenhuma criação de usuário ou tenant tiver sido implementada.