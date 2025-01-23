$('#submit').click(function(e){ 
    e.preventDefault();
});

function clearParameterAndSubmit(param, form) {

    const dropdown = document.getElementById(param);
    if (dropdown) dropdown.value = 0;
    form.submit();
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

// Function to handle the change or click event
function handleElementChange(element) {
    // Update the lsID with the ID of the changed or clicked element
    /*
    var lsID = element.id;
    var parts = lsID.split("box");
    lsID = parts[0];
    document.getElementById('lsID').value = lsID;
    sessionStorage.setItem('lsID', lsID);
    window.location.hash = lsID + 'box';
    */
    
}
function decreaseNOF() {
    const nOFSelect = document.getElementById('nOF');
    const currentVal = parseInt(nOFSelect.value);

    // Increment by 1, ensuring it doesn't exceed the maximum value (3)
    if (currentVal > 0) {
        nOFSelect.value = currentVal - 1;
        nOFSelect.form.submit();  // Submit the form after incrementing
    }
}

function incrementNOF() {
    const nOFSelect = document.getElementById('nOF');
    const currentVal = parseInt(nOFSelect.value);

    // Increment by 1, ensuring it doesn't exceed the maximum value (3)
    if (currentVal < 6) {
        nOFSelect.value = currentVal + 1;
        nOFSelect.form.submit();  // Submit the form after incrementing
    }
}

document.addEventListener("DOMContentLoaded", function () {
    const boxesCheckboxes = document.querySelectorAll("input[platoonCheckbox]");
    const collapsableHeader = document.querySelectorAll(".collapsible");
    const grids = document.querySelectorAll(".grid");
    const selectElements = document.querySelectorAll('form input[type="checkbox"], form select, form button[id*="box"]');

    const overlay = document.getElementById("infoOverlay");
    const closeOverlay = document.getElementById("closeOverlay");
    const platoonDetails = document.getElementById("platoonDetails");
    const urlParams = new URLSearchParams(window.location.search);
    

    // Show overlay when info button is clicked
    document.querySelectorAll(".info-btn").forEach(button => {
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

    // Close overlay
    closeOverlay.addEventListener("click", () => {
        overlay.classList.add("hidden");
        platoonDetails.innerHTML = ""; // Clear previous details
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
                console.log(data);
                if (data.success) {
                    console.log(data.weapons);
                    
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
    function fetchPlatoonConfig(codes,platoon) {        
        fetch("selectedPlatoonConfig.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({codes: codes})

        })
            .then(response => response.json())
            .then(data => {
                console.log(data);
                if (data.success) {
                    console.log(data.weapons);
                    
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
    function fetchCardnDetails(codes) {        
        fetch("fetchCardDetails.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({codes: codes})

        })
            .then(response => response.json())
            .then(data => {
                console.log(data);
                if (data.success) {
                    console.log(data.cards);
                    
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
    /*
    // Initialize the lsID with the first element's ID
    let lsID = selectElements[0].id;

    // On page load, retrieve the value from Session Storage
    lsID = sessionStorage.getItem('lsID');

    // Update the hidden input field's value
    if (lsID) {
        document.getElementById('lsID').value = lsID;
        window.location.hash = lsID + 'box';
    }
        */

    selectElements.forEach((element) => {
        // Check if the element is a checkbox or select element
        if (element.tagName === 'SELECT' || element.type === 'checkbox') {
            element.addEventListener('change', function () {
                handleElementChange(element);
                /*
                const form = this.closest("form");
                if (form) {
                    form.submit();
                }
                    */
            });
        }
        // Add click event listener for buttons
        else if (element.tagName === 'BUTTON') {
            element.addEventListener('click', function () {
                handleElementChange(element);
                const form = this.closest("form");
                if (form) {
                    form.submit();
                }
            });
        }
    });
    
    grids.forEach(function(grid) {
        const boxes = grid.querySelectorAll(".box");
        const gridHeight = 37;
        boxes.forEach(box => {
            box.style.gridRowEnd = "span 1";
            var height = box.scrollHeight;
            // Set the grid-row property based on the height
            for (var index = 1; index <  Math.floor(height/gridHeight)+3; index++) {
                if ((height+12) > ((gridHeight*index))) {
                    box.style.gridRowEnd = "span " + (index+1);
                }
            }

        });
    });

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
     * @param {HTMLElement} box - The parent box container
     * @param {HTMLElement} currentCheckbox - The current checkbox being interacted with
     */
    function uncheckOtherCheckboxes(box, currentCheckbox) {
        const groupCheckboxes = box.querySelectorAll('input[platoonCheckbox]');

        groupCheckboxes.forEach(otherCheckbox => {
            if (otherCheckbox !== currentCheckbox) {
                otherCheckbox.checked = false;
            }
        });
    }

    /**
     * Removes child elements from the given platoon when unchecked
     * @param {HTMLElement} platoonConfig - The platoon configuration div
     */
    function removeChildElements(platoonConfig) {
        if (platoonConfig) {
            platoonConfig.classList.remove("selected");
            const childElements = platoonConfig.querySelectorAll('select, input');
            childElements.forEach(child => {
                const childName = child.getAttribute("name");
                if (childName) {
                    urlParams.delete(childName);
                }
            });
        }
    }

    boxesCheckboxes.forEach(checkbox => {
        checkbox.addEventListener("change", function () {

            const group = this.getAttribute("name"); // Or: const group = this.classList[0];
            const platoon = this.parentElement;
            const platoonCode = this.value; // Checkbox value (platoon code)
            const box = platoon.parentElement;
            const thisPlatoonConfig = platoon.querySelector(".selectedPlatoon");
            const isBlackBox = platoon.classList.contains('blackbox'); // Check if parent is blackbox
            console.log(isBlackBox);
            
            
            if (!box) return; // Skip if no group identifier is found

            console.log(urlParams.toString());
            
            
            // 🟢 SCENARIO 1: Platoon is SELECTED
            if (this.checked) {
                // 1. Uncheck all other checkboxes in the same box
                uncheckOtherCheckboxes(box, this);

                // 3. Update search string for the current platoon
                urlParams.set(group, platoonCode);

                const groupSelectedPlatoonConfig = box.querySelectorAll(".selectedPlatoon");
                groupSelectedPlatoonConfig.forEach(otherPlatoonConfig => {
                    if (otherPlatoonConfig === thisPlatoonConfig) {
                        otherPlatoonConfig.classList.add("selected");
                    } else {
                        removeChildElements(otherPlatoonConfig);
                    }
                });
            } 

            // 🔴 SCENARIO 2: Platoon is DESELECTED
            else {
                if (isBlackBox) {
                    // Prevent deselecting the last platoon in a blackbox
                    const firstPlatoonCheckbox = box.querySelector('input[platoonCheckbox]');
                    if (firstPlatoonCheckbox && firstPlatoonCheckbox !== this) {
                        firstPlatoonCheckbox.checked = true;
                        const platoon = firstPlatoonCheckbox.parentElement;
                        platoon.querySelector(".selectedPlatoon").classList.add("selected");
                        
                        urlParams.set(firstPlatoonCheckbox.getAttribute("name"), firstPlatoonCheckbox.value);
                    } else if (firstPlatoonCheckbox === this) {
                        // If the current checkbox is the first one, recheck it
                        this.checked = true;
                        urlParams.set(group, platoonCode);
                    }
                } else {
                    // 1. Remove the platoon from the search string
                    urlParams.delete(group);

                    // 2. Remove `.selected` class from the platoon config
                    if (thisPlatoonConfig) {
                        removeChildElements(thisPlatoonConfig);
                    }
                }
            }

            // ✅ Update the URL in the browser without reloading
            const newUrl = `${window.location.pathname}?${urlParams.toString()}${window.location.hash}`;
            window.history.pushState({}, '', newUrl);

            console.log("Updated URL:", newUrl);
            // Optionally, submit the form if applicable
            /*
            const form = this.closest("form");
            if (form) {
                form.submit();
            }
                */

        });
    });
    // Show/hide the button based on scroll position
    window.addEventListener("scroll", function () {
        if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
            document.getElementById("backToTopButton").style.display = "block";
        } else {
            document.getElementById("backToTopButton").style.display = "none";
        }
    });

    // Scroll to the top when the button is clicked
    document.getElementById("backToTopButton").onclick = function () {
        document.body.scrollTop = 0;
        document.documentElement.scrollTop = 0;
    };

});