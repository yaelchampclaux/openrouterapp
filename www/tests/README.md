# Tests Unitaires - OpenRouter Application

## Installation

Ajoute les dépendances de test à ton `composer.json` :

```bash
docker-compose exec php composer require --dev phpunit/phpunit symfony/test-pack
```

## Structure des tests

```
tests/
├── bootstrap.php           # Bootstrap pour PHPUnit
├── Unit/                   # Tests unitaires (sans base de données)
│   ├── Controller/
│   │   └── ApiChatControllerTest.php
│   ├── Entity/
│   │   ├── ConversationTest.php
│   │   ├── MessageTest.php
│   │   └── SummaryTest.php
│   └── Service/
│       ├── ConversationServiceTest.php
│       ├── OpenRouterServiceTest.php
│       └── PdfTextExtractorTest.php
├── Integration/            # Tests d'intégration (avec base de données)
│   └── (à créer selon besoins)
├── Functional/             # Tests fonctionnels (requêtes HTTP)
│   └── (à créer selon besoins)
└── Fixtures/               # Fichiers de test (PDF, images, etc.)
    └── sample.pdf          # À créer pour les tests PDF
```

## Exécution des tests

### Tous les tests
```bash
docker-compose exec php ./vendor/bin/phpunit
```

### Tests unitaires uniquement
```bash
docker-compose exec php ./vendor/bin/phpunit --testsuite Unit
```

### Un fichier de test spécifique
```bash
docker-compose exec php ./vendor/bin/phpunit tests/Unit/Service/ConversationServiceTest.php
```

### Avec couverture de code
```bash
docker-compose exec php ./vendor/bin/phpunit --coverage-html coverage/
```

### Mode verbose
```bash
docker-compose exec php ./vendor/bin/phpunit -v
```

## Couverture des tests

### Services testés

| Service | Méthodes testées | Couverture estimée |
|---------|------------------|-------------------|
| ConversationService | createConversation, continueConversation, getConversationContext, generateTitle | ~95% |
| OpenRouterService | sendMessage (normal, images, PDF Claude, PDF Gemini, erreurs) | ~90% |
| PdfTextExtractor | extractTextFromBase64 (erreurs) | ~60%* |

*Nécessite un fichier PDF de fixture pour une couverture complète.

### Entités testées

| Entité | Tests | Couverture estimée |
|--------|-------|-------------------|
| Conversation | Getters/Setters, addMessage, initialisation | ~100% |
| Message | Getters/Setters, fluent interface | ~100% |
| Summary | Getters/Setters, lifecycle callback | ~100% |

### Contrôleurs testés

| Contrôleur | Méthodes testées | Notes |
|------------|------------------|-------|
| ApiChatController | modelCanProcessFiles (méthode privée) | Via réflexion |

## Ajout d'un fichier PDF de fixture

Pour compléter les tests du `PdfTextExtractor`, créer un fichier PDF de test :

1. Créer le dossier : `mkdir -p tests/Fixtures`
2. Placer un fichier PDF simple nommé `sample.pdf` dans ce dossier

## Bonnes pratiques

1. **Arrange-Act-Assert** : Chaque test suit ce pattern
2. **Nommage** : `testMethodNameWithCondition` ou `testMethodName_Condition_ExpectedResult`
3. **Isolation** : Chaque test est indépendant (setUp/tearDown)
4. **Mocking** : Utilisation de PHPUnit MockObject pour les dépendances
5. **Data Providers** : Pour tester plusieurs cas similaires (à ajouter selon besoins)

## Prochaines étapes

1. [ ] Ajouter des tests d'intégration avec base de données
2. [ ] Ajouter des tests fonctionnels pour les endpoints API
3. [ ] Créer des fixtures PDF pour les tests complets
4. [ ] Configurer la CI/CD pour exécuter les tests automatiquement
5. [ ] Ajouter des data providers pour les cas de tests répétitifs
