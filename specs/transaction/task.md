# Atividade — Implementar gestão de transações

## Objetivo da atividade

Implementar a funcionalidade completa de transações, contemplando:

- modelo e persistência em Laravel;
- isolamento e autorização por tenant;
- upload privado de documento;
- listagem, visualização, cadastro, edição e soft delete;
- telas React integradas ao back-end;
- testes automatizados.

## T001 — Criar domínio e banco de dados

- Criar enum `TransactionStatus` com `processing`, `approved` e `denied`.
- Criar migration da tabela `transactions`.
- Adicionar chaves para tenant e usuário criador.
- Adicionar `amount` como `decimal(15, 2)`.
- Adicionar CPF como `char(11)`.
- Adicionar metadados do documento.
- Adicionar status, timestamps e `softDeletes`.
- Criar índices para tenant, status e exclusão lógica.
- Criar model `Transaction` com casts, fillable controlado e `SoftDeletes`.
- Criar relacionamentos com `Tenant` e `User`.
- Criar factory para testes.

**Resultado esperado:** uma transação deve persistir valor, CPF, documento e status, vinculada ao tenant e ao usuário criador.

## T002 — Implementar validação

- Criar `StoreTransactionRequest`.
- Criar `UpdateTransactionRequest`.
- Normalizar o CPF para 11 dígitos.
- Validar os dígitos verificadores do CPF.
- Validar valor positivo com até duas casas decimais.
- Validar status pelo enum.
- Exigir documento no cadastro e torná-lo opcional na edição.
- Aceitar PDF, JPEG e PNG com até 10 MB.
- Ignorar ou rejeitar campos de sistema enviados pelo cliente.
- Autorizar os requests conforme usuário, tenant e recurso.

**Resultado esperado:** requisições inválidas devem retornar `422` sem alterar banco ou storage.

## T003 — Implementar armazenamento de documentos

- Configurar e utilizar disco privado.
- Gerar nome físico seguro e aleatório.
- Organizar os arquivos por tenant sem confiar no payload.
- Persistir nome original e MIME type.
- Remover arquivo recém-criado quando a persistência falhar.
- Implementar substituição segura na edição.
- Criar endpoint de download autorizado.
- Não expor caminho físico em respostas.

**Resultado esperado:** documentos devem ser gravados e baixados somente por fluxos autenticados e autorizados.

## T004 — Implementar autorização e escopo de tenant

- Criar `TransactionPolicy`.
- Registrar ou descobrir a Policy conforme a convenção do Laravel.
- Exigir autenticação e tenant válido em todas as rotas.
- Filtrar listas pelo tenant autenticado.
- Aplicar escopo de tenant antes de buscar por ID.
- Retornar `404` para recurso inexistente, excluído ou de outro tenant.
- Impedir alteração de `tenant_id`, `user_id` e `created_at`.
- Cobrir visualização, edição, exclusão e download.

**Resultado esperado:** nenhum usuário deve descobrir ou operar transações de outro tenant.

## T005 — Implementar listagem no back-end

- Criar `TransactionController@index`.
- Selecionar apenas ID, data de criação e valor para a lista.
- Ordenar por data de criação e ID, em ordem decrescente.
- Paginar com 15 registros por página.
- Criar `TransactionResource` para padronizar a resposta.
- Excluir registros com `deleted_at` preenchido.
- Criar rota `GET /api/transactions`.

**Resultado esperado:** a API deve retornar uma lista paginada e ordenada, restrita ao tenant autenticado.

## T006 — Implementar cadastro no back-end

- Criar `TransactionController@store`.
- Obter tenant e usuário da sessão.
- Salvar o arquivo no disco privado.
- Persistir a transação de modo consistente com o arquivo.
- Retornar `201`, mensagem, ID e redirecionamento.
- Criar rota `POST /api/transactions`.

**Resultado esperado:** uma requisição válida deve criar a transação para o tenant e usuário autenticados.

## T007 — Implementar visualização e download no back-end

- Criar `TransactionController@show`.
- Retornar todos os campos públicos e o usuário criador.
- Retornar URL autorizada, sem revelar o caminho do arquivo.
- Criar `TransactionController@document`.
- Validar tenant, Policy e existência do documento.
- Criar rotas de visualização e download.

**Resultado esperado:** somente um usuário do mesmo tenant deve visualizar a transação e baixar seu documento.

## T008 — Implementar edição no back-end

- Criar `TransactionController@update`.
- Atualizar somente valor, CPF, status e documento opcional.
- Manter o documento atual quando nenhum novo arquivo for enviado.
- Trocar o documento sem deixar arquivo órfão em caso de erro.
- Manter usuário criador, tenant e data de criação.
- Retornar mensagem e redirecionamento.
- Criar rota `PUT /api/transactions/{transaction}`.

**Resultado esperado:** somente os campos editáveis devem ser modificados e a operação deve manter consistência entre banco e storage.

## T009 — Implementar exclusão lógica no back-end

- Criar `TransactionController@destroy`.
- Autorizar o recurso com escopo de tenant.
- Executar `delete()` com `SoftDeletes`.
- Preservar o documento.
- Retornar mensagem de sucesso.
- Criar rota `DELETE /api/transactions/{transaction}`.

**Resultado esperado:** `deleted_at` deve ser preenchido e a transação deve desaparecer das consultas comuns sem apagar seu arquivo.

## T010 — Criar página de listagem em React

- Criar rota e página `/transactions`.
- Torná-la o destino após o login.
- Criar botão Nova transação.
- Exibir as colunas Data e hora e Valor.
- Formatar data/hora em `pt-BR` e valor em BRL.
- Tornar cada linha clicável para visualização.
- Criar estados de carregamento, erro e lista vazia.
- Implementar paginação.
- Garantir layout responsivo e navegação por teclado.

**Resultado esperado:** após o login, o usuário deve visualizar e navegar pelas transações do seu tenant.

## T011 — Criar menu de ações e exclusão em React

- Adicionar botão de três pontos verticais ao fim de cada linha.
- Adicionar rótulo acessível ao botão.
- Criar opções Ver, Editar e Excluir.
- Impedir que o clique no menu acione o clique da linha.
- Criar diálogo de confirmação acessível.
- Não enviar exclusão ao cancelar.
- Atualizar a lista e exibir feedback ao excluir com sucesso.
- Apresentar erro sem remover a linha quando a API falhar.

**Resultado esperado:** todas as ações devem ser acessíveis pelo menu e a exclusão só deve ocorrer após confirmação.

## T012 — Criar formulário de transação em React

- Criar componente compartilhado de formulário.
- Criar páginas de cadastro e edição.
- Implementar campo monetário sem perda de precisão.
- Implementar máscara visual para CPF.
- Implementar seletor de status.
- Implementar upload com tipos e limite informados.
- Exibir o documento atual na edição.
- Enviar `multipart/form-data`.
- Desabilitar o envio durante o processamento.
- Apresentar erros junto aos campos.
- Redirecionar para `/transactions` após sucesso.
- Não enviar `tenant_id` ou `user_id`.

**Resultado esperado:** o mesmo formulário deve cadastrar e editar uma transação com validação e feedback adequados.

## T013 — Criar página de visualização em React

- Criar página `/transactions/{id}`.
- Exibir data e hora, valor, CPF, status e usuário criador.
- Exibir nome e link autorizado do documento.
- Criar ações Voltar e Editar.
- Tratar carregamento, erro e registro não encontrado.

**Resultado esperado:** o usuário deve visualizar todos os dados permitidos e voltar à lista.

## T014 — Criar testes do back-end

- Testar listagem, ordem, paginação e estado excluído.
- Testar cadastro válido e validações.
- Testar normalização e dígitos do CPF.
- Testar precisão do valor e status permitidos.
- Testar arquivo obrigatório, tipo e tamanho.
- Testar storage privado e download.
- Testar edição com e sem substituição do documento.
- Testar rollback/limpeza de arquivos em falhas.
- Testar imutabilidade dos campos de sistema.
- Testar soft delete e preservação do documento.
- Testar usuário não autenticado e tenant inválido.
- Testar isolamento entre tenants em todos os endpoints.
- Usar `Storage::fake()`.

**Resultado esperado:** os fluxos, validações, arquivos e limites de autorização devem possuir cobertura automatizada.

## T015 — Criar testes do React

- Testar renderização e formatação da lista.
- Testar clique na linha e opção Ver.
- Testar menu por mouse e teclado.
- Testar paginação e estado vazio.
- Testar cadastro e edição.
- Testar upload e erros `422`.
- Testar bloqueio durante envio.
- Testar confirmação e cancelamento da exclusão.
- Testar atualização da lista após exclusão.
- Testar que IDs de usuário e tenant não são enviados.
- Testar visualização e link do documento.

**Resultado esperado:** os fluxos principais e estados de interface devem estar validados por testes automatizados.

## T016 — Executar verificação final

- Executar testes do back-end.
- Executar testes do front-end.
- Executar lint, formatação e verificação de tipos.
- Revisar rotas e respostas contra a especificação.
- Verificar manualmente cadastro, edição, visualização e exclusão.
- Verificar acesso cruzado entre dois tenants.
- Verificar acessibilidade do menu e do diálogo.

**Resultado esperado:** todas as verificações devem passar sem erros ou acesso indevido.

## Definição de pronto

A atividade será considerada concluída quando:

- o login redirecionar para a lista de transações;
- a lista mostrar data/hora e valor, com paginação e menu de ações;
- o usuário puder visualizar, cadastrar e editar transações;
- o formulário validar valor, CPF, documento e status;
- o usuário criador e o tenant forem definidos pela sessão;
- os documentos forem privados e exigirem autorização para download;
- a exclusão solicitar confirmação e preencher `deleted_at`;
- registros excluídos deixarem de aparecer sem perder o documento;
- todas as consultas e operações estiverem isoladas por tenant;
- estados de carregamento, vazio e erro estiverem implementados;
- requisitos básicos de acessibilidade estiverem atendidos;
- testes de back-end e front-end estiverem passando;
- lint, formatação e verificação de tipos estiverem passando.
