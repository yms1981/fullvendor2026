(function (window) {
    'use strict';

    var els = null;
    var stepItems = {};
    var groupSep = ',';

    function ensureElements() {
        if (els) {
            return els;
        }

        var root = document.createElement('div');
        root.id = 'fv-import-progress';
        root.className = 'fv-import-progress';
        root.setAttribute('aria-hidden', 'true');
        root.setAttribute('role', 'dialog');
        root.setAttribute('aria-modal', 'true');
        root.innerHTML =
            '<div class="fv-import-progress__backdrop"></div>' +
            '<div class="fv-import-progress__panel">' +
                '<div class="fv-import-progress__icon" aria-hidden="true"><i class="mdi mdi-file-import"></i></div>' +
                '<h3 class="fv-import-progress__title"></h3>' +
                '<div class="fv-import-progress__bar-track">' +
                    '<div class="fv-import-progress__bar"></div>' +
                '</div>' +
                '<p class="fv-import-progress__percent">0%</p>' +
                '<p class="fv-import-progress__step"></p>' +
                '<p class="fv-import-progress__detail"></p>' +
                '<ul class="fv-import-progress__steps"></ul>' +
            '</div>';

        document.body.appendChild(root);

        els = {
            root: root,
            title: root.querySelector('.fv-import-progress__title'),
            bar: root.querySelector('.fv-import-progress__bar'),
            percent: root.querySelector('.fv-import-progress__percent'),
            step: root.querySelector('.fv-import-progress__step'),
            detail: root.querySelector('.fv-import-progress__detail'),
            stepsList: root.querySelector('.fv-import-progress__steps')
        };

        return els;
    }

    function formatCount(value) {
        var n = parseInt(value, 10) || 0;
        var sep = groupSep || ',';
        return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, sep);
    }

    function replaceTokens(template, data) {
        if (!template) {
            return '';
        }
        return String(template).replace(/\{(\w+)\}/g, function (_, key) {
            if (!data || data[key] === undefined || data[key] === null) {
                return '';
            }
            return String(data[key]);
        });
    }

    function renderSteps(steps) {
        var e = ensureElements();
        e.stepsList.innerHTML = '';
        stepItems = {};

        (steps || []).forEach(function (step) {
            if (!step || !step.key) {
                return;
            }
            var li = document.createElement('li');
            li.className = 'fv-import-progress__step-item is-pending';
            li.setAttribute('data-step', step.key);
            li.innerHTML =
                '<span class="fv-import-progress__step-marker" aria-hidden="true"></span>' +
                '<span class="fv-import-progress__step-label"></span>';
            li.querySelector('.fv-import-progress__step-label').textContent = step.label || step.key;
            e.stepsList.appendChild(li);
            stepItems[step.key] = li;
        });
    }

    window.FvImportProgress = {
        configure: function (options) {
            options = options || {};
            if (options.groupSep) {
                groupSep = options.groupSep;
            }
        },

        show: function (options) {
            options = options || {};
            var e = ensureElements();

            if (options.groupSep) {
                groupSep = options.groupSep;
            }

            e.title.textContent = options.title || '';
            renderSteps(options.steps || []);
            e.step.textContent = '';
            e.detail.textContent = '';
            this.setProgress(0, '');

            e.root.classList.add('is-visible');
            e.root.setAttribute('aria-hidden', 'false');
        },

        hide: function () {
            if (!els) {
                return;
            }
            els.root.classList.remove('is-visible');
            els.root.setAttribute('aria-hidden', 'true');
        },

        setProgress: function (percent, detail, stepLabel) {
            var e = ensureElements();
            var safe = Math.max(0, Math.min(100, parseInt(percent, 10) || 0));

            e.bar.style.width = safe + '%';
            e.percent.textContent = safe + '%';

            if (stepLabel !== undefined) {
                e.step.textContent = stepLabel || '';
            }
            if (detail !== undefined) {
                e.detail.textContent = detail || '';
            }
        },

        setStep: function (key, status) {
            var item = stepItems[key];
            if (!item) {
                return;
            }
            item.classList.remove('is-pending', 'is-active', 'is-done');
            if (status === 'active') {
                item.classList.add('is-active');
            } else if (status === 'done') {
                item.classList.add('is-done');
            } else {
                item.classList.add('is-pending');
            }
        },

        formatCount: formatCount,

        replaceTokens: replaceTokens,

        runBatches: function (config) {
            config = config || {};
            var items = config.items || [];
            var batchSize = Math.max(1, parseInt(config.batchSize, 10) || 5);
            var total = items.length;
            var offset = 0;

            function next() {
                if (offset >= total) {
                    if (typeof config.onComplete === 'function') {
                        config.onComplete();
                    }
                    return;
                }

                var chunk = items.slice(offset, offset + batchSize);
                offset += chunk.length;
                var pct = total > 0 ? Math.min(95, Math.round((offset / total) * 95)) : 0;

                if (typeof config.onProgress === 'function') {
                    config.onProgress(pct, offset, total);
                }

                Promise.resolve(config.processBatch(chunk, offset, total))
                    .then(function () {
                        next();
                    })
                    .catch(function (error) {
                        if (typeof config.onError === 'function') {
                            config.onError(error);
                        }
                    });
            }

            next();
        }
    };
})(window);
