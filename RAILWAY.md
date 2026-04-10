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
| `PORT` | `80` |
| `OHRM_DB_HOST` | `${{MySQL.MYSQL_HOST}}` |
| `OHRM_DB_PORT` | `${{MySQL.MYSQL_PORT}}` |
| `OHRM_DB_NAME` | `${{MySQL.MYSQL_DATABASE}}` |
| `OHRM_DB_USER` | `${{MySQL.MYSQL_USER}}` |
| `OHRM_DB_PASSWORD` | `${{MySQL.MYSQL_PASSWORD}}` |

> Railway permite referenciar variáveis entre serviços com `${{NomeServico.VARIAVEL}}`

## 4. Configuração do Dockerfile

O Railway detecta automaticamente o `Dockerfile` na raiz do repositório.
O Dockerfile de produção (`Dockerfile`) baixa a versão estável do OrangeHRM.

Se quiser fazer deploy do código fonte customizado (ex: módulo OKR), será necessário
ajustar o `Dockerfile` para copiar o código local em vez de baixar do SourceForge.

## 5. Setup Inicial

Após o primeiro deploy:

1. Acesse a URL pública gerada pelo Railway
2. O OrangeHRM redireciona automaticamente para o **installer** (`/installer/index.php`)
3. Preencha os dados de conexão com o banco (usar as variáveis do Railway)
4. Crie o admin e a organização
5. Pronto — o sistema está funcional

## 6. Deploy Automático

Toda vez que houver merge na branch `main`, o Railway:
1. Detecta o push
2. Faz build do Dockerfile
3. Substitui o container automaticamente (zero-downtime)

## 7. Fluxo de Trabalho

```
feature/okr  →  PR  →  develop (testes)  →  PR  →  main (deploy automático)
```

Nunca commitar direto na `main`.

## 8. Sincronizar com Upstream

```bash
git fetch upstream
git checkout develop
git merge upstream/main
# resolver conflitos se houver
git push origin develop
```

Recomendado: fazer isso trimestralmente para manter o fork atualizado.
