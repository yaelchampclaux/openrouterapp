# Utiliser une image de base Python
FROM python:3.9-slim

# Définir le répertoire de travail
WORKDIR /app

# Installer les dépendances système nécessaires
RUN apt-get update && apt-get install -y \
    libglib2.0-0 \
    libglib2.0-dev \
    && rm -rf /var/lib/apt/lists/*
	
# Installer mkdocs-with-pdf et le plugin Material pour MkDocs
RUN pip install mkdocs-with-pdf==0.9.3 mkdocs-material

# Exposer le port 8000
EXPOSE 8000

# Commande par défaut pour démarrer MkDocs
CMD ["mkdocs", "serve", "--dev-addr=0.0.0.0:8000"]

