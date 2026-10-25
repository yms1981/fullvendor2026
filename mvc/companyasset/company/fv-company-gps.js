(function () {
    'use strict';

    var cfg = window.FV_COMPANY_GPS || {};
    var lang = cfg.lang || {};
    var apiBase = String(cfg.apiBaseUrl || '/api').replace(/\/$/, '');
    var token = cfg.apiToken || '';
    var companyId = Number(cfg.companyId || 0);

    var state = {
        open: false,
        view: 'list', // list | map
        sellers: [],
        search: '',
        activeSeller: null,
        date: todayYmd(),
        map: null,
        layer: null,
        markers: []
    };

    function t(key, fallback) {
        return lang[key] != null && lang[key] !== '' ? lang[key] : (fallback || key);
    }

    function $(id) {
        return document.getElementById(id);
    }

    function todayYmd() {
        var d = new Date();
        var m = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        return d.getFullYear() + '-' + m + '-' + day;
    }

    function apiUrl(path) {
        var sep = path.indexOf('?') >= 0 ? '&' : '?';
        return apiBase + '/v2/company-gps/' + path.replace(/^\//, '') + sep + 'company_id=' + encodeURIComponent(companyId);
    }

    function authHeaders() {
        return {
            'Authorization': 'Bearer ' + token,
            'Accept': 'application/json',
            'X-Company-Id': String(companyId)
        };
    }

    function request(path) {
        return fetch(apiUrl(path), { method: 'GET', headers: authHeaders() }).then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (body) {
                if (!res.ok) {
                    var err = new Error((body && body.message) || ('HTTP ' + res.status));
                    err.status = res.status;
                    err.body = body;
                    throw err;
                }
                return body;
            });
        });
    }

    function initials(name) {
        name = String(name || '?').trim();
        var parts = name.split(/\s+/).filter(Boolean);
        if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
        return name.slice(0, 2).toUpperCase();
    }

    function resolveSellerAvatarUrl(seller) {
        var imageBase = String(cfg.imageBaseUrl || '').replace(/\/$/, '');
        var ref = String((seller && (seller.image || seller.avatar_url)) || '').trim();
        if (!ref) {
            return '';
        }
        if (/^https?:\/\//i.test(ref) || ref.indexOf('//') === 0) {
            // Evitar hosts internos (API_SERVER) inaccesibles desde el navegador.
            if (/laravel\.test|localhost(:\d+)?/i.test(ref)) {
                ref = ref.replace(/^https?:\/\/[^/]+/i, '').replace(/^\/\/[^/]+/, '');
            } else {
                return ref;
            }
        }
        // /api/v2/images/{uuid} → proxy company/image
        var mApi = ref.match(/\/api\/v2\/images\/([0-9a-f-]{36}(?:\.[a-z0-9]+)?)/i);
        if (mApi && imageBase) {
            return imageBase + '/' + mApi[1];
        }
        if (/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}(?:\.[a-z0-9]+)?$/i.test(ref) && imageBase) {
            return imageBase + '/' + ref;
        }
        if (ref.charAt(0) === '/') {
            return ref;
        }
        return imageBase ? (imageBase + '/' + ref.replace(/^\/+/, '')) : '';
    }

    function formatWhen(iso) {
        if (!iso) return t('never_tracked', '');
        try {
            var d = new Date(iso);
            if (isNaN(d.getTime())) return iso;
            return d.toLocaleString();
        } catch (e) {
            return iso;
        }
    }

    function setOpen(open) {
        state.open = !!open;
        var btn = $('fvGpsBtn');
        var panel = $('fvGpsPanel');
        var backdrop = $('fvGpsBackdrop');
        if (!panel || !backdrop) return;
        if (state.open) {
            panel.hidden = false;
            backdrop.hidden = false;
            requestAnimationFrame(function () {
                panel.classList.add('fv-open');
                backdrop.classList.add('fv-open');
            });
            if (state.view === 'list') loadSellers();
            if (state.view === 'map') {
                ensureMap();
                loadTrack();
            }
        } else {
            panel.classList.remove('fv-open');
            backdrop.classList.remove('fv-open');
            setTimeout(function () {
                if (!state.open) {
                    panel.hidden = true;
                    backdrop.hidden = true;
                }
            }, 280);
        }
        if (btn) btn.setAttribute('aria-expanded', state.open ? 'true' : 'false');
    }

    function showList() {
        state.view = 'list';
        state.activeSeller = null;
        var list = $('fvGpsListView');
        var mapV = $('fvGpsMapView');
        var back = $('fvGpsBack');
        var panel = $('fvGpsPanel');
        var title = $('fvGpsTitle');
        var sub = $('fvGpsSubtitle');
        if (list) list.hidden = false;
        if (mapV) mapV.hidden = true;
        if (back) back.hidden = true;
        if (panel) panel.classList.remove('fv-map-mode');
        if (title) title.textContent = t('widget_title', '');
        if (sub) sub.textContent = t('sellers_subtitle', '');
    }

    function showMap(seller) {
        state.view = 'map';
        state.activeSeller = seller;
        var list = $('fvGpsListView');
        var mapV = $('fvGpsMapView');
        var back = $('fvGpsBack');
        var panel = $('fvGpsPanel');
        var title = $('fvGpsTitle');
        var sub = $('fvGpsSubtitle');
        var dateInput = $('fvGpsDate');
        if (list) list.hidden = true;
        if (mapV) mapV.hidden = false;
        if (back) back.hidden = false;
        if (panel) panel.classList.add('fv-map-mode');
        if (title) title.textContent = seller.name || t('route_title', '');
        if (sub) sub.textContent = seller.email || '';
        if (dateInput) {
            if (!dateInput.value) dateInput.value = state.date;
            state.date = dateInput.value || state.date;
        }
        // Esperar al layout/animación del panel ancho antes de medir el mapa
        requestAnimationFrame(function () {
            ensureMap();
            loadTrack();
        });
    }

    function renderSellers() {
        var wrap = $('fvGpsSellers');
        var empty = $('fvGpsEmpty');
        var err = $('fvGpsError');
        if (!wrap) return;
        if (err) err.hidden = true;

        var q = String(state.search || '').trim().toLowerCase();
        var rows = state.sellers.filter(function (s) {
            if (!q) return true;
            var hay = ((s.name || '') + ' ' + (s.email || '')).toLowerCase();
            return hay.indexOf(q) >= 0;
        });

        wrap.innerHTML = '';
        if (rows.length === 0) {
            if (empty) empty.hidden = false;
            return;
        }
        if (empty) empty.hidden = true;

        rows.forEach(function (s) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'fv-gps-item';
            btn.addEventListener('click', function () { showMap(s); });

            var av = document.createElement('div');
            av.className = 'fv-gps-avatar';
            var avatarSrc = resolveSellerAvatarUrl(s);
            if (avatarSrc) {
                var img = document.createElement('img');
                img.src = avatarSrc;
                img.alt = '';
                img.onerror = function () {
                    img.remove();
                    av.textContent = initials(s.name);
                };
                av.appendChild(img);
            } else {
                av.textContent = initials(s.name);
            }

            var main = document.createElement('div');
            main.className = 'fv-gps-item-main';
            var name = document.createElement('div');
            name.className = 'fv-gps-item-name';
            name.textContent = s.name || ('#' + s.user_id);
            var meta = document.createElement('div');
            meta.className = 'fv-gps-item-meta';
            meta.textContent = s.last_point_at
                ? (t('last_point', '') + ' ' + formatWhen(s.last_point_at))
                : t('never_tracked', '');
            main.appendChild(name);
            main.appendChild(meta);

            var chev = document.createElement('i');
            chev.className = 'mdi mdi-chevron-right fv-gps-item-chevron';

            btn.appendChild(av);
            btn.appendChild(main);
            btn.appendChild(chev);
            wrap.appendChild(btn);
        });
    }

    function loadSellers() {
        var err = $('fvGpsError');
        if (!companyId || !token) {
            if (err) {
                err.hidden = false;
                err.textContent = t('error_load', '');
            }
            return;
        }
        request('sellers').then(function (body) {
            state.sellers = (body && body.data) || [];
            renderSellers();
        }).catch(function () {
            if (err) {
                err.hidden = false;
                err.textContent = t('error_load', '');
            }
        });
    }

    function clearMapLayers() {
        if (!state.map) return;
        if (state.layer) {
            state.map.removeLayer(state.layer);
            state.layer = null;
        }
        state.markers.forEach(function (m) {
            try { state.map.removeLayer(m); } catch (e) {}
        });
        state.markers = [];
    }

    function refreshMapSize() {
        if (!state.map) return;
        requestAnimationFrame(function () {
            try { state.map.invalidateSize(true); } catch (e) {}
            // Tras la animación de ancho del panel (280ms)
            setTimeout(function () {
                try { state.map.invalidateSize(true); } catch (e2) {}
            }, 300);
            setTimeout(function () {
                try { state.map.invalidateSize(true); } catch (e3) {}
            }, 450);
        });
    }

    function ensureMap() {
        if (typeof L === 'undefined') return;
        var el = $('fvGpsMap');
        if (!el) return;
        if (state.map) {
            refreshMapSize();
            return;
        }
        state.map = L.map(el, {
            zoomControl: true,
            preferCanvas: false
        }).setView([4.65, -74.1], 6);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(state.map);
        refreshMapSize();
    }

    function drawRoute(points) {
        clearMapLayers();
        var empty = $('fvGpsMapEmpty');
        var meta = $('fvGpsMapMeta');
        if (!state.map) return;

        if (!points || points.length === 0) {
            if (empty) empty.hidden = false;
            if (meta) meta.textContent = t('points_count', '').replace('{n}', '0');
            return;
        }
        if (empty) empty.hidden = true;

        var latlngs = points.map(function (p) {
            return [Number(p.latitude), Number(p.longitude)];
        }).filter(function (ll) {
            return isFinite(ll[0]) && isFinite(ll[1]) && !(ll[0] === 0 && ll[1] === 0);
        });

        if (latlngs.length === 0) {
            if (empty) empty.hidden = false;
            if (meta) meta.textContent = t('points_count', '').replace('{n}', '0');
            return;
        }

        state.layer = L.polyline(latlngs, {
            color: '#c82333',
            weight: 4,
            opacity: 0.9
        }).addTo(state.map);

        var start = L.circleMarker(latlngs[0], {
            radius: 7,
            color: '#212529',
            fillColor: '#ffffff',
            fillOpacity: 1,
            weight: 2
        }).addTo(state.map).bindPopup(t('start_point', ''));
        state.markers.push(start);

        var end = L.circleMarker(latlngs[latlngs.length - 1], {
            radius: 7,
            color: '#c82333',
            fillColor: '#dc3545',
            fillOpacity: 1
        }).addTo(state.map).bindPopup(t('end_point', ''));
        state.markers.push(end);

        // Marcadores intermedios ligeros (máx 40)
        var step = Math.max(1, Math.ceil(latlngs.length / 40));
        for (var i = step; i < latlngs.length - 1; i += step) {
            var p = points[i];
            var m = L.circleMarker(latlngs[i], {
                radius: 3,
                color: '#c82333',
                fillColor: '#fde8ea',
                fillOpacity: 0.95
            }).addTo(state.map);
            if (p && (p.address || p.created)) {
                m.bindPopup(
                    (p.created ? formatWhen(p.created) : '') +
                    (p.address ? ('<br>' + String(p.address)) : '')
                );
            }
            state.markers.push(m);
        }

        refreshMapSize();
        setTimeout(function () {
            try {
                state.map.fitBounds(state.layer.getBounds(), { padding: [36, 36] });
            } catch (e) {}
        }, 320);

        if (meta) {
            var first = points[0] && points[0].created ? formatWhen(points[0].created) : '';
            var last = points[points.length - 1] && points[points.length - 1].created
                ? formatWhen(points[points.length - 1].created)
                : '';
            meta.textContent = t('points_count', '').replace('{n}', String(latlngs.length)) +
                (first && last ? (' · ' + first + ' → ' + last) : '');
        }
    }

    function loadTrack() {
        if (!state.activeSeller) return;
        var empty = $('fvGpsMapEmpty');
        var meta = $('fvGpsMapMeta');
        var dateInput = $('fvGpsDate');
        state.date = (dateInput && dateInput.value) || state.date || todayYmd();
        if (meta) meta.textContent = t('loading', '');
        if (empty) empty.hidden = true;

        var path = 'track?user_id=' + encodeURIComponent(state.activeSeller.user_id) +
            '&date=' + encodeURIComponent(state.date);

        request(path).then(function (body) {
            var data = (body && body.data) || {};
            drawRoute(data.points || []);
        }).catch(function () {
            clearMapLayers();
            if (meta) meta.textContent = t('error_load_route', '');
            if (empty) {
                empty.hidden = false;
                empty.textContent = t('error_load_route', '');
            }
        });
    }

    function bind() {
        var btn = $('fvGpsBtn');
        var close = $('fvGpsClose');
        var back = $('fvGpsBack');
        var backdrop = $('fvGpsBackdrop');
        var search = $('fvGpsSearch');
        var dateInput = $('fvGpsDate');
        var reload = $('fvGpsReload');

        if (btn) btn.addEventListener('click', function () { setOpen(!state.open); });
        if (close) close.addEventListener('click', function () { setOpen(false); });
        if (backdrop) backdrop.addEventListener('click', function () { setOpen(false); });
        if (back) back.addEventListener('click', function () { showList(); });
        if (search) {
            search.addEventListener('input', function () {
                state.search = search.value || '';
                renderSellers();
            });
        }
        if (dateInput) {
            dateInput.value = state.date;
            dateInput.addEventListener('change', function () {
                state.date = dateInput.value || todayYmd();
                loadTrack();
            });
        }
        if (reload) reload.addEventListener('click', function () { loadTrack(); });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && state.open) {
                if (state.view === 'map') showList();
                else setOpen(false);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bind);
    } else {
        bind();
    }
})();
