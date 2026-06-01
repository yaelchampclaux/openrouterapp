#!/bin/bash

# Couleurs pour les messages
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fonction pour afficher les messages
print_message() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Fonction pour vérifier les prérequis
check_prerequisites() {
    print_message "Vérification des prérequis..."
    
    if ! command -v docker &> /dev/null; then
        print_error "Docker n'est pas installé. Veuillez l'installer avant de continuer."
        exit 1
    fi
    
    if ! command -v docker-compose &> /dev/null && ! docker compose version &> /dev/null; then
        print_error "Docker Compose n'est pas installé. Veuillez l'installer avant de continuer."
        exit 1
    fi
    
    print_success "Prérequis vérifiés ✓"
}

# Fonction pour vérifier/créer le fichier de mot de passe
setup_password() {
    if [ ! -f "db_root_password.txt" ]; then
        print_warning "Le fichier db_root_password.txt n'existe pas."
        read -sp "Entrez un mot de passe pour la base de données MariaDB: " db_password
        echo
        echo "$db_password" > db_root_password.txt
        chmod 600 db_root_password.txt
        print_success "Fichier db_root_password.txt créé ✓"
    else
        db_password=$(cat db_root_password.txt)
        print_success "Fichier db_root_password.txt trouvé ✓"
    fi
}

# Fonction pour configurer le .env de Symfony
setup_symfony_env() {
    print_message "Configuration du fichier .env de Symfony..."
    
    if [ ! -f "www/.env" ]; then
        print_error "Le fichier www/.env n'existe pas."
        exit 1
    fi
    
    # Remplacer la ligne DATABASE_URL
    sed -i.bak "s|DATABASE_URL=.*|DATABASE_URL=\"mysql://root:${db_password}@db-ora:3306/openrouterapp?serverVersion=11.6.2-MariaDB-ubu2404\"|g" www/.env
    
    print_success "Fichier www/.env configuré ✓"
}

# Fonction pour démarrer les conteneurs
start_containers() {
    print_message "Démarrage des conteneurs Docker..."
    docker compose up -d --build
    
    if [ $? -ne 0 ]; then
        print_error "Échec du démarrage des conteneurs."
        exit 1
    fi
    
    print_success "Conteneurs démarrés ✓"
}

# Fonction pour attendre que les conteneurs soient prêts
wait_for_containers() {
    print_message "Attente du démarrage complet des services..."
    
    # Attendre que MariaDB soit prêt
    timeout=60
    counter=0
    until docker exec db-ora mariadb -u root -p"${db_password}" -e "SELECT 1" &> /dev/null; do
        sleep 2
        counter=$((counter + 2))
        if [ $counter -ge $timeout ]; then
            print_error "Timeout: MariaDB n'a pas démarré dans les temps."
            exit 1
        fi
        echo -n "."
    done
    echo
    
    print_success "Base de données prête ✓"
}

# Fonction pour installer les dépendances Symfony
install_symfony_dependencies() {
    print_message "Installation des dépendances Symfony..."
    
    docker exec -u you www-ora bash -c "cd ora && composer install --no-interaction"
    
    if [ $? -ne 0 ]; then
        print_error "Échec de l'installation des dépendances."
        exit 1
    fi
    
    print_success "Dépendances installées ✓"
}

# Fonction pour créer la structure de la base de données
setup_database() {
    print_message "Création de la structure de la base de données..."
    
    # Créer la base de données si elle n'existe pas
    docker exec www-ora bash -c "cd ora && php bin/console doctrine:database:create --if-not-exists --no-interaction"
    
    # Créer/mettre à jour le schéma
    docker exec www-ora bash -c "cd ora && php bin/console doctrine:schema:update --force --no-interaction"
    
    if [ $? -ne 0 ]; then
        print_error "Échec de la création de la structure de la base de données."
        exit 1
    fi
    
    print_success "Structure de la base de données créée ✓"
}

# Fonction pour vérifier/créer le fichier .env.local
setup_openrouter_key() {
    if [ ! -f ".env.local" ]; then
        print_warning "Aucune clé OpenRouter trouvée."
        echo
        echo "Pour utiliser OpenRouter, une clé API est nécessaire."
        echo "Inscription : https://openrouter.ai/"
        echo
        read -p "Entrez votre clé OpenRouter (laisser vide si vous n'en avez pas encore) : " openrouter_key

        if [ -z "$openrouter_key" ]; then
            echo "OPENROUTER_API_KEY=" > .env.local
            print_warning "Aucune clé saisie."
            print_warning "Vous pourrez l'ajouter plus tard en éditant le fichier .env.local"
        else
            echo "OPENROUTER_API_KEY=$openrouter_key" > .env.local
            print_success "Clé OpenRouter enregistrée ✓"
        fi

        chmod 600 .env.local
        print_success "Fichier .env.local créé ✓"
    else
        print_success "Fichier .env.local déjà présent ✓"
    fi
}

# Fonction pour afficher les informations de connexion
show_info() {
    echo ""
    echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║     OpenRouterApplication - Environnement prêt ! 🚀       ║${NC}"
    echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "${BLUE}📱 Application Symfony:${NC}      http://localhost:9310"
    echo -e "${BLUE}🗄️  PhpMyAdmin:${NC}              http://localhost:9311"
    echo -e "${BLUE}📚 Documentation (MkDocs):${NC}  http://localhost:9312"
    echo -e "${BLUE}🐘 PostgreSQL:${NC}              localhost:5432"
    echo ""
    echo -e "${YELLOW}Commandes utiles:${NC}"
    echo -e "  • Voir les logs:           ${GREEN}docker compose logs -f${NC}"
    echo -e "  • Arrêter:                 ${GREEN}docker compose down${NC}"
    echo -e "  • Redémarrer:              ${GREEN}docker compose restart${NC}"
    echo -e "  • Accéder au conteneur:    ${GREEN}docker exec -it www-ora /bin/bash${NC}"
    echo ""
}

# Programme principal
main() {
    clear
    echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║     OpenRouterApplication - Script d'installation         ║${NC}"
    echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    
    # Vérifier si on est dans le bon répertoire
    if [ ! -f "docker-compose.yml" ]; then
        print_error "Le fichier docker-compose.yml n'a pas été trouvé."
        print_error "Veuillez exécuter ce script depuis le répertoire racine du projet."
        exit 1
    fi
    
    check_prerequisites
    setup_password
    setup_symfony_env
    start_containers
    wait_for_containers
    install_symfony_dependencies
    setup_database
    show_info
}

# Gestion des arguments
case "${1:-}" in
    --help|-h)
        echo "Usage: ./start.sh [OPTIONS]"
        echo ""
        echo "Options:"
        echo "  --help, -h     Afficher cette aide"
        echo "  --reset        Réinitialiser complètement l'environnement"
        echo ""
        exit 0
        ;;
    --reset)
        print_warning "Réinitialisation complète de l'environnement..."
        docker-compose down -v
        rm -f db_root_password.txt
        rm -f www/.env.bak
        print_success "Environnement réinitialisé. Relancez ./start.sh"
        exit 0
        ;;
    "")
        main
        ;;
    *)
        print_error "Option inconnue: $1"
        echo "Utilisez --help pour voir les options disponibles"
        exit 1
        ;;
esac