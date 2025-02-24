function saveRow(row) { 
    const rowId = row.querySelector(".save-btn").getAttribute("data-id");
    const updatedData = {
        id: rowId,
        name: row.querySelector(`#name-${rowId} .nameField`).textContent,
        event: row.querySelector(`#event-${rowId}`).textContent
    };

    fetch("save_row.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(updatedData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {

            row.querySelector(".save-btn").style.display = "none"; // Hide the save button after saving
            if (row) {
                row.classList.add("row-updated");
                setTimeout(() => {
                    row.classList.remove("row-updated");
                }, 5000); // Highlight for 2 seconds
            }
        } else {
            alert("Failed to save row.");
        }
    });
}

function displayItem(item) {

    const imageArray = item.image.split('|');
    const MSI = item.MOTIVATION.charAt(0) + "-" + item.SKILL.charAt(0) + "-" + item.IS_HIT_ON.charAt(0);
    const platoonTitle = document.createElement('div');
    const platoonMSI = document.createElement('div');
    platoonMSIdiv = document.createElement('div');
    platoonTitle.classList.add("title");
    platoonMSI.classList.add("MSI");
    platoonTitle.innerHTML= `<div>${item.title}</div><div>${item.code}</div>`;

    platoonMSIdiv.innerHTML = MSI;
    platoonMSI.appendChild(platoonMSIdiv);
    const imgDiv = document.createElement('div');
    imgDiv.classList.add("platoonImages");

    imageArray.forEach(image => {
        const imgElement = document.createElement('img');
        imgElement.src = `img/${image}.svg`; // Adjust the path as needed
        imgDiv.appendChild(imgElement);
    });
    return {
        imgDiv,
        platoonTitle,
        platoonMSI
    };
}



document.addEventListener("DOMContentLoaded", function () {
    

    
    const editSelectedModal = document.getElementById("editSelectedModal");
    const closeSelectedModal = editSelectedModal.querySelector(".close");
    const editModal = document.getElementById("editModal");
    const closeModal = document.querySelector(".modal .close");
    const editForm = document.getElementById("editForm");

    const editSelectedEventInput = document.getElementById("editSelectedEventInput");
    const editNamePrefixInput = document.getElementById("editNamePrefix");
    const deleteButton = document.getElementById("deleteButton");
    const eventDatalist = document.getElementById("eventList");
    const editSelectedButton = document.getElementById("editSelectedButton");
    const editSelectedForm = document.getElementById("editSelectedForm");
    const selectAllCheckbox = document.getElementById("selectAll");
    const rowCheckboxes = document.querySelectorAll(".row-checkbox");
    const deleteForm = document.getElementById("deleteForm");
    const duplicateSelectedButton = document.getElementById("duplicateSelectedButton");

    const tableBody = document.querySelector("#listTable tbody");
    // Set up the MutationObserver
    const observer = new MutationObserver((mutationsList) => {
        for (const mutation of mutationsList) {
            if (mutation.type === "childList" || mutation.type === "subtree") {
                initializeFilters();
                initializeSorters();
                initializeGlobalFilterInput();
                initiateRowSaveButtons();
                initializeContentEditableListeners();
                initiateEditButtons();
                break; // Only need to reinitialize once per batch
            }
            if (mutation.type === "characterData" ||  mutation.type === "attributes") {
                initializeFilters();
                initializeSorters();
                initializeGlobalFilterInput();
            }
        }
    });
    // Observer configuration
    const observerConfig = {
        childList: true,
        attributes: true,
        characterData: true,
        subtree: true
    };
    // Start observing the table body for child node changes
    observer.observe(tableBody, observerConfig);
    const editRowHeight = document.querySelector(".editRow").offsetHeight;
    document.documentElement.style.setProperty("--edit-row-height", `${editRowHeight}px`);
    // Update on page load and resize

    // Utility to temporarily disable the observer
    function withObserverDisabled(callback) {
        observer.disconnect(); // Stop observing
        callback(); // Perform the desired operation
        observer.observe(tableBody, observerConfig); // Resume observing
    }
    function handleFocus() {
        console.log("Disabling observer for contenteditable.");
        observer.disconnect(); // Temporarily disable the observer
    }
    
    function handleBlur() {
        console.log("Re-enabling observer after contenteditable.");
        observer.observe(document.querySelector("#listTable tbody"), observerConfig); // Re-enable the observer
    }

    const updateStickyPosition = () => {
        const editRow = document.querySelector(".editRow");
        const header = document.querySelector(".header");
        const root = document.documentElement;
        const isMobile = window.innerWidth <= 1100;
       
       
        if (isMobile) {
            let headerVisibleHeight = header.getBoundingClientRect().bottom;
            if (headerVisibleHeight >0 ) {
                document.getElementById("chenckboxHeaderCell").style.position="unset";
            } else {
                document.getElementById("chenckboxHeaderCell").style="";
            }
            let editRowHeight = editRow.offsetHeight + ((headerVisibleHeight >0)? headerVisibleHeight : 0);
    
            root.style.setProperty("--edit-row-height", `${editRowHeight}px`);
        }
    };
    updateStickyPosition();
    window.addEventListener("resize", updateStickyPosition);
    window.addEventListener("scroll", updateStickyPosition); 

    function initializeSorters() {
        const listTable = document.getElementById("listTable");
        const headersSort  = listTable.querySelectorAll("thead .sort");
        const headers = listTable.querySelectorAll("thead th");
        // Sorting functionality for each header column
        headersSort.forEach((header) => {
            header.addEventListener("click", () => {
                withObserverDisabled(() => {
                    const tbody = listTable.querySelector("tbody");
                    const rows = Array.from(tbody.querySelectorAll("tr"));
                    const columnIndex = Array.from(header.closest('tr').querySelectorAll("th")).indexOf(header.closest("th")); // Dynamically get column index         
                    const isAscending = header.getAttribute("data-order") === "asc";
                    header.setAttribute("data-order", isAscending ? "desc" : "asc");
                    // Remove arrows from all headers
                    headers.forEach(h => h.querySelector(".sort-arrow")?.remove());
    
                    // Add arrow to the clicked header
                    const arrow = document.createElement("span");
                    arrow.className = "sort-arrow";
                    arrow.textContent = isAscending ? " ▼" : " ▲"; // Down for descending, up for ascending
                    header.appendChild(arrow);
                    rows.sort((rowA, rowB) => {
                        const cellA = rowA.children[columnIndex];
                        const cellB = rowB.children[columnIndex];
            
                        // Extract content outside the .MSI div
                        const textA = Array.from(cellA?.childNodes || [])
                            .filter(node => !(node.classList && node.classList.contains("MSI"))) // Exclude .MSI
                            .map(node => node.textContent?.trim() || "") // Extract text
                            .join(" ");
    
                        const textB = Array.from(cellB?.childNodes || [])
                            .filter(node => !(node.classList && node.classList.contains("MSI"))) // Exclude .MSI
                            .map(node => node.textContent?.trim() || "") // Extract text
                            .join(" "); // Combine text into a single string
            
                        // Perform the sort
                        return isAscending ? textA.localeCompare(textB) : textB.localeCompare(textA);
                    });
                    rows.forEach(row => tbody.appendChild(row));
    
                });
    
            });
        });
    }
    
    function initializeFilters() {
        const listTable = document.getElementById("listTable");
        const headers = listTable.querySelectorAll("thead th");
    
        // Clear existing filter inputs to avoid duplicates
        const filterInputs = listTable.querySelectorAll("select.filter-input");
        filterInputs.forEach(input => input.innerHTML = '<option value="all">All</option>');
    
        headers.forEach((header, columnIndex) => {
            
                const filterInput = header.querySelector("select.filter-input");
                if (!filterInput) return;
    
                const uniqueValues = new Set();
                listTable.querySelectorAll("tbody tr").forEach(row => {
                    const cell = row.children[columnIndex];
                    if (cell) {
                        let cellText = Array.from(cell.childNodes)
                            .filter(node => !(node.classList && node.classList.contains("MSI"))) // Skip MSI nodes
                            .map(node => node.textContent.trim())
                            .join(" ");
                        uniqueValues.add(cellText);
                    }
                });
                // Convert Set to Array and sort it
                const sortedUniqueValues = Array.from(uniqueValues).sort((a, b) => a.localeCompare(b, undefined, { sensitivity: 'base' }));

                // Use sortedUniqueValues as needed, e.g., populating a dropdown
                sortedUniqueValues.forEach(value => {
                    const option = document.createElement("option");
                    option.textContent = value;
                    option.value = value.toLowerCase().replace(/\s+/g, "-");
                    filterInput.appendChild(option);
                });

    
                // Reapply filtering on dropdown change
                filterInput.addEventListener("change", () => {
                    withObserverDisabled(() => {
                    const filterValue = filterInput.value;
                    const rows = listTable.querySelectorAll("tbody tr");
                    rows.forEach(row => {
                        const cell = row.children[columnIndex];
                        if (cell) {
                            let cellText = Array.from(cell.childNodes)
                                .filter(node => !(node.classList && node.classList.contains("MSI"))) // Exclude MSI elements
                                .map(node => node.textContent.trim())
                                .join(" ").toLowerCase().replace(/\s+/g, "-");
                            row.style.display = (filterValue === "all" || cellText.includes(filterValue)) ? "" : "none";
                        }
                    });
                    console.log("filters reinitiated");
                });
            });
        });
    }
    
    function initializeGlobalFilterInput() {
        // Global text filter for all table rows
        const listTable = document.getElementById("listTable");
        const globalFilterInput = document.getElementById("filterInput");
        globalFilterInput.addEventListener("input", () => {
            const filterValue = globalFilterInput.value.toLowerCase();
            const rows = listTable.querySelectorAll("tbody tr");
            rows.forEach(row => {
                const isVisible = Array.from(row.querySelectorAll("td")).some(cell =>
                    cell.textContent.toLowerCase().includes(filterValue)
                );
                row.style.display = isVisible ? "" : "none";
            });
        });
    }
    
    
    function initiateRowSaveButtons() {
        const rows = document.querySelectorAll("tbody tr");
        const updatedRows = new Set();
        const saveAllBtn = document.getElementById("saveAllBtn");
        rows.forEach(row => {
            const cells = row.querySelectorAll("[contenteditable]");
            const saveButton = row.querySelector(".save-btn");
    
            // Store initial cell values
            const originalValues = Array.from(cells).map(cell => cell.textContent.trim());
    
            // Add event listener to each editable cell
            cells.forEach((cell, index) => {
                cell.addEventListener("input", () => {
                    const currentValues = Array.from(cells).map(c => c.textContent.trim());
    
                    // Check if any value has changed compared to the original
                    const isEdited = currentValues.some((value, i) => value !== originalValues[i]);
                    updatedRows.add(row); // Track the updated row
                    // Show or hide the save button based on changes
                    saveButton.style.display = isEdited ? "inline-block" : "none";
                    saveAllBtn.style.display = updatedRows.size > 0 ? "inline-block" : "none";
                });
            });
    
            // Save All button functionality
            saveAllBtn.addEventListener("click", () => {
                updatedRows.forEach((row) => saveRow(row));
                updatedRows.clear(); // Clear the list of updated rows
                saveAllBtn.style.display = "none"; // Hide "Save All" button
            });
    
            // Save button functionality
            saveButton.addEventListener("click", () => {
                updatedRows.delete(row); // Remove from the updated list
                saveAllBtn.style.display = updatedRows.size > 0 ? "inline-block" : "none";
                
                const rowId = saveButton.getAttribute("data-id");
                const updatedData = {
                    id: rowId,
                    name: row.querySelector(`#name-${rowId} .nameField`).textContent,
                    event: row.querySelector(`#event-${rowId}`).textContent
                };
    
                fetch("save_row.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(updatedData)
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (row) {
                                row.classList.add("row-updated");
                                setTimeout(() => {
                                    row.classList.remove("row-updated");
                                }, 5000); // Highlight for 2 seconds
                            }
                            saveButton.style.display = "none"; // Hide the save button after saving
                        } else {
                            alert("Failed to save row.");
                        }
                    });
            });
        });
    }
    
    function initializeContentEditableListeners() {
        const contentEditables = document.querySelectorAll("#listTable tbody [contenteditable=true]");
        
        contentEditables.forEach(editable => {
            // Remove existing listeners to avoid duplicate handlers
            editable.removeEventListener("focus", handleFocus);
            editable.removeEventListener("blur", handleBlur);
    
            // Attach fresh listeners
            editable.addEventListener("focus", handleFocus);
            editable.addEventListener("blur", handleBlur);
        });
    }

    function initiateEditButtons() {
        const editButtons = document.querySelectorAll(".edit-button");
        const entryIdInput = document.getElementById("entryId");
        const editNameInput = document.getElementById("editName");
        const editEventInput = document.getElementById("editEvent");
        const eventDatalist = document.getElementById("eventList");
        // Show modal and populate fields on "Edit" button click
        editButtons.forEach(button => {
            button.addEventListener("click", () => {
                const entryId = button.dataset.id;
                const row = button.closest("tr");
                const currentName = document.getElementById(`name-${entryId}`).textContent.trim();
                const currentEvent = document.getElementById(`event-${entryId}`).textContent.trim();
                entryIdInput.value = entryId;
                editNameInput.value = currentName;
                editEventInput.value = currentEvent;
                const rowId = row.id;
                const codes = JSON.parse(row.dataset.codes); // Retrieve unique platoon codes as an array
                const boxes = JSON.parse(row.dataset.boxes);
                const formations  = JSON.parse(row.dataset.formations);
                const platoonStats = document.getElementById('platoonStats');
    
                
                platoonStats.innerHTML  = "";
                fetch('fetch_data.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: rowId, codes: codes })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            let currentFormationRow = null;
                            let supportFormationRow = null; // Row for support formation
                            let isSupport = false; // Flag to indicate support section
    
                            Object.entries(boxes).forEach(([boxKey, boxCode]) => {
                                                
                                if (boxKey.startsWith('Sup')||boxKey.startsWith('Black')||boxKey.startsWith('CdPl')) {
                                    if (!isSupport) {
                                        // Create the "Support Formation" div if it doesn't exist yet
                                        supportFormationRow = document.createElement('div');
                                        supportFormationRow.classList.add("Formation");
                                        const headerDiv = document.createElement('div');
                                        headerDiv.classList.add("formationHeader");
                                        headerDiv.textContent = "Support";
                                        platoonStats.appendChild(headerDiv);
                                        platoonStats.appendChild(supportFormationRow);
                                        isSupport = true; // Set support flag to true
                                    }
    
                                    // Add the support box to the support formation
                                    const supportBox = document.createElement('div');
                                    supportBox.classList.add("box");
                                    // Add relevant stats to the support box
                                    data.items.forEach(statCell => {
                                        if (boxCode === statCell.code) {
                                            const { imgDiv, platoonTitle, platoonMSI } = displayItem(statCell);
                                            supportBox.appendChild(platoonTitle);
                                            supportBox.appendChild(platoonMSI);
                                            supportBox.appendChild(imgDiv);
                                        }
                                    });
                                    supportFormationRow.appendChild(supportBox);
                                } else if (!isSupport) {
    
                                    // If the box represents a formation (no '-' in the key), create a new formation row
                                    if (!boxKey.includes('-')) {
                                        currentFormationRow = document.createElement('div');
                                        currentFormationRow.classList.add("Formation");
                                        // Find and set the title from the formations list
                                        const matchingFormation = Object.values(formations).find(
                                            formation => formation.code === boxCode
                                        );
                                        if (matchingFormation) {
                                            const headerDiv   = document.createElement('div');
                                            headerDiv.classList.add("formationHeader");
                                            headerDiv.textContent = matchingFormation.title;
                                            platoonStats.appendChild(headerDiv);
                                        }
                                        if (!boxKey.includes('Book')) {
                                            platoonStats.appendChild(currentFormationRow);
                                        }
                                        
                                    } else if (currentFormationRow) {
                                        const boxRow  = document.createElement('div');
                                        boxRow .classList.add("box");
                                        data.items.forEach(statCell => {
                                            if ((boxCode == statCell.code)) {
                                                const { imgDiv, platoonTitle, platoonMSI } = displayItem(statCell);
                                                boxRow.appendChild(platoonTitle);
                                                boxRow.appendChild(platoonMSI);
                                                boxRow.appendChild(imgDiv);
                                            }
                                        });
                                        currentFormationRow.appendChild(boxRow);
                                    }
                                }
                            });
    
                        } else {
                            console.error(`Failed to fetch data for Row ${rowId}:`, data.error);
                        }
                    })
                    .catch(error => {
                        console.error('AJAX Error:', error);
                    });
                // Populate datalist with all event options from the table
                eventDatalist.innerHTML = ""; // Clear previous options
                document.querySelectorAll("td[id^='event-']").forEach(eventCell => {
                    const eventValue = eventCell.textContent.trim();
    
                    if (!Array.from(eventDatalist.options).some(option => option.value === eventValue) && eventValue != "0") {
    
                        const option = document.createElement("option");
                        option.value = eventValue;
                        eventDatalist.appendChild(option);
                    }
                });
                editModal.style.display = "block";
            });
        });
    }

    initializeFilters();
    initializeSorters();
    initializeGlobalFilterInput();
    initiateRowSaveButtons();
    initializeContentEditableListeners();
    initiateEditButtons();

    
    document.querySelectorAll(".set-list-number").forEach(link => {
        link.addEventListener("click", (event) => {
            event.preventDefault();
            

            const listId = link.getAttribute("data-id");
            const url = link.getAttribute("href");
            

            fetch("set_list_number.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ listId })
            })
            .then(response  => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }               
                return response.json();
            })
            .then(data => {
                if (data.success) {
                // Redirect after updating session
                window.location.href = url;
                } else {
                    console.error("Server returned error:", data.message);
                }
            })
                
            .catch(error => console.error("Error:", error));
        });
    });
    

    // Close modal function
    function closeModalFktn() {
        document.getElementById("editModal").style.display = "none";
    }

    // Common function to save data from the modal
    function saveModalData() {
        const entryId = document.getElementById("entryId").value.trim();
        const editName = document.getElementById("editName").value.trim();
        const editEvent = document.getElementById("editEvent").value.trim();

        if (!entryId || !editName || !editEvent) {
            alert("All fields are required.");
            return;
        }

        const requestData = {
            id: entryId,
            name: editName,
            event: editEvent
        };

        fetch("save_row.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(requestData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the affected row in the table
                const row = document.querySelector(`tr[id="${entryId}"]`);
                if (row) {
                    // Update name field
                    const nameCell = row.querySelector(`#name-${entryId} .nameField`);
                    if (nameCell) nameCell.textContent = editName;

                    // Update event field
                    const eventCell = row.querySelector(`#event-${entryId}`);
                    if (eventCell) eventCell.textContent = editEvent;
                }
                alert("Entry successfully updated!");
                closeModalFktn(); // Function to close the modal
                if (row) {
                    row.classList.add("row-updated");
                    setTimeout(() => {
                        row.classList.remove("row-updated");
                    }, 5000); // Highlight for 2 seconds
                }
                
            } else {
                alert(`Failed to update entry: ${data.message || "Unknown error"}`);
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("An error occurred while updating the entry.");
        });
    }

    
    // Close modal when clicking the "X" button
    closeModal.addEventListener("click", () => {
        editModal.style.display = "none";
    });

    // Close modal when clicking the "X" button
    closeSelectedModal.addEventListener("click", () => {
        editSelectedModal.style.display = "none";
    });

    // Close modal when clicking outside the modal content
    window.addEventListener("click", (event) => {
        if (event.target === editModal) {
            editModal.style.display = "none";
        }
        if (event.target === editSelectedModal) {
            editSelectedModal.style.display = "none";
        }
    });
    //edit single modal rename button
    document.getElementById("renameButton").addEventListener("click", () => {
        saveModalData();
    });
    //edit single modal event button
    document.getElementById("updateEventButton").addEventListener("click", () => {
        saveModalData();
    });

    // Handle delete action in single edit modal
    deleteButton.addEventListener("click", () => {
        if (confirm("Are you sure you want to delete this list?")) {
            const selectedIds = [entryIdInput.value];
            if (!selectedIds) {
                alert("Invalid ID.");
                return;
            }
                
            fetch("delete_selected.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({ selectedIds }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                // Remove the rows from the table
                selectedIds.forEach(id => {
                    const row = document.getElementById(id);
                    if (row) row.remove();
                });

                    alert("Deleted list succesfully.");
                } else {
                    alert("Error deleting list.");
                }
                closeModalFktn(); // Function to close the modal
            })
            .catch(err => {
                console.error("Error:", err);
                alert("Error deleting entries. Please try again.");
            });
            
        }
    });

    // Handle duplicate in edit modal action
    duplicateButton.addEventListener("click", () => {
        const entryId = entryIdInput.value; // Get the ID of the entry to duplicate
        if (!entryId) {
            alert("Invalid ID.");
            return;
        }
        // Set the form action to the duplicate handler PHP file
        editForm.action = "duplicate_url.php"; 
        editForm.submit();

    });


    // "Select All" functionality based on filtering
    selectAllCheckbox.addEventListener("change", function () {
        const isChecked = this.checked;
        const visibleCheckboxes = listTable.querySelectorAll("tbody tr:not([style*='display: none']) .row-checkbox");
        visibleCheckboxes.forEach(checkbox => {
            checkbox.checked = isChecked;
        });
    });

    rowCheckboxes.forEach(checkbox => {
        checkbox.addEventListener("change", () => {
            checkbox.closest("tr").classList.add("selected-row");
            if (!checkbox.checked) {
                checkbox.closest("tr").classList.remove("selected-row");
                selectAllCheckbox.checked = false;
            } else if ([...rowCheckboxes].every(cb => cb.checked)) {
                selectAllCheckbox.checked = true;
            }

            

        });
    });


    
    //initiate edit multiple objects modal
    editSelectedButton.addEventListener("click", () => {
        // Gather IDs of selected rows
        const selectedRows = Array.from(rowCheckboxes).filter(cb => cb.checked);
        if (selectedRows.length === 0) {
            alert("Please select at least one item to edit.");
            return;
        }
        // Pre-fill modal fields based on the first selected row (optional)
        const firstSelectedRow = selectedRows[0].closest("tr");
        const eventValue = firstSelectedRow.querySelector("td[id^='event-']").textContent.trim();
        const nameValue = firstSelectedRow.querySelector("td[id^='name-']").textContent.trim();

        editSelectedEventInput.value = eventValue; // Pre-fill event
        editNamePrefixInput.value = ""; // Example: Start with empty prefix
        // Populate datalist with all event options from the table
        eventDatalist.innerHTML = ""; // Clear previous options
            document.querySelectorAll("td[id^='event-']").forEach(eventCell => {
                const eventValue = eventCell.textContent.trim();
                if (!Array.from(eventDatalist.options).some(option => option.value === eventValue) && eventValue != "0") {

                    const option = document.createElement("option");
                    option.value = eventValue;
                    eventDatalist.appendChild(option);
                }
            });

        editSelectedModal.style.display = "block";
    });

    //delete all selected
    deleteForm.addEventListener("submit", function (event) {
        event.preventDefault(); // Prevent default form submission
        const selectedIds = Array.from(rowCheckboxes)
        .filter(checkbox => checkbox.checked)
        .map(checkbox => checkbox.value); // Collect the IDs of selected rows

        // If no rows are selected, alert the user
        if (selectedIds.length === 0) {
            alert("Please select at least one row to duplicate.");
            return;
        }
        // Confirm the action with the user
        if (confirm("Are you sure you want to delete the selected list?")) {
            
            fetch("delete_selected.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({ selectedIds }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                // Remove the rows from the table
                selectedIds.forEach(id => {
                    const row = document.getElementById(id);
                    if (row) row.remove();
                });

                    alert("Deleted list succesfully.");
                } else {
                    alert("Error deleting list.");
                }
            })
            .catch(err => {
                console.error("Error:", err);
                alert("Error deleting entries. Please try again.");
            });
        }

    });


    // edit selected 
    editSelectedForm.addEventListener("submit", (event) => {
        event.preventDefault();

        // Collect selected rows
        const selectedRows = Array.from(rowCheckboxes).filter(cb => cb.checked);

        // Gather input values from the modal
        const newEvent = editSelectedEventInput.value.trim();
        const namePrefix = editNamePrefixInput.value.trim();

        if (!newEvent && !namePrefix) {
            alert("You must fill out at least one field.");
            return;
        }

        // Create an array to send the changes
        const updates = selectedRows.map(checkbox => {
            const row = checkbox.closest("tr");
            const id = checkbox.value; // Assuming the checkbox's value is the ID
            return {
                id: id,
                event: newEvent || row.querySelector("td[id^='event-']").textContent.trim(),
                name: namePrefix + row.querySelector("td[id^='name-']").textContent.trim(),
            };
        });

        // Send data to the server
        fetch("edit_multiple_url.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({ updates }),
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                                    // Update rows dynamically instead of reloading the page
                    updates.forEach(update => {
                        const row = document.querySelector(`tr input[value="${update.id}"]`).closest("tr");
                        if (newEvent) {
                            row.querySelector("td[id^='event-']").textContent = update.event;
                        }
                        if (namePrefix) {
                            row.querySelector("td[id^='name-']").textContent = update.name;
                        }
                    });
                    editSelectedModal.style.display = "none";

                } else {
                    alert("Error updating entries.");
                }
            })
            .catch(err => {
                console.error("Error:", err);
                alert("An error occurred while updating entries.");
            });
    });


    // Handle "Duplicate Selected" button click
    duplicateSelectedButton.addEventListener("click", () => {
        const selectedIds = Array.from(rowCheckboxes)
        .filter(checkbox => checkbox.checked)
        .map(checkbox => checkbox.value); // Collect the IDs of selected rows

        // If no rows are selected, alert the user
        if (selectedIds.length === 0) {
            alert("Please select at least one row to duplicate.");
            return;
        }

        // Confirm the action with the user
        if (confirm("Are you sure you want to duplicate the selected entries?")) {
            
            fetch("duplicate_selected_url.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({ ids: selectedIds }),
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Add the new rows dynamically
                    const tbody = listTable.querySelector("tbody");

                    // Create a temporary container for parsing HTML
                    const tempContainer = document.createElement("tbody");
                    tempContainer.innerHTML = data.duplicates;

                    // Append each row to the table
                    Array.from(tempContainer.querySelectorAll("tr")).forEach(newRow => {
                        tbody.appendChild(newRow);
                    });


                } else {
                    alert("Error duplicating entries.");
                }
            })
            .catch(err => {
                console.error("Error:", err);
                alert("An error occurred while duplicating entries.");
            });
        }
    });
});
