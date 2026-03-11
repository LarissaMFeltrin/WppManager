#!/bin/bash

echo "=========================================="
echo "  Baileys Service - Setup"
echo "=========================================="
echo ""

# Verificar Node.js
if ! command -v node &> /dev/null; then
    echo "ERRO: Node.js nao encontrado. Instale com: sudo apt install nodejs"
    exit 1
fi

NODE_VERSION=$(node -v)
echo "Node.js: $NODE_VERSION"

# Verificar npm
if ! command -v npm &> /dev/null; then
    echo "ERRO: npm nao encontrado."
    exit 1
fi

NPM_VERSION=$(npm -v)
echo "npm: $NPM_VERSION"
echo ""

# Instalar dependencias
echo "Instalando dependencias..."
npm install

# Criar diretorios necessarios
mkdir -p auth_info
mkdir -p tmp_uploads
mkdir -p logs
mkdir -p ../storage/app/public/media

# Verificar .env
if [ ! -f .env ]; then
    echo ""
    echo "ATENCAO: Arquivo .env nao encontrado!"
    echo "Copie o .env.example e configure as variaveis."
    exit 1
fi

echo ""
echo "=========================================="
echo "  Setup concluido!"
echo "=========================================="
echo ""
echo "Para iniciar o servico:"
echo "  npm start           # Modo desenvolvimento"
echo "  pm2 start ecosystem.config.js  # Modo producao"
echo ""
echo "Para conectar ao WhatsApp:"
echo "  1. Inicie o servico"
echo "  2. Escaneie o QR Code no terminal"
echo "     ou acesse: http://localhost:3001/api/connection-status"
echo ""
