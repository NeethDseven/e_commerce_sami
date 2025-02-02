import { login } from '../services/loginService.js';

document.addEventListener('DOMContentLoaded', () => {
    const validLoginBtn = document.querySelector('#valid-login-btn');
    const loginForm = document.querySelector('#login-form');
    const errorElement = document.querySelector('#errors');

    validLoginBtn.addEventListener('click', async (event) => {
<<<<<<< HEAD
        event.preventDefault();
=======
        event.preventDefault(); // Prevent default form submission
>>>>>>> origin/develop

        if (!loginForm.checkValidity()) {
            loginForm.reportValidity();
            return false;
        }

        const loginResult = await login(loginForm.elements['username'].value, loginForm.elements['password'].value);

        if (loginResult.hasOwnProperty('authentication')) {
            alert(loginResult.message);
            document.location.href = loginResult.redirect;
        } else if (loginResult.hasOwnProperty('errors')) {
            const errors = [];
            for (let i = 0; i < loginResult.errors.length; i++) {
                errors.push(`<div class="alert alert-danger" role="alert">${loginResult.errors[i]}</div>`);
            }

            errorElement.innerHTML = errors.join('');
        }
    });
});