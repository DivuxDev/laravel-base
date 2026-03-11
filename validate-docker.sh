#!/bin/bash

# Script para validar la configuración Docker antes de desplegar en Coolify
# Uso: bash validate-docker.sh

set -e

echo "🔍 Validando configuración Docker..."
echo ""

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Contador de errores
ERRORS=0

# Función para imprimir resultado
check() {
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓${NC} $1"
    else
        echo -e "${RED}✗${NC} $1"
        ERRORS=$((ERRORS + 1))
    fi
}

# 1. Verificar que exista Dockerfile
echo "📋 Checking archivos requeridos..."
test -f "Dockerfile"
check "Dockerfile existe"

test -f ".dockerignore"
check ".dockerignore existe"

test -f "entrypoint.sh"
check "entrypoint.sh existe"

test -f "composer.json"
check "composer.json existe"

echo ""

# 2. Verificar que exista .env o .env.example
echo "⚙️  Checking variables de entorno..."
if [ -f ".env" ]; then
    echo -e "${GREEN}✓${NC} .env configurado"
else
    echo -e "${YELLOW}⚠${NC} .env no existe (será necesario en Coolify)"
fi

test -f ".env.example"
check ".env.example existe"

echo ""

# 3. Verificar estructura del proyecto
echo "📁 Checking estructura del proyecto..."
test -d "app"
check "Carpeta 'app' existe"

test -d "routes"
check "Carpeta 'routes' existe"

test -d "database"
check "Carpeta 'database' existe"

test -d "storage"
check "Carpeta 'storage' existe"

echo ""

# 4. Verificar que Docker esté instalado
echo "🐳 Checking Docker..."
which docker > /dev/null
check "Docker instalado"

which docker-compose > /dev/null
check "Docker Compose instalado"

echo ""

# 5. Verificar sintaxis del Dockerfile
echo "✅ Validando Dockerfile..."
if command -v dockerfile_lint &> /dev/null; then
    dockerfile_lint -f Dockerfile
    check "Dockerfile válido"
else
    echo -e "${YELLOW}⚠${NC} dockerfile_lint no instalado (http://www.projectatomic.io/projects/dockerfile_lint/)"
fi

echo ""

# 6. Verificar permisos
echo "🔐 Checking permisos..."
if [ -x "entrypoint.sh" ]; then
    echo -e "${GREEN}✓${NC} entrypoint.sh es ejecutable"
else
    echo -e "${YELLOW}⚠${NC} entrypoint.sh no es ejecutable"
    echo "    Ejecuta: chmod +x entrypoint.sh"
    ERRORS=$((ERRORS + 1))
fi

echo ""

# 7. Verificar que composer.lock exista (recomendado)
echo "📦 Checking dependencias..."
if [ -f "composer.lock" ]; then
    echo -e "${GREEN}✓${NC} composer.lock existe (recomendado para producción)"
else
    echo -e "${YELLOW}⚠${NC} composer.lock no existe"
    echo "    Ejecuta: composer install"
fi

echo ""

# Resumen
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if [ $ERRORS -eq 0 ]; then
    echo -e "${GREEN}✅ Validación completada exitosamente!${NC}"
    echo ""
    echo "Tu proyecto está listo para desplegar en Coolify:"
    echo "1. Conecta el repositorio a Coolify"
    echo "2. Configura las variables de entorno (ver DEPLOYMENT.md)"
    echo "3. ¡Despliega!"
else
    echo -e "${RED}❌ Se encontraron $ERRORS error(es)${NC}"
    echo ""
    echo "Soluciona los problemas anteriores antes de desplegar."
    exit 1
fi

echo ""
