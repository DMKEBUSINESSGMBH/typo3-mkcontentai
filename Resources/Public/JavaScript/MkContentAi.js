define(['jquery'], function ($) {
    $(document).ready(function () {
        $("form :submit").not(".noSpinner").click(function () {
            $(this).prop("disabled", true);
            $(this).closest('form').submit();
            $(this).closest('form').html(
                `<i class="fa fa-spinner fa-spin"></i> Loading`
            );
        });
        $(".btn:not(form .btn)").not(".noSpinner").click(function () {
            $(this).prop("disabled", true);
            $(this).html(
                `<i class="fa fa-spinner fa-spin"></i> Loading`
            );
        });
        $("ul li .dropdown-item").click(function () {
            $(this).prop("disabled", true);
            $(this).closest('div').html(
                `<i class="fa fa-spinner fa-spin"></i> Loading`
            );
        });
    });
});
