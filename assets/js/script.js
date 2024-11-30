$(document).ready(function () {
    let instanceName = "";

    init();

    function init() {
        loadInstanceNames();
        setupTheme();
        setupOkButton();
        setupThemeToggle();
    }

    function showErrorMessage(message) {
        $('#errorMessage').text(message); 
        $('#errorContainer').fadeIn();
        setTimeout(function() {
            $('#errorContainer').fadeOut();
        }, 5000);
    }

    $('#closeError').on('click', function () {
        $('#errorContainer').fadeOut(); 
    });

    function loadInstanceNames() {
        $.ajax({
            url: "../config/connection_mysql.php",
            method: "GET",
            dataType: "json",
            success: function (data) {
                if (data.length > 0) {
                    populateInstanceDropdown(data);
                    instanceName = data[0];
                    loadInstanceData(instanceName);
                } else {
                    showErrorMessage("Brak dostępnych instancji dla tego użytkownika.");
                }
            },
            error: function (xhr, status, error) {
                showErrorMessage("Błąd ładowania instancji: " + error);
            }
        });
    }

    function populateInstanceDropdown(data) {
        const instanceSource = data.map(name => ({ label: name, value: name }));
        $("#instanceDisplay").jqxComboBox({
            width: '200px',
            height: '25px',
            source: instanceSource,
            displayMember: 'label',
            valueMember: 'value',
            selectedIndex: 0,
            theme: 'darkblue'
        }).on('select', handleInstanceSelection);
    }

    function handleInstanceSelection(event) {
        if (event.args) {
            instanceName = event.args.item.value;
            loadInstanceData(instanceName);
        }
    }

    function loadInstanceData(instanceName) {
        $.ajax({
            url: "../config/connection_mssql.php",
            method: "POST",
            data: { server: instanceName },
            success: function (data) {
                console.log('Response from connection_mssql.php:', data);
                populateDataTable(data);
            },
            error: function (xhr, status, error) {
                showErrorMessage("Błąd ładowania danych: " + error);
            }
        });
    }

    function populateDataTable(data) {
        const source = {
            datatype: 'json',
            datafields: [
                { name: 'Lp', type: 'int' },
                { name: 'Nazwa', type: 'string' },
                { name: 'Rozmiar', type: 'string' },
                { name: 'bool', type: 'bool' },
                { name: 'instance', type: 'string' }
            ],
            localdata: data.databases
        };

        const dataAdapter = new $.jqx.dataAdapter(source);
        $("#dtable").jqxDataTable({
            source: dataAdapter,
            width: '570px',
            altRows: true,
            pageable: true,
            sortable: true,
            theme: 'darkblue',
            columns: [
                { text: 'Lp.', dataField: 'Lp', width: 100, align: 'center', cellsAlign: 'center' },
                { text: 'Nazwa', dataField: 'Nazwa', width: 200, align: 'center', cellsAlign: 'center' },
                { text: 'Rozmiar', dataField: 'Rozmiar', width: 180, align: 'center', cellsAlign: 'center' },
                {
                    text: 'Wybrane', dataField: 'bool', width: 90, align: 'center', cellsAlign: 'center',
                    cellsRenderer: function (row, column, value, rowData) {
                        return `<input type="checkbox" class="checkbox" ${value ? 'checked' : ''} />
                                <input type="hidden" data-field="Nazwa" value="${rowData.Nazwa}" />`;
                    }
                }
            ]
        });
    }

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
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

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
        console.log("Sending data:", { currentDateTime, databases, instanceName, email, password });

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
                console.log("Response from save_order.php: ", response);
                $("#result").html('<h3>Zlecenie zostało zapisane pomyślnie!</h3>');
                $("#emailInput").val('');
                $("#passwordInput").val('');
                $(".checkbox").prop('checked', false);
            },
            error: function (xhr, status, error) {
                console.error("Error saving order: ", error);
                showErrorMessage("Błąd zapisu zlecenia: " + error);
            }
        });
    }

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