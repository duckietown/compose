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

    $(document).ready(function () {
        $('.dt-form, .robot-settings, .dt-settings').each(function () {
            convertBooleanRadios(this);
        });
    });
})(window, window.jQuery);
