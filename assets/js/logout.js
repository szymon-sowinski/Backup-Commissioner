$(document).ready(function () {
    $('#logoutButton').on('click', function() {
        $.ajax({
            url: '../controllers/logout.php',
            method: 'GET',
            success: function() {
                
                window.location.href = '../views/login.html';
            },
            error: function() {
                alert('Błąd podczas wylogowywania!');
            }
        });
    });
});