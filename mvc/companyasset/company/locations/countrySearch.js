$(document).ready(function() {
    //COUNTRY
    let countrySelected = false;

    if ($('#country_id').val()) {
        countrySelected = true;
    }

    function parseLocationResponse(raw) {
        if (raw === null || raw === undefined || raw === '') {
            return [];
        }
        if (Array.isArray(raw)) {
            return raw;
        }
        if (typeof raw === 'object') {
            if (Array.isArray(raw.data)) {
                return raw.data;
            }
            return [];
        }
        if (typeof raw === 'string') {
            try {
                var parsed = JSON.parse(raw);
                if (Array.isArray(parsed)) {
                    return parsed;
                }
                if (parsed && Array.isArray(parsed.data)) {
                    return parsed.data;
                }
            } catch (e) {
                return [];
            }
        }
        return [];
    }

    $('#country_name').on('input', function() {
        let term = $(this).val();

        if (term.trim() === '') {
            countrySelected = false;
            $('#country_id').val(null);
            $('#country_suggestions').empty();
            return;
        }

        if (term.length < 2) {
            $('#country_suggestions').empty();
            return;
        }

        $.ajax({
            url: options.url + '/countries/searchCountry',
            method: 'GET',
            data: {
                term: term
            },
            dataType: 'text'
        }).done(function(raw) {
            let suggestions = parseLocationResponse(raw);
            let box = $('#country_suggestions');
            box.empty();

            suggestions.forEach(function(item) {
                item = item || {};
                box.append(`<div data-id="${item.id}" data-name="${item.name}">${item.name}</div>`);
            });

            if (suggestions.length) {
                box.show();
            }
        }).fail(function() {
            $('#country_suggestions').empty();
        });
    });

    $('#country_suggestions').on('click', 'div', function(e) {
        e.stopPropagation();

        let name = $(this).data('name');
        let id = $(this).data('id');
        $('#country_name').val(name);
        $('#country_id').val(id);
        $('#country_suggestions').empty();
        countrySelected = true;
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#country_name, #country_suggestions').length) {
            if (!countrySelected) {
                let inputName = $('#country_name');
                inputName.val(null);
                inputName.removeClass('is-valid');
                $('#country_id').val(null);
            }

            $('#country_suggestions').empty();
        }
    });
});
