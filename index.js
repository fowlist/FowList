$('#submit').click(function(e){ 
    e.preventDefault();
});

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('open');
}


function updateURL(newUrl) {
    window.history.pushState({}, '', newUrl);
    // Update the hidden input with the new URL
    const hiddenUrlInput = document.getElementById('updated_url');
    if (hiddenUrlInput) {
        hiddenUrlInput.value = newUrl;
    }
}

function clearParameterAndSubmit(param, form) {
    const dropdowns = document.querySelectorAll(`[id^="${param}"]`);
    const dropdown = document.querySelector(`[name="${param}"]`);
    const hash = dropdown ? dropdown.name : null;
    if (dropdowns) {
        dropdowns.forEach(element => {
            element.value = null;
        });
    }
    
    if (hash) {
        // Append the hash to the form action
        form.action = `${form.action.split('#')[0]}#${hash}`;
    }

    cleanEmptyFieldsBeforeSubmission(form);
    form.submit();
}

/**
 * Removes empty fields from a GET form before submission
 * @param {HTMLFormElement} form 
 */
function cleanEmptyFieldsBeforeSubmission(form) {
    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        if (input.value.trim() === "") {
            input.disabled = true; // Disable empty fields so they're not included
        }
    });
}

function resetDropdownAndSubmit() {
    // Get the dropdown element from the other form
    const dropdown = document.getElementById('listNameList');
    // Reset the dropdown value to the default (first) option
    if (dropdown) {
        dropdown.selectedIndex = 0;
    }
    // Set the hidden input value to 0 to clear the session
    document.getElementById('loadedListName').value = 0;
    // Submit the current form (form1)
    document.getElementById('form1').submit();
}

function setDropdownAndSubmit() {
    // Get the dropdown element from the other form
    const dropdown = document.getElementById('listNameList');
    // Reset the dropdown value to the default (first) option
    if (dropdown) {
        dropdown.selectedIndex = 0;
    } 
    // Submit the current form (form1)
    document.getElementById('form1').submit();
}

function updateCostCalculation(selectedPlatoon) {
    const forcePoints = document.getElementById("pointsOnTop").querySelector(".Points").querySelector("div");
    const formation = selectedPlatoon.closest('.Formation');
    const header = formation.previousElementSibling;
    const formationPoints = header.querySelector(".Points").querySelector("div");
    
    const oldForcePoints = parseInt(forcePoints.innerText);
    const oldFormationPoints = parseInt(formationPoints.innerText);
    let newFormationPoints =0;
    let newForcePoints = 0;
    if (selectedPlatoon.parentElement.querySelector("input[platoonCheckbox]")) {
        if (selectedPlatoon.parentElement.querySelector("input[platoonCheckbox]").checked) {
            const points = selectedPlatoon.closest(".box").querySelector(".Points").querySelector("div");
            const thesePoints = parseInt(points.innerText);
            newFormationPoints = oldFormationPoints + thesePoints;
            newForcePoints = oldForcePoints + thesePoints;
            selectedPlatoon.setAttribute("lastPrice", thesePoints);
        } else {
            const thesePoints = parseInt(selectedPlatoon.getAttribute("lastPrice"));
            newFormationPoints = oldFormationPoints - thesePoints;
            newForcePoints = oldForcePoints - thesePoints;
        }
    } else if (selectedPlatoon.parentElement.querySelector("input[fCardCheckbox]")) {
        if (selectedPlatoon.parentElement.querySelector("input[fCardCheckbox]").checked) {
            const points = selectedPlatoon.closest(".box").querySelector(".Points").querySelector("div");
            const thesePoints = parseInt(points.innerText);
            newFormationPoints = oldFormationPoints + thesePoints;
            newForcePoints = oldForcePoints + thesePoints;
            selectedPlatoon.setAttribute("lastPrice", thesePoints);
        } else {
            const thesePoints = parseInt(selectedPlatoon.getAttribute("lastPrice"));
            newFormationPoints = oldFormationPoints - thesePoints;
            newForcePoints = oldForcePoints - thesePoints;
        }
    }

    formationPoints.innerText = newFormationPoints +" Points";
    forcePoints.innerText = newForcePoints +" Points";
    flashElement(formationPoints);
    flashElement(forcePoints);
}

function updatePrerequisites(selectedPlatoon) {
    const allWarriorCheckboxes = document.querySelectorAll("input[type='checkbox'][prerequisite*='Warrior']");
    const allConfigItems = selectedPlatoon.querySelectorAll("input[type='checkbox'], select[class*='Option']") ?? [];
    let warriorChecked = false; // Track if a warrior checkbox is checked globally
    let prerequisitesChanged = false; // Track if anything changes

    allConfigItems.forEach(configItem => {
        const prerequisite = configItem.getAttribute("prerequisite");

        if (prerequisite && prerequisite !== "0") {
            const requiredCodes = prerequisite.split('|'); // Split prerequisite into an array

            // 🔹 Handle "AddOn" Logic
            if (prerequisite.includes("AddOn")) {
                const prerequisitesMet = requiredCodes.some(code =>
                    Array.from(allConfigItems).some(otherCheckbox =>
                        otherCheckbox.value === code && otherCheckbox.checked
                    )
                );

                const wasDisabled = configItem.disabled;
                configItem.disabled = !prerequisitesMet;

                // 🔹 Handle points update if the state changes
                if (wasDisabled !== configItem.disabled) {
                    prerequisitesChanged = true; // Mark that something changed

                    if (configItem.disabled) {
                        if (configItem.checked) {
                            configItem.checked = false;
                        } else if (configItem.options && configItem.options[configItem.selectedIndex]?.value) {
                            configItem.selectedIndex = 0;
                        }
                    }
                }
            }

            // 🔹 Handle "Warrior" Logic
            else if (prerequisite.includes("Warrior")) {
                if (configItem.checked) {
                    warriorChecked = true; // Mark that a warrior checkbox is selected
                }
            }
        }
    });

    // 🔹 Handle Global Warrior Logic
    if (warriorChecked) {
        allWarriorCheckboxes.forEach(warriorCheckbox => {
            if (!warriorCheckbox.checked) {
                warriorCheckbox.disabled = true;
                prerequisitesChanged = true;
            }
        });
    } else {
        allWarriorCheckboxes.forEach(warriorCheckbox => {
            warriorCheckbox.disabled = false;
            prerequisitesChanged = true;
        });
    }

    // 🔹 If prerequisites changed, recalculate the box points
    if (prerequisitesChanged) {
        const box = selectedPlatoon.closest(".box");
        if (box) {
            recalculateBoxPoints(box);
        }
    }
}


function initiatePrerequisites(selectedPlatoon) {
    const allWarriorCheckboxes = document.querySelectorAll("input[type='checkbox'][prerequisite*='Warrior']");
    const allCheckboxes = selectedPlatoon.querySelectorAll("input[type='checkbox']") ?? [];

    allCheckboxes.forEach(checkbox => {
        const prerequisite = checkbox.getAttribute("prerequisite");

        if (prerequisite && prerequisite !== "0") {
            const requiredCodes = prerequisite.split('|'); // Split prerequisite into an array
            
            if (prerequisite.includes("Warrior")) {
                allWarriorCheckboxes.forEach(warriorCheckbox => {
                    if (warriorCheckbox.checked&&warriorCheckbox!==checkbox) {
                        checkbox.disabled = true;
                    }
                });
            } 
        }
    });
}

function updatePoints(selectElement, selectedPlatoon) {
    return new Promise(resolve => {
        const urlParams = new URLSearchParams(window.location.search);
        const isConfigBox = selectElement.parentElement.classList.contains('configBox');
        const isOptionBox = selectElement.parentElement.classList.contains('optionBox');
        const formation = selectedPlatoon.closest('.Formation');
        const header = formation?.previousElementSibling;
        const points = selectElement.closest('.box')?.querySelector(".Points div");
        const formationPoints = header?.querySelector(".Points div");
        const forcePoints = document.getElementById("pointsOnTop")?.querySelector(".Points div");

        // Ensure all required elements exist
        if (!points || !formationPoints || !forcePoints) {
            console.error("updatePoints: Missing elements in the DOM.");
            resolve();
            return;
        }

        // Get previous values before updating
        const oldForcePoints = parseInt(forcePoints.innerText) || 0;
        const oldFormationPoints = parseInt(formationPoints.innerText) || 0;
        const oldPoints = parseInt(points.innerText) || 0;

        // Remove old value from the totals
        let newFormationPoints = oldFormationPoints - oldPoints;
        let newForcePoints = oldForcePoints - oldPoints;

        let currentCost = parseInt(selectElement.getAttribute('currentCost') ?? selectElement.getAttribute('cost') ?? '0');
        let newCost = oldPoints;

        // Fix calculation order for team multiplicator
        if (isConfigBox) {
            selectedPlatoon.setAttribute('currentNrOfTeams', selectElement.options[selectElement.selectedIndex]?.getAttribute('nrOfTeams') || "1");
        } else if (isOptionBox) {
            selectedPlatoon.setAttribute('currentNrOfAddedTeams', selectElement.options[selectElement.selectedIndex]?.getAttribute('value') || "0");
        }

        // Fix race condition with `perTeamMultiplicator`
        let perTeamMultiplicator = parseFloat(selectElement.getAttribute('pricePerTeam') ?? '0') 
                                    * (parseInt(selectedPlatoon.getAttribute('currentNrOfTeams') ?? '1'));

        perTeamMultiplicator = (perTeamMultiplicator === 0 || !perTeamMultiplicator) ? 1 : perTeamMultiplicator;

        // Handle different element types
        if (selectElement.type === 'checkbox') {
            const checkBoxCost = Math.round((parseInt(selectElement.getAttribute('cost') ?? "0")) * perTeamMultiplicator);

            if (selectElement.checked) {
                newCost += (urlParams.has(selectElement.getAttribute("name")) ? checkBoxCost - currentCost : checkBoxCost);
                urlParams.set(selectElement.getAttribute("name"), selectElement.value);
            } else {
                newCost -= checkBoxCost;
                urlParams.delete(selectElement.getAttribute("name"));
            }

            selectElement.setAttribute('currentCost', checkBoxCost);
            if (selectElement.nextElementSibling) {
                const spanElement = selectElement.nextElementSibling.querySelector("span");
                if (spanElement) spanElement.innerHTML = checkBoxCost;
            }
        } 
        
        else if (selectElement.tagName.toLowerCase() === 'select') {
            const selectedDropDown = selectElement.options[selectElement.selectedIndex];
            if (isConfigBox) {
                selectedPlatoon.setAttribute('currentNrOfTeams', selectedDropDown.getAttribute('nrOfTeams'));

            }
            const dropDownCost = parseInt(selectedDropDown.getAttribute('cost') ?? '0');

            newCost = newCost - currentCost + dropDownCost;
            selectElement.setAttribute('currentCost', dropDownCost);
            urlParams.set(selectElement.getAttribute("name"), selectedDropDown.value);
        } 
        
        else if (selectElement.nodeName === 'CARD') {
            const cardCost = Math.round(parseFloat(selectElement.getAttribute('priceFactor') ?? '0') 
                            * (parseInt(selectedPlatoon.getAttribute('currentNrOfTeams') ?? '1')));

            newCost = newCost - currentCost + cardCost;
            selectElement.setAttribute('currentCost', cardCost);
        } 
        
        else {
            if (selectElement.value.trim() !== "") {
                urlParams.set(selectElement.getAttribute("name"), selectElement.value);
            } else {
                urlParams.delete(selectElement.getAttribute("name"));
            }
        }

        // Update UI
        points.innerText = `${newCost} Points`;
        selectedPlatoon.setAttribute("lastPrice", newCost);
        formationPoints.innerText = `${newFormationPoints + newCost} Points`;
        forcePoints.innerText = `${newForcePoints + newCost} Points`;

        // Ensure URL update happens last
        setTimeout(() => {
            const newUrl = `${window.location.pathname}?${urlParams.toString()}${window.location.hash}`;
            updateURL(newUrl);
            resolve();
            //
        }, 50);  // Small delay to ensure attributes are fully updated
    });
}
//

function recalculateBoxPoints(box) {
    return new Promise(resolve => {
        const points = box.querySelector(".Points div");
        const formation = box.closest('.Formation');
        const header = formation?.previousElementSibling;
        const formationPoints = header?.querySelector(".Points div");
        const forcePoints = document.getElementById("pointsOnTop")?.querySelector(".Points div");

        // Ensure required elements exist
        if (!points || !formationPoints || !forcePoints) {
            console.error("recalculateBoxPoints: Missing elements in the DOM.");
            resolve();
            return;
        }

        // Step 1: Get the **current values** before updating
        const oldForcePoints = parseInt(forcePoints.innerText) || 0;
        const oldFormationPoints = parseInt(formationPoints.innerText) || 0;
        const oldBoxPoints = parseInt(points.innerText) || 0;
        let newTotalCost = 0;

        // Step 2: Find all selectable elements inside the box
        const selectableElements = box.querySelectorAll("input[type='checkbox']:checked, select, card");
        const allSelectableElements = box.querySelectorAll("input[type='checkbox'], select, card");

        // Step 3: Calculate total cost **using Promises**
        let costPromises = Array.from(selectableElements).map(element => {
            return new Promise(resolveElement => {
                let elementCost = 0;
                let perTeamMultiplicator = null;

                if (element.type === 'checkbox') {
                    if ((element.getAttribute('pricePerTeam') ?? false) != '0') {
                        perTeamMultiplicator = parseFloat(element.getAttribute('pricePerTeam') ?? '1') 
                                                * (parseInt(box.getAttribute('currentNrOfTeams') ?? '1'));
                    }
                    elementCost = Math.round(parseInt(element.getAttribute('cost') ?? '0') * (perTeamMultiplicator ?? 1));
                } 
                else if (element.tagName.toLowerCase() === 'select') {
                    const selectedOption = element.options[element.selectedIndex];
                    elementCost = parseInt(selectedOption.getAttribute('cost') ?? '0');

                    const isConfigBox = element.parentElement.classList.contains('configBox');
                    if (isConfigBox) {
                        box.setAttribute('currentNrOfTeams', selectedOption.getAttribute('nrOfTeams'));
                    }
                }
                else if (element.nodeName === 'CARD') {
                    if ((element.getAttribute('priceFactor') ?? false) !== '0') {
                        elementCost = Math.round(parseFloat(element.getAttribute('priceFactor') ?? '0') 
                                    * (parseInt(box.getAttribute('currentNrOfTeams') ?? '1')));
                    } else {
                        elementCost = parseInt(element.getAttribute('cost') ?? '0');
                    }
                    element.setAttribute('currentCost', elementCost);
                }

                newTotalCost += elementCost;
                resolveElement(); // Resolve this element's calculation
            });
        });

        // Step 4: Wait for **all calculations to finish** before updating UI
        Promise.all(costPromises).then(() => {
            // Apply the flashing effect
            flashElement(points);
            flashElement(formationPoints);
            flashElement(forcePoints);

            // Update the UI **only after all calculations are done**
            points.innerText = `${newTotalCost} Points`;
            formationPoints.innerText = `${oldFormationPoints - oldBoxPoints + newTotalCost} Points`;
            forcePoints.innerText = `${oldForcePoints - oldBoxPoints + newTotalCost} Points`;

            // Save last calculated price in the box
            box.setAttribute("lastPrice", newTotalCost);

            // Step 5: **Update URL only after all calculations are done**
            const urlParams = new URLSearchParams(window.location.search);
            allSelectableElements.forEach(element => {

                const name = element.getAttribute("name");
                // If no name is defined, skip the element
                if (!name) return;

                // For checkboxes: if not checked, remove its parameter.
                if (element.type === 'checkbox') {
                    if (!element.checked) {
                        urlParams.delete(name);
                    } else {
                        urlParams.set(name, element.value);
                    }
                } 
                // For select elements (or other elements that support value)
                else if (element.tagName.toLowerCase() === 'select' || element.nodeName.toLowerCase() === 'card') {
                    const value = element.value.trim();
                    if (value === "") {
                        urlParams.delete(name);
                    } else {
                        urlParams.set(name, value);
                    }
                } 
                // For any other element, use its value
                else {
                    const value = element.value.trim();
                    if (value === "") {
                        urlParams.delete(name);
                    } else {
                        urlParams.set(name, value);
                    }
                }
                

            });

            const newUrl = `${window.location.pathname}?${urlParams.toString()}${window.location.hash}`;
            updateURL(newUrl);
            resolve(); // Resolve the final Promise
        });
    });
}
/**
 * 🔹 Flash Effect Function
 * - Adds the "flash-effect" class to an element.
 * - Removes it after the animation duration (1 second).
 */
function flashElement(element) {
    if (!element) return;
    element.classList.add("flash-effect");
    setTimeout(() => {
        element.classList.remove("flash-effect");
    }, 500); // Match animation duration
}


function handleElementChange(selectElement) {
    const selectedPlatoon = selectElement.closest('.selectedPlatoon');
    const box = selectElement.closest('.selectedPlatoon');
    const platoonSelectElements = selectedPlatoon.querySelectorAll("select, input, card");
    
    // ✅ Update prerequisites
    updatePrerequisites(selectedPlatoon);
    // ✅ Update points and URL
    recalculateBoxPoints(box);
    /*updatePoints(selectElement, selectedPlatoon)
    .then(() => {
        platoonSelectElements.forEach(element => {

            if (((element.checked ?? true)&&element !==selectElement)) {
                updatePoints(element, selectedPlatoon);
            }
            
        });
    });
*/
}

function decreaseNOF(id) {
    const nOFSelect = document.getElementById(id);
    const currentVal = parseInt(nOFSelect.value);


    if (currentVal > 0) {
        nOFSelect.value = currentVal - 1;
        const hash = "F" + nOFSelect.value;
        if (hash) {
            // Append the hash to the form action
            form.action = `${form.action.split('#')[0]}#${hash}`;
        }
        nOFSelect.form.submit();  // Submit the form after incrementing
    }
}

function incrementNOF(id) {
    const nOFSelect = document.getElementById(id);
    const currentVal = parseInt(nOFSelect.value);

    // Increment by 1, ensuring it doesn't exceed the maximum value (6)
    if (currentVal < 6) {
        nOFSelect.value = currentVal + 1;
        const hash = "F" + nOFSelect.value;
        if (hash) {
            // Append the hash to the form action
            form.action = `${form.action.split('#')[0]}#${hash}`;
        }
        nOFSelect.form.submit();  // Submit the form after incrementing
    }
}

document.addEventListener("DOMContentLoaded", function () {
    const boxesCheckboxes = document.querySelectorAll("input[platoonCheckbox]");
    const fCardsCheckboxes = document.querySelectorAll("input[fCardCheckbox]");
    
    const collapsableHeader = document.querySelectorAll(".collapsible");
    const grids = document.querySelectorAll(".grid");

    const overlay = document.getElementById("infoOverlay");
    const closeOverlay = document.getElementById("closeOverlay");
    const platoonDetails = document.getElementById("platoonDetails");
    const urlParams = new URLSearchParams(window.location.search);

        if (typeof linkQuery !== 'undefined' && linkQuery) {
            const newUrl = `${window.location.pathname}?${linkQuery}${window.location.hash}`;
            window.history.replaceState({}, '', newUrl); // Replace the current URL without reloading
        }

    // Special Button Handling
    document.getElementById('process-link').addEventListener('click', function () {
        const form = document.getElementById('form');
        cleanEmptyFieldsBeforeSubmission(form);
        const formData = new FormData(form);

        // Convert FormData to URL query string
        const queryString = new URLSearchParams(formData).toString();

        console.log('Form Data as Query String:', queryString);

        fetch(`getNewLink.php?${queryString}`, {
            method: 'GET'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Redirecting to:', data.url);
                // Redirect to the returned URL
                window.location.href = data.url;
            } 
        })
        .catch(error => {
            console.error('Error:', error);
        });
    });

    document.getElementById("saveListForm").querySelectorAll('button[type="submit"]').forEach(button => {
        button.addEventListener('click',  function (event) {
            if (button.name != "loadSelected") {
                event.preventDefault(); // Prevent the default form submission
                const form1 = document.getElementById('form');
                cleanEmptyFieldsBeforeSubmission(form1);
                const formData = new FormData(form1);
        
                // Convert FormData to URL query string
                const queryString = new URLSearchParams(formData).toString();
        
                console.log('Form Data as Query String:', queryString);
        
                fetch(`getNewLink.php?${queryString}`, {
                    method: 'GET'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
        
                        const form = this.closest("form");
                        
                        // Create a hidden input to simulate the button click
                        const hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = button.name;
                        hiddenInput.value = button.innerText;
                        // Append the hidden input to the form
                        
                        if (form) {
                            form.action = `index.php?${data.query}`;
                            form.appendChild(hiddenInput);
                            form.submit();
                        }
                    } 
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }
        });
    });
    


    function updateSelectElements() {
        return new Promise(resolve => {
        const selectElements = document.getElementById("form").querySelectorAll('form input[type="checkbox"], form select, form button[id*="box"]');
        selectElements.forEach((element) => {
            // Check if the element is a checkbox or select element

            
            if ((   element.tagName === 'SELECT' || 
                    element.type === 'checkbox')&&
                    !element.attributes.platoonCheckbox&&
                    
                    !element.attributes.formselect&&
                    !element.attributes.fCardSelect&&
                    !element.attributes.fCardCheckbox&&
                    !element.attributes.formcard) {
                
                element.addEventListener('change', function () {
                    handleElementChange(element);
                    
                    const form = this.closest("form");
                    if (form) {
                        // Find the closest ancestor with the class 'box'
                        const boxElement = element.closest('.box');
                        const hash = boxElement ? boxElement.id : null;
                        
                        if (hash) {
                            // Append the hash to the form action
                            form.action = `${form.action.split('#')[0]}#${hash}`;
                        }
                    }
                });
            }
            else if ( element.tagName === 'SELECT'&&
                !element.attributes.fCardCheckbox) {
            
            element.addEventListener('change', function () {

                const form = this.closest("form");
                if (form) {
                    const hash = element ? element.name : null;
                    
                    if (hash) {
                        // Append the hash to the form action
                        form.action = `${form.action.split('#')[0]}#${hash}`;
                    }
                    cleanEmptyFieldsBeforeSubmission(form);
                    form.submit();
                }
            });
        } 
        else if (element.attributes.formcard) {
            
            element.addEventListener('change', function() {
                if (!this.checked) {
                  // If the checkbox is unchecked, recheck it
                  this.checked = true;
                }
              });
        }
            // Add click event listener for buttons
            else if (element.tagName === 'BUTTON') {
                element.addEventListener('click', function () {

                    const form = this.closest("form");
                    if (form) {

                        const hash = element ? element.name : null;
                        
                        if (hash) {
                            // Append the hash to the form action
                            form.action = `${form.action.split('#')[0]}#${hash}`;
                        }
                        cleanEmptyFieldsBeforeSubmission(form);
                        form.submit();
                    }
                });
            }
        });
        document.querySelectorAll(".info-btn, .smallCard-btn").forEach(button => {
            button.addEventListener("click", () => {
                
                if (button.dataset.codes) {
                    const platoonCodes = JSON.parse(button.dataset.codes);
                    fetchPlatoonDetails(platoonCodes);
                }
                if (button.dataset.cards) {
                    const cardCodes = JSON.parse(button.dataset.cards);
                    
                    fetchCardnDetails(cardCodes);
                }
                overlay.classList.remove("hidden");
                
            });
        });
        resolve();
        });
    }

    updateSelectElements();
    // Show overlay when info button is clicked

    


    // Close overlay
    closeOverlay.addEventListener("click", () => {
        overlay.classList.add("hidden");
        platoonDetails.innerHTML = ""; // Clear previous details
    });

    // Close modal when clicking outside the modal content
    window.addEventListener("click", (event) => {
        if (event.target === overlay) {
            overlay.classList.add("hidden");
            platoonDetails.innerHTML = ""; // Clear previous details
        }
    });

    // Fetch platoon details via AJAX
    function fetchPlatoonDetails(codes) {        
        fetch("fetchPlatoonDetails.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({codes: codes})

        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    platoonDetails.innerHTML = data.html;
                    //displayPlatoonDetails(data.platoon);
                } else {
                    console.error("Error:", data.error);
                    platoonDetails.innerHTML = `<p>Error fetching details</p>`;
                }
            })
            .catch(err => {
                console.error("Error:", err);
                platoonDetails.innerHTML = `<p>Failed to load details.</p>`;
            });
    }

    // Fetch platoon details via AJAX
    function fetchPlatoonConfig(data, selectedPlatoon) {    
        const platoonData = typeof data === 'string' ? JSON.parse(data) : data;
        
        return fetch("selectedPlatoonConfig.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({platoonInfo: platoonData })
        })
            .then(response => response.json())
            .then(data => {
                
                if (data.success) {
                    selectedPlatoon.innerHTML = data.html;
                    selectedPlatoon.classList.add("selected");
                } else {
                    console.error("Error:", data.error);
                    platoonDetails.innerHTML = `<p>Error fetching details</p>`;
                }
            })
            .then(() => {
                return initiatePrerequisites(selectedPlatoon);
            })
                
            .then(() => {
                return updateCostCalculation(selectedPlatoon);
            })
            .catch(err => {
                console.error("Error:", err);
                platoonDetails.innerHTML = `<p>Failed to load details.</p>`;
            });
    }
    // Fetch platoon details via AJAX
    function fetchCardConfig(data, selectedPlatoon) {    
        const codes = typeof data === 'string' ? JSON.parse(data) : data;
        
        return fetch("selectedCardConfig.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({code: codes})
        })
            .then(response => response.json())
            .then(data => {
                
                if (data.success) {
                    selectedPlatoon.innerHTML = data.html;
                    selectedPlatoon.classList.add("selected");
                } else {
                    console.error("Error:", data.error);
                    platoonDetails.innerHTML = `<p>Error fetching details</p>`;
                }
            })
            .then(() => {
                return initiatePrerequisites(selectedPlatoon);
            })
                
            .then(() => {
                return updateCostCalculation(selectedPlatoon);
            })
            .catch(err => {
                console.error("Error:", err);
                platoonDetails.innerHTML = `<p>Failed to load details.</p>`;
            });
    }


    function fetchCardnDetails(codes) {        
        fetch("fetchCardDetails.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({codes: codes})

        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    
                    platoonDetails.innerHTML = data.html;
                    //displayPlatoonDetails(data.platoon);
                } else {
                    console.error("Error:", data.error);
                    platoonDetails.innerHTML = `<p>Error fetching details</p>`;
                }
            })
            .catch(err => {
                console.error("Error:", err);
                platoonDetails.innerHTML = `<p>Failed to load details.</p>`;
            });
    }


    const gridContainer = this.querySelector('.grid');
    const computedStyle = window.getComputedStyle(gridContainer);
    // Parse grid-auto-rows and gap from the CSS (assumed to be in px)
    const gridAutoRows = parseFloat(computedStyle.getPropertyValue('grid-auto-rows'));
    const gap = parseFloat(computedStyle.getPropertyValue('gap'));
    const gridHeight = gridAutoRows+gap;
    grids.forEach(grid => {
        const boxes = grid.querySelectorAll(".box");
        initDistributeGrid(boxes);
    });

    function initDistributeGrid(boxes) {
        return new Promise(resolve => {
            requestAnimationFrame(() => {

                boxes.forEach(box => {
                    box.style.gridRowEnd = "span 1";
                    box.getBoundingClientRect(); // Force reflow
                    
                    const height = box.scrollHeight;
                    const rowsToSpan = Math.ceil((height+1) / gridHeight);
                    box.style.gridRowEnd = `span ${rowsToSpan}`;
                });
                resolve(); // Complete after adjustment
            });
        });
    }
    
    function redistributeGrid(box) {
        return new Promise(resolve => {
            const boxes = box.closest('.grid').querySelectorAll(".box");
            // Step 1: First - Capture the initial positions
            const firstPositions = new Map();
            boxes.forEach(box => {
                firstPositions.set(box, box.getBoundingClientRect());
            });
    
            requestAnimationFrame(() => {
                const gridHeight = 37;
                boxes.forEach(box => {
                    box.style.gridRowEnd = "span 1";
                    box.getBoundingClientRect(); // Force reflow
    
                    const height = box.scrollHeight;
                    let rowsToSpan = 1;
    
                    for (let index = 1; index < Math.floor(height / gridHeight) + 3; index++) {
                        if ((height + 12) > (gridHeight * index)) {
                            rowsToSpan = index + 1;
                        }
                    }
                    box.style.gridRowEnd = `span ${rowsToSpan}`;
                });
    
                // Step 2: Last - Capture final positions
                requestAnimationFrame(() => {
                    boxes.forEach(box => {
                        const firstRect = firstPositions.get(box);
                        const lastRect = box.getBoundingClientRect();
    
                        const deltaX = firstRect.left - lastRect.left;
                        const deltaY = firstRect.top - lastRect.top;
    
                        // Step 3: Invert - Apply transform to bridge the visual gap
                        box.style.transform = `translate(${deltaX}px, ${deltaY}px)`;
                        box.style.transition = 'transform 0s'; // Prevent transition during inversion
    
                        // Step 4: Play - Smoothly animate back to natural position
                        requestAnimationFrame(() => {
                            box.style.transform = '';
                            box.style.transition = 'transform 0.3s ease-in-out';
                        });
                    });
    
                    // Complete after all transitions
                    setTimeout(resolve, 300); // Match transition duration
                });
            });
        });
    }

    collapsableHeader.forEach(header => {
        header.addEventListener("click", function() {
            this.classList.toggle("active");
            var content = this.nextElementSibling;
            if (content.style.display === "none") {
            content.style.display = "inline-block";
            } else {
            content.style.display = "none";
            }
        });
    });

    /**
     * Unchecks all other checkboxes in the same box
     * @param {HTMLElement} box - The container element for checkboxes
     * @param {HTMLElement} currentCheckbox - The currently checked checkbox
     * @returns {Promise} Resolves after all other checkboxes are unchecked
     */
    function uncheckOtherCheckboxes(box, currentCheckbox) {
        return new Promise((resolve, reject) => {
            try {
                if (currentCheckbox.hasAttribute("ally")) {
                    const alliesCheckboxes = document.querySelectorAll("input[ally]");
                    alliesCheckboxes.forEach(alliedCheckbox => {
                        if (alliedCheckbox != currentCheckbox) {
                            if (alliedCheckbox.checked ) {
                                alliedCheckbox.checked = false;
                                const otherPlatoonConfig = alliedCheckbox.parentElement.querySelector(".selectedPlatoon");
                                updateCostCalculation(otherPlatoonConfig);
                                removeChildElements(otherPlatoonConfig);
                            }
                        }
                    });
                }
                const groupCheckboxes = box.querySelectorAll('input[platoonCheckbox]');        
                groupCheckboxes.forEach(otherCheckbox => {
                    if (otherCheckbox !== currentCheckbox) {
                        if (otherCheckbox.checked ) {
                            otherCheckbox.checked = false;
                            updateCostCalculation(otherCheckbox.parentElement.querySelector(".selectedPlatoon"));
                        }
                    }
                });

                resolve(); // Mark the Promise as complete
            } catch (error) {
                reject(`Error in uncheckOtherCheckboxes: ${error.message}`);
            }
        });
    }

    /**
     * Removes child elements from the given platoon when unchecked
     * @param {HTMLElement} platoonConfig - The platoon configuration div
     * @returns {Promise} Resolves after child elements are removed
     */
    function removeChildElements(platoonConfig) {
        return new Promise((resolve, reject) => {
            try {
                if (platoonConfig) {
                    platoonConfig.classList.remove("selected");
                    platoonConfig.innerHTML = ""; // Clear the content
                    const childElements = platoonConfig.querySelectorAll('select, input');
                    childElements.forEach(child => {
                        const childName = child.getAttribute("name");
                        if (childName) {
                            urlParams.delete(childName);
                        }
                    });
                }
                resolve(); // Mark the operation as complete
            } catch (error) {
                reject(`Error in removeChildElements: ${error.message}`);
            }
        });
    }

    /**
     * Handles platoon selection/deselection, including blackbox-specific behavior.
     * @param {HTMLElement} box - The container for the platoons.
     * @param {HTMLElement} checkbox - The checkbox being interacted with.
     * @param {HTMLElement} platoonConfig - The configuration div for the selected platoon.
     * @param {boolean} isBlackBox - Whether the box is a blackbox.
     * @returns {Promise} Resolves after handling selection and updates.
     */
    function handlePlatoonSelection(box, checkbox, platoonConfig, isBlackBox) {
        return new Promise((resolve, reject) => {
            try {


                // Step 1: Uncheck all other checkboxes in the same box
                const groupSelectedPlatoonConfig = box.querySelectorAll(".selectedPlatoon");
                uncheckOtherCheckboxes(box, checkbox)
                    .then(() => {
                        return Promise.all(
                            Array.from(groupSelectedPlatoonConfig).map(otherPlatoonConfig => {
                                if (otherPlatoonConfig === platoonConfig) {
                                    platoonConfig.classList.add("selected");
                                } else {
                                    return removeChildElements(otherPlatoonConfig);
                                }
                            })
                        );
                    })
                    .then(() => {
                        return fetchPlatoonConfig(checkbox.dataset.platooninfo, platoonConfig);
                    })
                    .then(() => {
                        // Step 4: Update dependent select elements
                        return updateSelectElements();
                    })
                    .then(() => {
                        // Step 5: Adjust the grid layout
                        return redistributeGrid(box);
                    })
                    .then(() => {
                        // Step 6: Update the URL
                        const otherQueryAttributes = box.querySelectorAll('input[platoonCheckbox]');
                        otherQueryAttributes.forEach(element => {
                            if (element !== checkbox && !element.checked) {
                                urlParams.delete(element.getAttribute("name"));
                            }
                        });
    
                        const queryAttribute = checkbox.getAttribute("name");
                        const platoonCode = checkbox.value;
    
                        if (checkbox.checked) {
                            urlParams.set(queryAttribute, platoonCode);
                        } else {
                            urlParams.delete(queryAttribute);
                        }
    
                        const newUrl = `${window.location.pathname}?${urlParams.toString()}${window.location.hash}`;
                        updateURL(newUrl);
                        console.log("Updated URL:\n" + newUrl.replaceAll("&", "\n"));
                        resolve();
                    })
                    .catch(error => {
                        reject(`Error in handlePlatoonSelection sequence: ${error.message}`);
                    });
            } catch (error) {
                reject(`Unexpected error in handlePlatoonSelection: ${error.message}`);
            }
        });
    }

    function handleFCardSelection(box, checkbox, platoonConfig, isBlackBox) {
        return new Promise((resolve, reject) => {
            try {
                    fetchCardConfig(checkbox.dataset.cardinfo, platoonConfig)

                    .then(() => {
                        // Step 5: Adjust the grid layout
                        return redistributeGrid(box);
                    })
                    .then(() => {
    
                        const queryAttribute = checkbox.getAttribute("name");
                        const platoonCode = checkbox.value;
    
                        if (checkbox.checked) {
                            urlParams.set(queryAttribute, platoonCode);
                        } else {
                            urlParams.delete(queryAttribute);
                        }
    
                        const newUrl = `${window.location.pathname}?${urlParams.toString()}${window.location.hash}`;
                        updateURL(newUrl);
                        console.log("Updated URL:\n" + newUrl.replaceAll("&", "\n"));
                        resolve();
                    })
                    .catch(error => {
                        reject(`Error in handlePlatoonSelection sequence: ${error.message}`);
                    });
            } catch (error) {
                reject(`Unexpected error in handlePlatoonSelection: ${error.message}`);
            }
        });
    }

    fCardsCheckboxes.forEach(checkbox => {
        checkbox.addEventListener("change", function () {

            const queryAttribute = this.getAttribute("name");
            const card = this.parentElement;
            const box = card.parentElement;
            const thisCardConfig = card.querySelector(".selectedCard");
            
            // 🟢 SCENARIO 1: Platoon is SELECTED
            if (this.checked) {
                handleFCardSelection(box, this, thisCardConfig, false)
                    .then(() => console.log("Regular box sequence completed."))
                    .catch(error => console.error(error));
            }

            // 🔴 SCENARIO 2: Platoon is DESELECTED
            else {
                // 1. Remove the platoon from the search string
                urlParams.delete(queryAttribute);
                // ✅ Update the URL in the browser without reloading
                
                const newUrl = `${window.location.pathname}?${urlParams.toString()}${window.location.hash}`;
                // 2. Remove `.selected` class from the platoon config
                updateURL(newUrl);
                if (thisCardConfig) {
                    removeChildElements(thisCardConfig)
                    .then(() => redistributeGrid(box))
                    .then(() => updateCostCalculation(thisCardConfig));
                }
            }
        });
    });
    
    boxesCheckboxes.forEach(checkbox => {
        initiatePrerequisites(checkbox.parentElement)
        checkbox.addEventListener("change", function () {

            const queryAttribute = this.getAttribute("name");
            const platoon = this.parentElement;
            const platoonCode = this.value; // Checkbox value (platoon code)
            const box = platoon.parentElement;
            
            const thisPlatoonConfig = platoon.querySelector(".selectedPlatoon");
            const isBlackBox = platoon.classList.contains('blackbox'); // Check if parent is blackbox

            if (!box) return;
            
            // 🟢 SCENARIO 1: Platoon is SELECTED
            if (this.checked) {
                handlePlatoonSelection(box, this, thisPlatoonConfig, false)
                    .then(() => console.log("Regular box sequence completed."))
                    .catch(error => console.error(error));
            }

            // 🔴 SCENARIO 2: Platoon is DESELECTED
            else {
                // 1. Remove the platoon from the search string
                urlParams.delete(queryAttribute);
                // ✅ Update the URL in the browser without reloading
                
                const newUrl = `${window.location.pathname}?${urlParams.toString()}${window.location.hash}`;
                // 2. Remove `.selected` class from the platoon config
                updateURL(newUrl);
                if (thisPlatoonConfig) {
                    removeChildElements(thisPlatoonConfig)
                    .then(() => redistributeGrid(box))
                    .then(() => updateCostCalculation(thisPlatoonConfig))
                    .then(() => {
                        if (isBlackBox) {
                            // Find the first platoon in the box
                            const firstPlatoonCheckbox = box.querySelector('input[platoonCheckbox]');
                            const firstplatoonConfig = firstPlatoonCheckbox.parentElement.querySelector(".selectedPlatoon");
                            firstPlatoonCheckbox.checked = true; // Re-select the first platoon8
                                    
                            handlePlatoonSelection(box, firstPlatoonCheckbox, firstplatoonConfig, false)
                                .then(() => console.log("Blackbox sequence completed."))
                                .catch(error => console.error(error));
                        }
                    })
                }
            }
        });
    });
    let lastScrollTop = 0;
    const header = document.getElementById('main-header');

    

    window.addEventListener('scroll', () => {
        const currentScroll = window.scrollY || document.documentElement.scrollTop;
        if (currentScroll > lastScrollTop) {
            // Scrolling down
            header.classList.add('hiddenM');
            document.getElementById("backToTopButton").style.display = "block";
        } else {
            // Scrolling up
            header.classList.remove('hiddenM');
            document.getElementById("backToTopButton").style.display = "none";
        }
        lastScrollTop = currentScroll <= 0 ? 0 : currentScroll; // For mobile or negative scrolling
    });

    // Scroll to the top when the button is clicked
    document.getElementById("backToTopButton").onclick = function () {
        document.body.scrollTop = 0;
        document.documentElement.scrollTop = 0;
    };

});