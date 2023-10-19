define(['jquery', 'cropper'], function ($, Cropper) {
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

        const image = document.getElementById('image');
        const cropper = new Cropper(image, {
            aspectRatio: 1 / 1,
            cropBoxResizable: false,
            dragMode: 'none',
            zoomable: false,
            viewMode: 1,
            data: {
                width: 256,
                height: 256
            },
            crop(event) {
            },
            ready: function () {
                let naturalWidth = this.cropper.getImageData().naturalWidth;
                let naturalHeight = this.cropper.getImageData().naturalHeight;

                this.cropper.setCanvasData({
                    left: 0,
                    top: 0,
                    width: naturalWidth,
                    height: naturalHeight
                });
            }
        });

        $('#extend').on('submit', function(event) {
            event.preventDefault();

            let canvas = cropper.getCroppedCanvas();
            let croppedImageSrc = canvas.toDataURL('image/png');

            document.getElementById('croppedImage').src = croppedImageSrc;
            document.getElementById('CroppedBase64').value = croppedImageSrc;

            this.submit();
        });

        document.getElementById('cropSize').addEventListener('change', function (e) {
            if (e.target && e.target.matches('.form-check-input')) {
                const newWidth = e.target.getAttribute('data-width');
                const newHeight = e.target.getAttribute('data-height');

                if (newWidth && newHeight) {
                    cropper.setCropBoxData({
                        width: parseInt(newWidth, 10),
                        height: parseInt(newHeight, 10)
                    });
                }
            }
        });
    });
});
