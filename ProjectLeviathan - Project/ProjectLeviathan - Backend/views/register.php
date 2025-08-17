<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crea una cuenta - ProjectLeviathan</title>
    <link rel="stylesheet" href="<?php echo $BASE_URL_BACKEND; ?>assets/css/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>

<body>
    <div class="page-wrapper">
        <header class="page-header"><a href="#" class="logo-link"></a></header>
        <main class="main-container">
            <section class="content-wrapper">
                <form id="register-form" method="POST" novalidate>
                    <input type="hidden" id="csrf_token" name="csrf_token" value="">
                    <div class="register-stage active" id="stage-1">
                        <h1>Crea una cuenta</h1>
                        <p class="form-subtitle">Usa tu correo y contraseña para empezar.</p>
                        <div class="input-wrapper"><input class="input-field" type="email" id="email" name="email" required placeholder=" " maxlength="126"><label class="input-label" for="email">Dirección de correo electrónico*</label></div>
                        <div class="input-wrapper"><input class="input-field" type="password" id="password" name="password" required placeholder=" " minlength="8" maxlength="30"><label class="input-label" for="password">Contraseña*</label><span class="material-symbols-rounded" id="toggle-password">visibility</span></div>
                        <button type="button" class="continue-btn" data-action="next-stage"><span>Continuar</span></button>
                        <div class="error-container disabled">
                            <span class="error-message"></span>
                        </div>
                        </div>
                    <div class="register-stage disabled" id="stage-2">
                        <h1>Completa tu perfil</h1>
                        <p class="form-subtitle">Solo necesitamos unos datos más.</p>
                        <div class="input-wrapper"><input class="input-field" type="text" id="username" name="username" required placeholder=" " minlength="4" maxlength="25"><label class="input-label" for="username">Nombre de usuario*</label></div>
                        <div class="input-wrapper">
                            <label class="input-label static" for="phone">Número de teléfono*</label>
                            <div class="phone-group" id="phone-group">
                                <div class="country-selector" id="country-selector" tabindex="0"><span class="country-code" id="selected-code">+52</span><span class="material-symbols-rounded">arrow_drop_down</span></div>
                                <input class="input-field phone-field" type="tel" id="phone" name="phone" required placeholder=" " minlength="10" maxlength="10">
                            </div>
                            <div class="module-content country-selector-module disabled body-title" id="country-dropdown">
                                <div class="menu-content">
                                    <div class="menu-body">
                                        <div class="menu-list">
                                            <div class="menu-link" data-code="+52">
                                                <div class="menu-link-icon"><span class="material-symbols-rounded">public</span></div>
                                                <div class="menu-link-text"><span>México</span></div>
                                            </div>
                                            <div class="menu-link" data-code="+1">
                                                <div class="menu-link-icon"><span class="material-symbols-rounded">public</span></div>
                                                <div class="menu-link-text"><span>Estados Unidos</span></div>
                                            </div>
                                            <div class="menu-link" data-code="+1">
                                                <div class="menu-link-icon"><span class="material-symbols-rounded">public</span></div>
                                                <div class="menu-link-text"><span>Canadá</span></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" id="country_code" name="country_code" value="+52">
                        </div>
                        <button type="button" class="continue-btn" data-action="submit-register"><span>Finalizar registro</span></button>
                        <div class="error-container disabled">
                            <span class="error-message"></span>
                        </div>
                        </div>

                    <?php
                    // --- Incluir y configurar el parcial de verificación ---
                    $verificationStageId = 'stage-3';
                    $verificationTitle = 'Último paso';
                    $verificationButtonAction = 'submit-verification';
                    $verificationButtonText = 'Verificar cuenta';
                    include 'partials/verification-stage.php';
                    ?>

                    </form>
                <p class="other-page-link">¿Ya tienes una cuenta? <a href="<?php echo $BASE_URL_BACKEND; ?>login">Inicia sesión</a></p>
            </section>
        </main>
        <footer class="page-footer"><a href="#">Términos de uso</a><span class="separator">|</span><a href="#">Política de privacidad</a></footer>
    </div>
    <script>
        window.backendConfig = {
            baseUrl: '<?php echo $BASE_URL_BACKEND; ?>'
        };
    </script>
    <script src="<?php echo $BASE_URL_BACKEND; ?>assets/js/auth.js"></script>
</body>

</html>