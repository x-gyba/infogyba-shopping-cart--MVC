#!/bin/bash

set -e

PROJECT_DIR="$(cd "$(dirname "$0")/.." && pwd)"

ENV_LOCAL="$PROJECT_DIR/.env"
ENV_PROD="$PROJECT_DIR/.env.production"

echo "Projeto: $PROJECT_DIR"

if [ ! -f "$ENV_PROD" ]; then
    echo "ERRO: arquivo .env.production não encontrado."
    exit 1
fi

if [ -f "$ENV_LOCAL" ]; then
    BACKUP="$PROJECT_DIR/.env.backup.$(date +%Y%m%d_%H%M%S)"
    cp "$ENV_LOCAL" "$BACKUP"
    echo "Backup criado: $BACKUP"
fi

cp "$ENV_PROD" "$ENV_LOCAL"

chmod 600 "$ENV_LOCAL"

echo "Arquivo .env de produção aplicado com sucesso."
