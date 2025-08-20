import { initDragController } from './drag-controller.js';
import { initUrlManager, navigateToUrl, getCurrentUrlState, setupPopStateHandler, setInitialHistoryState, updatePageTitle } from './url-manager.js';

function initMainController() {
    const closeOnClickOutside = true;
    const closeOnEscape = true;
    const allowMultipleActiveModules = false;
    let isAnimating = false;

    let websocket = null;
    let currentChatGroupUUID = null;
    let allChatMembers = [];
    let messageOptionsPopper = null;
    let currentReplyMessageId = null;

    const popperInstances = {};

    initUrlManager();
    const initialState = getCurrentUrlState();

    let isSectionHomeActive = initialState ? initialState.section === 'home' : true;
    let isSectionExploreActive = initialState ? initialState.section === 'explore' : false;
    let isSectionChatActive = initialState ? initialState.section === 'chat' : false;
    let activeChatGroup = null;
    let isSectionSettingsActive = initialState ? initialState.section === 'settings' : false;
    let isSectionHelpActive = initialState ? initialState.section === 'help' : false;

    let isSectionMunicipalitiesActive = initialState ? initialState.subsection === 'municipalities' : false;
    let isSectionUniversitiesActive = initialState ? initialState.subsection === 'universities' : false;
    let isSectionProfileActive = initialState ? initialState.subsection === 'profile' : false;
    let isSectionLoginActive = initialState ? initialState.subsection === 'login' : false;
    let isSectionAccessibilityActive = initialState ? initialState.subsection === 'accessibility' : false;
    let isSectionPrivacyActive = initialState ? initialState.subsection === 'privacy' : false;
    let isSectionTermsActive = initialState ? initialState.subsection === 'terms' : false;
    let isSectionCookiesActive = initialState ? initialState.subsection === 'cookies' : false;
    let isSectionSuggestionsActive = initialState ? initialState.subsection === 'suggestions' : false;

    const toggleOptionsButton = document.querySelector('[data-action="toggleModuleOptions"]');
    const moduleOptions = document.querySelector('[data-module="moduleOptions"]');
    const toggleSurfaceButton = document.querySelector('[data-action="toggleModuleSurface"]');
    const moduleSurface = document.querySelector('[data-module="moduleSurface"]');
    const surfaceMain = document.querySelector('[data-surface-type="main"]');
    const surfaceSettings = document.querySelector('[data-surface-type="settings"]');
    const surfaceHelp = document.querySelector('[data-surface-type="help"]');
    const surfaceChat = document.querySelector('[data-surface-type="chat"]');
    const customSelectorButtons = document.querySelectorAll('[data-action="toggleSelector"]');
    const logoutButton = document.querySelector('[data-action="logout"]');

    const sectionHome = document.querySelector('[data-section="sectionHome"]');
    const sectionExplore = document.querySelector('[data-section="sectionExplore"]');
    const sectionChat = document.querySelector('[data-section="sectionChat"]');
    const sectionSettings = document.querySelector('[data-section="sectionSettings"]');
    const sectionHelp = document.querySelector('[data-section="sectionHelp"]');
    const sectionProfile = document.querySelector('[data-section="sectionProfile"]');
    const sectionLogin = document.querySelector('[data-section="sectionLogin"]');
    const sectionAccessibility = document.querySelector('[data-section="sectionAccessibility"]');
    const sectionPrivacy = document.querySelector('[data-section="sectionPrivacy"]');
    const sectionTerms = document.querySelector('[data-section="sectionTerms"]');
    const sectionCookies = document.querySelector('[data-section="sectionCookies"]');
    const sectionSuggestions = document.querySelector('[data-section="sectionSuggestions"]');

    const toggleSectionHomeButtons = document.querySelectorAll('[data-action="toggleSectionHome"]');
    const toggleSectionExploreButtons = document.querySelectorAll('[data-action="toggleSectionExplore"]');
    const toggleSectionSettingsButton = document.querySelector('[data-action="toggleSectionSettings"]');
    const toggleSectionHelpButton = document.querySelector('[data-action="toggleSectionHelp"]');
    const toggleSectionHomeFromSettingsButton = document.querySelector('[data-action="toggleSectionHomeFromSettings"]');
    const toggleSectionProfileButton = document.querySelector('[data-action="toggleSectionProfile"]');
    const toggleSectionLoginButton = document.querySelector('[data-action="toggleSectionLogin"]');
    const toggleSectionAccessibilityButton = document.querySelector('[data-action="toggleSectionAccessibility"]');
    const toggleSectionHomeFromHelpButton = document.querySelector('[data-action="toggleSectionHomeFromHelp"]');
    const toggleSectionPrivacyButton = document.querySelector('[data-action="toggleSectionPrivacy"]');
    const toggleSectionTermsButton = document.querySelector('[data-action="toggleSectionTerms"]');
    const toggleSectionCookiesButton = document.querySelector('[data-action="toggleSectionCookies"]');
    const toggleSectionSuggestionsButton = document.querySelector('[data-action="toggleSectionSuggestions"]');

    const accountActionModal = document.querySelector('[data-module="accountActionModal"]');
    const updatePasswordDialog = accountActionModal?.querySelector('[data-dialog="updatePassword"]');
    const openUpdatePasswordModalButton = document.querySelector('[data-action="openUpdatePasswordModal"]');
    const closeAccountActionModalButtons = document.querySelectorAll('[data-action="closeAccountActionModal"]');
    const paneConfirmPassword = updatePasswordDialog?.querySelector('[data-pane="confirmPassword"]');
    const paneSetNewPassword = updatePasswordDialog?.querySelector('[data-pane="setNewPassword"]');
    const currentPasswordInput = document.getElementById('current-password');
    const newPasswordInput = document.getElementById('new-password');
    const confirmPasswordInput = document.getElementById('confirm-password');
    const confirmCurrentPasswordButton = document.querySelector('[data-action="confirmCurrentPassword"]');
    const saveNewPasswordButton = document.querySelector('[data-action="saveNewPassword"]');
    const confirmErrorContainer = paneConfirmPassword?.querySelector('.dialog-error-message');
    const newErrorContainer = paneSetNewPassword?.querySelector('.dialog-error-message');
    const deleteAccountDialog = accountActionModal?.querySelector('[data-dialog="deleteAccount"]');
    const openDeleteAccountModalButton = document.querySelector('[data-action="openDeleteAccountModal"]');
    const confirmDeleteAccountButton = document.querySelector('[data-action="confirmDeleteAccount"]');
    const deletePasswordInput = document.getElementById('delete-confirm-password');
    const deleteErrorContainer = deleteAccountDialog?.querySelector('.dialog-error-message');

    const exploreTabs = sectionExplore.querySelector('.discovery-tabs');
    const searchInput = document.getElementById('community-search-input');
    const municipalitiesGrid = sectionExplore.querySelector('.discovery-content-section[data-section-id="municipalities"] .discovery-grid');
    const universitiesGrid = sectionExplore.querySelector('.discovery-content-section[data-section-id="universities"] .discovery-grid');
    const loadMoreMunicipalitiesButton = document.querySelector('.load-more-button[data-type="municipalities"]');
    const loadMoreUniversitiesButton = document.querySelector('.load-more-button[data-type="universities"]');

    const ITEMS_PER_PAGE = 12;
    let allMunicipalities = [];
    let allUniversities = [];
    let displayedMunicipalitiesCount = 0;
    let displayedUniversitiesCount = 0;
    let currentUniversityFilter = 'all';


    if (!toggleOptionsButton || !moduleOptions || !toggleSurfaceButton || !moduleSurface || !sectionHome || !sectionExplore || !sectionSettings || !sectionHelp) return;

    const menuContentOptions = moduleOptions.querySelector('.menu-content');

    setInitialHistoryState();
    setupPopStateHandler((section, subsection, updateHistory) => {
        handleNavigationChange(section, subsection, updateHistory);
    });

    const updateLogState = () => {
        const toState = (active) => active ? '✅ Activo' : '❌ Inactivo';
        const tableData = {
            '── Sections ──': { section: 'Home', status: toState(isSectionHomeActive) },
            '   ': { section: 'Explore', status: toState(isSectionExploreActive) },
            '    ': { section: 'Chat', status: toState(isSectionChatActive) },
            '     ': { section: 'Settings', status: toState(isSectionSettingsActive) },
            '      ': { section: 'Help', status: toState(isSectionHelpActive) },
            '── Sub-sections (Explore) ──': { section: 'Municipalities', status: toState(isSectionMunicipalitiesActive) },
            '       ': { section: 'Universities', status: toState(isSectionUniversitiesActive) },
            '── Sub-sections (Settings) ──': { section: 'Profile', status: toState(isSectionProfileActive) },
            '        ': { section: 'Login', status: toState(isSectionLoginActive) },
            '         ': { section: 'Accessibility', status: toState(isSectionAccessibilityActive) },
            '── Sub-sections (Help) ──': { section: 'Privacy Policy', status: toState(isSectionPrivacyActive) },
            '           ': { section: 'Terms & Conditions', status: toState(isSectionTermsActive) },
            '            ': { section: 'Cookies Policy', status: toState(isSectionCookiesActive) },
            '             ': { section: 'Suggestions', status: toState(isSectionSuggestionsActive) },
        };
        console.group("ProjectLeviathan - State Overview");
        console.table(tableData);
        console.groupEnd();
    };

    const resetPasswordModal = () => {
        if (currentPasswordInput) currentPasswordInput.value = '';
        if (newPasswordInput) newPasswordInput.value = '';
        if (confirmPasswordInput) confirmPasswordInput.value = '';
        if (confirmErrorContainer) confirmErrorContainer.style.display = 'none';
        if (newErrorContainer) newErrorContainer.style.display = 'none';
        paneConfirmPassword?.classList.remove('disabled');
        paneConfirmPassword?.classList.add('active');
        paneSetNewPassword?.classList.add('disabled');
        paneSetNewPassword?.classList.remove('active');
    };

    const resetDeleteAccountModal = () => {
        if (deletePasswordInput) deletePasswordInput.value = '';
        if (deleteErrorContainer) deleteErrorContainer.style.display = 'none';
    };

    const openAccountActionModal = (dialogType) => {
        if (!accountActionModal) return;
        let targetDialog;

        const reportMessageDialog = accountActionModal?.querySelector('[data-dialog="reportMessage"]');

        if (dialogType === 'updatePassword') {
            resetPasswordModal();
            targetDialog = updatePasswordDialog;
        } else if (dialogType === 'deleteAccount') {
            resetDeleteAccountModal();
            targetDialog = deleteAccountDialog;
        } else if (dialogType === 'reportMessage') {
            targetDialog = reportMessageDialog;
        } else {
            return;
        }

        accountActionModal.classList.remove('disabled');
        accountActionModal.classList.add('active');
        targetDialog?.classList.remove('disabled');
        targetDialog?.classList.add('active');
    };

    const closeAccountActionModal = () => {
        if (!accountActionModal) return false;

        accountActionModal.classList.add('disabled');
        accountActionModal.classList.remove('active');

        accountActionModal.querySelectorAll('.dialog-pane').forEach(pane => {
            pane.classList.add('disabled');
            pane.classList.remove('active');
        });

        return true;
    };


    const showNewPasswordPane = async () => {
        const formData = new FormData();
        formData.append('action', 'update_password');
        formData.append('current_password', currentPasswordInput.value);
        formData.append('csrf_token', window.PROJECT_CONFIG.csrfToken);

        const result = await sendPasswordRequest(formData, confirmErrorContainer);

        if (result.success) {
            confirmErrorContainer.style.display = 'none';
            paneConfirmPassword.classList.remove('active');
            paneConfirmPassword.classList.add('disabled');
            paneSetNewPassword.classList.remove('disabled');
            paneSetNewPassword.classList.add('active');
        } else {
            confirmErrorContainer.textContent = result.message;
            confirmErrorContainer.style.display = 'block';
        }
    };

    const saveNewPassword = async () => {
        const formData = new FormData();
        formData.append('action', 'update_password');
        formData.append('current_password', currentPasswordInput.value);
        formData.append('new_password', newPasswordInput.value);
        formData.append('confirm_password', confirmPasswordInput.value);
        formData.append('csrf_token', window.PROJECT_CONFIG.csrfToken);

        const result = await sendPasswordRequest(formData, newErrorContainer);

        if (result.success) {
            alert(result.message);
            closeAccountActionModal();
        } else {
            newErrorContainer.textContent = result.message;
            newErrorContainer.style.display = 'block';
        }
    };

    const sendPasswordRequest = async (formData, errorContainer) => {
        errorContainer.style.display = 'none';
        try {
            const response = await fetch(window.PROJECT_CONFIG.apiUrl, {
                method: 'POST',
                body: formData
            });
            return await response.json();
        } catch (error) {
            errorContainer.textContent = 'Error de conexión. Inténtalo de nuevo.';
            errorContainer.style.display = 'block';
            return { success: false, message: 'Error de conexión.' };
        }
    };

    const handleDeleteAccount = async () => {
        if (!deletePasswordInput || !deleteErrorContainer) return;

        const formData = new FormData();
        formData.append('action', 'delete_account');
        formData.append('password', deletePasswordInput.value);
        formData.append('csrf_token', window.PROJECT_CONFIG.csrfToken);

        try {
            const response = await fetch(window.PROJECT_CONFIG.apiUrl, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                alert('Tu cuenta ha sido eliminada.');
                window.location.href = result.redirect_url;
            } else {
                deleteErrorContainer.textContent = result.message || 'Ocurrió un error.';
                deleteErrorContainer.style.display = 'block';
            }
        } catch (error) {
            deleteErrorContainer.textContent = 'Error de conexión. Inténtalo de nuevo.';
            deleteErrorContainer.style.display = 'block';
        }
    };

    const setMenuOptionsClosed = () => {
        moduleOptions.classList.add('disabled');
        moduleOptions.classList.remove('active', 'fade-out');
        menuContentOptions.classList.add('disabled');
        menuContentOptions.classList.remove('active');
    };

    const setMenuOptionsOpen = () => {
        moduleOptions.classList.remove('disabled');
        moduleOptions.classList.add('active');
        menuContentOptions.classList.remove('disabled');
    };

    const closeMenuOptions = () => {
        if (isAnimating || !moduleOptions.classList.contains('active')) return false;

        if (window.innerWidth <= 468 && menuContentOptions) {
            isAnimating = true;
            menuContentOptions.removeAttribute('style');
            moduleOptions.classList.remove('fade-in');
            moduleOptions.classList.add('fade-out');
            menuContentOptions.classList.remove('active');

            moduleOptions.addEventListener('animationend', (e) => {
                if (e.animationName === 'fadeOut') {
                    setMenuOptionsClosed();
                    isAnimating = false;
                }
            }, { once: true });
        } else {
            setMenuOptionsClosed();
        }
        return true;
    };

    const openMenuOptions = () => {
        if (isAnimating || moduleOptions.classList.contains('active')) return false;

        if (!allowMultipleActiveModules) {
            closeAllModules();
        }

        setMenuOptionsOpen();

        if (window.innerWidth <= 468 && menuContentOptions) {
            isAnimating = true;
            moduleOptions.classList.remove('fade-out');
            moduleOptions.classList.add('fade-in');

            requestAnimationFrame(() => {
                menuContentOptions.classList.add('active');
            });

            moduleOptions.addEventListener('animationend', (e) => {
                if (e.animationName === 'fadeIn') {
                    moduleOptions.classList.remove('fade-in');
                    isAnimating = false;
                }
            }, { once: true });
        } else {
            menuContentOptions.classList.add('active');
        }
        return true;
    };

    const setMenuSurfaceClosed = () => {
        moduleSurface.classList.add('disabled');
        moduleSurface.classList.remove('active');
    };

    const setMenuSurfaceOpen = () => {
        if (!allowMultipleActiveModules) {
            closeAllModules();
        }
        moduleSurface.classList.remove('disabled');
        moduleSurface.classList.add('active');

        const surfaces = {
            main: surfaceMain,
            settings: surfaceSettings,
            help: surfaceHelp,
            chat: surfaceChat
        };

        let activeSurfaceType = 'main';
        if (isSectionSettingsActive) activeSurfaceType = 'settings';
        else if (isSectionHelpActive) activeSurfaceType = 'help';
        else if (isSectionChatActive) activeSurfaceType = 'chat';

        for (const type in surfaces) {
            if (surfaces[type]) {
                surfaces[type].classList.toggle('active', type === activeSurfaceType);
                surfaces[type].classList.toggle('disabled', type !== activeSurfaceType);
            }
        }
    };

    const closeMenuSurface = () => {
        if (!moduleSurface.classList.contains('active')) return false;
        setMenuSurfaceClosed();
        return true;
    };

    const openMenuSurface = () => {
        if (moduleSurface.classList.contains('active')) return false;
        setMenuSurfaceOpen();
        return true;
    };

    const closeMessageOptions = () => {
        if (messageOptionsPopper) {
            const dropdown = document.getElementById('message-options-dropdown');
            if (dropdown) {
                dropdown.remove();
            }
            messageOptionsPopper.destroy();
            messageOptionsPopper = null;
            document.querySelector('.message-bubble.active')?.classList.remove('active');
            return true;
        }
        return false;
    };

    const openMessageOptions = (messageElement) => {
        if (messageOptionsPopper) {
            closeMessageOptions();
        }
        
        if (messageElement.classList.contains('deleted-message')) {
            return;
        }

        messageElement.classList.add('active');

        const template = document.getElementById('message-options-template');
        const dropdown = template.cloneNode(true);
        dropdown.id = 'message-options-dropdown';
        dropdown.style.display = 'block';

        const authorId = parseInt(messageElement.dataset.authorId, 10);
        const isCurrentUser = authorId === window.PROJECT_CONFIG.userId;

        const reportButton = dropdown.querySelector('[data-action="report-message"]');
        if (reportButton) {
            reportButton.style.display = isCurrentUser ? 'none' : 'flex';
        }

        // --- INICIO DE LA MODIFICACIÓN ---
        const deleteButton = dropdown.querySelector('[data-action="delete-message"]');
        if (deleteButton) {
            const messageTimestamp = messageElement.dataset.timestamp;
            const sentDate = new Date(messageTimestamp);
            const now = new Date();
            const canDelete = isCurrentUser && (now - sentDate) < 10 * 60 * 1000; // 10 minutos
            deleteButton.style.display = canDelete ? 'flex' : 'none';
        }
        // --- FIN DE LA MODIFICACIÓN ---

        document.body.appendChild(dropdown);

        messageOptionsPopper = Popper.createPopper(messageElement, dropdown, {
            placement: 'bottom-start',
            modifiers: [{ name: 'offset', options: { offset: [0, 8] } }],
        });
    };

    const closeAllSelectors = () => {
        let closed = false;
        document.querySelectorAll('[data-module="moduleSelector"].active').forEach(selector => {
            const button = document.querySelector(`[aria-controls="${selector.id}"]`);
            if (button) {
                button.classList.remove('active');
            }
            selector.classList.add('disabled');
            selector.classList.remove('active');

            const popperId = selector.id;
            if (popperInstances[popperId]) {
                popperInstances[popperId].destroy();
                delete popperInstances[popperId];
            }
            closed = true;
        });
        return closed;
    };

    const closeAllModules = () => {
        closeAllSelectors();
        closeMenuOptions();
        closeMenuSurface();
        closeAccountActionModal();
        closeMessageOptions();
    };

    const updateMainMenuButtons = (activeAction) => {
        const mainMenuLinks = surfaceMain.querySelectorAll('.menu-link');
        mainMenuLinks.forEach(link => {
            link.classList.toggle('active', link.dataset.action === activeAction);
        });
    };

    const updateSettingsMenuButtons = (activeAction) => {
        const settingsMenuLinks = surfaceSettings.querySelectorAll('.menu-link');
        settingsMenuLinks.forEach(link => {
            link.classList.toggle('active', link.dataset.action === activeAction);
        });
    };

    const updateHelpMenuButtons = (activeAction) => {
        const helpMenuLinks = surfaceHelp.querySelectorAll('.menu-link');
        helpMenuLinks.forEach(link => {
            link.classList.toggle('active', link.dataset.action === activeAction);
        });
    };

    const setSectionActive = (sectionToShow, sectionsToHide, activeStateSetter, updateUrl = true) => {
        sectionToShow.classList.remove('disabled');
        sectionToShow.classList.add('active');
        sectionsToHide.forEach(section => {
            section.classList.add('disabled');
            section.classList.remove('active');
        });

        isSectionHomeActive = activeStateSetter === 'home';
        isSectionExploreActive = activeStateSetter === 'explore';
        isSectionChatActive = activeStateSetter === 'chat';
        isSectionSettingsActive = activeStateSetter === 'settings';
        isSectionHelpActive = activeStateSetter === 'help';

        if (activeStateSetter !== 'settings') {
            isSectionProfileActive = false; isSectionLoginActive = false; isSectionAccessibilityActive = false;
        }
        if (activeStateSetter !== 'help') {
            isSectionPrivacyActive = false; isSectionTermsActive = false; isSectionCookiesActive = false; isSectionSuggestionsActive = false;
        }
        if (activeStateSetter !== 'explore') {
            isSectionMunicipalitiesActive = false; isSectionUniversitiesActive = false;
        }

        const surfaces = {
            main: surfaceMain,
            settings: surfaceSettings,
            help: surfaceHelp,
            chat: surfaceChat
        };

        let activeSurfaceType = 'main';
        if (isSectionSettingsActive) activeSurfaceType = 'settings';
        else if (isSectionHelpActive) activeSurfaceType = 'help';
        else if (isSectionChatActive) activeSurfaceType = 'chat';

        for (const type in surfaces) {
            if (surfaces[type]) {
                surfaces[type].classList.toggle('active', type === activeSurfaceType);
                surfaces[type].classList.toggle('disabled', type !== activeSurfaceType);
            }
        }

        if (updateUrl) {
            let subsection = null;
            if (isSectionExploreActive) {
                subsection = isSectionMunicipalitiesActive ? 'municipalities' : 'universities';
            } else if (isSectionSettingsActive) {
                subsection = isSectionProfileActive ? 'profile' : isSectionLoginActive ? 'login' : 'accessibility';
            } else if (isSectionHelpActive) {
                subsection = isSectionPrivacyActive ? 'privacy' : isSectionTermsActive ? 'terms' : isSectionCookiesActive ? 'cookies' : 'suggestions';
            }
            else if (isSectionChatActive) {
                subsection = activeChatGroup;
            }
            navigateToUrl(activeStateSetter, subsection);
        }
    };

    const setSubSectionActive = (sectionToShow, sectionsToHide, activeStateSetter, updateUrl = true) => {
        sectionToShow.classList.remove('disabled');
        sectionToShow.classList.add('active');
        sectionsToHide.forEach(section => {
            section.classList.add('disabled');
            section.classList.remove('active');
        });

        isSectionMunicipalitiesActive = activeStateSetter === 'municipalities';
        isSectionUniversitiesActive = activeStateSetter === 'universities';
        isSectionProfileActive = activeStateSetter === 'profile';
        isSectionLoginActive = activeStateSetter === 'login';
        isSectionAccessibilityActive = activeStateSetter === 'accessibility';
        isSectionPrivacyActive = activeStateSetter === 'privacy';
        isSectionTermsActive = activeStateSetter === 'terms';
        isSectionCookiesActive = activeStateSetter === 'suggestions';
        isSectionSuggestionsActive = activeStateSetter === 'suggestions';

        if (updateUrl) {
            const mainSection = isSectionExploreActive ? 'explore' : isSectionSettingsActive ? 'settings' : 'help';
            navigateToUrl(mainSection, activeStateSetter);
        }
    };

    const resetUIComponents = () => {
        closeAllModules();

        document.querySelectorAll('.profile-card-item .edit-state').forEach(editState => {
            if (!editState.classList.contains('hidden')) {
                editState.classList.add('hidden');
                const parent = editState.closest('.profile-card-item');
                if (parent) {
                    const viewState = parent.querySelector('.view-state');
                    if (viewState && viewState.classList.contains('hidden')) {
                        viewState.classList.remove('hidden');
                    }
                }
            }
        });
    };

    const loadAccountDates = async () => {
        const creationDateElem = document.getElementById('account-creation-date');
        const lastUpdateElem = document.getElementById('last-password-update');

        if (!creationDateElem || !lastUpdateElem) return;

        try {
            const response = await fetch(`${window.PROJECT_CONFIG.apiUrl}?action=get_account_dates`);
            const data = await response.json();

            if (data.success) {
                creationDateElem.textContent = data.creation_date;
                lastUpdateElem.textContent = data.last_password_update;
            } else {
                creationDateElem.textContent = 'No disponible';
                lastUpdateElem.textContent = 'No disponible';
            }
        } catch (error) {
            creationDateElem.textContent = 'Error al cargar';
            lastUpdateElem.textContent = 'Error al cargar';
        }
    };

    const formatMessageTime = (isoTimestamp) => {
        if (!isoTimestamp) return '';
        try {
            const date = new Date(isoTimestamp);
            const hours = date.getHours().toString().padStart(2, '0');
            const minutes = date.getMinutes().toString().padStart(2, '0');
            return `${hours}:${minutes}`;
        } catch (e) {
            console.error("Invalid timestamp format:", isoTimestamp);
            return '';
        }
    };

    const connectWebSocket = async (groupUuid) => {
        if (websocket && websocket.readyState === WebSocket.OPEN) {
            websocket.close();
        }

        try {
            const formData = new FormData();
            formData.append('action', 'get_websocket_token');
            formData.append('csrf_token', window.PROJECT_CONFIG.csrfToken);

            const response = await fetch(window.PROJECT_CONFIG.wsApiUrl, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (!data.success) {
                console.error('No se pudo obtener el token para el WebSocket:', data.message);
                alert('Error de autenticación al conectar con el chat.');
                return;
            }

            const token = data.token;
            currentChatGroupUUID = groupUuid;

            websocket = new WebSocket('ws://localhost:8765');

            websocket.onopen = () => {
                console.log('WebSocket conectado.');
                const authMessage = {
                    type: 'auth',
                    token: token,
                    group_uuid: groupUuid
                };
                websocket.send(JSON.stringify(authMessage));
            };

            websocket.onmessage = (event) => {
                const data = JSON.parse(event.data);
                if (data.type === 'new_message') {
                    appendMessage(data);
                }
                if (data.type === 'user_status_update') {
                    updateMembersList(data.online_users);
                }
                // --- INICIO DE LA MODIFICACIÓN ---
                if (data.type === 'message_deleted') {
                    const messageBubble = document.querySelector(`[data-message-id="${data.message_id}"]`);
                    if (messageBubble) {
                        messageBubble.classList.add('deleted-message');
                        const content = messageBubble.querySelector('.message-content');
                        if (content) {
                            content.innerHTML = `<p><em>Mensaje eliminado</em></p>`;
                        }
                    }
                }
                // --- FIN DE LA MODIFICACIÓN ---
            };

            websocket.onclose = () => {
                console.log('WebSocket desconectado.');
                websocket = null;
            };

            websocket.onerror = (error) => {
                console.error('Error en WebSocket:', error);
                websocket = null;
            };

        } catch (error) {
            console.error('Error al intentar conectar al WebSocket:', error);
            alert('Error de conexión con el chat.');
        }
    };

    const appendMessage = (data) => {
        const messagesContainer = document.getElementById('chat-messages-container');
        if (!messagesContainer) return;
    
        const isSentByCurrentUser = data.user_id === window.PROJECT_CONFIG.userId;
        const messageClass = isSentByCurrentUser ? 'sent' : 'received';
        const username = isSentByCurrentUser ? 'Tú' : data.username;
        const time = formatMessageTime(data.timestamp);
    
        const messageBubble = document.createElement('div');
        messageBubble.className = `message-bubble ${messageClass}`;
        messageBubble.dataset.messageId = data.message_id;
        messageBubble.dataset.authorId = data.user_id;
        messageBubble.dataset.timestamp = data.timestamp; // Guardamos el timestamp
    
        let replyHTML = '';
        if (data.reply_context && !data.is_deleted) {
            const replyAuthor = data.reply_context.username === window.PROJECT_CONFIG.username ? 'Tú' : data.reply_context.username;
            replyHTML = `
                <div class="reply-context">
                    <strong>${replyAuthor}</strong>
                    <p>${data.reply_context.message_text}</p>
                </div>
            `;
        }
    
        // --- INICIO DE LA MODIFICACIÓN ---
        if (data.is_deleted) {
            messageBubble.classList.add('deleted-message');
            messageBubble.innerHTML = `
                <div class="message-content">
                    <p><em>${data.message}</em></p>
                </div>`;
        } else {
            messageBubble.innerHTML = `
                ${replyHTML}
                <span class="message-info">${username}</span>
                <div class="message-content">
                    <p>${data.message}</p>
                    <span class="message-time">${time}</span>
                </div>`;
        }
        // --- FIN DE LA MODIFICACIÓN ---
    
        messagesContainer.appendChild(messageBubble);
        messagesContainer.parentElement.scrollTop = messagesContainer.parentElement.scrollHeight;
    };

    const loadChat = async (groupInfo) => {
        const sidebarTitle = document.getElementById('sidebar-group-title');
        const messagesContainer = document.getElementById('chat-messages-container');
        const membersListContainer = document.getElementById('chat-members-list');

        if (!groupInfo || !groupInfo.uuid) {
            console.error("No se proporcionó información del grupo para cargar el chat.");
            handleNavigationChange('home');
            return;
        }

        sidebarTitle.textContent = 'Cargando...';
        messagesContainer.innerHTML = '<div class="loader"></div>';
        membersListContainer.innerHTML = '<div class="loader"></div>';

        try {
            const [detailsResponse, membersResponse] = await Promise.all([
                fetch(`${window.PROJECT_CONFIG.apiUrl}?action=get_group_details&group_uuid=${groupInfo.uuid}`),
                fetch(`${window.PROJECT_CONFIG.apiUrl}?action=get_group_members&group_uuid=${groupInfo.uuid}`)
            ]);

            const detailsData = await detailsResponse.json();
            const membersData = await membersResponse.json();

            if (detailsData.success && membersData.success) {
                const realTitle = detailsData.group.group_title;
                sidebarTitle.textContent = realTitle;
                activeChatGroup = { uuid: groupInfo.uuid, title: realTitle };
                updatePageTitle('chat', activeChatGroup);

                allChatMembers = membersData.members;
                updateMembersList([]);

                const messagesResponse = await fetch(`${window.PROJECT_CONFIG.apiUrl}?action=get_chat_messages&group_uuid=${groupInfo.uuid}`);
                const messagesData = await messagesResponse.json();

                messagesContainer.innerHTML = '';
                if (messagesData.success && messagesData.messages.length > 0) {
                    messagesData.messages.forEach(appendMessage);
                } else {
                    messagesContainer.innerHTML = `<p class="empty-grid-message">Sé el primero en enviar un mensaje.</p>`;
                }

                connectWebSocket(groupInfo.uuid);
            } else {
                const errorMessage = detailsData.message || membersData.message || 'Ocurrió un error inesperado.';
                alert(errorMessage);
                handleNavigationChange('home');
            }
        } catch (error) {
            console.error("Error al cargar los detalles del chat:", error);
            alert("Error de conexión al intentar cargar el chat.");
            handleNavigationChange('home');
        }
    };

    const updateMembersList = (onlineUserIds) => {
        const membersListContainer = document.getElementById('chat-members-list');
        const sidebarCount = document.getElementById('sidebar-online-count');
        if (!membersListContainer || !sidebarCount) return;

        sidebarCount.textContent = `${onlineUserIds.length} en línea`;
        membersListContainer.innerHTML = '';

        const membersByRole = allChatMembers.reduce((acc, member) => {
            const role = member.role || 'user';
            if (!acc[role]) {
                acc[role] = [];
            }
            acc[role].push(member);
            return acc;
        }, {});

        const roleOrder = ['owner', 'admin', 'community-manager', 'moderator', 'elite', 'premium', 'vip', 'user'];

        roleOrder.forEach(role => {
            if (membersByRole[role] && membersByRole[role].length > 0) {
                const roleHeader = document.createElement('div');
                roleHeader.className = 'member-role-header';
                const roleName = role.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                roleHeader.textContent = `${roleName}s`;
                membersListContainer.appendChild(roleHeader);

                membersByRole[role].forEach(member => {
                    const isOnline = onlineUserIds.includes(member.id);
                    const statusClass = isOnline ? 'online' : 'offline';

                    const memberElement = document.createElement('div');
                    memberElement.className = 'member-item';
                    memberElement.innerHTML = `
                        <div class="member-status ${statusClass}"></div>
                        <span class="member-name">${member.username}</span>
                    `;
                    membersListContainer.appendChild(memberElement);
                });
            }
        });
    };

    const handleNavigationChange = (section, subsection = null, updateUrl = true) => {
        const wasExploreActive = isSectionExploreActive;
        resetUIComponents();

        if (section !== 'chat' && websocket) {
            websocket.close();
            currentChatGroupUUID = null;
        }

        if (section === 'home') {
            setSectionActive(sectionHome, [sectionExplore, sectionChat, sectionSettings, sectionHelp], 'home', updateUrl);
            updateMainMenuButtons('toggleSectionHome');
            loadHomeContent();
        } else if (section === 'explore') {
            setSectionActive(sectionExplore, [sectionHome, sectionChat, sectionSettings, sectionHelp], 'explore', false);
            updateMainMenuButtons('toggleSectionExplore');
            const sub = subsection || 'municipalities';
            const municipalitiesSection = document.querySelector('[data-section-id="municipalities"]');
            const universitiesSection = document.querySelector('[data-section-id="universities"]');
            if (sub === 'municipalities') {
                setSubSectionActive(municipalitiesSection, [universitiesSection], 'municipalities', updateUrl);
                exploreTabs.querySelector('.tab-item[data-tab="municipalities"]').classList.add('active');
                exploreTabs.querySelector('.tab-item[data-tab="universities"]').classList.remove('active');
            } else if (sub === 'universities') {
                setSubSectionActive(universitiesSection, [municipalitiesSection], 'universities', updateUrl);
                exploreTabs.querySelector('.tab-item[data-tab="universities"]').classList.add('active');
                exploreTabs.querySelector('.tab-item[data-tab="municipalities"]').classList.remove('active');
            }
            if (allMunicipalities.length === 0) loadMunicipalityGroups();
            if (allUniversities.length === 0) loadUniversityGroups(currentUniversityFilter);
            populateMunicipalityFilter();
        }
        else if (section === 'chat') {
            activeChatGroup = subsection;
            setSectionActive(sectionChat, [sectionHome, sectionExplore, sectionSettings, sectionHelp], 'chat', updateUrl);
            loadChat(subsection);
        }
        else if (section === 'settings') {
            setSectionActive(sectionSettings, [sectionHome, sectionExplore, sectionChat, sectionHelp], 'settings', false);
            const sub = subsection || 'profile';
            if (sub === 'profile') {
                setSubSectionActive(sectionProfile, [sectionLogin, sectionAccessibility], 'profile', updateUrl);
                updateSettingsMenuButtons('toggleSectionProfile');
            } else if (sub === 'login') {
                setSubSectionActive(sectionLogin, [sectionProfile, sectionAccessibility], 'login', updateUrl);
                updateSettingsMenuButtons('toggleSectionLogin');
                loadAccountDates();
            } else if (sub === 'accessibility') {
                setSubSectionActive(sectionAccessibility, [sectionProfile, sectionLogin], 'accessibility', updateUrl);
                updateSettingsMenuButtons('toggleSectionAccessibility');
            }
        } else if (section === 'help') {
            setSectionActive(sectionHelp, [sectionHome, sectionExplore, sectionChat, sectionHelp], 'help', false);
            const sub = subsection || 'privacy';
            if (sub === 'privacy') {
                setSubSectionActive(sectionPrivacy, [sectionTerms, sectionCookies, sectionSuggestions], 'privacy', updateUrl);
                updateHelpMenuButtons('toggleSectionPrivacy');
            } else if (sub === 'terms') {
                setSubSectionActive(sectionTerms, [sectionPrivacy, sectionCookies, sectionSuggestions], 'terms', updateUrl);
                updateHelpMenuButtons('toggleSectionTerms');
            } else if (sub === 'cookies') {
                setSubSectionActive(sectionCookies, [sectionPrivacy, sectionTerms, sectionSuggestions], 'cookies', updateUrl);
                updateHelpMenuButtons('toggleSectionCookies');
            } else if (sub === 'suggestions') {
                setSubSectionActive(sectionSuggestions, [sectionPrivacy, sectionTerms, sectionCookies], 'suggestions', updateUrl);
                updateHelpMenuButtons('toggleSectionSuggestions');
            }
        }

        if (wasExploreActive && section !== 'explore') {
            resetExploreSection();
        }

        if (window.innerWidth <= 468) {
            closeMenuSurface();
            closeMenuOptions();
        }

        updateLogState();
    };

    const handleResize = () => {
        if (moduleOptions.classList.contains('active')) {
            if (window.innerWidth <= 468) {
                if (!menuContentOptions.classList.contains('active')) {
                    menuContentOptions.classList.add('active');
                }
            } else {
                menuContentOptions.classList.remove('active');
                menuContentOptions.removeAttribute('style');
            }
        }
    };

    const handleProfileUpdate = async (button) => {
        const field = button.dataset.field;
        const parentItem = button.closest('.profile-card-item');
        const editState = parentItem.querySelector('.edit-state');
        const viewState = parentItem.querySelector('.view-state');
        const input = editState.querySelector('.edit-input');
        const errorSpan = editState.querySelector('.edit-error-message');
        const newValue = input.value;

        errorSpan.style.display = 'none';
        errorSpan.textContent = '';

        if (field === 'email') {
            const emailRegex = /^[a-zA-Z0-9._-]+@(gmail\.com|outlook\.com)$/i;
            if (!emailRegex.test(newValue)) {
                errorSpan.textContent = 'Solo se permiten correos de @gmail.com o @outlook.com.';
                errorSpan.style.display = 'block';
                return;
            }
        }

        if (field === 'username') {
            const usernameRegex = /^[a-zA-Z0-9_]{4,25}$/;
            if (!usernameRegex.test(newValue)) {
                errorSpan.textContent = 'El nombre debe tener entre 4 y 25 caracteres, y solo puede contener letras, números y guiones bajos.';
                errorSpan.style.display = 'block';
                return;
            }
        }

        const formData = new FormData();
        formData.append('action', 'update_profile');
        formData.append('field', field);
        formData.append('value', newValue);
        formData.append('csrf_token', window.PROJECT_CONFIG.csrfToken);

        try {
            const response = await fetch(window.PROJECT_CONFIG.apiUrl, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                const displaySpan = viewState.querySelector('.profile-card-info span');
                displaySpan.textContent = result.newValue;

                editState.classList.add('hidden');
                viewState.classList.remove('hidden');
            } else {
                errorSpan.textContent = result.message || 'Ocurrió un error desconocido.';
                errorSpan.style.display = 'block';
            }
        } catch (error) {
            errorSpan.textContent = 'Error de conexión. Inténtalo de nuevo.';
            errorSpan.style.display = 'block';
        }
    };

    const handlePreferenceUpdate = async (field, value) => {
        const formData = new FormData();
        formData.append('action', 'update_preference');
        formData.append('field', field);
        formData.append('value', value);
        formData.append('csrf_token', window.PROJECT_CONFIG.csrfToken);

        try {
            const response = await fetch(window.PROJECT_CONFIG.apiUrl, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                if (window.PROJECT_CONFIG.userPreferences) {
                    window.PROJECT_CONFIG.userPreferences[field] = String(value);
                }
            } else {
                console.error('Failed to save preference:', result.message);
            }
        } catch (error) {
            console.error('Connection error while saving preference:', error);
        }
    };

    const initializePreferenceControls = () => {
        const prefs = window.PROJECT_CONFIG.userPreferences || {};

        document.querySelectorAll('[data-preference-field]').forEach(container => {
            const field = container.dataset.preferenceField;
            const value = prefs[field];

            const toggle = container.querySelector('.toggle-switch input[type="checkbox"]');
            if (toggle) {
                toggle.checked = (value == true);
            }

            const selectorButton = container.querySelector('.selector-input');
            if (selectorButton) {
                const menuList = container.querySelector('.menu-list');
                const activeLink = menuList.querySelector(`.menu-link[data-value="${value}"]`) || menuList.querySelector('.menu-link.active');

                if (activeLink) {
                    const textSpan = selectorButton.querySelector('.selected-value-text');
                    const iconSpan = selectorButton.querySelector('.selected-value-icon.left .material-symbols-rounded');

                    textSpan.textContent = activeLink.querySelector('.menu-link-text span').textContent;
                    if (iconSpan && activeLink.querySelector('.menu-link-icon .material-symbols-rounded')) {
                        iconSpan.textContent = activeLink.querySelector('.menu-link-icon .material-symbols-rounded').textContent;
                    }
                }
            }
        });
    };

    const themeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

    const applyTheme = (themeValue) => {
        const docEl = document.documentElement;
        let isDark;

        if (themeValue === 'system') {
            isDark = themeMediaQuery.matches;
        } else {
            isDark = themeValue === 'dark';
        }

        docEl.classList.remove(isDark ? 'light-theme' : 'dark-theme');
        docEl.classList.add(isDark ? 'dark-theme' : 'light-theme');
    };

    const handleSystemThemeChange = (e) => {
        const currentThemePref = window.PROJECT_CONFIG.userPreferences.theme;
        if (currentThemePref === 'system') {
            applyTheme('system');
        }
    };

    const resetExploreSection = () => {
        allMunicipalities = [];
        allUniversities = [];
        displayedMunicipalitiesCount = 0;
        displayedUniversitiesCount = 0;
        currentUniversityFilter = 'all';

        if (municipalitiesGrid) municipalitiesGrid.innerHTML = '';
        if (universitiesGrid) universitiesGrid.innerHTML = '';
        if (searchInput) searchInput.value = '';

        if (exploreTabs) {
            exploreTabs.querySelector('.tab-item[data-tab="municipalities"]').classList.add('active');
            exploreTabs.querySelector('.tab-item[data-tab="universities"]').classList.remove('active');
        }

        document.querySelector('.discovery-content-section[data-section-id="municipalities"]').classList.add('active');
        document.querySelector('.discovery-content-section[data-section-id="universities"]').classList.remove('active');

        const universityFilterButton = document.getElementById('university-municipality-selector-button');
        const universityFilterDropdown = document.getElementById('university-municipality-selector-dropdown');
        if (universityFilterButton && universityFilterDropdown) {
            universityFilterButton.querySelector('.selected-value-text').textContent = 'Filtrar por municipio';
            universityFilterDropdown.querySelectorAll('.menu-link').forEach(l => l.classList.remove('active'));
            const allOption = universityFilterDropdown.querySelector('.menu-link[data-value="all"]');
            if (allOption) allOption.classList.add('active');
        }
    };

    const displayGroups = (sourceArray, gridElement, countState, buttonElement) => {
        gridElement.innerHTML = '';
        const groupsToDisplay = sourceArray.slice(0, countState);

        if (groupsToDisplay.length === 0 && sourceArray.length > 0) {
            gridElement.innerHTML = '<p class="empty-grid-message">No se encontraron más comunidades.</p>';
        } else if (sourceArray.length === 0) {
            gridElement.innerHTML = '<p class="empty-grid-message">No hay comunidades para mostrar.</p>';
        } else {
            renderGroupCards(groupsToDisplay, gridElement);
        }

        if (countState >= sourceArray.length) {
            buttonElement.classList.add('hidden');
        } else {
            buttonElement.classList.remove('hidden');
        }
    };

    const loadMunicipalityGroups = async () => {
        try {
            const response = await fetch(`${window.PROJECT_CONFIG.apiUrl}?action=get_municipality_groups`);
            const data = await response.json();
            if (data.success) {
                allMunicipalities = data.groups;
                displayedMunicipalitiesCount = ITEMS_PER_PAGE;
                displayGroups(allMunicipalities, municipalitiesGrid, displayedMunicipalitiesCount, loadMoreMunicipalitiesButton);
            } else {
                municipalitiesGrid.innerHTML = `<p>${data.message || 'Error al cargar grupos.'}</p>`;
            }
        } catch (error) {
            municipalitiesGrid.innerHTML = '<p>Error de conexión.</p>';
        }
    };

    const loadUniversityGroups = async (municipalityId) => {
        currentUniversityFilter = municipalityId;
        universitiesGrid.innerHTML = '<div class="loader-container"><div class="loader"></div></div>';
        try {
            const response = await fetch(`${window.PROJECT_CONFIG.apiUrl}?action=get_university_groups&municipality_id=${municipalityId}`);
            const data = await response.json();

            if (data.success) {
                allUniversities = data.groups;
                displayedUniversitiesCount = ITEMS_PER_PAGE;
                displayGroups(allUniversities, universitiesGrid, displayedUniversitiesCount, loadMoreUniversitiesButton);
            } else {
                universitiesGrid.innerHTML = `<p>${data.message || 'Error al cargar universidades.'}</p>`;
            }
        } catch (error) {
            console.error('Error al cargar universidades:', error);
            universitiesGrid.innerHTML = '<p>Error de conexión.</p>';
        }
    };

    const populateMunicipalityFilter = async () => {
        const universityMunicipalitySelectorDropdown = document.getElementById('university-municipality-selector-dropdown');
        if (!universityMunicipalitySelectorDropdown) return;

        try {
            const response = await fetch(`${window.PROJECT_CONFIG.apiUrl}?action=get_municipalities`);
            const data = await response.json();
            if (data.success) {
                const menuList = universityMunicipalitySelectorDropdown.querySelector('.menu-list');
                if (!menuList) return;
                menuList.innerHTML = '';

                let totalUniversities = 0;
                data.municipalities.forEach(municipality => {
                    totalUniversities += parseInt(municipality.university_count, 10);
                });

                const allOption = document.createElement('div');
                allOption.className = 'menu-link active';
                allOption.dataset.value = 'all';
                allOption.innerHTML = `
                    <div class="menu-link-icon"><span class="material-symbols-rounded">public</span></div>
                    <div class="menu-link-text">
                        <span>Todos los municipios</span>
                        <span class="menu-link-badge">${totalUniversities}</span>
                    </div>
                `;
                menuList.appendChild(allOption);

                data.municipalities.forEach(municipality => {
                    const option = document.createElement('div');
                    option.className = 'menu-link';
                    option.dataset.value = municipality.id;
                    option.innerHTML = `
                        <div class="menu-link-icon"><span class="material-symbols-rounded">location_city</span></div>
                        <div class="menu-link-text">
                            <span>${municipality.group_title}</span>
                            <span class="menu-link-badge">${municipality.university_count}</span>
                        </div>
                    `;
                    menuList.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Error populating municipality filter:', error);
        }
    };

    const renderGroupCards = (groups, grid) => {
        grid.innerHTML = '';
        groups.forEach(group => {
            const isMember = group.is_member;
            const buttonText = isMember ? 'Abandonar' : 'Unirse';
            const buttonClass = isMember ? 'community-card-button leave' : 'community-card-button';
            const card = document.createElement('div');
            card.className = 'community-card';
            card.dataset.groupUuid = group.uuid;
            card.dataset.groupName = group.group_title.toLowerCase();

            const groupType = grid === universitiesGrid ? 'university' : 'municipality';
            card.dataset.groupType = groupType;

            const icon = grid === universitiesGrid ? 'school' : 'groups';

            card.innerHTML = `
                <div class="community-card-header">
                    <div class="community-card-icon-wrapper">
                        <span class="material-symbols-rounded">${icon}</span>
                    </div>
                    <div class="community-card-info">
                        <h3 class="community-card-title">${group.group_title}</h3>
                        <p class="community-card-subtitle">${group.group_subtitle}</p>
                    </div>
                </div>
                <div class="community-card-footer">
                    <div class="community-card-stats">
                        <div class="info-pill">
                            <span class="material-symbols-rounded">${group.privacy === 'public' ? 'public' : 'lock'}</span>
                            <span>${group.privacy === 'public' ? 'Público' : 'Privado'}</span>
                        </div>
                        <div class="info-pill">
                            <span class="material-symbols-rounded">group</span>
                            <span data-member-count>${group.members}</span>
                        </div>
                    </div>
                    <button class="${buttonClass}" data-privacy="${group.privacy}">${buttonText}</button>
                </div>`;
            grid.appendChild(card);
        });
    };

    const loadHomeContent = async () => {
        try {
            const response = await fetch(`${window.PROJECT_CONFIG.apiUrl}?action=get_user_groups`);
            const data = await response.json();
            if (data.success && data.groups.length > 0) {
                renderDashboardView(data.groups);
            } else {
                renderDiscoveryView();
            }
        } catch (error) {
            console.error("Error loading user groups, showing discovery view.", error);
            renderDiscoveryView();
        }
    };

    const refreshHomeView = () => {
        loadHomeContent();
    };

    const renderDashboardView = (groups) => {
        const homeTabs = document.getElementById('home-tabs');
        const homeGrid = document.getElementById('home-grid');

        homeTabs.innerHTML = `
            <div class="tab-item active" data-tab="my-communities">
                <span class="material-symbols-rounded">groups</span>
                <span>Mis Comunidades</span>
            </div>
            <div class="tab-item" data-tab="activity-feed">
                <span class="material-symbols-rounded">feed</span>
                <span>Actividad Reciente</span>
            </div>
        `;

        let gridHTML = '';
        groups.forEach(group => {
            const icon = group.group_type === 'university' ? 'school' : 'groups';
            gridHTML += `
                <div class="community-card" data-group-uuid="${group.uuid}">
                    <div class="community-card-header">
                        <div class="community-card-icon-wrapper">
                            <span class="material-symbols-rounded">${icon}</span>
                        </div>
                        <div class="community-card-info">
                            <h3 class="community-card-title">${group.group_title}</h3>
                            <p class="community-card-subtitle">${group.group_subtitle}</p>
                        </div>
                    </div>
                    <div class="community-card-footer">
                        <span class="community-card-members">${group.members} miembros</span>
                        <button class="community-card-button view">Ver</button>
                    </div>
                </div>
            `;
        });
        homeGrid.innerHTML = gridHTML;
    };

    const renderDiscoveryView = () => {
        const homeTabs = document.getElementById('home-tabs');
        const homeGrid = document.getElementById('home-grid');

        homeTabs.innerHTML = `
            <div class="tab-item active" data-tab="recommendations">
                <span class="material-symbols-rounded">recommend</span>
                <span>Recomendaciones</span>
            </div>
            <div class="tab-item" data-tab="trending">
                <span class="material-symbols-rounded">local_fire_department</span>
                <span>Tendencias</span>
            </div>
        `;

        homeGrid.innerHTML = `
            <div class="community-card">
                <div class="community-card-header">
                    <div class="community-card-icon-wrapper">
                        <span class="material-symbols-rounded">groups</span>
                    </div>
                    <div class="community-card-info">
                        <h3 class="community-card-title">Comunidad de Victoria</h3>
                        <p class="community-card-subtitle">Espacio para los residentes de la capital.</p>
                    </div>
                </div>
                <div class="community-card-footer">
                    <span class="community-card-members">1,234 miembros</span>
                    <button class="community-card-button">Unirse</button>
                </div>
            </div>
            <div class="community-card">
                 <div class="community-card-header">
                    <div class="community-card-icon-wrapper">
                        <span class="material-symbols-rounded">school</span>
                    </div>
                    <div class="community-card-info">
                        <h3 class="community-card-title">Universidad Politécnica</h3>
                        <p class="community-card-subtitle">Comunidad oficial de estudiantes.</p>
                    </div>
                </div>
                <div class="community-card-footer">
                    <span class="community-card-members">567 miembros</span>
                    <button class="community-card-button">Unirse</button>
                </div>
            </div>
        `;
    };

    const reportMessage = async (messageId) => {
        const formData = new FormData();
        formData.append('action', 'report_message');
        formData.append('message_id', messageId);
        formData.append('csrf_token', window.PROJECT_CONFIG.csrfToken);

        try {
            const response = await fetch(window.PROJECT_CONFIG.apiUrl, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                alert('Mensaje reportado con éxito.');
            } else {
                alert(`Error al reportar: ${result.message}`);
            }
        } catch (error) {
            alert('Error de conexión al reportar el mensaje.');
        } finally {
            closeAccountActionModal();
        }
    };

    function setupEventListeners() {
        toggleOptionsButton.addEventListener('click', (e) => {
            e.stopPropagation();
            if (moduleOptions.classList.contains('active')) {
                closeMenuOptions();
            } else {
                openMenuOptions();
            }
        });

        toggleSurfaceButton.addEventListener('click', (e) => {
            e.stopPropagation();
            if (moduleSurface.classList.contains('active')) {
                closeMenuSurface();
            } else {
                openMenuSurface();
            }
        });

        const homeGrid = document.getElementById('home-grid');
        if (homeGrid) {
            homeGrid.addEventListener('click', (e) => {
                const viewButton = e.target.closest('.community-card-button.view');
                if (!viewButton) return;

                const card = viewButton.closest('.community-card');
                const groupId = card.dataset.groupUuid;
                const groupTitle = card.querySelector('.community-card-title').textContent;

                if (groupId && groupTitle) {
                    handleNavigationChange('chat', { uuid: groupId, title: groupTitle });
                }
            });
        }

        const chatContainer = document.getElementById('chat-section-container');
        if (chatContainer) {
            chatContainer.addEventListener('click', (e) => {
                const messageBubble = e.target.closest('.message-bubble');
                if (messageBubble) {
                    e.stopPropagation();
                    const isAlreadyActive = messageBubble.classList.contains('active');
                    closeAllModules();
                    if (!isAlreadyActive) {
                        openMessageOptions(messageBubble);
                    }
                }
            });
        }

        document.body.addEventListener('click', (e) => {
            const replyButton = e.target.closest('[data-action="reply-message"]');
            if (replyButton) {
                e.stopPropagation();
                const messageBubble = document.querySelector('.message-bubble.active');
                if (messageBubble) {
                    const messageId = messageBubble.dataset.messageId;
                    const author = messageBubble.querySelector('.message-info').textContent;
                    const text = messageBubble.querySelector('.message-content p').textContent;

                    currentReplyMessageId = messageId;

                    const previewContainer = document.getElementById('reply-preview-container');
                    previewContainer.querySelector('.reply-preview-author').textContent = author;
                    previewContainer.querySelector('.reply-preview-text').textContent = text;
                    previewContainer.classList.remove('disabled');
                    previewContainer.classList.add('active');

                    document.querySelector('.chat-input-field').focus();
                }
                closeMessageOptions();
            }

            const cancelReplyButton = e.target.closest('[data-action="cancel-reply"]');
            if (cancelReplyButton) {
                currentReplyMessageId = null;
                const previewContainer = document.getElementById('reply-preview-container');
                previewContainer.classList.add('disabled');
                previewContainer.classList.remove('active');
            }

            const reportButton = e.target.closest('[data-action="report-message"]');
            if (reportButton) {
                e.stopPropagation();
                const messageBubble = document.querySelector('.message-bubble.active');
                if (messageBubble) {
                    const messageId = messageBubble.dataset.messageId;
                    const messageText = messageBubble.querySelector('.message-content p').textContent;

                    const reportDialog = document.querySelector('[data-dialog="reportMessage"]');
                    if (reportDialog) {
                        reportDialog.querySelector('.reported-message-text').textContent = messageText;
                        reportDialog.querySelector('input[name="message_id"]').value = messageId;

                        closeAllModules();
                        openAccountActionModal('reportMessage');
                    }
                }
            }

            // --- INICIO DE LA MODIFICACIÓN: MANEJAR CLIC EN ELIMINAR ---
            const deleteButton = e.target.closest('[data-action="delete-message"]');
            if (deleteButton) {
                e.stopPropagation();
                const messageBubble = document.querySelector('.message-bubble.active');
                if (messageBubble) {
                    const messageId = messageBubble.dataset.messageId;
                    if (websocket && websocket.readyState === WebSocket.OPEN) {
                        const messageData = {
                            type: 'delete_message',
                            message_id: parseInt(messageId, 10)
                        };
                        websocket.send(JSON.stringify(messageData));
                    }
                }
                closeMessageOptions();
            }
            // --- FIN DE LA MODIFICACIÓN ---

            const confirmReportButton = e.target.closest('[data-action="confirmReport"]');
            if (confirmReportButton) {
                const reportDialog = confirmReportButton.closest('[data-dialog="reportMessage"]');
                if (reportDialog) {
                    const messageId = reportDialog.querySelector('input[name="message_id"]').value;
                    reportMessage(messageId);
                }
            }

            const copyButton = e.target.closest('[data-action="copy-message"]');
            if (copyButton) {
                e.stopPropagation();
                const messageBubble = document.querySelector('.message-bubble.active');
                if (messageBubble) {
                    const messageText = messageBubble.querySelector('.message-content p').textContent;
                    // --- INICIO DE LA MODIFICACIÓN ---
                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(messageText).catch(err => {
                            console.error('Error al copiar el texto con la API moderna: ', err);
                        });
                    } else {
                        // Fallback para HTTP o navegadores no compatibles
                        const textArea = document.createElement("textarea");
                        textArea.value = messageText;
                        textArea.style.position = "fixed";
                        textArea.style.left = "-9999px";
                        document.body.appendChild(textArea);
                        textArea.focus();
                        textArea.select();
                        try {
                            document.execCommand('copy');
                        } catch (err) {
                            console.error('Error al copiar el texto con el método de fallback: ', err);
                        }
                        document.body.removeChild(textArea);
                    }
                    // --- FIN DE LA MODIFICACIÓN ---
                    closeMessageOptions();
                }
            }
        });

        // --- INICIO DE LA MODIFICACIÓN ---
        const chatForm = document.getElementById('chat-form');
        if (chatForm) {
            chatForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const input = chatForm.querySelector('.chat-input-field');
                const message = input.value.trim();

                if (message && websocket && websocket.readyState === WebSocket.OPEN) {
                    const messageData = {
                        type: 'chat_message',
                        message: message,
                        reply_to_message_id: currentReplyMessageId
                    };
                    websocket.send(JSON.stringify(messageData));
                    input.value = '';

                    // Ocultar la vista previa y resetear el ID de respuesta
                    currentReplyMessageId = null;
                    const previewContainer = document.getElementById('reply-preview-container');
                    previewContainer.classList.add('disabled');
                    previewContainer.classList.remove('active');
                }
            });
        }
        // --- FIN DE LA MODIFICACIÓN ---

        const usernameInput = document.querySelector('[data-section="name"] .edit-input');
        if (usernameInput) {
            usernameInput.addEventListener('input', (e) => {
                e.target.value = e.target.value.replace(/[^a-zA-Z0-9_]/g, '');
            });
        }

        if (exploreTabs) {
            exploreTabs.addEventListener('click', (e) => {
                const tabItem = e.target.closest('.tab-item');
                if (!tabItem || tabItem.classList.contains('active')) return;
                const targetTab = tabItem.dataset.tab;
                handleNavigationChange('explore', targetTab);
            });
        }

        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                const searchTerm = e.target.value.toLowerCase();
                document.querySelectorAll('.community-card').forEach(card => {
                    const groupName = card.dataset.groupName;
                    card.style.display = groupName.includes(searchTerm) ? 'flex' : 'none';
                });
            });
        }

        if (loadMoreMunicipalitiesButton) {
            loadMoreMunicipalitiesButton.addEventListener('click', () => {
                displayedMunicipalitiesCount += ITEMS_PER_PAGE;
                displayGroups(allMunicipalities, municipalitiesGrid, displayedMunicipalitiesCount, loadMoreMunicipalitiesButton);
            });
        }

        if (loadMoreUniversitiesButton) {
            loadMoreUniversitiesButton.addEventListener('click', () => {
                displayedUniversitiesCount += ITEMS_PER_PAGE;
                displayGroups(allUniversities, universitiesGrid, displayedUniversitiesCount, loadMoreUniversitiesButton);
            });
        }

        document.querySelectorAll('[data-action="toggleSelector"]').forEach((button, index) => {
            const parentControlGroup = button.closest('.profile-control-group, .explore-control-group');
            if (!parentControlGroup) return;

            const selectorDropdown = parentControlGroup.querySelector('[data-module="moduleSelector"]');
            if (!selectorDropdown) return;

            if (!selectorDropdown.id) {
                selectorDropdown.id = `selector-dropdown-${index}`;
            }
            const popperId = selectorDropdown.id;
            button.setAttribute('aria-controls', popperId);

            button.addEventListener('click', (e) => {
                e.stopPropagation();
                const isAlreadyActive = selectorDropdown.classList.contains('active');

                if (!allowMultipleActiveModules) {
                    closeAllModules();
                }

                if (isAlreadyActive) {
                    closeAllSelectors();
                } else {
                    selectorDropdown.classList.remove('disabled');
                    selectorDropdown.classList.add('active');
                    button.classList.add('active');

                    popperInstances[popperId] = Popper.createPopper(button, selectorDropdown, {
                        placement: 'bottom-start',
                        modifiers: [{ name: 'offset', options: { offset: [0, 8] } }],
                    });
                }
            });

            selectorDropdown.addEventListener('click', (e) => {
                const link = e.target.closest('.menu-link');
                if (!link) return;

                const newTextSpan = link.querySelector('.menu-link-text span');
                const newText = newTextSpan ? newTextSpan.textContent : '';

                if (button.querySelector('.selected-value-text')) {
                    button.querySelector('.selected-value-text').textContent = newText;
                }

                const newIcon = link.querySelector('.menu-link-icon .material-symbols-rounded');
                const selectedValueIconLeft = button.querySelector('.selected-value-icon.left .material-symbols-rounded');
                if (selectedValueIconLeft && newIcon) {
                    selectedValueIconLeft.textContent = newIcon.textContent;
                }

                selectorDropdown.querySelectorAll('.menu-link').forEach(l => l.classList.remove('active'));
                link.classList.add('active');

                const parentItem = button.closest('[data-preference-field]');
                if (parentItem) {
                    const preferenceField = parentItem.dataset.preferenceField;
                    const newValue = link.dataset.value;
                    handlePreferenceUpdate(preferenceField, newValue);
                    if (preferenceField === 'theme') {
                        applyTheme(newValue);
                    }
                }

                if (button.id === 'university-municipality-selector-button') {
                    const municipalityId = link.dataset.value;
                    loadUniversityGroups(municipalityId);
                }

                closeAllSelectors();
            });
        });

        const discoveryContent = sectionExplore.querySelector('.discovery-content');
        if (discoveryContent) {
            discoveryContent.addEventListener('click', async (e) => {
                const joinButton = e.target.closest('.community-card-button');
                if (!joinButton || joinButton.classList.contains('view')) return;

                const card = joinButton.closest('.community-card');
                const groupUuid = card.dataset.groupUuid;
                const groupType = card.dataset.groupType;
                const privacy = joinButton.dataset.privacy;

                if (privacy === 'private' && !joinButton.classList.contains('leave')) {
                    const accessCodeDialog = document.querySelector('[data-dialog="accessCode"]');
                    if (accessCodeDialog) {
                        accessCodeDialog.dataset.groupUuid = groupUuid;
                        accessCodeDialog.dataset.groupType = groupType;

                        const accountActionModal = document.querySelector('[data-module="accountActionModal"]');
                        accountActionModal.classList.remove('disabled');
                        accountActionModal.classList.add('active');
                        accessCodeDialog.classList.remove('disabled');
                        accessCodeDialog.classList.add('active');
                    }
                    return;
                }

                const formData = new FormData();
                formData.append('action', 'toggle_group_membership');
                formData.append('group_uuid', groupUuid);
                formData.append('group_type', groupType);
                formData.append('csrf_token', window.PROJECT_CONFIG.csrfToken);

                try {
                    const response = await fetch(window.PROJECT_CONFIG.apiUrl, {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();

                    if (result.success) {
                        if (result.action === 'private') {
                            alert(result.message);
                        } else {
                            const memberCountSpan = card.querySelector('[data-member-count]');
                            memberCountSpan.textContent = `${result.newMemberCount}`;
                            if (result.action === 'joined') {
                                joinButton.textContent = 'Abandonar';
                                joinButton.classList.add('leave');
                            } else {
                                joinButton.textContent = 'Unirse';
                                joinButton.classList.remove('leave');
                            }
                            refreshHomeView();
                        }
                    } else {
                        alert(result.message || 'Ocurrió un error.');
                    }
                } catch (error) {
                    alert('Error de conexión.');
                }
            });
        }


        const submitAccessCodeButton = document.querySelector('[data-action="submitAccessCode"]');
        if (submitAccessCodeButton) {
            submitAccessCodeButton.addEventListener('click', async () => {
                const accessCodeDialog = document.querySelector('[data-dialog="accessCode"]');
                const groupUuid = accessCodeDialog.dataset.groupUuid;
                const groupType = accessCodeDialog.dataset.groupType;
                const accessCodeInput = document.getElementById('group-access-code');
                const errorContainer = accessCodeDialog.querySelector('.dialog-error-message');

                const formData = new FormData();
                formData.append('action', 'join_private_group');
                formData.append('group_uuid', groupUuid);
                formData.append('group_type', groupType);
                formData.append('access_code', accessCodeInput.value);
                formData.append('csrf_token', window.PROJECT_CONFIG.csrfToken);

                try {
                    const response = await fetch(window.PROJECT_CONFIG.apiUrl, {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();

                    if (result.success) {
                        closeAccountActionModal();
                        const card = document.querySelector(`.community-card[data-group-uuid="${groupUuid}"]`);
                        if (card) {
                            const memberCountSpan = card.querySelector('[data-member-count]');
                            const joinButton = card.querySelector('.community-card-button');
                            memberCountSpan.textContent = `${result.newMemberCount}`;
                            joinButton.textContent = 'Abandonar';
                            joinButton.classList.add('leave');
                        }
                        refreshHomeView();
                    } else {
                        errorContainer.textContent = result.message || 'Ocurrió un error.';
                        errorContainer.style.display = 'block';
                    }
                } catch (error) {
                    errorContainer.textContent = 'Error de conexión.';
                    errorContainer.style.display = 'block';
                }
            });
        }

        const groupAccessCodeInput = document.getElementById('group-access-code');
        if (groupAccessCodeInput) {
            groupAccessCodeInput.addEventListener('input', (e) => {
                const input = e.target;
                let value = input.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
                let formattedValue = '';
                if (value.length > 0) {
                    formattedValue = value.match(/.{1,4}/g).join('-');
                }
                input.value = formattedValue;
            });
        }

        document.querySelectorAll('.toggle-switch input[type="checkbox"]').forEach(toggle => {
            toggle.addEventListener('change', (e) => {
                const parentItem = e.target.closest('.profile-card-item');
                if (parentItem && parentItem.dataset.preferenceField) {
                    const field = parentItem.dataset.preferenceField;
                    const value = e.target.checked;
                    handlePreferenceUpdate(field, value);
                }
            });
        });

        document.querySelectorAll('[data-action="toggleEditState"]').forEach(button => {
            button.addEventListener('click', (e) => {
                const parent = e.target.closest('.profile-card-item');
                parent.querySelector('.view-state').classList.add('hidden');
                parent.querySelector('.edit-state').classList.remove('hidden');
            });
        });

        document.querySelectorAll('[data-action="toggleViewState"]').forEach(button => {
            button.addEventListener('click', (e) => {
                const parent = e.target.closest('.profile-card-item');
                parent.querySelector('.edit-state').classList.add('hidden');
                parent.querySelector('.view-state').classList.remove('hidden');
                const errorSpan = parent.querySelector('.edit-error-message');
                if (errorSpan) {
                    errorSpan.style.display = 'none';
                    errorSpan.textContent = '';
                }
            });
        });

        document.querySelectorAll('[data-action="saveProfile"]').forEach(button => {
            button.addEventListener('click', (e) => {
                handleProfileUpdate(e.target);
            });
        });

        const generalContentTop = document.querySelector('.general-content-top');
        const scrollableSections = document.querySelectorAll('.section-content.overflow-y');

        scrollableSections.forEach(section => {
            section.addEventListener('scroll', () => {
                if (generalContentTop) {
                    generalContentTop.classList.toggle('shadow', section.scrollTop > 0);
                }
            });
        });

        if (openUpdatePasswordModalButton) {
            openUpdatePasswordModalButton.addEventListener('click', () => openAccountActionModal('updatePassword'));
        }
        if (openDeleteAccountModalButton) {
            openDeleteAccountModalButton.addEventListener('click', () => openAccountActionModal('deleteAccount'));
        }
        closeAccountActionModalButtons.forEach(button => {
            button.addEventListener('click', closeAccountActionModal);
        });

        if (confirmCurrentPasswordButton) {
            confirmCurrentPasswordButton.addEventListener('click', showNewPasswordPane);
        }

        if (saveNewPasswordButton) {
            saveNewPasswordButton.addEventListener('click', saveNewPassword);
        }

        if (confirmDeleteAccountButton) {
            confirmDeleteAccountButton.addEventListener('click', handleDeleteAccount);
        }

        if (toggleSectionHomeButtons) {
            toggleSectionHomeButtons.forEach(button => {
                button.addEventListener('click', () => {
                    if (!isSectionHomeActive) handleNavigationChange('home');
                });
            });
        }

        if (toggleSectionExploreButtons) {
            toggleSectionExploreButtons.forEach(button => {
                button.addEventListener('click', () => {
                    if (!isSectionExploreActive) handleNavigationChange('explore', 'municipalities');
                });
            });
        }

        if (toggleSectionSettingsButton) {
            toggleSectionSettingsButton.addEventListener('click', () => {
                if (!isSectionSettingsActive || !isSectionProfileActive) {
                    handleNavigationChange('settings', 'profile');
                }
                closeMenuOptions();
            });
        }
        if (toggleSectionHelpButton) {
            toggleSectionHelpButton.addEventListener('click', () => {
                if (!isSectionHelpActive || !isSectionPrivacyActive) {
                    handleNavigationChange('help', 'privacy');
                }
                closeMenuOptions();
            });
        }

        if (logoutButton) {
            logoutButton.addEventListener('click', () => {
                if (logoutButton.classList.contains('loading')) return;
                logoutButton.classList.add('loading');
                const loaderIconContainer = document.createElement('div');
                loaderIconContainer.className = 'menu-link-icon';
                const loader = document.createElement('div');
                loader.className = 'loader';
                loaderIconContainer.appendChild(loader);
                logoutButton.appendChild(loaderIconContainer);
                const backendLogoutUrl = window.PROJECT_CONFIG.baseUrl.replace('ProjectLeviathan - Frontend', 'ProjectLeviathan - Backend/logout.php');
                const csrfToken = window.PROJECT_CONFIG.csrfToken;
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = backendLogoutUrl;
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = csrfToken;
                form.appendChild(csrfInput);
                document.body.appendChild(form);
                form.submit();
            });
        }

        if (toggleSectionHomeFromSettingsButton) {
            toggleSectionHomeFromSettingsButton.addEventListener('click', () => handleNavigationChange('home'));
        }
        if (toggleSectionProfileButton) {
            toggleSectionProfileButton.addEventListener('click', () => {
                if (!isSectionProfileActive) handleNavigationChange('settings', 'profile');
            });
        }
        if (toggleSectionLoginButton) {
            toggleSectionLoginButton.addEventListener('click', () => {
                if (!isSectionLoginActive) handleNavigationChange('settings', 'login');
            });
        }
        if (toggleSectionAccessibilityButton) {
            toggleSectionAccessibilityButton.addEventListener('click', () => {
                if (!isSectionAccessibilityActive) handleNavigationChange('settings', 'accessibility');
            });
        }
        if (toggleSectionHomeFromHelpButton) {
            toggleSectionHomeFromHelpButton.addEventListener('click', () => handleNavigationChange('home'));
        }
        if (toggleSectionPrivacyButton) {
            toggleSectionPrivacyButton.addEventListener('click', () => {
                if (!isSectionPrivacyActive) handleNavigationChange('help', 'privacy');
            });
        }
        if (toggleSectionTermsButton) {
            toggleSectionTermsButton.addEventListener('click', () => {
                if (!isSectionTermsActive) handleNavigationChange('help', 'terms');
            });
        }
        if (toggleSectionCookiesButton) {
            toggleSectionCookiesButton.addEventListener('click', () => {
                if (!isSectionCookiesActive) handleNavigationChange('help', 'cookies');
            });
        }
        if (toggleSectionSuggestionsButton) {
            toggleSectionSuggestionsButton.addEventListener('click', () => {
                if (!isSectionSuggestionsActive) handleNavigationChange('help', 'suggestions');
            });
        }

        if (closeOnClickOutside) {
            document.addEventListener('click', (e) => {
                if (isAnimating) return;
                const moduleOptionsIsOpen = moduleOptions.classList.contains('active');
                if (moduleOptionsIsOpen) {
                    if (window.innerWidth <= 468 && e.target === moduleOptions) {
                        closeMenuOptions();
                    }
                    else if (window.innerWidth > 468 && !moduleOptions.contains(e.target) && !toggleOptionsButton.contains(e.target)) {
                        closeMenuOptions();
                    }
                }
                const activeSelector = document.querySelector('[data-module="moduleSelector"].active');
                if (activeSelector) {
                    const selectorButton = document.querySelector(`[aria-controls="${activeSelector.id}"]`);
                    if (selectorButton && !selectorButton.contains(e.target) && !activeSelector.contains(e.target)) {
                        closeAllSelectors();
                    }
                }
                if (moduleSurface.classList.contains('active') && !moduleSurface.contains(e.target) && !toggleSurfaceButton.contains(e.target)) {
                    closeMenuSurface();
                }
                if (accountActionModal.classList.contains('active') && e.target === accountActionModal) {
                    closeAccountActionModal();
                }

                const activeMessageDropdown = document.getElementById('message-options-dropdown');
                if (activeMessageDropdown && !activeMessageDropdown.contains(e.target)) {
                    closeMessageOptions();
                }
            });
        }

        if (closeOnEscape) {
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    closeAllModules();
                }
            });
        }

        window.addEventListener('resize', handleResize);
        themeMediaQuery.addEventListener('change', handleSystemThemeChange);
    }

    const initializePageData = () => {
        const initialState = getCurrentUrlState();
        if (initialState) {
            let initialSubsection = initialState.subsection;
            if (initialState.isChatSection && initialState.id) {
                initialSubsection = { uuid: initialState.id, title: 'Cargando...' };
            }
            handleNavigationChange(initialState.section, initialSubsection, false);

            if (initialState.section === 'settings' && initialState.subsection === 'login') {
                loadAccountDates();
            }
        }
        initializePreferenceControls();
    };

    setupEventListeners();
    initializePageData();

    const handleDragClose = () => {
        if (moduleOptions.classList.contains('active')) {
            closeMenuOptions();
        }
    };

    initDragController(handleDragClose, () => isAnimating);

    updateLogState();
    console.log('ProjectLeviathan initialized with URL routing and dynamic modules support');
}

export { initMainController };