// Aula Virtual Académica — auth.js
// Validación en el cliente para los formularios de autenticación:
//   - Registro público (#register-form)
//   - Cambio de contraseña (#change-password-form)
// La validación del servidor sigue siendo la autoridad final.
(function () {
    'use strict';

    function setError(input, message) {
        input.classList.add('is-invalid');
        var feedback = input.parentNode.querySelector('.invalid-feedback');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            input.parentNode.appendChild(feedback);
        }
        feedback.textContent = message;
    }

    function clearError(input) {
        input.classList.remove('is-invalid');
        var feedback = input.parentNode.querySelector('.invalid-feedback');
        if (feedback) {
            feedback.remove();
        }
    }

    function validEmail(value) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
    }

    // ---- Registro público ----
    var registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', function (ev) {
            var firstName = registerForm.querySelector('[name="first_name"]');
            var lastName = registerForm.querySelector('[name="last_name"]');
            var email = registerForm.querySelector('[name="email"]');

            var valid = true;
            [firstName, lastName, email].forEach(clearError);

            if (!firstName.value.trim()) {
                setError(firstName, 'Ingresa tu nombre.');
                valid = false;
            }
            if (!lastName.value.trim()) {
                setError(lastName, 'Ingresa tu apellido.');
                valid = false;
            }
            if (!validEmail(email.value.trim())) {
                setError(email, 'Ingresa un correo electrónico válido.');
                valid = false;
            }

            if (!valid) {
                ev.preventDefault();
            }
        });
    }

    // ---- Cambio de contraseña ----
    var passwordForm = document.getElementById('change-password-form');
    if (passwordForm) {
        var current = passwordForm.querySelector('[name="current_password"]');
        var newPass = passwordForm.querySelector('[name="new_password"]');
        var confirm = passwordForm.querySelector('[name="confirm_password"]');

        passwordForm.addEventListener('submit', function (ev) {
            [current, newPass, confirm].forEach(clearError);
            var valid = true;

            if (!current.value) {
                setError(current, 'Ingresa tu contraseña actual.');
                valid = false;
            }
            if (newPass.value.length < 8) {
                setError(newPass, 'La contraseña debe tener al menos 8 caracteres.');
                valid = false;
            }
            if (confirm.value !== newPass.value) {
                setError(confirm, 'Las contraseñas no coinciden.');
                valid = false;
            }

            if (!valid) {
                ev.preventDefault();
            }
        });
    }
})();
