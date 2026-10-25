$(function () {
    // No inicializar #seller_asigned aquí: en customer/add y customer/edit se hace en la vista (multiselect).
    // Si se llama select2() aquí después, se pisa la config múltiple en la pantalla de edición.
    function setupLocationAutocomplete(inputNameId, hiddenId, suggestionsId, url) {
        let locationSelected = !!$(`#${hiddenId}`).val();

        const obj = {
            locationSelected,
            setSelected(value) {
                this.locationSelected = value;
            }
        };

        $(`#${inputNameId}`).on('input', function() {
            let term = $(this).val();

            if (term.trim() === '') {
                obj.locationSelected = false;
                $(`#${hiddenId}`).val(null);
                $(`#${suggestionsId}`).empty();
                return;
            }

            if (term.length < 2) {
                $(`#${suggestionsId}`).empty();
                return;
            }

            $.ajax({
                url: options.url + url,
                method: "GET",
                data: { term: term },
                success: function(data) {
                    let suggestions = JSON.parse(data);
                    let box = $(`#${suggestionsId}`);
                    box.empty();

                    suggestions.forEach(function(item) {
                        box.append(`<div data-id="${item.id}" data-name="${item.name}">${item.name}</div>`);
                    });

                    box.show();
                }
            });
        });

        $(`#${suggestionsId}`).on('click', 'div', function(e) {
            e.stopPropagation();
            let name = $(this).data('name');
            let id = $(this).data('id');
            $(`#${inputNameId}`).val(name);
            $(`#${hiddenId}`).val(id);
            $(`#${suggestionsId}`).empty();
            obj.locationSelected = true;
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest(`#${inputNameId}, #${suggestionsId}`).length) {
                if (!obj.locationSelected) {
                    $(`#${inputNameId}`).val(null).removeClass('is-valid');
                    $(`#${hiddenId}`).val(null);
                }
                $(`#${suggestionsId}`).empty();
            }
        });

        return obj;
    }

    const commercialCountry = setupLocationAutocomplete('commercial_country_name', 'commercial_country_id','commercial_country_suggestions', '/countries/searchCountry');
    const commercialState   = setupLocationAutocomplete('commercial_state_name', 'commercial_state_id','commercial_state_suggestions', '/states/searchState');
    const commercialCity    = setupLocationAutocomplete('commercial_city_name', 'commercial_city_id', 'commercial_city_suggestions', '/cities/searchCity');

    const dispatchCountry = setupLocationAutocomplete('dispatch_country_name', 'dispatch_country_id', 'dispatch_country_suggestions', '/countries/searchCountry');
    const dispatchState   = setupLocationAutocomplete('dispatch_state_name', 'dispatch_state_id', 'dispatch_state_suggestions', '/states/searchState');
    const dispatchCity    = setupLocationAutocomplete('dispatch_city_name', 'dispatch_city_id', 'dispatch_city_suggestions', '/cities/searchCity');

    $('#asignar_datos').on('change', function() {
        if ($(this).is(':checked')) {
            $('#dispatch_country_id').val($('#commercial_country_id').val());
            $('#dispatch_state_id').val($('#commercial_state_id').val());
            $('#dispatch_city_id').val($('#commercial_city_id').val());

            $('#dispatch_country_name').val($('#commercial_country_name').val()).prop('disabled', true);
            $('#dispatch_state_name').val($('#commercial_state_name').val()).prop('disabled', true);
            $('#dispatch_city_name').val($('#commercial_city_name').val()).prop('disabled', true);

            if (typeof dispatchCountry !== 'undefined' && dispatchCountry && typeof dispatchCountry.setSelected === 'function') {
                dispatchCountry.setSelected(true);
            }
            if (typeof dispatchState !== 'undefined' && dispatchState && typeof dispatchState.setSelected === 'function') {
                dispatchState.setSelected(true);
            }
            if (typeof dispatchCity !== 'undefined' && dispatchCity && typeof dispatchCity.setSelected === 'function') {
                dispatchCity.setSelected(true);
            }
        } else {
            $('#dispatch_country_id').val('');
            $('#dispatch_state_id').val('');
            $('#dispatch_city_id').val('');

            $('#dispatch_country_name').val('').prop('disabled', false);
            $('#dispatch_state_name').val('').prop('disabled', false);
            $('#dispatch_city_name').val('').prop('disabled', false);

            if (typeof dispatchCountry !== 'undefined' && dispatchCountry && typeof dispatchCountry.setSelected === 'function') {
                dispatchCountry.setSelected(false);
            }
            if (typeof dispatchState !== 'undefined' && dispatchState && typeof dispatchState.setSelected === 'function') {
                dispatchState.setSelected(false);
            }
            if (typeof dispatchCity !== 'undefined' && dispatchCity && typeof dispatchCity.setSelected === 'function') {
                dispatchCity.setSelected(false);
            }
        }
    });
});
