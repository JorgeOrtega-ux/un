<?php
// --- INICIO DE LA MODIFICACIÓN ---
// Carga las preferencias para usarlas directamente en el HTML.
$prefs = $_SESSION['user_preferences'] ?? [];
// --- FIN DE LA MODIFICACIÓN ---
?>
<div class="section-content overflow-y <?php echo $CURRENT_SUBSECTION === 'accessibility' ? 'active' : 'disabled'; ?>" data-section="sectionAccessibility">
    <div class="settings-container">
        <div class="profile-card">
            <div class="profile-header-container">
                <div class="profile-header">
                    <h2>Accesibilidad</h2>
                    <p>Configura las opciones de accesibilidad para adaptar la interfaz a tus necesidades.</p>
                </div>
            </div>
        </div>

        <div class="profile-card">
            <div class="profile-card-item-column" data-preference-field="theme">
                <div class="profile-card-info allow-wrap">
                    <strong>Tema</strong>
                    <span>Personaliza la apariencia de tu cuenta. Selecciona un tema o sincroniza con tu sistema.</span>
                </div>
                <div class="profile-control-group">
                    <div class="selector-input" data-action="toggleSelector">
                        <div class="selected-value">
                            <div class="selected-value-icon left">
                                <span class="material-symbols-rounded"></span>
                            </div>
                            <span class="selected-value-text"></span>
                        </div>
                        <div class="selected-value-icon">
                            <span class="material-symbols-rounded">arrow_drop_down</span>
                        </div>
                    </div>
                    <div class="module-content module-selector body-title disabled" data-module="moduleSelector">
                         <div class="menu-content">
                            <div class="menu-body overflow-y">
                                <div class="menu-list">
                                    <div class="menu-link <?php echo ($prefs['theme'] ?? 'system') === 'system' ? 'active' : ''; ?>" data-value="system">
                                        <div class="menu-link-icon"><span class="material-symbols-rounded">sync</span></div>
                                        <div class="menu-link-text"><span>Sincronizar con el sistema</span></div>
                                    </div>
                                    <div class="menu-link <?php echo ($prefs['theme'] ?? '') === 'light' ? 'active' : ''; ?>" data-value="light">
                                        <div class="menu-link-icon"><span class="material-symbols-rounded">light_mode</span></div>
                                        <div class="menu-link-text"><span>Tema claro</span></div>
                                    </div>
                                    <div class="menu-link <?php echo ($prefs['theme'] ?? '') === 'dark' ? 'active' : ''; ?>" data-value="dark">
                                        <div class="menu-link-icon"><span class="material-symbols-rounded">dark_mode</span></div>
                                        <div class="menu-link-text"><span>Tema oscuro</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="profile-card">
            <div class="profile-card-item with-divider toggle-item" data-preference-field="shortcuts_need_modifier">
                <div class="profile-card-content">
                    <div class="profile-card-info allow-wrap">
                        <strong>Los atajos necesitan un modificador</strong>
                        <span>Para crear atajos, es necesario usar la tecla modificadora Alt.</span>
                    </div>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" <?php echo !empty($prefs['shortcuts_need_modifier']) ? 'checked' : ''; ?>>
                    <span class="toggle-slider"></span>
                    <span class="material-symbols-rounded">done</span>
                </label>
            </div>
            <div class="profile-card-item toggle-item" data-preference-field="high_contrast_colors">
                <div class="profile-card-content">
                    <div class="profile-card-info allow-wrap">
                        <strong>Contraste alto de colores</strong>
                        <span>Se mantiene un mayor contraste entre el texto y el fondo, incluidos los fondos con degradados.</span>
                    </div>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" <?php echo !empty($prefs['high_contrast_colors']) ? 'checked' : ''; ?>>
                    <span class="toggle-slider"></span>
                    <span class="material-symbols-rounded">done</span>
                </label>
            </div>
        </div>
    </div>
</div>