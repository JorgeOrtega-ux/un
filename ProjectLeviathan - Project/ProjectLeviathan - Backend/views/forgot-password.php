<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - ProjectLeviathan</title>
    <link rel="stylesheet" href="<?php echo $BASE_URL_BACKEND; ?>assets/css/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>

<body>
    <div class="page-wrapper">
        <header class="page-header"><a href="../" class="logo-link"></a></header>
        <main class="main-container">
            <section class="content-wrapper">
                <form id="forgot-password-form" method="POST" novalidate>
                    <input type="hidden" id="csrf_token" name="csrf_token" value="">
                    <div class="register-stage active" id="stage-1">
                        <h1>Recuperar contraseña</h1>
                        <p class="form-subtitle">Ingresa tu correo electrónico para buscar tu cuenta.</p>
                        <div class="input-wrapper">
                            <input class="input-field" type="email" id="email" name="email" required placeholder=" " maxlength="126">
                            <label class="input-label" for="email">Dirección de correo electrónico*</label>
                        </div>
                        <button type="button" class="continue-btn" data-action="send-code"><span>Continuar</span></button>
                        <div class="error-container disabled">
                            <span class="error-message"></span>
                        </div>
                        </div>

                    <?php
                    // --- Incluir y configurar el parcial de verificación ---
                    $verificationStageId = 'stage-2';
                    include 'partials/verification-stage.php';
                    ?>

                    <div class="register-stage disabled" id="stage-3">
                        <h1>Crea una nueva contraseña</h1>
                        <p class="form-subtitle">Asegúrate de que tu nueva contraseña sea segura.</p>
                        <div class="input-wrapper">
                            <input class="input-field" type="password" id="password" name="password" required placeholder=" " minlength="8" maxlength="30">
                            <label class="input-label" for="password">Nueva contraseña*</label>
                        </div>
                        <div class="input-wrapper">
                            <input class="input-field" type="password" id="confirm_password" name="confirm_password" required placeholder=" " minlength="8" maxlength="30">
                            <label class="input-label" for="confirm_password">Confirmar nueva contraseña*</label>
                        </div>
                        <button type="button" class="continue-btn" data-action="reset-password"><span>Restablecer contraseña</span></button>
                        <div class="error-container disabled">
                            <span class="error-message"></span>
                        </div>
                        </div>
                </form>
                <p class="other-page-link" id="main-bottom-link"><a href="<?php echo $BASE_URL_BACKEND; ?>login">Volver a Iniciar sesión</a></p>
            </section>
        </main>
        <footer class="page-footer">
            <a href="#">Términos de uso</a><span class="separator">|</span><a href="#">Política de privacidad</a>
        </footer>
    </div>
    <script>
        window.backendConfig = {
            baseUrl: '<?php echo $BASE_URL_BACKEND; ?>'
        };
    </script>
    <script src="<?php echo $BASE_URL_BACKEND; ?>assets/js/auth.js"></script>
</body>

</html>