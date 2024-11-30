function setupTheme() {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        $('body').removeClass('light-theme').addClass('dark-theme');
        $('#themeToggle').prop('checked', true);
    } else {
        $('body').addClass('light-theme');
    }
}

function setupThemeToggle() {
    $('#themeToggle').on('change', function () {
        if (this.checked) {
            $('body').removeClass('light-theme').addClass('dark-theme');
            localStorage.setItem('theme', 'dark');
        } else {
            $('body').removeClass('dark-theme').addClass('light-theme');
            localStorage.setItem('theme', 'light');
        }
    });
}