(function ($) {
    'use strict';

    if (window.__fvCustomerLastActivityBound) {
        return;
    }
    window.__fvCustomerLastActivityBound = true;

    var openMenuEl = null;
    var openToggleEl = null;

    function root() {
        return document.querySelector('.fv-customer-last-activity');
    }

    function meta(key, fallback) {
        var el = root();
        if (!el) {
            return fallback || '';
        }
        return String(el.getAttribute(key) || fallback || '');
    }

    function swalFire(options) {
        if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
            return Swal.fire(options);
        }
        return Promise.resolve({ isConfirmed: false });
    }

    function closeCatalogActionsMenu() {
        if (openMenuEl) {
            openMenuEl.hidden = true;
            openMenuEl.classList.remove('is-open');
            openMenuEl.style.top = '';
            openMenuEl.style.left = '';
            openMenuEl.style.right = '';
            if (openMenuEl.parentElement !== openMenuEl.__fvHomeParent && openMenuEl.__fvHomeParent) {
                openMenuEl.__fvHomeParent.appendChild(openMenuEl);
            }
        }
        if (openToggleEl) {
            openToggleEl.setAttribute('aria-expanded', 'false');
            openToggleEl.classList.remove('is-open');
        }
        openMenuEl = null;
        openToggleEl = null;
    }

    function openCatalogActionsMenu(toggle, menu) {
        closeCatalogActionsMenu();
        if (!toggle || !menu) {
            return;
        }
        if (!menu.__fvHomeParent) {
            menu.__fvHomeParent = menu.parentElement;
        }
        document.body.appendChild(menu);
        menu.hidden = false;
        menu.classList.add('is-open');

        var rect = toggle.getBoundingClientRect();
        var menuWidth = Math.max(menu.offsetWidth || 0, 168);
        var top = rect.bottom + 4;
        var left = rect.right - menuWidth;
        if (left < 8) {
            left = 8;
        }
        if (left + menuWidth > window.innerWidth - 8) {
            left = Math.max(8, window.innerWidth - menuWidth - 8);
        }
        if (top + menu.offsetHeight > window.innerHeight - 8) {
            top = Math.max(8, rect.top - menu.offsetHeight - 4);
        }

        menu.style.top = Math.round(top) + 'px';
        menu.style.left = Math.round(left) + 'px';
        menu.style.right = 'auto';

        toggle.setAttribute('aria-expanded', 'true');
        toggle.classList.add('is-open');
        openMenuEl = menu;
        openToggleEl = toggle;
    }

    $(function () {
        var $wrap = $('.fv-customer-last-activity');
        if (!$wrap.length) {
            return;
        }

        $(document).on('click', '.js-fv-catalog-actions-toggle', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var toggle = this;
            var menu = toggle.parentElement
                ? toggle.parentElement.querySelector('.fv-catalog-row-actions__menu')
                : null;
            if (!menu && toggle.__fvBoundMenu) {
                menu = toggle.__fvBoundMenu;
            }
            if (!menu) {
                return;
            }
            toggle.__fvBoundMenu = menu;
            if (openToggleEl === toggle && openMenuEl === menu && !menu.hidden) {
                closeCatalogActionsMenu();
                return;
            }
            openCatalogActionsMenu(toggle, menu);
        });

        $(document).on('click', '.fv-catalog-row-actions__menu', function (e) {
            e.stopPropagation();
        });

        $(document).on('click', function () {
            closeCatalogActionsMenu();
        });

        $(window).on('scroll resize', function () {
            closeCatalogActionsMenu();
        });

        $(document).on('click', '.js-fv-copy-catalog-link', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var link = String($(this).attr('data-link') || '');
            closeCatalogActionsMenu();
            if (!link) {
                return;
            }
            var done = function () {
                swalFire({
                    icon: 'success',
                    title: meta('data-msg-copied', 'OK'),
                    confirmButtonText: meta('data-msg-accept', 'OK'),
                });
            };
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(link).then(done).catch(function () {
                    var aux = document.createElement('input');
                    aux.value = link;
                    document.body.appendChild(aux);
                    aux.select();
                    document.execCommand('copy');
                    document.body.removeChild(aux);
                    done();
                });
                return;
            }
            var aux = document.createElement('input');
            aux.value = link;
            document.body.appendChild(aux);
            aux.select();
            document.execCommand('copy');
            document.body.removeChild(aux);
            done();
        });

        $(document).on('click', '.js-fv-show-catalog-link', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var link = String($(this).attr('data-link') || '');
            closeCatalogActionsMenu();
            $('#fv-customer-showlink-text').text(link);
            $('#fv-customer-showlink-modal').modal('show');
        });

        $(document).on('click', '.js-fv-expire-catalog-link', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var cartId = parseInt($(this).attr('data-cart-id') || '0', 10) || 0;
            var companyId = parseInt(meta('data-company-id', '0'), 10) || 0;
            closeCatalogActionsMenu();
            if (cartId < 1 || companyId < 1) {
                return;
            }

            swalFire({
                icon: 'warning',
                title: meta('data-msg-expire-confirm', 'Are you sure?'),
                showCancelButton: true,
                confirmButtonText: meta('data-msg-accept', 'OK'),
                cancelButtonText: meta('data-msg-cancel', 'Cancel'),
                confirmButtonColor: '#dc2626',
                reverseButtons: true,
            }).then(function (result) {
                if (!result || !result.isConfirmed) {
                    return;
                }
                var base = meta('data-api-base', '/api').replace(/\/$/, '');
                var token = meta('data-api-token', '');
                var headers = {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                };
                if (token) {
                    headers.Authorization = 'Bearer ' + token;
                }

                $.ajax({
                    url: base + '/v2/catalogcart/company/' + companyId + '/cart/' + cartId + '/expire',
                    method: 'POST',
                    headers: headers,
                })
                    .done(function () {
                        window.location.reload();
                    })
                    .fail(function () {
                        swalFire({
                            icon: 'error',
                            title: meta('data-msg-error', 'Error'),
                            confirmButtonText: meta('data-msg-accept', 'OK'),
                        });
                    });
            });
        });
    });
})(jQuery);
