/**
 * Shared dashboard UI helpers (modern theme).
 * Converts SmartForm boolean checkboxes into On/Off radios inside .dt-form.
 */
(function (window, $) {
    if (!$) return;

    function unwrapToggle($input) {
        if ($input.data('bs.toggle') && typeof $input.bootstrapToggle === 'function') {
            try { $input.bootstrapToggle('destroy'); } catch (e) {}
        }
        var $wrap = $input.closest('div.toggle');
        if ($wrap.length) {
            $wrap.replaceWith($input);
        }
        $input.removeAttr('data-toggle').removeAttr('data-onstyle').removeAttr('data-offstyle')
            .removeAttr('data-class').removeAttr('data-size');
        $input.css('display', 'none');
    }

    function convertBooleanRadios(root) {
        var $root = $(root);
        $root.find('input[type="checkbox"].compose-smart-form-input').each(function () {
            var $input = $(this);
            if ($input.data('robot-settings-radios')) {
                return;
            }
            unwrapToggle($input);

            var id = $input.attr('id') || ('dt-bool-' + Math.random().toString(36).slice(2, 10));
            var groupName = 'dt-settings-bool-' + id;
            var on = !!$input.prop('checked');
            var disabled = !!$input.prop('disabled');
            var disabledAttr = disabled ? ' disabled' : '';

            var $radios = $(
                '<div class="robot-settings-radios" role="radiogroup">' +
                    '<label class="robot-settings-radio">' +
                        '<input type="radio" name="' + groupName + '" value="1"' + (on ? ' checked' : '') + disabledAttr + '>' +
                        '<span>On</span>' +
                    '</label>' +
                    '<label class="robot-settings-radio">' +
                        '<input type="radio" name="' + groupName + '" value="0"' + (!on ? ' checked' : '') + disabledAttr + '>' +
                        '<span>Off</span>' +
                    '</label>' +
                '</div>'
            );

            $radios.find('input[type="radio"]').on('change', function () {
                $input.prop('checked', this.value === '1').trigger('change');
            });

            $input.data('robot-settings-radios', true);
            $input.after($radios);
        });
    }

    window.DuckietownUI = window.DuckietownUI || {};
    window.DuckietownUI.convertBooleanRadios = convertBooleanRadios;

    var THEME_KEY = 'dt-theme';
    function themeCurrent() {
        return document.documentElement.getAttribute('data-dt-theme') === 'dark' ? 'dark' : 'light';
    }
    function themeToken(name, fallback) {
        var value = '';
        try {
            value = (window.getComputedStyle(document.documentElement).getPropertyValue(name) || '').trim();
        } catch (err) {}
        return value || fallback;
    }
    function paintLuminance(color) {
        if (!color || typeof color !== 'string') return null;
        var s = color.trim().toLowerCase();
        if (s === 'white') return 255;
        var hex = s.match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/);
        if (hex) {
            var h = hex[1];
            if (h.length === 3) h = h[0] + h[0] + h[1] + h[1] + h[2] + h[2];
            return 0.299 * parseInt(h.slice(0, 2), 16) + 0.587 * parseInt(h.slice(2, 4), 16) + 0.114 * parseInt(h.slice(4, 6), 16);
        }
        var rgb = s.match(/^rgba?\(\s*([0-9.]+)\s*,\s*([0-9.]+)\s*,\s*([0-9.]+)/);
        if (rgb) {
            return 0.299 * Number(rgb[1]) + 0.587 * Number(rgb[2]) + 0.114 * Number(rgb[3]);
        }
        return null;
    }
    function isLeftoverTrack(color) {
        var lum = paintLuminance(color);
        if (lum === null) return false;
        if (lum > 190) return true;
        var s = color.trim().toLowerCase();
        return s === '#1c2333' || s === 'rgb(28, 35, 51)' || s === 'rgba(28, 35, 51, 1)';
    }
    function recolorPaints(value, replacement) {
        if (Array.isArray(value)) {
            return value.map(function (item) { return recolorPaints(item, replacement); });
        }
        return isLeftoverTrack(value) ? replacement : value;
    }
    function themeCharts(theme) {
        if (!window.Chart || !Chart.defaults || !Chart.defaults.global) return;
        var muted = themeToken('--r-muted', theme === 'dark' ? '#c5cddc' : '#6b7280');
        var card = themeToken('--r-card', theme === 'dark' ? '#232a39' : '#ffffff');
        var track = themeToken('--r-track', theme === 'dark' ? '#1c2333' : '#e8ebf1');
        var grid = theme === 'dark' ? 'rgba(255,255,255,0.12)' : 'rgba(17,24,39,0.08)';
        Chart.defaults.global.defaultFontColor = muted;
        if (Chart.defaults.scale) {
            if (Chart.defaults.scale.ticks) Chart.defaults.scale.ticks.fontColor = muted;
            if (Chart.defaults.scale.scaleLabel) Chart.defaults.scale.scaleLabel.fontColor = muted;
            if (Chart.defaults.scale.gridLines) {
                Chart.defaults.scale.gridLines.color = grid;
                Chart.defaults.scale.gridLines.zeroLineColor = grid;
            }
        }
        if (!Chart.instances) return;
        $.each(Chart.instances, function (_, chart) {
            if (!chart || !chart.options) return;
            var scales = chart.options.scales || {};
            $.each(['xAxes', 'yAxes'], function (_, axisKey) {
                $.each(scales[axisKey] || [], function (__, axis) {
                    if (axis.ticks) axis.ticks.fontColor = muted;
                    if (axis.scaleLabel) axis.scaleLabel.fontColor = muted;
                    if (axis.gridLines) {
                        axis.gridLines.color = grid;
                        axis.gridLines.zeroLineColor = grid;
                    }
                });
            });
            if (chart.options.legend && chart.options.legend.labels) {
                chart.options.legend.labels.fontColor = muted;
            }
            if (chart.options.elements && chart.options.elements.center) {
                var centerColor = chart.options.elements.center.color;
                if (!centerColor || centerColor === '#000' || paintLuminance(centerColor) < 40) {
                    chart.options.elements.center.color = muted;
                }
            }
            $.each((chart.data && chart.data.datasets) || [], function (__, dataset) {
                if (dataset.pointBackgroundColor) {
                    dataset.pointBackgroundColor = card;
                }
                ['backgroundColor', 'hoverBackgroundColor'].forEach(function (key) {
                    if (dataset[key] !== undefined) {
                        dataset[key] = recolorPaints(dataset[key], track);
                    }
                });
            });
            if (typeof chart.update === 'function') chart.update();
        });
    }
    function themeApply(theme) {
        var t = (theme === 'dark') ? 'dark' : 'light';
        document.documentElement.setAttribute('data-dt-theme', t);
        if (document.body) {
            document.body.setAttribute('data-dt-theme', t);
        }
        var btn = document.getElementById('dt-theme-toggle');
        if (btn) {
            var label = (t === 'dark') ? 'Switch to light theme' : 'Switch to dark theme';
            btn.setAttribute('aria-pressed', t === 'dark' ? 'true' : 'false');
            btn.setAttribute('title', label);
            btn.setAttribute('aria-label', label);
        }
        themeCharts(t);
        try {
            document.documentElement.dispatchEvent(new CustomEvent('dt-theme-change', { detail: { theme: t } }));
        } catch (err) {}
    }
    function themeToggle(e) {
        if (e) e.preventDefault();
        var next = themeCurrent() === 'dark' ? 'light' : 'dark';
        try { localStorage.setItem(THEME_KEY, next); } catch (err) {}
        themeApply(next);
        return next;
    }
    window.DuckietownTheme = {
        current: themeCurrent,
        apply: themeApply,
        toggle: themeToggle
    };

    $(document).on('click', '#dt-theme-toggle', themeToggle);

    if (window.Chart && Chart.pluginService && !Chart.__dtThemeHook) {
        Chart.__dtThemeHook = true;
        Chart.pluginService.register({
            afterInit: function () {
                setTimeout(function () {
                    themeCharts(themeCurrent());
                }, 0);
            }
        });
    }

    $(document).ready(function () {
        themeApply(themeCurrent());
        $('.dt-form, .robot-settings, .dt-settings').each(function () {
            convertBooleanRadios(this);
        });
    });
})(window, window.jQuery);
