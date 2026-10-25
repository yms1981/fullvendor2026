$(document).ready(function() {
    //COUNTRY
    let countrySelected = false;

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
            url: env.url + '/search',
            method: "GET",
            data: {
                resource: 'country',
                term: term
            },
            success: function(data) {
                let suggestions = JSON.parse(data);
                let box = $('#country_suggestions');
                box.empty();

                suggestions.forEach(function(item) {
                    box.append(`<div data-id="${item.id}" data-name="${item.name}">${item.name}</div>`);
                });

                box.show();
            }
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

    //STATE
    let stateSelected = false;

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

        $.ajax({
            url: env.url + '/search',
            method: "GET",
            data: {
                resource: 'state',
                term: term
            },
            success: function(data) {
                let suggestions = JSON.parse(data);
                let box = $('#state_suggestions');
                box.empty();

                suggestions.forEach(function(item) {
                    box.append(`<div data-id="${item.id}" data-name="${item.name}">${item.name}</div>`);
                });

                box.show();
            }
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

    //CITY
    let citySelected = false;

    $('#city_name').on('input', function() {
        // let term = $(this).val();
        //
        // if (term.trim() === '') {
        //     citySelected = false;
        //     $('#city_id').val(null);
        //     $('#city_suggestions').empty();
        //     return;
        // }
        //
        // if (term.length < 2) {
        //     $('#city_suggestions').empty();
        //     return;
        // }
        //
        // $.ajax({
        //     url: env.url + '/search',
        //     method: "GET",
        //     data: {
        //         resource: 'city',
        //         term: term
        //     },
        //     success: function(data) {
        //         let suggestions = JSON.parse(data);
        //         let box = $('#city_suggestions');
        //         box.empty();
        //
        //         suggestions.forEach(function(item) {
        //             box.append(`<div data-id="${item.id}" data-name="${item.name}">${item.name}</div>`);
        //         });
        //
        //         box.show();
        //     }
        // });
    });

    $('#city_suggestions').on('click', 'div', function(e) {
        e.stopPropagation();

        let name = $(this).data('name');
        let id = $(this).data('id');
        $('#city_name').val(name);
        $('#city_id').val(id);
        $('#city_suggestions').empty();
        citySelected = true;
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
