<?php if (!class_exists('nc_core')) { die; } ?>

 <div class="nc-modal-dialog" data-width="600" data-height="auto">
    <div class="nc-modal-dialog-header">
        <h2><?= NETCAT_MODULE_LANDING_SAVE_PRESET_SETTINGS_HEADER ?></h2>
    </div>
    <div class="nc-modal-dialog-body">
        <form class="nc-form nc--vertical" action="<?= $current_url ?>" method="POST">
            <input type="hidden" name="action" value="save_preset_settings">
            <input type="hidden" name="landing_page_id" value="<?= $landing_page_id ?>">

            <input type="hidden" name="screenshot" value="">
            <input type="hidden" name="screenshot_thumbnail" value="">

            <p><?= NETCAT_MODULE_LANDING_SAVE_PRESET_SETTINGS_DESCRIPTION ?></p>
            <div class="nc-padding-10"></div>
            <div class="nc-form-row">
                <label><?= NETCAT_MODULE_LANDING_SAVE_PRESET_SETTINGS_NAME_LABEL ?>:</label>
                <input type="text" name="name" size="50" maxlength="255" style="width:99%">
            </div>
            <div class="nc-form-row">
                <label><?= NETCAT_MODULE_LANDING_SAVE_PRESET_SETTINGS_DESCRIPTION_LABEL ?>:</label>
                <textarea name="description" style="width:99%" rows="3"></textarea>
            </div>
        </form>
    </div>
    <div class="nc-modal-dialog-footer">
        <button class="nc-landing-save-preset-dialog-submit-button nc--blue"><?= NETCAT_REMIND_SAVE_SAVE ?></button>
        <button data-action="close"><?= CONTROL_BUTTON_CANCEL ?></button>
    </div>

    <script>
        (function() {
            var dialog = nc.ui.modal_dialog.get_current_dialog(),
                screenshot_input = dialog.find('input[name=screenshot]'),
                screenshot_thumbnail_input = dialog.find('input[name=screenshot_thumbnail]');

            // кнопка отправки формы
            function submit_when_screenshot_is_ready() {
                if (screenshot_input.val().length) {
                    dialog.submit_form();
                }
                else {
                    setTimeout(submit_when_screenshot_is_ready, 50);
                }
            }

            dialog.find('.nc-landing-save-preset-dialog-submit-button').click(function() {
                $nc(this).addClass('nc--loading').prop('disabled', true);
                submit_when_screenshot_is_ready();
            });

            // действие при получении ответа после отправки формы
            dialog.set_option('on_submit_response', function() {
                dialog.close();
            });

            /**
             * Создание скриншотов
             */
            var url_prefix = location.protocol + '//' + location.hostname + (location.port ? ':' + location.port : ''),
                html2canvas_folder = url_prefix + '<?= nc_module_path('landing') . 'admin/html2canvas/' ?>',
                screenshot_width = 1200,
                screenshot_thumbnail_width = 512,
                screenshot_thumbnail_height = 632,
                iframe_load_timeout = 10000, // сколько ждём события load во фрейме
                iframe_content_timeout = 10000, // сколько ждём загрузки контента в фрейме (определяется по наличию класса tpl-state-loading)
                iframe_content_check_interval = 100, // как часто проверять наличие элементов с классом tpl-state-loading
                iframe = $nc('<iframe>') // iframe для подготовки скриншота
                    .css({
                        position: 'absolute',
                        top: 0,
                        left: -screenshot_width,
                        width: screenshot_width,
                        height: 1000
                    })
                    .appendTo('body')
//                    .attr('src', url_prefix + '<?//= $page_path ?>//')// страница должна быть на текущем домене
                    .on('load', create_screenshot_in_iframe);

            // FF 43.0.2 не загружает контент при присваивании src?! (FF 43.0.1 работает как положено)
            iframe[0].contentWindow.location = '<?= $page_path ?>';

            var iframe_load_timeout_id = setTimeout(on_screenshot_failure, iframe_load_timeout);

            function on_screenshot_failure() {
                screenshot_input.val(0);
                iframe.remove();
            }

            function create_screenshot_in_iframe() {
                function resolve_content_check_promise() {
                    iframe_content_loaded.resolve();
                    clearInterval(iframe_content_check_interval_id);
                }

                function check_if_iframe_has_loading_elements() {
                    if (!$nc('.tpl-state-loading', iframe_document).length) {
                        resolve_content_check_promise();
                    }
                }

                function make_screenshot() {
                    iframe_window.html2canvas(iframe_document.body, {
                        useCORS: true,
                        proxy: html2canvas_folder + 'proxy.php',
                        onrendered: function (canvas) {
                            // make a thumbnail
                            var thumbnail_canvas = document.createElement('canvas');
                            thumbnail_canvas.width = screenshot_thumbnail_width;
                            thumbnail_canvas.height = screenshot_thumbnail_height;
                            thumbnail_canvas.getContext('2d').drawImage(
                                canvas,
                                0, 0, canvas.width, screenshot_thumbnail_height * (screenshot_width / screenshot_thumbnail_width),
                                0, 0, screenshot_thumbnail_width, screenshot_thumbnail_height
                            );
                            // JPEG с приемлемым качеством, генерируемый в браузере, по размеру больше PNG...
                            screenshot_thumbnail_input.val(thumbnail_canvas.toDataURL('image/png'));

                            // save the full screenshot
                            screenshot_input.val(canvas.toDataURL('image/png'));

                            // done with the iframe
                            iframe.remove();
                        }
                    });
                }

                try {
                    var iframe_document = this.contentDocument,
                        iframe_window = this.contentWindow,
                        html2canvas_loaded = iframe_window.nc.load_script(html2canvas_folder + 'html2canvas.min.js', true),
                        iframe_content_loaded = $nc.Deferred(),
                        iframe_content_check_interval_id;

                    clearTimeout(iframe_load_timeout_id);

                    // каждые iframe_content_check_interval мс (но не дольше, чем
                    // iframe_content_timeout мс в сумме) проверяем наличие
                    // элементов .tpl-state-loading
                    iframe_content_check_interval_id = setInterval(check_if_iframe_has_loading_elements, iframe_content_check_interval);
                    setTimeout(resolve_content_check_promise, iframe_content_timeout);
                    check_if_iframe_has_loading_elements();

                    // убираем из документа в фрейме тулбар Неткета
                    $nc('.nc-navbar', iframe_document).remove();
                    $nc('body', iframe_document).css('margin-top', '0');

                    // когда всё готово, делаем скриншотик
                    $nc.when(html2canvas_loaded, iframe_content_loaded).then(make_screenshot);
                }
                catch (e) {
                    on_screenshot_failure();
                    console && console.log(e);
                }
            }

        })()
    </script>

</div>
