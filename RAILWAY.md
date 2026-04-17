# Deploy OrangeHRM no Railway

## 1. Criar o Projeto no Railway

1. Acesse [railway.app](https://railway.app) e faça login
2. Clique em **New Project**
3. Selecione **Deploy from GitHub Repo**
4. Conecte o repositório `halstenGit/orangehrm`
5. Branch de deploy: **main**

## 2. Adicionar o Serviço MySQL

No mesmo projeto Railway:

1. Clique em **+ New** → **Database** → **MySQL**
2. O Railway cria automaticamente as variáveis:
   - `MYSQL_HOST`, `MYSQL_PORT`, `MYSQL_DATABASE`
   - `MYSQL_USER`, `MYSQL_PASSWORD`, `MYSQL_ROOT_PASSWORD`
   - `MYSQL_URL` (connection string completa)

## 3. Variáveis de Ambiente do OrangeHRM

No serviço do app (não no MySQL), adicione:

| Variável | Valor |
|---|---|
| `PORT` | Railway define automaticamente |
| `OHRM_DB_HOST` | `${{MySQL.MYSQL_HOST}}` |
| `OHRM_DB_PORT` | `${{MySQL.MYSQL_PORT}}` |
| `OHRM_DB_NAME` | `${{MySQL.MYSQL_DATABASE}}` |
| `OHRM_DB_USER` | `${{MySQL.MYSQL_USER}}` |
| `OHRM_DB_PASSWORD` | `${{MySQL.MYSQL_PASSWORD}}` |

> Railway permite referenciar variáveis entre serviços com `${{NomeServico.VARIAVEL}}`

## 4. Build do Dockerfile

O `Dockerfile` na raiz faz build multi-stage:

1. **Stage 1 (Node)** — compila o frontend Vue (`src/client/` → `web/dist/`)
2. **Stage 2 (PHP/Apache)** — copia o código fonte, instala dependências Composer, e recebe os assets do frontend

O entrypoint (`docker-entrypoint.sh`) configura o Apache para escutar na porta
definida pela variável `PORT` do Railway.

> Isso significa que **qualquer customização no fork** (ex: módulo OKR) será
> incluída automaticamente no deploy.

## 5. Setup Inicial

Após o primeiro deploy:

1. Acesse a URL pública gerada pelo Railway
2. O OrangeHRM redireciona automaticamente para o **installer** (`/installer/index.php`)
3. Preencha os dados de conexão com o banco (usar as variáveis do Railway — ver na aba **Variables** do serviço MySQL)
4. Crie o admin e a organização
5. Pronto — o sistema está funcional

> **Importante**: após completar o installer, o `Conf.php` é salvo dentro do
> container. Se o container reiniciar, será necessário refazer o setup. Para
> persistência, considere usar um Railway Volume montado em `/var/www/html/lib/confs`.

## 6. Persistência com Railway Volume

Para não perder o `Conf.php` e as chaves de criptografia entre deploys:

1. No serviço do app, vá em **Settings** → **Volumes**
2. Adicione um volume com mount path: `/var/www/html/lib/confs`
3. Isso garante que a configuração sobrevive a redeploys

## 7. Deploy Automático

Toda vez que houver merge na branch `main`, o Railway:
1. Detecta o push
2. Faz build do Dockerfile (multi-stage)
3. Substitui o container automaticamente

## 8. Fluxo de Trabalho

```
feature/okr  →  PR  →  develop (testes)  →  PR  →  main (deploy automático)
```

Nunca commitar direto na `main`.

## 9. Sincronizar com Upstream

```bash
git fetch upstream
git checkout develop
git merge upstream/main
# resolver conflitos se houver
git push origin develop
```

Recomendado: fazer isso trimestralmente para manter o fork atualizado.
