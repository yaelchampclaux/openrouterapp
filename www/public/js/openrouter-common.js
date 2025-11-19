//openrouter-common.js
// Switch between tabs
function switchTab(tabId) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Remove active class from all tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Activate the selected tab
    document.getElementById(tabId).classList.add('active');
    
    // Add active class to the clicked button
    const buttonIndex = tabId === 'chat-tab' ? 0 : 1;
    document.querySelectorAll('.tab-btn')[buttonIndex].classList.add('active');
    
    // Load models if needed
    if (tabId === 'chat-tab') {
        if (document.getElementById('model').options.length <= 1) {
            loadModels();
        }
    } 
}