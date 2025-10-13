/**
 * PressML Meta Box JavaScript
 *
 * @package PressML
 * @since 1.0.0
 */

jQuery(document).ready(function($) {
    $('#chrmrtns_pml_sync_group').on('click', function(e) {
        e.preventDefault();

        var translationGroup = $('#chrmrtns_pml_translation_group').val();
        if (!translationGroup) {
            alert(pressmlMetaBox.alertEnterGroup);
            return;
        }

        var button = $(this);
        button.prop('disabled', true).text(pressmlMetaBox.textSyncing);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'chrmrtns_pml_sync_translation_group',
                translation_group: translationGroup,
                post_id: pressmlMetaBox.postId,
                nonce: pressmlMetaBox.nonce
            },
            success: function(response) {
                if (response.success) {
                    var urlsUpdated = 0;
                    // Loop through all returned URLs and populate corresponding fields
                    $.each(response.data, function(key, url) {
                        if (key.endsWith('_url')) {
                            var langCode = key.replace('_url', '');
                            var fieldId = '#chrmrtns_pml_hreflang_' + langCode;
                            if ($(fieldId).length) {
                                $(fieldId).val(url);
                                urlsUpdated++;
                            }
                        }
                    });

                    if (urlsUpdated > 0) {
                        alert(pressmlMetaBox.textSyncSuccess + ' (' + urlsUpdated + ' ' + pressmlMetaBox.textFieldsUpdated + ')');
                    } else {
                        alert(pressmlMetaBox.alertNoFields);
                    }
                } else {
                    alert(response.data || pressmlMetaBox.alertSyncFailed);
                }
            },
            error: function() {
                alert(pressmlMetaBox.alertAjaxError);
            },
            complete: function() {
                button.prop('disabled', false).text(pressmlMetaBox.textSyncButton);
            }
        });
    });
});
