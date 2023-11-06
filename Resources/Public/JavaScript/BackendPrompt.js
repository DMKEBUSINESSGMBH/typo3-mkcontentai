require(['jquery', 'TYPO3/CMS/Backend/Utility/MessageUtility'], function($, BackendUtility) {
    $(document).ready(function() {

        let box = `<div class="form-section form-control-wrap">
                       <div class="form-control-wrap">
                           <label>Prompt for image generation</label>
                           <input type="text" id="image-input" class="form-control"/>
                       </div>
                       <button type="button" id="generate-image" class="btn btn-default">
                              <span class="spinner-border spinner-border-sm" style="display: none"></span>
                              Generate images
                        </button>
                       <div id="image-container" class="form-control-wrap flex"></div>
                   </div>`;

        $('#prompt').on('click', function() {
            let $button = $(this);
            $button.after(box);
        });

        $(document).on('click', '#generate-image', (e) => {
          let inputValue = $('#image-input').val();
          let imageContainer = $('#image-container');
          let spinner = $(e.currentTarget).find('.spinner-border');
          spinner.show();

            $.ajax({
                type: 'POST',
                url: TYPO3.settings.ajaxUrls.image_prompt,
                data: {
                    promptText: inputValue
                },
                success: function(response) {
                    require(['TYPO3/CMS/Backend/Notification'], function(Notification) {
                        Notification.success('Image was generated via ', response.name);
                    });
                    var images = response.images;
                    displayImages(images,imageContainer);
                    spinner.hide();
                },
                error: function(xhr, status, error) {
                    spinner.hide();
                    require(['TYPO3/CMS/Backend/Notification'], function(Notification) {
                        Notification.error('Error', xhr.responseJSON.error);
                    });
                }
            });
        });

        $(document).on('click', '.save-image', (e) => {
          let imageSrc = $(e.currentTarget).data('src');
          let spinner = $(e.currentTarget).find('.spinner-border');
          spinner.show();

          $.ajax({
              url: TYPO3.settings.ajaxUrls.blob_image,
              type: 'POST',
              data: {
                  imageUrl: imageSrc
              },
              success: function(response) {
                  var byteCharacters = atob(response);

                  var byteArrays = [];
                  for (var i = 0; i < byteCharacters.length; i++) {
                      byteArrays.push(byteCharacters.charCodeAt(i));
                  }

                  var byteArray = new Uint8Array(byteArrays);

                  var blob = new Blob([byteArray], { type: 'image/png' });
                  submitForm(blob);
                  spinner.hide();
              },
              error: function(xhr, status, error) {
                  require(['TYPO3/CMS/Backend/Notification'], function(Notification) {
                      Notification.error('Something wrong', '');
                  });
                  spinner.hide();
              }
          });
        })

        function displayImages(images,$imageContainer) {

            $imageContainer.empty();

            images.forEach(function(image, index) {
                var src;
                if (image.base64) {
                    src = 'data:image/png;base64,' + image.base64;
                } else {
                    src = image.url;
                }
                var imageUrl = image.url;
                let imageBox = `<div class="form-control-wrap">
                                  <img src="${src}" width="256"/>
                                  <div class="form-control-wrap">
                                    <button type="button" class="btn btn-default save-image" data-src="${imageUrl}">
                                           <span class="spinner-border spinner-border-sm" style="display: none"></span>
                                           Save file and relation
                                    </button>
                                  </div>
                                </div>`;

                $imageContainer.append(imageBox);
            });
        }

        function submitForm(response) {
            var formData = new FormData();
            formData.append('data[upload][1][target]', '1:/user_upload/');
            formData.append('data[upload][1][data]', '1');
            formData.append('overwriteExistingFiles', 'rename');
            formData.append('redirect', '');
            formData.append('upload_1', response, Date.now() + '.png');

            $.ajax({
                type: 'POST',
                url: TYPO3.settings.ajaxUrls.file_process,
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    const i = {
                        actionName: "typo3:foreignRelation:insert",
                        objectGroup: 'data-1-tt_content-1-image-sys_file_reference',
                        table: "sys_file",
                        uid: response.upload[0].uid
                    };
                    BackendUtility.MessageUtility.send(i)
                    require(['TYPO3/CMS/Backend/Notification'], function(Notification) {
                        Notification.success('AI generated image saved', 'Image and relation has been saved.');
                    });
                },
                error: function(xhr, status, error) {
                    require(['TYPO3/CMS/Backend/Notification'], function(Notification) {
                        Notification.error('Something wrong', '');
                    });
                }
            });
        }
    });
});
