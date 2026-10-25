$(document).ready(function() {
    //CITY
    let citySelected = false;

    if ($('#city_id').val()) {
        citySelected = true;
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

    function getSelectedStateId() {
        var v = String($('#state_id').val() || '').trim();
        if (v === '' || !/^\d+$/.test(v)) {
            return null;
        }
        return parseInt(v, 10);
    }

    function escapeHtml(text) {
        return String(text || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    $('#city_name').on('input', function() {
        let term = $(this).val();

        if (term.trim() === '') {
            citySelected = false;
            $('#city_id').val(null);
            $('#city_suggestions').empty();
            return;
        }

        if (term.length < 2) {
            $('#city_suggestions').empty();
            return;
        }

        var stateId = getSelectedStateId();
        var countryId = getSelectedCountryId();
        if (!stateId && !countryId) {
            $('#city_suggestions').empty();
            return;
        }

        var data = { term: term };
        if (stateId) {
            data.state_id = stateId;
        } else {
            data.country_id = countryId;
        }

        $.ajax({
            url: options.url + '/cities/searchCity',
            method: 'GET',
            data: data,
            dataType: 'text'
        }).done(function(raw) {
            let suggestions = parseLocationResponse(raw);
            let box = $('#city_suggestions');
            box.empty();

            suggestions.forEach(function(item) {
                item = item || {};
                var label = item.name || '';
                if (item.state_name && !stateId) {
                    label = label + ' (' + item.state_name + ')';
                }
                box.append(
                    '<div data-id="' + escapeHtml(item.id) + '"'
                    + ' data-name="' + escapeHtml(item.name) + '"'
                    + ' data-state-id="' + escapeHtml(item.state_id) + '"'
                    + ' data-state-name="' + escapeHtml(item.state_name) + '">'
                    + escapeHtml(label)
                    + '</div>'
                );
            });

            if (suggestions.length) {
                box.show();
            }
        }).fail(function() {
            $('#city_suggestions').empty();
        });
    });

    $('#city_suggestions').on('click', 'div', function(e) {
        e.stopPropagation();

        let name = $(this).attr('data-name');
        let id = $(this).attr('data-id');
        let stateId = $(this).attr('data-state-id');
        let stateName = $(this).attr('data-state-name');

        $('#city_name').val(name);
        $('#city_id').val(id);
        $('#city_suggestions').empty();
        citySelected = true;

        if (stateId && String(stateId).match(/^\d+$/)) {
            $('#state_id').val(stateId);
            if (stateName) {
                $('#state_name').val(stateName);
            }
        }
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#city_name, #city_suggestions').length) {
            if (!citySelected) {
                let inputName = $('#city_name');
                inputName.val(null);
                inputName.removeClass('is-valid');
                $('#city_id').val(null);
            }

            $('#city_suggestions').empty();
        }
    });
});
