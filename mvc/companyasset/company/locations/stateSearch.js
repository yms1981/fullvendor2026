$(document).ready(function() {
    //STATE
    let stateSelected = false;

    if ($('#state_id').val()) {
        stateSelected = true;
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

    function getSelectedCountryId() {
        var $country = $('#country_id');
        if (!$country.length) {
            return null;
        }
        var v = String($country.val() || '').trim();
        if (v === '' || !/^\d+$/.test(v)) {
            return null;
        }
        return parseInt(v, 10);
    }

    $('#state_name').on('input', function() {
        let term = $(this).val();

        if (term.trim() === '') {
            stateSelected = false;
            $('#state_id').val(null);
            $('#state_suggestions').empty();
            return;
        }

        if (term.length < 2) {
            $('#state_suggestions').empty();
            return;
        }

        var countryId = getSelectedCountryId();
        if (!countryId) {
            $('#state_suggestions').empty();
            return;
        }

        $.ajax({
            url: options.url + '/states/searchState',
            method: 'GET',
            data: {
                term: term,
                country_id: countryId
            },
            dataType: 'text'
        }).done(function(raw) {
            let suggestions = parseLocationResponse(raw);
            let box = $('#state_suggestions');
            box.empty();

            suggestions.forEach(function(item) {
                item = item || {};
                box.append(`<div data-id="${item.id}" data-name="${item.name}">${item.name}</div>`);
            });

            if (suggestions.length) {
                box.show();
            }
        }).fail(function() {
            $('#state_suggestions').empty();
        });
    });

    $('#state_suggestions').on('click', 'div', function(e) {
        e.stopPropagation();

        let name = $(this).data('name');
        let id = $(this).data('id');
        $('#state_name').val(name);
        $('#state_id').val(id);
        $('#state_suggestions').empty();
        stateSelected = true;
        $('#city_name').val('');
        $('#city_id').val('');
        $('#city_suggestions').empty();
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#state_name, #state_suggestions').length) {
            if (!stateSelected) {
                let inputName = $('#state_name');
                inputName.val(null);
                inputName.removeClass('is-valid');
                $('#state_id').val(null);
            }

            $('#state_suggestions').empty();
        }
    });
});
