Design técnico — Login e autorização por tenant
Visão geral

A solução será dividida em:

React
  ↓
POST /api/login
  ↓
Laravel Authentication
  ↓
Sessão
  ↓
Tenant do usuário
  ↓
Rotas protegidas e consultas isoladas

O React será responsável apenas pela interface e pelo envio das credenciais.

O Laravel será responsável por:

autenticação;
criação da sessão;
identificação do usuário;
identificação do tenant;
autorização;
isolamento dos dados.
Arquitetura proposta
Front-end
frontend/
└── src/
    ├── pages/
    │   └── LoginPage.jsx
    ├── components/
    │   └── LoginForm.jsx
    ├── services/
    │   └── authService.js
    ├── contexts/
    │   └── AuthContext.jsx
    └── routes/
        └── AppRoutes.jsx
Back-end
app/
├── Http/
│   ├── Controllers/
│   │   └── AuthController.php
│   ├── Requests/
│   │   └── LoginRequest.php
│   └── Middleware/
│       └── InitializeTenantFromUser.php
├── Models/
│   ├── User.php
│   └── Tenant.php
└── Policies/
Modelo de dados
Usuário

O usuário deve possuir vínculo com um tenant.

Exemplo:

users
- id
- tenant_id
- name
- email
- password
- created_at
- updated_at
Tenant
tenants
- id
- name
- active
- created_at
- updated_at

Relação esperada:

Tenant 1 ─── N Users

Cada usuário pertence a um tenant.

Fluxo de login
O React envia e-mail e senha.
O Laravel valida os campos.
O sistema normaliza o e-mail.
O sistema busca o usuário.
O sistema compara a senha com o hash.
O sistema obtém o tenant pelo usuário.
O sistema valida se o tenant existe e está ativo.
O Laravel autentica o usuário.
A sessão é regenerada.
O tenant é armazenado ou disponibilizado na sessão.
O back-end retorna os dados públicos.
O React redireciona para /transactions.
Back-end
Rota
Route::post('/login', [AuthController::class, 'login']);

As rotas protegidas devem utilizar autenticação por sessão:

Route::middleware([
    'auth',
    InitializeTenantFromUser::class,
])->group(function () {
    Route::get('/transactions', ...);
});
LoginRequest

Responsabilidades:

validar e-mail obrigatório;
validar formato do e-mail;
validar senha obrigatória;
impedir campos não permitidos quando necessário.

Exemplo de validação:

return [
    'email' => ['required', 'email'],
    'password' => ['required', 'string'],
];
AuthController

Responsabilidades:

receber o LoginRequest;
normalizar o e-mail;
tentar autenticar o usuário;
validar o tenant;
regenerar a sessão;
retornar os dados públicos.

O controller não deve:

criar usuários;
criar tenants;
aceitar tenant informado pelo cliente;
consultar dados de outros tenants;
retornar o hash da senha.

Fluxo conceitual:

public function login(LoginRequest $request)
{
    $credentials = [
        'email' => strtolower(trim($request->email)),
        'password' => $request->password,
    ];

    if (! Auth::attempt($credentials)) {
        return response()->json([
            'message' => 'E-mail ou senha inválidos.',
        ], 401);
    }

    $request->session()->regenerate();

    $user = Auth::user();
    $tenant = $user->tenant;

    if (! $tenant || ! $tenant->active) {
        Auth::logout();
        $request->session()->invalidate();

        return response()->json([
            'message' => 'Usuário sem acesso a um tenant válido.',
        ], 403);
    }

    $request->session()->put('tenant_id', $tenant->id);

    return response()->json([
        'message' => 'Login realizado com sucesso.',
        'data' => [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
            ],
            'redirect_to' => '/transactions',
        ],
    ]);
}
Middleware de tenant

O middleware deve:

verificar se existe usuário autenticado;
obter o tenant do usuário;
validar o tenant;
disponibilizar o tenant para a requisição;
impedir acesso quando o tenant não for válido.

Exemplo conceitual:

public function handle(Request $request, Closure $next)
{
    $user = $request->user();

    if (! $user || ! $user->tenant) {
        abort(403);
    }

    app()->instance(Tenant::class, $user->tenant);

    return $next($request);
}

O tenant deve ser sempre obtido pelo usuário autenticado.

O valor da sessão pode ser utilizado como apoio, mas não deve substituir a validação do vínculo atual do usuário.

Autorização

A autenticação responde:

Quem é o usuário?

A autorização por tenant responde:

Este usuário pode acessar este recurso?

Toda entidade pertencente a um tenant deve possuir tenant_id.

Exemplo:

transactions
- id
- tenant_id
- user_id
- description
- amount

Toda consulta deve considerar o tenant autenticado:

Transaction::query()
    ->where('tenant_id', $request->user()->tenant_id)
    ->get();

Para consultas por identificador:

Transaction::query()
    ->where('tenant_id', $request->user()->tenant_id)
    ->findOrFail($transactionId);

Não utilizar:

Transaction::findOrFail($transactionId);

isoladamente, pois isso pode permitir acesso a registros de outro tenant.

Policies

As Policies devem comparar o tenant do usuário com o tenant do recurso.

Exemplo:

public function view(User $user, Transaction $transaction): bool
{
    return $user->tenant_id === $transaction->tenant_id;
}

Mesmo utilizando Policies, as consultas devem continuar filtradas por tenant.

A Policy funciona como uma camada adicional de segurança.

Front-end React
Tela de login

A tela deve possuir:

campo de e-mail;
campo de senha;
botão de entrar;
mensagem de erro;
indicador de carregamento.

Exemplo conceitual:

function LoginPage() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const navigate = useNavigate();

  async function handleSubmit(event) {
    event.preventDefault();
    setLoading(true);
    setError('');

    try {
      const response = await login({
        email,
        password,
      });

      navigate(response.data.redirect_to);
    } catch (error) {
      setError(
        error.response?.data?.message ??
          'Não foi possível realizar o login.'
      );
    } finally {
      setLoading(false);
    }
  }

  return (
    <form onSubmit={handleSubmit}>
      <input
        type="email"
        value={email}
        onChange={(event) => setEmail(event.target.value)}
        placeholder="E-mail"
      />

      <input
        type="password"
        value={password}
        onChange={(event) => setPassword(event.target.value)}
        placeholder="Senha"
      />

      {error && <span>{error}</span>}

      <button type="submit" disabled={loading}>
        {loading ? 'Entrando...' : 'Entrar'}
      </button>
    </form>
  );
}
Serviço de autenticação
export async function login(credentials) {
  const response = await api.post('/api/login', credentials, {
    withCredentials: true,
  });

  return response.data;
}

A opção withCredentials deve ser utilizada para permitir o envio e recebimento do cookie de sessão quando front-end e back-end estiverem em origens diferentes.

Proteção CSRF

Como a autenticação utiliza sessão e cookie, o front-end deve obter o cookie CSRF antes do login quando a configuração utilizada exigir isso.

Fluxo esperado:

GET /sanctum/csrf-cookie
POST /api/login

Exemplo:

export async function login(credentials) {
  await api.get('/sanctum/csrf-cookie', {
    withCredentials: true,
  });

  const response = await api.post('/api/login', credentials, {
    withCredentials: true,
  });

  return response.data;
}
Configuração de CORS e sessão

O back-end deve permitir a origem do front-end.

Configurações esperadas:

supports_credentials = true

Os cookies devem utilizar:

HttpOnly
Secure em produção
SameSite adequado

O domínio da sessão deve ser compatível com a arquitetura utilizada.

Testes de back-end

Criar testes para:

login válido;
senha incorreta;
usuário inexistente;
campos obrigatórios;
usuário sem tenant;
tenant inativo;
regeneração da sessão;
armazenamento do tenant;
rota protegida sem autenticação;
rota protegida com autenticação;
acesso a recurso de outro tenant;
tentativa de informar tenant_id;
ausência de senha na resposta.
Testes de front-end

Criar testes para:

renderização dos campos;
envio do formulário;
bloqueio do botão durante carregamento;
exibição de erro;
envio apenas de e-mail e senha;
redirecionamento após sucesso;
manutenção do e-mail após falha;
limpeza opcional da senha após falha.
Decisões técnicas
utilizar sessão e cookie;
não utilizar JWT;
utilizar Laravel para autenticação;
obter tenant pelo usuário autenticado;
não permitir seleção de tenant;
utilizar middleware para inicialização do tenant;
utilizar filtros por tenant nas consultas;
utilizar Policies como proteção adicional;
utilizar React apenas para a interface;
redirecionar para /transactions após o login.