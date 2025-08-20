<?php
// --- Valores por defecto para asegurar que las variables existan ---
$verificationStageId = $verificationStageId ?? 'stage-verification';
$verificationTitle = $verificationTitle ?? 'Ingresa el código';
$verificationText = $verificationText ?? 'Te hemos enviado un código a tu correo electrónico';
$verificationButtonAction = $verificationButtonAction ?? 'verify-code';
$verificationButtonText = $verificationButtonText ?? 'Continuar';
?>

<div class="register-stage disabled" id="<?php echo htmlspecialchars($verificationStageId); ?>">
    <h1><?php echo htmlspecialchars($verificationTitle); ?></h1>
    <p class="verification-text">
        <?php echo htmlspecialchars($verificationText); ?> <span id="verification-email"></span>. Ingresa el código aquí para completar la verificación de tu cuenta.
    </p>
    <div class="input-wrapper">
        <input class="input-field" type="text" id="verification_code" name="verification_code" required placeholder=" " minlength="14" maxlength="14">
        <label class="input-label" for="verification_code">Código de verificación*</label>
    </div>

    <button type="button" class="continue-btn" data-action="<?php echo htmlspecialchars($verificationButtonAction); ?>">
        <span><?php echo htmlspecialchars($verificationButtonText); ?></span>
    </button>

    <div class="error-container disabled">
        <span class="error-message"></span>
    </div>
    <p class="other-page-link"><a href="#" data-action="resend-code">Reenviar código de verificación</a></p>
</div>