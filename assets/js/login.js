$(document).ready(function () {
    $('#loginForm').on('submit', function (e) {
        e.preventDefault();

        const loginData = {
            login: $('#loginInput').val(),
            password: $('#userPasswordInput').val()
        };

        $.ajax({
            url: "../controllers/login.php",
            method: "POST",
            data: loginData,
            dataType: "json",
            success: function (response) {
                if (response.success) {
                    window.location.href = "../public/index.php";
                } else {
                    showErrorMessage(response.message);
                }
            },
            error: function () {
                showErrorMessage("Błąd połączenia z serwerem LDAP. Możliwy problem z serwerem lub jego konfiguracją.");
            }
        });
    });
});