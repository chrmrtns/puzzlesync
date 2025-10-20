/**
 * PressML Meta Box JavaScript
 *
 * @package PressML
 * @since 1.0.0
 */

jQuery(document).ready(function($) {
    $('#chrmrtns_puzzlesync_sync_group').on('click', function(e) {
        e.preventDefault();

        var translationGroup = $('#chrmrtns_puzzlesync_translation_group').val();
        if (!translationGroup) {
            alert(puzzlesyncMetaBox.alertEnterGroup);
            return;
        }

        var button = $(this);
        button.prop('disabled', true).text(puzzlesyncMetaBox.textSyncing);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'chrmrtns_puzzlesync_sync_translation_group',
                translation_group: translationGroup,
                post_id: puzzlesyncMetaBox.postId,
                nonce: puzzlesyncMetaBox.nonce
            },
            success: function(response) {
                if (response.success) {
                    var urlsUpdated = 0;
                    // Loop through all returned URLs and populate corresponding fields
                    $.each(response.data, function(key, url) {
                        if (key.endsWith('_url')) {
                            var langCode = key.replace('_url', '');
                            var fieldId = '#chrmrtns_puzzlesync_hreflang_' + langCode;
                            if ($(fieldId).length) {
                                $(fieldId).val(url);
                                urlsUpdated++;
                            }
                        }
                    });

                    if (urlsUpdated > 0) {
                        alert(puzzlesyncMetaBox.textSyncSuccess + ' (' + urlsUpdated + ' ' + puzzlesyncMetaBox.textFieldsUpdated + ')');
                    } else {
                        alert(puzzlesyncMetaBox.alertNoFields);
                    }
                } else {
                    alert(response.data || puzzlesyncMetaBox.alertSyncFailed);
                }
            },
            error: function() {
                alert(puzzlesyncMetaBox.alertAjaxError);
            },
            complete: function() {
                button.prop('disabled', false).text(puzzlesyncMetaBox.textSyncButton);
            }
        });
    });
});
