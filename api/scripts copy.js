
$(document).ready(function() {
    // Load initial data and set selections from URL
    fetchInitialData();


    function generateOptions(items, valueField, textField, defaultOption) {
        let options = `<option value="">${defaultOption}</option>`;
        options += items.map(item => `<option value="${item[valueField]}">${item[textField]}</option>`).join('');
        return options;
    }

    function updateURLParams() {
        const urlParams = new URLSearchParams(window.location.search);

        $('select').each(function() {
            const id = $(this).attr('id').replace('Dropdown', '');
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



    function fetchInitialData() {
        const cached = localStorage.getItem('periods');
        if (cached) {
            $('#pdDropdown').html(generateOptions(JSON.parse(cached).Periods, 'period', 'periodLong', 'Select Period'));
            setSelectionsFromURL();
        } else {
            $.ajax({
                url: 'api/backend.php',
                type: 'GET',
                data: { action: 'fetchInitialData' },
                success: function(response) {
                    localStorage.setItem('periods', JSON.stringify(response));
                    $('#pdDropdown').html(generateOptions(response.Periods, 'period', 'periodLong', 'Select Period'));
                    setSelectionsFromURL();
                }
            });
        }
    }

    function setSelectionsFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        const pd = urlParams.get('pd');

        if (pd) {
            $('#pdDropdown').val(pd).trigger('change');
            fetchNations(pd);
        }
    }
    
    function fetchNations(pd) {
        const cached = localStorage.getItem(`nations_${pd}`);
        if (cached) {
            populateNationsDropdown(JSON.parse(cached));
        } else {
            $.ajax({
                url: 'api/backend.php',
                type: 'GET',
                data: { action: 'fetchNations', period: pd },
                success: function(response) {
                        localStorage.setItem(`nations_${pd}`, JSON.stringify(response));
                        populateNationsDropdown(response);
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
            populateBooksDropdown(JSON.parse(cached));
        } else {
        $.ajax({
            url: 'api/backend.php',
            type: 'GET',
            data: { action: 'fetchBooks', period: pd, nation: nation },
            success: function(response) {
                    localStorage.setItem(`books_${pd}_${nation}`, JSON.stringify(response));
                    populateBooksDropdown(response);
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching books:', status, error,pd,nation);
                }
            });
        }
    }

    function fetchFormations(bookTitle) {
        $.ajax({
            url: 'api/backend.php',
            type: 'GET',
            data: { action: 'fetchFormations', bookTitle: bookTitle },
            success: function(response) {
                // Populate formations dropdown
                // Example: $('#formationsDropdown').html(generateOptions(response.Formations, 'code', 'title', 'Select Formation'));
            },
            error: function(xhr, status, error) {
                console.error('Error fetching formations:', status, error);
            }
        });
    }

    function populateNationsDropdown(data) {
        const urlParams = new URLSearchParams(window.location.search);
        const nation = urlParams.get('ntn');
        $('#ntnDropdown').html(generateOptions(data.Nations, 'Nation', 'Nation', 'Select Nation')).prop('disabled', false);
            if (nation) {
                $('#ntnDropdown').val(nation).trigger('change');
                fetchBooks($('#pdDropdown').val(), nation);
            }
    }


    function populateBooksDropdown(data) {
        const urlParams = new URLSearchParams(window.location.search);
        const book = urlParams.get('Book');
        $('#BookDropdown').html(generateOptions(data.Books, 'code', 'Book', 'Select Book')).prop('disabled', false);
            if (book) {
                $('#BookDropdown').val(book);
            }
    }



    $('#pdDropdown').change(function() {
        const pd = $(this).val();
        if (pd) {
            fetchNations(pd, {});
            $('#ntnDropdown').prop('disabled', false);
        } else {
            $('#ntnDropdown').prop('disabled', true).html(generateOptions([], '', '', 'Select Nation'));
            $('#BookDropdown').prop('disabled', true).html(generateOptions([], '', '', 'Select Book'));
        }
        updateURLParams();
    });

    $('#ntnDropdown').change(function() {
        const pd = $('#pdDropdown').val();
        const nation = $(this).val();
        if (nation) {
            fetchBooks(pd, nation, {});
            $('#BookDropdown').prop('disabled', false);
        } else {
            $('#BookDropdown').prop('disabled', true).html(generateOptions([], '', '', 'Select Book'));

        }
        updateURLParams();
    });

    $('#BookDropdown').change(function() {
        updateURLParams();
        const bookTitle = $(this).val();
        fetchFormations(bookTitle);
    });

    $('#ntnDropdownClear').on( "click", function() {
        $('#ntnDropdown').val("");
        $('#BookDropdown').prop('disabled', true).val("");
        updateURLParams();
    });

    $('#BookDropdownClear').on( "click", function() {
        $('#BookDropdown').val("");

        updateURLParams();
    });


    $('#formationsDropdown').change(function() {
        const formationCode = $(this).val();
        fetchFormationDetails(formationCode);
    });

    function fetchFormationDetails(formationCode) {
        $.ajax({
            url: 'backend.php',
            type: 'GET',
            data: { action: 'fetchFormationDetails', formationCode: formationCode },
            success: function(response) {
                // Display formation details
                // Example: $('#formationDetails').html(generateFormationDetails(response.FormationDetails));
            },
            error: function(xhr, status, error) {
                console.error('Error fetching formation details:', status, error);
            }
        });
    }

    function generateFormationDetails(details) {
        // Generate HTML for formation details
    }
});