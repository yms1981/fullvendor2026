/**
 * Cascada País → Estado → Ciudad → ZIP (Select2).
 * ZIP se lista según el ESTADO (no solo la ciudad) con búsqueda AJAX.
 * Espera selects: {prefix}_state_id, {prefix}_city_id, {prefix}_zip_code
 * y hiddens de nombre: {prefix}_state, {prefix}_city
 */
(function ($) {
    'use strict';

    if (!window.options || !window.options.url) {
        return;
    }

    var labelSelect = (window.pageTexts && (window.pageTexts.label_select || window.pageTexts.placeholder_select_country)) || '';

    function fetchJson(url, data) {
        return $.ajax({
            url: url,
            method: 'GET',
            data: data || {},
            dataType: 'text'
        }).then(function (raw) {
            try {
                var items = (typeof raw === 'string') ? JSON.parse(raw) : raw;
                return Array.isArray(items) ? items : [];
            } catch (e) {
                return [];
            }
        }, function () {
            return [];
        });
    }

    function destroySelect2($el) {
        if (!$el.length || typeof $.fn.select2 === 'undefined') {
            return;
        }
        try {
            if ($el.hasClass('select2-hidden-accessible')) {
                $el.select2('destroy');
            }
        } catch (e) { /* ignore */ }
    }

    function initSelect2($el, tags) {
        if (!$el.length || typeof $.fn.select2 === 'undefined') {
            return;
        }
        destroySelect2($el);
        var opts = {
            width: '100%',
            allowClear: true,
            placeholder: labelSelect || ' '
        };
        if (tags) {
            opts.tags = true;
            opts.createTag = function (params) {
                var t = $.trim(params.term || '');
                if (!t) {
                    return null;
                }
                return { id: t.toUpperCase(), text: t.toUpperCase(), newTag: true };
            };
        }
        $el.select2(opts);
    }

    function setOptions($sel, items, selectedId, selectedText, tags) {
        var cur = selectedId != null && String(selectedId) !== '' ? String(selectedId) : '';
        var curText = (selectedText || '').toString().trim();
        var html = '<option value=\"\"></option>';
        var found = false;

        (items || []).forEach(function (it) {
            var id = (it.id != null ? it.id : '').toString();
            var name = (it.name || it.postal_code || '').toString();
            var sel = '';
            if (cur !== '' && id === cur) {
                sel = ' selected';
                found = true;
            } else if (cur === '' && curText !== '' && name.toUpperCase() === curText.toUpperCase()) {
                sel = ' selected';
                found = true;
                cur = id;
            }
            html += '<option value=\"' + $('<div>').text(id).html() + '\"' + sel + '>' +
                $('<div>').text(name.toUpperCase()).html() + '</option>';
        });

        if (!found && cur !== '') {
            html += '<option value=\"' + $('<div>').text(cur).html() + '\" selected>' +
                $('<div>').text((curText || cur).toUpperCase()).html() + '</option>';
        } else if (!found && curText !== '' && tags) {
            html += '<option value=\"' + $('<div>').text(curText.toUpperCase()).html() + '\" selected>' +
                $('<div>').text(curText.toUpperCase()).html() + '</option>';
        }

        destroySelect2($sel);
        $sel.html(html);
        initSelect2($sel, !!tags);
    }

    function syncName($sel, $hidden) {
        if (!$hidden.length) {
            return;
        }
        var val = $sel.val();
        if (!val) {
            $hidden.val('');
            return;
        }
        $hidden.val($.trim($sel.find('option:selected').text() || '').toUpperCase());
    }

    function notify(prefix) {
        if (prefix === 'commercial' && typeof window.updateCommercialDeliveryAddress === 'function') {
            window.updateCommercialDeliveryAddress();
        }
        if (prefix === 'dispatch' && typeof window.updateDispatchDeliveryAddress === 'function') {
            window.updateDispatchDeliveryAddress();
        }
        if (typeof window.unlockEditableAddressFields === 'function') {
            window.unlockEditableAddressFields();
        }
    }

    function GeoCascade(prefix) {
        this.prefix = prefix;
        this.$country = $('#' + prefix + '_country_id');
        this.$state = $('#' + prefix + '_state_id');
        this.$stateName = $('#' + prefix + '_state');
        this.$city = $('#' + prefix + '_city_id');
        this.$cityName = $('#' + prefix + '_city');
        this.$zip = $('#' + prefix + '_zip_code');
        if (!this.$country.length || !this.$state.length || !this.$city.length || !this.$zip.length) {
            return;
        }
        this.bind();
        this.refresh(false);
    }

    GeoCascade.prototype.bind = function () {
        var self = this;
        this.$country.on('change.fvgeo', function () {
            self.refresh(true);
            notify(self.prefix);
        });
        this.$state.on('change.fvgeo', function () {
            syncName(self.$state, self.$stateName);
            // Al cambiar estado: recargar ciudades y ZIP del estado.
            self.loadCities(true).then(function () {
                return self.loadZips(true);
            }).then(function () {
                notify(self.prefix);
            });
        });
        this.$city.on('change.fvgeo', function () {
            syncName(self.$city, self.$cityName);
            // Mantener listado ZIP del estado (no vaciar al elegir ciudad).
            notify(self.prefix);
        });
        this.$zip.on('change.fvgeo', function () {
            notify(self.prefix);
        });
    };

    GeoCascade.prototype.refresh = function (reset) {
        var self = this;
        return this.loadStates(reset).then(function () {
            return self.loadCities(reset);
        }).then(function () {
            return self.loadZips(reset);
        }).then(function () {
            notify(self.prefix);
        });
    };

    GeoCascade.prototype.loadStates = function (reset) {
        var self = this;
        var countryId = this.$country.val();
        var keepId = reset ? '' : (this.$state.val() || '');
        var keepText = reset ? '' : (this.$stateName.val() || '');

        if (reset) {
            this.$stateName.val('');
            this.$cityName.val('');
            setOptions(this.$city, [], '', '', false);
            this.clearZip();
        }

        if (!countryId) {
            setOptions(this.$state, [], '', '', false);
            return $.Deferred().resolve().promise();
        }

        return fetchJson(options.url + '/states/searchstate', { country_id: countryId }).then(function (items) {
            setOptions(self.$state, items, keepId, keepText, false);
            syncName(self.$state, self.$stateName);
        });
    };

    GeoCascade.prototype.loadCities = function (reset) {
        var self = this;
        var stateId = this.$state.val();
        var keepId = reset ? '' : (this.$city.val() || '');
        var keepText = reset ? '' : (this.$cityName.val() || '');

        if (reset) {
            this.$cityName.val('');
        }

        if (!stateId) {
            setOptions(this.$city, [], '', '', false);
            return $.Deferred().resolve().promise();
        }

        return fetchJson(options.url + '/cities/searchcity', { state_id: stateId }).then(function (items) {
            setOptions(self.$city, items, keepId, keepText, false);
            syncName(self.$city, self.$cityName);
        });
    };

    GeoCascade.prototype.clearZip = function () {
        destroySelect2(this.$zip);
        this.$zip.html('<option value=\"\"></option>');
        initSelect2(this.$zip, true);
    };

    /**
     * ZIP por ESTADO (principal). Select2 AJAX para buscar entre muchos códigos.
     */
    GeoCascade.prototype.loadZips = function (reset) {
        var self = this;
        var stateId = this.$state.val();
        var countryId = this.$country.val();
        var keep = reset ? '' : $.trim(this.$zip.val() || '');

        if (!stateId && !countryId) {
            this.clearZip();
            return $.Deferred().resolve().promise();
        }

        var queryBase = {};
        if (stateId) {
            queryBase.state_id = stateId;
        } else {
            queryBase.country_id = countryId;
        }

        // Precarga inicial (primeros del estado) + valor actual.
        return fetchJson(options.url + '/postal/searchzip', queryBase).then(function (items) {
            setOptions(self.$zip, items, keep, keep, true);
            self.initZipAjax(queryBase, keep);
        });
    };

    GeoCascade.prototype.initZipAjax = function (queryBase, keep) {
        var self = this;
        if (!this.$zip.length || typeof $.fn.select2 === 'undefined') {
            return;
        }

        // Re-inicializar Select2 con AJAX sobre el mismo select (mantiene options precargadas).
        destroySelect2(this.$zip);
        if (keep && this.$zip.find('option[value=\"' + keep.replace(/"/g, '\\"') + '\"]').length === 0) {
            this.$zip.append(
                $('<option>', { value: keep, text: keep, selected: true })
            );
        }

        this.$zip.select2({
            width: '100%',
            allowClear: true,
            placeholder: labelSelect || ' ',
            tags: true,
            createTag: function (params) {
                var t = $.trim(params.term || '');
                if (!t) {
                    return null;
                }
                return { id: t.toUpperCase(), text: t.toUpperCase(), newTag: true };
            },
            ajax: {
                url: options.url + '/postal/searchzip',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    var q = $.extend({}, queryBase);
                    if (params.term) {
                        q.term = params.term;
                    }
                    return q;
                },
                processResults: function (data) {
                    var items = Array.isArray(data) ? data : [];
                    return {
                        results: items.map(function (it) {
                            var code = (it.id || it.postal_code || it.name || '').toString();
                            var place = (it.place_name || '').toString();
                            var text = place ? (code + ' — ' + place.toUpperCase()) : code;
                            return { id: code, text: text };
                        })
                    };
                },
                cache: true
            },
            minimumInputLength: 0
        });

        if (keep) {
            this.$zip.val(keep).trigger('change.select2');
        }
    };

    $(function () {
        window.fvGeoCommercial = new GeoCascade('commercial');
        window.fvGeoDispatch = new GeoCascade('dispatch');

        $('#asignar_datos').on('change.fvgeo', function () {
            if (!$(this).is(':checked') || !window.fvGeoDispatch) {
                return;
            }
            setTimeout(function () {
                window.fvGeoDispatch.refresh(false);
            }, 100);
        });
    });
})(jQuery);
