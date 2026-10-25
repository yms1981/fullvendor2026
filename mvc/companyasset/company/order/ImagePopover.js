/**
 * Popover de imagen ampliada (lista productos / inventario / órdenes).
 * data-img = URL de preview; el <img> interno es solo la miniatura.
 * Espera a que Bootstrap popover esté disponible (se carga en el footer).
 */
(function (window, $) {
    'use strict';

    function resolvePopoverImgSrc(el) {
        var $el = $(el);
        var dataImg = String($el.attr('data-img') || '').trim();
        if (dataImg !== '' && dataImg.indexOf('data:') !== 0) {
            return dataImg;
        }
        var $thumb = $el.find('img').first();
        if ($thumb.length) {
            var liveSrc = String($thumb.attr('src') || '').trim();
            if (liveSrc && liveSrc.indexOf('data:') !== 0) {
                return liveSrc;
            }
        }
        return '';
    }

    function buildPopoverContent(el) {
        var img = resolvePopoverImgSrc(el);
        if (!img) {
            return '';
        }
        var $wrap = $('<div class="fv-popover-img-preview__inner"></div>');
        $wrap.append(
            $('<img>', {
                src: img,
                alt: '',
                class: 'fv-popover-img-preview__photo',
                draggable: false
            })
        );
        return $wrap;
    }

    function initOrderImagePopovers(root) {
        if (typeof $ === 'undefined' || typeof $.fn === 'undefined' || typeof $.fn.popover !== 'function') {
            return false;
        }
        var $scope = $(root || document);
        var $els = $scope.find('[data-toggle="popover-hover"]');
        if (!$els.length && $scope.is('[data-toggle="popover-hover"]')) {
            $els = $scope;
        }
        if (!$els.length) {
            return true;
        }

        $els.each(function () {
            var $t = $(this);
            if ($t.data('bs.popover')) {
                try {
                    $t.popover('dispose');
                } catch (e) { /* ignore */ }
            }
        });

        var tpl = '<div class="popover fv-popover-img-preview" role="tooltip"><div class="arrow"></div><div class="popover-body"></div></div>';
        $els.popover({
            html: true,
            trigger: 'hover focus',
            placement: 'right',
            container: 'body',
            template: tpl,
            title: '',
            sanitize: false,
            content: function () {
                return buildPopoverContent(this);
            }
        }).on('inserted.bs.popover', function () {
            var inst = $(this).data('bs.popover');
            if (inst && typeof inst.getTipElement === 'function') {
                var tip = inst.getTipElement();
                if (tip) {
                    tip.classList.add('fv-popover-img-preview');
                }
            }
        });
        return true;
    }

    function initWhenReady(attempt) {
        attempt = attempt || 0;
        if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.popover !== 'function') {
            if (attempt < 120) {
                setTimeout(function () {
                    initWhenReady(attempt + 1);
                }, 50);
            }
            return;
        }
        initOrderImagePopovers(document);
    }

    window.fvInitOrderImagePopovers = initOrderImagePopovers;

    if (typeof window.jQuery !== 'undefined') {
        window.jQuery(function () {
            initWhenReady(0);
        });
    } else {
        initWhenReady(0);
    }
})(window, window.jQuery);
