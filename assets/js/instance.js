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