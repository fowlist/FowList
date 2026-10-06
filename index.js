$('#submit').click(function(e){ 
    e.preventDefault();
});

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('open');
}


/**
 * Proper rounding function that handles floating-point precision issues
 * @param {number} value - The value to round
 * @returns {number} The rounded value
 */
function properRound(value) {
    return Math.round(value + Number.EPSILON * Math.sign(value));
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


/**
 * price update for force and formation when checking/unchecking platoon and 
 * force card checkboxes with pricePerTeam or priceFactor attributes.
 * Calculates the new points based on the checkbox's cost and the number of teams in the platoon,
 * then updates the points display and applies a flash effect to highlight the change.
 * Relies on the checkbox having a "cost" attribute and optionally "pricePerTeam" or "priceFactor" for dynamic pricing.
 * @param {HTMLInputElement[]} selectedPlatoon
 */
function updateCostCalculation(selectedPlatoon) {
    const forcePoints = document.getElementById("pointsOnTop").querySelector(".Points").querySelector("div");
    const formation = selectedPlatoon.closest('.Formation');
    if (!formation) return; // force card boxes live outside .Formation — nothing to update here
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

/**
 * Shared helper: enforces that only one checkbox per value can be checked/enabled
 * across a given array of checkboxes. Used by both updateLimitedCardPrerequisites
 * and the "Limited" branch of updatePlatoonCheckboxPrerequisites.
 * @param {HTMLInputElement[]} checkboxes
 */
function enforceUniqueChecked(checkboxes) {
    const checkedByValue = new Map();

    // First pass: record the first checked checkbox per value; uncheck any duplicates
    checkboxes.forEach(cb => {
        if (cb.checked) {
            if (!checkedByValue.has(cb.value)) {
                checkedByValue.set(cb.value, cb);
            } else {
                cb.checked = false;
                cb.dispatchEvent(new Event('change'));
            }
        }
    });

    // Second pass: disable all non-winning checkboxes that share a checked value
    checkboxes.forEach(cb => {
        const shouldDisable = checkedByValue.has(cb.value) && checkedByValue.get(cb.value) !== cb;
        if (cb.disabled !== shouldDisable) {
            cb.disabled = shouldDisable;
            if (shouldDisable && cb.checked) {
                cb.checked = false;
                cb.dispatchEvent(new Event('change'));
            }
        }
    });
}

function updateLimitedCardPrerequisites() {
    const limitedCards = Array.from(
        document.querySelectorAll("input[type='checkbox'][prerequisite='Limited']")
    );
    enforceUniqueChecked(limitedCards);
}


function updatePrerequisites(selectedPlatoon) {
    const allPlatoonCheckboxes = Array.from(document.querySelectorAll('input[platoonCheckbox]'));
    const allWarriorCheckboxes = Array.from(
        document.querySelectorAll("input[type='checkbox'][prerequisite*='Warrior']")
    );
    const allConfigItems = Array.from(
        selectedPlatoon.querySelectorAll("input[type='checkbox'], select[class*='Option']")
    );
    let prerequisitesChanged = false;

    // 🔹 PASS 1: Build blocker map — only scan if current platoon actually has Block rules
    const blockers = new Map();
    const hasBlockRules = allConfigItems.some(
        item => item.getAttribute("prerequisite")?.includes("Block")
    );
    if (hasBlockRules) {
        document.querySelectorAll('.selectedPlatoon, .selectedCard').forEach(selected => {
            selected.querySelectorAll("input[type='checkbox'], select[class*='Option']").forEach(configItem => {
                const prerequisite = configItem.getAttribute("prerequisite");
                if (prerequisite && prerequisite.includes("Block")) {
                    const blockCodes = prerequisite.split("Block|")[1]
                        .split(/[,|]/).map(code => code.trim()).filter(Boolean);
                    blockCodes.forEach(code => {
                        if (!blockers.has(code)) blockers.set(code, []);
                        blockers.get(code).push({ source: configItem, sourceId: configItem.id });
                    });
                }
            });
        });
    }

    // 🔹 PASS 2: Process each config item's prerequisite rules
    allConfigItems.forEach(configItem => {
        const prerequisite = configItem.getAttribute("prerequisite");
        if (!prerequisite || prerequisite === "0") return;

        const requiredCodes = prerequisite.split(/[,|]/).map(code => code.trim()).filter(Boolean);

        // 🔹 Handle "AddOn" Logic
        if (prerequisite.includes("AddOn")) {
            const prerequisitesMet = requiredCodes.some(code =>
                allConfigItems.some(other => other.value === code && other.checked)
            );
            const wasDisabled = configItem.disabled;
            configItem.disabled = !prerequisitesMet;

            if (wasDisabled !== configItem.disabled) {
                prerequisitesChanged = true;
                if (configItem.disabled) {
                    if (configItem.checked) {
                        configItem.checked = false;
                    } else if (configItem.options && configItem.options[configItem.selectedIndex]?.value) {
                        configItem.selectedIndex = 0;
                    }
                }
            }
        }

        // 🔹 Handle "Block" Logic
        if (prerequisite.includes("Block")) {
            const blockCodes = prerequisite.split("Block|")[1]
                .split(/[,|]/).map(code => code.trim()).filter(Boolean);

            blockCodes.forEach(code => {
                const targetCheckbox = allPlatoonCheckboxes.find(cb => cb.value === code);
                if (targetCheckbox) {
                    const activeBlockers = blockers.get(code)?.filter(b => b.source.checked) || [];
                    const shouldBeDisabled = activeBlockers.length > 0;

                    if (targetCheckbox.disabled !== shouldBeDisabled) {
                        targetCheckbox.disabled = shouldBeDisabled;
                        prerequisitesChanged = true;
                        if (shouldBeDisabled && targetCheckbox.checked) {
                            targetCheckbox.checked = false;
                            targetCheckbox.dispatchEvent(new Event('change'));
                        }
                    }
                }
            });
        }
    });

    // 🔹 Handle Global Warrior Logic — scan ALL selected containers, not just current platoon
    const warriorChecked = document.querySelectorAll('.selectedPlatoon, .selectedCard')
        ? Array.from(document.querySelectorAll('.selectedPlatoon, .selectedCard')).some(container =>
            Array.from(
                container.querySelectorAll("input[type='checkbox'][prerequisite*='Warrior']")
            ).some(cb => cb.checked)
        )
        : false;

    allWarriorCheckboxes.forEach(warriorCheckbox => {
        const shouldBeDisabled = warriorChecked && !warriorCheckbox.checked;
        if (warriorCheckbox.disabled !== shouldBeDisabled) {
            warriorCheckbox.disabled = shouldBeDisabled;
            prerequisitesChanged = true;
        }
    });

    // If prerequisites changed, recalculate the box points
    if (prerequisitesChanged) {
        const box = selectedPlatoon.closest(".box");
        if (box) {
            recalculateBoxPoints(box);
        }
    }
}

/**
 * Global Block prerequisite enforcement.
 * Scans every checked item across ALL selected platoons/cards for Block rules,
 * then disables (or re-enables) the targeted platoon checkboxes accordingly.
 * Called after page load AJAX restores and after any platoon selection change,
 * so Block state is always correct regardless of how the DOM was populated.
 */
function applyAllBlockPrerequisites() {
    const allPlatoonCheckboxes = Array.from(document.querySelectorAll('input[platoonCheckbox]'));

    // Build a complete blocker map from every checked item in the entire DOM
    const blockers = new Map();
    document.querySelectorAll('.selectedPlatoon, .selectedCard').forEach(container => {
        container.querySelectorAll("input[type='checkbox'], select[class*='Option']").forEach(item => {
            const prerequisite = item.getAttribute("prerequisite");
            if (!prerequisite || !prerequisite.includes("Block")) return;
            const blockCodes = prerequisite.split("Block|")[1]
                .split(/[,|]/).map(c => c.trim()).filter(Boolean);
            blockCodes.forEach(code => {
                if (!blockers.has(code)) blockers.set(code, []);
                blockers.get(code).push(item);
            });
        });
    });

    // For every platoon checkbox, determine if any active (checked) blocker targets it
    allPlatoonCheckboxes.forEach(checkbox => {
        const blockersForThis = blockers.get(checkbox.value) || [];
        const shouldBeDisabled = blockersForThis.some(b => b.checked);
        if (checkbox.disabled !== shouldBeDisabled) {
            checkbox.disabled = shouldBeDisabled;
            // If the checkbox was actually selected while being blocked, uncheck it silently.
            // We do NOT dispatch 'change' here — that would trigger the full deselect/AJAX
            // chain and cause cascading side effects during page load.
            if (shouldBeDisabled && checkbox.checked) {
                checkbox.checked = false;
            }
        }
    });
}

function updatePlatoonCheckboxPrerequisites() {
    const allPlatoonCheckboxes = Array.from(document.querySelectorAll('input[platoonCheckbox]'));
    const allCardSelects = Array.from(document.querySelectorAll('select[fcardselect], input[fCardCheckbox]'));

    // Collect all "Limited" platoon checkboxes and run shared helper once
    const limitedCheckboxes = allPlatoonCheckboxes.filter(cb => {
        try {
            return JSON.parse(cb.getAttribute('data-platooninfo') || '{}').prerequisite === "Limited";
        } catch { return false; }
    });
    enforceUniqueChecked(limitedCheckboxes);

    // Process non-Limited prerequisites
    allPlatoonCheckboxes.forEach(checkbox => {
        const info = checkbox.getAttribute('data-platooninfo');
        if (!info) return;
        let platoonData;
        try {
            platoonData = JSON.parse(info);
        } catch (e) {
            return;
        }
        const prerequisite = platoonData.prerequisite;
        if (!prerequisite || prerequisite.trim() === "" ) {
            checkbox.disabled = false;
            return;
        }
        if (prerequisite === "Limited") return; // already handled above

        const requiredCodes = prerequisite.split(/[,|]/).map(code => code.trim()).filter(Boolean);
        const anyMet = requiredCodes.some(code =>
            allPlatoonCheckboxes.some(other =>
                other !== checkbox && other.value === code && other.checked
            ) ||
            allCardSelects.some(card =>
                (card.tagName === 'SELECT' && card.value === code) ||
                (card.type === 'checkbox' && card.value === code && card.checked)
            )
        );
        checkbox.disabled = !anyMet;
        if (!anyMet && checkbox.checked) {
            checkbox.checked = false;
            checkbox.dispatchEvent(new Event('change'));
        }
    });
}

function initiatePrerequisites(selectedPlatoon) {
    // Check globally whether any Warrior card is already checked
    const anyWarriorChecked = Array.from(
        document.querySelectorAll("input[type='checkbox'][prerequisite*='Warrior']")
    ).some(cb => cb.checked);

    if (!anyWarriorChecked) return;

    // Disable any unchecked Warrior checkboxes in this newly loaded platoon
    selectedPlatoon.querySelectorAll("input[type='checkbox'][prerequisite*='Warrior']").forEach(checkbox => {
        if (!checkbox.checked) {
            checkbox.disabled = true;
        }
    });
}

function updatePoints(selectElement, selectedPlatoon) {
    return new Promise(resolve => {
        const urlParams = new URLSearchParams(window.location.search);
        const isConfigBox = selectElement.parentElement.classList.contains('configBox');
        const isOptionBox = selectElement.parentElement.classList.contains('optionBox');
        const formation = selectedPlatoon.closest('.Formation');
        const header = formation.previousElementSibling;
        const points = selectElement.closest('.box').querySelector(".Points").querySelector("div");
        const formationPoints = header.querySelector(".Points").querySelector("div");
        const forcePoints = document.getElementById("pointsOnTop").querySelector(".Points").querySelector("div");
        const oldForcePoints = parseInt(forcePoints.innerText);
        const oldFormationPoints = parseInt(formationPoints.innerText);
        const oldPoints = parseInt(points.innerText);
        const newFormationPoints = oldFormationPoints - oldPoints;
        const newForcePoints = oldForcePoints - oldPoints;
        const currentCost = parseInt(selectElement.getAttribute('currentCost') ?? selectElement.getAttribute('cost') ?? '0');

        let newCost = oldPoints;
        let perTeamMultiplicator = parseFloat(selectElement.getAttribute('pricePerTeam') ?? '0') 
                                * (parseInt(selectedPlatoon.getAttribute('currentNrOfTeams') ?? '1')
                                ); //+ parseInt(selectedPlatoon.getAttribute('currentNrOfAddedTeams') ?? '0')

        perTeamMultiplicator = (perTeamMultiplicator==0)||(!perTeamMultiplicator)? 1:perTeamMultiplicator;

        if (selectElement.type === 'checkbox') {
            const checkBoxCost = properRound(parseInt(selectElement.getAttribute('cost')??"0") * perTeamMultiplicator);
            if (selectElement.checked) {

                if (urlParams.has(selectElement.getAttribute("name"))) {

                    newCost +=  checkBoxCost - currentCost;
                    selectElement.setAttribute('currentCost',checkBoxCost);
                    if (selectElement.nextElementSibling) {
                        selectElement.nextElementSibling.querySelector("span").innerHTML = checkBoxCost;
                    }
                    
                } else {
                    newCost += checkBoxCost;
                    urlParams.set(selectElement.getAttribute("name"), selectElement.value);
                }
                
            } else {
                newCost -= checkBoxCost;
                urlParams.delete(selectElement.getAttribute("name"));
            }
        } else if ((selectElement.type??"").toLowerCase() === 'select-one') {
            // Handle <select> elements
            const selectedDropDown = selectElement.options[selectElement.selectedIndex];
            
            if (isConfigBox) {
                selectedPlatoon.setAttribute('currentNrOfTeams', selectedDropDown.getAttribute('nrOfTeams'));

            } else if (isOptionBox) {
                const currentNrOfAddedTeams = parseInt(selectedDropDown.getAttribute('value')) ? parseInt(selectedDropDown.getAttribute('value')) : "0";
                
                selectedPlatoon.setAttribute('currentNrOfAddedTeams',currentNrOfAddedTeams);
            }
            const dropDownCost = parseInt(selectedDropDown.getAttribute('cost') ?? '0');       
            selectElement.setAttribute('currentCost',dropDownCost);
            newCost = newCost - currentCost + dropDownCost;
            
            urlParams.set(selectElement.getAttribute("name"), selectedDropDown.value);
            
        } else if (selectElement.nodeName === 'CARD') {
            const cardCost = properRound(parseFloat(selectElement.getAttribute('priceFactor') ?? '0') 
            * (parseInt(selectedPlatoon.getAttribute('currentNrOfTeams') ?? '1')
            )); //+ parseInt(selectedPlatoon.getAttribute('currentNrOfAddedTeams') ?? '0')
            
            newCost = newCost - currentCost +cardCost;
            selectElement.setAttribute('currentCost',cardCost);

        }
        else  {
            if (selectElement.getAttribute("value").trim() !== "") {
                urlParams.set(selectElement.getAttribute("name"), selectElement.value);
            } else {
                urlParams.delete(selectElement.getAttribute("name"));
            }
        }

        points.innerText = `${newCost} Points`;
        selectedPlatoon.setAttribute("lastPrice", newCost);
        formationPoints.innerText = `${newFormationPoints + newCost} Points`;
        forcePoints.innerText = `${newForcePoints + newCost} Points`;

        // ✅ Update the URL in the browser without reloading
        const newUrl = `${window.location.pathname}?${urlParams.toString()}${window.location.hash}`;
        window.history.pushState({}, '', newUrl);
        resolve();
        //
    });
}
//

/**
 * Recalculates the points for a given box by summing up the costs of all selected options within it,
 * then updates the points display for the box, its parent formation, and the overall force.
 * Handles dynamic pricing based on "pricePerTeam" and "priceFactor" attributes, and ensures the URL reflects the current selections.
 * Designed to be called after any change that could affect the box's total cost, including prerequisite changes that may enable/disable options.
 * Relies on the box having a ".Points div" element for displaying its points, and the formation and force points being located in specific places in the DOM.
 * @param {HTMLInputElement[]} box 
 * @returns 
 */
function recalculateBoxPoints(box) {
    return new Promise(resolve => {
        const points = box.querySelector(".Points div");
        // Force card boxes sit outside .Formation — they only update the force total
        const formation = box.closest('.Formation');
        const header = formation?.previousElementSibling;
        const formationPoints = header?.querySelector(".Points div"); // null for force card boxes
        const forcePoints = document.getElementById("pointsOnTop")?.querySelector(".Points div");

        if (!points || !forcePoints) {
            resolve();
            return;
        }

        // Step 1: Get the **current values** before updating
        const oldForcePoints = parseInt(forcePoints.innerText) || 0;
        const oldFormationPoints = formationPoints ? (parseInt(formationPoints.innerText) || 0) : 0;
        // If .Points div lives inside .selectedPlatoon, removeChildElements may have wiped it
        // and saved the pre-wipe value in box.dataset.lastKnownPoints. Use that when present.
        const lastKnown = parseInt(box.dataset.lastKnownPoints ?? '');
        const oldBoxPoints = !isNaN(lastKnown) ? lastKnown : (parseInt(points.innerText) || 0);
        delete box.dataset.lastKnownPoints; // consume it so it doesn't interfere next time
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
                                                * (parseFloat(box.getAttribute('currentNrOfTeams') ?? '1'));
                    }
                    elementCost = properRound(parseFloat(element.getAttribute('cost') ?? '0') * (perTeamMultiplicator ?? 1.0));
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
                    if ((element.getAttribute('pricePerTeam') ?? false) != '0') {
                        perTeamMultiplicator = parseFloat(element.getAttribute('pricePerTeam') ?? '1') 
                                                * (parseInt(box.getAttribute('currentNrOfTeams') ?? '1'));
                        elementCost = properRound(parseInt(element.getAttribute('cost') ?? '0') * (perTeamMultiplicator ?? 1));
                    } else if ((element.getAttribute('priceFactor') ?? false) !== '0') {
                        elementCost = properRound(parseFloat(element.getAttribute('priceFactor') ?? '0') 
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
            if (formationPoints) flashElement(formationPoints);
            flashElement(forcePoints);

            // Update the UI **only after all calculations are done**
            points.innerText = `${newTotalCost} Points`;
            if (formationPoints) {
                formationPoints.innerText = `${oldFormationPoints - oldBoxPoints + newTotalCost} Points`;
            }
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


async function handleElementChange(selectElement) {
    const selectedPlatoon = selectElement.closest('.selectedPlatoon');
    if (!selectedPlatoon) {
        const selectedCard = selectElement.closest('.selectedCard');
        if (!selectedCard) return;
        // ✅ Update prerequisites
        updatePrerequisites(selectedCard);
        // ✅ Update points and URL
        recalculateBoxPoints(selectedCard);
        return;
    }

    // ✅ Update prerequisites
    await updatePrerequisites(selectedPlatoon);
    await updateLimitedCardPrerequisites();
    await regenerateConfigArray(selectedPlatoon);
    // ✅ Update points and URL
    await recalculateBoxPoints(selectedPlatoon);
    await updatePricePerTeam(selectedPlatoon)
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

function updatePricePerTeam(selectedPlatoon) {
    const currentNrOfTeams = parseInt(selectedPlatoon.getAttribute('currentNrOfTeams') ?? '1');

    // Hitta alla inputs med pricePerTeam="1" inne i selectedPlatoon
    const pricePerTeamInputs = selectedPlatoon.querySelectorAll('input[type="checkbox"][pricePerTeam="1"]');

    pricePerTeamInputs.forEach(input => {
        const baseCost = parseInt(input.getAttribute('cost') ?? '0');
        const newCost = baseCost * currentNrOfTeams;

        // Uppdatera currentCost attributet så att allt stämmer i vidare beräkningar
        input.setAttribute('currentCost', newCost);

        // Uppdatera den visuella cost-spanen om den finns
        const costSpan = input.parentElement.querySelector('span[name="cost"]');
        if (costSpan) {
            costSpan.innerText = newCost;
        }
    });
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

/**
 * Reads the team pool from a select-element's selected option's teamsnumbers attribute.
 * Returns a plain object keyed by UPPERCASE team name -> count.
 * @param {HTMLOptionElement} option
 * @returns {Object}
 */
function getTeamsPool(option) {
    if (!option) return {};
    const raw = option.getAttribute('teamsnumbers');
    if (!raw) return {};
    try {
        const parsed = JSON.parse(raw.replace(/&quot;/g, '"'));
        const pool = {};
        if (Array.isArray(parsed)) {
            parsed.forEach(t => { pool[t.name.toUpperCase()] = parseInt(t.count) || 0; });
        } else {
            Object.entries(parsed).forEach(([k, v]) => { pool[k.toUpperCase()] = parseInt(v) || 0; });
        }
        return pool;
    } catch (e) {
        console.error("Error parsing teamsnumbers:", e);
        return {};
    }
}



function regenerateOptions(select, maxAvailable) {
    const addTeams  = select.getAttribute('addteams') || 'Extra team';
    const baseCost  = parseInt(select.getAttribute('cost')) || 1;
    const eachPrice = (select.getAttribute('eachprice') || '').trim();
    const isPerTeam = eachPrice !== '';
    const currentVal = parseInt(select.value) || 0;

    select.innerHTML = '';
    select.add(new Option(`No ${addTeams}`, ''));

    for (let i = 1; i <= maxAvailable; i++) {
        const totalCost = isPerTeam ? i * baseCost : baseCost;
        const opt = new Option(`${i}x ${addTeams} (${totalCost} points)`, i);
        opt.setAttribute('cost', totalCost);
        select.add(opt);
    }

    select.value = Math.min(currentVal, maxAvailable) || '';
}

/**
 * Walks all Op selects inside a selectedPlatoon/selectedCard in order.
 * Builds a live team pool from the .select-element's chosen option, then for
 * each Op select enables/disables individual options based on how many of the
 * required team are actually available in the pool. Updates the pool as it goes.
 * @param {HTMLElement} selectedPlatoon
 * @returns {Promise}
 */

function regenerateConfigArray(selectedPlatoon) {
    return new Promise(resolve => {
        const selectElement = selectedPlatoon.querySelector("select.select-element");
        let pool = selectElement
            ? getTeamsPool(selectElement.options[selectElement.selectedIndex])
            : {};

        const opSelects = Array.from(selectedPlatoon.querySelectorAll("select, input[type='checkbox']"))
            .filter(el => /Op\d+/.test(el.id))
            .sort((a, b) => {
                const num = el => { const m = el.id.match(/Op(\d+)/); return m ? parseInt(m[1]) : 0; };
                return num(a) - num(b);
            });          

        opSelects.forEach(select => {
            const removeTeam = (select.getAttribute('removeteam') || '').trim().toUpperCase();
            const addTeams   = (select.getAttribute('addteams')   || '').trim().toUpperCase();
            const numberAction = (select.getAttribute('numberaction') || '').trim();
            const isCheckbox   = select.type === 'checkbox';
            // Determine how many options this select should have
            let maxAvailable;
            if (removeTeam !== '') {
                maxAvailable = pool[removeTeam] || 0;
            } else if (addTeams !== '') {
                maxAvailable = pool[addTeams] || 0;
            } else {
                maxAvailable = Infinity;
            }
                // No removeteam = additive or applies to all — always available, skip entirely
            if (removeTeam === '') {
                select.disabled = false;
                const label = select.closest('label') ?? select.parentElement.querySelector('label');
                if (label) label.classList.remove('disabledLabel');
                                if (isCheckbox && select.checked && addTeams !== '') {
                    pool[addTeams] = (pool[addTeams] || 0) + 1;
                }
                return;
            }
            if (isCheckbox) {
                // Checkbox: just enable/disable based on whether the team exists in pool
                select.disabled = (maxAvailable === 0);
                if (select.disabled && select.checked) {
                    select.checked = false;
                }
                const label = select.closest('label');
                if (label) label.classList.toggle('disabledLabel', select.disabled);

                // Update pool if checked
                if (select.checked) {
                    let removedCount;
                    if (numberAction.toUpperCase() === 'ALL') {
                        removedCount = pool[removeTeam] || 0; // remove everything
                    } else {
                        removedCount = 1; // ONE, or any other fixed value
                    }
                    pool[removeTeam] = Math.max(0, (pool[removeTeam] || 0) - removedCount);
                    if (addTeams !== '') {
                        pool[addTeams] = (pool[addTeams] || 0)  + removedCount;
                    }
                }
            } else {
                const effectiveVal = parseInt(select.value) || 0;
                switch (numberAction) {
                    case 'any or All':
                        // Rebuild option list to exactly match what's available
                    
                        if (maxAvailable !== Infinity) {
                            regenerateOptions(select, maxAvailable);
                        }
                        select.disabled = (maxAvailable === 0);

                        // Update pool based on what is now selected
                        
                        if (effectiveVal > 0 && removeTeam !== '') {
                            pool[removeTeam] = Math.max(0, (pool[removeTeam] || 0) - effectiveVal);
                            if (addTeams !== '') {
                                pool[addTeams] = (pool[addTeams] || 0) + effectiveVal;
                            }
                        }
                        break;
                    case 'two': case 'three': case 'four': case 'five':
                        // Rebuild option list to exactly match what's available
                        if (numberAction === 'two') {
                            regenerateOptions(select, Math.min(maxAvailable, 2));
                        } else if (numberAction === 'three') {
                            regenerateOptions(select, Math.min(maxAvailable, 3));
                        } else if (numberAction === 'four') {
                            regenerateOptions(select, Math.min(maxAvailable, 4));
                        } else if (numberAction === 'five') {
                            regenerateOptions(select, Math.min(maxAvailable, 5));
                        }
                        select.disabled = (maxAvailable === 0);

                        // Update pool based on what is now selected
                        
                        if (effectiveVal > 0 && removeTeam !== '') {
                            pool[removeTeam] = Math.max(0, (pool[removeTeam] || 0) - effectiveVal);
                            if (addTeams !== '') {
                                pool[addTeams] = (pool[addTeams] || 0) + effectiveVal;
                            }
                        }
                        break;
                    case 'up to half':
                        // Rebuild option list to exactly match what's available
                    
                        if (maxAvailable !== Infinity) {
                            regenerateOptions(select, maxAvailable/2);
                        }
                        select.disabled = (maxAvailable === 0);

                        // Update pool based on what is now selected
                        
                        if (effectiveVal > 0 && removeTeam !== '') {
                            pool[removeTeam] = Math.max(0, (pool[removeTeam] || 0) - effectiveVal);
                            if (addTeams !== '') {
                                pool[addTeams] = (pool[addTeams] || 0) + effectiveVal;
                            }
                        }
                        break;
                    case 'ALL':
                        // Fixed options — just enable/disable the whole select based on availability
                        select.disabled = false;

                        break;
                    default:
                        // Fixed options — just enable/disable the whole select based on availability
                        select.disabled = (maxAvailable === 0);
                        if (select.disabled) select.value = '';
                        break;
                }
            }
            

        });

        selectedPlatoon.setAttribute('teamsNumbers', JSON.stringify(
            Object.entries(pool).map(([name, count]) => ({ name, count }))
        ));

        resolve();
    });
}


document.addEventListener("DOMContentLoaded", function () {
    
    const boxesCheckboxes = document.querySelectorAll("input[platoonCheckbox]");
    const fCardsCheckboxes = document.querySelectorAll("input[fCardCheckbox]");
    
    const collapsableHeader = document.querySelectorAll(".collapsible");
    const grids = document.querySelectorAll(".grid");
    const viewListButtons = [
        document.getElementById('process-link'),
        document.getElementById("viewListTopButton")
      ];


    const overlay = document.getElementById("infoOverlay");
    const closeOverlay = document.getElementById("closeOverlay");
    const platoonDetails = document.getElementById("platoonDetails");

        if (typeof linkQuery !== 'undefined' && linkQuery) {
            const newUrl = `${window.location.pathname}?${linkQuery}${window.location.hash}`;
            window.history.replaceState({}, '', newUrl); // Replace the current URL without reloading
        }

    // urlParams must be captured AFTER the linkQuery replaceState above,
    // so it reflects the actual current URL (including any linkQuery overrides).
    const urlParams = new URLSearchParams(window.location.search);
    // Preserve selected period (pd) for all AJAX/URL steps so server uses correct DB
    const pdParam = urlParams.get('pd') || (typeof linkQuery !== 'undefined' && linkQuery ? (new URLSearchParams(linkQuery).get('pd')) : null);

    function appendPdToFormData(fd) {
        if (!pdParam) return;
        try {
            if (typeof fd.has === 'function') {
                if (!fd.has('pd')) fd.append('pd', pdParam);
            } else if (fd instanceof Object) {
                // fallback for plain objects used as payloads
                if (!('pd' in fd)) fd.pd = pdParam;
            }
        } catch (e) {
            // no-op
        }
    }

    // Special Button Handling
    viewListButtons.forEach(button => { 
        button.addEventListener('click', function () {
        const form = document.getElementById('form');
        cleanEmptyFieldsBeforeSubmission(form);
        const formData = new FormData(form);
        appendPdToFormData(formData);

        // Convert FormData to URL query string
        const queryString = new URLSearchParams(formData).toString();


        fetch(`getNewLink.php?${queryString}`, {
            method: 'GET'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                    // Update address bar for legal evaluation
                    window.history.replaceState({}, '', data.updateUrl);
                    // Redirect to the printable list
                    window.location.href = data.redirectUrl;
            } 
        })
        .catch(error => {
            console.error('Error:', error);
        });
    });
    });
     
    document.getElementById("viewListBFTopButton").addEventListener('click', function () {
        const form = document.getElementById('form');
        cleanEmptyFieldsBeforeSubmission(form);
        const formData = new FormData(form);
        appendPdToFormData(formData);

        // Convert FormData to URL query string
        const queryString = new URLSearchParams(formData).toString();


        fetch(`getNewLink.php?${queryString}`, {
            method: 'GET'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Redirect to the returned URL
                window.location.href = data.url.replace("listPrintGet.php","listPrintGetBFStyle.php");
            } 
        })
        .catch(error => {
            console.error('Error:', error);
        });
    });

    const saveListForm = document.getElementById('saveListForm');
    if (saveListForm) {
        saveListForm.querySelectorAll('button[type="submit"]').forEach(button => {
        button.addEventListener('click',  function (event) {
            if (button.name === "updateSelected" && !confirm("Are you sure you want to update the selected list?")) {
                return;
            }
            if (button.name != "loadSelected") {
                event.preventDefault(); // Prevent the default form submission
                const form = document.getElementById('form');
                cleanEmptyFieldsBeforeSubmission(form);
                const formData = new FormData(form);
                const saveListFormData = new FormData(saveListForm);
                appendPdToFormData(formData);
                appendPdToFormData(saveListFormData);

                const queryString = new URLSearchParams(formData).toString();

                saveListFormData.append(button.name, button.value);
                if (button.name == "save_url"||button.name == "updateSelected") {
                    saveListFormData.append("refreshSelected", true);
                }
                // Convert FormData to URL query string
                const path = window.location.pathname;
                const page = path.substring(path.lastIndexOf('/') + 1);
                fetch(`saveNewLink.php?${queryString}`, {
                    method: 'post',
                    body: saveListFormData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {

                        switch (button.name) {
                            case "save_url":
                            case "updateSelected": {


                                const listFrame = document.querySelector("#listNameListFrame"); 
                                listFrame.innerHTML = ''; // Clear old radios

                                data.listList.forEach(list => {
                                    const id = "listNameList_" + list.value;

                                    // Create label
                                    const label = document.createElement("label");
                                    label.setAttribute("for", id);
                                    label.className = "list-item";
                                    label.dataset.nation = list.nation;
                                    label.dataset.period = list.period;
                                    label.dataset.event = list.event;

                                    // Radio input
                                    const radio = document.createElement("input");
                                    radio.type = "radio";
                                    radio.name = "listNameList";
                                    radio.id = id;
                                    radio.value = list.value;
                                    if (list.selected) radio.checked = true;

                                    // Images
                                    const imgPeriod = document.createElement("img");
                                    imgPeriod.className = "period insignia";
                                    imgPeriod.src = "img/" + list.period + ".svg";
                                    imgPeriod.alt = "";

                                    const imgNation = document.createElement("img");
                                    imgNation.className = "insignia";
                                    imgNation.src = "img/" + list.nation + ".svg";
                                    imgNation.alt = "";

                                    // Text spans
                                    const textSpan = document.createElement("span");
                                    textSpan.className = "list-text";
                                    textSpan.textContent = list.description;

                                    const eventSpan = document.createElement("span");
                                    eventSpan.className = "event-text";
                                    eventSpan.textContent = list.event;

                                    // Build label
                                    label.appendChild(radio);
                                    label.appendChild(imgPeriod);
                                    label.appendChild(imgNation);
                                    label.appendChild(textSpan);
                                    label.appendChild(eventSpan);

                                    listFrame.appendChild(label);
                                });

                                showToast(
                                    (button.name === "save_url") 
                                        ? "List saved successfully!" 
                                        : "List updated!", 
                                    "success"
                                );
                                break;
                                /*



                               
                                const listDropdown = document.querySelector("#listNameList");
                                const defaultOption = listDropdown.querySelector('option[value=""]');
                                listDropdown.innerHTML = '';
                                if (defaultOption) {
                                    listDropdown.appendChild(defaultOption);
                                }
                                data.listList.forEach(list => {
                                    const option = document.createElement('option');
                                    option.value = list.value;
                                    option.textContent = list.description;
                                    option.selected = list.selected;
                                    listDropdown.appendChild(option);
                                });
                                showToast((button.name === "save_url")?"List saved successfully!":"List updated!", "success");
                                break;
                                */
                            
                            }
                            default: {
                                window.location.href = `${page}?${data.query}`;
                                break;
                            }
                        }
                    } 
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast("An error occurred while saving.", error);
                });
            }
        });
    });
    }
    
    function showToast(message, type = "success") {
        const toast = document.getElementById('toast');
        const toastMessage = document.getElementById('toast-message');
    
        toastMessage.textContent = message;
    
        // Anpassa färger beroende på typ (success, error, info)
        if (type === "success") {
            toast.style.backgroundColor = "#4CAF50"; // Grön
        } else if (type === "error") {
            toast.style.backgroundColor = "#f44336"; // Röd
        } else {
            toast.style.backgroundColor = "#333"; // Standard mörk
        }
    
        // Visa toast
        toast.style.opacity = "1";
        toast.style.transform = "translateX(-50%) translateY(0)";
        toast.classList.add("flash-effect");
        
        // Dölj efter 3 sekunder
        setTimeout(() => {
            toast.style.opacity = "0";
            toast.style.transform = "translateX(-50%) translateY(20px)";
        }, 3000);
    }

    function updateSelectElements() {
        return new Promise(resolve => {
        const selectElements = document.getElementById("form").querySelectorAll('input[type="checkbox"], select, button[id*="box"]');
        selectElements.forEach((element) => {
            // Skip elements that already had listeners attached in a previous call
            if (element.dataset.listenerAttached) return;
            element.dataset.listenerAttached = "1";
            if ((   element.tagName === 'SELECT' || 
                    element.type === 'checkbox')&&
                    !element.hasAttribute('platoonCheckbox')&&
                    
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
            else if (element.tagName === 'BUTTON' && element.id && element.id.includes("box")) {
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
            // Add click event listener for buttons
            else if (element.tagName === 'BUTTON') {
                element.addEventListener('click', function () {
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

    // Fetch platoon details for information via AJAX
    function fetchPlatoonDetails(codes) {        
        fetch("fetchPlatoonDetails.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify((() => { const o = {codes: codes}; if (pdParam) o.pd = pdParam; return o; })())
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

    // Fetch platoon config via AJAX
    function fetchPlatoonConfig(data, selectedPlatoon) {    
        const platoonData = typeof data === 'string' ? JSON.parse(data) : data;
        
        return fetch("selectedPlatoonConfig.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify((() => { const o = {platoonInfo: platoonData}; if (pdParam) o.pd = pdParam; return o; })())
        })
            .then(response => response.json())
            .then(data => {
                
                if (data.success) {
                    selectedPlatoon.innerHTML = data.html;
                    selectedPlatoon.classList.add("selected");

                    // Restore checkbox/select state from URL — server injects HTML unchecked,
                    // so we re-apply URL params to check the right boxes.
                    const currentParams = new URLSearchParams(window.location.search);
                    selectedPlatoon.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                        const name = cb.getAttribute('name');
                        if (name && currentParams.has(name) && currentParams.get(name) === cb.value) {
                            cb.checked = true;
                        }
                    });
                    selectedPlatoon.querySelectorAll('select').forEach(sel => {
                        const name = sel.getAttribute('name');
                        const val = name && currentParams.get(name);
                        if (val) sel.value = val;
                    });
                } else {
                    console.error("Error:", data.error);
                    platoonDetails.innerHTML = `<p>Error fetching details</p>`;
                }
            })
            .then(() => {
                return regenerateConfigArray(selectedPlatoon); // selectedPlatoon here is actually the selectedCard element

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
    // Fetch card config via AJAX
    function fetchCardConfig(data, selectedPlatoon) {    
    const parsed = typeof data === 'string' ? JSON.parse(data) : data;

    // Ensure pd included in card config payload so server picks correct DB
    if (pdParam && typeof parsed === 'object') parsed.pd = parsed.pd || pdParam;
        
        return fetch("selectedCardConfig.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
        body: JSON.stringify(parsed) // ← send directly, not wrapped in cardInfo
        })
    .then(response => response.text())
    .then(text => {

        return JSON.parse(text); // ← only parse once
    })
            .then(data => {
                if (data.success) {
                    selectedPlatoon.innerHTML = data.html;
                    selectedPlatoon.classList.add("selected");
                } else {
            console.error("PHP error:", data);
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

// Fetch card details for info via AJAX
    function fetchCardnDetails(codes) {        
        fetch("fetchCardDetails.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify((() => { const o = {codes: codes}; if (pdParam) o.pd = pdParam; return o; })())
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
            const content = this.nextElementSibling;
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
                    const listHeaders = document.querySelectorAll(".collapsible");
                    let numberOfAlliesAllowed = 1;
                    listHeaders.forEach(thisHeader => {
                        if (thisHeader.classList.contains("Italian")) {
                            numberOfAlliesAllowed = 2;                      
                        }
                    } );
                    const alliesCheckboxes = document.querySelectorAll("input[ally]");
                    alliesCheckboxes.forEach(alliedCheckbox => {
                        if (alliedCheckbox != currentCheckbox) {
                            if (alliedCheckbox.checked ) {
                                if (numberOfAlliesAllowed > 1) {
                                    numberOfAlliesAllowed--;
                                } else {
                                    alliedCheckbox.checked = false;
                                    const otherPlatoonConfig = alliedCheckbox.parentElement.querySelector(".selectedPlatoon");
                                    updateCostCalculation(otherPlatoonConfig);
                                    removeChildElements(otherPlatoonConfig);
                                }
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
                    // Save the current box points BEFORE wiping — the .Points div may live
                    // inside .selectedPlatoon (not as a sibling), so it will be destroyed.
                    // recalculateBoxPoints needs this to compute the correct delta.
                    const box = platoonConfig.closest('.box');
                    const currentPointsDiv = platoonConfig.querySelector('.Points div');
                    if (box && currentPointsDiv) {
                        box.dataset.lastKnownPoints = parseInt(currentPointsDiv.innerText) || 0;
                    }

                    // Query children BEFORE clearing innerHTML — after the wipe the NodeList is empty
                    const childElements = platoonConfig.querySelectorAll('select, input');
                    const freshParams = new URLSearchParams(window.location.search);
                    childElements.forEach(child => {
                        const childName = child.getAttribute("name");
                        if (childName) {
                            freshParams.delete(childName);
                        }
                    });
                    platoonConfig.classList.remove("selected");
                    platoonConfig.innerHTML = "";
                    const newUrl = `${window.location.pathname}?${freshParams.toString()}${window.location.hash}`;
                    updateURL(newUrl);
                }
                resolve();
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
                        // Step 4b: Re-evaluate all Block prerequisites now that new platoon DOM is present
                        applyAllBlockPrerequisites();
                    })
                    .then(() => {
                        // Step 5: Adjust the grid layout
                        return redistributeGrid(box);
                    })
                    .then(() => {
                        // Step 6: Update the URL
                        const freshParams = new URLSearchParams(window.location.search);
                        const otherQueryAttributes = box.querySelectorAll('input[platoonCheckbox]');
                        otherQueryAttributes.forEach(element => {
                            if (element !== checkbox && !element.checked) {
                                freshParams.delete(element.getAttribute("name"));
                            }
                        });
    
                        const queryAttribute = checkbox.getAttribute("name");
                        const platoonCode = checkbox.value;
    
                        if (checkbox.checked) {
                            freshParams.set(queryAttribute, platoonCode);
                        } else {
                            freshParams.delete(queryAttribute);
                        }
    
                        const newUrl = `${window.location.pathname}?${freshParams.toString()}${window.location.hash}`;
                        updateURL(newUrl);
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
                        // Step 4: Update dependent select elements
                        return updateSelectElements();
                    })
                    .then(() => {
                        // Step 4b: Re-evaluate all Block prerequisites now that new card DOM is present
                        applyAllBlockPrerequisites();
                    })
                    .then(() => {
                        // Step 5: Adjust the grid layout
                        return redistributeGrid(box);
                    })
                    .then(() => {
                        const freshParams = new URLSearchParams(window.location.search);
                        const queryAttribute = checkbox.getAttribute("name");
                        const platoonCode = checkbox.value;
    
                        if (checkbox.checked) {
                            freshParams.set(queryAttribute, platoonCode);
                        } else {
                            freshParams.delete(queryAttribute);
                        }
    
                        const newUrl = `${window.location.pathname}?${freshParams.toString()}${window.location.hash}`;
                        updateURL(newUrl);
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
                    .catch(error => console.error(error));
            }

            // 🔴 SCENARIO 2: Platoon is DESELECTED
            else {
                // 1. Remove the card from the search string
                const freshParams = new URLSearchParams(window.location.search);
                freshParams.delete(queryAttribute);
                const newUrl = `${window.location.pathname}?${freshParams.toString()}${window.location.hash}`;
                updateURL(newUrl);
                if (thisCardConfig) {
                    removeChildElements(thisCardConfig)
                    .then(() => redistributeGrid(box))
                    .then(() => updateCostCalculation(thisCardConfig))
                    .then(() => applyAllBlockPrerequisites());
                }
            }
        });
    });
    
    boxesCheckboxes.forEach(checkbox => {
        initiatePrerequisites(checkbox.parentElement)
        checkbox.addEventListener("change", function () {

            updatePlatoonCheckboxPrerequisites();

            const queryAttribute = this.getAttribute("name");
            const platoon = this.parentElement;
            const platoonCode = this.value; // Checkbox value (platoon code)
            const box = platoon.parentElement;
            
            const thisPlatoonConfig = platoon.querySelector(".selectedPlatoon");
            const isBlackBox = platoon.classList.contains('blackbox'); // Check if parent is blackbox

            if (!box) return;
            
            // 🟢 SCENARIO 1: Platoon is SELECTED
            if (this.checked && !this.disabled) {
                handlePlatoonSelection(box, this, thisPlatoonConfig, false)
                    .catch(error => console.error(error));
            }

            // 🔴 SCENARIO 2: Platoon is DESELECTED
            else {
                this.checked = false;
                // 1. Remove the platoon from the search string
                const freshParams = new URLSearchParams(window.location.search);
                freshParams.delete(queryAttribute);
                const newUrl = `${window.location.pathname}?${freshParams.toString()}${window.location.hash}`;
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

    // ─── Page initialisation ────────────────────────────────────────────────

    function syncDOMWithURL() {
        // Synka alla platoon-checkboxar
        document.querySelectorAll('input[type="checkbox"][platoonCheckbox]').forEach(checkbox => {
            const name = checkbox.getAttribute('name');
            const value = checkbox.value;

            if (urlParams.has(name) && urlParams.get(name) === value) {
                checkbox.checked = true;
            } else {
                checkbox.checked = false;
            }

            const platoon = checkbox.closest('.platoon, .blackbox');
            const box = platoon?.closest('.box');
            const selectedPlatoonConfig = platoon?.querySelector('.selectedPlatoon');
            const isBlackBox = platoon?.classList.contains('blackbox');

            if (checkbox.checked && box && selectedPlatoonConfig) {
                if (selectedPlatoonConfig.innerHTML.trim() !== '') {
                    // Blackbox: server already rendered this content correctly.
                    // Just restore checkbox/select state inside it — do NOT wipe and re-fetch,
                    // as removeChildElements would strip URL params (like F1-2Card1) before
                    // fetchPlatoonConfig could restore them.
                    selectedPlatoonConfig.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                        const cbName = cb.getAttribute('name');
                        if (cbName && urlParams.has(cbName) && urlParams.get(cbName) === cb.value) {
                            cb.checked = true;
                        }
                    });
                    selectedPlatoonConfig.querySelectorAll('select').forEach(sel => {
                        const selName = sel.getAttribute('name');
                        const selVal = selName && urlParams.get(selName);
                        if (selVal) sel.value = selVal;
                        regenerateConfigArray(selectedPlatoonConfig);    
                    });
                } else {
                    // Regular platoon or empty blackbox: fetch content via AJAX.
                    handlePlatoonSelection(box, checkbox, selectedPlatoonConfig, isBlackBox);
                }
            }
        });

        // Synka alla <select>-element outside .selectedPlatoon (e.g. formation switchers)
        document.querySelectorAll('select').forEach(select => {
            const name = select.getAttribute('name');
            const value = urlParams.get(name);

            if (value) {
                select.value = value;
                // Do not call updatePoints here — the server already rendered the correct
                // totals; calling updatePoints would add to them a second time.
            }
        });
    }

    function initPage() {
        syncDOMWithURL();
        updatePlatoonCheckboxPrerequisites();
        updateLimitedCardPrerequisites();
        enforceUniqueFormationCardTitles();
        // syncDOMWithURL fires AJAX for each checked platoon; applyAllBlockPrerequisites
        // runs after a short yield so Block rules see the fully-restored DOM.
        setTimeout(applyAllBlockPrerequisites, 0);
    }


    /**
     * Formation card title enforcement:
     * - Once any formation selects a card title, all other formations must use
     *   that same title (if available) — no other title can be chosen.
     * - If a formation has no option matching the selected title, its select
     *   is fully disabled (that formation can't use any card).
     * - If no title is selected anywhere, all selects are unrestricted.
     */
    function enforceUniqueFormationCardTitles() {
        const allCardSelects = Array.from(document.querySelectorAll('select[fcardselect]'));
 
        // Find the globally active title (first one found across all selects)
        let activeTitle = null;
    let sourceSel = null; // ← track which select set the title
        allCardSelects.forEach(sel => {
            if (activeTitle) return;
            const chosen = sel.options[sel.selectedIndex];
            const title = chosen?.getAttribute('data-cardtitle');
        if (title) {
            activeTitle = title;
            sourceSel = sel; // ← remember the source
        }
        });
 
        allCardSelects.forEach(sel => {
        if (!activeTitle || sel === sourceSel) {
            // No title selected anywhere, OR this is the select that set it — unrestrict
                Array.from(sel.options).forEach(opt => { opt.disabled = false; });
                sel.disabled = false;
                return;
            }
 
            // Check if this select has an option matching the active title
            const matchingOpt = Array.from(sel.options).find(
                opt => opt.getAttribute('data-cardtitle') === activeTitle
            );
 
            if (!matchingOpt) {
                // No matching option — disable the entire select
                sel.disabled = true;
                sel.selectedIndex = 0;
                return;
            }
 
            // Has a matching option — enable select, disable all non-matching titled options
            sel.disabled = false;
            Array.from(sel.options).forEach(opt => {
                const title = opt.getAttribute('data-cardtitle');
            // Only disable if it HAS a title AND it doesn't match — no title = always available
                opt.disabled = title !== null && title !== activeTitle;
            });
        });
    }

    initPage();

    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            initPage();
        }
    });

});