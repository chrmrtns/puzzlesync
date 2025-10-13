/**
 * PressML Settings Page JavaScript
 *
 * @package PressML
 * @since 1.0.0
 */

jQuery(document).ready(function($) {
    let languageIndex = pressmlSettings.languageCount;

    $('#chrmrtns-pml-add-language').click(function() {
        const newRow = `
            <tr>
                <td>
                    <input type="text" name="languages[${languageIndex}][code]"
                           value="" placeholder="en" maxlength="5" required style="width: 60px;" />
                </td>
                <td>
                    <input type="text" name="languages[${languageIndex}][name]"
                           value="" placeholder="English" required style="width: 100%;" />
                </td>
                <td>
                    <input type="text" name="languages[${languageIndex}][flag]"
                           value="" placeholder="🇺🇸" maxlength="10" required style="width: 60px;" />
                </td>
                <td>
                    <button type="button" class="button chrmrtns-pml-remove-language">
                        ${pressmlSettings.textRemove}
                    </button>
                </td>
            </tr>
        `;
        $('#chrmrtns-pml-languages-table tbody').append(newRow);
        languageIndex++;
        updateRemoveButtons();
    });

    $(document).on('click', '.chrmrtns-pml-remove-language', function() {
        $(this).closest('tr').remove();
        updateRemoveButtons();
    });

    function updateRemoveButtons() {
        const rows = $('#chrmrtns-pml-languages-table tbody tr').length;
        $('.chrmrtns-pml-remove-language').prop('disabled', rows <= 1);
    }
});
