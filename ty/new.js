const config = {
    pd: {
        nextDropdown: 'ntn',
        fetchFunction: fetchNations,
        disableNext: true,
    },
    ntn: {
        nextDropdown: 'Book',
        fetchFunction: fetchBooks,
        disableNext: true,
    },
    Book: {
        nextDropdown: null,
        fetchFunction: null,
        disableNext: false,
    }
};

// Function to clear subsequent dropdowns and buttons
function clearSubsequent(name) {
    let clearNext = false;

    for (let key in config) {
        if (clearNext) {
            const nextDropdown = document.getElementById(key);
            if (nextDropdown) {
                nextDropdown.value = "";  // Clear the value
                $(nextDropdown).prop('disabled', true).empty();  // Disable and clear dropdown
            }

            const clearButton = document.getElementById(`${key}Clear`);
            if (clearButton) {
                $(clearButton).remove();  // Remove clear button
            }
        }

        if (key === name) {
            clearNext = true;  // Start clearing subsequent fields after the current one
        }
    }
}

// Generalized change event listener
document.getElementById('mainForm').addEventListener('change', (event) => {
    const target = event.target;
    updateURLParams();
    const urlParams = new URLSearchParams(window.location.search);
    const nation = urlParams.get('ntn');
    const pd = urlParams.get('pd');

    // Clear subsequent dropdowns and buttons
    clearSubsequent(target.name);

    const configItem = config[target.name];
    if (configItem) {
        const nextDropdown = document.getElementById(configItem.nextDropdown);
        if (target.value === "") {
            if (nextDropdown) {
                $(nextDropdown).prop('disabled', true);
                nextDropdown.innerHTML = "";
            }
            configItem.fetchFunction && configItem.fetchFunction(pd, nation);
        } else {
            if (nextDropdown) {
                $(nextDropdown).prop('disabled', false);
            }
            configItem.fetchFunction && configItem.fetchFunction(pd, nation);
        }
    }
});

// Generalized click event listener
document.getElementById('mainForm').addEventListener('click', (event) => {
    const target = event.target;
    const button = target.closest('button') || target; // Find parent button if the image is clicked

    if (button) {
        updateURLParams();
        const urlParams = new URLSearchParams(window.location.search);
        const nation = urlParams.get('ntn');
        const pd = urlParams.get('pd');
        
        if (button.id.includes('Clear')) {
            const parent = document.getElementById(button.id.replace('Clear', ''));
            parent.value = "";

            // Clear subsequent dropdowns and buttons
            clearSubsequent(parent.name);

            const configItem = config[parent.name];
            if (configItem) {
                const nextDropdown = document.getElementById(configItem.nextDropdown);
                if (nextDropdown) {
                    $(nextDropdown).prop('disabled', true).empty();
                }
                configItem.fetchFunction && configItem.fetchFunction(pd, nation);
            }
            updateURLParams();
        } else if (button.type !== 'select-one') {
            const configItem = config[button.name];
            if (configItem) {
                $(`#${button.name}`).val(button.value);
                configItem.fetchFunction && configItem.fetchFunction(pd, button.value);
                updateURLParams();
            }
        }
    }
});
