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

document.addEventListener('DOMContentLoaded', () => {
    setSelectionsFromURL();

    function updateURLParams() {
        const urlParams = new URLSearchParams(window.location.search);
    
        $('select').each(function() {
            const id = $(this).attr('id');
            const value = $(this).val();
            if (value) {
                urlParams.set(id, value);
            } else {
                urlParams.delete(id);
            }
        });
    
        const newUrl = `${window.location.pathname}?${urlParams.toString()}`;
        window.history.replaceState({}, '', newUrl);
    }
    
    
    function fetchPeriod() {
        const cached = localStorage.getItem('periods');
        if (cached) {
            generateDropdown('pd', JSON.parse(cached).Periods, 'period', 'periodLong', 'Select Period');
            generateButtons('pd', JSON.parse(cached).Periods, 'period');
        } else {
            $.ajax({
                url: 'api/backend.php',
                type: 'GET',
                data: { action: 'fetchInitialData' },
                success: function(response) {
                    localStorage.setItem('periods', JSON.stringify(response));
                    generateDropdown('pd', response.Periods, 'period', 'periodLong', 'Select Period');
                    generateButtons('pd', response.Periods, 'period');
                }
            });
        }
    }
    
    function setSelectionsFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        const nation = urlParams.get('ntn');
        const book = urlParams.get('Book');
        const pd = urlParams.get('pd');
        fetchPeriod();
        if (pd) {
            $('#pd').val(pd).trigger('change');
            fetchNations(pd);
            if (nation) {
                $('#ntn').val(nation).trigger('change');
                fetchBooks(pd, nation);
            }
        }
    }


    document.getElementById('mainForm').addEventListener('change', (event) => {
        const target = event.target;
        updateURLParams();
        const urlParams = new URLSearchParams(window.location.search);
        const nation = urlParams.get('ntn');
        const book = urlParams.get('Book');
        const pd = urlParams.get('pd');

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
    document.getElementById('mainForm').addEventListener('click', (event) => {
        const target = event.target;  
            
        if (target.tagName == 'IMG') {
            var button = target.closest('button');
        } else {
            var button = target;
        }
        updateURLParams();
        const urlParams = new URLSearchParams(window.location.search);
        const nation = urlParams.get('ntn');
        const book = urlParams.get('Book');
        const pd = urlParams.get('pd');
        if (button.id.includes('Clear')) {
            const parent = document.getElementById(button.id.replace('Clear', ''));
            parent.value = "";
            const configItem = config[parent.name];
            console.log(configItem);
            if (configItem) {
                const nextDropdown = document.getElementById(configItem.nextDropdown);
                console.log(nextDropdown);
                if (nextDropdown) {
                    $(nextDropdown).prop('disabled', true);
                }
                configItem.fetchFunction && configItem.fetchFunction(pd, nation);
            }
                
            switch (parent.name ) {
                case 'pd':
                    $('#ntn').prop('disabled', true);
                    $('#ntnClear').prop('disabled', true);
                    $('#formation').empty();
                    fetchPeriod();
                    break;
                case 'ntn':
                    $('#Book').prop('disabled', true);
                    $('#BooClear').prop('disabled', true);
                    $('#formation').empty();
                    fetchNations(pd);
                    break;
                default: break;
            }
            updateURLParams();
        } else if (button.type != 'select-one') {

            
            switch (button.name) {
                case 'pd':
                    fetchNations(button.value);
                    $('#ntn').prop('disabled', false);
                    $('#ntnClear').prop('disabled', false);
                    $('#pd').val(button.value);
                    break;
                case 'ntn':
                    $('#ntn').val(button.value);
                    fetchBooks(pd, button.value); 
                    $('#Book').prop('disabled', false);
                    $('#BooClear').prop('disabled', false);
                    break;
                default: break;
            }
            updateURLParams();
        }
    })
});



function fetchNations(pd) {
    const cached = localStorage.getItem(`nations_${pd}`);
    if (cached) {
        generateDropdown('ntn', JSON.parse(cached).Nations, 'Nation', 'Nation', 'Select Nation');
        generateButtons('ntn', JSON.parse(cached).Nations, 'Nation');
        
    } else {
        $.ajax({
            url: 'api/backend.php',
            type: 'GET',
            data: { action: 'fetchNations', period: pd },
            success: function(response) {
                    localStorage.setItem(`nations_${pd}`, JSON.stringify(response));
                    generateDropdown('ntn', response.Nations, 'Nation', 'Nation', 'Select Nation');
                    generateButtons('ntn', response.Nations, 'Nation');
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching nations:', status, error);
                }
            });
    }
}

function fetchBooks(pd, nation) {
    const cached = localStorage.getItem(`books_${pd}_${nation}`);
    if (cached) {
        generateDropdown('Book', JSON.parse(cached).Books, 'code', 'Book', 'Select Book');
        generateButtons('Book', JSON.parse(cached).Books, 'Book');
        localStorage.setItem(`book`, JSON.stringify(JSON.parse(cached)));
    } else {
        $.ajax({
            url: 'api/backend.php',
            type: 'GET',
            data: { action: 'fetchBooks', period: pd, nation: nation },
            success: function(response) {
                
                    localStorage.setItem(`books_${pd}_${nation}`, JSON.stringify(response));
                    generateDropdown('Book', response.Books, 'code', 'Book', 'Select Book');
                    generateButtons('Book', response.Books, 'Book');
                    localStorage.setItem(`books`, JSON.stringify(response));

                },
                error: function(xhr, status, error) {
                    console.error('Error fetching nations:', status, error);
                }
            });
    }
}

function fetchFormationsInBook(book) {
    const cached = localStorage.getItem(`formations_${book}`);
    const currentBooks = localStorage.getItem(`books`);
    console.log(currentBooks);
    console.log(book);
    
    if (cached) {
        generateDropdown('F1', JSON.parse(cached).Formations, 'code', 'Formation', 'Select Formation');
        generateButtons('F1', JSON.parse(cached).Formations, 'Formation');
        
    } else {
        $.ajax({
            url: 'api/backend.php',
            type: 'GET',
            data: { action: 'fetchFormationsFromBook', Book: book },
            success: function(response) {
                console.log(response);
                
                    localStorage.setItem(`formations_${book}`, JSON.stringify(response));
                    generateDropdown('F1', response.Formations, 'code', 'Formation', 'Select Formation');
                    generateButtons('F1', response.Formations, 'Formation');
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching nations:', status, error);
                }
            });
    }
}



function generateDropdown(name, items, valueKey, textKey, placeholder) {
    let select = $(`#${name}`);
    if (select.length === 0) {
        const header = $('#header');
        select = $('<select></select>').attr('name', name).attr('id', name);
        header.append(select);
    }
    select.empty();
    select.append(`<option value="">${placeholder}</option>`);
    items.forEach(item => {
        const option = $('<option></option>').attr('value', item[valueKey]).text(item[textKey]);
        select.append(option);
    });

    if ($(`#${name}Clear`).length === 0) {
        const clearButton = $('<button></button>').attr('type', 'button').attr('id', `${name}Clear`).text('Clear');
        $('#header').append(clearButton);
    }
}

function generateButtons(name, items, key) {
    const formation = $('#formation');
    formation.empty(); // Clear existing content
    const grid = $('<div></div>').addClass('grid');
    formation.append(grid);
    items.forEach(item => {
        const box = $('<div></div>').addClass('box');
        const platoon = $('<div></div>').addClass('platoon');
        const title = $('<div></div>').addClass('title').css('height', '100px');
        const button = $('<button></button>')
        .attr('type', 'button')
        .attr('name', name)
        .css({width: '100%' });
        switch (key) {
            case 'Nation':
                button.addClass(item[key])
                .attr('value', item[key])
                .html(`<span class='nation'><img src='img/${item[key]}.svg'></span><br>${item[key]}`);
                break;
            case 'Book':
                button.addClass(item['Nation'])
                .attr('value', item['code'])
                .html(`<span class='nation'><img src='img/${item['Nation']}.svg'></span><br>${item[key]}`);
            break;
            case 'period':
                button.addClass(item['period'])
                .attr('value', item['period'])
                .html(`<span class='nation'><img src='img/${item['period']}.svg'></span><br>${item['periodLong']}`);
            break;        
            default:
                break;
        }
        title.append(button);
        platoon.append(title);
        box.append(platoon);
        grid.append(box);
    });
    updateHeight();
}

function updateHeight() {
    var grids = document.querySelectorAll(".grid");
    grids.forEach(function(grid) {
        var boxes = grid.querySelectorAll(".box");

        // Delay the measurement
        var gridHeight = 37;
        for (var i = 0; i < boxes.length; i++) {
            var box = boxes[i];
            box.style.gridRowEnd = "span 1";
            var height = box.scrollHeight;
            // Set the grid-row property based on the height
            for (var index = 1; index <  Math.floor(height/gridHeight)+3; index++) {
                
                if ((height+12) > ((gridHeight*index))) {
                    box.style.gridRowEnd = "span " + (index+1);
                    
                }
            }
        }
    });
}
