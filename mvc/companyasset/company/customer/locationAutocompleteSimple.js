/**
 * Autocompletado simple para Estado/Ciudad con un único <input>.
 * El input visible (name=commercial_state/commercial_city/dispatch_*) envía el texto que ve el usuario.
 * Junto a él, un hidden (commercial_state_id, etc.) lleva el ID seleccionado (si el usuario eligió de la lista).
 * Si el usuario escribe texto libre sin seleccionar, el hidden se vacía y el backend intenta resolver por nombre.
 *
 * Atributos data-* en el input:
 *   data-fv-location:    "state" | "city"
 *   data-fv-hidden:      selector del input hidden donde guardar el id (ej. #commercial_state_id)
 *   data-fv-suggestions: selector del div donde renderizar las sugerencias
 *   data-fv-country:     selector del input/select que tiene country_id (solo para state)
 *   data-fv-state:       selector del hidden state_id (solo para city)
 */
$(function () {
    if (!window.options || !window.options.url) {
        return;
    }

    var DEBOUNCE_MS = 200;
    var debounceTimers = {};

    function $field(input) {
        return $(input);
    }

    function clearSuggestions($suggestions) {
        $suggestions.empty().hide();
    }

    function renderSuggestions($suggestions, items, $input, $hidden) {
        if (!items || items.length === 0) {
            clearSuggestions($suggestions);
            return;
        }
        var html = '';
        items.forEach(function (it) {
            var name = (it.name || '').toString();
            var id = (it.id || '').toString();
            html += '<div class="fv-location-suggestion" data-id="' + $('<div>').text(id).html() + '" data-name="' + $('<div>').text(name).html() + '">' + $('<div>').text(name).html() + '</div>';
        });
        $suggestions.html(html).show();

        $suggestions.find('.fv-location-suggestion').off('mousedown.fvac').on('mousedown.fvac', function (e) {
            e.preventDefault();
            var id = $(this).attr('data-id');
            var name = $(this).attr('data-name');
            $input.val((name || '').toUpperCase());
            $hidden.val(id || '');
            clearSuggestions($suggestions);
            $input.trigger('change');
            // Si es un estado y cambia, reseteamos su ciudad asociada
            if ($input.is('#commercial_state')) {
                $('#commercial_city').val('');
                $('#commercial_city_id').val('');
            } else if ($input.is('#dispatch_state')) {
                $('#dispatch_city').val('');
                $('#dispatch_city_id').val('');
            }
        });
    }

    function getCountryIdFromInput($input) {
        var sel = $input.attr('data-fv-country');
        if (!sel) {
            return null;
        }
        var $c = $(sel);
        if (!$c.length) {
            return null;
        }
        var v = $c.val();
        v = (v === null || v === undefined) ? '' : String(v).trim();
        if (v === '') {
            return null;
        }
        return /^\d+$/.test(v) ? parseInt(v, 10) : null;
    }

    function getStateIdFromInput($input) {
        var sel = $input.attr('data-fv-state');
        if (!sel) {
            return null;
        }
        var $s = $(sel);
        if (!$s.length) {
            return null;
        }
        var v = $s.val();
        v = (v === null || v === undefined) ? '' : String(v).trim();
        if (v === '') {
            return null;
        }
        return /^\d+$/.test(v) ? parseInt(v, 10) : null;
    }

    function fetchSuggestions($input, $suggestions, $hidden, type, term) {
        var data = { term: term };
        var endpoint;
        if (type === 'state') {
            var cid = getCountryIdFromInput($input);
            if (!cid) {
                clearSuggestions($suggestions);
                return;
            }
            data.country_id = cid;
            endpoint = options.url + '/states/searchstate';
        } else {
            var sid = getStateIdFromInput($input);
            if (!sid) {
                clearSuggestions($suggestions);
                return;
            }
            data.state_id = sid;
            endpoint = options.url + '/cities/searchcity';
        }

        $.ajax({
            url: endpoint,
            method: 'GET',
            data: data,
            dataType: 'text'
        }).done(function (raw) {
            var items;
            try {
                items = (typeof raw === 'string') ? JSON.parse(raw) : raw;
            } catch (e) {
                items = [];
            }
            if (!Array.isArray(items)) {
                items = [];
            }
            renderSuggestions($suggestions, items, $input, $hidden);
        }).fail(function () {
            clearSuggestions($suggestions);
        });
    }

    function bindInput($input) {
        var type = ($input.attr('data-fv-location') || '').toLowerCase();
        if (type !== 'state' && type !== 'city') {
            return;
        }
        var $hidden = $($input.attr('data-fv-hidden') || '');
        var $suggestions = $($input.attr('data-fv-suggestions') || '');
        if (!$hidden.length || !$suggestions.length) {
            return;
        }

        $input.on('input.fvac', function () {
            var term = $.trim($(this).val() || '');
            // Cualquier edición desvincula el ID elegido previamente.
            $hidden.val('');
            if (term.length < 1) {
                clearSuggestions($suggestions);
                return;
            }
            var key = $input.attr('id') || term;
            if (debounceTimers[key]) {
                clearTimeout(debounceTimers[key]);
            }
            debounceTimers[key] = setTimeout(function () {
                fetchSuggestions($input, $suggestions, $hidden, type, term);
            }, DEBOUNCE_MS);
        });

        $input.on('focus.fvac', function () {
            var term = $.trim($(this).val() || '');
            if (term.length >= 1) {
                fetchSuggestions($input, $suggestions, $hidden, type, term);
            }
        });

        $input.on('blur.fvac', function () {
            // Pequeño delay para que el mousedown del suggestion se procese antes.
            setTimeout(function () { clearSuggestions($suggestions); }, 150);
        });
    }

    $('.fv-location-input').each(function () {
        bindInput($(this));
    });

    // Si cambia el país comercial/entrega, reseteamos el estado/ciudad asociados.
    $('#commercial_country_id').on('change.fvac', function () {
        $('#commercial_state').val('');
        $('#commercial_state_id').val('');
        $('#commercial_city').val('');
        $('#commercial_city_id').val('');
    });
    $('#dispatch_country_id').on('change.fvac', function () {
        $('#dispatch_state').val('');
        $('#dispatch_state_id').val('');
        $('#dispatch_city').val('');
        $('#dispatch_city_id').val('');
    });
});
