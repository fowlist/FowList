async function loadValidation() {
    // Hämta data från API:et
    const response = await fetch("api/validate.php");
    const errors = await response.json();

    // Initiera Tabulator-tabellen
    new Tabulator("#validation", {
        data: errors,
        layout: "fitColumns",
        columns: [
            { title: "Table", field: "table" },
            { title: "Message", field: "message" },
            { title: "Invalid value", field: "value" },
            { 
                title: "Row", 
                field: "row", 
                formatter: cell => JSON.stringify(cell.getValue()) 
            }
        ]
    });
}

// Kör funktionen
loadValidation();