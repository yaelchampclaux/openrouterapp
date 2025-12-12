# How Do Conversations Work?

This section explains the underlying logic behind conversation management in the OpenRouter Chat application, from creation to persistence and continuation.

## Key Concepts

In this application, a "conversation" is not just a series of exchanges, but a structured entity that maintains context and history.

*   **Conversation**: Represented by the `App\Entity\Conversation` entity. It groups a series of messages and is characterized by a unique `id`, a `title`, a creation date (`createdAt`), and the `modelId` of the AI model used for this conversation.
*   **Message**: Represented by the `App\Entity\Message` entity. Each message belongs to a specific conversation and contains its `content` (the text), its `role` ('user' for you or 'assistant' for the AI), and its creation date (`createdAt`).
*   **Context**: For an AI model to respond relevantly, it needs to remember previous exchanges. The "context" of a conversation is the set of all messages (user and assistant) that occurred in that conversation, presented in chronological order.

## Conversation Lifecycle

### 1. Starting a New Conversation

When you initiate your very first question after clicking "New Conversation" or during the initial page load:

1.  **Sending the Initial Prompt**: You enter a prompt and potentially a title for the chat, then click "Send".
2.  **Creating the `Conversation` Entity**:
    *   The `ChatController` receives your request on the `/chat/start` endpoint.
    *   It uses `ConversationService::createConversation()` to create a new `Conversation` instance.
    *   If you provided a title, it is used. Otherwise, the `ConversationService` generates a default title based on the first 50 characters of your initial prompt.
    *   Your initial prompt is saved as the first `Message` (`role: 'user'`) in this new conversation.
3.  **Sending to OpenRouter**: The `ConversationService` prepares an array of messages (containing only your initial prompt) and sends it to the OpenRouter API via the `OpenRouterService`.
4.  **Receiving the Response and Recording**:
    *   The AI's response is received.
    *   This response is saved as a new `Message` (`role: 'assistant'`) in the same `Conversation`.
    *   Additionally, a record is created in `App\Entity\ChatHistory` (for global history display) with the prompt, response, model, and conversation title.
5.  **Displaying the Response**: The AI's response is displayed in the user interface and the new conversation ID is returned to the frontend for future requests.

### 2. Continuing an Existing Conversation

Each time you send a new prompt after already receiving a response in the same session (without clicking "New Conversation"):

1.  **Sending the New Prompt**: Your new prompt is sent to the `/chat/continue/{conversationId}` endpoint. The `conversationId` is the ID of the current conversation, retrieved following the initial conversation creation.
2.  **Adding the New User Message**:
    *   The `ChatController` finds the corresponding `Conversation` via its `id`.
    *   The `ConversationService::continueConversation()` adds your new prompt as a `Message` (`role: 'user'`) to this existing conversation.
3.  **Retrieving the Complete Context**:
    *   The `ConversationService::getConversationContext()` is called. This method retrieves *all* messages (user and assistant) associated with this `Conversation` from the database.
    *   **This is the step that ensures the conversation's "memory".** All messages are sorted by creation date to maintain chronological order and are formatted to be sent to the OpenRouter API (an array of objects `{'role': 'user/assistant', 'content': '...'}`).
4.  **Sending to OpenRouter with Context**: The `OpenRouterService` sends the entire historical message context to the selected AI model. The model uses this context to generate a coherent response.
5.  **Receiving the Response and Recording**:
    *   The AI's response is received.
    *   It is saved as a new `Message` (`role: 'assistant'`) in the current `Conversation`.
    *   As before, a record is added to `ChatHistory` for persistence and easy consultation.
6.  **Displaying the Response**: The AI's response is displayed to the user.

## Persistence and History

All conversations and their messages are stored in a database.

*   `App\Entity\Conversation` stores the basic conversation information (ID, title, model, creation date).
*   `App\Entity\Message` stores each turn of speech (your prompt and the AI's response) and is linked to a `Conversation`.
*   `App\Entity\ChatHistory` is a separate table that keeps a simplified trace of each individual Q&A exchange, making it easy to display the global history of requests and responses, even if they are part of longer conversations.

This architecture ensures that your conversations are stored durably and that the AI model can always access the complete exchange history to maintain contextuality.