$nc.fn.addImageEditing = function () {
    return this.each(function () {
        var image_obj = $nc(this);

        if (image_obj.hasClass('editing-applied') || $nc('.nc-navbar .nc-quick-menu li.nc--active').index() != 1) {
            return;
        }

        image_obj.addClass('editing-applied');

        image_obj.wrap('');
        image_obj.css('position', 'relative');
        image_obj.click(function (e) {
            e.preventDefault();
            var d = new Date();

            nc_register_modal_resize_handler();
            nc_remove_content_for_modal();

            var cropArray = [];

            //var cropWidth = parseInt(image_obj.data("width"));
            //var cropHeight = parseInt(image_obj.data("height"));

            var modalWindowSize = {
                width: 730,
                height: 'auto',
                top: 0,
                left: 0
            };


            /*cropSizes = '';
             if(cropWidth && cropHeight){
             cropSizes = cropWidth+"x"+cropHeight;
             cropArray.push(cropSizes);
             }*/
            for (var i in nc_crop_ratio) {
                //if(cropSizes && cropSizes != nc_crop_ratio[i]){
                cropArray.push(nc_crop_ratio[i]);
                //}
            }

            $nc('#actionCropPopup').modal({
                onShow: function (dialog) {
                    var container = dialog.container;
                    if (modalWindowSize) {
                        var currentLeft = parseInt(container.css('left'));
                        var currentWidth = container.width();

                        var currentTop = parseInt(container.css('top'));
                        var currentHeight = container.height();

                        container.css({
                            width: modalWindowSize.width,
                            height: modalWindowSize.height
                        });

                        container.css({
                            left: currentLeft + (currentWidth - container.width()) / 2,
                            top: currentTop + (currentHeight - container.height()) / 2
                        }).addClass('simplemodal-container-fixed-size');
                    }
                    else {
                        container.removeClass('simplemodal-container-fixed-size');
                        $nc(window).resize();
                    }

                    if (image_obj.is("img")) {

                        if (cropArray.length <= 0) {
                            container.find(".ratioContainer").hide();
                        }

                        var source;
                        if (image_obj.hasClass('no-image')) {
                            source = '';
                            container.find('.ratioContainer').hide();
                        }
                        else {
                            source = image_obj.attr('src') + '?' + d.getTime()
                        }

                        imageCrop.init({
                            type: 1,
                            img_obj: image_obj,
                            upload_only: false,
                            source: source,
                            ratio: cropArray, // Массив предустановленных размеров
                            popup: $nc('#actionCropPopup'), // Объект всплывающего окна
                            done: function () {
                                $nc(window).resize();
                            }
                        });
                    }
                    else {

                        container.find('.ratioContainer').hide();

                        imageCrop.init({
                            type: 2,
                            img_obj: image_obj,
                            upload_only: true,
                            source: image_obj.data('filepath') + "?" + d.getTime(),
                            ratio: cropArray, // Массив предустановленных размеров
                            popup: $nc('#actionCropPopup'), // Объект всплывающего окна
                            done: function () {
                                $nc(window).resize();
                            }
                        });
                    }
                },
                closeHTML: "<a class='modalCloseImg' style='top: -60px'></a>",
                onClose: function (e) {
                    $nc.modal.close();
                    $nc(document).unbind('keydown.simplemodal');
                    nc_remove_content_for_modal();
                }
            });
        });
    });
};

$nc(function () {
    if ($nc('.nc-navbar .nc-quick-menu li.nc--active').index() == 1) {
        $nc('body').append('<div id="actionCropPopup" style="display: none"><div style="padding-top: 20px;" class="nc_admin_form_menu"><h2>Редактирование изображения</h2><div class="nc_admin_form_menu_hr"></div></div><div class="container nc_admin_form_body nc-admin"><div class="actionImage imageContainer"></div><div class="ratioContainer">Выберите размер превью картинки для редактирования<div class="actionRatio"></div></div></div><div class="nc_admin_form_buttons"><button class="nc_admin_metro_button_delete nc--left nc-btn nc--red" type="button">Удалить</button><input class="preview" type="file" accept="image/*" data-url="/netcat/admin/crop_image.php" name="new_image" /><button class="nc_admin_metro_button nc-btn nc--blue" type="button">Сохранить</button><button class="nc_admin_metro_button_cancel nc-btn nc--red nc--bordered nc--right" type="button">Отмена</button></div></div>');
    }
    $nc(".cropable").addImageEditing();
});
