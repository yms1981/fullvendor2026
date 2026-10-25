/**
 * FullVendor — Modales unificados:
 * - Cabecera con icono
 * - No cierre por clic fuera / Escape
 * - Iconos en casillas de formulario
 */
(function (window, $) {
    'use strict';

    if (!$) {
        return;
    }

    var HEADER_ICON_RULES = [
        { re: /\bbuscar\b|\bsearch\b/i, icon: 'mdi-account-multiple' },
        { re: /add|nuevo|nueva|create|agregar/i, icon: 'mdi-plus-circle-outline' },
        { re: /edit|editar|update|modificar/i, icon: 'mdi-pencil-outline' },
        { re: /delete|eliminar|remove|borrar/i, icon: 'mdi-delete-outline' },
        { re: /export|exportar/i, icon: 'mdi-export' },
        { re: /import|importar/i, icon: 'mdi-import' },
        { re: /image|imagen|foto|photo|avatar/i, icon: 'mdi-image-outline' },
        { re: /user|usuario|seller|vendedor|gerente|manager/i, icon: 'mdi-account-outline' },
        { re: /customer|cliente/i, icon: 'mdi-account-tie-outline' },
        { re: /product|producto/i, icon: 'mdi-package-variant-closed' },
        { re: /categor/i, icon: 'mdi-tag-outline' },
        { re: /catalog|catálogo|catalogo/i, icon: 'mdi-book-open-page-variant-outline' },
        { re: /order|pedido|orden/i, icon: 'mdi-clipboard-text-outline' },
        { re: /warehouse|almacén|almacen/i, icon: 'mdi-warehouse' },
        { re: /email|correo|mail/i, icon: 'mdi-email-outline' },
        { re: /send|enviar/i, icon: 'mdi-send-outline' },
        { re: /preview|vista|ver/i, icon: 'mdi-eye-outline' },
        { re: /download|descargar/i, icon: 'mdi-download-outline' },
        { re: /assign|asignar/i, icon: 'mdi-account-switch-outline' },
        { re: /status|estado/i, icon: 'mdi-toggle-switch-outline' },
        { re: /history|historial/i, icon: 'mdi-history' },
        { re: /print|imprimir/i, icon: 'mdi-printer-outline' },
        { re: /group|grupo/i, icon: 'mdi-account-group-outline' },
        { re: /term|plazo|pago/i, icon: 'mdi-file-document-outline' },
        { re: /unit|unidad/i, icon: 'mdi-ruler-square' },
        { re: /currency|moneda/i, icon: 'mdi-cash' },
        { re: /profile|perfil|rol/i, icon: 'mdi-shield-account-outline' }
    ];

    var FIELD_ICON_RULES = [
        { re: /password|pass|contrase|clave/i, icon: 'mdi-lock-outline' },
        { re: /email|correo|e-mail|mail/i, icon: 'mdi-email-outline' },
        { re: /first[_\s-]?name|nombre[_\s-]?pila|nombres/i, icon: 'mdi-account-circle-outline' },
        { re: /last[_\s-]?name|apellido/i, icon: 'mdi-account-circle-outline' },
        { re: /\bname\b|nombre|username|usuario|login/i, icon: 'mdi-account-outline' },
        { re: /role|rol|tipo|type/i, icon: 'mdi-badge-account-outline' },
        { re: /profile|perfil/i, icon: 'mdi-card-account-details-outline' },
        { re: /phone|telefono|tel[eé]fono|mobile|celular|cell|fax/i, icon: 'mdi-phone-outline' },
        { re: /address|direccion|direcci[oó]n|street|calle/i, icon: 'mdi-map-marker-outline' },
        { re: /city|ciudad/i, icon: 'mdi-city-variant-outline' },
        { re: /state|estado|province|provincia/i, icon: 'mdi-map-outline' },
        { re: /zip|postal|c[oó]digo[_\s-]?postal|cp\b/i, icon: 'mdi-mailbox-outline' },
        { re: /country|pa[ií]s/i, icon: 'mdi-earth' },
        { re: /warehouse|almac[eé]n/i, icon: 'mdi-warehouse' },
        { re: /customer|cliente/i, icon: 'mdi-account-tie-outline' },
        { re: /seller|vendedor/i, icon: 'mdi-account-tie' },
        { re: /sku|barcode|c[oó]digo|code|ean/i, icon: 'mdi-barcode' },
        { re: /price|precio|cost|costo|amount|monto|sale|compra|fob/i, icon: 'mdi-currency-usd' },
        { re: /stock|cantidad|qty|quantity|moq|m[ií]nima/i, icon: 'mdi-counter' },
        { re: /tag|etiqueta/i, icon: 'mdi-tag-multiple-outline' },
        { re: /categor/i, icon: 'mdi-tag-outline' },
        { re: /catalog|cat[aá]logo/i, icon: 'mdi-book-open-page-variant-outline' },
        { re: /product|producto/i, icon: 'mdi-package-variant-closed' },
        { re: /unit|unidad/i, icon: 'mdi-ruler-square' },
        { re: /location|ubicaci[oó]n|warehouse_loc/i, icon: 'mdi-map-marker-radius-outline' },
        { re: /status|estado|active|activo/i, icon: 'mdi-toggle-switch-outline' },
        { re: /descri|detalle|detail|note|nota|comment|observa/i, icon: 'mdi-text-box-outline' },
        { re: /date|fecha|created|desde|hasta/i, icon: 'mdi-calendar-outline' },
        { re: /search|buscar|filtro|filter/i, icon: 'mdi-magnify' },
        { re: /group|grupo/i, icon: 'mdi-account-group-outline' },
        { re: /term|plazo|pago|payment/i, icon: 'mdi-file-document-outline' },
        { re: /currency|moneda|symbol/i, icon: 'mdi-cash' },
        { re: /url|link|web|website|sitio/i, icon: 'mdi-link-variant' },
        { re: /image|imagen|foto|photo|file|archivo/i, icon: 'mdi-image-outline' },
        { re: /subject|asunto|title|t[ií]tulo/i, icon: 'mdi-format-title' },
        { re: /message|mensaje|body|contenido/i, icon: 'mdi-message-text-outline' },
        { re: /company|empresa|business|negocio/i, icon: 'mdi-domain' },
        { re: /order|pedido|orden/i, icon: 'mdi-clipboard-text-outline' }
    ];

    function matchIcon(rules, haystack, fallback) {
        for (var i = 0; i < rules.length; i++) {
            if (rules[i].re.test(haystack)) {
                return rules[i].icon;
            }
        }
        return fallback;
    }

    function resolveHeaderIcon($modal, $title) {
        var custom = $modal.attr('data-fv-modal-icon');
        if (custom && String(custom).trim() !== '') {
            return String(custom).replace(/^mdi\s+/, '').trim();
        }

        var haystack = [
            $modal.attr('id') || '',
            $modal.attr('class') || '',
            $title.text() || ''
        ].join(' ');

        return matchIcon(HEADER_ICON_RULES, haystack, 'mdi-window-maximize');
    }

    function normalizeMdi(icon) {
        icon = String(icon || '').replace(/^mdi\s+/, '').trim();
        if (icon.indexOf('mdi-') !== 0) {
            icon = 'mdi-' + icon.replace(/^mdi-?/, '');
        }
        return /^mdi-[a-z0-9-]+$/i.test(icon) ? icon : 'mdi-window-maximize';
    }

    function titlePlainText($title) {
        var $clone = $title.clone();
        $clone.find('.modal-header__avatar').remove();
        return $.trim($clone.text().replace(/\s+/g, ' '));
    }

    function applyAccentTitle($title, text) {
        if ($title.find('.modal-header__accent').length) {
            return;
        }
        var parts = String(text || '').trim().split(/\s+/).filter(function (part) {
            return part !== '';
        });
        if (!parts.length) {
            return;
        }
        var last = parts.pop();
        $title.empty();
        if (parts.length) {
            $title.append(document.createTextNode(parts.join(' ') + ' '));
        }
        $title.append($('<span class="modal-header__accent"></span>').text(last));
    }

    function ensureHeaderAvatar($modal) {
        var $header = $modal.find('> .modal-dialog > .modal-content > .modal-header').first();
        if (!$header.length) {
            $header = $modal.find('.modal-header').first();
        }
        if (!$header.length) {
            return;
        }
        if ($header.attr('data-fv-header-ready') === '1') {
            var $readyTitle = $header.find('.modal-title').first();
            if ($readyTitle.length && !$readyTitle.find('.modal-header__accent').length) {
                var again = titlePlainText($readyTitle);
                if (again) {
                    $readyTitle.find('.modal-header__avatar').remove();
                    applyAccentTitle($readyTitle, again);
                }
            }
            return;
        }

        var $title = $header.find('.modal-title').first();
        if (!$title.length) {
            return;
        }

        var plain = titlePlainText($title);
        if (!plain) {
            return;
        }

        var $existingIcon = $header.find('.modal-header__avatar i').first();
        var iconName = '';
        if ($existingIcon.length) {
            iconName = (($existingIcon.attr('class') || '').match(/mdi-[a-z0-9-]+/i) || [])[0] || '';
        }
        if (!iconName) {
            iconName = normalizeMdi(resolveHeaderIcon($modal, $title));
        }

        var $subtitle = $header.find('.modal-subtitle').first();
        if ($subtitle.length && $.trim($subtitle.text()) === '') {
            $subtitle.remove();
            $subtitle = $();
        }

        var $lead = $header.children('.modal-header__lead').first();
        if (!$lead.length) {
            $lead = $('<div class="modal-header__lead"></div>');
            $header.prepend($lead);
        }

        var $avatar = $lead.children('.modal-header__avatar').first();
        if (!$avatar.length) {
            $avatar = $('<span class="modal-header__avatar" aria-hidden="true"></span>');
            $lead.prepend($avatar);
        }
        if (!$avatar.find('i').length) {
            $avatar.append($('<i class="mdi" aria-hidden="true"></i>').addClass(iconName));
        }

        var $copy = $lead.children('.modal-header__copy').first();
        if (!$copy.length) {
            $copy = $('<div class="modal-header__copy"></div>');
            $lead.append($copy);
        }

        $title.find('.modal-header__avatar').remove();
        if (!$title.parent().is($copy)) {
            $copy.append($title);
        }
        if ($subtitle.length && !$subtitle.parent().is($copy)) {
            $copy.append($subtitle);
        }
        applyAccentTitle($title, plain);
        $header.find('.modal-header__avatar').not($avatar).remove();

        $header.children('div').not('.modal-header__lead').each(function () {
            var $wrap = $(this);
            if ($wrap.find('button, a, input, select, textarea, .close, .modal-title').length) {
                return;
            }
            if ($.trim($wrap.text()) === '') {
                $wrap.remove();
            }
        });

        $header.attr('data-fv-header-ready', '1');
    }

    function ensureCloseButton($modal) {
        var $header = $modal.find('> .modal-dialog > .modal-content > .modal-header').first();
        if (!$header.length) {
            $header = $modal.find('.modal-header').first();
        }
        if (!$header.length) {
            return;
        }

        var $close = $header.children('button.close, .close[data-dismiss="modal"], .fv-modal-close-x').first();
        if ($close.length) {
            $close.addClass('fv-modal-close-x');
            if (!$close.find('.mdi, .fa, .fas, span').length) {
                $close.append('<i class="mdi mdi-close" aria-hidden="true"></i>');
            }
            return;
        }

        var label = '';
        if (typeof window.fvUiText === 'function') {
            label = window.fvUiText('close') || window.fvUiText('cerrar') || window.fvUiText('cancel') || '';
        }
        $header.append(
            '<button type="button" class="close fv-modal-close-x" data-dismiss="modal" aria-label="' +
            $('<div>').text(label || '×').html() +
            '"><i class="mdi mdi-close" aria-hidden="true"></i></button>'
        );
    }

    function fieldHaystack($el) {
        var id = $el.attr('id') || '';
        var name = $el.attr('name') || '';
        var placeholder = $el.attr('placeholder') || '';
        var aria = $el.attr('aria-label') || '';
        var labelText = '';

        if (id) {
            var $label = $el.closest('.form-group, .mb-2, .mb-3, [class*="col-"]').find('label[for="' + id + '"]').first();
            if (!$label.length) {
                $label = $('label[for="' + id + '"]').first();
            }
            if ($label.length) {
                labelText = $label.text() || '';
            }
        }
        if (!labelText) {
            var $prevLabel = $el.closest('.form-group, .mb-2, .mb-3').children('label').first();
            if ($prevLabel.length) {
                labelText = $prevLabel.text() || '';
            }
        }

        return [id, name, placeholder, aria, labelText].join(' ');
    }

    function resolveFieldIcon($el) {
        var custom = $el.attr('data-fv-field-icon');
        if (custom && String(custom).trim() !== '') {
            var c = String(custom).trim();
            return c.indexOf('mdi-') === 0 ? c : ('mdi-' + c.replace(/^mdi-?/, ''));
        }
        return matchIcon(FIELD_ICON_RULES, fieldHaystack($el), 'mdi-form-textbox');
    }

    function shouldSkipField($el) {
        if (!$el || !$el.length) {
            return true;
        }
        if ($el.closest('.modal-input-icon').length) {
            return true;
        }
        if ($el.attr('data-fv-no-icon') === '1') {
            return true;
        }
        if ($el.closest('.fv-cat-groups-create').length) {
            return true;
        }

        var type = String($el.attr('type') || '').toLowerCase();
        if (['hidden', 'checkbox', 'radio', 'file', 'submit', 'button', 'reset', 'image', 'color', 'range'].indexOf(type) !== -1) {
            return true;
        }

        // Teléfonos / grupos con prefijo: ya tienen UI propia
        if ($el.closest('.input-group').length && $el.closest('.input-group').find('.input-group-text, .input-group-prepend, .input-group-append').length) {
            return true;
        }

        // Select2 / bootstrap-select ocultan el control original
        if ($el.hasClass('select2-hidden-accessible') || $el.data('select2')) {
            return true;
        }
        if ($el.parent().hasClass('bootstrap-select') || $el.closest('.bootstrap-select').length) {
            return true;
        }

        // Summernote / editores ricos
        if ($el.closest('.note-editor, .tox, .ql-container').length) {
            return true;
        }

        // Controles de búsqueda de DataTables / filtros internos
        if ($el.closest('.dataTables_filter, .dataTables_length').length) {
            return true;
        }

        return false;
    }

    function wrapFieldWithIcon($el) {
        if (shouldSkipField($el)) {
            return;
        }

        var icon = resolveFieldIcon($el);
        var $wrap = $('<div class="modal-input-icon fv-modal-auto-icon"></div>');
        $el.before($wrap);
        $wrap.append('<i class="mdi ' + icon + '" aria-hidden="true"></i>');
        $wrap.append($el);
    }

    function ensureFieldIcons($modal) {
        if (!$modal || !$modal.length) {
            return;
        }
        if ($modal.attr('data-fv-no-field-icons') === '1') {
            return;
        }

        var $body = $modal.find('.modal-body').first();
        if (!$body.length) {
            $body = $modal;
        }

        $body.find('input.form-control, select.form-control, textarea.form-control, input[type="text"], input[type="email"], input[type="password"], input[type="number"], input[type="tel"], input[type="url"], input[type="search"], select, textarea')
            .each(function () {
                var $el = $(this);
                if (!$el.hasClass('form-control') && $el.is('input, select, textarea')) {
                    // Algunos modales usan inputs sin form-control
                    if ($el.closest('.modal-footer').length) {
                        return;
                    }
                }
                if ($el.closest('.modal-footer').length) {
                    return;
                }
                wrapFieldWithIcon($el);
            });
    }

    function enhanceModal($modal) {
        lockModalDismiss($modal);
        ensureHeaderAvatar($modal);
        ensureCloseButton($modal);
        ensureFieldIcons($modal);
    }

    function lockModalDismiss($modal) {
        if (!$modal || !$modal.length) {
            return;
        }
        if ($modal.attr('data-fv-allow-backdrop-close') === '1') {
            return;
        }

        $modal.attr('data-backdrop', 'static');
        $modal.attr('data-keyboard', 'false');

        var instance = $modal.data('bs.modal');
        if (instance && instance._config) {
            instance._config.backdrop = 'static';
            instance._config.keyboard = false;
        }
    }

    function applyBootstrapDefaults() {
        if (!$.fn.modal || !$.fn.modal.Constructor || !$.fn.modal.Constructor.Default) {
            return false;
        }
        $.fn.modal.Constructor.Default.backdrop = 'static';
        $.fn.modal.Constructor.Default.keyboard = false;
        return true;
    }

    function lockAllModals() {
        $('.modal').each(function () {
            lockModalDismiss($(this));
        });
    }

    function boot() {
        applyBootstrapDefaults();
        lockAllModals();

        $(document).on('show.bs.modal', '.modal', function () {
            enhanceModal($(this));
        });

        // Contenido AJAX inyectado tras abrir (p. ej. editar usuario)
        $(document).on('shown.bs.modal', '.modal', function () {
            var $modal = $(this);
            window.setTimeout(function () {
                ensureHeaderAvatar($modal);
                ensureFieldIcons($modal);
            }, 50);
        });

        // Observar cuerpos que se rellenan por AJAX
        if (window.MutationObserver) {
            $(document).on('shown.bs.modal', '.modal', function () {
                var $modal = $(this);
                var bodyEl = $modal.find('.modal-body')[0];
                if (!bodyEl || bodyEl._fvIconObserver) {
                    return;
                }
                var obs = new MutationObserver(function () {
                    ensureFieldIcons($modal);
                });
                obs.observe(bodyEl, { childList: true, subtree: true });
                bodyEl._fvIconObserver = obs;
                $modal.one('hidden.bs.modal', function () {
                    try {
                        obs.disconnect();
                    } catch (e) { /* ignore */ }
                    bodyEl._fvIconObserver = null;
                });
            });
        }

        $('.modal.show').each(function () {
            enhanceModal($(this));
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    var tries = 0;
    var timer = window.setInterval(function () {
        tries += 1;
        if (applyBootstrapDefaults()) {
            lockAllModals();
            window.clearInterval(timer);
        } else if (tries > 40) {
            window.clearInterval(timer);
        }
    }, 150);

    window.FVCompanyModals = {
        ensureHeaderAvatar: ensureHeaderAvatar,
        ensureFieldIcons: ensureFieldIcons,
        lockModalDismiss: lockModalDismiss,
        enhanceModal: enhanceModal
    };
})(window, window.jQuery);
