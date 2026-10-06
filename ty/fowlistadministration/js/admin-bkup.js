let currentTable = null;
let table = null;
let currentTableMeta = null;

async function api(url, options = {}) {
  const response = await fetch(url, {
    headers: {
      "Content-Type": "application/json",
    },
    ...options,
  });

  const text = await response.text();

  try {
    return JSON.parse(text);
  } catch (err) {
    console.error("Invalid JSON from API:", url, "response:", text);
    throw new Error(`Invalid JSON response from ${url}: ${err.message}`);
  }
}

async function loadTables() {
  const tables = await api("api/table.php?action=data&table=admin_tables");

  const container = document.getElementById("table-list");

  container.innerHTML = "";

  tables
    .filter((t) => t.hidden != 1)
    .filter((t) => t.table_type === "editable")
    .forEach((t) => {
      const button = document.createElement("button");

      button.className = `table-button type-${t.table_type}`;

      button.innerHTML = `
                <div class="table-name">${t.display_name}</div>
            `;

      button.onclick = () => {
        loadTable(t);
      };

      container.appendChild(button);
    });
}

async function loadTable(tableMeta) {
  currentTableMeta = tableMeta;
  currentTable = tableMeta.table_name;

  document.getElementById("current-table").innerText = tableMeta.display_name;

  const schema = await api(`api/table.php?action=schema&table=${currentTable}`);

  const data = await api(`api/table.php?action=data&table=${currentTable}`);

  if (table) {
    table.destroy();
  }
console.log("Building columns with schema:", schema);
  const columns = buildColumns(schema.columns);

  const rowContextMenuActions = [
    {
      label: "Duplicate Row",
      action: function (e, row) {
        const rowData = row.getData();
        const newRowData = { ...rowData };
        
        // Remove primary key to avoid conflicts
        const pk = currentTableMeta.primaryKey || "id";
        newRowData[pk] = newRowData[pk] ? `${newRowData[pk]}_copy` : ""; // Append _copy to primary key value or set to empty if not present

        table.addRow(newRowData, true);
      }
    },
    {
      label: "🗑 Delete Row",
      action: function (e, row) {
        const cell = row.getCells()[0]; // Get the first cell of the row to pass to deleteRow
        deleteRow(e, cell);
      }
    }
    ];

  table = new Tabulator("#table", {
    data: data,
/*
    selectableRange:1, //allow only one range at a time
    selectableRangeColumns:true,
    selectableRangeRows:true,
    selectableRangeClearCells:true,
    
*/
    editTriggerEvent:"dblclick",

    columns: columns,

    pagination: true,

    paginationSize: 500,
    
    paginationSizeSelector: [100, 500, 1000, 5000],

    movableColumns: true,

    resizableRows: true,

    reactiveData: true,

    history: true,

        //configure clipboard to allow copy and paste of range format data
    clipboard:true,
    clipboardCopyStyled:false,
    clipboardCopyConfig:{
        rowHeaders:false,
        columnHeaders:false,
    },
    clipboardCopyRowRange:"range",
    clipboardPasteParser:"range",
    clipboardPasteAction:"range",

    height: "85vh",

    placeholder: "No data found",

    index: schema.primaryKey || "id",
    rowContextMenu: rowContextMenuActions,
    
  });
  setupTableEvents();

  updateToolbar();
}

function buildColumns(schemaColumns) {
  const columns = [];

  // Define the context menu items for the header
  const headerMenuActions = [
      {
          label: function(component) {
              const definition = component.getDefinition ? component.getDefinition() : {};
              return definition.frozen ? "❄️ Unfreeze Column" : "❄️ Freeze Column";
          },
          action: function(e, component) {
              const column = component.getColumn ? component.getColumn() : component;
              const currentDefinition = column.getDefinition ? column.getDefinition() : {};
              const isFrozen = !currentDefinition.frozen;

              if (column.updateDefinition) {
                  column.updateDefinition({ frozen: isFrozen });
              } else if (column.update) {
                  column.update({ frozen: isFrozen });
              }
          }
      }
  ];

  const firstCol = {
    formatter: "rownum",
    hozAlign: "center",
    width: 50,
    headerSort: false,
    frozen: true,
  };

  columns.push(firstCol);

  schemaColumns.forEach((col, index) => {
    const column = {
      title: col.title,

      field: col.field,
      visible: col.visible !== false,
      width: col.width || 180,
      sorter: col.sorter || "string",
      headerFilter: "input",
      headerMenu: headerMenuActions,
      tooltip: col.title,

    };

    if (currentTableMeta.table_type === "generated") {
      column.editor = false;
    } else {
      column.editor = col.editor || "input";
    }

    // input
    if (col.editor === "input") {
      column.editor = "list";

      column.editorParams = {

        valuesLookup:"active", //get the values from the currently active rows in this column
        sort: "asc",   
        history: true,
        clearable: true,
        autocomplete: true,
        listOnEmpty: true,
        freetext:true,
        formatter: "textarea",
        variableHeight: true,
        itemFormatter: function(label, value, item, element) {
            // Ersätter \n med <br> så att webbläsaren radbryter texten
            return label.replace(/\n/g, "<br>");
        }
      };
    }

    // textarea
    if (col.editor === "textarea") {
      column.editor = "textarea";
      column.editorParams = {
        history: true,

        formatter: "textarea",
        variableHeight: true,
        itemFormatter: function(label, value, item, element) {
            // Ersätter \n med <br> så att webbläsaren radbryter texten
            return label.replace(/\n/g, "<br>");
        }
      };

    }
    // number
    if (col.editor === "number") {
      column.editor = "number";
      column.editorParams = {
        selectContents: true,
      };
      column.cellClick = (e, cell) => cell.edit();
    }

    // checkbox
    if (col.editor === "tickCross") {
      column.formatter = "tickCross";
      column.editorParams = {
        selectContents: true,
      };
      column.hozAlign = "center";
    }

    // relation list
    if (col.editor === "list") {
      const isMulti = col.multiple || false;

      column.editor = "list";

      column.editorParams = {
        values: col.values || [],

        clearable: true,

        multiselect: isMulti,

        autocomplete: !isMulti,

        allowEmpty: !isMulti,

        listOnEmpty: true,
        freetext:true,
      };

      if (isMulti) {


        column.mutator = function (value) {
          if (!value) return [];

          return value.split(col.separator || "|").filter((v) => v !== "");
        };

        column.mutatorEdit = function (value) {
          if (!Array.isArray(value)) return "";

          return value.join(col.separator || "|");
        };
      }

      column.formatter = function (cell) {
        let value = cell.getValue();

        if (Array.isArray(value)) return value.join(", ");

        return value || "";
      };
    }
    // readonly
    if (col.readonly) {
      column.editor = false;

      column.cssClass = "readonly-column";
    }

    columns.push(column);
  });

  // delete button
  
  if (currentTableMeta.table_type === "editable") {
    columns.push({
      title: "",

      formatter: function () {
        return "🗑";
      },
      width: 60,
      hozAlign: "center",
      cellClick: deleteRow,
    });
  }
    

  return columns;
}

function setupTableEvents() {
  if (currentTableMeta.table_type !== "editable") {
    return;
  }

  table.on("cellEdited", async function (cell) {
    const rowData = cell.getRow().getData();

    const result = await api(
      `api/table.php?action=save&table=${currentTable}`,
      {
        method: "POST",
        body: JSON.stringify(rowData),
      },
    );

    if (!result.success) {
      alert(result.error || "Save failed");
    } else {
      let deps = await fetch(
        "api/rebuildDependencies.php",

        {
          method: "POST",

          body: new URLSearchParams({
            table: currentTable,
          }),
        },
      ).then((r) => r.json());

      for (let d of deps) {
        await fetch(
          "api/rebuild.php",

          {
            method: "POST",

            body: new URLSearchParams({
              table: d.target_table,
            }),
          },
        );
      }
    }
  });


table.on("clipboardPasted", async function(clipboard, rowData, rows) {
    // rowData is an array of all rows affected by the paste

    
    console.log(`Data pasted. Updating ${rowData.length} rows...`, rows);
    // 1. Hämta namnet på primärnyckeln från din konfiguration
    const schema = await api(`api/table.php?action=schema&table=${currentTable}`);

    const primaryKeyName = schema.primaryKey || "id";
    
    const rowDatafromRows = [];

    rows.forEach(row => {
        const data = row.getData();
        if (!data[primaryKeyName]) {
            console.warn("Row missing primary key, skipping:", data);
        }
                const cleanData = structuredClone(data); 
        rowDatafromRows.push(cleanData);

    });

    if (!rowDatafromRows) {
        alert("Kunde inte identifiera det markerade området.");
        return;
    }

    try {
        const result = await api(
            `api/table.php?action=rangeupdate&table=${currentTable}`,
            {
                method: "POST",
                body: JSON.stringify(rowDatafromRows),
            }
        );

        if (result.success) {
            console.log(`Range update complete: ${result.updated} updated, ${result.failed} failed`);
            if (result.errors && result.errors.length > 0) {
                console.error("Update errors:", result.errors);
                alert(`Pasted data saved! (${result.updated} updated, ${result.failed} failed)\n\nErrors:\n${result.errors.join('\n')}`);
            }
        } else {
            alert(result.error || "Range update failed");
        }
    } catch (error) {
        console.error("Error during range update:", error);
        alert("Failed to save pasted data: " + error.message);
    }
});

}

async function deleteRow(e, cell) {
  if (currentTableMeta.table_type !== "editable") {
    console.warn("Table is not editable, cannot delete row");
    return;
  }

  const row = cell.getRow();

  const rowData = row.getData();

  if (!confirm("Delete row?")) {
    return;
  }
  const schema = await api(`api/table.php?action=schema&table=${currentTable}`);

  const pk = schema.primaryKey || "id";

  console.log("Deleting row with PK", pk, "value", rowData[pk]);
  const result = await api(
    `api/table.php?action=delete&table=${currentTable}`,
    {
      method: "POST",
      body: JSON.stringify({
        [pk]: rowData[pk],
      }),
    },
  );

  if (result.success) {
    row.delete();
  } else {
    alert(result.error || "Delete failed");
  }
}

async function addRow() {
  if (!currentTable) {
    return;
  }

  if (currentTableMeta.table_type !== "editable") {
    return;
  }

  const schema = await api(`api/table.php?action=schema&table=${currentTable}`);

  const pk = schema.primaryKey || "id";
  const row = {};

  schema.columns.forEach((col) => {
    // Skip primary key - user must provide it
    if (col.field !== pk) {
      row[col.field] = "";
    }
  });

  // Prompt user for primary key value
  const pkValue = prompt(`Enter ${pk}:`);
  if (!pkValue) {
    return; // User cancelled
  }

  row[pk] = pkValue;

  const result = await api(`api/table.php?action=save&table=${currentTable}`, {
    method: "POST",
    body: JSON.stringify(row),
  });

  if (result.success) {
    table.addRow(row, true);
  } else {
    alert(result.error || "Failed to add row");
  }
}

function updateToolbar() {
  const addButton = document.getElementById("add-row");

  const rebuildButton = document.getElementById("rebuild-table");

  if (currentTableMeta.table_type === "editable") {
    addButton.style.display = "inline-block";
  } else {
    addButton.style.display = "none";
  }

  if (
    currentTableMeta.table_type === "generated" &&
    currentTableMeta.rebuild_handler
  ) {
    rebuildButton.style.display = "inline-block";
  } else {
    rebuildButton.style.display = "none";
  }
}

async function rebuildTable() {
  if (!currentTableMeta.rebuild_handler) {
    return;
  }

  const result = await api(`api/rebuild.php?table=${currentTable}`);

  if (result.success) {
    alert("Rebuild complete");

    loadTable(currentTableMeta);
  } else {
    alert(result.error || "Rebuild failed");
  }
}

async function syncSchema()  {

    const res = await fetch(
        "api/syncSchema.php"
    );

    const data = await res.json();

    alert(
        `Added ${data.tables} tables and ${data.columns} columns`
    );
}

function setupSearch() {
  document.getElementById("search").addEventListener("keyup", function () {
    if (!table) {
      return;
    }

    const value = this.value;

    if (value === "") {
      table.clearFilter();

      return;
    }

    const filters = [];

    table.getColumns().forEach((col) => {
      const field = col.getField();

      if (!field) {
        return;
      }

      filters.push({
        field: field,
        type: "like",
        value: value,
      });
    });

    table.setFilter([filters]);
  });
}

function setupToolbar() {
  document.getElementById("add-row").addEventListener("click", addRow);

  document
    .getElementById("rebuild-table")
    .addEventListener("click", rebuildTable);
  document
    .getElementById("syncMetadata")
    .addEventListener("click", syncSchema);
}

// Theme toggle: inject minimal CSS, apply theme and persist choice
function applyTheme(theme) {
  if (theme === "dark") {
    document.documentElement.classList.add("dark-mode");
  } else {
    document.documentElement.classList.remove("dark-mode");
  }
}

function setupThemeToggle() {
  const container = document.getElementById("theme-switch-container");
  const input = container ? container.querySelector("#theme-toggle") : null;
  const text = container ? container.querySelector(".theme-label") : null;
  const lightLink = document.getElementById("tabulator-light-css");
  const darkLink = document.getElementById("tabulator-dark-css");

  if (!input || !text || !lightLink || !darkLink) {
    return;
  }

  const saved = localStorage.getItem("theme") || (window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light");
  applyTheme(saved);
  input.checked = saved === "dark";
  text.textContent = saved === "dark" ? "Dark" : "Light";
  lightLink.disabled = saved === "dark";
  darkLink.disabled = saved !== "dark";

  input.addEventListener("change", () => {
    const next = input.checked ? "dark" : "light";
    applyTheme(next);
    text.textContent = next === "dark" ? "Dark" : "Light";
    lightLink.disabled = next === "dark";
    darkLink.disabled = next !== "dark";
    try {
      localStorage.setItem("theme", next);
    } catch (e) {
      /* ignore */
    }
  });
}

function init() {
  setupToolbar();

  setupSearch();
  setupThemeToggle();

  loadTables();
}

init();
