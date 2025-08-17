document.addEventListener('DOMContentLoaded', function () {
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    const forgotPasswordForm = document.getElementById('forgot-password-form');

    const logicUrl = window.backendConfig.baseUrl + 'config/auth_process.php';

    // --- VARIABLES GLOBALES PARA EL COOLDOWN ---
    let resendCooldownTimer = null;
    let isResendBlocked = false;

    const stageRoutes = {
        register: {
            'stage-1': 'register',
            'stage-2': 'register/aditional-data',
            'stage-3': 'register/validation-account'
        },
        'forgot-password': {
            'stage-1': 'forgot-password',
            'stage-2': 'forgot-password/verify-code',
            'stage-3': 'forgot-password/reset-password'
        }
    };

    const fetchCsrfToken = async (csrfTokenInput, form) => {
        try {
            const formData = new FormData();
            formData.append('action', 'get_csrf_token');
            const response = await fetch(logicUrl, { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success && result.csrf_token) {
                if (csrfTokenInput) csrfTokenInput.value = result.csrf_token;
            } else {
                // Intenta mostrar el error en el primer contenedor que encuentre
                const errorContainer = form.querySelector('.error-container');
                const errorText = errorContainer ? errorContainer.querySelector('.error-message') : null;
                showError('No se pudo establecer una conexión segura. Recarga la página.', errorContainer, errorText);
            }
        } catch (error) {
            const errorContainer = form.querySelector('.error-container');
            const errorText = errorContainer ? errorContainer.querySelector('.error-message') : null;
            showError('Error de conexión al inicializar. Por favor, recarga.', errorContainer, errorText);
        }
    };

    const showError = (message, errorContainer, errorText, fieldIds = []) => {
        if (errorText) errorText.textContent = message;
        if (errorContainer) {
            errorContainer.classList.remove('disabled');
            errorContainer.classList.add('active');
        }
        document.querySelectorAll('.error-border').forEach(el => el.classList.remove('error-border'));
        fieldIds.forEach(id => {
            const fieldElement = document.getElementById(id);
            if (fieldElement) {
                const elementToBorder = fieldElement.closest('.phone-group') || fieldElement;
                elementToBorder.classList.add('error-border');
            }
        });
    };

    // --- FUNCIONES PARA EL SISTEMA DE COOLDOWN ---
    const startResendCooldown = (seconds) => {
        if (resendCooldownTimer) {
            clearInterval(resendCooldownTimer);
        }

        isResendBlocked = true;
        let remainingSeconds = seconds;
        
        const resendLinks = document.querySelectorAll('a[data-action="resend-code"]');
        
        const updateResendLinks = () => {
            resendLinks.forEach(link => {
                if (remainingSeconds > 0) {
                    const minutes = Math.floor(remainingSeconds / 60);
                    const secs = remainingSeconds % 60;
                    const timeString = `${minutes}:${secs.toString().padStart(2, '0')}`;
                    link.textContent = `Reenviar código de verificación (${timeString})`;
                    link.classList.add('cooldown-active');
                } else {
                    link.textContent = 'Reenviar código de verificación';
                    link.classList.remove('cooldown-active');
                    isResendBlocked = false;
                }
            });
        };

        updateResendLinks();

        resendCooldownTimer = setInterval(() => {
            remainingSeconds--;
            updateResendLinks();
            
            if (remainingSeconds <= 0) {
                clearInterval(resendCooldownTimer);
                resendCooldownTimer = null;
                isResendBlocked = false;
            }
        }, 1000);
    };

    const stopResendCooldown = () => {
        if (resendCooldownTimer) {
            clearInterval(resendCooldownTimer);
            resendCooldownTimer = null;
        }
        isResendBlocked = false;
        
        const resendLinks = document.querySelectorAll('a[data-action="resend-code"]');
        resendLinks.forEach(link => {
            link.textContent = 'Reenviar código de verificación';
            link.classList.remove('cooldown-active');
        });
    };

    const handleFetch = async (url, formData, button) => {
        button.disabled = true;
        button.classList.add('btn-loading');
        try {
            const csrfTokenInput = document.getElementById('csrf_token');
            if (csrfTokenInput) formData.append('csrf_token', csrfTokenInput.value);
            const response = await fetch(url, { method: 'POST', body: formData });
            if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
            return await response.json();
        } catch (error) {
            return { success: false, message: 'No se pudo conectar con el servidor. Revisa tu conexión.' };
        } finally {
            button.disabled = false;
            button.classList.remove('btn-loading');
        }
    };

    const showStage = (form, stageId) => {
        form.querySelectorAll('.register-stage, .login-stage').forEach(stage => {
            stage.classList.toggle('active', stage.id === stageId);
            stage.classList.toggle('disabled', stage.id !== stageId);
        });
    };

    const navigateToStage = (form, formName, stageId) => {
        form.querySelectorAll('.error-container').forEach(container => {
            container.classList.add('disabled');
            container.classList.remove('active');
        });
        form.querySelectorAll('.error-border').forEach(el => el.classList.remove('error-border'));
        showStage(form, stageId);
        if (formName === 'forgot-password') {
            const bottomLink = document.getElementById('main-bottom-link');
            if (bottomLink) {
                bottomLink.style.display = (stageId === 'stage-1') ? 'block' : 'none';
            }
        }
        const newPath = stageRoutes[formName]?.[stageId];
        if (newPath) {
            const newUrl = window.backendConfig.baseUrl + newPath;
            if (window.location.href !== newUrl) {
                history.pushState({ formName, stageId }, '', newUrl);
            }
        }
    };

    const initializeForm = (form, formName) => {
        const path = window.location.pathname.replace(window.backendConfig.baseUrl, '').replace(/\/$/, '');
        const routes = stageRoutes[formName];
        let currentStageId = 'stage-1';
        for (const stageId in routes) {
            if (routes[stageId] === path) {
                currentStageId = stageId;
                break;
            }
        }
        showStage(form, currentStageId);
        if (formName === 'forgot-password') {
            const bottomLink = document.getElementById('main-bottom-link');
            if (bottomLink) {
                bottomLink.style.display = (currentStageId === 'stage-1') ? 'block' : 'none';
            }
        }
        const initialUrl = window.backendConfig.baseUrl + (routes[currentStageId] || '');
        history.replaceState({ formName, stageId: currentStageId }, '', initialUrl);
    };

    const validateFormStage = (stage, fieldsConfig) => {
        const errorContainer = stage.querySelector('.error-container');
        const errorText = errorContainer ? errorContainer.querySelector('.error-message') : null;
        const inputs = Array.from(stage.querySelectorAll('input[required]'));
        const emptyFields = [];
        for (const input of inputs) {
            if (!input.value.trim()) {
                emptyFields.push(input.id);
            }
        }
        if (emptyFields.length > 0) {
            const message = emptyFields.length > 1 ? 'Por favor, completa todos los campos requeridos.' : 'Por favor, completa el campo requerido.';
            showError(message, errorContainer, errorText, emptyFields);
            return false;
        }
        for (const input of inputs) {
            const value = input.value.trim();
            const fieldInfo = fieldsConfig[input.id];
            if (fieldInfo) {
                if (fieldInfo.min && value.length < fieldInfo.min) {
                    showError(`${fieldInfo.name} debe tener al menos ${fieldInfo.min} caracteres.`, errorContainer, errorText, [input.id]);
                    return false;
                }
                if (fieldInfo.max && value.length > fieldInfo.max) {
                    showError(`${fieldInfo.name} no puede tener más de ${fieldInfo.max} caracteres.`, errorContainer, errorText, [input.id]);
                    return false;
                }
                if (fieldInfo.regex && !fieldInfo.regex.test(value)) {
                    showError(fieldInfo.errorMessage, errorContainer, errorText, [input.id]);
                    return false;
                }
                if (input.id === 'confirm_password' && value !== document.getElementById('password').value) {
                    showError('Las contraseñas no coinciden.', errorContainer, errorText, ['password', 'confirm_password']);
                    return false;
                }
            }
        }
        return true;
    };

    const formatVerificationCode = (e) => {
        const input = e.target;
        let value = input.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        let formattedValue = '';
        for (let i = 0; i < value.length; i++) {
            if (i > 0 && i % 4 === 0) {
                formattedValue += '-';
            }
            formattedValue += value[i];
        }
        input.value = formattedValue;
    };

    if (loginForm) {
        const csrfTokenInput = document.getElementById('csrf_token');
        const handleLogin = async (e) => {
            e.preventDefault();
            const stage = loginForm.querySelector('.login-stage.active');
            const errorContainer = stage.querySelector('.error-container');
            const errorText = errorContainer.querySelector('.error-message');
            const fieldsConfig = {
                email: { name: 'Correo electrónico' },
                password: { name: 'Contraseña' }
            };
            if (!validateFormStage(stage, fieldsConfig)) return;
            const button = stage.querySelector('.continue-btn');
            if (button.classList.contains('btn-loading')) return;
            const formData = new FormData(loginForm);
            formData.append('action', 'login');
            const result = await handleFetch(logicUrl, formData, button);
            if (result.success && result.redirect_url) {
                window.location.href = result.redirect_url;
            } else if (result.redirect_url) {
                window.location.href = result.reason ? `${result.redirect_url}?reason=${encodeURIComponent(result.reason)}` : result.redirect_url;
            } else {
                showError(result.message || 'Ocurrió un error.', errorContainer, errorText, ['email', 'password']);
            }
        };
        const togglePassword = document.getElementById('toggle-password');
        if (togglePassword) {
            togglePassword.addEventListener('click', () => {
                const passwordInput = document.getElementById('password');
                const isPassword = passwordInput.type === 'password';
                passwordInput.type = isPassword ? 'text' : 'password';
                togglePassword.textContent = isPassword ? 'visibility_off' : 'visibility';
            });
        }
        loginForm.addEventListener('focusin', e => {
            if (e.target.matches('.input-field')) {
                const stage = e.target.closest('.login-stage');
                const errorContainer = stage.querySelector('.error-container');
                e.target.classList.remove('error-border');
                if (stage.querySelectorAll('.error-border').length === 0) errorContainer.classList.add('disabled');
            }
        });
        loginForm.addEventListener('submit', handleLogin);
        fetchCsrfToken(csrfTokenInput, loginForm);
    }

    if (registerForm) {
        const csrfTokenInput = document.getElementById('csrf_token');
        const fieldsConfig = {
            email: { name: 'Correo electrónico', regex: /^[a-zA-Z0-9._-]+@(gmail\.com|outlook\.com)$/i, errorMessage: 'Solo se permiten correos de @gmail.com o @outlook.com.' },
            password: { name: 'La contraseña', min: 8, max: 30 },
            username: { name: 'El nombre de usuario', min: 4, max: 25 },
            phone: { name: 'El número de teléfono', min: 10, max: 10 },
            verification_code: { name: 'El código de verificación', min: 14, max: 14 }
        };
        initializeForm(registerForm, 'register');
        registerForm.addEventListener('click', async function (e) {
            const target = e.target;
            const button = target.closest('button[data-action]');
            const resendLink = target.closest('a[data-action="resend-code"]');
            const currentStage = target.closest('.register-stage');
            const errorContainer = currentStage ? currentStage.querySelector('.error-container') : null;
            const errorText = errorContainer ? errorContainer.querySelector('.error-message') : null;

            if (resendLink) {
                e.preventDefault();
                
                // --- VERIFICACIÓN DE COOLDOWN EN FRONTEND ---
                if (isResendBlocked) {
                    showError('Por favor, espera antes de solicitar otro código.', errorContainer, errorText);
                    return;
                }

                const formData = new FormData();
                formData.append('action', 'resend_code');
                formData.append('email', registerForm.querySelector('#email').value);
                try {
                    const csrfToken = document.getElementById('csrf_token').value;
                    formData.append('csrf_token', csrfToken);
                    const response = await fetch(logicUrl, { method: 'POST', body: formData });
                    const result = await response.json();
                    if (result.success) {
                        // Iniciar el cooldown si el backend lo indica
                        if (result.cooldown_seconds) {
                            startResendCooldown(result.cooldown_seconds);
                        }
                    } else {
                        showError(result.message, errorContainer, errorText);
                        // Si hay cooldown en la respuesta de error, iniciarlo
                        if (result.cooldown_seconds) {
                            startResendCooldown(result.cooldown_seconds);
                        }
                    }
                } catch (err) {
                    showError('Error de conexión.', errorContainer, errorText);
                }
                return;
            }

            if (!button || button.classList.contains('btn-loading')) return;
            const action = button.getAttribute('data-action');
            if (!validateFormStage(currentStage, fieldsConfig)) return;
            const formData = new FormData(registerForm);
            if (action === 'next-stage') {
                formData.append('action', 'validate_step1');
                const result = await handleFetch(logicUrl, formData, button);
                if (result.success) navigateToStage(registerForm, 'register', 'stage-2');
                else showError(result.message || 'Error.', errorContainer, errorText, ['email']);
            } else if (action === 'submit-register') {
                formData.append('action', 'generate_code');
                const result = await handleFetch(logicUrl, formData, button);
                if (result.success) {
                    if (result.email) {
                        const emailSpan = registerForm.querySelector('#verification-email');
                        if (emailSpan) {
                            emailSpan.textContent = result.email;
                        }
                    }
                    // Iniciar el cooldown
                    if (result.cooldown_seconds) {
                        startResendCooldown(result.cooldown_seconds);
                    }
                    navigateToStage(registerForm, 'register', 'stage-3');
                } else {
                    showError(result.message || 'Error.', errorContainer, errorText);
                }
            } else if (action === 'submit-verification') {
                formData.append('action', 'verify_account');
                const result = await handleFetch(logicUrl, formData, button);
                if (result.success && result.redirect_url) {
                    stopResendCooldown(); // Limpiar el cooldown al completar la verificación
                    window.location.href = result.redirect_url;
                } else {
                    showError(result.message || 'Error.', errorContainer, errorText, ['verification_code']);
                }
            }
        });
        registerForm.addEventListener('input', e => {
            const input = e.target;
            if (input.id === 'username') input.value = input.value.replace(/[^a-zA-Z0-9_]/g, '');
            if (input.id === 'phone') input.value = input.value.replace(/[^0-9]/g, '');
            if (input.id === 'verification_code') {
                formatVerificationCode(e);
            }
        });
        registerForm.addEventListener('focusin', e => {
            if (e.target.matches('.input-field, .phone-field')) {
                const stage = e.target.closest('.register-stage');
                const errorContainer = stage.querySelector('.error-container');
                const elementToClean = e.target.closest('.phone-group') || e.target;
                elementToClean.classList.remove('error-border');
                if (stage.querySelectorAll('.error-border').length === 0) errorContainer.classList.add('disabled');
            }
        });
        const togglePassword = document.getElementById('toggle-password');
        if (togglePassword) {
            togglePassword.addEventListener('click', () => {
                const passwordInput = document.getElementById('password');
                passwordInput.type = passwordInput.type === 'password' ? 'text' : 'password';
                togglePassword.textContent = passwordInput.type === 'password' ? 'visibility' : 'visibility_off';
            });
        }
        const phoneGroup = document.getElementById('phone-group');
        if (phoneGroup) {
            const countrySelector = document.getElementById('country-selector');
            const countryDropdown = document.getElementById('country-dropdown');
            countrySelector?.addEventListener('click', () => countryDropdown.classList.toggle('disabled'));
            countryDropdown?.querySelectorAll('.menu-link').forEach(link => {
                link.addEventListener('click', () => {
                    document.getElementById('selected-code').textContent = link.dataset.code;
                    document.getElementById('country_code').value = link.dataset.code;
                    countryDropdown.classList.add('disabled');
                });
            });
            window.addEventListener('click', e => {
                if (!phoneGroup.contains(e.target)) countryDropdown?.classList.add('disabled');
            });
        }
        fetchCsrfToken(csrfTokenInput, registerForm);
    }

    if (forgotPasswordForm) {
        const csrfTokenInput = document.getElementById('csrf_token');
        const fieldsConfig = {
            email: { name: 'Correo electrónico', regex: /.+@.+\..+/, errorMessage: 'El formato del correo no es válido.' },
            verification_code: { name: 'El código', min: 14, max: 14 },
            password: { name: 'La nueva contraseña', min: 8, max: 30 },
            confirm_password: { name: 'La confirmación de contraseña' }
        };
        initializeForm(forgotPasswordForm, 'forgot-password');
        forgotPasswordForm.addEventListener('click', async function (e) {
            const target = e.target;
            const button = target.closest('button[data-action]');
            const resendLink = target.closest('a[data-action="resend-code"]');
            const currentStage = target.closest('.register-stage');
            const errorContainer = currentStage ? currentStage.querySelector('.error-container') : null;
            const errorText = errorContainer ? errorContainer.querySelector('.error-message') : null;

            if (resendLink) {
                e.preventDefault();
                
                // --- VERIFICACIÓN DE COOLDOWN EN FRONTEND ---
                if (isResendBlocked) {
                    showError('Por favor, espera antes de solicitar otro código.', errorContainer, errorText);
                    return;
                }

                const formData = new FormData();
                formData.append('action', 'resend_code');
                formData.append('email', forgotPasswordForm.querySelector('#email').value);
                try {
                    const csrfToken = document.getElementById('csrf_token').value;
                    formData.append('csrf_token', csrfToken);
                    const response = await fetch(logicUrl, { method: 'POST', body: formData });
                    const result = await response.json();
                    if (result.success) {
                        // Iniciar el cooldown si el backend lo indica
                        if (result.cooldown_seconds) {
                            startResendCooldown(result.cooldown_seconds);
                        }
                    } else {
                        showError(result.message, errorContainer, errorText, ['email']);
                        // Si hay cooldown en la respuesta de error, iniciarlo
                        if (result.cooldown_seconds) {
                            startResendCooldown(result.cooldown_seconds);
                        }
                    }
                } catch (err) {
                    showError('Error de conexión.', errorContainer, errorText);
                }
                return;
            }

            if (!button || button.classList.contains('btn-loading')) return;
            const action = button.getAttribute('data-action');
            if (!validateFormStage(currentStage, fieldsConfig)) return;
            const formData = new FormData(forgotPasswordForm);
            switch (action) {
                case 'send-code':
                    formData.append('action', 'send_code');
                    const resultSend = await handleFetch(logicUrl, formData, button);
                    if (resultSend.success) {
                        if (resultSend.email) {
                            const emailSpan = forgotPasswordForm.querySelector('#verification-email');
                            if (emailSpan) {
                                emailSpan.textContent = resultSend.email;
                            }
                        }
                        // Iniciar el cooldown
                        if (resultSend.cooldown_seconds) {
                            startResendCooldown(resultSend.cooldown_seconds);
                        }
                        navigateToStage(forgotPasswordForm, 'forgot-password', 'stage-2');
                    } else {
                        showError(resultSend.message, errorContainer, errorText, ['email']);
                    }
                    break;
                case 'verify-code':
                    formData.append('action', 'verify_code');
                    const resultVerify = await handleFetch(logicUrl, formData, button);
                    if (resultVerify.success) {
                        stopResendCooldown(); // Limpiar el cooldown al verificar exitosamente
                        navigateToStage(forgotPasswordForm, 'forgot-password', 'stage-3');
                    } else {
                        showError(resultVerify.message, errorContainer, errorText, ['verification_code']);
                    }
                    break;
                case 'reset-password':
                    formData.append('action', 'reset_password');
                    const resultReset = await handleFetch(logicUrl, formData, button);
                    if (resultReset.success) window.location.href = window.backendConfig.baseUrl + 'login';
                    else showError(resultReset.message, errorContainer, errorText, ['password', 'confirm_password']);
                    break;
            }
        });
        forgotPasswordForm.addEventListener('input', e => {
            if (e.target.id === 'verification_code') {
                formatVerificationCode(e);
            }
        });
        forgotPasswordForm.addEventListener('focusin', e => {
            if (e.target.matches('.input-field')) {
                const stage = e.target.closest('.register-stage');
                const errorContainer = stage.querySelector('.error-container');
                e.target.classList.remove('error-border');
                if (stage.querySelectorAll('.error-border').length === 0) errorContainer.classList.add('disabled');
            }
        });
        fetchCsrfToken(csrfTokenInput, forgotPasswordForm);
    }

    window.addEventListener('popstate', (event) => {
        if (event.state) {
            const { formName, stageId } = event.state;
            const form = document.getElementById(`${formName}-form`);
            if (form) {
                showStage(form, stageId);
                if (formName === 'forgot-password') {
                    const bottomLink = document.getElementById('main-bottom-link');
                    if (bottomLink) {
                        bottomLink.style.display = (stageId === 'stage-1') ? 'block' : 'none';
                    }
                }
            }
        }
    });

    // --- LIMPIAR COOLDOWN AL SALIR DE LA PÁGINA ---
    window.addEventListener('beforeunload', () => {
        stopResendCooldown();
    });
});