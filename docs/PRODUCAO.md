# WppManager - Guia de Deploy em Produção

Este documento contém todas as informações necessárias para configurar e rodar o WppManager em um servidor de produção.

---

## Índice

1. [Requisitos do Sistema](#requisitos-do-sistema)
2. [Instalação de Dependências](#instalação-de-dependências)
3. [Configuração do Projeto](#configuração-do-projeto)
4. [Banco de Dados](#banco-de-dados)
5. [Evolution API](#evolution-api)
6. [Servidor Web (Nginx)](#servidor-web-nginx)
7. [Supervisor (Queue Worker)](#supervisor-queue-worker)
8. [Laravel Scheduler (Cron)](#laravel-scheduler-cron)
9. [WebSockets (Reverb)](#websockets-reverb)
10. [Permissões](#permissões)
11. [Comandos Úteis](#comandos-úteis)
12. [Troubleshooting](#troubleshooting)

---

## Requisitos do Sistema

### Software Necessário

| Software | Versão Mínima | Comando para verificar |
|----------|---------------|------------------------|
| PHP | 8.2+ | `php -v` |
| Composer | 2.x | `composer -V` |
| Node.js | 18+ | `node -v` |
| NPM | 9+ | `npm -v` |
| MySQL/MariaDB | 8.0+ / 10.6+ | `mysql --version` |
| Nginx | 1.18+ | `nginx -v` |
| Supervisor | 4.x | `supervisord -v` |
| Git | 2.x | `git --version` |

### Extensões PHP Necessárias

```bash
# Verificar extensões instaladas
php -m

# Extensões necessárias:
- bcmath
- ctype
- curl
- dom
- fileinfo
- json
- mbstring
- openssl
- pcre
- pdo
- pdo_mysql
- tokenizer
- xml
- zip
```

---

## Instalação de Dependências

### 1. Atualizar sistema e instalar pacotes base

```bash
sudo apt update && sudo apt upgrade -y

# Instalar dependências do sistema
sudo apt install -y curl git unzip nginx supervisor cron
```

### 2. Instalar PHP 8.2+

```bash
# Adicionar repositório PHP
sudo apt install -y lsb-release apt-transport-https ca-certificates
sudo wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/php.list

# Instalar PHP e extensões
sudo apt update
sudo apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-mysql php8.2-mbstring \
    php8.2-xml php8.2-curl php8.2-zip php8.2-bcmath php8.2-gd php8.2-intl
```

### 3. Instalar Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 4. Instalar Node.js (via NVM)

```bash
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash
source ~/.bashrc
nvm install 20
nvm use 20
```

### 5. Instalar MySQL/MariaDB

```bash
sudo apt install -y mariadb-server mariadb-client

# Configurar segurança
sudo mysql_secure_installation

# Criar banco e usuário
sudo mysql -u root -p
```

```sql
CREATE DATABASE wpp_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'wpp_user'@'localhost' IDENTIFIED BY 'sua_senha_segura';
GRANT ALL PRIVILEGES ON wpp_manager.* TO 'wpp_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## Configuração do Projeto

### 1. Clonar repositório

```bash
cd /var/www
sudo git clone https://github.com/seu-usuario/wpp-manager.git
cd wpp-manager
```

### 2. Instalar dependências

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

### 3. Configurar ambiente (.env)

```bash
cp .env.example .env
php artisan key:generate
nano .env
```

### Variáveis de Ambiente Importantes

```env
# === APLICAÇÃO ===
APP_NAME="WppManager"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://seu-dominio.com.br

# === BANCO DE DADOS ===
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wpp_manager
DB_USERNAME=wpp_user
DB_PASSWORD=sua_senha_segura

# === SESSÃO E CACHE ===
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# === EVOLUTION API ===
EVOLUTION_API_URL=http://localhost:8085
EVOLUTION_API_KEY=sua_chave_api_evolution

# === WEBSOCKETS (REVERB) ===
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=seu_app_id
REVERB_APP_KEY=sua_app_key
REVERB_APP_SECRET=seu_app_secret
REVERB_HOST=seu-dominio.com.br
REVERB_PORT=6001
REVERB_SCHEME=https

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### 4. Executar migrations

```bash
php artisan migrate --force
```

### 5. Criar usuário admin inicial

```bash
php artisan tinker
```

```php
use App\Models\User;
User::create([
    'name' => 'Admin',
    'email' => 'admin@empresa.com',
    'password' => bcrypt('senha_segura'),
    'role' => 'admin',
]);
exit;
```

### 6. Otimizações para produção

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

---

## Evolution API

### Instalação via Docker

```bash
# Criar diretório
mkdir -p /opt/evolution-api
cd /opt/evolution-api

# Criar docker-compose.yml
cat > docker-compose.yml << 'EOF'
version: '3.8'
services:
  evolution-api:
    image: atendai/evolution-api:latest
    container_name: evolution-api
    restart: always
    ports:
      - "8085:8080"
    environment:
      - SERVER_URL=https://api.seu-dominio.com.br
      - AUTHENTICATION_API_KEY=sua_chave_api_aqui
      - DATABASE_ENABLED=true
      - DATABASE_PROVIDER=postgresql
      - DATABASE_CONNECTION_URI=postgresql://user:pass@localhost:5432/evolution
    volumes:
      - evolution_instances:/evolution/instances
      - evolution_store:/evolution/store

volumes:
  evolution_instances:
  evolution_store:
EOF

# Iniciar
docker-compose up -d
```

### Configurar Webhook na Evolution API

O webhook deve apontar para o servidor WppManager:

```
URL: https://seu-dominio.com.br/api/webhook/evolution
```

**Eventos necessários:**
- MESSAGES_UPSERT
- MESSAGES_UPDATE
- CONNECTION_UPDATE
- QRCODE_UPDATED
- GROUPS_UPSERT
- GROUP_UPDATE
- GROUP_PARTICIPANTS_UPDATE

**Importante para Docker:** Se a Evolution API roda em Docker e o WppManager no host, use o IP do host Docker:
```
URL: http://172.17.0.1:8000/api/webhook/evolution
```

---

## Servidor Web (Nginx)

### Configuração do Virtual Host

```bash
sudo nano /etc/nginx/sites-available/wpp-manager
```

```nginx
server {
    listen 80;
    server_name seu-dominio.com.br;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name seu-dominio.com.br;

    root /var/www/wpp-manager/public;
    index index.php;

    # SSL (ajustar caminhos do certificado)
    ssl_certificate /etc/letsencrypt/live/seu-dominio.com.br/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/seu-dominio.com.br/privkey.pem;

    # Logs
    access_log /var/log/nginx/wpp-manager-access.log;
    error_log /var/log/nginx/wpp-manager-error.log;

    # Configurações de segurança
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    # Tamanho máximo de upload
    client_max_body_size 64M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Cache de assets estáticos
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
```

### Ativar site e reiniciar Nginx

```bash
sudo ln -s /etc/nginx/sites-available/wpp-manager /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### Instalar SSL com Certbot

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d seu-dominio.com.br
```

---

## Supervisor (Queue Worker)

O Supervisor mantém o worker de fila rodando permanentemente.

### Copiar configuração

```bash
sudo cp /var/www/wpp-manager/supervisor/wpp-manager-worker.conf /etc/supervisor/conf.d/
```

### Conteúdo do arquivo (já incluído no projeto)

```ini
[program:wpp-manager-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/wpp-manager/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/wpp-manager/storage/logs/worker.log
stopwaitsecs=3600
```

### Ativar e iniciar

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start wpp-manager-worker:*
```

### Verificar status

```bash
sudo supervisorctl status
```

---

## Laravel Scheduler (Cron)

O Scheduler executa tarefas automáticas como sincronização de status das instâncias.

### Adicionar ao Crontab

```bash
sudo crontab -e
```

Adicionar linha:

```cron
* * * * * cd /var/www/wpp-manager && php artisan schedule:run >> /dev/null 2>&1
```

### Tarefas agendadas

| Comando | Frequência | Descrição |
|---------|------------|-----------|
| `instances:sync-status` | A cada minuto | Sincroniza status de conexão das instâncias WhatsApp |

---

## WebSockets (Reverb)

O Reverb fornece WebSockets para atualizações em tempo real.

### Configuração do Supervisor para Reverb

```bash
sudo nano /etc/supervisor/conf.d/wpp-manager-reverb.conf
```

```ini
[program:wpp-manager-reverb]
process_name=%(program_name)s
command=php /var/www/wpp-manager/artisan reverb:start --host=0.0.0.0 --port=6001
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/wpp-manager/storage/logs/reverb.log
stopwaitsecs=3600
```

### Ativar

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start wpp-manager-reverb
```

### Nginx Proxy para WebSocket (opcional, para SSL)

Adicionar ao virtual host:

```nginx
location /app {
    proxy_pass http://127.0.0.1:6001;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_cache_bypass $http_upgrade;
}
```

---

## Permissões

```bash
cd /var/www/wpp-manager

# Proprietário
sudo chown -R www-data:www-data .

# Permissões de diretórios
sudo find . -type d -exec chmod 755 {} \;

# Permissões de arquivos
sudo find . -type f -exec chmod 644 {} \;

# Diretórios que precisam de escrita
sudo chmod -R 775 storage bootstrap/cache
```

---

## Comandos Úteis

### Gerenciamento do Sistema

```bash
# Limpar caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Recriar caches (produção)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Ver logs em tempo real
tail -f storage/logs/laravel.log

# Ver logs do worker
tail -f storage/logs/worker.log
```

### Comandos Específicos do WppManager

```bash
# Sincronizar status das instâncias manualmente
php artisan instances:sync-status

# Corrigir nomes de grupos
php artisan groups:fix-names

# Corrigir nomes de grupos de uma conta específica
php artisan groups:fix-names --account=1
```

### Supervisor

```bash
# Ver status de todos os processos
sudo supervisorctl status

# Reiniciar worker
sudo supervisorctl restart wpp-manager-worker:*

# Reiniciar Reverb
sudo supervisorctl restart wpp-manager-reverb

# Recarregar configurações
sudo supervisorctl reread
sudo supervisorctl update
```

### Filas

```bash
# Ver jobs pendentes
php artisan queue:monitor

# Ver jobs falhados
php artisan queue:failed

# Reprocessar jobs falhados
php artisan queue:retry all

# Limpar jobs falhados
php artisan queue:flush
```

---

## Troubleshooting

### Instância mostrando "Desconectado" incorretamente

1. Verificar se o scheduler está rodando:
```bash
grep -c "schedule:run" /var/log/syslog
```

2. Executar manualmente:
```bash
php artisan instances:sync-status
```

### Mensagens não chegando (Webhook)

1. Verificar URL do webhook na Evolution API
2. Se Evolution roda em Docker, usar IP `172.17.0.1` ao invés de `localhost`
3. Verificar logs:
```bash
tail -f storage/logs/laravel.log | grep -i webhook
```

### Queue worker não processa jobs

1. Verificar Supervisor:
```bash
sudo supervisorctl status
```

2. Ver logs do worker:
```bash
tail -f storage/logs/worker.log
```

3. Reiniciar worker:
```bash
sudo supervisorctl restart wpp-manager-worker:*
```

### Grupos com nomes numéricos

Executar comando para corrigir:
```bash
php artisan groups:fix-names
```

### Erro 500 / Página em branco

1. Verificar logs:
```bash
tail -f storage/logs/laravel.log
```

2. Verificar permissões:
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

3. Limpar e recriar cache:
```bash
php artisan config:clear
php artisan cache:clear
```

### WebSocket não conecta

1. Verificar se Reverb está rodando:
```bash
sudo supervisorctl status wpp-manager-reverb
```

2. Verificar porta 6001 está aberta:
```bash
sudo netstat -tlnp | grep 6001
```

3. Verificar configuração do .env (VITE_REVERB_*)

---

## Checklist de Deploy

- [ ] PHP 8.2+ instalado com extensões
- [ ] Composer instalado
- [ ] Node.js 18+ instalado
- [ ] MySQL/MariaDB configurado
- [ ] Projeto clonado e dependências instaladas
- [ ] Arquivo .env configurado
- [ ] Migrations executadas
- [ ] Usuário admin criado
- [ ] Nginx configurado e SSL ativo
- [ ] Supervisor configurado (worker + reverb)
- [ ] Crontab configurado (scheduler)
- [ ] Permissões corretas
- [ ] Evolution API instalada e configurada
- [ ] Webhook da Evolution apontando para o sistema
- [ ] Teste de envio/recebimento de mensagens

---

## Suporte

Para problemas ou dúvidas:
- Verificar logs em `storage/logs/`
- Consultar documentação do Laravel: https://laravel.com/docs
- Documentação Evolution API: https://doc.evolution-api.com
