/**
 * Cascada País → Estado → Ciudad → ZIP (Select2).
 * ZIP se lista según el ESTADO (no solo la ciudad) con búsqueda AJAX.
 * Estados: si hay sigla (code), el select muestra la sigla.
 * Espera selects: {prefix}_state_id, {prefix}_city_id, {prefix}_zip_code
 * y hiddens de nombre: {prefix}_state, {prefix}_city
 */
(function ($) {
    'use strict';

    if (!window.options || !window.options.url) {
        return;
    }

    var labelSelect = (window.pageTexts && (window.pageTexts.label_select || window.pageTexts.placeholder_select_country)) || '';

    function placeholderText() {
        var fromPage = (window.pageTexts && (window.pageTexts.label_select || window.pageTexts.placeholder_select_country)) || '';
        return fromPage || labelSelect || ' ';
    }

    function fetchJson(url, data) {
        return $.ajax({
            url: url,
            method: 'GET',
            data: data || {},
            dataType: 'text'
        }).then(function (raw) {
            try {
                var items = (typeof raw === 'string') ? JSON.parse(raw) : raw;
                if (Array.isArray(items)) {
                    return items;
                }
                if (items && Array.isArray(items.data)) {
                    return items.data;
                }
                return [];
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
            placeholder: placeholderText()
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

    function itemCode(it) {
        return $.trim((it && (it.code || it.abbr || it.state_code) || '')).toString().toUpperCase();
    }

    function itemFullName(it) {
        var raw = $.trim((it && (it.full_name || it.state_name) || '')).toString();
        if (raw) {
            return raw.toUpperCase();
        }
        // Si solo viene "name" tipo "ND - NORTH DAKOTA", extraer el nombre.
        var name = $.trim((it && it.name) || '').toString().toUpperCase();
        var m = name.match(/^[A-Z0-9]{1,5}\s*-\s*(.+)$/);
        return m ? $.trim(m[1]) : name;
    }

    function itemLabel(it) {
        var code = itemCode(it);
        var full = itemFullName(it);
        if (code && full) {
            return code + ' - ' + full;
        }
        if (code) {
            return code;
        }
        return full;
    }

    function matchesStateKeep(curUpper, code, full, label) {
        if (!curUpper) {
            return false;
        }
        if (label && label.toUpperCase() === curUpper) {
            return true;
        }
        if (code && code === curUpper) {
            return true;
        }
        if (full && full.toUpperCase() === curUpper) {
            return true;
        }
        // "ND - NORTH DAKOTA" guardado completo, o partes invertidas.
        if (code && full) {
            var combo = (code + ' - ' + full).toUpperCase();
            if (combo === curUpper) {
                return true;
            }
        }
        return false;
    }

    function setOptions($sel, items, selectedId, selectedText, tags) {
        var cur = selectedId != null && String(selectedId) !== '' ? String(selectedId) : '';
        var curText = (selectedText || '').toString().trim();
        var curUpper = curText.toUpperCase();
        var html = '<option value=\"\"></option>';
        var found = false;

        (items || []).forEach(function (it) {
            var id = (it.id != null ? it.id : '').toString();
            var code = itemCode(it);
            var full = itemFullName(it);
            var label = itemLabel(it);
            var sel = '';
            if (cur !== '' && id === cur) {
                sel = ' selected';
                found = true;
            } else if (cur === '' && curUpper !== '' && matchesStateKeep(curUpper, code, full, label)) {
                sel = ' selected';
                found = true;
                cur = id;
            }
            html += '<option value=\"' + $('<div>').text(id).html() + '\"'
                + ' data-code=\"' + $('<div>').text(code).html() + '\"'
                + ' data-full-name=\"' + $('<div>').text(full).html() + '\"'
                + sel + '>'
                + $('<div>').text(label).html() + '</option>';
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
        var selId = ($sel.attr('id') || '');
        if (selId.indexOf('_state_id') !== -1) {
            initStateSelect2($sel);
        } else if (selId.indexOf('_zip_code') !== -1) {
            // ZIP se reinicializa con AJAX en initZipAjax; aquí solo placeholder.
            initSelect2($sel, !!tags);
        } else {
            initSelect2($sel, !!tags);
        }
    }

    function zipLabel(it) {
        var code = $.trim((it && (it.postal_code || it.id || it.name) || '')).toString();
        // Si name ya viene "58103 - FARGO", usarlo.
        var rawName = $.trim((it && it.name) || '').toString();
        if (rawName.indexOf(' - ') > 0 && rawName.indexOf(code) === 0) {
            return rawName.toUpperCase();
        }
        var place = $.trim((it && (it.place_name || it.city_name) || '')).toString().toUpperCase();
        if (code && place) {
            return code + ' - ' + place;
        }
        return (code || rawName).toUpperCase();
    }

    function setZipOptions($sel, items, selectedCode) {
        var cur = selectedCode != null && String(selectedCode) !== '' ? String(selectedCode) : '';
        var html = '<option value=\"\"></option>';
        var found = false;

        (items || []).forEach(function (it) {
            var code = $.trim((it.postal_code || it.id || '')).toString();
            if (!code) {
                return;
            }
            var place = $.trim((it.place_name || it.city_name || '')).toString().toUpperCase();
            var label = zipLabel(it);
            var sel = (cur !== '' && code === cur) ? ' selected' : '';
            if (sel) {
                found = true;
            }
            html += '<option value=\"' + $('<div>').text(code).html() + '\"'
                + ' data-place=\"' + $('<div>').text(place).html() + '\"'
                + ' data-city-id=\"' + $('<div>').text(String(it.city_id || '')).html() + '\"'
                + ' data-state-id=\"' + $('<div>').text(String(it.state_id || '')).html() + '\"'
                + ' data-state-code=\"' + $('<div>').text(String(it.state_code || '')).html() + '\"'
                + ' data-state-name=\"' + $('<div>').text(String(it.state_name || '')).html() + '\"'
                + sel + '>'
                + $('<div>').text(label).html() + '</option>';
        });

        if (!found && cur !== '') {
            html += '<option value=\"' + $('<div>').text(cur).html() + '\" selected>' +
                $('<div>').text(cur.toUpperCase()).html() + '</option>';
        }

        destroySelect2($sel);
        $sel.html(html);
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
        var $opt = $sel.find('option:selected');
        var code = $.trim($opt.attr('data-code') || '').toUpperCase();
        var full = $.trim($opt.attr('data-full-name') || '').toUpperCase();
        var text = $.trim($opt.text() || '').toUpperCase();
        // Hidden / concatenación: solo sigla si hay; si no, solo nombre (nunca "MI - MICHIGAN").
        if (code) {
            $hidden.val(code);
            return;
        }
        var m = text.match(/^([A-Z0-9]{1,5})\s*[-–—]\s+(.+)$/);
        if (m) {
            $hidden.val(m[1]);
            return;
        }
        $hidden.val(full || text);
    }

    function initStateSelect2($el) {
        if (!$el.length || typeof $.fn.select2 === 'undefined') {
            return;
        }
        destroySelect2($el);
        $el.select2({
            width: '100%',
            allowClear: true,
            placeholder: placeholderText(),
            matcher: function (params, data) {
                if ($.trim(params.term || '') === '') {
                    return data;
                }
                if (!data || !data.id) {
                    return null;
                }
                var term = $.trim(params.term).toUpperCase();
                var $opt = $(data.element);
                var code = $.trim($opt.attr('data-code') || '').toUpperCase();
                var full = $.trim($opt.attr('data-full-name') || '').toUpperCase();
                var text = $.trim(data.text || '').toUpperCase();
                if (text.indexOf(term) > -1
                    || (code && code.indexOf(term) > -1)
                    || (full && full.indexOf(term) > -1)) {
                    return data;
                }
                return null;
            }
        });
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
            if (self.syncingFromZip) {
                // Cambio de estado disparado por elegir ZIP: no resetear ZIP.
                return;
            }
            self.loadCities(true).then(function () {
                return self.loadZips(true);
            }).then(function () {
                notify(self.prefix);
            });
        });
        this.$city.on('change.fvgeo', function () {
            syncName(self.$city, self.$cityName);
            if (self.syncingFromZip) {
                notify(self.prefix);
                return;
            }
            // ZIP se filtra por estado (no por ciudad); no vaciar al cambiar ciudad.
            notify(self.prefix);
        });
        this.$zip.on('change.fvgeo', function () {
            notify(self.prefix);
        });
        this.$zip.on('select2:select.fvgeo', function (e) {
            var data = (e && e.params && e.params.data) ? e.params.data : {};
            var $opt = self.$zip.find('option:selected');
            var meta = {
                place_name: data.place_name || $opt.attr('data-place') || '',
                city_id: data.city_id || $opt.attr('data-city-id') || '',
                state_id: data.state_id || $opt.attr('data-state-id') || '',
                state_code: data.state_code || $opt.attr('data-state-code') || '',
                state_name: data.state_name || $opt.attr('data-state-name') || ''
            };
            if (!meta.place_name && data.text) {
                var parts = String(data.text).split(/\s+-\s+/);
                if (parts.length > 1) {
                    meta.place_name = parts.slice(1).join(' - ');
                }
            }
            self.applyZipSelection(meta).then(function () {
                notify(self.prefix);
            });
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
     * ZIP: con estado → filtra por estado; sin estado → todo el país.
     * Texto visible: "ZIPCODE - CITY". Valor / reconocimiento: zipcode.
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

        var query = { country_id: countryId || '' };
        if (stateId) {
            query.state_id = stateId;
        }

        return fetchJson(options.url + '/postal/searchzip', query).then(function (items) {
            setZipOptions(self.$zip, items, keep);
            self.initZipAjax(keep);
        });
    };

    /**
     * Al elegir un ZIP: si estado/ciudad están vacíos, asignarlos desde el ZIP.
     */
    GeoCascade.prototype.applyZipSelection = function (meta) {
        var self = this;
        meta = meta || {};
        var place = $.trim(meta.place_name || '').toUpperCase();
        var stateId = String(meta.state_id || '');
        var stateCode = $.trim(meta.state_code || '').toUpperCase();
        var stateName = $.trim(meta.state_name || '').toUpperCase();
        var cityId = String(meta.city_id || '');

        var stateEmpty = !this.$state.val();
        var cityEmpty = !this.$city.val();

        var chain = $.Deferred().resolve().promise();

        if (stateEmpty && (stateId || stateCode || stateName)) {
            chain = chain.then(function () {
                return self.ensureStateSelected(stateId, stateCode, stateName);
            }).then(function () {
                return self.loadCities(false);
            });
        }

        if (cityEmpty && (place || cityId)) {
            chain = chain.then(function () {
                if (!self.$city.val() || self.$city.find('option').length <= 1) {
                    return self.loadCities(false);
                }
            }).then(function () {
                self.selectCityByMeta(place, cityId);
            });
        }

        return chain;
    };

    GeoCascade.prototype.ensureStateSelected = function (stateId, stateCode, stateName) {
        var self = this;
        var wantId = stateId ? String(stateId) : '';
        var wantCode = $.trim(stateCode || '').toUpperCase();
        var wantName = $.trim(stateName || '').toUpperCase();

        var match = '';
        this.$state.find('option').each(function () {
            var $o = $(this);
            var id = String($o.val() || '');
            if (!id) {
                return;
            }
            var code = $.trim($o.attr('data-code') || '').toUpperCase();
            var full = $.trim($o.attr('data-full-name') || '').toUpperCase();
            var text = $.trim($o.text() || '').toUpperCase();
            if ((wantId && id === wantId)
                || (wantCode && code === wantCode)
                || (wantName && (full === wantName || text.indexOf(wantName) > -1))
                || (wantCode && text.indexOf(wantCode + ' -') === 0)) {
                match = id;
                return false;
            }
        });

        var ensureLoaded = $.Deferred().resolve().promise();
        if (!match && this.$country.val()) {
            ensureLoaded = this.loadStates(false);
        }

        return ensureLoaded.then(function () {
            if (!match) {
                self.$state.find('option').each(function () {
                    var $o = $(this);
                    var id = String($o.val() || '');
                    if (!id) {
                        return;
                    }
                    var code = $.trim($o.attr('data-code') || '').toUpperCase();
                    var full = $.trim($o.attr('data-full-name') || '').toUpperCase();
                    var text = $.trim($o.text() || '').toUpperCase();
                    if ((wantId && id === wantId)
                        || (wantCode && code === wantCode)
                        || (wantName && (full === wantName || text.indexOf(wantName) > -1))
                        || (wantCode && text.indexOf(wantCode + ' -') === 0)) {
                        match = id;
                        return false;
                    }
                });
            }
            if (!match || String(self.$state.val() || '') === match) {
                syncName(self.$state, self.$stateName);
                return;
            }
            self.syncingFromZip = true;
            self.$state.val(match).trigger('change.select2');
            syncName(self.$state, self.$stateName);
            self.syncingFromZip = false;
        });
    };

    GeoCascade.prototype.selectCityByMeta = function (place, cityId) {
        var want = $.trim(place || '').toUpperCase();
        var wantId = cityId ? String(cityId) : '';
        if ((!want && !wantId) || !this.$city.length) {
            return;
        }
        var match = '';
        this.$city.find('option').each(function () {
            var id = String($(this).val() || '');
            var text = $.trim($(this).text() || '').toUpperCase();
            if (!id) {
                return;
            }
            if ((wantId && id === wantId) || (want && text === want)) {
                match = id;
                return false;
            }
        });
        if (!match) {
            // Si la ciudad no está en el catálogo, dejar el nombre en el hidden.
            if (want && this.$cityName.length) {
                this.$cityName.val(want);
            }
            return;
        }
        if (String(this.$city.val() || '') === match) {
            syncName(this.$city, this.$cityName);
            return;
        }
        this.syncingFromZip = true;
        this.$city.val(match).trigger('change.select2');
        syncName(this.$city, this.$cityName);
        this.syncingFromZip = false;
    };

    GeoCascade.prototype.selectCityByName = function (place) {
        this.selectCityByMeta(place, '');
    };

    GeoCascade.prototype.initZipAjax = function (keep) {
        var self = this;
        if (!this.$zip.length || typeof $.fn.select2 === 'undefined') {
            return;
        }

        destroySelect2(this.$zip);
        if (keep && this.$zip.find('option[value=\"' + keep.replace(/"/g, '\\"') + '\"]').length === 0) {
            this.$zip.append(
                $('<option>', { value: keep, text: keep, selected: true })
            );
        }

        this.$zip.select2({
            width: '100%',
            allowClear: true,
            placeholder: placeholderText(),
            tags: true,
            createTag: function (params) {
                var t = $.trim(params.term || '');
                if (!t) {
                    return null;
                }
                // El valor sigue siendo solo el zipcode.
                var code = t.split(/\s+-\s+/)[0].toUpperCase();
                return { id: code, text: code, newTag: true };
            },
            ajax: {
                url: options.url + '/postal/searchzip',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    var q = {};
                    var stateId = self.$state.val();
                    var countryId = self.$country.val();
                    // Con estado: filtrar por estado. Sin estado: todo el país.
                    if (stateId) {
                        q.state_id = stateId;
                    }
                    if (countryId) {
                        q.country_id = countryId;
                    }
                    if (params.term) {
                        q.term = params.term;
                    }
                    return q;
                },
                processResults: function (data) {
                    var items = Array.isArray(data) ? data : (data && Array.isArray(data.data) ? data.data : []);
                    return {
                        results: items.map(function (it) {
                            var code = (it.postal_code || it.id || '').toString();
                            var place = (it.place_name || it.city_name || '').toString().toUpperCase();
                            var text = place ? (code + ' - ' + place) : code;
                            return {
                                id: code,
                                text: text,
                                place_name: place,
                                city_id: it.city_id || '',
                                state_id: it.state_id || '',
                                state_code: it.state_code || '',
                                state_name: it.state_name || ''
                            };
                        })
                    };
                },
                cache: false
            },
            minimumInputLength: 0,
            templateSelection: function (item) {
                // En el cerrado del select mostrar zip - city si viene; valor = zip.
                if (!item || !item.id) {
                    return item && item.text ? item.text : '';
                }
                var $opt = self.$zip.find('option[value=\"' + String(item.id).replace(/"/g, '\\"') + '\"]');
                var place = $opt.length ? $.trim($opt.attr('data-place') || '') : (item.place_name || '');
                if (!place && item.text && String(item.text).indexOf(' - ') > 0) {
                    return item.text;
                }
                return place ? (item.id + ' - ' + String(place).toUpperCase()) : String(item.id);
            }
        });

        if (keep) {
            this.$zip.val(keep).trigger('change.select2');
        }
    };

    /**
     * Copia dirección comercial → delivery (mismos valores, siglas y concatenación).
     */
    function copyCommercialAddressToDispatch() {
        var $cAddr = $('#commercial_address');
        var $dAddr = $('#dispatch_address');
        if ($cAddr.length && $dAddr.length) {
            $dAddr.val($cAddr.val());
        }
        $('#dispatch_zone').val($('#commercial_zone').val() || '');
        $('#dispatch_state').val($('#commercial_state').val() || '');
        $('#dispatch_city').val($('#commercial_city').val() || '');

        var cCountry = $('#commercial_country_id').val() || '';
        $('#dispatch_country_id').val(cCountry);
        if (typeof $.fn.select2 !== 'undefined') {
            try { $('#dispatch_country_id').trigger('change.select2'); } catch (e0) {}
        }

        // Clonar opciones (incluye data-code / data-full-name) y selección.
        var pairs = [
            ['#commercial_state_id', '#dispatch_state_id', true],
            ['#commercial_city_id', '#dispatch_city_id', false],
            ['#commercial_zip_code', '#dispatch_zip_code', false]
        ];
        pairs.forEach(function (pair) {
            var $src = $(pair[0]);
            var $dst = $(pair[1]);
            var asState = !!pair[2];
            if (!$src.length || !$dst.length) {
                return;
            }
            var val = $src.val();
            destroySelect2($dst);
            $dst.html($src.html());
            $dst.val(val);
            if (asState) {
                initStateSelect2($dst);
            } else if (pair[1].indexOf('zip') !== -1) {
                initSelect2($dst, true);
            } else {
                initSelect2($dst, false);
            }
            if (val) {
                try { $dst.val(val).trigger('change.select2'); } catch (e) {
                    $dst.val(val).trigger('change');
                }
            }
        });

        syncName($('#dispatch_state_id'), $('#dispatch_state'));
        syncName($('#dispatch_city_id'), $('#dispatch_city'));

        if (typeof window.updateDispatchDeliveryAddress === 'function') {
            window.updateDispatchDeliveryAddress();
        }
        if (typeof window.unlockEditableAddressFields === 'function') {
            window.unlockEditableAddressFields();
        }
    }

    window.fvCopyCommercialAddressToDispatch = copyCommercialAddressToDispatch;
    window.fvInitStateSelect2 = initStateSelect2;

    $(function () {
        window.fvGeoCommercial = new GeoCascade('commercial');
        window.fvGeoDispatch = new GeoCascade('dispatch');
        if ($('#profile_state_id').length && $('#profile_city_id').length && $('#profile_zip_code').length) {
            window.fvGeoProfile = new GeoCascade('profile');
        }

        $('#asignar_datos').on('change.fvgeo', function () {
            if (!$(this).is(':checked')) {
                return;
            }
            // La vista puede llamar a fvCopyCommercialAddressToDispatch;
            // si no, copiamos aquí y refrescamos cascada delivery.
            setTimeout(function () {
                if (typeof window.fvCopyCommercialAddressToDispatch === 'function') {
                    window.fvCopyCommercialAddressToDispatch();
                } else if (window.fvGeoDispatch) {
                    window.fvGeoDispatch.refresh(false);
                }
            }, 50);
        });
    });
})(jQuery);
