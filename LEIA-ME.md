# Fretar na Vercel (gratuito)

Estrutura: **dois projetos na Vercel** a partir deste mesmo repositório, mais um banco no Neon.

```
frontend/  -> site React (projeto 2 na Vercel)
backend/   -> API Laravel em container (projeto 1 na Vercel)
banco      -> Neon (PostgreSQL gratuito)
```

O site chama `/api/...` no próprio endereço dele; a Vercel repassa essas chamadas ao back.
Por isso não há problema de CORS e o app instalado no celular tem um endereço fixo.

## Passo 1 — Banco no Neon
1. Crie uma conta em https://neon.com e um projeto novo.
2. Na tela "Connect", **desmarque "Pooled connection"** e copie a *connection string*
   (algo como `postgresql://usuario:senha@ep-xxxx.neon.tech/neondb?sslmode=require`).

## Passo 2 — Criar as tabelas no Neon (pelo seu PC)
Use a pasta do projeto que já funciona no seu computador (a que tem a pasta `vendor`).
No PowerShell, dentro dela:

```powershell
$env:DB_CONNECTION = "pgsql"
$env:DB_URL = "COLE_AQUI_A_CONNECTION_STRING"
$env:DB_SSLMODE = "require"
php artisan migrate --force
php artisan db:seed --force
```

Se o `migrate` reclamar da extensão `citext`, abra o **SQL Editor** do Neon, rode
`CREATE EXTENSION IF NOT EXISTS citext;` e repita o `migrate`.

Depois **feche esse terminal** (essas variáveis só valem nele; assim o seu `php artisan serve`
local volta a usar o banco local). O `db:seed` cria as contas de teste
`cliente@fretar.com` e `motorista@fretar.com` (senha `secret123`).

## Passo 3 — Subir o código para o GitHub
Crie um repositório **novo** (para não mexer no repositório do grupo) e envie esta pasta inteira:

```powershell
cd CAMINHO\DESTA\PASTA
git init
git add .
git status        # confira: NÃO pode aparecer .env, vendor nem node_modules
git commit -m "Fretar na Vercel"
git branch -M main
git remote add origin https://github.com/SEU_USUARIO/SEU_REPOSITORIO.git
git push -u origin main
```

## Passo 4 — Projeto do BACK na Vercel
1. Em https://vercel.com: **Add New > Project** e importe o repositório.
2. **Root Directory:** `backend`. Se pedir o Framework Preset, deixe "Other"/"Container"/"Services".
3. **Environment Variables** (adicione antes de implantar):

| Nome | Valor |
|---|---|
| `APP_KEY` | resultado de `php artisan key:generate --show` (começa com `base64:`) |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | o endereço do projeto (pode ajustar depois) |
| `DB_CONNECTION` | `pgsql` |
| `DB_URL` | a connection string do Neon (sem pooler) |
| `DB_SSLMODE` | `require` |
| `STRIPE_MOCK_ENABLED` | `true` = pagamento simulado (padrão). Para usar a Stripe em modo de teste: `false` |
| `STRIPE_SECRET_KEY` | (opcional) chave secreta de teste `sk_test_...`. Só no back, nunca no GitHub |

4. **Deploy.** Quando terminar, abra `https://ENDERECO-DO-BACK.vercel.app/api/health`.
   Deve aparecer `{"status":"ok"}`.
5. Se pedir login da Vercel nesse endereço: Settings > Deployment Protection > desative
   "Vercel Authentication" **deste projeto do back**. Use sempre o endereço de produção
   (o curto, `nome-do-projeto.vercel.app`), não o endereço longo de cada deploy.

## Passo 5 — Apontar o site para o back
Abra `frontend/vercel.json` e troque `TROQUE-PELO-ENDERECO-DO-BACK.vercel.app` pelo domínio de
produção do back (por exemplo `fretar-api.vercel.app`). Mantenha `https://` e `/api/:path*` como estão.
Depois envie a alteração (a Vercel reimplanta sozinha):

```powershell
git add frontend/vercel.json
git commit -m "Aponta o site para o back"
git push
```

## Passo 6 — Projeto do SITE na Vercel
**Add New > Project**, importe o **mesmo** repositório, com **Root Directory:** `frontend`
(o Framework Preset deve aparecer como Vite). Variável de ambiente, só se for usar a Stripe:
`VITE_STRIPE_PUBLISHABLE_KEY` = `pk_test_...` (chave **pública**; ela precisa existir **antes** do build).
**Deploy.**

> Stripe é opcional. Sem as chaves, o pagamento fica simulado. Veja o guia `LEIA-ME-STRIPE.md`
> do pacote de Stripe para criar a conta e as chaves de teste.

## Passo 7 — Testar
Abra o endereço do site, cadastre um cliente e um motorista, peça um frete e aceite no outro
celular. No celular, instale o app pelo menu do navegador ("Instalar app").
Para a apresentação, use o endereço curto de produção do site.

## Limites e cuidados
- Recursos em **beta** na Vercel (execução de container). Se mudarem, veja a documentação da Vercel.
- Depois de ~5 minutos sem uso o back "dorme": a primeira chamada pode levar alguns segundos.
  Antes de apresentar, abra o site e faça um login para "acordar" tudo.
- O plano gratuito da Vercel é para projetos pessoais, sem fins comerciais.
- O Neon gratuito tem 0,5 GB e cota de horas de computação por mês.
- Sem WebSocket: o app já atualiza por consulta a cada poucos segundos.
- Sessão e cache usam memória (a API usa tokens): não há login "preso" a um servidor.
