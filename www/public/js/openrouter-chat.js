// ora_v2e//www/public/js/openrouter-chat.js
const modelSelect = document.getElementById('model');
const responseDiv = document.getElementById('response');
const modelDescriptionDiv = document.getElementById('model-description');
const codingFilter = document.getElementById('coding-filter');
const imageProcessingFilter = document.getElementById('image-processing-filter');
const pdfProcessingFilter = document.getElementById('pdf-processing-filter');
const promptElement = document.getElementById('prompt');
const sendButton = document.getElementById('send-button');
const chatTitleInput = document.getElementById('chat-title');

let allModels = [];
const DEFAULT_MODEL = "google/gemini-2.5-flash-lite-preview-06-17";//"openai/gpt-4o-mini-search-preview";//"openai/gpt-4.1-mini";
const CODE_KEYWORDS = ['code', 'coder', 'coding', 'program', 'programming', 'developer', 'development'];

let currentConversationId = null; 

const uploadFileButton = document.getElementById('upload-file-button');
const fileUploadInput = document.getElementById('file-upload');

// Initialize when document is ready - FIXED VERSION
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 DOM Content Loaded - Starting initialization');
    
    // Wait for external libraries to load
    const checkLibrariesAndInit = () => {
        if (typeof marked !== 'undefined' && typeof hljs !== 'undefined') {
            console.log('✅ All libraries loaded, initializing...');
            initializeApp();
        } else {
            console.log('⏳ Waiting for libraries...', {
                marked: typeof marked !== 'undefined',
                hljs: typeof hljs !== 'undefined'
            });
            setTimeout(checkLibrariesAndInit, 100);
        }
    };
    
    checkLibrariesAndInit();
});

// Separate initialization function
async function initializeApp() {
    try {
        console.log('🔧 Starting app initialization...');
        
        // Load models first
        const modelsLoaded = await loadModels();
        if (!modelsLoaded) {
            console.error('❌ Failed to load models');
            displayFormattedResponse('Failed to load models. Please refresh the page.');
            return;
        }
        
        console.log('✅ Models loaded successfully');
        updateSendButtonText();
        
        // Check for conversation resume data
        const resumeData = sessionStorage.getItem('resume_conversation');
        if (resumeData) {
            console.log('📋 Found resume data, processing...');
            const data = JSON.parse(resumeData);
            sessionStorage.removeItem('resume_conversation');
            
            selectModelById(data.modelId);
            chatTitleInput.value = data.title;
            loadConversationContext(data);
        }
        
        console.log('🎉 App initialization complete');
    } catch (error) {
        console.error('💥 Initialization error:', error);
        displayFormattedResponse('Failed to initialize the application. Please refresh the page.');
    }
}

// Enhanced loadModels function with better error handling
async function loadModels() {
    try {
        console.log('📡 Fetching models from API...');
        displayFormattedResponse('Loading models...'); 
        
        const res = await fetch('/api/models');
        
        if (!res.ok) {
            const errorText = await res.text();
            console.error('❌ API Error:', res.status, errorText);
            throw new Error(`API returned ${res.status}: ${errorText}`);
        }
        
        const data = await res.json();
        console.log('📦 Received models data:', data);
        
        if (!data || !data.models || !Array.isArray(data.models)) {
            console.error('❌ Invalid models data structure:', data);
            throw new Error('Invalid models data structure received from API');
        }
        
        allModels = data.models;
        console.log(`✅ Loaded ${allModels.length} models`);
        
        if (allModels.length === 0) {
            console.warn('⚠️ No models received from API');
            displayFormattedResponse('No models available. Please check your API connection.');
            return false;
        }
        
        responseDiv.textContent = ''; 
        
        // Update counts and filters
        updateModelCounts();
        filterModels();
        updateAllFilterCounts();
        setDefaultModel();
        
        // Update file upload button visibility
        const initialModel = modelSelect.options[modelSelect.selectedIndex];
        if (initialModel) {
            const initialModelId = initialModel.value;
            const initialModelData = allModels.find(m => m.id === initialModelId);
            
            if (initialModelData && (initialModelData.canProcessFiles || initialModelData.pdfSupport !== 'none')) {
                uploadFileButton.style.display = 'inline-block';
            } else {
                uploadFileButton.style.display = 'none';
            }
        }
        
        console.log('🎯 Model selector populated with', modelSelect.options.length, 'options');
        return true;
        
    } catch (err) {
        console.error('💥 loadModels error:', err);
        displayFormattedResponse(`Failed to load models: ${err.message}. Please try refreshing the page.`);
        return false;
    }
}

// Add debug function to check model loading
function debugModelLoading() {
    console.log('🔍 DEBUG INFO:');
    console.log('- allModels length:', allModels.length);
    console.log('- modelSelect options:', modelSelect.options.length);
    console.log('- modelSelect innerHTML:', modelSelect.innerHTML.substring(0, 200));
    console.log('- First few models:', allModels.slice(0, 3));
}

// Enhanced filterModels with debug logging
function filterModels() {
    console.log('🔄 Filtering models...');
    
    if (allModels.length === 0) {
        console.warn('⚠️ No models to filter!');
        return;
    }
    
    const filterValue = document.querySelector('input[name="model-filter"]:checked').value;
    const codingFilterChecked = codingFilter.checked;
    const imageProcessingFilterChecked = imageProcessingFilter.checked;
    const pdfProcessingFilterChecked = pdfProcessingFilter.checked;
    const xlsxProcessingFilterChecked = document.getElementById('xlsx-processing-filter').checked;
    
    console.log('🎛️ Current filters:', {
        filterValue,
        codingFilterChecked,
        imageProcessingFilterChecked,
        pdfProcessingFilterChecked,
        xlsxProcessingFilterChecked
    });
    
    let filteredModels = allModels;
    
    // Apply price filter
    if (filterValue === 'free') {
        filteredModels = filteredModels.filter(m => m.is_free === true);
    } else if (filterValue === 'paid') {
        filteredModels = filteredModels.filter(m => m.is_free === false);
    }
    
    // Update filter counts based on current price filter
    updateAllFilterCounts();
    
    // Apply feature filters
    let activeFeatureFilters = 0;
    
    if (codingFilterChecked) {
        filteredModels = filteredModels.filter(m => isCodeModel(m));
        activeFeatureFilters++;
    }
    
    if (imageProcessingFilterChecked) {
        filteredModels = filteredModels.filter(m => canProcessImages(m));
        activeFeatureFilters++;
    }
    
    if (pdfProcessingFilterChecked) {
        filteredModels = filteredModels.filter(m => canProcessPDFs(m));
        activeFeatureFilters++;
    }
    
    if (xlsxProcessingFilterChecked) {
        filteredModels = filteredModels.filter(m => canProcessXLSX(m));
        activeFeatureFilters++;
    }

    console.log(`📊 Filtered to ${filteredModels.length} models`);

    // Update main counts after all filters
    const allFilteredCount = filteredModels.length;
    const freeFilteredCount = filteredModels.filter(m => m.is_free === true).length;
    const paidFilteredCount = filteredModels.filter(m => m.is_free === false).length;
    
    document.getElementById('all-count').textContent = `(${allFilteredCount})`;
    document.getElementById('free-count').textContent = `(${freeFilteredCount})`;
    document.getElementById('paid-count').textContent = `(${paidFilteredCount})`;
    
    const currentSelection = modelSelect.value;
    
    modelSelect.innerHTML = filteredModels.map(m => {
        const priceText = formatPrice(m.total_price);
        return `
            <option value="${m.id}" 
                    data-description="${m.description}" 
                    data-price="${m.total_price}">
                ${m.label} - ${priceText}
            </option>
        `;
    }).join('');
    
    console.log(`✅ Model select populated with ${modelSelect.options.length} options`);
    
    if (currentSelection) {
        const matchingOption = Array.from(modelSelect.options).find(
            option => option.value === currentSelection
        );
        
        if (matchingOption) {
            modelSelect.value = currentSelection;
        } else if (modelSelect.options.length > 0) {
            modelSelect.selectedIndex = 0;
        }
    }
    
    displayModelDescription();

    const selectedModel = modelSelect.options[modelSelect.selectedIndex];
    const selectedModelId = selectedModel?.value;
    const currentModel = allModels.find(m => m.id === selectedModelId);
    
    if (currentModel && (currentModel.canProcessFiles || currentModel.pdfSupport !== 'none')) {
        uploadFileButton.style.display = 'inline-block';
    } else {
        uploadFileButton.style.display = 'none';
    }
}

// Fonction pour extraire le texte brut d'un élément code
function extractRawTextFromCodeElement(codeElement) {
    // Méthode 1: Utiliser l'attribut data si disponible
    if (codeElement.hasAttribute('data-raw-code')) {
        return codeElement.getAttribute('data-raw-code');
    }
    
    // Méthode 2: Parcourir tous les nœuds texte
    let text = '';
    const walker = document.createTreeWalker(
        codeElement,
        NodeFilter.SHOW_TEXT,
        null,
        false
    );
    
    let node;
    while (node = walker.nextNode()) {
        text += node.textContent;
    }
    
    // Méthode 3: Si pas de texte trouvé, utiliser textContent directement
    if (!text.trim() && codeElement.textContent) {
        text = codeElement.textContent;
    }
    
    // Méthode 4: Si encore rien, utiliser innerText
    if (!text.trim() && codeElement.innerText) {
        text = codeElement.innerText;
    }
    
    return text;
}

// Fonction pour créer et gérer le bouton Copy
function addCopyButtonToCodeBlock(preElement, codeElement, originalCode, language) {
    // Vérifier si le bouton existe déjà
    if (preElement.querySelector('.copy-code-button')) {
        return;
    }
    
    console.log(`🔧 Adding copy button for ${language} code (${originalCode.length} chars)`);
    
    const copyButton = document.createElement('button');
    copyButton.className = 'copy-code-button';
    copyButton.innerHTML = '<i class="fas fa-copy"></i> Copy';
    copyButton.setAttribute('title', `Copy ${language} code to clipboard`);
    copyButton.type = 'button'; // Explicitement définir le type
    
    // Stocker le code original dans l'élément
    copyButton.setAttribute('data-code', originalCode);
    codeElement.setAttribute('data-raw-code', originalCode);
    
    // Log pour debug
    console.log('📋 Stored code sample:', originalCode.substring(0, 100) + '...');
    
    copyButton.addEventListener('click', async function(event) {
        event.preventDefault();
        event.stopPropagation();
        
        console.log('🖱️ Copy button clicked');
        
        // Récupérer le code à copier
        let textToCopy = this.getAttribute('data-code') || originalCode;
        
        if (!textToCopy || !textToCopy.trim()) {
            console.error('❌ No text to copy found!');
            this.innerHTML = '<i class="fas fa-exclamation-triangle"></i> No text';
            this.style.background = 'linear-gradient(135deg, #f59e0b, #d97706)';
            
            setTimeout(() => {
                this.innerHTML = '<i class="fas fa-copy"></i> Copy';
                this.style.background = 'linear-gradient(135deg, #3b82f6, #1d4ed8)';
            }, 2000);
            return;
        }
        
        console.log('📋 Attempting to copy:', textToCopy.length, 'characters');
        console.log('📋 First 100 chars:', textToCopy.substring(0, 100));
        
        // Méthode 1: API Clipboard moderne
        if (navigator.clipboard && window.isSecureContext) {
            try {
                await navigator.clipboard.writeText(textToCopy);
                console.log('✅ Clipboard API copy successful');
                
                this.innerHTML = '<i class="fas fa-check"></i> Copied!';
                this.style.background = 'linear-gradient(135deg, #10b981, #059669)';
                
                setTimeout(() => {
                    this.innerHTML = '<i class="fas fa-copy"></i> Copy';
                    this.style.background = 'linear-gradient(135deg, #3b82f6, #1d4ed8)';
                }, 2000);
                return;
                
            } catch (err) {
                console.warn('⚠️ Clipboard API failed:', err);
            }
        }
        
        // Méthode 2: Fallback avec textarea et execCommand
        try {
            console.log('🔄 Using fallback method...');
            
            const textArea = document.createElement('textarea');
            textArea.value = textToCopy;
            
            // Styles pour rendre invisible mais sélectionnable
            textArea.style.position = 'fixed';
            textArea.style.left = '-9999px';
            textArea.style.top = '-9999px';
            textArea.style.width = '1px';
            textArea.style.height = '1px';
            textArea.style.padding = '0';
            textArea.style.border = 'none';
            textArea.style.outline = 'none';
            textArea.style.boxShadow = 'none';
            textArea.style.background = 'transparent';
            textArea.setAttribute('readonly', '');
            
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            textArea.setSelectionRange(0, textToCopy.length);
            
            const successful = document.execCommand('copy');
            document.body.removeChild(textArea);
            
            if (successful) {
                console.log('✅ Fallback copy successful');
                
                this.innerHTML = '<i class="fas fa-check"></i> Copied!';
                this.style.background = 'linear-gradient(135deg, #10b981, #059669)';
                
                setTimeout(() => {
                    this.innerHTML = '<i class="fas fa-copy"></i> Copy';
                    this.style.background = 'linear-gradient(135deg, #3b82f6, #1d4ed8)';
                }, 2000);
                
            } else {
                throw new Error('execCommand returned false');
            }
            
        } catch (fallbackErr) {
            console.error('❌ All copy methods failed:', fallbackErr);
            
            this.innerHTML = '<i class="fas fa-times"></i> Failed';
            this.style.background = 'linear-gradient(135deg, #ef4444, #dc2626)';
            
            // Afficher une alerte comme dernier recours
            setTimeout(() => {
                if (confirm('Copy failed. Would you like to see the code in a popup so you can copy it manually?')) {
                    const popup = window.open('', '_blank', 'width=800,height=600');
                    popup.document.write(`
                        <html>
                            <head><title>Code to Copy</title></head>
                            <body style="font-family: monospace; padding: 20px;">
                                <h2>${language} Code:</h2>
                                <textarea style="width: 100%; height: 80%; font-family: monospace;" readonly>${textToCopy}</textarea>
                                <br><br>
                                <button onclick="document.querySelector('textarea').select(); document.execCommand('copy'); alert('Copied!');">Copy All</button>
                            </body>
                        </html>
                    `);
                }
                
                this.innerHTML = '<i class="fas fa-copy"></i> Copy';
                this.style.background = 'linear-gradient(135deg, #3b82f6, #1d4ed8)';
            }, 1000);
        }
    });
    
    // Ajouter le bouton au bloc pre
    preElement.style.position = 'relative';
    preElement.appendChild(copyButton);
    
    console.log('✅ Copy button added successfully');
}

// Version mise à jour de la fonction displayFormattedResponse
// Remplacer la partie qui traite les blocs de code par ceci :
function processCodeBlocksForCopy(tempDiv) {
    const codeBlocks = tempDiv.querySelectorAll('pre code');
    console.log(`🔍 Processing ${codeBlocks.length} code blocks for copy functionality`);
    
    codeBlocks.forEach((codeBlock, index) => {
        const preBlock = codeBlock.parentElement;
        console.log(`📦 Processing code block ${index + 1}`);
        
        // Extraire le code original AVANT toute transformation
        let originalCode = '';
        
        // Si le code a été parsé par marked, il pourrait être dans textContent
        if (codeBlock.textContent) {
            originalCode = codeBlock.textContent;
        }
        
        // Nettoyer le code si nécessaire
        originalCode = originalCode.replace(/^\s+|\s+$/g, ''); // Trim
        
        // Détecter le langage
        let language = 'plaintext';
        const classes = Array.from(codeBlock.classList);
        for (const cls of classes) {
            if (cls.startsWith('language-')) {
                language = cls.replace('language-', '');
                break;
            }
        }
        
        console.log(`📝 Code block ${index + 1}: ${language}, ${originalCode.length} chars`);
        
        // Re-appliquer la coloration syntaxique
        if (typeof hljs !== 'undefined') {
            try {
                let result;
                if (language !== 'plaintext' && hljs.getLanguage(language)) {
                    result = hljs.highlight(originalCode, { language: language });
                } else {
                    result = hljs.highlightAuto(originalCode);
                    language = result.language || 'plaintext';
                }
                
                codeBlock.innerHTML = result.value;
                codeBlock.classList.add('hljs');
                
            } catch (err) {
                console.error('❌ Highlighting failed:', err);
                codeBlock.innerHTML = originalCode.replace(/</g, '&lt;').replace(/>/g, '&gt;');
            }
        }
        
        // Ajouter l'indicateur de langage
        preBlock.setAttribute('data-language', language);
        
        // Ajouter le bouton de copie avec le code original
        addCopyButtonToCodeBlock(preBlock, codeBlock, originalCode, language);
    });
}

// Fonction pour afficher la réponse formatée en Markdown
// SOLUTION COMPLETE - Remplacer entièrement displayFormattedResponse

function displayFormattedResponse(responseText) {
    console.log('🔧 Processing response text');
    
    if (!responseText || responseText.trim() === '') {
        responseDiv.innerHTML = ''; 
        return;
    }

    if (typeof marked === 'undefined' || typeof hljs === 'undefined') {
        responseDiv.innerHTML = `<pre style="white-space: pre-wrap; color: #e2e8f0;">${responseText}</pre>`;
        return;
    }

    try {
        // Configuration marked
        marked.setOptions({
            breaks: true,
            gfm: true,
            sanitize: false,
            smartLists: true,
            smartypants: true,
            highlight: null
        });
        
        // Parser markdown
        const htmlContent = marked.parse(responseText);
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = htmlContent;
        
        // Traiter les blocs de code avec numérotation globale
        const codeBlocks = tempDiv.querySelectorAll('pre code');
        
        codeBlocks.forEach((codeElement, index) => {
            const preElement = codeElement.parentElement;
            const originalCode = codeElement.textContent || '';
            
            // Déterminer le langage
            let language = 'plaintext';
            for (const className of codeElement.classList) {
                if (className.startsWith('language-')) {
                    language = className.replace('language-', '');
                    break;
                }
            }
            
            console.log(`Processing block ${index}: ${language} (${originalCode.length} chars)`);
            
            // Appliquer coloration
            if (hljs) {
                try {
                    const result = language !== 'plaintext' && hljs.getLanguage(language) 
                        ? hljs.highlight(originalCode, { language }) 
                        : hljs.highlightAuto(originalCode);
                    codeElement.innerHTML = result.value;
                    codeElement.classList.add('hljs');
                } catch (e) {
                    console.error('Highlighting failed:', e);
                }
            }
            
            // Style du pre
            preElement.style.position = 'relative';
            preElement.setAttribute('data-language', language);
            
            // Créer identifiant unique
            const uniqueId = `copyBtn_${Date.now()}_${index}`;
            
            // Créer le bouton
            const copyButton = document.createElement('button');
            copyButton.id = uniqueId;
            copyButton.innerHTML = '<i class="fas fa-copy"></i> Copy';
            copyButton.title = `Copy ${language} code`;
            copyButton.className = 'copy-code-button';
            copyButton.type = 'button';
            
            // Styles inline
            Object.assign(copyButton.style, {
                position: 'absolute',
                top: '10px',
                right: '10px',
                background: 'linear-gradient(135deg, #3b82f6, #1d4ed8)',
                color: 'white',
                border: 'none',
                padding: '6px 12px',
                borderRadius: '4px',
                cursor: 'pointer',
                zIndex: '10',
                fontSize: '12px',
                fontFamily: 'inherit'
            });
            
            // Stocker le code dans un attribut data
            copyButton.setAttribute('data-code-to-copy', originalCode);
            
            preElement.appendChild(copyButton);
        });
        
        // Afficher le contenu
        responseDiv.classList.remove('loading');
        responseDiv.innerHTML = tempDiv.innerHTML;
        
        // Attacher les événements APRES insertion dans le DOM
        setTimeout(() => {
            attachCopyEvents();
        }, 100);
        
    } catch (error) {
        console.error('Formatting error:', error);
        responseDiv.innerHTML = `<pre style="color: #ef4444;">${responseText}</pre>`;
    }
}

// Fonction séparée pour attacher les événements de copie
function attachCopyEvents() {
    const copyButtons = responseDiv.querySelectorAll('.copy-code-button');
    
    copyButtons.forEach((button, index) => {
        // Supprimer les anciens événements
        button.replaceWith(button.cloneNode(true));
        const newButton = responseDiv.querySelectorAll('.copy-code-button')[index];
        
        newButton.addEventListener('click', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const codeText = this.getAttribute('data-code-to-copy');
            console.log(`📋 Copying code: "${codeText.substring(0, 50)}..." (${codeText.length} chars)`);
            
            if (!codeText) {
                console.error('No code to copy');
                return;
            }
            
            try {
                // Tentative 1: API Clipboard moderne
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(codeText);
                    console.log('✅ Clipboard API successful');
                } else {
                    // Tentative 2: Méthode fallback
                    const textArea = document.createElement('textarea');
                    textArea.value = codeText;
                    textArea.style.cssText = 'position:fixed;left:-9999px;top:-9999px;opacity:0;';
                    
                    document.body.appendChild(textArea);
                    textArea.focus();
                    textArea.select();
                    textArea.setSelectionRange(0, codeText.length);
                    
                    const success = document.execCommand('copy');
                    document.body.removeChild(textArea);
                    
                    if (!success) throw new Error('execCommand failed');
                    console.log('✅ Fallback successful');
                }
                
                // Feedback visuel
                this.innerHTML = '<i class="fas fa-check"></i> Copied!';
                this.style.background = 'linear-gradient(135deg, #10b981, #059669)';
                
                setTimeout(() => {
                    this.innerHTML = '<i class="fas fa-copy"></i> Copy';
                    this.style.background = 'linear-gradient(135deg, #3b82f6, #1d4ed8)';
                }, 2000);
                
            } catch (error) {
                console.error('❌ Copy failed completely:', error);
                
                // Dernière solution : prompt
                this.innerHTML = '<i class="fas fa-times"></i> Failed';
                this.style.background = 'linear-gradient(135deg, #ef4444, #dc2626)';
                
                setTimeout(() => {
                    prompt('Copy this code manually (Ctrl+A then Ctrl+C):', codeText);
                    this.innerHTML = '<i class="fas fa-copy"></i> Copy';
                    this.style.background = 'linear-gradient(135deg, #3b82f6, #1d4ed8)';
                }, 1000);
            }
        });
        
        console.log(`✅ Event attached to button ${index}`);
    });
    
    console.log(`📋 Total copy buttons with events: ${copyButtons.length}`);
}

// Fonction utilitaire pour échapper le HTML
function escapeHTML(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// Enhanced function to show loading state
function showLoadingResponse(message = 'Generating response...') {
    responseDiv.classList.add('loading');
    responseDiv.innerHTML = `
        <div style="display: flex; align-items: center; color: #94a3b8;">
            <div style="margin-right: 12px;">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <span>${message}</span>
        </div>
    `;
}

// Function to enhance existing content (for conversation loading)
function enhanceExistingContent() {
    // Re-process any existing code blocks
    document.querySelectorAll('.response pre code').forEach((codeBlock) => {
        if (!codeBlock.parentElement.querySelector('.copy-code-button')) {
            hljs.highlightElement(codeBlock);
            
            const preBlock = codeBlock.parentElement;
            const copyButton = document.createElement('button');
            copyButton.className = 'copy-code-button';
            copyButton.innerHTML = '<i class="fas fa-copy"></i> Copy';
            
            copyButton.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(codeBlock.textContent);
                    copyButton.innerHTML = '<i class="fas fa-check"></i> Copied!';
                    copyButton.style.background = 'linear-gradient(135deg, #10b981, #059669)';
                    setTimeout(() => {
                        copyButton.innerHTML = '<i class="fas fa-copy"></i> Copy';
                        copyButton.style.background = 'linear-gradient(135deg, #3b82f6, #1d4ed8)';
                    }, 2000);
                } catch (err) {
                    console.error('Copy error:', err);
                }
            });
            
            preBlock.style.position = 'relative';
            preBlock.appendChild(copyButton);
        }
    });
}

// Load conversation context
async function loadConversationContext(resumeData) {
    try {
        responseDiv.textContent = 'Loading conversation context...';
        
        if (resumeData.contextType === 'complete') {
            
            const response = await fetch(`/api/chat-history/${resumeData.messageId}/conversation-thread`);
            
            if (!response.ok) {
                const errorData = await response.json();
                console.error("Chat history API error:", errorData);
                throw new Error('Failed to load chat history: ' + (errorData.error || response.statusText));
            }
            
            const thread = await response.json();
            
            let contextText = "";
            const currentIndex = thread.currentMessageIndex;
            
            for (let i = 0; i <= currentIndex; i++) {
                const message = thread.messages[i];
                contextText += "USER: " + message.prompt + "\n\n" +
                               "ASSISTANT: " + message.response + "\n\n";
            }
            
            promptElement.value = '--- Previous conversation ---\n\n' + 
                contextText + 
                '--- End of previous conversation ---\n\n' +
                'Continue this conversation based on the above context:';
                
        } else if (resumeData.contextType === 'summary') {
           
            const response = await fetch(`/api/chat-history/${resumeData.messageId}/summary`);
            
            if (!response.ok) {
                const errorData = await response.json();
                console.error("Summary API error:", errorData);
                throw new Error('Failed to load summary: ' + (errorData.error || response.statusText));
            }
            
            const data = await response.json();
            
            if (!data.summary || data.summary.trim() === '') {
                promptElement.value = '--- The summary was empty, please provide context ---\n\n' + 
                    'This conversation was about: ' + resumeData.title + '\n\n' +
                    'Please enter your question to continue:';
            } else {
                promptElement.value = '--- Summary of previous conversation ---\n\n' + 
                    data.summary + 
                    '\n\n--- End of summary ---\n\n' +
                    'Continue this conversation based on the above summary:';
            }
        } else {
            throw new Error('Invalid context type: ' + resumeData.contextType);
        }

        setTimeout(() => {
            enhanceExistingContent();
        }, 100);
        
        displayFormattedResponse('Conversation context loaded. You can now continue the conversation.');
        


    } catch (error) {
        console.error('Error loading conversation context:', error);
        displayFormattedResponse('Error loading conversation context: ' + error.message);
    }
}

// Select a model by ID
function selectModelById(modelId) {
    for (let i = 0; i < modelSelect.options.length; i++) {
        if (modelSelect.options[i].value === modelId) {
            modelSelect.selectedIndex = i;
            displayModelDescription();
            return true;
        }
    }
    
    document.querySelector('input[name="model-filter"][value="all"]').checked = true;
    filterModels();
    
    for (let i = 0; i < modelSelect.options.length; i++) {
        if (modelSelect.options[i].value === modelId) {
            modelSelect.selectedIndex = i;
            displayModelDescription();
            return true;
        }
    }
    
    console.warn(`Model ${modelId} not found in available models`);
    return false;
}

function updateModelCounts() {
    const allCount = allModels.length;
    const freeCount = allModels.filter(m => m.is_free === true).length;
    const paidCount = allModels.filter(m => m.is_free === false).length;
    
    document.getElementById('all-count').textContent = `(${allCount})`;
    document.getElementById('free-count').textContent = `(${freeCount})`;
    document.getElementById('paid-count').textContent = `(${paidCount})`;
}

function updateAllFilterCounts() {
    const filterValue = document.querySelector('input[name="model-filter"]:checked').value;
    let baseModels = allModels;
    
    if (filterValue === 'free') {
        baseModels = baseModels.filter(m => m.is_free === true);
    } else if (filterValue === 'paid') {
        baseModels = baseModels.filter(m => m.is_free === false);
    }
    
    // Update coding models count
    const codingModelsCount = baseModels.filter(m => isCodeModel(m)).length;
    document.getElementById('coding-count').textContent = `(${codingModelsCount})`;
    
    // Update image processing models count
    const imageProcessingCount = baseModels.filter(m => canProcessImages(m)).length;
    document.getElementById('image-processing-count').textContent = `(${imageProcessingCount})`;
    
    // Update PDF processing models count
    const pdfProcessingCount = baseModels.filter(m => canProcessPDFs(m)).length;
    document.getElementById('pdf-processing-count').textContent = `(${pdfProcessingCount})`;

    // NEW: XLSX Processing Count
    const xlsxProcessingCount = baseModels.filter(m => canProcessXLSX(m)).length;
    document.getElementById('xlsx-processing-count').textContent = `(${xlsxProcessingCount})`;
}

function canProcessImages(model) {
    // Un modèle peut traiter des images s'il a canProcessFiles ou si c'est explicitement un modèle vision
    return model.canProcessFiles === true || 
           (model.description && model.description.toLowerCase().includes('vision')) ||
           (model.id && model.id.toLowerCase().includes('vision'));
}

function canProcessPDFs(model) {
    // Un modèle peut traiter des PDFs selon le niveau de support
    return model.pdfSupport === 'native' || 
           model.pdfSupport === 'limited' || 
           model.pdfSupport === 'possible';
}

function canProcessXLSX(model) {
    const idLower = model.id.toLowerCase();
    const descLower = (model.description || '').toLowerCase();
    const labelLower = (model.label || '').toLowerCase();
    
    const xlsxKeywords = [
        'xlsx', 'excel', 'spreadsheet', 'tabular data', 
        'data analysis', 'data processing', 'xls', 'tabular'
    ];
    
    return xlsxKeywords.some(keyword => 
        idLower.includes(keyword) || 
        descLower.includes(keyword) || 
        labelLower.includes(keyword)
    );
}

function setDefaultModel() {
    for (let i = 0; i < modelSelect.options.length; i++) {
        if (modelSelect.options[i].value === DEFAULT_MODEL) {
            modelSelect.selectedIndex = i;
            break;
        }
    }
    displayModelDescription();
}

function isCodeModel(model) {
    const idLower = model.id.toLowerCase();
    const descLower = (model.description || '').toLowerCase();
    const labelLower = (model.label || '').toLowerCase();
    
    if (idLower.includes('codellama') || 
        idLower.includes('deepseek-coder') || 
        idLower.includes('wizardcoder') || 
        idLower.includes('phind') ||
        idLower.includes('code-') ||
        idLower.includes('coder')) {
        return true;
    }
    
    for (const keyword of CODE_KEYWORDS) {
        if (descLower.includes(keyword) || labelLower.includes(keyword)) {
            return true;
        }
    }
    
    return false;
}

function formatPrice(pricePerMillion) {
    if (pricePerMillion === 0) {
        return "Free";
    }
    return `$${pricePerMillion.toFixed(3)}/M`; 
}

function displayModelDescription() {
    const selectedOption = modelSelect.options[modelSelect.selectedIndex];
    const description = selectedOption?.getAttribute('data-description') || '';
    const modelId = selectedOption?.value || '';
    
    // Trouver le modèle complet pour avoir accès à pdfSupport
    const currentModel = allModels.find(m => m.id === modelId);
    
    let descriptionText = description;
    
    // Ajouter des informations sur le support des fichiers si pertinent
    if (currentModel && (currentModel.canProcessFiles || currentModel.pdfSupport !== 'none')) {
        descriptionText += '\n\n📎 File support: ';
        
        if (currentModel.pdfSupport === 'native') {
            descriptionText += '✅ Full PDF and image support';
        } else if (currentModel.pdfSupport === 'limited') {
            descriptionText += '⚠️ Images supported, PDF support may be limited';
        } else if (currentModel.pdfSupport === 'possible') {
            descriptionText += '🔄 Image support confirmed, PDF support varies';
        } else if (currentModel.canProcessFiles) {
            descriptionText += '🖼️ Image support only';
        }
    }
    
    modelDescriptionDiv.textContent = descriptionText;
}

function displayAllModels() {
    const sortedModels = [...allModels].sort((a, b) => b.total_price - a.total_price);

    let modelsListHtml = '<h2>Available Models (Ordered by Price):</h2><ul>';
    sortedModels.forEach(m => {
        const priceText = formatPrice(m.total_price);
        let fileSupport = '';
        
        if (m.pdfSupport === 'native') {
            fileSupport = ' - 📎 ✅ Full PDF and image support';
        } else if (m.pdfSupport === 'limited') {
            fileSupport = ' - 📎 ⚠️ Images supported, PDF support may be limited';
        } else if (m.pdfSupport === 'possible') {
            fileSupport = ' - 📎 🔄 Image support confirmed, PDF support varies';
        } else if (m.canProcessFiles) {
            fileSupport = ' - 📎 🖼️ Image support only';
        }
        
        modelsListHtml += `<li><strong>${m.label}</strong> - Price: ${priceText}${fileSupport}<br>${m.description}</li>`;
    });
    modelsListHtml += '</ul>';
    
    modelDescriptionDiv.innerHTML = modelsListHtml;
}

// Handle send message
async function handleSendMessage() {
    if (currentConversationId) {
        sendMessage();
        return;
    }

    const prompt = promptElement.value.trim();
    if (!prompt) {
        displayFormattedResponse('Please enter a prompt.');
        return;
    }

    const chatTitle = chatTitleInput.value.trim();

    if (!chatTitle) {
        showTitleConfirmationModal(prompt);
    } else {
        sendMessage();
    }
}

// Show title confirmation modal
function showTitleConfirmationModal(prompt) {
    const modalOverlay = document.createElement('div');
    modalOverlay.className = 'title-modal-overlay';
    modalOverlay.innerHTML = `
        <div class="title-modal-content">
            <label for="modal-chat-title">Chat title is empty. Do you want to continue with a default title, or enter a custom one?</label>
            <input type="text" id="modal-chat-title" placeholder="Enter custom title (optional)">
            <div class="title-modal-buttons">
                <button id="modal-yes-button">Yes (Default title)</button>
                <button id="modal-cancel-button" class="cancel-button">No (Cancel)</button>
                <button id="modal-continue-button">Continue with Custom Title</button>
            </div>
        </div>
    `;
    document.body.appendChild(modalOverlay);

    const modalChatTitleInput = document.getElementById('modal-chat-title');
    const modalYesButton = document.getElementById('modal-yes-button');
    const modalCancelButton = document.getElementById('modal-cancel-button');
    const modalContinueButton = document.getElementById('modal-continue-button');

    modalYesButton.onclick = () => {
        chatTitleInput.value = '';
        document.body.removeChild(modalOverlay);
        sendMessage();
    };

    modalCancelButton.onclick = () => {
        document.body.removeChild(modalOverlay);
    };

    modalContinueButton.onclick = () => {
        const customTitle = modalChatTitleInput.value.trim();
        if (customTitle) {
            chatTitleInput.value = customTitle;
            document.body.removeChild(modalOverlay);
            sendMessage();
        } else {
            alert('Please enter a custom title or click "Yes" for a default title.');
        }
    };
}

// Send message to backend
async function sendMessage() {
    try {

        const prompt = promptElement.value.trim();
        
        if (!prompt) {
            displayFormattedResponse('Please enter a prompt.');
            return;
        }
        
        const model = modelSelect.value;
        const chatTitle = chatTitleInput.value.trim();

        //displayFormattedResponse('Generating response...'); 
        showLoadingResponse('Generating response...');
        sendButton.disabled = true;

        let apiUrl = '';
        let requestBody = {};

        // Include file data if present
        const fileData = window.currentFileData;
        
        if (!currentConversationId) {
            apiUrl = '/chat/start';
            requestBody = { 
                prompt, 
                model, 
                title: chatTitle || null,
                file: fileData ? {
                    base64: fileData.base64,
                    type: fileData.type,
                    name: fileData.name
                } : null
            };
        } else {
            apiUrl = `/chat/continue/${currentConversationId}`;
            requestBody = { 
                prompt,
                file: fileData ? {
                    base64: fileData.base64,
                    type: fileData.type,
                    name: fileData.name
                } : null
            };
        }

        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(requestBody)
        });

        if (!response.ok) {
            const errorText = await response.text();
            console.error('Server error response:', errorText);
            displayFormattedResponse('Server error: ' + errorText);
            sendButton.disabled = false;
            return;
        }

        const data = await response.json();
        console.log('📨 Raw response from server:', data.response);
        displayFormattedResponse(data.response || data.error || 'No response.');

        if (data.conversationId && !currentConversationId) {
            currentConversationId = data.conversationId;
            updateSendButtonText();
            if (!chatTitle && data.conversationTitle) {
                chatTitleInput.value = data.conversationTitle;
            }
        }

        // Clear file data after sending
        window.currentFileData = null;
        fileUploadInput.value = '';

    } catch (e) {
        //console.error('Complete sendMessage Error:', e);
        displayFormattedResponse('An unexpected error occurred: ' + e.message);
    } finally {
        sendButton.disabled = false;
    }
}

// Reset conversation
function resetConversation() {
    currentConversationId = null;
    promptElement.value = '';
    responseDiv.textContent = '';
    chatTitleInput.value = '';
    window.currentFileData = null;
    fileUploadInput.value = '';
    updateSendButtonText();
    loadModels();
}

// Update send button text
function updateSendButtonText() {
    if (currentConversationId) {
        sendButton.textContent = 'Continue conversation';
    } else {
        sendButton.textContent = 'Send';
    }
}

// Trigger file upload
function triggerFileUpload() {
    fileUploadInput.click();
}

// Convert file to base64
function convertFileToBase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = () => {
            // Remove data:xxx;base64, prefix
            const base64 = reader.result.split(',')[1];
            
            resolve(base64);
        };
        reader.onerror = error => reject(error);
    });
}

// Handle file selection
fileUploadInput.addEventListener('change', async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    // File type validation
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',  'application/vnd.ms-excel'];
    if (!allowedTypes.includes(file.type)) {
        alert('Please upload a valid image (JPEG, PNG, GIF), PDF, or Excel file.');
        return;
    }

    // File size validation
    const maxSize = 10 * 1024 * 1024; // 10MB
    if (file.size > maxSize) {
        alert('File is too large. Maximum file size is 10MB.');
        return;
    }

    try {
        // Convert file to base64
        const base64File = await convertFileToBase64(file);
        
        // Append file to prompt
        appendFileToPrompt(base64File, file.type, file.name);
    } catch (error) {
        console.error('File processing error:', error);
        alert('Error processing file. Please try again.');
    }
});

// Append file to prompt
function appendFileToPrompt(base64File, fileType, fileName) {
    const currentPrompt = promptElement.value;
    
    let fileDescription = `[Uploaded ${fileType === 'application/pdf' ? 'PDF' : 'Image'}: ${fileName}]\n`;
    
    promptElement.value = fileDescription + currentPrompt;

    // Store file data for sending with message
    window.currentFileData = {
        base64: base64File,
        type: fileType,
        name: fileName
    };
}

// Add this debug function to the global scope so you can call it from browser console
window.debugModelLoading = debugModelLoading;