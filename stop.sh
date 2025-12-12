#!/bin/bash

# Couleurs pour les messages
RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

print_message() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_message "Arrêt des conteneurs..."
docker-compose down

if [ $? -eq 0 ]; then
    print_success "Conteneurs arrêtés ✓"
else
    echo -e "${RED}[ERROR]${NC} Échec de l'arrêt des conteneurs"
    exit 1
fi