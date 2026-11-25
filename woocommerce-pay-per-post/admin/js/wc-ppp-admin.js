(function ($) {
    'use strict';

    $(function () {

        $(".post-types-button").click(function (e) {
            e.preventDefault();
            $(".current-post-types").toggle("fast");
        });

        $('.wc_pay_per_post_display_on_my_account').click(function(e){
           $('.my-account-shortcode-container').toggle("fast");
        });


        $(".wc_pay_per_post_delete_settings").change(function () {

            if ($(this).prop('checked')) {
                alert('Are you sure you want to do this?  If you deactivate this will delete all settings and records created by this plugin.  You have been warned');
            }

        });

        $(".wc_pay_per_post_display_on_my_account").change(function () {

            if ($(this).prop('checked')) {
                alert('After saving these options, you may have to save your permalinks again if the link on the My Account pages goes to a 404 page.  You can easily do this by going to Settings->Permalinks and just pressing the save button.  ');
            }

        });

        $("#wc_pay_per_post_custom_post_types").select2({
            tokenSeparators: [',', ' '],
            width: 'resolve' // need to override the changed default
        });

        $("#wc_pay_per_post_product_ids").select2({
            tokenSeparators: [',', ' '],
            width: 'resolve' // need to override the changed default
        });

        $("#wc_pay_per_post_woocommerce_membership_ids").select2({
            tokenSeparators: [',', ' '],
            width: 'resolve' // need to override the changed default
        });

        $('[data-wcppp-box]').each(function () {
            postEditorBox($(this));
        });



        // Help Page
        $('#wc-ppp-help-nav-tabs a').click(function (event) {
            event.preventDefault();

            $('#wc-ppp-help-nav-tabs a').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            $('.wc-ppp-help-tab').hide();
            $($(this).attr('href')).show();

        });

    });

    /**
     * The Pay For Post box in the classic editor: show the chosen protection
     * type's settings and the custom message, and keep the summary at the top
     * in step with the fields. Everything is posted whether shown or not, as the
     * tabs it replaced did, so hiding a field never changes what is saved.
     */
    function postEditorBox($box) {
        const text = window.wcPppBox || {};
        const $products = $('#wc_pay_per_post_product_ids');
        const $badge = $box.find('[data-wcppp-badge]');
        const $summary = $box.find('[data-wcppp-summary]');

        function type() {
            return $box.find('input[name="wc_pay_per_post_protection_type"]:checked').val() || 'standard';
        }

        function amount($fields, selector) {
            return $.trim($fields.find(selector).first().val() || '0');
        }

        function unit($fields) {
            return $.trim($fields.find('[data-wcppp-unit] option:selected').first().text());
        }

        function format(template, values) {
            return String(template || '').replace(/%(\d)\$s/g, function (match, index) {
                return values[index - 1];
            });
        }

        function summary() {
            const current = type();
            const $fields = $box.find('[data-wcppp-for="' + current + '"]');

            if (!($products.val() || []).length) {
                return text.unprotected;
            }

            if ('delay' === current && $fields.length) {
                return format(text.delay, [amount($fields, '[data-wcppp-num]'), unit($fields)]);
            }

            if ('page-view' === current && $fields.length) {
                if ($fields.find('[data-wcppp-window]').is(':checked')) {
                    const $window = $fields.find('[data-wcppp-window-fields]');
                    return format(text.viewsWithin, [amount($fields, '[data-wcppp-num]'), amount($window, '[data-wcppp-window-num]'), unit($window)]);
                }
                return format(text.views, [amount($fields, '[data-wcppp-num]')]);
            }

            if ('expire' === current && $fields.length) {
                return format(text.expire, [amount($fields, '[data-wcppp-num]'), unit($fields)]);
            }

            return text.standard;
        }

        function refresh() {
            const current = type();
            const protectedPost = ($products.val() || []).length > 0;

            $box.find('[data-wcppp-for]').each(function () {
                this.hidden = $(this).data('wcpppFor') !== current;
            });
            $box.find('[data-wcppp-remaining]').prop('hidden', 'page-view' !== current && 'expire' !== current);
            $box.find('[data-wcppp-window-fields]').prop('hidden', !$box.find('[data-wcppp-window]').is(':checked'));

            $badge.toggleClass('is-on', protectedPost).text(protectedPost ? $badge.data('on') : $badge.data('off'));
            $summary.text(summary() || '');
        }

        $box.on('change input', 'input, select', refresh);
        $products.on('change', refresh);

        $box.on('change', 'input[name="wc_pay_per_post_paywall_message"]', function () {
            const custom = 'custom' === $box.find('input[name="wc_pay_per_post_paywall_message"]:checked').val();
            $box.find('[data-wcppp-message]').prop('hidden', !custom);
            if (custom) {
                // TinyMCE sizes itself on load; one shown after a hidden load
                // needs a nudge to lay out its toolbar and frame.
                $(window).trigger('resize');
            }
        });

        $box.on('click', '[data-wcppp-insert]', function () {
            const token = $(this).data('wcpppInsert');
            const editor = window.tinymce && window.tinymce.get('wc_pay_per_post_restricted_content_override');

            if (editor && !editor.isHidden()) {
                editor.focus();
                editor.insertContent(token);
                return;
            }

            const field = document.getElementById('wc_pay_per_post_restricted_content_override');
            if (field) {
                const start = field.selectionStart || field.value.length;
                const end = field.selectionEnd || start;
                field.value = field.value.slice(0, start) + token + field.value.slice(end);
                field.focus();
                field.selectionStart = field.selectionEnd = start + token.length;
            }
        });

        refresh();
    }

})(jQuery);
