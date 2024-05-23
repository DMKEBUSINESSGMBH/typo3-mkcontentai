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
        const action = document.getElementById('operationName').value;
        const client = document.getElementById("clientApi").value;
        const submitButton = document.querySelector('input[type="submit"]');
        let customHeight = 256;
        let customWidth = 256;
        let dragModeValue = 'none';
        let viewModeValue = 1;
        let cropBoxResizable = false;
        let aspectRatio = 1;
        let scalable = false;
        let autoCropArea = 0;
        let zoomable = false;
        submitButton.disabled = false;


        if (client === "StabilityAiClient" && action === 'extend') {
            customHeight = image.height;
            customWidth = image.width;
            dragModeValue = 'crop';
            cropBoxResizable = true;
            aspectRatio = NaN;
        }

        if (client === "StabilityAiClient" && action === 'prepareImageToVideo') {
            aspectRatio = NaN;
            customWidth = document.querySelector('input[name="size"]').getAttribute('data-width') ?? 768;
            customHeight = document.querySelector('input[name="size"]').getAttribute('data-height') ?? 768;

            const inputs = document.querySelectorAll('input[name="size"]');
            let disabledInputsCount = 0;

            for (let i = 0; element = inputs[i]; i++) {
                element.disabled = false;

                if (false === validateImageDimensionsData(image.width, image.height, element.getAttribute('data-width'), element.getAttribute('data-height'))) {
                    element.disabled = true;
                    customWidth = 0;
                    customHeight = 0;
                    disabledInputsCount++;
                }
            }

            if (inputs.length === disabledInputsCount) {
                submitButton.disabled = true;
            }

            customWidth = parseInt(customWidth, 10);
            customHeight = parseInt(customHeight, 10);
        }

        const cropper = new Cropper(image, {
            aspectRatio: aspectRatio,
            cropBoxResizable: cropBoxResizable,
            dragMode: dragModeValue,
            zoomable: zoomable,
            scalable: scalable,
            autoCropArea: autoCropArea,
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
                if (document.querySelector('input[name="size"]')) {
                    document.querySelector('input[name="size"]').click();
                }
            }
        });

        $('.crop-form').on('submit', function (event) {
            event.preventDefault();
            let minHeight;
            let minWidth;

            if (document.querySelector('input[name="size"]:checked')) {
                minWidth = document.querySelector('input[name="size"]:checked').getAttribute('data-width') ?? 256;
                minHeight = document.querySelector('input[name="size"]:checked').getAttribute('data-height') ?? 256;
                minWidth = parseInt(minWidth, 10);
                minHeight = parseInt(minHeight, 10);
            }
            let canvas = cropper.getCroppedCanvas({
                    minWidth: minWidth,
                    minHeight: minHeight
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

        function validateImageDimensionsData(imageWidth, imageHeight, inputWidth, inputHeight) {
            if (imageWidth < inputWidth || imageHeight < inputHeight) {
                return false;
            }
        }
    });
});
