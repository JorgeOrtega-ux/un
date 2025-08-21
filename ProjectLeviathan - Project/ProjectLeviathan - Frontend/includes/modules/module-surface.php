<div class="module-content module-surface body-title disabled" data-module="moduleSurface">
    <div class="menu-content overflow-y disabled" data-surface-type="main">
        <div class="menu-list">
            <div class="menu-link <?php echo ($CURRENT_SECTION === 'home') ? 'active' : ''; ?>" data-action="toggleSectionHome">
                <div class="menu-link-icon">
                    <span class="material-symbols-rounded">home</span>
                </div>
                <div class="menu-link-text">
                    <span>Página principal</span>
                </div>
            </div>
            <div class="menu-link <?php echo ($CURRENT_SECTION === 'explore') ? 'active' : ''; ?>" data-action="toggleSectionExplore">
                <div class="menu-link-icon">
                    <span class="material-symbols-rounded">explore</span>
                </div>
                <div class="menu-link-text">
                    <span>Explorar comunidades</span>
                </div>
            </div>
        </div>
    </div>
    <div class="menu-content overflow-y disabled" data-surface-type="settings">
        <div class="menu-list">
            <div class="menu-link" data-action="toggleSectionHomeFromSettings">
                <div class="menu-link-icon">
                    <span class="material-symbols-rounded">arrow_back</span>
                </div>
                <div class="menu-link-text">
                    <span>Volver a inicio</span>
                </div>
            </div>
            <div class="menu-link <?php echo ($CURRENT_SUBSECTION === 'profile') ? 'active' : ''; ?>" data-action="toggleSectionProfile">
                <div class="menu-link-icon">
                    <span class="material-symbols-rounded">person</span>
                </div>
                <div class="menu-link-text">
                    <span>Tu Perfil</span>
                </div>
            </div>
            <div class="menu-link <?php echo ($CURRENT_SUBSECTION === 'login') ? 'active' : ''; ?>" data-action="toggleSectionLogin">
                <div class="menu-link-icon">
                    <span class="material-symbols-rounded">login</span>
                </div>
                <div class="menu-link-text">
                    <span>Iniciar Sesión</span>
                </div>
            </div>
            <div class="menu-link <?php echo ($CURRENT_SUBSECTION === 'accessibility') ? 'active' : ''; ?>" data-action="toggleSectionAccessibility">
                <div class="menu-link-icon">
                    <span class="material-symbols-rounded">accessibility</span>
                </div>
                <div class="menu-link-text">
                    <span>Accesibilidad</span>
                </div>
            </div>
        </div>
    </div>
    <div class="menu-content overflow-y disabled" data-surface-type="help">
        <div class="menu-list">
            <div class="menu-link" data-action="toggleSectionHomeFromHelp">
                <div class="menu-link-icon">
                    <span class="material-symbols-rounded">arrow_back</span>
                </div>
                <div class="menu-link-text">
                    <span>Volver a inicio</span>
                </div>
            </div>
            <div class="menu-link <?php echo ($CURRENT_SUBSECTION === 'privacy') ? 'active' : ''; ?>" data-action="toggleSectionPrivacy">
                <div class="menu-link-icon">
                    <span class="material-symbols-rounded">privacy_tip</span>
                </div>
                <div class="menu-link-text">
                    <span>Política de privacidad</span>
                </div>
            </div>
            <div class="menu-link <?php echo ($CURRENT_SUBSECTION === 'terms') ? 'active' : ''; ?>" data-action="toggleSectionTerms">
                <div class="menu-link-icon">
                    <span class="material-symbols-rounded">gavel</span>
                </div>
                <div class="menu-link-text">
                    <span>Términos y condiciones</span>
                </div>
            </div>
            <div class="menu-link <?php echo ($CURRENT_SUBSECTION === 'cookies') ? 'active' : ''; ?>" data-action="toggleSectionCookies">
                <div class="menu-link-icon">
                    <span class="material-symbols-rounded">cookie</span>
                </div>
                <div class="menu-link-text">
                    <span>Política de cookies</span>
                </div>
            </div>
            <div class="menu-link <?php echo ($CURRENT_SUBSECTION === 'suggestions') ? 'active' : ''; ?>" data-action="toggleSectionSuggestions">
                <div class="menu-link-icon">
                    <span class="material-symbols-rounded">feedback</span>
                </div>
                <div class="menu-link-text">
                    <span>Enviar sugerencias</span>
                </div>
            </div>
        </div>
    </div>
    <div class="menu-content overflow-y disabled" data-surface-type="chat">
        <div class="menu-list">
            <div class="menu-link" data-action="toggleSectionHome">
                <div class="menu-link-icon">
                    <span class="material-symbols-rounded">arrow_back</span>
                </div>
                <div class="menu-link-text">
                    <span>Volver a inicio</span>
                </div>
            </div>
            <div class="menu-link-separator"></div>
            <div class="menu-link <?php echo ($CURRENT_SUBSECTION === 'messages') ? 'active' : ''; ?>" data-action="toggleChatMessages">
                <div class="menu-link-icon">
                    <span class="material-symbols-rounded">chat</span>
                </div>
                <div class="menu-link-text">
                    <span id="chat-messages-menu-title">Chat</span>
                </div>
            </div>
            <div class="menu-link <?php echo ($CURRENT_SUBSECTION === 'members') ? 'active' : ''; ?>" data-action="toggleChatMembers">
                <div class="menu-link-icon">
                    <span class="material-symbols-rounded">groups</span>
                </div>
                <div class="menu-link-text">
                    <span>Ver miembros</span>
                </div>
            </div>
        </div>
    </div>
    </div>