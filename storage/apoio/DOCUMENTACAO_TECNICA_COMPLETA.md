# WPP Manager - Documentacao Tecnica Completa

> Sistema de Atendimento WhatsApp Multi-instancia
##Essa documentação está em Yii2 com Baileys

---

## INDICE

1. [Visao Geral](#1-visao-geral)
2. [Arquitetura do Sistema](#2-arquitetura-do-sistema)
3. [Estrutura de Diretorios](#3-estrutura-de-diretorios)
4. [Banco de Dados](#4-banco-de-dados)
5. [Regras de Negocio](#5-regras-de-negocio)
6. [Integracao WhatsApp/Baileys](#6-integracao-whatsappbaileys)
7. [APIs e Endpoints](#7-apis-e-endpoints)
8. [Sistema de Permissoes](#8-sistema-de-permissoes)
9. [Guia de Instalacao](#9-guia-de-instalacao)
10. [Telas do Sistema](#10-telas-do-sistema)

---

## 1. VISAO GERAL

O WPP Manager e um sistema de atendimento via WhatsApp com suporte a multiplas instancias, composto por:

- **Backend PHP/Yii2** (porta 8095): Interface web, fila de atendimento, gerenciamento
- **Servico Node.js/Baileys** (porta 3000): Conexao WhatsApp, envio/recebimento de mensagens
- **Banco de Dados MySQL**: Armazena mensagens, chats, conversas, contatos, autenticacao

### Tecnologias Principais

| Componente | Tecnologia | Versao |
|-----------|-----------|--------|
| Backend | Yii2 Framework | 2.0.45 |
| Frontend Web | Bootstrap 5 + AdminLTE 3 | - |
| WhatsApp Lib | Baileys | 6.7.16 |
| API Server | Express.js | 4.21.2 |
| Database | MySQL | 8.0+ |
| Process Manager | PM2 | 6.0.14 |
| PHP | - | >= 7.4.0 |
| Node.js | - | 16+ |

---

## 2. ARQUITETURA DO SISTEMA

```
┌─────────────────────────────────────────────────────────────┐
│                    FRONTEND (Browser)                        │
│              Interface do usuario final                      │
│          URL: http://192.168.1.82:8095                      │
└──────────────────────┬──────────────────────────────────────┘
                       │ HTTP/AJAX (Polling)
┌──────────────────────▼──────────────────────────────────────┐
│                    BACKEND (Yii2 PHP)                       │
│         Painel administrativo e gerenciamento                │
│   Controllers: Chat, Conversa, Atendente, WhatsappAccount   │
└──────────────────────┬──────────────────────────────────────┘
                       │ HTTP REST (localhost:3000)
┌──────────────────────▼──────────────────────────────────────┐
│              WHATSAPP SERVICE (Node.js)                      │
│          Gerenciamento de conexao WhatsApp                   │
│               Porta: 3000 (PM2 Process)                      │
│   Base: Baileys (@whiskeysockets/baileys v6.7.16)           │
└──────────────────────┬──────────────────────────────────────┘
                       │ MySQL Connection
┌──────────────────────▼──────────────────────────────────────┐
│              BANCO DE DADOS (MySQL)                          │
│         whatsapp_atendimento (localhost, root)               │
│   Tabelas: chats, messages, conversas, atendentes, etc       │
└─────────────────────────────────────────────────────────────┘
```

### Fluxo de Comunicacao

1. **Usuario acessa Frontend**: Navegador → Yii2 (PHP)
2. **Backend precisa de WhatsApp**: Backend → Node.js (REST API porta 3000)
3. **Node.js gerencia WhatsApp**: Baileys → WhatsApp Web
4. **Dados sao persistidos**: Node.js → MySQL (mesmo banco)
5. **Frontend atualiza**: Polling AJAX a cada 3-5 segundos

---

## 3. ESTRUTURA DE DIRETORIOS

```
/opt/htdoc_geral/projwhats/wpp-manager/
│
├── common/                          # Codigo compartilhado (PHP)
│   ├── config/                      # Configuracoes comuns
│   │   ├── main.php                # Config Yii2 base
│   │   └── params.php              # Parametros compartilhados
│   └── models/                      # Models (Yii2 ActiveRecord)
│       ├── Chat.php                # Modelo de chat (WhatsApp)
│       ├── Message.php             # Modelo de mensagem
│       ├── Conversa.php            # Modelo de conversa/fila
│       ├── Atendente.php           # Modelo de atendente
│       ├── WhatsappAccount.php     # Instancia WhatsApp conectada
│       ├── Contact.php             # Modelo de contato
│       ├── Empresa.php             # Multi-empresa
│       ├── AtendenteAccount.php    # Junction atendente-conta (M2M)
│       └── User.php                # Usuario do sistema
│
├── backend/                         # Painel administrativo (Yii2)
│   ├── config/main.php             # Config backend
│   ├── controllers/                 # Controladores
│   │   ├── BaseController.php       # Controller base com filtros
│   │   ├── ChatController.php       # Painel de conversas
│   │   ├── ConversaController.php   # Fila e atendimento
│   │   ├── AtendenteController.php  # Gerenciar atendentes
│   │   ├── ContactController.php    # Gerenciar contatos
│   │   ├── MonitorController.php    # Monitoramento
│   │   └── WhatsappAccountController.php # Instancias
│   ├── views/                       # Templates Yii2
│   └── web/                         # Document root
│       ├── index.php               # Entry point
│       └── uploads/media/          # Midia de mensagens
│
├── console/                         # Comandos CLI (Yii2)
│   └── migrations/                  # Database migrations
│
├── whatsapp-service/                # Servico Node.js (WhatsApp)
│   ├── src/
│   │   ├── server.js               # Express server (porta 3000)
│   │   ├── whatsapp-connection.js  # Conexao Baileys + eventos
│   │   ├── database.js             # Pool MySQL
│   │   └── mysql-auth-state.js     # Auth state no MySQL
│   ├── auth_info/                   # Credenciais WhatsApp
│   ├── package.json                 # Dependencias Node.js
│   └── .env                         # Variaveis ambiente
│
├── composer.json                    # Dependencias PHP
└── yii                              # Console script
```

---

## 4. BANCO DE DADOS

### 4.1 Diagrama de Relacionamentos

```
empresas (1) ──────→ (N) users
         └──────→ (N) atendentes
         └──────→ (N) whatsapp_accounts

users (1) ──────→ (1) atendentes (opcional, via user_id)

atendentes (N) ←──→ (N) whatsapp_accounts (via atendente_account)
           └────→ (N) conversas

whatsapp_accounts (1) ────→ (N) chats
                  └────→ (N) contacts
                  └────→ (N) conversas

chats (1) ──────→ (N) messages
      └──────→ (1) conversas
```

### 4.2 Tabelas Principais

#### **empresas** (Multi-tenant)
| Campo | Tipo | Descricao |
|-------|------|-----------|
| id | INT PK | ID unico |
| nome | VARCHAR(200) | Nome da empresa |
| cnpj | VARCHAR(20) | CNPJ |
| status | TINYINT | 1=ativo, 0=inativo |
| created_at | TIMESTAMP | Data criacao |

#### **users** (Usuarios do Sistema)
| Campo | Tipo | Descricao |
|-------|------|-----------|
| id | INT PK | ID unico |
| empresa_id | INT FK | Empresa vinculada |
| username | VARCHAR(255) | Nome de usuario (UNIQUE) |
| email | VARCHAR(255) | Email (UNIQUE) |
| password_hash | VARCHAR(255) | Senha (bcrypt) |
| auth_token | VARCHAR(255) | Token API |
| role | ENUM | 'admin', 'supervisor', 'agent' |
| status | SMALLINT | 10=ativo, 0=inativo |

#### **atendentes** (Agentes de Atendimento)
| Campo | Tipo | Descricao |
|-------|------|-----------|
| id | INT PK | ID unico |
| empresa_id | INT FK | Empresa |
| user_id | INT FK | Vinculo com User (opcional) |
| nome | VARCHAR(100) | Nome completo |
| email | VARCHAR(100) | Email (UNIQUE) |
| status | ENUM | 'online', 'offline', 'ocupado' |
| max_conversas | INT | Maximo simultaneas (default: 5) |
| conversas_ativas | INT | Contador atual |

#### **whatsapp_accounts** (Instancias WhatsApp)
| Campo | Tipo | Descricao |
|-------|------|-----------|
| id | INT PK | ID unico |
| empresa_id | INT FK | Empresa |
| phone_number | VARCHAR(20) | Numero WhatsApp |
| session_name | VARCHAR(255) | Nome da sessao (UNIQUE) |
| owner_jid | VARCHAR(255) | JID do proprietario |
| is_connected | TINYINT | 1=conectado, 0=desconectado |
| service_port | INT | Porta do servico Node.js |

#### **atendente_account** (Junction N:M)
| Campo | Tipo | Descricao |
|-------|------|-----------|
| id | INT PK | ID unico |
| atendente_id | INT FK | Atendente |
| account_id | INT FK | Conta WhatsApp |

#### **chats** (Conversas do WhatsApp)
| Campo | Tipo | Descricao |
|-------|------|-----------|
| id | INT PK | ID unico |
| account_id | INT FK | Conta WhatsApp |
| chat_id | VARCHAR(255) | JID do chat |
| chat_name | VARCHAR(255) | Nome do contato/grupo |
| chat_type | ENUM | 'individual', 'group' |
| unread_count | INT | Nao lidos (0=lido, -1=marcado nao lido) |
| last_message_timestamp | BIGINT | Unix timestamp |

#### **messages** (Mensagens)
| Campo | Tipo | Descricao |
|-------|------|-----------|
| id | INT PK | ID unico |
| chat_id | INT FK | Chat |
| message_key | VARCHAR(255) | ID unico da mensagem (UNIQUE) |
| from_jid | VARCHAR(255) | Remetente |
| message_text | LONGTEXT | Texto |
| message_type | ENUM | 'text', 'image', 'video', etc |
| media_url | VARCHAR(500) | URL da midia |
| is_from_me | TINYINT | 1=enviada, 0=recebida |
| sent_by_user_id | INT | Atendente que enviou |
| status | ENUM | 'sent', 'delivered', 'read' |
| timestamp | BIGINT | Unix timestamp |
| quoted_message_id | VARCHAR(255) | Mensagem respondida |
| is_edited | TINYINT | Se foi editada |
| is_deleted | TINYINT | Se foi deletada |
| reactions | TEXT | JSON de reacoes |

#### **conversas** (Fila de Atendimento)
| Campo | Tipo | Descricao |
|-------|------|-----------|
| id | INT PK | ID unico |
| cliente_numero | VARCHAR(100) | Numero do cliente |
| cliente_nome | VARCHAR(100) | Nome do cliente |
| chat_id | INT FK | Chat vinculado |
| account_id | INT FK | Conta WhatsApp |
| atendente_id | INT FK | Atendente (NULL=na fila) |
| status | ENUM | 'aguardando', 'em_atendimento', 'finalizada' |
| iniciada_em | DATETIME | Quando criada |
| atendida_em | DATETIME | Quando atendente pegou |
| finalizada_em | DATETIME | Quando finalizada |
| cliente_aguardando_desde | DATETIME | Ultima msg do cliente |

#### **contacts** (Contatos)
| Campo | Tipo | Descricao |
|-------|------|-----------|
| id | INT PK | ID unico |
| account_id | INT FK | Conta WhatsApp |
| jid | VARCHAR(255) | JID do contato |
| name | VARCHAR(255) | Nome |
| phone_number | VARCHAR(20) | Telefone |
| profile_picture_url | VARCHAR(500) | Foto de perfil |

#### **baileys_auth** (Credenciais E2EE - MySQL)
| Campo | Tipo | Descricao |
|-------|------|-----------|
| id | INT PK | ID unico |
| account_id | INT | Conta WhatsApp |
| key_type | VARCHAR(50) | 'creds', 'pre-key', 'session', etc |
| key_id | VARCHAR(255) | ID da chave |
| key_data | LONGBLOB | Dados serializados |

---

## 5. REGRAS DE NEGOCIO

### 5.1 Fluxo da Fila de Atendimento

```
┌────────────────────────────────────────────────────────────────┐
│ 1. CLIENTE ENVIA MENSAGEM                                      │
│ ├─ Node.js recebe messages.upsert (fromMe=false)               │
│ ├─ Valida tipo e JID                                            │
│ ├─ Salva mensagem e contato                                     │
│ └─ upsertConversa() → status='aguardando'                       │
└────────────────────────────────────────────────────────────────┘
                          ▼
┌────────────────────────────────────────────────────────────────┐
│ 2. CONVERSA NA FILA                                             │
│ ├─ Atendente ve em "Fila de Espera"                            │
│ ├─ Validacoes: limite conversas, status='aguardando'            │
│ └─ Tempo em fila: now() - ultima_msg_em                        │
└────────────────────────────────────────────────────────────────┘
                          ▼
┌────────────────────────────────────────────────────────────────┐
│ 3. ATENDENTE PEGA CONVERSA                                      │
│ ├─ POST /conversa/pegar?id=X                                    │
│ ├─ status='aguardando' → status='em_atendimento'               │
│ ├─ atendente_id = [id]                                          │
│ ├─ conversas_ativas++                                           │
│ └─ Redireciona para /chat/painel                               │
└────────────────────────────────────────────────────────────────┘
                          ▼
┌────────────────────────────────────────────────────────────────┐
│ 4. ATENDENTE RESPONDE                                           │
│ ├─ Envia mensagem via painel (fromMe=true)                     │
│ ├─ Node.js detecta isOwnMessage() → finaliza conversa          │
│ ├─ status → 'finalizada'                                       │
│ ├─ conversas_ativas--                                           │
│ └─ marcarChatComoLido() → unread_count=0                       │
└────────────────────────────────────────────────────────────────┘
                          ▼
┌────────────────────────────────────────────────────────────────┐
│ 5. REABERTURA (cliente marca nao lido ou envia nova msg)       │
│ ├─ chats.update com unreadCount=-1 ou messages.upsert          │
│ ├─ reabrirConversa() ou upsertConversa()                        │
│ └─ status='finalizada' → status='aguardando'                    │
└────────────────────────────────────────────────────────────────┘
```

### 5.2 Estados de Conversa

| Estado | Descricao | Quem interage |
|--------|-----------|---------------|
| `aguardando` | Na fila, sem atendente | Qualquer atendente pode pegar |
| `em_atendimento` | Atribuida a atendente | Atendente atribuido responde |
| `finalizada` | Encerrada | Pode reabrir com nova msg |

### 5.3 Logica LID (Linked Identity)

**Problema:** Mensagens do WhatsApp Web chegam com `fromMe=false` mas `participant=LID@lid`

**Solucao:**
```javascript
function isOwnMessage(msg) {
    if (msg.key.fromMe) return true;

    // Detectar via LID (Linked Identity)
    if (ownLid && msg.key.participant) {
        const participantBase = msg.key.participant.split(':')[0].split('@')[0];
        const lidBase = ownLid.split(':')[0].split('@')[0];
        if (participantBase === lidBase) return true;
    }
    return false;
}
```

### 5.4 Finalizacao Automatica

| Trigger | Condicao | Acao |
|---------|----------|------|
| Atendente envia msg | `fromMe=true` via painel | `finalizarConversaLida()` |
| Resposta WhatsApp Web | `fromMe=false` mas LID match | `finalizarConversaLida()` |
| Chat lido | `chats.update` com `unreadCount=0` | `finalizarConversaLida()` |
| Manual | Atendente clica "Finalizar" | `finalizarConversaLida()` |

### 5.5 connectionStableAt

- **Valor:** 2 minutos apos conexao
- **Motivo:** Sync inicial gera eventos de historico que podem ser falsos positivos
- **Uso:** So finaliza por `unreadCount=0` apos `connectionStableAt`

### 5.6 Limite de Conversas

- Campo: `atendente.max_conversas` (default: 5)
- Contador: `atendente.conversas_ativas`
- Validacao ao pegar conversa: `conversas_ativas < max_conversas`

---

## 6. INTEGRACAO WHATSAPP/BAILEYS

### 6.1 Inicializacao

```javascript
sock = makeWASocket({
    version: waVersion,
    auth: {
        creds: state.creds,
        keys: makeCacheableSignalKeyStore(state.keys, logger)
    },
    browser: Browsers.macOS('Desktop'),
    syncFullHistory: true,
    maxMsgRetryCount: 5,
});
```

### 6.2 Auth State MySQL

- Credenciais salvas em `baileys_auth` no MySQL
- Transacional: protege contra corrupcao em crashes
- Migra automaticamente de arquivos JSON para MySQL

### 6.3 Eventos Tratados

| Evento | Acao |
|--------|------|
| `connection.update` | Gerencia conexao/reconexao |
| `creds.update` | Salva credenciais no MySQL |
| `messages.upsert` (type=notify) | Salva msg, cria conversa |
| `messages.update` | Atualiza status/edicao |
| `messages.reaction` | Salva reacao |
| `chats.update` | Finaliza/reabre por leitura |
| `messaging-history.set` | Sync de historico (NAO cria conversas) |
| `contacts.upsert` | Salva contatos |

### 6.4 Reconexao com Backoff

- Rate limit 405: exponential backoff (30s, 60s, 120s)
- Max 5 tentativas
- Apos falha: limpa credenciais, pede novo QR

### 6.5 Download de Midia

```javascript
const buffer = await downloadMediaMessage(msg, 'buffer', {});
const ext = MIME_EXTENSIONS[mimetype] || '.bin';
fs.writeFileSync(`/uploads/media/${messageId}${ext}`, buffer);
```

---

## 7. APIS E ENDPOINTS

### 7.1 Node.js (Servico WhatsApp)

**Base URL:** `http://localhost:3000`

| Metodo | Rota | Descricao |
|--------|------|-----------|
| GET | `/health` | Status da conexao |
| GET | `/api/stats` | Estatisticas |
| GET | `/api/chats` | Listar chats |
| GET | `/api/messages/:jid` | Buscar mensagens |
| GET | `/api/connection-status` | Status + QR code |
| GET | `/api/profile-pic/:jid` | Foto de perfil |
| POST | `/api/send-message` | Enviar texto |
| POST | `/api/send-media` | Enviar arquivo |
| POST | `/api/edit-message` | Editar mensagem |
| POST | `/api/delete-message` | Excluir mensagem |
| POST | `/api/react-message` | Adicionar reacao |
| POST | `/api/forward-message` | Encaminhar |
| POST | `/api/mark-read/:jid` | Marcar como lido |
| POST | `/api/sync-recent/:jid` | Sync recente |
| POST | `/api/sync-chat/:jid` | Sync historico |
| POST | `/api/sync-groups` | Sync grupos |

### 7.2 PHP/Yii2 (Backend)

**Base URL:** `http://192.168.1.82:8095`

#### Conversa (Fila e Atendimento)
| Metodo | Rota | Descricao |
|--------|------|-----------|
| GET | `/conversa/fila` | Pagina da fila |
| GET | `/conversa/fila-json` | JSON da fila |
| POST | `/conversa/pegar?id=X` | Pegar conversa |
| POST | `/conversa/pegar-ajax?id=X` | Pegar (AJAX) |
| GET | `/conversa/meu-console` | Console do atendente |
| GET | `/conversa/minhas-conversas` | JSON conversas ativas |
| POST | `/conversa/finalizar?id=X` | Finalizar |
| POST | `/conversa/devolver?id=X` | Devolver para fila |
| POST | `/conversa/finalizar-massa` | Finalizar multiplas |

#### Chat (Painel de Mensagens)
| Metodo | Rota | Descricao |
|--------|------|-----------|
| GET | `/chat/painel` | Painel de atendimento |
| GET | `/chat/chat-list` | Lista de chats |
| GET | `/chat/messages?chat_id=X` | Mensagens do chat |
| POST | `/chat/send-message` | Enviar texto |
| POST | `/chat/send-media` | Enviar arquivo |
| POST | `/chat/edit-message` | Editar |
| POST | `/chat/delete-message` | Excluir |
| POST | `/chat/react-message` | Reagir |
| POST | `/chat/forward-message` | Encaminhar |

#### WhatsApp Account (Instancias)
| Metodo | Rota | Descricao |
|--------|------|-----------|
| GET | `/whatsapp-account/index` | Listar instancias |
| GET | `/whatsapp-account/connect?id=X` | Tela de pairing |
| GET | `/whatsapp-account/connection-status?id=X` | Status conexao |

### 7.3 Comunicacao PHP → Node.js

```php
// Exemplo: Enviar mensagem
$ch = curl_init('http://localhost:3000/api/send-message');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'jid' => '554699123456@s.whatsapp.net',
        'text' => 'Ola!',
        'sentByUserId' => 5
    ]),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
]);
$response = curl_exec($ch);
```

---

## 8. SISTEMA DE PERMISSOES

### 8.1 Roles (Papeis)

| Role | Constante | Acesso |
|------|-----------|--------|
| **Admin** | `'admin'` | Acesso total, todas empresas |
| **Supervisor** | `'supervisor'` | Gerencia sua empresa |
| **Agent** | `'agent'` | Atendimento basico |

### 8.2 Permissoes por Role

| Funcionalidade | Admin | Supervisor | Agent |
|---|---|---|---|
| Gerenciar Empresas | ✅ | ❌ | ❌ |
| Ver todas empresas | ✅ | ❌ | ❌ |
| Gerenciar Usuarios | ✅ | ✅ (sua empresa) | ❌ |
| Gerenciar Atendentes | ✅ | ✅ (sua empresa) | ❌ |
| Gerenciar Contas WhatsApp | ✅ | ✅ (sua empresa) | ❌ |
| Acessar Fila | ✅ | ✅ | ✅ (contas vinculadas) |
| Atender Conversas | ✅ | ✅ | ✅ |
| Finalizar Conversas | ✅ (qualquer) | ✅ (qualquer) | ✅ (apenas suas) |

### 8.3 Filtro por Empresa

```php
// Em BaseController
protected function applyEmpresaFilter($query)
{
    if (!$this->isAdmin()) {
        $query->andWhere(['empresa_id' => $this->getEmpresaId()]);
    }
    return $query;
}
```

### 8.4 Autenticacao

- **Tipo:** Session-based com cookie httpOnly
- **Cookie:** `_identity-wpp-manager`
- **Senha:** Hash bcrypt via `Yii::$app->security`
- **Remember Me:** 30 dias

### 8.5 Vinculacao Atendente-Instancia

- Tabela `atendente_account` mapeia N:M
- Atendente so ve conversas das contas vinculadas
- Filtro aplicado em `ConversaController::actionFila()`

---

## 9. GUIA DE INSTALACAO

### 9.1 Prerequisitos

```bash
php -v          # >= 7.4.0
mysql --version # MySQL 8.0+
node --version  # >= 16
composer --version
npm --version
```

### 9.2 Setup Inicial

```bash
cd /opt/htdoc_geral/projwhats/wpp-manager

# 1. Instalar dependencias PHP
composer install

# 2. Criar banco de dados
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS whatsapp_atendimento CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 3. Executar migrations
./yii migrate --interactive=0

# 4. Instalar dependencias Node.js
cd whatsapp-service
npm install
```

### 9.3 Configuracao

**Backend (PHP):** `backend/config/main-local.php`
```php
'db' => [
    'dsn' => 'mysql:host=localhost;dbname=whatsapp_atendimento',
    'username' => 'root',
    'password' => 'sua_senha',
],
```

**Node.js:** `whatsapp-service/.env`
```env
PORT=3000
DB_HOST=127.0.0.1
DB_USER=root
DB_PASSWORD=sua_senha
DB_NAME=whatsapp_atendimento
ACCOUNT_ID=3
PHONE_NUMBER=5544988050924
```

### 9.4 Rodar em Desenvolvimento

**Terminal 1 - Backend PHP:**
```bash
php -S 192.168.1.82:8095 -t backend/web/
```

**Terminal 2 - Node.js:**
```bash
cd whatsapp-service
npm run dev
```

### 9.5 Rodar em Producao (PM2)

```bash
cd whatsapp-service
pm2 start src/server.js --name "wpp-service"
pm2 save
pm2 startup
```

### 9.6 Primeiro Acesso

1. Criar empresa: Admin → Empresas → Nova
2. Conectar WhatsApp: WhatsApp Accounts → Nova → Conectar → Escanear QR
3. Vincular atendentes: Atendentes → Editar → Vincular Contas
4. Acessar painel: Painel de Conversas

---

## 10. TELAS DO SISTEMA

### 10.1 Dashboard (`/site/index`)
- Cards: Total chats, Mensagens do dia, Instancias online, Conversas ativas
- Lista das ultimas 10 mensagens
- Instancias conectadas

### 10.2 Empresa (`/empresa`) - Admin Only
- CRUD completo de empresas
- Listagem com paginacao

### 10.3 Instancias (`/whatsapp-account`)
- Listar instancias com status de conexao
- Criar, editar, excluir instancias

### 10.4 Nova Instancia (`/whatsapp-account/create`)
- Formulario de criacao
- Selecao de empresa (admin) ou automatica

### 10.5 Painel de Conversas (`/chat/painel`)
- Interface estilo WhatsApp Web
- Lista de chats com busca
- Enviar texto, midia, reagir, editar, deletar, encaminhar
- Polling automatico de mensagens

### 10.6 Fila de Espera (`/conversa/fila`)
- Conversas aguardando atendimento
- Ordenadas por tempo (FIFO)
- Botao "Pegar" para atribuir

### 10.7 Meu Console (`/conversa/meu-console`)
- Conversas ativas do atendente
- Finalizar, devolver, finalizar em massa
- Badge com contador da fila

### 10.8 Contatos (`/contact`)
- Listagem com busca e filtro por instancia
- Estatisticas de contatos

### 10.9 Monitor (`/monitor`)
- Dashboard de monitoramento
- Cards: instancias online, fila, em atendimento
- Tabelas de instancias e atendentes

### 10.10 Supervisao (`/monitor/supervisao`)
- Painel em tempo real
- Todas conversas em atendimento
- Dados de atendentes (status, conversas ativas)

### 10.11 Historico Conversas (`/monitor/conversas`)
- Historico completo com filtros
- Estatisticas por atendente
- Filtros: atendente, status, periodo

### 10.12 Logs de Webhook (`/log-sistema`)
- Logs do sistema com filtros
- Tipos: erro, info, atendimento
- Niveis: debug, info, warning, error

### 10.13 Sincronizar Contatos (`/contact/sync`)
- Criar contatos faltantes
- Atualizar nomes vazios
- Sincronizar grupos via Node.js

---

## VARIAVEIS DE AMBIENTE

### Node.js (.env)

```env
# Servidor
PORT=3000

# Banco de dados
DB_HOST=127.0.0.1
DB_USER=root
DB_PASSWORD=aaa
DB_NAME=whatsapp_atendimento

# WhatsApp
AUTH_DIR=./auth_info
ACCOUNT_ID=3
PHONE_NUMBER=5544988050924

# Log
LOG_LEVEL=info
```

### PHP (backend/config/main-local.php)

```php
'db' => [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=whatsapp_atendimento',
    'username' => 'root',
    'password' => 'aaa',
    'charset' => 'utf8mb4',
],
```

---

## ARQUIVOS-CHAVE DE REFERENCIA

| Arquivo | Proposito |
|---------|-----------|
| `common/models/Chat.php` | Modelo de chat |
| `common/models/Message.php` | Modelo de mensagem |
| `common/models/Conversa.php` | Fila de atendimento |
| `common/models/Atendente.php` | Atendente |
| `common/models/User.php` | Usuario e roles |
| `backend/controllers/BaseController.php` | Filtros de seguranca |
| `backend/controllers/ChatController.php` | Painel de conversas |
| `backend/controllers/ConversaController.php` | Fila e atendimento |
| `whatsapp-service/src/server.js` | Servidor Express |
| `whatsapp-service/src/whatsapp-connection.js` | Logica Baileys |
| `whatsapp-service/src/database.js` | Pool MySQL |
| `whatsapp-service/src/mysql-auth-state.js` | Auth no MySQL |
| `console/migrations/` | Migrations do banco |

---

*Documentacao gerada em 2026-03-11*
