# Utilisation de l'Application OpenRouter Chat

Cette page vous guide à travers l'interface de l'application OpenRouter Chat, vous expliquant comment interagir avec les modèles d'IA et gérer vos conversations.

## Présentation de l'Interface

L'interface de l'application est conçue pour être intuitive et vous permettre de démarrer rapidement des conversations avec différents modèles d'IA.

![Schéma de l'interface OpenRouter Chat](img/chat_interface_overview.png)
_Note: L'image ci-dessus est un placeholder. Vous pouvez la remplacer par une capture d'écran réelle de votre interface pour une meilleure compréhension._

### Éléments Clés de l'Interface

1.  **Navigation Principale**
    *   **View Chat History**: Accédez à l'historique complet de toutes vos conversations passées.
    *   **Check Status**: Vérifiez le statut actuel du service OpenRouter.ai (utile en cas de problème).
    *   **Check Credits**: Consultez votre solde de crédits sur OpenRouter.ai.
    *   **Check Best AI 1 / Check Best AI 2**: Liens vers des classements et comparaisons de modèles d'IA pour vous aider à choisir.
    *   **New Conversation**: Redémarre l'interface pour une toute nouvelle conversation, effaçant le prompt actuel et la réponse.

2.  **Filtres de Modèles**
    Ces options vous permettent de filtrer la liste des modèles disponibles en fonction de leur statut ou de leurs capacités.
    *   **All**: Affiche tous les modèles disponibles.
    *   **Free**: Affiche uniquement les modèles gratuits.
    *   **Paid**: Affiche uniquement les modèles payants.
    *   **Coding Models (Checkbox)**: Cochez cette case pour afficher spécifiquement les modèles optimisés pour le codage.

3.  **Sélection du Modèle**
    Le menu déroulant `select id="model"` vous permet de choisir le modèle d'IA avec lequel vous souhaitez interagir. Une fois un modèle sélectionné, une brève description (si disponible) s'affichera en dessous.

4.  **Titre de la Conversation (Optionnel)**
    `input type="text" id="chat-title"`: Avant de lancer votre première question, vous pouvez saisir un titre pour votre conversation. Si ce champ est laissé vide, un titre sera automatiquement généré à partir de votre première question. Ce titre sera utilisé pour identifier facilement la conversation dans votre historique.

5.  **Zone de Saisie du Prompt**
    `textarea id="prompt"`: C'est ici que vous tapez vos questions, commandes ou instructions pour le modèle d'IA.

6.  **Bouton "Send"**
    `button id="send-button"`: Cliquez sur ce bouton pour envoyer votre prompt au modèle d'IA sélectionné et initier ou continuer une conversation.

7.  **Zone de Réponse**
    `div class="response" id="response"`: La réponse générée par le modèle d'IA s'affichera dans cette zone.

## Démarrer une Nouvelle Conversation

1.  **Choisissez un Modèle**: Sélectionnez le modèle d'IA que vous souhaitez utiliser dans le menu déroulant. Vous pouvez utiliser les filtres (All, Free, Paid, Coding Models) pour affiner votre choix.
2.  **Donnez un Titre (Optionnel)**: Entrez un titre descriptif pour votre conversation dans le champ "Enter chat title (optional)".
3.  **Écrivez votre Prompt**: Saisissez votre question ou commande dans la zone de texte prévue à cet effet.
4.  **Envoyez**: Cliquez sur le bouton "Send".

Votre première question lancera une nouvelle conversation. La réponse du modèle apparaîtra dans la zone de réponse.

## Continuer une Conversation

Une fois une conversation démarrée, chaque nouveau prompt que vous envoyez via la zone de texte et le bouton "Send" sera ajouté à la même conversation. Le modèle aura accès à l'historique complet de cette conversation pour contextualiser ses réponses.

## Gérer les Conversations

*   **Nouvelle Conversation**: Si vous souhaitez démarrer une conversation sans lien avec la précédente, cliquez sur le bouton "New Conversation". Cela effacera l'interface actuelle et vous permettra de commencer de zéro.
*   **Historique des Chats**: Vous pouvez consulter toutes vos conversations passées en cliquant sur "View Chat History". Chaque conversation sera listée avec son titre et vous pourrez y accéder pour revoir les échanges.