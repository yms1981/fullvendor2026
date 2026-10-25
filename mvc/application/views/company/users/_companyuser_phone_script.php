<?php
/**
 * Máscaras y prefijo +XX según país de la compañía (misma idea que edición de cliente).
 *
 * @var array $companyPhoneCountryMeta id, sortname, phonecode, phonemask, name
 * @var string $companyUserCountryFlagsUrl
 */
$pm = $companyPhoneCountryMeta ?? [
    'id' => '',
    'sortname' => '',
    'phonecode' => 0,
    'phonemask' => '',
    'name' => '',
];
$flagsUrl = $companyUserCountryFlagsUrl ?? (rtrim((string) base_url(), '/') . '/images/flags/');
$optId = (int) ($pm['id'] ?? 0);
$optSort = htmlspecialchars((string) ($pm['sortname'] ?? ''), ENT_QUOTES, 'UTF-8');
$optPc = (int) ($pm['phonecode'] ?? 0);
$optMask = htmlspecialchars((string) ($pm['phonemask'] ?? ''), ENT_QUOTES, 'UTF-8');
$optName = htmlspecialchars((string) ($pm['name'] ?? ''), ENT_QUOTES, 'UTF-8');
?>
<select id="company_user_phone_country_select" class="d-none" aria-hidden="true" tabindex="-1">
    <?php if ($optId > 0): ?>
        <option value="<?= $optId ?>" selected
                data-sortname="<?= $optSort ?>"
                data-phonecode="<?= $optPc ?>"
                data-phonemask="<?= $optMask ?>"><?= $optName ?></option>
    <?php else: ?>
        <option value="" selected data-sortname="" data-phonecode="0" data-phonemask=""></option>
    <?php endif; ?>
</select>
<script>
(function() {
    function waitForJQuery(cb) {
        if (typeof jQuery !== 'undefined') {
            cb(jQuery);
            return;
        }
        var n = 0;
        var t = setInterval(function() {
            if (typeof jQuery !== 'undefined') {
                clearInterval(t);
                cb(jQuery);
            } else if (++n > 80) {
                clearInterval(t);
            }
        }, 50);
    }
    waitForJQuery(function($) {
        $(function() {
            var countryFlagsBaseUrl = <?= json_encode($flagsUrl) ?>;

            function countryFlagHtml(sortname) {
                var sn = String(sortname || '').toLowerCase().replace(/[^a-z-]/g, '');
                if (sn.length < 2) return '';
                var src = countryFlagsBaseUrl + sn + '.png';
                return '<img src="' + src.replace(/&/g, '&amp;').replace(/"/g, '&quot;') + '" alt="" style="display:inline-block;width:20px;height:15px;object-fit:cover;vertical-align:middle;border:1px solid #ddd;border-radius:2px;margin-right:8px" onerror="this.style.display=\'none\'" />';
            }

            function updateCompanyUserPhonePrefix() {
                var cid = $('#company_user_phone_country_select').val();
                var $opt = cid ? $('#company_user_phone_country_select option[value="' + cid + '"]') : $();
                var html;
                if (!$opt.length) {
                    html = '<span class="text-muted small">—</span>';
                } else {
                    var sortname = String($opt.data('sortname') || '').toLowerCase();
                    var pc = parseInt($opt.data('phonecode'), 10);
                    if (isNaN(pc)) pc = 0;
                    var flag = countryFlagHtml(sortname);
                    html = flag + '<span class="ml-1 font-weight-bold">+' + pc + '</span>';
                }
                $('.companyuser-tel-prefix').html(html);
            }

            function updateCompanyUserPhoneInputMasks() {
                if (typeof Inputmask === 'undefined') {
                    return false;
                }
                var cid = $('#company_user_phone_country_select').val();
                var $opt = cid ? $('#company_user_phone_country_select option[value="' + cid + '"]') : $();
                var maskPh = '';
                if ($opt.length) {
                    maskPh = ($opt.attr('data-phonemask') || '').trim();
                    if (!maskPh) {
                        var pcMask = parseInt($opt.attr('data-phonecode'), 10);
                        if (!isNaN(pcMask) && pcMask === 1) {
                            maskPh = '(###) ###-####';
                        }
                    }
                }
                var els = document.querySelectorAll('.companyuser-tel-input');
                if (!els.length) {
                    return true;
                }
                var rawVals = [];
                for (var i = 0; i < els.length; i++) {
                    rawVals.push((els[i].value || '').replace(/\D/g, ''));
                }
                for (var j = 0; j < els.length; j++) {
                    try { Inputmask.remove(els[j]); } catch (e) {}
                }
                if (!maskPh) {
                    for (var k = 0; k < els.length; k++) {
                        els[k].value = rawVals[k] || '';
                    }
                    return true;
                }
                var imMask = maskPh.replace(/#/g, '9');
                var opts = {
                    mask: imMask,
                    placeholder: '_',
                    showMaskOnHover: false,
                    autoUnmask: true,
                    clearIncomplete: false
                };
                for (var m = 0; m < els.length; m++) {
                    new Inputmask(opts).mask(els[m]);
                    if (els[m].inputmask && typeof els[m].inputmask.setValue === 'function') {
                        els[m].inputmask.setValue(rawVals[m] || '');
                    } else {
                        $(els[m]).val(rawVals[m] || '').trigger('input');
                    }
                }
                return true;
            }

            function runPhoneMasks() {
                updateCompanyUserPhonePrefix();
                if (!updateCompanyUserPhoneInputMasks()) {
                    var a = 0, mx = 60;
                    var iv = setInterval(function() {
                        if (updateCompanyUserPhoneInputMasks() || ++a >= mx) {
                            clearInterval(iv);
                        }
                    }, 100);
                }
            }

            runPhoneMasks();
        });
    });
})();
</script>
