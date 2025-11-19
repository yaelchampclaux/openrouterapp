# Comment les Conversations Fonctionnent-elles ?

Cette section explique la logique sous-jacente à la gestion des conversations dans l'application OpenRouter Chat, de la création à la persistance et à la continuation.

## Concepts Clés

Dans cette application, une "conversation" n'est pas seulement une série d'échanges, mais une entité structurée qui maintient le contexte et l'historique.

*   **Conversation**: Représentée par l'entité `App\Entity\Conversation`. Elle regroupe une série de messages et est caractérisée par un `id` unique, un `title` (titre), une date de création (`createdAt`), et le `modelId` du modèle d'IA utilisé pour cette conversation.
*   **Message**: Représenté par l'entité `App\Entity\Message`. Chaque message appartient à une conversation spécifique et contient son `content` (le texte), son `role` ('user' pour vous ou 'assistant' pour l'IA), et sa date de création (`createdAt`).
*   **Contexte**: Pour qu'un modèle d'IA puisse répondre de manière pertinente, il a besoin de se souvenir des échanges précédents. Le "contexte" d'une conversation est l'ensemble de tous les messages (utilisateur et assistant) qui ont eu lieu dans cette conversation, présentés dans l'ordre chronologique.

## Cycle de Vie d'une Conversation

### 1. Démarrer une Nouvelle Conversation

Lorsque vous initiez votre toute première question après avoir cliqué sur "New Conversation" ou lors du chargement initial de la page:

1.  **Envoi du Prompt Initial**: Vous saisissez un prompt et potentiellement un titre pour le chat, puis cliquez sur "Send".
2.  **Création de l'Entité `Conversation`**:
    *   Le `ChatController` reçoit votre requête sur l'endpoint `/chat/start`.
    *   Il utilise le `ConversationService::createConversation()` pour créer une nouvelle instance de `Conversation`.
    *   Si vous avez fourni un titre, il est utilisé. Sinon, le `ConversationService` génère un titre par défaut basé sur les 50 premiers caractères de votre prompt initial.
    *   Votre prompt initial est enregistré comme le premier `Message` (`role: 'user'`) dans cette nouvelle conversation.
3.  **Envoi à OpenRouter**: Le `ConversationService` prépare un tableau de messages (contenant uniquement votre prompt initial) et l'envoie à l'API OpenRouter via l'`OpenRouterService`.
4.  **Réception de la Réponse et Enregistrement**:
    *   La réponse de l'IA est reçue.
    *   Cette réponse est enregistrée comme un nouveau `Message` (`role: 'assistant'`) dans la même `Conversation`.
    *   De plus, un enregistrement est créé dans l'`App\Entity\ChatHistory` (pour l'affichage global de l'historique) avec le prompt, la réponse, le modèle et le titre de la conversation.
5.  **Affichage de la Réponse**: La réponse de l'IA est affichée dans l'interface utilisateur et l'ID de la nouvelle conversation est renvoyé au frontend pour les requêtes futures.

### 2. Continuer une Conversation Existante

Chaque fois que vous envoyez un nouveau prompt après avoir déjà reçu une réponse dans la même session (sans avoir cliqué sur "New Conversation"):

1.  **Envoi du Nouveau Prompt**: Votre nouveau prompt est envoyé à l'endpoint `/chat/continue/{conversationId}`. L'`conversationId` est l'ID de la conversation en cours, récupéré suite à la création initiale de la conversation.
2.  **Ajout du Nouveau Message Utilisateur**:
    *   Le `ChatController` trouve la `Conversation` correspondante via son `id`.
    *   Le `ConversationService::continueConversation()` ajoute votre nouveau prompt comme un `Message` (`role: 'user'`) à cette conversation existante.
3.  **Récupération du Contexte Complet**:
    *   Le `ConversationService::getConversationContext()` est appelé. Cette méthode récupère *tous* les messages (utilisateur et assistant) associés à cette `Conversation` depuis la base de données.
    *   **C'est cette étape qui assure la "mémoire" de la conversation.** Tous les messages sont triés par date de création pour maintenir l'ordre chronologique et sont formatés de manière à être envoyés à l'API OpenRouter (un tableau d'objets `{'role': 'user/assistant', 'content': '...'}`).
4.  **Envoi à OpenRouter avec Contexte**: L'`OpenRouterService` envoie l'ensemble du contexte historique des messages au modèle d'IA sélectionné. Le modèle utilise ce contexte pour générer une réponse cohérente.
5.  **Réception de la Réponse et Enregistrement**:
    *   La réponse de l'IA est reçue.
    *   Elle est enregistrée comme un nouveau `Message` (`role: 'assistant'`) dans la `Conversation` actuelle.
    *   Comme précédemment, un enregistrement est ajouté à `ChatHistory` pour la persistance et la consultation facile.
6.  **Affichage de la Réponse**: La réponse de l'IA est affichée à l'utilisateur.

## Persistance et Historique

Toutes les conversations et leurs messages sont stockés dans une base de données.

*   L'`App\Entity\Conversation` stocke les informations de base de la conversation (ID, titre, modèle, date de création).
*   L'`App\Entity\Message` stocke chaque tour de parole (votre prompt et la réponse de l'IA) et est lié à une `Conversation`.
*   L'`App\Entity\ChatHistory` est une table séparée qui garde une trace simplifiée de chaque échange Q/R individuel, ce qui facilite l'affichage de l'historique global des requêtes et réponses, même si elles font partie de conversations plus longues.

Cette architecture garantit que vos conversations sont stockées de manière durable et que le modèle d'IA peut toujours accéder à l'historique complet des échanges pour maintenir la contextualité.