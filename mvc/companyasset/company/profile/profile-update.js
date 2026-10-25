$(document).ready(function () {

    $(".js-example-matcher-start").select2({
        matcher: matchStart,
        width: '100%'
    });

    function syncFormatTileSelection(input) {
        if (!input || !input.name) return;
        var name = input.name;
        document.querySelectorAll('input.fv-fmt-tile__input[name="' + name + '"]').forEach(function (el) {
            var label = el.closest('.fv-fmt-tile');
            if (label) {
                label.classList.toggle('is-selected', el.checked);
            }
        });
    }

    $(document)
        .on('change', 'input.fv-fmt-tile__input', function () {
            syncFormatTileSelection(this);
        })
        .on('change', 'input[name=show_semaforo_stock]', function () {
            if ($(this).val() == 1) {
                $('section#stock_traffic_light').show();
            } else {
                $('section#stock_traffic_light').hide();
            }
        });

    document.querySelectorAll('input.fv-fmt-tile__input:checked').forEach(function (el) {
        syncFormatTileSelection(el);
    });
});
