let urlManagerConfig = null;
let urlMap = null;

function initUrlManager() {
    urlManagerConfig = window.PROJECT_CONFIG;
    urlMap = {
        home: '',
        'explore-municipalities': 'explore/municipalities',
        'explore-universities': 'explore/universities',
        // --- INICIO DE LA MODIFICACIÓN ---
        'chat-messages': 'chat',
        'chat-members': 'chat/members',
        // --- FIN DE LA MODIFICACIÓN ---
        'settings-profile': 'settings/your-account',
        'settings-login': 'settings/login',
        'settings-accessibility': 'settings/accessibility',
        'help-privacy': 'help/privacy',
        'help-terms': 'help/terms',
        'help-cookies': 'help/cookies',
        'help-suggestions': 'help/suggestions'
    };
}

function generateUrl(section, subsection = null) {
    if (!urlManagerConfig) return '#';
    
    // --- INICIO DE LA MODIFICACIÓN ---
    // Lógica para manejar URLs dinámicas como /chat/:id
    if (section === 'chat' && typeof subsection === 'object' && subsection.uuid) {
        const key = `chat-${subsection.type || 'messages'}`;
        const path = urlMap[key];
        return `${urlManagerConfig.baseUrl}/${path}/${subsection.uuid}`;
    }
    // --- FIN DE LA MODIFICACIÓN ---

    let key = section;
    if (subsection) {
        key += `-${subsection}`;
    }

    const path = urlMap[key] !== undefined ? urlMap[key] : (urlMap[section] || '');
    
    return path ? `${urlManagerConfig.baseUrl}/${path}` : urlManagerConfig.baseUrl;
}

function navigateToUrl(section, subsection = null, updateHistory = true) {
    if (!urlManagerConfig) return;
    
    const url = generateUrl(section, subsection);
    
    if (updateHistory && window.location.href !== url) {
        history.pushState({
            section: section,
            subsection: subsection
        }, '', url);
    }

    updatePageTitle(section, subsection);
}

function updatePageTitle(section, subsection = null) {
    const titles = {
        home: 'Página Principal - ProjectLeviathan',
        explore: 'Explorar Comunidades - ProjectLeviathan',
        // --- INICIO DE LA MODIFICACIÓN ---
        chat: subsection && subsection.title ? `${subsection.title} - Chat` : 'Chat - ProjectLeviathan',
        // --- FIN DE LA MODIFICACIÓN ---
        settings: 'Configuración - ProjectLeviathan',
        help: 'Ayuda y Recursos - ProjectLeviathan'
    };

    const title = titles[section] || 'ProjectLeviathan';
    document.title = title;
}

function getCurrentUrlState() {
    if (!urlManagerConfig) return null;
    
    const section = urlManagerConfig.currentSection;
    const subsection = urlManagerConfig.currentSubsection;
    // --- INICIO DE LA MODIFICACIÓN ---
    const id = urlManagerConfig.currentId;
    // --- FIN DE LA MODIFICACIÓN ---
    
    return {
        section: section,
        subsection: subsection,
        // --- INICIO DE LA MODIFICACIÓN ---
        id: id,
        isChatSection: section === 'chat',
        // --- FIN DE LA MODIFICACIÓN ---
        isSettingsSection: section === 'settings',
        isHelpSection: section === 'help',
        isExploreSection: section === 'explore'
    };
}

function setupPopStateHandler(callback) {
    window.addEventListener('popstate', (event) => {
        if (event.state) {
            const { section, subsection } = event.state;
            callback(section, subsection, false);
        } else {
            const initialState = getCurrentUrlState();
            // --- INICIO DE LA MODIFICACIÓN ---
            if (initialState.isChatSection && initialState.id) {
                 const subsectionObject = {
                    uuid: initialState.id,
                    type: initialState.subsection,
                    title: 'Cargando...'
                };
                callback(initialState.section, subsectionObject, false);
            } else {
                callback(initialState.section, initialState.subsection, false);
            }
            // --- FIN DE LA MODIFICACIÓN ---
        }
    });
}

function setInitialHistoryState() {
    if (!urlManagerConfig) return;
    
    const currentState = getCurrentUrlState();
    
    if (!history.state && currentState) {
        // --- INICIO DE LA MODIFICACIÓN ---
        let subsectionForState = currentState.subsection;
        if (currentState.isChatSection && currentState.id) {
            subsectionForState = {
                uuid: currentState.id,
                type: currentState.subsection,
                title: 'Cargando...'
            };
        }
        // --- FIN DE LA MODIFICACIÓN ---

        history.replaceState({
            section: currentState.section,
            subsection: subsectionForState
        }, '', window.location.href);
    }
}

export {
    initUrlManager,
    generateUrl,
    navigateToUrl,
    updatePageTitle,
    getCurrentUrlState,
    setupPopStateHandler,
    setInitialHistoryState
};