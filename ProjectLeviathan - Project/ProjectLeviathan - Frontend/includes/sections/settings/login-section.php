<div class="section-content overflow-y <?php echo $CURRENT_SUBSECTION === 'login' ? 'active' : 'disabled'; ?>" data-section="sectionLogin">
    <div class="settings-container">
        <div class="profile-card">
            <div class="profile-header-container">
                <div class="profile-header">
                    <h2>Iniciar sesión</h2>
                    <p>Gestiona tu contraseña, sesiones activas y otros datos relacionados con la seguridad de tu cuenta.</p>
                </div>
            </div>
        </div>

        <div class="profile-card">
            <div class="profile-card-item column-layout">
                <div class="view-state">
                    <div class="profile-card-content">
                        <div class="icon-background">
                            <span class="material-symbols-rounded">lock</span>
                        </div>
                        <div class="profile-card-info allow-wrap">
                            <strong>Contraseña</strong>
                            <span id="last-password-update">Cargando...</span>
                        </div>
                    </div>
                    <button class="edit-button" data-action="openUpdatePasswordModal">Actualizar</button>
                </div>
            </div>
        </div>

        <div class="profile-card">
            <div class="profile-card-item with-divider column-layout">
                <div class="view-state">
                    <div class="profile-card-content">
                        <div class="profile-card-info allow-wrap">
                            <strong>Cerrar sesión en todos los dispositivos</strong>
                            <div class="logout-everywhere-section">
                                <span>¿Crees que olvidaste cerrar sesión en otro lugar? Protege tu cuenta cerrando todas las sesiones activas ahora mismo.</span>
                            </div>
                        </div>
                    </div>
                    <button class="edit-button">Cerrar todas las sesiones</button>
                </div>
            </div>
            <div class="profile-card-item column-layout">
                <div class="view-state">
                    <div class="profile-card-content">
                        <div class="profile-card-info allow-wrap">
                            <strong>Eliminar tu cuenta</strong>
                            <div class="delete-account-warning">
                                <p>
                                    Esta acción es permanente y no se puede deshacer. Al continuar, perderás el acceso a todas tus comunidades y cualquier contenido que hayas guardado. Tu cuenta se creó el <span id="account-creation-date">Cargando...</span>.
                                </p>
                            </div>
                        </div>
                    </div>
                    <button class="edit-button" data-action="openDeleteAccountModal">Eliminar cuenta</button>
                </div>
            </div>
        </div>
    </div>
</div>