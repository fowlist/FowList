let currentDatabase = ""; // Sätts när användaren väljer period
let currentTable = null;
let table = null;
let currentTableMeta = null;
let currentSchema = null;
let currentFieldNames = [];
let isProgrammaticDelete = false;
let isAutoIdUpdate = false;
let changedRowsQueue = new Set();
let saveTimeout = null;
let isUpdatingLiveValues = false; // Hindrar oändliga loopar
let activeSuggestionIndex = -1;


async function api(url, options = {}) {
  // Lägg automatiskt till den aktiva databasen i URL:en om parametern saknas
  // Om vi har en aktiv databas vald, och URL:en inte redan innehåller databas-parametern
  if (currentDatabase && !url.includes('db=')) {
      // Lägg till parametern snyggt beroende på om det redan finns ett frågetecken i URL:en
      url += url.includes('?') ? `&db=${encodeURIComponent(currentDatabase)}` : `?db=${encodeURIComponent(currentDatabase)}`;
  }
  const response = await fetch(url, {
    headers: {
      "Content-Type": "application/json",
    },
    ...options,
  });

  // --- LÖSNING: HANTERA UTGÅNGEN SESSION / INLOGGNINGSKRAV ---
  // Om servern svarar med 401 Unauthorized, betyder det att sessionen har gått ut 
  // eller att användaren inte är inloggad. Vi skickar dem till login.php direkt.
  if (response.status === 401) {
    console.warn("Session has terminated. sending to login...");
    window.location.href = 'login.php';
    // Returnera ett löfte som aldrig uppfylls (återställs av omdirigeringen) för att stoppa resten av koden
    return new Promise(() => {}); 
  }

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
  console.log(tables);
  tables
    .filter((t) => t.hidden != 1)
    .filter((t) => t.table_type === "editable")
    .sort((a, b) => parseInt(a.default_sort || 0) - parseInt(b.default_sort || 0))
    .forEach((t) => {
      const button = document.createElement("button");
      button.className = `table-button type-${t.table_type}`;
      button.innerHTML = `<div class="table-name">${t.icon} ${t.display_name}</div>`;

      button.onclick = () => {
        loadTable(t);
      };

      container.appendChild(button);
    });

  // --- ADDED: AUTO-OPEN LAST ACTIVE TABLE ON STARTUP ---
  // Look into memory to check if the user worked on a table during their last session
  const savedTableMeta = localStorage.getItem('jss_active_table_meta');
  if (savedTableMeta) {
    try {
      const lastTable = JSON.parse(savedTableMeta);
      console.log("Session memory found. Auto-opening table:", lastTable.table_name);
      
      // Execute your existing full table initialization sequence
      loadTable(lastTable);
    } catch (e) {
      console.error("Failed to parse startup table session meta:", e);
    }
  }
}

function rowToObject(row) {
  if (!row) {
    return null;
  }

  if (Array.isArray(row)) {
    return currentFieldNames.reduce((obj, field, index) => {
      obj[field] = row[index] != null ? row[index] : "";
      return obj;
    }, {});
  }

  return row;
}

function objectToRow(row) {
  return currentFieldNames.map((field) => row[field] != null ? row[field] : "");
}

function buildSpreadsheetColumns(schemaColumns) {
  const columns = schemaColumns.map((col) => {
    const column = {
      name: col.field,
      title: col.title || col.field,
      width: col.visible ? (col.width || 180) :  5,

      align: col.align || "left",
      readOnly: currentTableMeta.table_type === "generated" || col.readonly,
      wordWrap: col.editor === "textarea" || col.editor === "input",
      type: "text",
      sortOnClick: true,  
    };

    switch (col.editor) {
      case "number":
        column.type = "number";
        break;
      case "tickCross":
        column.type = "checkbox";
        break;
      case "textarea":
        column.type = "textarea";
        break;
      case "list":
        column.type = "dropdown";
        column.autocomplete = true;
        column.source = col.values || [];
        column.parent_column = col.parent_column || null;
        column.relation_parent_column = col.relation_parent_column || null;
        
        if (col.multiple) {
          console.log("Enabling multiple selection for column", col.field);
          column.multiple = true;
          column.delimiter = col.separator_char || ';';
        }
        console.log(column)
        break;
      case "input":
        column.type = "text";
        break;
      case "input":
        column.type = "text";
        break;
      case "autoId":
        column.type = "number";
        column.readOnly = true;
        break;
      case "calculated":
        column.type = "text";
        column.readOnly = true;
        break;
      default:
        column.type = "text";
    }
        if (col.field === 'image'&& col.editor == 'image') {
            column.type = 'html';
            column.readOnly = true; // Behåll den låst eftersom den är beräknad
        }
    return column;
  });
  return columns;
}

function applyContentTransformsToRows(activeWorksheet, rowYs) {
    if (!currentSchema || !currentSchema.columns) return;

    rowYs.forEach((y) => {
        currentSchema.columns.forEach((col) => {
            const x = currentFieldNames.indexOf(col.field);
            if (x === -1) return;

            if (col.editor === 'image') {
                const rawValue = activeWorksheet.getValueFromCoords(x, y);
                const cleanValue = rawValue ? String(rawValue).trim() : '';

                let newValue;
                if (cleanValue && cleanValue !== '0' && cleanValue !== 'null'
                    && !cleanValue.startsWith('<img')) {
                    // NYTT: pipe-separerade bildkoder → flera <img>-taggar, precis som loadTable()
                    newValue = cleanValue.split('|', 13)
                        .map(eachValue => eachValue.trim())
                        .filter(Boolean)
                        .map(eachValue =>
                            `<img src="../img/${eachValue}.svg" style="height: 25px; object-fit: contain; margin: 0 auto;" title="${eachValue}">`
                        )
                        .join('');
                } else if (!cleanValue || cleanValue === '0' || cleanValue === 'null') {
                    newValue = '';
                } else {
                    return; // redan konverterad <img>-tagg, lämna oförändrad
                }

                isUpdatingLiveValues = true;
                activeWorksheet.setValueFromCoords(x, y, newValue, true);
                isUpdatingLiveValues = false;
            }
        });
    });
}

async function loadTable(tableMeta) {
    lastSelectionRange = null
    localStorage.setItem('jss_active_table_meta', JSON.stringify(tableMeta));

    currentTableMeta = tableMeta;
    currentTable = tableMeta.table_name;
    await loadGeneratorConfigs(currentTable);
    renderGeneratorToolbarButtons();
    updateGeneratorToolbarVisibility(); // NYTT
    // --- NYTT: Visa direkt att tabellen laddas, oavsett om man klickade i sidomenyn eller på reload ---
    if (typeof showSyncStatus === 'function') {
        showSyncStatus('saving', `Loading ${tableMeta.display_name}...`);
    }

    document.getElementById("current-table").innerText = tableMeta.display_name;

    const schema = await api(`api/table.php?action=schema&table=${currentTable}`);
    const data = await api(`api/table.php?action=data&table=${currentTable}`);

    currentSchema = schema;
    currentFieldNames = schema.columns.map((col) => col.field);
    console.log("Loaded schema for table", currentTable, "schema:", schema);
    if (table) {
        jspreadsheet.destroy(document.getElementById('table'));
    }
    
    const sheetData = data.map((row) => rowToObject(row));
    normalizeMultiselectForLoad(sheetData, schema.columns);

    // --- NEW CODE: Fetch and map comments from database ---
    let mappedComments = {};
    try {
        // 1. Fetch your comment records for this table
        // Assuming this returns an array: [{ row_key: "105", column_name: "status", comment: "Review this" }]
        const dbComments = await api(`api/table.php?action=get_all_comments&target_table=${currentTable}`);
        
        // 2. Map DB keys to spreadsheet alphanumeric addresses (e.g., "C2")
        mappedComments = mapDbCommentsToCoordinates(dbComments, sheetData, currentFieldNames, currentSchema);
    } catch (error) {
        console.error("Failed to fetch or map comments:", error);
    }

    // --- NYTT: GENERELL BILD-KONVERTERARE BASERAD PÅ EDITOR: "image" ---
    // Vi letar i schemat efter vilken kolumn (eller kolumner) som har editor satt till "image"
    if (schema && schema.columns) {
    applyContentTransformsToRows(
        {
            getValueFromCoords: (x, y) => sheetData[y]?.[currentFieldNames[x]],
            setValueFromCoords: (x, y, val) => {
                if (sheetData[y]) sheetData[y][currentFieldNames[x]] = val;
            }
        },
        sheetData.map((_, i) => i)
    );
    }
    // 2. Skapa nu din kolumn-array. Se till att bildkolumnen får rätt CE v5-inställningar
  const spreadsheetColumns = buildSpreadsheetColumns(schema.columns);
  spreadsheetColumns.forEach((col, colIndex) => {
      // Hämta motsvarande kolumn-metadata från ditt schema
      const colMeta = schema.columns[colIndex];
      
      if (colMeta && colMeta.editor === 'image') {
          col.type = 'html'; // Tvingar CE v5 att rendera din <img>-tagg i stället för text
          col.readOnly = true; // Håll den låst eftersom den är beräknad/SQL-driven
      }
  });


  table = jspreadsheet(document.getElementById("table"), {
    

    worksheets: [{
      data: sheetData,
      columns: spreadsheetColumns,
      minDimensions: [schema.columns.length, 6],
      tableOverflow: true,
      allowComments: true,
      comments: mappedComments,
      columnSorting: true,
      sortOnClick: true,  
      columnResize: true,
      allowInsertColumn: false,
      allowDeleteColumn: false,
      allowRenameColumn: false,
      search: true,
      wordWrap: true,
      editable: currentTableMeta.table_type === "editable",
      filters: true,
      freezeColumns:1,

    }],

    contextMenu: function(instance, x, y, e, items) {
        if (currentTable === 'teams') {
            const activeWs = instance.worksheets ? instance.worksheets : instance;
            items.unshift({
                title: '🖼️ Upload SVG image for this row',
                onclick: () => uploadSvgForRow(activeWs, y)
            });
            items.splice(1, 0, { type: 'line' });
        }
        if (currentTable === 'platoonconfigdb_source') {

            let generateShortIdItem = {
                title: '🆔 Generate Unique ShortID',
                onclick: () => generateUniqueShortID(instance, x, y, e, items)
            };
            items.unshift(generateShortIdItem);

        }
        // NYTT: alla datadrivna generatorer, byggda från admin_generators
        activeGeneratorConfigs
            .sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0))
            .forEach((config) => {
                items.unshift({
                    title: `${config.icon || '⚙️'} ${config.label}`,
                    onclick: () => runGeneratorConfig(config, instance, x, y)
                });
            });

        items.splice(activeGeneratorConfigs.length + 1, 0, { type: 'line' });
              // Lägg bara till detta val om användaren faktiskt befinner sig i tabellen 'admin_columns'!
        if (currentTable === 'admin_columns') {
          
          let syncItem = {
              title: '⚡ Create/Sync this row as a column in MySQL',
              onclick: function() {
                  // 1. Hämta datan för raden du högerklickade på
                  const activeWorksheet = instance.worksheets ? instance.worksheets : instance;
                  const rowData = rowToObject(activeWorksheet.getRowData(y));
                  
                  // Ta reda på måltabellen och kolumnnamnet från din admin_columns-rad
                  const targetTable = rowData.table_name;
                  const targetColumn = rowData.column_name;

                  if (!targetTable || !targetColumn) {
                      alert("Du måste fylla i både table_name och column_name innan du kan synka!");
                      return;
                  }

                  if (confirm(`Vill du fysiskt skapa/modifiera kolumnen "${targetColumn}" i MySQL-tabellen "${targetTable}"?`)) {
                      // 2. Anropa ditt nya PHP-case
                      api(`api/table.php?action=sync_column&table=${targetTable}&column=${targetColumn}`)
                      .then(result => {
                          if (result && result.success) {
                              alert(result.message);
                          } else {
                              alert("Synk misslyckades: " + (result ? result.error : "Okänt fel"));
                          }
                      }).catch(err => console.error("Nätverksfel vid synk:", err));
                  }
              }
          };
          
          items.unshift(syncItem);
        }
        const activeWorksheetForMenu = instance.worksheets ? instance.worksheets : instance;
        const columnDef = activeWorksheetForMenu.options.columns[x];

        if (columnDef && columnDef.type === 'dropdown') {
            let addNewItem = {
                title: 'Add new value to list',
                onclick: function() {
                console.log("Adding new value to list for column", x, "row", y);

                    let newListItem = prompt("Enter new value for list:");

                    if (newListItem === "") {
                        alert("Value cannot be empty!");
                        return;
                    }
                    instance.setValueFromCoords(x, y, newListItem);
                }
            };
            // Lägg till det nya valet högst upp i den befintliga menyn
            items.unshift(addNewItem);
        }
        // 1. Hämta alla markerade rader
        let selectedRows = instance.getSelectedRows();

        let count = selectedRows.length;

        // Om användaren bara högerklickade utan att markera flera rader, sätter vi antalet till 1
        if (count === 0) {
            count = 1;
        }

        // 2. MENYVAL: INFOGA RADER OVANFÖR
        // Hitta den översta raden bland de markerade (om bara en rad, använd y)
        let topRowIndex = count > 1 ? Math.min(...selectedRows) : y;
        if (count > 1) {
          let insertRowsItem = {
            title: count === 1 ? 'Insert 1 row above' : `Insert ${count} rows above`,
            onclick: function() {
                // Parametrar i v5: (antal rader, referensradens index, infoga före = true)
                instance.insertRow(count, topRowIndex, true);
            }
          };
            // Ta bort standardvalen "Copy" och "Save as" (eller liknande) från menyn
            items = items.filter((item) => {
                console.log(item.title);
                if (!item.title) return true; // keep separators / items without a title
                const t = item.title.toLowerCase();
                return !(t.includes('insert a new row') );
            });

          // Lägg till valet i menyn
          items.unshift(insertRowsItem);

        }

        // Hämta markeringen för att se om användaren har markerat flera rader nedåt
        let range = instance.getSelection();
        let isMultipleRowsSelected = range && Math.abs(range[1] - range[3]) > 0;

        // Lägg bara till Fill Down i menyn om mer än en rad är markerad
        if (isMultipleRowsSelected) {
            let fillDownItem = {
                title: 'Fill Down (Ctrl + D)',
                onclick: function() {
                    fillDown(instance);
                }
            };
            // Lägg till den i menyn (t.ex. näst längst upp)
            items.unshift(fillDownItem);
        }


        return items;
    },
    
    onbeforedeleterow:  function(instance, rowIndexArray) {
      if (isProgrammaticDelete) {
        return true; 
      }
      deleteRow(rowIndexArray);
    return false;
    },
    onbeforechange: function(instance, cell, x, y, value) {
        // 1. Kontrollera att tabelltypen tillåter redigering
        if (currentTableMeta.table_type !== "editable") {
            return false;
        }
        if (isAutoIdUpdate) {
            return value;
        }
        const columns = instance.getConfig().columns;
        const currentColumnField = columns[x].field || columns[x].name;
        const pkFieldName = schema.primaryKey || "id";

        // 5. VALIDERA BARA OM DET ÄR PRIMARY KEY SOM ÄNDRAS
        if (currentColumnField === pkFieldName) {
            const newValue = String(value).trim();
            const oldValue = instance.getValueFromCoords(x, y);
            if (newValue === "" || newValue === "0") {
                alert("Primary key kan inte vara tom!");
                return value; // Avbryt ändringen
            }

            // Hämta alla befintliga värden i just denna PK-kolumn
            const columnData = instance.getColumnData(x);

            // Kontrollera om värdet redan finns på någon ANNAN rad (rowIndex !== y)
            const isDuplicate = columnData.some((existingValue, rowIndex) => {
                if (parseInt(rowIndex) === parseInt(y)) return false;
                return String(existingValue).toUpperCase().trim() === newValue.toUpperCase(); // Jämför utan att bry sig om versaler/gemener och trimma whitespace  
            });

            if (isDuplicate) {
                alert(`Värdet "${newValue}" är inte unikt! Kolumnen "${pkFieldName}" kräver unika värden.`);
                return oldValue; // Avbryt ändringen och återställ cellen
            }
        }
        
        // Om det inte var en primary key, eller om värdet var unikt och godkänt:
        return value; 
    },
    // Detta event körs automatiskt när en användare trycker Ctrl + Z (Ångra)
    onundo: async function(instance, historyRecord) {
        // Kontrollera att tabellen tillåter redigering
        if (currentTableMeta.table_type !== "editable") {
            return;
        }

        console.log("Ctrl + Z registrerat, synkar ändringar med databasen...", historyRecord);

        // Jspreadsheet skickar med ett historikobjekt. Vi vill ta reda på vilka rader som påverkades.
        // Beroende på om det var en enskild cell eller ett cellområde som ångrades,
        // loopar vi igenom de påverkade radindexen.
        const affectedRows = new Set();

        // Om historiken innehåller information om specifika rader/celler
        if (historyRecord && historyRecord.records) {
            historyRecord.records.forEach(record => {
                // record.y innehåller radindexet för cellen som återställdes
                if (record.y !== undefined) {
                    affectedRows.add(parseInt(record.y));
                }
            });
        } 
        // Vissa builds sparar radindex direkt i roten av historikobjektet
        else if (historyRecord && historyRecord.y !== undefined) {
            affectedRows.add(parseInt(historyRecord.y));
        }

        // Kör din befintliga spara-funktion för varje rad som påverkades av ångringen
        for (const rowIndex of affectedRows) {
            console.log(`Sparar om rad ${rowIndex} efter Ctrl + Z`);
            
            // Kalla på din existerande spara-funktion (t.ex. saveRowByIndex eller saveRow)
            if (typeof saveRowByIndex === 'function') {
                await saveRowByIndex(rowIndex);
            }
        }
    },
    oninsertrow: function (instance, row) {
        // Logic for handling row insertion
        const pkLower = String(currentSchema.primaryKey).toLowerCase();
        console.log("Row inserted at index", row, "current primary key", pkLower);
        if (pkLower === "autoid" || pkLower === "autonr" || pkLower === "id") {

          row.forEach(async (eachRow) => {
            // get next autoId value from backend and set it in the new row
            api(`api/table.php?action=nextAutoId&table=${currentTable}`).then(result => {
              if (result.success) {
                const autoId = result.autoId || result.autonr || result.id;
                const pkColumnIndex = currentSchema.columns.findIndex(col => col.field === currentSchema.primaryKey);
                if (pkColumnIndex !== -1) {
                  console.log("Setting autoId", autoId, "in column index", pkColumnIndex, "for new row at index", eachRow.row);
                  instance.setValueFromCoords(pkColumnIndex, eachRow.row, autoId,true); 
                } else {
                  console.log("not able to set autoId", autoId, "in column index", pkColumnIndex, "for new row at index", eachRow.row);
                }
              } else {
                alert(result.error || "Failed to get next autoId");
              }
            });
          });
        }
        
    },
    onselection: function(worksheetInstance, x1, y1, x2, y2) {
        // 1. Spara positionen i localStorage (currentTable är ditt tabellnamn)
        updateSavedState(currentTable, 'cursor', { x1: x1, y1: y1, x2: x2, y2: y2 });

        const isRangeSelection = (x1 !== x2 || y1 !== y2);

        let activeX = x1, activeY = y1;

        if (isRangeSelection) {
            // Gissningslogiken behövs ENDAST här, där det finns en faktisk flercellsmarkering
            if (lastSelectionRange) {
                const prev = lastSelectionRange;
                if (y2 !== prev.y2 && y1 === prev.y1) activeY = y2;
                else if (y1 !== prev.y1 && y2 === prev.y2) activeY = y1;
                else activeY = y2; // fallback: anta att man drar nedåt

                if (x2 !== prev.x2 && x1 === prev.x1) activeX = x2;
                else if (x1 !== prev.x1 && x2 === prev.x2) activeX = x1;
                else activeX = x2;
            } else {
                activeX = x2;
                activeY = y2;
            }
        }
        // OBS: annars (enskild cell / piltangentnavigering) används x1,y1 rakt av — alltid exakt, oavsett avstånd

        lastSelectionRange = { x1: x1, y1: y1, x2: x2, y2: y2 };

        let cellName = jspreadsheet.helpers.getColumnName(activeX) + (parseInt(activeY) + 1);
        const cellElement = worksheetInstance.getCell(cellName);
        if (!cellElement) return;

        cellElement.scrollIntoView({ block: 'nearest', inline: 'nearest' });
        const container = cellElement.closest('.jss_spreadsheet') || document.getElementById('table');


        if (container) {
            if (activeY === 0 && activeX === 0) {
                container.scrollTo({ top: 0, left: 0, behavior: 'auto' });
            } else if (activeY === 0) {
                container.scrollTo({ top: 0, behavior: 'auto' });
            } else if (activeX === 0) {
                container.scrollTo({ left: 0, behavior: 'auto' });
            }
        }


        if (container) {
            compensateForFrozenOverlap(cellElement, container, activeX, activeY);
        }
        // --- DYNAMISK DROPDOWN-LOGIK I CE v5 ---
        const columns = worksheetInstance.getConfig().columns;
        const currentColumn = columns[x1];

        // Kontrollera om kolumnen du just klickat på har en föräldrakolumn definierad
        if (currentColumn && currentColumn.parent_column) {

            // 1. Hitta kolumnindexet för föräldern
            const parentIdx = columns.findIndex(c => (c.field || c.name) === currentColumn.parent_column);
            if (parentIdx !== -1) {
                // 2. Hämta det nuvarande värdet i föräldracellen på samma rad (y1)
                let parentCellName = jspreadsheet.helpers.getColumnName(parentIdx) + (parseInt(y1) + 1);
                const parentValue = worksheetInstance.getValue(parentCellName);

                // Om föräldern är tom, nollställ listan direkt så man inte kan välja fel rader
                if (!parentValue) {
                    currentColumn.values = [];
                    return;
                }
                if (currentColumn.relation_parent_column) {
                    // 3. Gör anropet till din table.php (action=dropdown_options)
                    api(`api/table.php?action=dropdown_options&table=${currentTable}&column=${currentColumn.name}&parent_val=${encodeURIComponent(parentValue)}`)
                    .then(data => {
                    
                        if (data && data.success && data.values) {
                            // I CE v5 uppdaterar vi kolumnens värden direkt i dess konfiguration
                            currentColumn.source = data.values;
                            
                        }
                    }).catch(err => console.error("Kunde inte hämta beroende dropdown-data:", err));
                } else {
                    console.log(parentValue.split("|"));
                    
                    currentColumn.source = parentValue.split("|");
                }


            }
        }
    },
    // Detta event körs automatiskt när en användare ändrar bredd på en kolumn
    onresizecolumn: function(instance, columnNumber, width) {
        // 1. Hämta kolumninställningarna från kalkylbladet
        const columns = instance.getConfig().columns;
        const columnConfig = columns[columnNumber];

        // 2. Hämta kolumnens riktiga namn (mappar mot 'column_name' i din DB)
        const columnName = columnConfig.name || columnConfig.field;

        // Kontrollera att vi har ett giltigt kolumnnamn och tabellnamn
        if (!columnName || !currentTable) {
            console.warn("Kunde inte hitta kolumnnamn eller tabellnamn för att spara bredd");
            return;
        }

        // 3. Skicka uppdateringen till ditt PHP-api via en POST
        api(`api/table.php?action=update_column_width&table=${currentTable}`, {
            method: "POST",
            body: JSON.stringify({
                table: currentTable,   // t.ex. 'weaponsLink'
                column_name: columnName,    // t.ex. 'weapon_id'
                width: parseInt(width)      // Den nya bredden i pixlar
            })
        }).then(result => {
            if (!result.success) {
                console.error("Misslyckades att spara kolumnbredd:", result.error);
            }
        }).catch(err => {
            console.error("Nätverksfel vid sparande av kolumnbredd:", err);
        });
    },
     // Detta event körs automatiskt när en användare flyttar en kolumn till en ny plats
    onmovecolumn: function(instance, origin, destination) {
        if (!currentTable) return;

        // 1. Hämta alla kolumner från kalkylbladets nuvarande visuella ordning
        // (Vi säkrar upp instansen beroende på om det är worksheet eller rotelement)
        const jssInstance = instance.jspreadsheet || instance;
        const columns = jssInstance.getConfig().columns;

        const columnOrderList = [];

        // 2. Loopa igenom kolumnerna och bygg en lista med deras nya index (sort_order)
        columns.forEach((col, index) => {
            const columnName = col.name || col.field;
            if (columnName) {
                columnOrderList.push({
                    column_name: columnName,
                    sort_order: index // Det nya indexet blir dess sort_order i databasen
                });
            }
        });

        console.log(`Sparar ny kolumnordning för tabell ${currentTable}`, columnOrderList);

        // 3. Skicka HELA den nya ordningen till din PHP-backend
        api(`api/table.php?action=update_column_order&table=${currentTable}`, {
            method: "POST",
            body: JSON.stringify({
                table: currentTable,
                orders: columnOrderList // Skickar med en array av {column_name, sort_order}
            })
        }).then(result => {
            if (!result.success) {
                console.error("Misslyckades att spara kolumnordning:", result.error);
            } else {
                console.log("Ny kolumnordning sparad!");
            }
        }).catch(err => {
            console.error("Nätverksfel vid sparande av kolumnordning:", err);
        });
    },
    onchange: function(instance, cell, x, y, value) {
        if (currentTableMeta.table_type !== "editable") return;
        // Om vi just nu injicerar värden från servern, gör absolut ingenting!
        if (isUpdatingLiveValues) return; 

        
        // 1. Lägg till radindexet i vår spar-kö
        changedRowsQueue.add(parseInt(y));

        // 2. Visa din nya statusruta (Se Del 2 nedan)
        showSyncStatus('saving', 'Saving changes...');

        // 3. Rensa tidigare timeout. Detta gör att koden väntar tills användaren skrivit/klistrat klart!
        clearTimeout(saveTimeout);

        // 4. Vänta 200ms efter SISTA celländringen innan vi skickar allt till servern
        saveTimeout = setTimeout(function() {
            triggerBatchSave();
        }, 200);
        setTimeout(() => {
        }, 50);
    },
    onload: function(instance) {
      let savedState = localStorage.getItem('jss_state_' + currentTable);
      console.log("Loaded saved state for schema", currentTable, "state:", savedState);
              // Plocka ut det aktiva worksheetet (samma som din fungerande sortering använder)
      const activeWorksheet = instance.worksheets[0];
      if (typeof showSyncStatus === 'function') {
          showSyncStatus('synced', 'All changes saved!');
      }
      if (savedState) {
          savedState = JSON.parse(savedState);
        
          if (!activeWorksheet) return;
          // 1. Återställ Sortering
          if (savedState.sorting) {
              activeWorksheet.orderBy(savedState.sorting.column, savedState.sorting.direction);
          }
          
          // 2. Återställ Filter (om tillgängligt)
          if (savedState.filters) {
              savedState.filters.forEach(f => {
                  activeWorksheet.setFilter(f.column, f.values);
              });
          }

          // 3. Återställ Markering, Scroll och Fokus
          if (savedState.cursor) {
              // Vi väntar 100ms så att tabellen hinner rita cellerna i webbläsaren först
              setTimeout(() => {
                  // Markera cellen/området programmatiskt
                  activeWorksheet.updateSelectionFromCoords(
                      savedState.cursor.x1, 
                      savedState.cursor.y1, 
                      savedState.cursor.x2, 
                      savedState.cursor.y2
                  );
                  
                  // Hämta cellens element för att kunna tvinga fönstret/containern att scrolla dit
                  let cellName = jspreadsheet.helpers.getColumnName(savedState.cursor.x1) + (parseInt(savedState.cursor.y1) + 1);
                  const cellElement = activeWorksheet.getCell(cellName);
                  
                  if (cellElement) {
                      cellElement.scrollIntoView({
                          block: 'nearest',
                          inline: 'nearest'
                      });
                  }

                  // Sätt tangentbordsfokus på kalkylbladet
                  if (typeof activeWorksheet.focus === 'function') {
                      activeWorksheet.focus();
                  }
              }, 100);
          }
      }
        
    },
    // Lägg till dessa inuti dina Jspreadsheet-inställningar:
    onbeforeedition: function(instance, cell, x, y) {
        const activeWorksheet = (table && Array.isArray(table)) ? table[0] : table;
        if (!activeWorksheet) return;

        // 1. Kontrollera att kolumnen inte REDAN är en dropdown eller lista
        const columns = typeof activeWorksheet.getConfig === 'function' 
                        ? activeWorksheet.getConfig().columns 
                        : activeWorksheet.options.columns;
                        
        if (columns && columns[x] && (columns[x].type === 'dropdown' || columns[x].type === 'list' || columns[x].editor === 'list')) {
            return; // Låt riktiga dropdowns vara
        }

        // 2. Skanna kolumnen i minnet efter alla UNIKA värden som redan skrivits där
        const uniqueValues = new Set();
        const totalRowsCount = activeWorksheet.options.data ? activeWorksheet.options.data.length : activeWorksheet.rows.length;

        for (let rowY = 0; rowY < totalRowsCount; rowY++) {
            if (parseInt(rowY) === parseInt(y)) continue; // Hoppa över raden vi står på

            const cellValue = String(activeWorksheet.getValueFromCoords(x, rowY) || '').trim();
            if (cellValue && cellValue.length > 1) {
                uniqueValues.add(cellValue);
            }
        }

        // 3. Om vi hittade tidigare värden i kolumnen, förvandla den tillfälligt till en dropdown!
        if (uniqueValues.size > 0) {
            // Spara kolumnens originaltyp på objektet så vi kan återställa den sen
            if (!columns[x]._originalType) {
                columns[x]._originalType = columns[x].type || 'text';
            }

            // Gör om kolumnen till en sökbar dropdown fylld med de unika värdena
            columns[x].type = 'dropdown';
            columns[x].source = Array.from(uniqueValues);
            columns[x].autocomplete = true; // Aktiverar sökning/förslag när man skriver
        }
    },

    oneditionend: function(instance, cell, x, y, value, save) {
        const activeWorksheet = (table && Array.isArray(table)) ? table[0] : table;
        if (!activeWorksheet) return;

        // Återställ kolumnen till sin originaltyp (t.ex. 'text') omedelbart när användaren tryckt Enter
        const columns = typeof activeWorksheet.getConfig === 'function' 
                        ? activeWorksheet.getConfig().columns 
                        : activeWorksheet.options.columns;

        if (columns && columns[x] && columns[x]._originalType) {
            columns[x].type = columns[x]._originalType;
            delete columns[x].source; // Rensa förslagsdatan så den laddas fräsch nästa gång
        }
    },


    allowComments: true, 
    oncomments: async function(instance, newComments, oldComments) {
        // Access the current active worksheet instance
        const activeWorksheet = instance; 
        const pkField = currentSchema?.primaryKey || 'id';
        const pkX = currentFieldNames.indexOf(pkField);
        for (const [cellName, commentText] of Object.entries(newComments)) {
            // 1. Convert cell identifier (e.g., "B4") to numeric index coordinates [x, y]
            const coords = jspreadsheet.helpers.getCoordsFromCellName(cellName);
            
            if (!coords) continue;
            
            const x = parseInt(coords[0]);
            const y = parseInt(coords[1]);

            // 2. Resolve target dynamic address using your custom business logic
            const columnName = currentFieldNames[x] || 'row';
            const rowKey = String(activeWorksheet.getValueFromCoords(pkX, y) || y);

            // 3. Process database synchronization
            if (commentText === null || commentText.trim() === '') {
                // SCENARIO A: Comment Deleted
                try {
                    // Fetch using your specified GET parameters to safely pull the ID first
                    // 1. Hämta kommentaren säkert med din api-funktion (JSON parsas automatiskt)
                    const commentData = await api(`api/table.php?action=get_comments&target_table=${currentTable}&row_key=${encodeURIComponent(rowKey)}&column_name=${encodeURIComponent(columnName)}`);

                    // If a comment entry exists with a database row ID, send the POST request
                    if (commentData && commentData[0].id) {
                        await api('api/table.php?action=delete_comment', { 
                            method: 'POST', 
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ id: commentData[0].id }) 
                        });
                        console.log(`Deleted comment for Row PK: ${rowKey}, Col: ${columnName}`);
                    }
                } catch (error) {
                    console.error('Failed to sync deletion to database:', error);
                }
                break; // Exit the loop early since we handled the deletion
            } else {
                // SCENARIO B: Comment Created or Updated
                try {
                    await api('api/table.php?action=save_comment', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            target_table: currentTable,
                            row_key: rowKey,
                            column_name: columnName,
                            comment: commentText
                        })
                    });
                    console.log(`Saved comment for Row PK: ${rowKey}, Col: ${columnName}`);
                } catch (error) {
                    console.error('Failed to sync update to database:', error);
                }
            }
        }
    },


    // 2. EVENT: Körs varje gång användaren sorterar en kolumn
    onsort: function(instance, column, direction) {
        updateSavedState(currentTable, 'sorting', { column: column, direction: direction });
    },

    // 3. EVENT: Körs varje gång användaren ändrar ett filter
    onfilter: function(instance, filters) {
        // 'filters' är en array med de aktiva filtren
        updateSavedState(currentTable, 'filters', filters);
    }
  });
  syncComparatorWidgetWithActiveTable(tableMeta);

  
  // --- START: GENERELL VILLKORSSTYRD FORMATERING VIA DYNAMISK CSS ---
  // 1. Städa bort eventuella gamla CSS-regler från förra tabellen så de inte krockar
  const oldStyle = document.getElementById('jss-dynamic-conditional-formatting');
  if (oldStyle) oldStyle.remove();

  if (schema && schema.columns) {
    // 4. GENERAL CONDITIONAL FORMATTING WITH MULTI-COLUMN SUPPORT
    window.applyLiveDatabaseFormatting = function() {

        const activeWorksheet = (table && Array.isArray(table)) ? table[0] : table;
        if (!activeWorksheet || !activeWorksheet.options || !activeWorksheet.options.data) return;

        const dataMatrix = activeWorksheet.options.data;
        const totalRowsCount = dataMatrix.length;

        let cellStyleTag = document.getElementById('jss-dynamic-cell-colors');
        if (!cellStyleTag) {
            cellStyleTag = document.createElement('style');
            cellStyleTag.id = 'jss-dynamic-cell-colors';
            document.head.appendChild(cellStyleTag);
        }

        // 1. Kompilera bara kolumner som faktiskt har en formel, en gång
        const formattedColumns = [];
        schema.columns.forEach((col) => {
            if (col.conditional_formatting) {
                const x = currentFieldNames.indexOf(col.field);
                if (x === -1) return;
                try {
                    const cleanFormula = col.conditional_formatting.replace(/\\"/g, '"');
                    const formatRule = new Function('value', 'row', `return ${cleanFormula};`);
                    formattedColumns.push({ x, formatRule });
                } catch (err) {
                    console.error(`Error compiling formula for field ${col.field}:`, err);
                }
            }
        });

        if (formattedColumns.length === 0) { cellStyleTag.innerHTML = ""; return; }

        // 2. Gruppera celler efter IDENTISK CSS-text, så stilblocket bara skrivs en gång
        const styleGroups = new Map(); // cssText -> [selector, selector, ...]

        // 3. Loopa rader EN gång, bygg row-map EN gång, kör sedan alla formler på den
        for (let y = 0; y < totalRowsCount; y++) {
            const rowDataMap = {};
            for (let i = 0; i < currentFieldNames.length; i++) {
                rowDataMap[currentFieldNames[i]] = dataMatrix[y][i];
            }

            for (const { x, formatRule } of formattedColumns) {
                let cssResult;
                try {
                    cssResult = formatRule(dataMatrix[y][x], rowDataMap);
                } catch (err) { continue; }
                if (!cssResult || cssResult.trim() === "") continue;

                if (!styleGroups.has(cssResult)) styleGroups.set(cssResult, []);
                styleGroups.get(cssResult).push(
                    `.jss_worksheet tbody tr[data-y="${y}"] td[data-x="${x}"], .jexcel tbody tr[data-y="${y}"] td[data-x="${x}"]`
                );
            }
        }

        // 4. Bygg slutgiltig stylesheet — EN regel per unik stil, selektorer grupperade
        let dynamicCellRules = "";
        styleGroups.forEach((selectors, cssText) => {
            dynamicCellRules += `${selectors.join(',\n')} { ${cssText} }\n`;
        });

        cellStyleTag.innerHTML = dynamicCellRules;
    };

    // 5. Kör beräkningen direkt vid start
    setTimeout(() => {
        if (typeof window.applyLiveDatabaseFormatting === 'function') {
            runWithLoadingIndicator(
                () => window.applyLiveDatabaseFormatting(),
                "Applying formatting...",
                "Formatting applied ✓"
            );
        }
    }, 150);

    // Snabb, riktad formatering — kör bara formler för specifika rader och sätt stilen direkt på cellen
    window.applyFormattingToRows = function(rowIndexes) {
        const activeWorksheet = (table && Array.isArray(table)) ? table[0] : table;
        if (!activeWorksheet || !schema || !schema.columns) return;

        const dataMatrix = activeWorksheet.options.data;

        const formattedColumns = [];
        schema.columns.forEach((col) => {
            if (col.conditional_formatting) {
                const x = currentFieldNames.indexOf(col.field);
                if (x === -1) return;
                try {
                    const cleanFormula = col.conditional_formatting.replace(/\\"/g, '"');
                    const formatRule = new Function('value', 'row', `return ${cleanFormula};`);
                    formattedColumns.push({ x, formatRule });
                } catch (err) {
                    console.error(`Error compiling formula for field ${col.field}:`, err);
                }
            }
        });
        if (formattedColumns.length === 0) return;

        rowIndexes.forEach((y) => {
            if (!dataMatrix[y]) return;
            const rowDataMap = {};
            for (let i = 0; i < currentFieldNames.length; i++) {
                rowDataMap[currentFieldNames[i]] = dataMatrix[y][i];
            }

            formattedColumns.forEach(({ x, formatRule }) => {
                let cssResult;
                try {
                    cssResult = formatRule(dataMatrix[y][x], rowDataMap);
                } catch (err) { return; }

                // Hämta DOM-cellen direkt och sätt stilen på den — ingen stylesheet-ombyggnad
                let cellElement = null;
                if (typeof activeWorksheet.getCellFromCoords === 'function') {
                    cellElement = activeWorksheet.getCellFromCoords(x, y);
                } else if (activeWorksheet.records && activeWorksheet.records[y] && activeWorksheet.records[y][x]) {
                    cellElement = activeWorksheet.records[y][x].element;
                }

                if (cellElement) {
                    cellElement.style.cssText = cssResult || '';
                } else {
                    console.warn(`Could not find DOM cell at x=${x}, y=${y} — check getCellFromCoords API`);
                }
            });
        });
    };


  }
  // --- SLUT: DYNAMISK VILLKORSSTYRD FORMATERING ---

}

function normalizeMultiselectForLoad(sheetData, schemaColumns) {
    schemaColumns.forEach((col, x) => {
        if (col.editor === 'list' && col.multiple) {
            sheetData.forEach((row) => {
                if (typeof row[col.field] === 'string' && row[col.field].includes(col.separator_char || '|')) {
                    row[col.field] = row[col.field].split(col.separator_char || '|').join(';');
                }
            });
        }
    });
}

// === GENERISK GENERATOR-MOTOR (datadriven via admin_generators) ===

let activeGeneratorConfigs = []; // fylls i loadTable() för aktuell tabell

async function loadGeneratorConfigs(tableName) {
    try {
        const configs = await api(`api/table.php?action=generators&table=${tableName}`);
        activeGeneratorConfigs = Array.isArray(configs) ? configs : [];
    } catch (err) {
        console.error("Kunde inte hämta generator-konfigurationer:", err);
        activeGeneratorConfigs = [];
    }
}

// --- regex_extract: extraherar capture-grupp 1 ur källtexten, ev. validerar mot lookup-tabell ---
async function runRegexExtract(config, selectedRows, activeWorksheet) {
    const sourceX = currentFieldNames.indexOf(config.source_column);
    const targetX = currentFieldNames.indexOf(config.target_column);
    if (sourceX === -1 || targetX === -1) {
        alert(`Saknar kolumn '${config.source_column}' eller '${config.target_column}'.`);
        return;
    }

    let lookupSet = null;
    if (config.lookup_table && config.lookup_column) {
        if (typeof showSyncStatus === 'function') showSyncStatus('saving', `Validerar mot ${config.lookup_table}...`);
        const map = await buildLookupMap(config.lookup_table, config.lookup_column, config.lookup_column);
        lookupSet = new Set(map.keys());
    }

    const regex = new RegExp(config.regex_pattern, 'g');
    let processedCount = 0;
    const invalidCodes = new Set();

    selectedRows.forEach((rowY) => {
        const sourceText = String(activeWorksheet.getValueFromCoords(sourceX, rowY) || '').trim();
        if (!sourceText) return;

        const matches = [...sourceText.matchAll(regex)].map(m => (m[1] !== undefined ? m[1] : m[0]));
        if (matches.length === 0) return;

        if (lookupSet) matches.forEach((code) => { if (!lookupSet.has(code)) invalidCodes.add(code); });

        activeWorksheet.setValueFromCoords(targetX, rowY, matches.join(config.separator || '|'), true);
        processedCount++;
    });

    if (invalidCodes.size > 0) {
        alert(`${config.label}: ${processedCount} rad(er), men ${invalidCodes.size} kod(er) hittades inte i ${config.lookup_table}:\n\n${[...invalidCodes].join(', ')}`);
    } else if (typeof showSyncStatus === 'function') {
        showSyncStatus('saving', `${config.label}: ${processedCount} rad(er) ✓`);
    }
}

function mapDbCommentsToCoordinates(dbComments, sheetData, currentFieldNames, currentSchema) {
    const commentsObj = {};
    if (!Array.isArray(dbComments)) return commentsObj;

    const pkField = currentSchema?.primaryKey || 'id';

    dbComments.forEach(item => {
        // Find row index (y) by matching the DB row's primary key value
        const y = sheetData.findIndex(row => String(row[pkField]) === String(item.row_key));
        
        // Find column index (x) by matching the field name
        const x = currentFieldNames.indexOf(item.column_name);

        // Map if matches exist in current grid view bounds
        if (y !== -1 && x !== -1) {

            const cellAddress = jspreadsheet.helpers.getCellNameFromCoords(x, y);
            
            commentsObj[cellAddress] = item.comment;
        }
    });

    return commentsObj;
}

// --- lookup_table_match: städar rader (via stripPatterns) och matchar mot lookup-tabell ---
async function runLookupTableMatch(config, selectedRows, activeWorksheet) {
    
    const sourceX = currentFieldNames.indexOf(config.source_column);
    const source2X = currentFieldNames.indexOf(config.source_column_2);
    const targetX = currentFieldNames.indexOf(config.target_column);
    const target2X = config.target_column_2 ? currentFieldNames.indexOf(config.target_column_2) : -1;
    if (sourceX === -1 || targetX === -1) {
        alert(`Saknar kolumn '${config.source_column}' eller '${config.target_column}'.`);
        return;
    }

    if (typeof showSyncStatus === 'function') showSyncStatus('saving', `Laddar ${config.lookup_table}...`);
    const lookupMap = await buildLookupMap(config.lookup_table, config.lookup_column, config.lookup_return_column);

    const extra = typeof config.extra_config === 'string' ? JSON.parse(config.extra_config) : (config.extra_config || {});
    const stripPatterns = (extra.stripPatterns || []).map(p => new RegExp(p, 'g'));

    function cleanLine(line) {
        let cleaned = line;
        stripPatterns.forEach((re) => { cleaned = cleaned.replace(re, ''); });
        return cleaned.trim();
    }

    let processedCount = 0;
    const unmatchedByRow = {};

    selectedRows.forEach((rowY) => {
        const sourceText = String(activeWorksheet.getValueFromCoords(sourceX, rowY) || '').trim();
        const source2Text = source2X !== -1
            ? String(activeWorksheet.getValueFromCoords(source2X, rowY) || '').trim()
            : '';
        if (!sourceText) return;
        const lines = source2Text
            ? source2Text.split('|').map(l => l.trim()).filter(Boolean)
            : sourceText.split(/\r?\n/).map(l => l.trim()).filter(Boolean);

        const matchedKeys = [], matchedReturns = [], unmatched = [];

        lines.forEach((line) => {
            const key = cleanLine(line);
            if (lookupMap.has(key)) {
                matchedKeys.push(key);
                const ret = lookupMap.get(key);
                if (ret) matchedReturns.push(ret);
            } else {
                unmatched.push(key);
            }
        });

        if (matchedKeys.length > 0) {
            activeWorksheet.setValueFromCoords(targetX, rowY, matchedKeys.join(config.separator || '|'), true);
            if (target2X !== -1) activeWorksheet.setValueFromCoords(target2X, rowY, matchedReturns.join(config.separator || '|'), true);
            processedCount++;
        }
        if (unmatched.length > 0) unmatchedByRow[rowY] = unmatched;
    });

    const unmatchedRowCount = Object.keys(unmatchedByRow).length;
    if (unmatchedRowCount > 0) {
        console.log(`${config.label}: hoppade poster:`, unmatchedByRow);
        if (typeof showSyncStatus === 'function') showSyncStatus('saving', `${config.label}: ${processedCount} rad(er). ${unmatchedRowCount} hade hoppade poster (se konsol).`);
    } else if (typeof showSyncStatus === 'function') {
        showSyncStatus('saving', `${config.label}: ${processedCount} rad(er) ✓`);
    }
}
// Delad parsningsmotor — körs en gång per rad, oavsett vilken av de tre knapparna som anropar den
function parseLineCounts(sourceText, extra, getFieldValue) {
    const rule = extra.firstLineRule || {};
    const lines = sourceText.split(/\r?\n/);

    const rawResults = [];
    const actualResults = [];
    let sum = 0;

    const conditionVal = rule.conditionField ? String(getFieldValue(rule.conditionField) || '').trim() : null;
    const excludeVal = rule.excludeField ? String(getFieldValue(rule.excludeField) || '').trim() : null;
    const divisor = rule.divisorField ? (parseInt(getFieldValue(rule.divisorField)) || 0) : 0;

    lines.forEach((line, index) => {
        const cleanLine = line.trim();
        if (!cleanLine) return;

        const match = cleanLine.match(/^([0-9]+)x/);
        if (!match) return;

        let num = parseInt(match[1]);
        let actualNum = num;
        sum += num; // alltid URSPRUNGLIGA antalet, oavsett ev. specialregel nedan

        const ruleApplies = rule.enabled && index === 0 &&
            rule.conditionField && conditionVal === rule.conditionValue &&
            rule.excludeField && excludeVal !== rule.excludeValue &&
            divisor > 0;

        if (ruleApplies) {
            if (rule.formula === 'subtract_one_divide') {
                num = (num - 1) / divisor;
            }
            // NYTT: fler formler kan läggas till här senare (t.ex. "divide_only", "multiply")
            const prefix = rule.prefix || '';
            num = prefix + num;
            actualNum = num + "x" + divisor;
        }

        rawResults.push(num);
        actualResults.push(actualNum);
    });

    return { rawResults, actualResults, sum };
}

// Routern som de tre knapparna faktiskt anropar — väljer output baserat på extra_config.outputType
function runLineCountCalc(config, selectedRows, activeWorksheet) {
    const sourceX = currentFieldNames.indexOf(config.source_column);
    const targetX = currentFieldNames.indexOf(config.target_column);
    if (sourceX === -1 || targetX === -1) {
        alert(`Saknar kolumn '${config.source_column}' eller '${config.target_column}'.`);
        return;
    }

    const extra = typeof config.extra_config === 'string' ? JSON.parse(config.extra_config) : (config.extra_config || {});
    const outputType = extra.outputType || 'raw_list';

    let processedCount = 0;

    selectedRows.forEach((rowY) => {
        const sourceText = String(activeWorksheet.getValueFromCoords(sourceX, rowY) || '').trim();
        if (!sourceText) return;

        const getFieldValue = (fieldName) => {
            const fx = currentFieldNames.indexOf(fieldName);
            return fx !== -1 ? activeWorksheet.getValueFromCoords(fx, rowY) : null;
        };

        const { rawResults, actualResults, sum } = parseLineCounts(sourceText, extra, getFieldValue);
        if (rawResults.length === 0) return;

        let finalValue;
        switch (outputType) {
            case 'actual_breakdown': finalValue = actualResults.join('|'); break;
            case 'sum_total':        finalValue = sum; break;
            case 'raw_list':
            default:                 finalValue = rawResults.join('|'); break;
        }

        activeWorksheet.setValueFromCoords(targetX, rowY, finalValue, true);
        processedCount++;
    });

    if (typeof showSyncStatus === 'function') showSyncStatus('saving', `${config.label}: ${processedCount} rad(er) ✓`);
}

function buildTokenScanRegex(map, useWordBoundaries = true) {
    const keys = Object.keys(map);
    if (keys.length === 0) return null;
    const sortedKeys = keys.sort((a, b) => b.length - a.length);
    const escaped = sortedKeys.map(k => k.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'));
    const pattern = useWordBoundaries ? `\\b(?:${escaped.join('|')})\\b` : `(?:${escaped.join('|')})`;
    return new RegExp(pattern, 'g');
}

function translateValueWildcard(text, map, outputSeparator, useWordBoundaries = true) {
    const regex = buildTokenScanRegex(map, useWordBoundaries);
    if (!regex) return text;
    const matches = text.match(regex);
    if (!matches || matches.length === 0) return text;
    return matches.map(m => (map[m] !== undefined ? map[m] : m)).join(outputSeparator);
}

function translateValueInPlace(text, map, useWordBoundaries = false) {
    const regex = buildTokenScanRegex(map, useWordBoundaries);
    if (!regex) return text;
    return text.replace(regex, (match) => (map[match] !== undefined ? map[match] : match));
}

function toLowercasePreservingAcronyms(text, preservedWords) {
    const escaped = preservedWords.map(w => w.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'));
    const regex = new RegExp(`\\b(${escaped.join('|')})\\b`, 'gi');

    let result = '';
    let lastIndex = 0;
    let match;

    while ((match = regex.exec(text)) !== null) {
        result += text.slice(lastIndex, match.index).toLowerCase(); // allt FÖRE -> gemener
        result += match[0].toUpperCase();                          // träffen -> versaler
        lastIndex = regex.lastIndex;
    }
    result += text.slice(lastIndex).toLowerCase(); // resten -> gemener

    return result;
}

function resolveActiveColumnIndex(x) {
    if (x !== null && x !== undefined && x !== -1) return x; // högerklick ger exakt kolumn direkt
    if (lastSelectionRange) return lastSelectionRange.x1;    // toolbar-knapp: senaste markerade kolumn
    return -1;
}

function runCaseTransform(config, selectedRows, activeWorksheet, activeColumnX) {
    const sourceX = config.source_column ? currentFieldNames.indexOf(config.source_column) : activeColumnX;
    const targetX = config.target_column ? currentFieldNames.indexOf(config.target_column) : activeColumnX;

    if (sourceX === -1 || targetX === -1) {
        alert("Kunde inte avgöra vilken kolumn som ska bearbetas — markera en cell i en kolumn och försök igen.");
        return;
    }

    const extra = typeof config.extra_config === 'string' ? JSON.parse(config.extra_config) : (config.extra_config || {});
    const preservedWords = extra.preservedWords || [];
    const removeLineBreaks = !!extra.removeLineBreaks;
    const lineBreakReplacement = extra.lineBreakReplacement !== undefined ? extra.lineBreakReplacement : ' ';

    let processedCount = 0;

    selectedRows.forEach((rowY) => {
        let sourceText = String(activeWorksheet.getValueFromCoords(sourceX, rowY) || '');
        if (!sourceText) return;

        // NYTT: ta bort radbrytningar FÖRE case-konverteringen, så ev. dubbla mellanslag
        // som uppstår också städas bort i samma svep
        if (removeLineBreaks) {
            sourceText = sourceText
                .replace(/\r?\n/g, lineBreakReplacement)
                .replace(/\s+/g, ' ') // krymper ev. dubbla mellanslag till ett, om replacement är " "
                .trim();
        }

        const result = toLowercasePreservingAcronyms(sourceText, preservedWords);
        activeWorksheet.setValueFromCoords(targetX, rowY, result, true);
        processedCount++;
    });

    if (typeof showSyncStatus === 'function') showSyncStatus('saving', `${config.label}: ${processedCount} rad(er) ✓`);
}

function runTokenMapReplace(config, selectedRows, activeWorksheet,activeColumnX) {
    const sourceX = config.source_column? currentFieldNames.indexOf(config.source_column): activeColumnX;
    const source2X = currentFieldNames.indexOf(config.source_column_2);
    const targetX = config.target_column? currentFieldNames.indexOf(config.target_column): activeColumnX;
    if (sourceX === -1 || targetX === -1) {
        alert(`Saknar kolumn '${config.source_column}' eller '${config.target_column}'.`);
        return;
    }
    const extra = typeof config.extra_config === 'string' ? JSON.parse(config.extra_config) : (config.extra_config || {});
    const map = extra.map || {};
    const outputSeparator = extra.outputSeparator || '|';
    const matchMode = extra.matchMode || 'wildcard_scan';
    const delimiter = extra.delimiter || null;

    function translateValue(text) {

        if (matchMode === 'delimiter_split' && delimiter) {
            return text.split(delimiter).map(t => t.trim()).filter(Boolean)
                .map(t => (map[t] !== undefined ? map[t] : t)).join(outputSeparator);
        }
        if (matchMode === 'in_place_replace') {
            return translateValueInPlace(text, map, !!extra.useWordBoundaries);
        }
        return translateValueWildcard(text, map, outputSeparator, extra.useWordBoundaries !== false);
    }

    let processedCount = 0;
    selectedRows.forEach((rowY) => {
        const source2Text = source2X !== -1
            ? String(activeWorksheet.getValueFromCoords(source2X, rowY) || '').trim()
            : '';
        const sourceText = String(activeWorksheet.getValueFromCoords(sourceX, rowY) || '').trim();
        if (!sourceText) return;
        if (source2Text) 
            activeWorksheet.setValueFromCoords(targetX, rowY, translateValue(source2Text), true)
        else
            activeWorksheet.setValueFromCoords(targetX, rowY, translateValue(sourceText), true);
        
        processedCount++;
    });

    if (typeof showSyncStatus === 'function') showSyncStatus('saving', `${config.label}: ${processedCount} rad(er) ✓`);
}
// --- ROUTER ---
async function runGeneratorConfig(config, instance, x, y) {
    const activeWorksheet = instance.worksheets ? instance.worksheets : instance;
    if (!activeWorksheet) return;

    const activeColumnX = resolveActiveColumnIndex(x);

    const selectedRows = getEffectiveSelectedRows(activeWorksheet, y);
    if (selectedRows.length === 0) {
        alert("Select at least one row before running generator.");
        return;
    }
console.log(config);
    switch (config.operation_type) {
        case 'regex_extract': await runRegexExtract(config, selectedRows, activeWorksheet); break;
        case 'lookup_table_match': await runLookupTableMatch(config, selectedRows, activeWorksheet); break;
        case 'line_count_calc': runLineCountCalc(config, selectedRows, activeWorksheet); break;
        case 'token_map_replace': runTokenMapReplace(config, selectedRows, activeWorksheet, activeColumnX); break;
        case 'case_transform': runCaseTransform(config, selectedRows, activeWorksheet, activeColumnX); break;
        default: console.warn(`Okänd operation_type: ${config.operation_type}`);
    }
}

// Delad hjälpfunktion — ersätter den gamla "selectedRows = [y]"-logiken överallt
function getEffectiveSelectedRows(activeWorksheet, y) {
    let selectedRows = [];
    if (typeof activeWorksheet.getSelectedRows === 'function') {
        selectedRows = activeWorksheet.getSelectedRows(true);
    }
    if (selectedRows && selectedRows.length > 0) return selectedRows;

    // Högerklick-fallet: vi vet exakt vilken rad
    if (y !== null && y !== undefined) return [y];

    // Knapp i verktygsfältet: använd senast kända markering (live, från onselection)
    if (lastSelectionRange) {
        const startY = Math.min(lastSelectionRange.y1, lastSelectionRange.y2);
        const endY = Math.max(lastSelectionRange.y1, lastSelectionRange.y2);
        const rows = [];
        for (let r = startY; r <= endY; r++) rows.push(r);
        return rows;
    }

    return [];
}

async function generateUniqueShortID(instance, x, y, e, items) {
    const activeWorksheet = instance.worksheets ? instance.worksheets : instance;
    if (!activeWorksheet) return;

    // 1. Resolve column indexes based on your schema layout
    const platoonX = currentFieldNames.indexOf('platoon');
    const costX = currentFieldNames.indexOf('cost');
    const sectionsX = currentFieldNames.indexOf('sections');
    const attachmentX = currentFieldNames.indexOf('attachment');
    const targetShortIdX = currentFieldNames.indexOf('shortID');

    if (platoonX === -1 || costX === -1 || targetShortIdX === -1) {
        alert("Error: Missing required columns ('platoon', 'cost', or 'shortID') in this table view.");
        return;
    }

    let selectedRows = getEffectiveSelectedRows(activeWorksheet, y);
    if (selectedRows.length === 0) {
        alert("Markera minst en rad innan du kör denna generator.");
        return;
    }

    // Hämta det totala antalet rader i kalkylbladet för att kunna skanna efter dubbletter
    const totalRowsCount = activeWorksheet.options.data ? activeWorksheet.options.data.length : activeWorksheet.rows.length;

    // En intern hjälparfunktion för att kontrollera om ett ID redan finns i tabellen
    // (Vi kollar både kalkylbladets minne och hoppar över raden vi håller på att beräkna just nu)
    function isIdAlreadyInUse(checkId, excludeRowY) {
        for (let currentY = 0; currentY < totalRowsCount; currentY++) {
            if (currentY === excludeRowY) continue;
            const existingId = String(activeWorksheet.getValueFromCoords(targetShortIdX, currentY) || '').trim();
            if (existingId === checkId) {
                return true; // Hittade en dubblett!
            }
        }
        return false; // ID:t är unikt
    }

    let processedCount = 0;

    // 3. Loop through each targeted selection row
    selectedRows.forEach((rowY) => {
        // Gather the source string metrics cleanly
        const platoonStr = String(activeWorksheet.getValueFromCoords(platoonX, rowY) || '').trim();
        const costStr    = String(activeWorksheet.getValueFromCoords(costX, rowY) || '').trim();
        const sectionsStr = String(activeWorksheet.getValueFromCoords(sectionsX, rowY) || '').trim();
        const attachStr   = String(activeWorksheet.getValueFromCoords(attachmentX, rowY) || '').trim();

        // Skip row configurations that completely lack base anchors
        if (!platoonStr && !costStr) return;

        // Clean and compress your values (removes pipes/special chars)
        const cleanSections = sectionsStr.replace(/[^a-zA-Z0-9]/g, ''); 
        const cleanAttachment = attachStr.replace(/[^a-zA-Z0-9]/g, '');

        // --- INTELLIGENT DUPLICATE DETECTOR & STEPPER ---
        let indexCounter = 1;
        let generatedShortID = "";
        let isUnique = false;

        // Vi loopar och stegar upp indexCounter ändå tills isIdAlreadyInUse returnerar false!
        while (!isUnique) {
            // Bygg bas-ID:t utifrån din exakta logik
            generatedShortID = `${costStr}${platoonStr}${cleanAttachment}${indexCounter}`;
            
            // Kontrollera om detta kombinerade ID redan är upptaget i kolumnen
            if (isIdAlreadyInUse(generatedShortID, rowY)) {
                console.log(`Dubblett upptäckt för "${generatedShortID}" på rad ${rowY}. Stegar upp index...`);
                indexCounter++; // Krock! Öka slutsiffran med 1 och försök igen
            } else {
                isUnique = true; // Hittade ett helt unikt ID!
            }
        }

        console.log(`Generated Unique ShortID for row ${rowY}: "${generatedShortID}"`);

        // 5. Inject the compiled tracking payload back into Jspreadsheet cell memory
        activeWorksheet.setValueFromCoords(targetShortIdX, rowY, generatedShortID, true);
        processedCount++;
    });

    if (processedCount > 0) {
        if (typeof showSyncStatus === 'function') {
            showSyncStatus('saving', `Generated ${processedCount} unique identifier codes...`);
        }
    }
}

async function calculateSections(instance, x, y, e, items) {
    // 1. Säkra upp kalkylbladet utifrån din array-struktur
    const activeWorksheet = instance.worksheets ? instance.worksheets : instance;
    if (!activeWorksheet) return;

    // 2. Hitta kolumn-indexen baserat på dina globala fältnamn
    const configX = currentFieldNames.indexOf('configuration');
    const targetX = currentFieldNames.indexOf('sections');
    const targetX2 = currentFieldNames.indexOf('actualSections');
    const targetXnrOfTeams = currentFieldNames.indexOf('nrOfTeams');                  
    const teamTypeX = currentFieldNames.indexOf('unitType');
    const platoonTypeX = currentFieldNames.indexOf('unitType'); // Keep your existing assignment fallback mapping
    const sectionSizeX = currentFieldNames.indexOf('sectionSize');

    if (configX === -1 || targetX === -1 || targetX2 === -1) {
        alert("Error: Could not find 'configuration' or target storage column in this table.");
        return;
    }

    // --- NEW: FETCH THE CURRENT USER SELECTION RANGE ---
    // getSelectedRows(true) returns a sorted array of uniquely highlighted row numbers [y1, y2, y3...]
    let selectedRows = [];
    if (typeof activeWorksheet.getSelectedRows === 'function') {
        selectedRows = activeWorksheet.getSelectedRows(true);
    }

    // Fallback: If no rows are highlighted or the selection engine fails, process just the right-clicked row 'y'
    if (!selectedRows || selectedRows.length === 0) {
        selectedRows = [y];
    }

    console.log(`Processing batch matrix calculation across rows:`, selectedRows);
    
    let processedCount = 0;

    // --- NEW: LOOP THROUGH EACH SELECTED ROW INDEX ---
    selectedRows.forEach((rowY) => {
        // Read values targeted strictly to the row context of the loop iteration (rowY)
        const configText = String(activeWorksheet.getValueFromCoords(configX, rowY) || '').trim();
        const teamType = String(activeWorksheet.getValueFromCoords(teamTypeX, rowY) || '').trim();
        const platoonType = String(activeWorksheet.getValueFromCoords(platoonTypeX, rowY) || '').trim();
        const sectionSize = parseInt(activeWorksheet.getValueFromCoords(sectionSizeX, rowY)) || 0;

        // Skip empty configuration slots silently inside a selection batch
        if (!configText) return;

        // 4. JAVASCRIPT-LOGIKEN: Splitta texten rad för rad och extrahera siffrorna
        const lines = configText.split(/\r?\n/);
        const results = [];
        const actualResults = [];
        let sumTeams = 0;

        lines.forEach((line, index) => {
            const cleanLine = line.trim();
            if (!cleanLine) return;

            // Matcha siffran innan x:et (t.ex. "3x" eller "14x")
            const match = cleanLine.match(/^([0-9]+)x/);
            if (match) {
                let num = parseInt(match[1]);
                let actualNum = num;
                sumTeams += num;

                // Keep your customized structural division condition logic
                if (index == 0 && teamType === 'Infantry' && platoonType !== 'Headquarters' && sectionSize > 0) {
                    num = (num - 1) / sectionSize;
                    num = "1|" + num;
                    

                    actualNum = num + "x" + sectionSize;
                }

                results.push(num);
                actualResults.push(actualNum);
            }
        });

        if (results.length > 0) {
            const finalString = results.join('|');
            const actualFinalString = actualResults.join('|');
            console.log(`Calculated string for row index ${rowY}: ${finalString}, ${actualFinalString}`);

            // 5. Update the column cell directly forcing Jspreadsheet change event processing
            activeWorksheet.setValueFromCoords(targetX, rowY, finalString, true);
            activeWorksheet.setValueFromCoords(targetX2, rowY, actualFinalString, true);
            activeWorksheet.setValueFromCoords(targetXnrOfTeams, rowY, sumTeams, true);
            processedCount++;
        }
    });

    if (processedCount > 0) {
        // Trigger your English status notification indicator matching the entire batch mutation context
        if (typeof showSyncStatus === 'function') {
            showSyncStatus('saving', `Recalculating ${processedCount} team matrices...`);
        }
    } else {
        alert("Could not extract any valid data patterns from the selected selection block.");
    }
}


async function generateAttacmentCodes(instance, x, y, e, items) {
    const activeWorksheet = instance.worksheets ? instance.worksheets : instance;
    if (!activeWorksheet) return;

    // 1. Resolve column indexes
    const configX = currentFieldNames.indexOf('configuration');
    const attachmentX = currentFieldNames.indexOf('attachment');

    if (configX === -1 || attachmentX === -1) {
        alert("Error: Missing required columns ('configuration' or 'attachment') in this table view.");
        return;
    }

    // 2. Fetch selected rows (fallback to right-clicked row)
    let selectedRows = [];
    if (typeof activeWorksheet.getSelectedRows === 'function') {
        selectedRows = activeWorksheet.getSelectedRows(true);
    }
    if (!selectedRows || selectedRows.length === 0) {
        selectedRows = [y];
    }

    // 3. Extract ALL matches per row, build pending updates first
    const pendingUpdates = []; // { rowY, codes: [...] }
    const allExtractedCodes = new Set();

    selectedRows.forEach((rowY) => {
        const configText = String(activeWorksheet.getValueFromCoords(configX, rowY) || '').trim();
        if (!configText) return;

        // Hitta ALLA matchningar i texten, inte bara den första
        const matches = configText.match(/\(T[A-Z]+[0-9]+[a-z]*\)/g);

        if (matches && matches.length > 0) {
            const cleanCodes = matches.map((m) => m.replace(/[()]/g, ''));
            cleanCodes.forEach((c) => allExtractedCodes.add(c));

            pendingUpdates.push({ rowY, codes: cleanCodes });
        } else {
            console.log(`No attachment code found for row ${rowY}`);
        }
    });

    if (pendingUpdates.length === 0) {
        alert("Could not extract any attachment codes from the selected rows.");
        return;
    }

    // 4. BATCH VALIDATION: hämta hela platoonsstats_source EN gång och bygg en uppslagstabell
    if (typeof showSyncStatus === 'function') {
        showSyncStatus('saving', `Validating ${allExtractedCodes.size} codes against database...`);
    }

    let validCodesSet = new Set();
    try {
        const statsRows = await api(`api/table.php?action=data&table=platoonsstats_source`);
        if (Array.isArray(statsRows)) {
            statsRows.forEach((row) => {
                if (row.code) validCodesSet.add(String(row.code).trim());
            });
        }
    } catch (err) {
        console.error("Failed to validate codes against platoonsstats_source:", err);
        alert("Warning: could not reach database to validate codes. Proceeding without validation.");
    }

    // 5. Identifiera ogiltiga koder innan vi skriver in dem
    const invalidCodes = [...allExtractedCodes].filter((c) => !validCodesSet.has(c));

    // 6. Skriv in värdena i kalkylbladet (pipe-separerade om flera)
    let processedCount = 0;
    pendingUpdates.forEach(({ rowY, codes }) => {
        const finalString = codes.join('|');
        console.log(`Setting attachment for row ${rowY}: "${finalString}"`);
        activeWorksheet.setValueFromCoords(attachmentX, rowY, finalString, true);
        processedCount++;
    });

    // 7. Rapportera resultat
    if (invalidCodes.length > 0) {
        alert(
            `Generated ${processedCount} attachment value(s), but ${invalidCodes.length} code(s) were NOT found in platoonsstats_source:\n\n` +
            invalidCodes.join(', ')
        );
    } else if (typeof showSyncStatus === 'function') {
        showSyncStatus('saving', `Generated ${processedCount} attachment value(s), all codes validated ✓`);
    }
}

async function generateTeamNImage(instance, x, y, e, items) {
    const activeWorksheet = instance.worksheets ? instance.worksheets : instance;
    if (!activeWorksheet) return;

    // 1. Kolumnindex — justera fältnamn om dina kolumner heter något annat (t.ex. 'teams' istället för 'team')
    const configX = currentFieldNames.indexOf('configuration');
    const teamX = currentFieldNames.indexOf('teams');
    const imageX = currentFieldNames.indexOf('image');

    if (configX === -1 || teamX === -1 || imageX === -1) {
        alert("Error: Missing required columns ('configuration', 'teams', or 'image') in this table view.");
        return;
    }

    // 2. Hämta markerade rader (fallback till högerklickad rad)
    let selectedRows = [];
    if (typeof activeWorksheet.getSelectedRows === 'function') {
        selectedRows = activeWorksheet.getSelectedRows(true);
    }
    if (!selectedRows || selectedRows.length === 0) {
        selectedRows = [y];
    }

    // 3. Hämta HELA teams-tabellen en gång och bygg en uppslagskarta team -> image
    if (typeof showSyncStatus === 'function') {
        showSyncStatus('saving', `Loading teams table...`);
    }

    let teamToImageMap = new Map();
    try {
        const teamsRows = await api(`api/table.php?action=data&table=teams`);
        if (Array.isArray(teamsRows)) {
            teamsRows.forEach((row) => {
                if (row.team) {
                    teamToImageMap.set(String(row.team).trim(), String(row.image || '').trim());
                }
            });
        }
    } catch (err) {
        console.error("Failed to load teams table:", err);
        alert("Could not load teams table — aborting.");
        return;
    }

    // 4. Hjälpfunktion: städa en konfigurationsrad till en ren team-sträng
    function cleanConfigLine(line) {
        let cleaned = line.replace(/^[0-9]+x\s*/, '');           // ta bort "3x " etc.
        cleaned = cleaned.replace(/\s*\(T[A-Z]+[0-9]+[a-z]*\)\s*/g, ''); // ta bort attachment-kod
        return cleaned.trim();
    }

    // 5. Bearbeta varje markerad rad
    let processedCount = 0;
    const unmatchedByRow = {}; // rowY -> [rader som inte hittades i teams]

    selectedRows.forEach((rowY) => {
        const configText = String(activeWorksheet.getValueFromCoords(configX, rowY) || '').trim();
        if (!configText) return;

        const lines = configText.split(/\r?\n/).map(l => l.trim()).filter(l => l.length > 0);

        const matchedTeams = [];
        const matchedImages = [];
        const unmatched = [];

        lines.forEach((line) => {
            const cleanTeam = cleanConfigLine(line);
            if (teamToImageMap.has(cleanTeam)) {
                matchedTeams.push(cleanTeam);
                const img = teamToImageMap.get(cleanTeam);
                if (img) matchedImages.push(img);
            } else {
                unmatched.push(cleanTeam); // t.ex. fordon som FV432 — finns inte i teams, hoppas över
            }
        });

        if (matchedTeams.length > 0) {
            activeWorksheet.setValueFromCoords(teamX, rowY, matchedTeams.join('|'), true);
            activeWorksheet.setValueFromCoords(imageX, rowY, matchedImages.join('|'), true);
            processedCount++;
        }

        if (unmatched.length > 0) {
            unmatchedByRow[rowY] = unmatched;
        }
    });

    // 6. Rapportera
    const unmatchedRowCount = Object.keys(unmatchedByRow).length;
    if (unmatchedRowCount > 0) {
        const preview = Object.entries(unmatchedByRow)
            .slice(0, 5)
            .map(([rowY, items]) => `Row ${rowY}: ${items.join(', ')}`)
            .join('\n');
        console.log("Unmatched lines (skipped, likely vehicles/non-team entries):", unmatchedByRow);
        if (typeof showSyncStatus === 'function') {
            showSyncStatus('saving', `Generated ${processedCount} row(s). ${unmatchedRowCount} row(s) had skipped entries (see console).`);
        }
    } else if (typeof showSyncStatus === 'function') {
        showSyncStatus('saving', `Generated team & image for ${processedCount} row(s) ✓`);
    }
}



function updateSavedState(currentTable, key, value) {
    let state = localStorage.getItem('jss_state_' + currentTable);
    state = state ? JSON.parse(state) : {};
    
    state[key] = value;
    localStorage.setItem('jss_state_' + currentTable, JSON.stringify(state));
}

async function saveRowByIndex(rowIndex) {

  if (!table || rowIndex == null) {

    return;
  }

  console.log("Saving row at index", table[0].getRowData(rowIndex));
  const rowData = rowToObject(table[0].getRowData(rowIndex));
  if (!rowData) {
    return;
  }

  const result = await api(`api/table.php?action=save&table=${currentTable}`, {
    method: "POST",
    body: JSON.stringify(rowData),
  });
  if (result && result.success) {
      showSyncStatus('synced', 'All changes saved!');

      // --- NEW: INJECT RECALCULATED SQL VALUES LIVE ---
      if (result.recalculated && Object.keys(result.recalculated).length > 0) {
          
          // Safely access your active worksheet context
          const activeWorksheet = Array.isArray(table) ? table[0] : (table.worksheets ? table.worksheets[0] : table);
          
          // Loop through all the calculated column fields returned by the server
          Object.keys(result.recalculated).forEach(fieldName => {
              const freshValue = result.recalculated[fieldName];

              // Find the visual column index (x) using your current field names mapping array
              const x = currentFieldNames.indexOf(fieldName);

              // If the column exists in the current view, push the value silently into the cell
              if (x !== -1) {
                  console.log(`Live updating SQL column '${fieldName}' at cell X: ${x}, Y: ${y} to: "${freshValue}"`);
                  
                  // setValue(x, y, value, forceUpdate) - passing true forces Jspreadsheet to redraw the cell
                  activeWorksheet.setValueFromCoords(x, y, freshValue, true);
              }
          });
      }
  } else {
      showSyncStatus('error', 'Save failed: ' + (result ? result.error : 'Unknown error'));
  }
  
}

async function triggerBatchSave() {
    if (changedRowsQueue.size === 0) return;

    // Gör om vår Set till en vanlig array med radindex
    const rowIndexes = Array.from(changedRowsQueue);
    changedRowsQueue.clear(); // Töm kön direkt så vi inte dubbelsparar vid nätverksfel

    const batchData = [];

    
    // Hämta det aktiva kalkylbladet (synkat med din arraystruktur)
    const activeWorksheet = Array.isArray(table) ? table[0] : (table.worksheets ? table.worksheets[0] : table);


    // Loopa igenom radindexen och hämta datan för varje rad
    rowIndexes.forEach(rowIndex => {
        const rawRowData = activeWorksheet.getRowData(rowIndex);
        if (rawRowData) {
            const rowObj = rowToObject(rawRowData);
            currentSchema.columns.forEach((col) => {
                if (col.editor === 'list' && col.multiple && typeof rowObj[col.field] === 'string') {
                    rowObj[col.field] = rowObj[col.field].split(';').join(col.separator_char || '|');
                }
            });
            batchData.push(rowObj);
        }
    });

    try {
        // Skicka ALLA rader i en enda POST-request
        const result = await api(`api/table.php?action=batch_save&table=${currentTable}`, {
            method: 'POST',
            body: JSON.stringify({ rader: batchData })
        });

        if (result && result.success) {
            showSyncStatus('synced', 'All changes saved!');

            // --- INJECT BATCH RECALCULATED SQL VALUES LIVE ---
                
            if (result.recalculated && Object.keys(result.recalculated).length > 0) {
                const calculatedIds = Object.keys(result.recalculated);
                const pkColumnX = currentFieldNames.indexOf(currentSchema.primaryKey || "id");

                let totalRowsCount = 0;
                if (activeWorksheet.options && activeWorksheet.options.data) {
                    totalRowsCount = activeWorksheet.options.data.length;
                } else if (activeWorksheet.rows) {
                    totalRowsCount = activeWorksheet.rows.length;
                } else if (typeof activeWorksheet.getRows === 'function') {
                    totalRowsCount = activeWorksheet.getRows();
                }

                // Bygg id -> radindex EN gång
                const idToRowIndex = new Map();
                for (let y = 0; y < totalRowsCount; y++) {
                    idToRowIndex.set(String(activeWorksheet.getValueFromCoords(pkColumnX, y)), y);
                }

                isUpdatingLiveValues = true;
                const updatedRowYs = [];

                calculatedIds.forEach((currentGridRowId) => {
                    const y = idToRowIndex.get(currentGridRowId);
                    if (y === undefined) return;

                    const freshRowValues = result.recalculated[currentGridRowId];
                    Object.keys(freshRowValues).forEach((fieldName) => {
                        const x = currentFieldNames.indexOf(fieldName);
                        if (x !== -1) {
                            activeWorksheet.setValueFromCoords(x, y, freshRowValues[fieldName], true);
                        }
                    });
                    updatedRowYs.push(y);
                });
                applyContentTransformsToRows(activeWorksheet, updatedRowYs);
                isUpdatingLiveValues = false;


                // --- ÄNDRAT: kör bara formatering på de rader som faktiskt fick nya värden ---
                if (typeof window.applyFormattingToRows === 'function' && updatedRowYs.length > 0) {
                    runWithLoadingIndicator(
                        () => window.applyFormattingToRows(updatedRowYs),
                        "Applying formatting after save...",
                        "Formatting applied ✓"
                    );
                    
                }
            }

        } else {
            showSyncStatus('error', 'Save failed: ' + (result ? result.error : 'Unknown error'));
        }
    } catch (err) {
        console.error("Nätverksfel vid batch-save:", err);
        showSyncStatus('error', 'Network error at batch save.');
    }
}



async function rebuildDependenciesForTable(tableName) {
  const deps = await fetch("api/rebuildDependencies.php", {
    method: "POST",
    body: new URLSearchParams({
      table: tableName,
    }),
  }).then((r) => r.json());

  for (let d of deps) {
    await fetch("api/rebuild.php", {
      method: "POST",
      body: new URLSearchParams({
        table: d.target_table,
      }),
    });
  }
}

function fillDown(instance) {
    // Hämta koordinaterna för det markerade området: [x1, y1, x2, y2]
    // x1, y1 är startcellen (ofta överst till vänster)
    // x2, y2 är slutcellen (ofta nederst till höger)
    let range = instance.getSelection();
    
    if (!range) {
        console.warn("Ingen markering hittades för Fill Down");
        return;
    }

    let x1 = Math.min(range[0], range[2]);
    let x2 = Math.max(range[0], range[2]);
    let y1 = Math.min(range[1], range[3]);
    let y2 = Math.max(range[1], range[3]);

    // Om användaren bara har markerat en enda rad kopiera från raden över


    // Loopa igenom varje kolumn i markeringen
    for (let col = x1; col <= x2; col++) {
        let sourceRow;
        let startTargetRow;

        if (y1 === y2) {
            // Fall 1: Endast EN rad är markerad -> Hämta från raden precis OVANFÖR
            sourceRow = y1 - 1;
            startTargetRow = y1; // Kopiera TILL den markerade raden
            
            // Säkerhetskontroll: Om man står på absolut första raden finns inget ovanför
            if (sourceRow < 0) continue; 
        } else {
            // Fall 2: Flera rader är markerade -> Hämta från den ÖVERSTA markerade raden
            sourceRow = y1;
            startTargetRow = y1 + 1; // Kopiera till alla rader UNDER den första
        }

        // Hämta källvärdet med koordinater (col, row)
        let sourceValue = instance.getValueFromCoords(col, sourceRow);

        // Kopiera ner värdet till målraderna
        for (let row = startTargetRow; row <= y2; row++) {
            // Ange (kolumn, rad, värde) direkt till setValue
            instance.setValueFromCoords(col, row, sourceValue);
        }
    }
}

async function deleteRow(rowIndexArray) {
  if (!currentTableMeta || currentTableMeta.table_type !== "editable") {
    console.warn("Table is not editable, cannot delete row");
    return;
  }
  try {

    const pksToDelete = [];

    // 1. Hämta schemat för att veta vad din Primary Key heter (t.ex. "id")
    const schema = await api(`api/table.php?action=schema&table=${currentTable}`);
    
    const pk = schema.primaryKey || "id";

    // 2. Loopa igenom det antal rader som ska tas bort och hämta deras PK
    for (let i = 0; i < rowIndexArray.length; i++) {
      let currentRowIndex = rowIndexArray[i];
      let rowData = rowToObject(table[0].getRowData(currentRowIndex));

      if (rowData && rowData[pk]) {
        pksToDelete.push(rowData[pk]);

      }
    }
    
    if (pksToDelete.length === 0) return false;

    // 3. Skicka en samlad array med alla ID:n till backend
    const result = await api(`api/table.php?action=delete&table=${currentTable}`, {
      method: "POST",
      body: JSON.stringify({
        [pk]: pksToDelete // Detta skickar t.ex. { id: [12, 13, 14] } till din PHP
      }),
    });

    if (result.success) {
      try {
        isProgrammaticDelete = true; 

        table[0].deleteRow(rowIndexArray[0], rowIndexArray.length);

      } finally {
        isProgrammaticDelete = false; 
      }

      return true; // Tillåt Jspreadsheet att ta bort raderna ur gränssnittet
    } else {
      alert(result.error || "Delete failed");
      return false;
    }
  } catch (error) {
    console.error("Ett fel uppstod vid borttagning:", error);
    return false;
  }
}

async function duplicateRow(rowIndex) {
  if (!currentTableMeta || currentTableMeta.table_type !== "editable") {
    return;
  }

  const rowData = table[0].getRowData(rowIndex);
  if (!rowData) {
    return;
  }

  const pk = currentSchema.primaryKey || "id";
  const newRowData = { ...rowData };
  newRowData[pk] = newRowData[pk] ? `${newRowData[pk]}_copy` : "";

  const result = await api(`api/table.php?action=save&table=${currentTable}`, {
    method: "POST",
    body: JSON.stringify(newRowData),
  });

  if (result.success) {
    const currentData = table[0].getData();
    currentData.push(newRowData);
    table[0].setData(currentData);
  } else {
    alert(result.error || "Duplicate failed");
  }
}

async function addRow(rowNumber) {
  if (!currentTable || currentTableMeta.table_type !== "editable") {
    return;
  }

  const schema = await api(`api/table.php?action=schema&table=${currentTable}`);
  const pk = schema.primaryKey || "id";
  const row = {};

  schema.columns.forEach((col) => {
    if (col.field !== pk) {
      row[col.field] = "";
    }
  });

  const pkValue = prompt(`Enter ${pk}:`);
  if (!pkValue) {
    return;
  }

  row[pk] = pkValue;

  const result = await api(`api/table.php?action=insert&table=${currentTable}`, {
    method: "POST",
    body: JSON.stringify(row),
  });
  if (result.success) {
    console.log("Columns config:", columns);
      table[0].insertRow( rowNumber);

  } else {
    alert(result.error || "Add row failed");
    
  }

}
// === SVG UPLOAD VIA HÖGERKLICK ===
async function uploadSvgForRow(activeWorksheet, rowY) {
    // 1. Hitta källkolumnerna för filnamnet
    const imageFields = ['image', 'platoonimage', 'hqimage'].filter(f => currentFieldNames.includes(f));
    if (imageFields.length === 0) {
        alert("Inga bildkolumner (image, platoonimage, hqimage) hittades i tabellen.");
        return;
    }

    // 2. Samla ALLA filnamn från ALLA bildkolumner, splitta på | och deduplicera
    const allFilenames = new Set();
    imageFields.forEach((field) => {
        const x = currentFieldNames.indexOf(field);
        const val = String(activeWorksheet.getValueFromCoords(x, rowY) || '').trim();
        if (val) val.split('|').map(v => v.trim()).filter(Boolean).forEach(v => allFilenames.add(v));
    });

    if (allFilenames.size === 0) {
        alert("Inga filnamn hittades i bild-kolumnerna för den här raden.\nFyll i minst ett värde i 'image', 'platoonimage' eller 'hqimage' först.");
        return;
    }

    // 3. Om det finns flera filnamn, låt användaren välja vilket
    let chosenFilename;
    if (allFilenames.size === 1) {
        chosenFilename = [...allFilenames][0];
    } else {
        const options = [...allFilenames];
        const choice = prompt(
            `Flera filnamn hittades. Ange ett:\n${options.map((o, i) => `${i+1}. ${o}`).join('\n')}\n\nSkriv namnet direkt (utan .svg):`,
            options[0]
        );
        if (!choice) return;
        chosenFilename = choice.trim();
    }

    // 4. Öppna filväljaren
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.svg,image/svg+xml';

    input.onchange = async function() {
        const file = input.files[0];
        if (!file) return;

        // 5. Klient-validering innan vi ens skickar till servern
        const text = await file.text();

        if (!text.trim().includes('<svg') && !text.trim().includes('<SVG')) {
            alert("Filen verkar inte vara en giltig SVG.");
            return;
        }

        const forbiddenPatterns = [
            /<image\s/i,
            /xlink:href\s*=\s*["']data:/i,
            /href\s*=\s*["']data:image/i,
            /data:image\/(png|jpe?g|gif|webp|bmp)/i,
        ];
        for (const pattern of forbiddenPatterns) {
            if (pattern.test(text)) {
                alert("SVG-filen innehåller inbäddade rasterbilder och är inte ren vektorgrafik.\nLadda upp en ren vektor-SVG.");
                return;
            }
        }

        // 6. Bygg FormData och skicka till PHP
        if (typeof showSyncStatus === 'function') showSyncStatus('saving', `Laddar upp ${chosenFilename}.svg...`);

        const formData = new FormData();
        formData.append('svg', file);
        formData.append('filename', chosenFilename);

        try {
            const result = await fetch(`api/table.php?action=upload_svg&db=${encodeURIComponent(currentDatabase)}`, {
                method: 'POST',
                body: formData,
                // OBS: sätt INTE Content-Type manuellt här — låt webbläsaren sätta multipart-boundary
            });

            const json = await result.json();
            if (json.success) {
                if (typeof showSyncStatus === 'function') {
                    showSyncStatus('synced', `${json.filename} sparad ✓`);
                }
            } else {
                alert("Uppladdning misslyckades:\n" + json.error);
                if (typeof showSyncStatus === 'function') showSyncStatus('error', 'Uppladdning misslyckades');
            }
        } catch (err) {
            console.error("Nätverksfel vid SVG-uppladdning:", err);
            alert("Nätverksfel vid uppladdning.");
            if (typeof showSyncStatus === 'function') showSyncStatus('error', 'Nätverksfel');
        }
    };

    input.click();
}
function highlightAutocompleteSuggestion(suggestBox, index) {
    const items = suggestBox.querySelectorAll('.autocomplete-suggestion');
    items.forEach((item, i) => {
        if (i === index) {
            item.classList.add('active');
            item.scrollIntoView({ block: 'nearest' });
        } else {
            item.classList.remove('active');
        }
    });
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

async function syncSchema() {
  const res = await fetch("api/syncSchema.php");
  const data = await res.json();
  alert(`Added ${data.tables} tables and ${data.columns} columns`);
}
async function rebuildCalculatedTables() {
  const res = await fetch("api/rebuild.php");
  const data = await res.json();
  alert(`rebuilt ${data.results} tables`);
}

function setupToolbar() {
  document.getElementById("rebuild-table").addEventListener("click", rebuildCalculatedTables);
  document.getElementById("syncMetadata").addEventListener("click", syncSchema);
  setupGeneratorToolbarButtons();
}

// Theme toggle: inject minimal CSS, apply theme and persist choice
function applyTheme(theme) {
  if (theme === "dark") {
    document.documentElement.classList.add("dark-mode");
  } else {
    document.documentElement.classList.remove("dark-mode");
  }
}


function showSyncStatus(status, text) {
    const indicator = document.getElementById('sync-indicator');
    if (!indicator) return;

    const icon = indicator.querySelector('.sync-icon');
    const textEl = indicator.querySelector('.sync-text');

    // Rensa gamla klasser
    indicator.className = 'sync-indicator ' + status;
    textEl.innerText = text;

    // Om det blev ett fel eller lyckades, låt meddelandet ligga kvar. 
    // Om det är "synced" (grönt), kan vi valfritt tona ner texten efter 3 sekunder om du vill:
    if (status === 'synced') {
        setTimeout(() => {
            // Behåll den grön, men signalera att systemet är vilande/redo
            textEl.innerText = 'Synked with server';
        }, 3000);
    }
}

// Run this initialization check on page startup (inside your init or window.onload function)
function checkSavedTableOnStartup() {
    const savedTableMeta = localStorage.getItem('jss_active_table_meta');
    
    if (savedTableMeta) {
        try {
            const tableMeta = JSON.parse(savedTableMeta);
            console.log("Found active session table memory. Auto-opening:", tableMeta.table_name);
            
            // Execute your existing table loader function
            if (typeof loadTable === 'function') {
                loadTable(tableMeta);
            }
        } catch (e) {
            console.error("Failed to parse saved startup table metadata:", e);
        }
    }
}


// 3. Funktion för att ladda kolumner i högerpanelen
async function loadRightColumns(type, specificTable = null) {
    const table = specificTable ? specificTable : document.getElementById(`comp-${type}-table`).value;
    const colSelect = document.getElementById(`comp-${type}-column`);
    
    if (!table) {
        colSelect.innerHTML = '<option value="">-- Select Table --</option>';
        if (type === 'target') colSelect.disabled = true;
        return;
    }

    try {
        // Återanvänder ditt existerande table.php schema-case!
        const schema = await api(`api/table.php?action=schema&table=${table}`);
        
        colSelect.innerHTML = '<option value="">-- Select Column --</option>';
        if (schema && schema.columns) {
            schema.columns.forEach(col => {
                const opt = document.createElement('option');
                opt.value = col.field;
                opt.innerText = col.field;
                colSelect.appendChild(opt);
            });
            colSelect.disabled = false;
        }
    } catch (err) {
        console.error("Failed to load sidebar widget columns:", err);
    }
}

// Denna funktion anropas automatiskt allra längst ner inuti din loadTable(tableMeta) funktion!
async function syncComparatorWidgetWithActiveTable(tableMeta) {
    const activeTableInput = document.getElementById('comp-active-table');
    const columnSelect = document.getElementById('comp-active-column');
    if (!activeTableInput || !columnSelect) return;

    // 1. Sätt namnet på den aktiva tabellen i textfältet
    activeTableInput.value = tableMeta.table_name;
    columnSelect.innerHTML = '<option value="">-- Loading Compare Fields --</option>';

    try {
        // 2. Hämta schemat för den aktiva tabellen (innehåller dina nya admin_columns-fält)
        const schema = await api(`api/table.php?action=schema&table=${tableMeta.table_name}`);
        
        columnSelect.innerHTML = '<option value="">-- Select Column --</option>';
        
        if (schema && schema.columns) {
            // Filtrera fram kolumner som faktiskt har ett konfigurerat compare-mål i databasen
            const compareColumns = schema.columns.filter(col => col.parent_table_compare && col.parent_column_compare);
            
            if (compareColumns.length === 0) {
                columnSelect.innerHTML = '<option value="">No compare metadata found</option>';
                return;
            }

            compareColumns.forEach(col => {
                const opt = document.createElement('option');
                // Spara kolumnnamnet som value (t.ex. 'code')
                opt.value = col.field;
                // Visa ett snyggt namn i dropdownen (t.ex. "code (vs formation_db_source)")
                opt.innerText = `${col.field} (vs ${col.parent_table_compare})`;
                columnSelect.appendChild(opt);
            });
        }
    } catch (err) {
        console.error("Failed to load smart compare metadata fields:", err);
        columnSelect.innerHTML = '<option value="">Error loading fields</option>';
    }
}

// Kör den automatiska jämförelsen mot din uppdaterade backend
async function runSmartSidebarComparison() {
    const srcTable = currentTable; // Den aktiva tabellen på skärmen
    const srcCol = document.getElementById('comp-active-column').value;
    const resultBox = document.getElementById('comp-result-box');

    if (!srcTable || !srcCol) {
        alert("Please select a column to verify first.");
        return;
    }

    resultBox.value = "Analyzing database integrity... Please wait...";

    try {
        // Vi anropar det nya smarta caset i din backend och skickar bara med tabell och kolumn!
        const response = await fetch(`api/compareColumns.php?action=smart_compare&table=${srcTable}&column=${srcCol}`);
        const text = await response.text();
        resultBox.value = text;
    } catch (err) {
        resultBox.value = "A network error occurred during analysis.";
        console.error(err);
    }
}

function compensateForFrozenOverlap(cellElement, container, activeX, activeY) {

    if (!cellElement || !container) {
        return;
    }

    const FROZEN_DATA_COLUMNS = 1;
    if (activeX < FROZEN_DATA_COLUMNS) {
        return;
    }

    const firstColEl = container.querySelector('.jss_worksheet > tbody > tr > td:first-child');
    const secondColEl = container.querySelector('.jss_worksheet > tbody > tr > td:nth-child(2)');
    const headerRowEl = container.querySelector('.jss_worksheet thead tr');

    const frozenLeftWidth = (firstColEl ? firstColEl.offsetWidth : 0) + (secondColEl ? secondColEl.offsetWidth : 0);
    const frozenTopHeight = headerRowEl ? headerRowEl.offsetHeight : 0;

    const containerRect = container.getBoundingClientRect();
    const cellRect = cellElement.getBoundingClientRect();

    const leftBoundary = containerRect.left + frozenLeftWidth;

    if (cellRect.left < leftBoundary) {
        const adjustment = leftBoundary - cellRect.left;
        container.scrollLeft -= adjustment;
    }
    const topBoundary = containerRect.top + frozenTopHeight;

    if (activeY > 0 && cellRect.top < topBoundary) {
        const adjustment = topBoundary - cellRect.top;
        container.scrollTop -= adjustment;
    }
}

function runWithLoadingIndicator(workFn, loadingMessage = "Processing...", doneMessage = "Done ✓") {
    if (typeof showSyncStatus === 'function') {
        showSyncStatus('saving', loadingMessage);
    }

    // Liten fördröjning (0ms räcker) så webbläsaren hinner MÅLA upp
    // loading-statusen innan den tunga synkrona koden blockerar tråden
    setTimeout(() => {
        const start = performance.now();

        workFn();

        const elapsed = (performance.now() - start).toFixed(0);
        console.log(`${loadingMessage} took ${elapsed}ms`);

        if (typeof showSyncStatus === 'function') {
            showSyncStatus('synced', doneMessage);
        }
    }, 10);
}

function setupGeneratorToolbarButtons() {
    const activeWorksheet = () => (table && Array.isArray(table)) ? table[0] : table;

    document.getElementById('gen-shortid').onclick = () => generateUniqueShortID(activeWorksheet(), null, null, null, null);


    document.getElementById('gen-run-all').onclick = async () => {
        if (typeof showSyncStatus === 'function') showSyncStatus('saving', 'Running all generators...');
        try {
            await generateUniqueShortID(activeWorksheet(), null, null, null, null);

            const runnableConfigs = activeGeneratorConfigs
                .filter(c => c.include_in_run_all)
                .sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));

            for (const config of runnableConfigs) {
                await runGeneratorConfig(config, activeWorksheet(), null, null);
            }

            if (typeof showSyncStatus === 'function') showSyncStatus('synced', 'All generators completed ✓');
        } catch (err) {
            console.error("Run All failed:", err);
            if (typeof showSyncStatus === 'function') showSyncStatus('error', 'Run All failed — see console');
        }
    };
}
function updateGeneratorToolbarVisibility() {
    const runnableConfigs = activeGeneratorConfigs
        .filter(c => c.include_in_run_all)
        .sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));
    document.getElementById('gen-run-all').style.display = Array.isArray(runnableConfigs)? 'inline-flex' : 'none';
    if (currentTable === 'platoonconfigdb_source') {
        document.getElementById('gen-shortid').style.display = Array.isArray(runnableConfigs)? 'inline-flex' : 'none';
    }
}

function renderGeneratorToolbarButtons() {
    const container = document.getElementById('generator-toolbar-buttons');
    if (!container) return;
    container.innerHTML = '';

    const activeWs = Array.isArray(table) ? table[0] : table;


    // Separator
    if (activeGeneratorConfigs.length > 0) {
        const sep = document.createElement('span');
        sep.style.cssText = 'width:1px;height:20px;background:var(--border);margin:0 4px;';
        container.appendChild(sep);
    }


    activeGeneratorConfigs
        .sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0))
        .forEach((config) => {
            const btn = document.createElement('button');
            btn.title = config.label;
            btn.innerText = config.icon || '⚙️';
            btn.onclick = () => runGeneratorConfig(config, Array.isArray(table) ? table[0] : table, null, null);
            container.appendChild(btn);
        });
}

function init() {

  loadTables();
  setupToolbar();
  
}

document.addEventListener('keydown', function(e) {
    // Kontrollera om användaren trycker Ctrl + D eller Cmd + D (Mac)
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'd') {
        
        // Säkerställ att vi har en aktiv Jspreadsheet-tabell på sidan
        // table[0] eller din globala variabel för instansen
        if (typeof table !== 'undefined') {
            
            // Blockera webbläsarens "Lägg till bokmärke"
            e.preventDefault(); 
            
            // Kör funktionen (använd table[0] eller table beroende på hur din instans sparas)
            console.log("Ctrl + D detected, attempting to fill down", table);
            let instance = Array.isArray(table) ? table[0] : table;
            if (instance && typeof instance.fillDown !== 'function') {
                fillDown(instance);
            }
        }
    }

    // 1. Kontrollera om användaren trycker Alt + Nedpil
    if (e.altKey && e.key === 'ArrowDown') {
        
        if (typeof table !== 'undefined' && table) {
            
            // --- FIXEN: Hämta kalkylbladet på exakt samma sätt som din fungerande getRowData gör (index 0) ---
            const activeWorksheet = Array.isArray(table) ? table[0] : (table.worksheets ? table.worksheets[0] : table);
            
            if (!activeWorksheet) return;

            // 2. Hämta det senaste sparade cellindexet från ditt egna fungerande minne
            let savedState = localStorage.getItem('jss_state_' + currentTable);
            if (!savedState) return;

            savedState = JSON.parse(savedState);
            if (!savedState.cursor || savedState.cursor.x1 === undefined) return;

            // Blockera webbläsarens standardbeteende för Alt+Nedpil
            e.preventDefault();

            // Hämta koordinaterna för cellen där markören står just nu
            const x = parseInt(savedState.cursor.x1);
            const y = parseInt(savedState.cursor.y1);

            // 3. Hämta kolumninställningarna på ett felsäkert sätt från din version
            const columns = typeof activeWorksheet.getConfig === 'function' 
                            ? activeWorksheet.getConfig().columns 
                            : (activeWorksheet.options ? activeWorksheet.options.columns : null);

            // 4. Om vi hittade kolumnerna, kontrollera om det är en dropdown
            if (columns && columns[x] && (columns[x].type === 'dropdown' || columns[x].editor === 'list' || columns[x].type === 'list')) {
                
                // 5. Hitta cellens HTML-element i DOM:en med hjälp av din fungerande .jss_worksheet-klass
                const cellElement = document.querySelector(`.jss_worksheet tbody tr[data-y="${y}"] td[data-x="${x}"]`);

                if (cellElement) {
                    console.log(`Öppnar dropdown för cell X: ${x}, Y: ${y} via Alt + Nedpil`);
                    
                    // Skapa och skicka ett virtuellt dubbelklick direkt till cellen
                    const dblClickEvent = new MouseEvent('dblclick', {
                        bubbles: true,
                        cancelable: true,
                        view: window
                    });
                    cellElement.dispatchEvent(dblClickEvent);
                }
            } else {
                console.warn("Kolumnen är inte konfigurerad som en dropdown, eller så kunde kolumninställningarna inte läsas ut.");
            }
        }
    }

    const suggestBox = document.getElementById('custom-autocomplete-box');
    if (!suggestBox) return;

    const isVisible = suggestBox.style.display === 'block';
    const items = suggestBox.querySelectorAll('.autocomplete-suggestion');

    if (isVisible && items.length > 0) {
        if (e.key === 'ArrowDown') {
            e.preventDefault(); // hindra kalkylbladet från att flytta markören nedåt
            activeSuggestionIndex = (activeSuggestionIndex + 1) % items.length;
            highlightAutocompleteSuggestion(suggestBox, activeSuggestionIndex);
            return;
        }

        if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeSuggestionIndex = (activeSuggestionIndex - 1 + items.length) % items.length;
            highlightAutocompleteSuggestion(suggestBox, activeSuggestionIndex);
            return;
        }

        if ((e.key === 'Enter' || e.key === 'Tab') && activeSuggestionIndex >= 0) {
            e.preventDefault();
            const selectedText = items[activeSuggestionIndex].innerText;
            const input = e.target;
            input.value = selectedText;
            input.dispatchEvent(new Event('input', { bubbles: true }));
            suggestBox.style.display = 'none';
            activeSuggestionIndex = -1;
            return; // OBS: kräver ev. ett andra tryck för att faktiskt flytta/committa cellen, se kommentar nedan
        }
    }

    if (e.key === 'Enter' || e.key === 'Escape' || e.key === 'Tab') {
        suggestBox.style.display = 'none';
        activeSuggestionIndex = -1;
    }
});

document.addEventListener('input', function(e) {
    const input = e.target;
    // Kontrollera att det är en aktiv textruta inuti ditt kalkylblad
    if (!input || (input.tagName !== 'INPUT' && input.tagName !== 'TEXTAREA')) return;
    if (!input.closest('.jss_worksheet') && !input.closest('.jexcel')) return;

    const activeWorksheet = (table && Array.isArray(table)) ? table[0] : table;
    if (!activeWorksheet) return;

    // Hämta var markören står just nu från ditt localStorage-minne
    let savedState = localStorage.getItem('jss_state_' + currentTable);
    if (!savedState) return;
    savedState = JSON.parse(savedState);
    if (!savedState.cursor || savedState.cursor.x1 === undefined) return;

    const x = parseInt(savedState.cursor.x1);
    const y = parseInt(savedState.cursor.y1);
    const typedText = input.value.toLowerCase().trim();

    const suggestBox = document.getElementById('custom-autocomplete-box');
    if (!suggestBox) return;

    // --- NEW: stäng av autocomplete helt för dropdown-kolumner ---
    const columnDef = activeWorksheet.options.columns ? activeWorksheet.options.columns[x] : null;
    if (columnDef && columnDef.type === 'dropdown') {
        suggestBox.style.display = 'none';
        return;
    }

    // Om användaren har skrivit mindre än 2 tecken, göm rutan
    if (typedText.length < 2) {
        suggestBox.style.display = 'none';
        return;
    }

    // Skanna kolumnen efter unika historiska värden
    const uniqueValues = new Set();
    const totalRows = activeWorksheet.options.data ? activeWorksheet.options.data.length : activeWorksheet.rows.length;

    for (let rowY = 0; rowY < totalRows; rowY++) {
        if (rowY === y) continue; // Hoppa över cellen vi skriver i just nu
        const val = String(activeWorksheet.getValueFromCoords(x, rowY) || '').trim();
        // Filtrera fram värden som matchar det användaren börjat skriva på
        if (val && val.toLowerCase().startsWith(typedText)) {
            uniqueValues.add(val);
        }
    }

    // Om vi hittade matchande förslag, bygg listan och visa den
    if (uniqueValues.size > 0) {
        suggestBox.innerHTML = '';
        
        uniqueValues.forEach(text => {
            const div = document.createElement('div');
            div.className = 'autocomplete-suggestion';
            div.innerText = text;
            
            // När man klickar på ett förslag, tryck in det i textrutan och stäng
            div.onmousedown = function(event) {
                event.preventDefault(); // Hindrar textrutan från att tappa fokus för tidigt
                input.value = text;
                suggestBox.style.display = 'none';
                
                // Skicka ett virtuellt input-event så att Jspreadsheet förstår att texten ändrats
                input.dispatchEvent(new Event('input', { bubbles: true }));
            };
            
            suggestBox.appendChild(div);
        });

        // POSITIONERING: Mät exakt var textrutan ligger på skärmen just nu
        const rect = input.getBoundingClientRect();
        
        // Placera vår externa ruta exakt under textrutan (plus tar hänsyn till scroll)
        suggestBox.style.left = (rect.left + window.scrollX) + 'px';
        suggestBox.style.top = (rect.bottom + window.scrollY) + 'px';
        suggestBox.style.width = rect.width + 'px';
        suggestBox.style.display = 'block';
    } else {
        suggestBox.style.display = 'none';
    }
});

// Göm förslagsrutan direkt när användaren klickar utanför eller trycker Enter/Esc
document.addEventListener('mousedown', function(e) {
    if (!e.target.classList.contains('autocomplete-suggestion')) {
        const box = document.getElementById('custom-autocomplete-box');
        if (box) box.style.display = 'none';
    }
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' || e.key === 'Escape' || e.key === 'Tab') {
        const box = document.getElementById('custom-autocomplete-box');
        if (box) box.style.display = 'none';
    }
});

document.addEventListener("DOMContentLoaded", function() {


    const periodSelect = document.getElementById("period-select");
    if (periodSelect) {
        // Kontrollera om det redan finns en sparad databas i localStorage från förra sessionen
        const savedDb = localStorage.getItem('jss_active_database');
        if (savedDb) {
            periodSelect.value = savedDb;
            currentDatabase = savedDb;
            // Kör loadTables() automatiskt för att ladda tabellerna för den sparade databasen
            loadTables();
        }

        // När användaren byter period i dropdown-menyn
        periodSelect.addEventListener("change", function() {
            currentDatabase = this.value;
            
            // Spara valet i webbläsarens minne så man slipper välja varje gång man laddar om sidan
            localStorage.setItem('jss_active_database', currentDatabase);

            console.log(`Database partition switched to: ${currentDatabase}`);

            if (!currentDatabase) {
                // Om användaren valde "-- Choose Period --", töm listorna
                document.getElementById("table-list").innerHTML = "";
                if (table) jspreadsheet.destroy(document.getElementById('table'));
                document.getElementById("current-table").innerText = "Select table";
                return;
            }

            // Slå om din nya statusindikator på skärmen
            if (typeof showSyncStatus === 'function') {
                showSyncStatus('saving', 'Switching database context...');
            }

            // Ladda om sidomenyns tabeller fräscht från den nya databasen!
            loadTables();
            
            // Töm det aktiva kalkylbladet på skärmen så man inte ser gammal data från förra perioden
            if (table) {
                jspreadsheet.destroy(document.getElementById('table'));
                document.getElementById("current-table").innerText = "Select table";
                currentTableMeta = null;
                currentTable = "";
            }
        });
    }
    const sortBtn = document.getElementById("sort-table-column");

    if (sortBtn) {
        sortBtn.addEventListener("click", function() {
            console.log("Sorteringsknapp i toolbar klickad!");

            // 1. HÄR ÄR FIXEN: Hämta det första kalkylbladet ur arrayen (precis som din getRowData gör!)
            const worksheet = (table && Array.isArray(table)) ? table[0] : table;
            
            if (!worksheet) {
                console.error("Kunde inte hitta kalkylbladsinstansen (table/worksheet saknas)");
                return;
            }

            // 2. Hämta det senaste sparade tillståndet från localStorage 
            let savedState = localStorage.getItem('jss_state_' + currentTable);
            if (!savedState) {
                alert("Klicka på en cell i den kolumn du vill sortera först!");
                return;
            }

            savedState = JSON.parse(savedState);
            if (!savedState.cursor || savedState.cursor.x1 === undefined) {
                alert("Klicka på en cell i den kolumn du vill sortera först!");
                return;
            }

            let columnNumber = parseInt(savedState.cursor.x1); 

            // 3. Kontrollera om metoden orderBy och getConfig finns på worksheet-objektet
            if (!isNaN(columnNumber) && typeof worksheet.orderBy === 'function') {
                
                // Hämta kolumninställningarna via kalkylbladets riktiga metoder
                const columns = typeof worksheet.getConfig === 'function' 
                                ? worksheet.getConfig().columns 
                                : worksheet.options.columns;

                if (!columns || !columns[columnNumber]) {
                    console.error("Kunde inte hitta kolumnkonfigurationen för index", columnNumber);
                    return;
                }
                
                // Växla riktning (0 = stigande/ASC, 1 = fallande/DESC)
                if (columns[columnNumber].currentSortDirection === undefined) {
                    columns[columnNumber].currentSortDirection = 0;
                } else {
                    columns[columnNumber].currentSortDirection = columns[columnNumber].currentSortDirection === 0 ? 1 : 0;
                }

                let nextDirection = columns[columnNumber].currentSortDirection;
                
                console.log(`Sorterar kolumn index ${columnNumber} via table[0].orderBy till riktning: ${nextDirection}`);
                
                // 4. Kör Jspreadsheets inbyggda och stabila sortering direkt på rätt objekt!
                worksheet.orderBy(columnNumber, nextDirection);
                setTimeout(() => {
                    if (typeof window.applyLiveDatabaseFormatting === 'function') {
                        runWithLoadingIndicator(
                            () => window.applyLiveDatabaseFormatting(),
                            "Applying formatting...",
                            "Formatting applied ✓"
                        );
                    }
                }, 150);
                    
            } else {
                console.error("orderBy-metoden saknas på worksheet-objektet. Kontrollera tillgängliga metoder:", Object.keys(worksheet));
            }
        });
    }

    const toggleBtn = document.getElementById("sidebar-toggle");
    const sidebar = document.querySelector(".sidebar");

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener("click", function() {
            // Växla klassen .active på både knappen (för X-animeringen) och sidomenyn
            toggleBtn.classList.toggle("active");
            sidebar.classList.toggle("active");
        });
    }

    const reloadBtn = document.getElementById("reload-table");

    if (reloadBtn) {
        reloadBtn.addEventListener("click", function() {
            // Check if a table is currently active
            if (!currentTableMeta) {
                alert("Please select a table from the sidebar first!");
                return;
            }

            console.log(`Reloading active table grid: ${currentTableMeta}`);



            // Fire your existing loadTable method to pull fresh rows from table.php
            loadTable(currentTableMeta);
            
        });
    }


    // --- FIX: PINNING FOR THE RIGHT SIDEBAR ---
    // Target your exact HTML id="right-sidebar-toggle"
    const rightToggleBtn = document.getElementById("right-sidebar-toggle");
    const sidebarRight = document.querySelector(".sidebar-right");

    if (rightToggleBtn && sidebarRight) {
        rightToggleBtn.addEventListener("click", function() {
            console.log("Pinning/Unpinning the right sidebar widget tools panel!");
            
            // Toggling the .active class locks the sidebar wide open via your CSS
            rightToggleBtn.classList.toggle("active");
            sidebarRight.classList.toggle("active");
        });
    }

    // Run your comparator target table loader on startup
    if (typeof populateTargetTablesDropdown === 'function') {
        populateTargetTablesDropdown();
    }

    const smartCompareBtn = document.getElementById("run-smart-compare");
    if (smartCompareBtn) {
        smartCompareBtn.addEventListener("click", runSmartSidebarComparison);
    }

    init();
});