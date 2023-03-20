define(['jquery'], function ($) {
    $(document).ready(function () {
        $("form :submit").click(function () {
            $(this).prop("disabled", true);
            $(this).closest('form').submit();
            $(this).closest('form').html(
                `<i class="fa fa-spinner fa-spin"></i> Loading`
            );
        });
        $(".btn:not(form .btn)").click(function () {
            $(this).prop("disabled", true);
            $(this).html(
                `<i class="fa fa-spinner fa-spin"></i> Loading`
            );
        });
    });
});
