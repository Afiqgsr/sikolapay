const passwordInput = document.getElementById('password');
const toggleButton = document.getElementById('togglePassword');
const passwordIcon = document.getElementById('passwordIcon');

if (passwordInput && toggleButton && passwordIcon) {

    toggleButton.addEventListener('click', () => {

        const isPassword = passwordInput.type === 'password';

        passwordInput.type = isPassword
            ? 'text'
            : 'password';

        passwordIcon.src = isPassword
            ? 'resource/img/icon-set=View_light.svg'
            : 'resource/img/View_hide_light.svg';
    });

}