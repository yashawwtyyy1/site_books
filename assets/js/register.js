$(function () {
    const $form = $('#register-form');
    const $username = $('#username');
    const $firstName = $('#first_name');
    const $lastName = $('#last_name');
    const $email = $('#email');
    const $password = $('#password');
    const $passwordConfirm = $('#password_confirmation');
    const $agreement = $('#agreement');
    const $feedback = $('#username-feedback');

    function setError($input, message) {
        const $group = $input.closest('.bg-form');
        $group.toggleClass('is-invalid', !!message);
        const $error = $input.siblings('.invalid-feedback');
        if ($error.length) {
            $error.text(message || '');
        }
        if (message) {
            $input.addClass('is-invalid');
        } else {
            $input.removeClass('is-invalid');
        }
    }

    function validateName($input) {
        const value = $input.val().trim();
        if (!value) {
            setError($input, 'Обязательное поле.');
            return false;
        }
        if (!/^[\p{L}\-\s]+$/u.test(value)) {
            setError($input, 'Допустимы только буквы, дефисы и пробелы.');
            return false;
        }
        setError($input, '');
        return true;
    }

    function validateUsername() {
        const value = $username.val().trim();
        if (!value) {
            setError($username, 'Введите логин.');
            $feedback.text('');
            return false;
        }
        if (!/^[A-Za-z0-9_]+$/.test(value)) {
            setError($username, 'Только латиница, цифры и _.');
            $feedback.text('');
            return false;
        }
        setError($username, '');
        $.getJSON('/ajax/check_username.php', { username: value }, function (data) {
            if (data.exists) {
                setError($username, 'Такой логин уже занят.');
                $feedback.text('Логин недоступен.');
            } else {
                $feedback.text('Логин свободен.');
                setError($username, '');
            }
        });
        return true;
    }

    function validateEmail() {
        const value = $email.val().trim();
        if (!value) {
            setError($email, 'Введите email.');
            return false;
        }
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            setError($email, 'Введите корректный email.');
            return false;
        }
        setError($email, '');
        return true;
    }

    function validatePassword() {
        const value = $password.val();
        if (!value) {
            setError($password, 'Введите пароль.');
            return false;
        }
        if (value.length < 6) {
            setError($password, 'Не менее 6 символов.');
            return false;
        }
        setError($password, '');
        return true;
    }

    function validatePasswordConfirmation() {
        const value = $passwordConfirm.val();
        if (!value) {
            setError($passwordConfirm, 'Повторите пароль.');
            return false;
        }
        if (value !== $password.val()) {
            setError($passwordConfirm, 'Пароли должны совпадать.');
            return false;
        }
        setError($passwordConfirm, '');
        return true;
    }

    function validateAgreement() {
        if (!$agreement.is(':checked')) {
            $agreement.addClass('is-invalid');
            return false;
        }
        $agreement.removeClass('is-invalid');
        return true;
    }

    $firstName.on('input blur', function () { validateName($firstName); });
    $lastName.on('input blur', function () { validateName($lastName); });
    $username.on('blur', validateUsername);
    $email.on('input blur', validateEmail);
    $password.on('input blur', function () {
        validatePassword();
        validatePasswordConfirmation();
    });
    $passwordConfirm.on('input blur', validatePasswordConfirmation);
    $agreement.on('change', validateAgreement);

    $form.on('submit', function (event) {
        const valid = [
            validateName($firstName),
            validateName($lastName),
            validateUsername(),
            validateEmail(),
            validatePassword(),
            validatePasswordConfirmation(),
            validateAgreement()
        ].every(Boolean);

        if (!valid) {
            event.preventDefault();
        }
    });
});
