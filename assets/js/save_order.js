function setupOkButton() {
    $("#okButton").jqxButton({ width: 'match-content', height: '35px', theme: 'darkblue' });
    $("#okButton").on("click", handleOkButtonClick);
}

function handleOkButtonClick() {
    const email = $("#emailInput").val().trim();
    const password = $("#passwordInput").val().trim();

    if (!validateForm(email, password)) return;

    const selectedDatabases = getSelectedDatabases();
    if (selectedDatabases.length > 0) {
        saveOrder(selectedDatabases, email, password);
    } else {
        showErrorMessage("Proszę wybrać przynajmniej jedną bazę danych.");
    }
}

function validateForm(email, password) {
    const emailPattern = /^[^\s@]+@[^\s@]+$/;

    if (!email || !emailPattern.test(email)) {
        showErrorMessage("Proszę wprowadzić poprawny adres e-mail.");
        return false;
    }

    if (!password) {
        showErrorMessage("Proszę wprowadzić hasło.");
        return false;
    }

    return true;
}


function getSelectedDatabases() {
    let selectedDatabases = [];
    $(".checkbox:checked").each(function () {
        const databaseName = $(this).closest('tr').find('input[data-field="Nazwa"]').val();
        selectedDatabases.push(databaseName);
    });
    return selectedDatabases;
}

function saveOrder(selectedDatabases, email, password) {
    const currentDateTime = new Date().toLocaleString();
    const databases = selectedDatabases.join(", ");

    $.ajax({
        type: "POST",
        url: "../controllers/save_order.php",
        data: {
            data_zlec: currentDateTime,
            nazwy_baz: databases,
            instancja_sql: instanceName,
            zrealizowane: 0,
            email: email,
            password: password
        },
        dataType: "json",
        success: function (response) {
            showSuccessMessage("Zlecenie zostało zapisane pomyślnie!");
            $("#emailInput").val('');
            $("#passwordInput").val('');
            $(".checkbox").prop('checked', false);
        },
        error: function (xhr, status, error) {
            showErrorMessage("Błąd zapisu zlecenia: " + error);
        }
    });
}
