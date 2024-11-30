function loadInstanceData(instanceName) {
    $.ajax({
        url: "../config/connection_mssql.php",
        method: "POST",
        data: { server: instanceName },
        success: function (data) {
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