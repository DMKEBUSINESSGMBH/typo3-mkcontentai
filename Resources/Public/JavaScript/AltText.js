require(['jquery'], function ($) {
    $(document).ready(function () {
        $(document).on('click', '.alt-refresh', function (event) {
            const button = $(this);
            const spinner = button.find('.spinner-border');
            spinner.show();

            $.ajax({
                type: 'POST',
                url: TYPO3.settings.ajaxUrls.alt_text,
                data:
                    {
                        fileUid: $(this).parent().data('uid-local'),
                        systemLanguageUid: $(this).parent().data('sys-language-uid')
                    },
                success: function (response) {
                    require(['TYPO3/CMS/Backend/Notification'], function (Notification) {
                        Notification.success('Alt text has been successfully generated by AI');
                    });

                    // whole form div (palette) where alternative fields are displayed
                    const alternativeDivContainer = button.parent().parent().parent().parent();
                    const alternativeCheckbox = alternativeDivContainer.find('input[type=checkbox]');

                    if (alternativeCheckbox.prop('checked') === false) {
                        alternativeCheckbox.click();
                    }

                    alternativeDivContainer.find('input[type=text]').val(response);
                    const hiddenAlternativeInput = alternativeDivContainer.find('input[type=hidden]')[1];
                    $(hiddenAlternativeInput).val(response);
                },
                error: function (response) {
                    require(['TYPO3/CMS/Backend/Notification'], function (Notification) {
                        let errorMessage = 'Unexpected error occurred, please try refresh text later';

                        if (response.responseText !== '') {
                            errorMessage = response.responseText;
                        }

                        Notification.error(errorMessage);
                    });
                },
                complete: function () {
                    spinner.hide();
                }
            });
            event.stopPropagation();
        });
    });
});
