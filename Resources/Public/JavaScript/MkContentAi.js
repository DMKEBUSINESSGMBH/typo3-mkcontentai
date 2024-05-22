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
        const client = document.getElementById("clientApi").value;
        let customHeight = 256;
        let customWidth = 256;
        let dragModeValue = 'none';
        let viewModeValue = 1;
        let cropBoxResizable = false;
        let aspectRatio = 1;

        if (client === "StabilityAiClient") {
            customHeight = image.height;
            customWidth = image.width;
            dragModeValue = 'crop';
            viewModeValue = 1;
            cropBoxResizable = true;
            aspectRatio = NaN;
        }

        const cropper = new Cropper(image, {
            aspectRatio: aspectRatio,
            cropBoxResizable: cropBoxResizable,
            dragMode: dragModeValue,

            zoomable: false,
            viewMode: viewModeValue,
            data: {
                width: customWidth,
                height: customHeight
            },
            crop(event) {
            },
            ready: function () {
                let naturalWidth = this.cropper.getImageData().naturalWidth;
                let naturalHeight = this.cropper.getImageData().naturalHeight;

                if (naturalHeight >= 256 && naturalWidth >= 256) {
                    this.cropper.setCanvasData({
                        left: 0,
                        top: 0,
                        width: naturalWidth,
                        height: naturalHeight
                    });
                }
            }
        });
        $('#extend').on('submit', function(event) {
            event.preventDefault();
            const client = document.getElementById("clientApi").value;
            let minWidthAndHeight;

            if (client === "StabilityAiClient") {
                minWidthAndHeight = image.height;
            } else {
                minWidthAndHeight = document.querySelector('input[name="size"]:checked').getAttribute('data-width') ?? 256;
            }
            minWidthAndHeight = parseInt(minWidthAndHeight, 10);
            let canvas = cropper.getCroppedCanvas({
                minWidth: minWidthAndHeight,
                minHeight: minWidthAndHeight
            }
            );
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
