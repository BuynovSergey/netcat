/**
 *
 * Загрузчик файлов (для полей одиночной и множественной загрузки).
 *
 * Требует для работы наличия особой разметки — см. nc_file_field(),
 * nc_multifield_template::get_form().
 *
 *
 * При выборе одновременно нескольких файлов и дальнейшем их удалении файлы
 * всё равно передаются на сервер, но игнорируются там (это сделано для упрощения
 * сопоставления дополнительных данных о файлах с загруженными файлами).
 *
 * Для каждого файла в множественной загрузке передаются параметры (F = имя поля):
 *  — $_FILES[f_F_file][]          загружаемые файлы
 *  — multifile_id[F][]            ID файла в Multifield (для новых файлов = '')
 *  — multifile_upload_index[F][]  порядковый номер файла в $_FILES[f_F_file][] (для старых файлов = -1)
 *  — multifile_name[F][]          поле 'Name' (описание/пользовательское название) для файла (если включено в настройках)
 *  — multifile_delete[F][]        1, если файл нужно удалить (как для новых, так и для старых файлов)
 *
 *  Порядок сортировки файлов определяется последовательностью multifile_*.
 *
 *  Дополнительно для полей множественной загрузки передаётся параметр multifile_js[F] = 1.
 *
 */
(function($) {

    if (!$) {
        return;
    }

    // Максимальный размер изображений, которые отображаются при предпросмотре загружаемых файлов:
    var MAX_IMAGE_PREVIEW_SIZE = 5 * 1024 * 1024;

    var isFileReaderAvailable = 'FileReader' in window; // IE9 и младше

    /**
     * Добавляет один файл из <input type="file">
     * @param input
     */
    function processSingleFileInputChange(input) {
        if (!input.value) {
            return;
        }

        addFileBlock(input, 0)
            .hide().slideDown(100)
            .find('.nc-upload-file-custom-name input:text').focus();
    }

    /**
     * Обрабатывает добавление файлов в поле множественной загрузки файлов
     * @param input
     */
    function processMultipleFileInputChange(input) {
        var $input = $(input),
            $uploadContainer = getUploadContainer($input),
            maxFiles = $uploadContainer.data('maxFiles'),
            fieldName = $uploadContainer.data('fieldName'),
            getInputName = function(n) { return 'multifile_' + n + '[' + fieldName + '][]'; },
            fileList = getFileList(input);

        // создание нового <input> на замену текущему
        $input.hide().clone().val('').appendTo($uploadContainer).show();

        // перенос <input> с выбранным файлом (файлами) в скрытый блок
        $input.removeClass('nc-upload-input').appendTo($uploadContainer.find('.nc-upload-new-files'));

        $.each(fileList, function(i) {
            var uploadIndex = getNextUploadIndex($uploadContainer);

            // если выбран только один файл, значение можно будет легко сбросить
            // при удалении файла в onFileRemoveClick()
            if (fileList.length == 1) {
                $input.addClass('nc-upload-file-index--' + uploadIndex)
            }

            // добавление блока для файла
            var $fileBlock = addFileBlock(input, i)
                .hide()
                .data('uploadIndex', uploadIndex)
                // добавление дополнительных полей для множественной загрузки
                .append(
                    makeHiddenInput(getInputName('id')),
                    makeHiddenInput(getInputName('upload_index'), uploadIndex),
                    makeHiddenInput(getInputName('delete'), 0).addClass('nc-upload-file-remove-hidden')
                );

            if ($uploadContainer.data('customName')) {
                // поле для описания файла
                var placeholder = $uploadContainer.data('customNameCaption'),
                    $customNameInput = makeInput('text', getInputName('name'), '', placeholder);
                $('<div class="nc-upload-file-custom-name"/>').append($customNameInput).appendTo($fileBlock);
            }

            // если достигнут лимит файлов, файл должен быть проигнорирован
            if (maxFiles && maxFiles != '0' && countFiles($uploadContainer) >= maxFiles) {
                markFileAsDeleted($fileBlock);
            }
            else {
                $fileBlock.slideDown(100);
            }
        });
    }

    /**
     * Добавляет div для загружаемого файла
     * @param input
     * @param fileIndex
     * @returns jQuery
     */
    function addFileBlock(input, fileIndex) {
        var file;
        if (input.type === 'hidden') {
            var url = input.value,
                fileName = url.split('/').pop();
            file = {
                name: fileName,
                type: fileName.split('.').pop(),
                url: url + '?' + Math.random().toString().substr(2),
                size: 0,
            };
        } else {
            file = getFileList(input)[fileIndex];
        }
        var $fileBlock = $(
                '<div class="nc-upload-file">' +
                '<div class="nc-upload-file-info">' +
                '<span class="nc-upload-file-name">' + file.name + '</span> ' +
                '<a href="#" class="nc-upload-file-remove" tabindex="-1">×</a>' +
                '</div>' +
                '</div>'
            ),
            $uploadContainer = getUploadContainer(input),
            $filesContainer = $uploadContainer.find('.nc-upload-files');

        $fileBlock.appendTo($filesContainer);
        initFileRemoveClick($fileBlock);

        if (isImageFileType(file.type)) {
            if (file.url) {
                addPreviewBlockAndDragIcon($fileBlock, file.type, file.name, file.url, true);
            } else if (isFileReaderAvailable && file.size < MAX_IMAGE_PREVIEW_SIZE) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    addPreviewBlockAndDragIcon($fileBlock, file.type, file.name, e.target.result, true);
                };
                reader.readAsDataURL(file);
            }
        } else {
            addPreviewBlockAndDragIcon($fileBlock, file.type, file.name, null, true);
        }

        // скрытие <input> при достижении ограничения на количество файлов
        checkMaxFileNumber($uploadContainer);

        return $fileBlock;
    }

    /**
     * Добавляет div для отображения картинки или иконки и ручку для перетаскивания
     * для режима без предпросмотра
     * @param $fileBlock
     * @param fileType
     * @param fileName
     * @param fileUrl
     * @param animate
     * @returns jQuery
     */
    function addPreviewBlockAndDragIcon($fileBlock, fileType, fileName, fileUrl, animate) {
        var previewClass = 'nc-upload-file-preview',
            dragClass = 'nc-upload-file-drag-handle',
            $preview = $('<div/>').addClass(previewClass + ' ' + dragClass),
            isImage = fileUrl && isImageFileType(fileType);

        if (isImage && isFileReaderAvailable) {
            $preview.addClass(previewClass + '-image').append('<img src="' + fileUrl + '" />');
            getUploadContainer($fileBlock).addClass('nc-upload-with-preview');
        }
        else {
            var nameLastDot = fileName.lastIndexOf('.');
            $preview.addClass(previewClass + '-other');
            if (nameLastDot > 0) {
                $preview.append(
                    '<span class="nc-upload-file-extension">' + fileName.substr(nameLastDot + 1) + '</span>'
                );
            }
        }

        $fileBlock.prepend(
            '<div class="nc-upload-file-drag-icon ' + dragClass + '"><i class="nc-icon nc--file-text"></i></div>',
            $preview
        );

        if ((isImage || $fileBlock.closest('.nc-upload-with-preview').length) && isFileReaderAvailable) {
            animate ? $preview.slideDown(100) : $preview.show();
        }

        return $preview;
    }

    /**
     * Возвращает true, если строка начинается с image
     * @param fileType
     * @returns Boolean
     */
    function isImageFileType(fileType) {
        return /^(image\/)?(jpe?g|png|gif|bmp|webp|svg([+-]xml)?)$/i.test(fileType);
    }

    /**
     * Получение input.files, а в IE 9 — FileList-подобного массива
     * @param input
     * @returns {*}
     */
    function getFileList(input) {
        if (isFileReaderAvailable) {
            return input.files;
        }

        // IE 9
        return [{
            name: input.value.substr(input.value.lastIndexOf('\\') + 1),
            type: '',
            size: 0
        }];
    }

    /**
     * Возвращает true, если элемент имеет класс nc-upload-multifile
     * @param $uploadContainer
     * @returns Boolean
     */
    function isMultifile($uploadContainer) {
        return $uploadContainer.hasClass('nc-upload-multifile');
    }

    /**
     * Возвращает блок .nc-upload, к которому принадлежит элемент
     */
    function getUploadContainer(element) {
        return $(element).closest('.nc-upload');
    }

    function getServerPathInput(element) {
        return getUploadContainer(element).find('.nc-upload-file-server-path');
    }

    /**
     * Возвращает значение свойства data-upload-index и увеличивает его на 1
     * @param $uploadContainer
     * @returns Number
     */
    function getNextUploadIndex($uploadContainer) {
        var property = 'nextUploadIndex',
            currentValue = $uploadContainer.data(property) || 0;
        $uploadContainer.data(property, currentValue + 1);
        return currentValue;
    }

    /**
     * Возвращает количество файлов в upload-блоке
     * @param $container
     * @returns Number
     */
    function countFiles($container) {
        var filterCallback = function() { return $(this).css('display') !== 'none' };
        return $container.find('.nc-upload-file').filter(filterCallback).length;
    }

    /**
     * Проверяет допустимое количество загружаемых файлов; если количество файлов
     * равно или больше допустимого, прячет кнопку загрузки файла
     * @param $uploadContainer
     */
    function checkMaxFileNumber($uploadContainer) {
        var maxFiles = isMultifile($uploadContainer) ? $uploadContainer.data('maxFiles') : 1;

        if (maxFiles) {
            var $tmpInput = getServerPathInput($uploadContainer),
                $fileInput = $uploadContainer.find('.nc-upload-input'),
                canUploadMoreFiles = countFiles($uploadContainer) < maxFiles;
            if ($tmpInput.length) {
                $fileInput.hide();
                $uploadContainer.find('.nc-upload-hide-if-full').toggle(canUploadMoreFiles);
            } else {
                $fileInput.toggle(canUploadMoreFiles);
            }
        }
    }

    /**
     * Возвращает новый <input type="hidden">
     * @param name
     * @param value
     * @returns {*}
     */
    function makeHiddenInput(name, value) {
        return makeInput('hidden', name, value)
    }

    /**
     * Возвращает новый <input>
     * @param type
     * @param name
     * @param value
     * @param placeholder
     * @returns {*|jQuery|HTMLElement}
     */
    function makeInput(type, name, value, placeholder = '') {
        return $('<input />', {
            type: type,
            name: name,
            value: value === undefined ? '' : value,
            placeholder
        })
    }

    /**
     * Обработка нажатия на «удалить файл»
     * @param e
     * @returns {boolean}
     */
    function onFileRemoveClick(e) {
        e.preventDefault();

        var $fileBlock = $(e.target).closest('.nc-upload-file'),
            $uploadContainer = getUploadContainer($fileBlock),
            $input = $uploadContainer.find('.nc-upload-input'),
            $targetForEvent = $input;

        markFileAsDeleted($fileBlock);

        if (isMultifile($uploadContainer)) {
            // Сбросить значения <input type="file"> при наличии нескольких файлов
            // без смены индексов не получится, так как собьются индексы
            // в multifile_upload_index[F][]...
            // Но можно очистить значение, если файл в поле был только один:
            $uploadContainer.find('.nc-upload-file-index--' + $fileBlock.data('uploadIndex')).val('');
        }
        else {
            // IE ≤ 9: нельзя очистить значение file input ($input.val(''))
            $targetForEvent = $input.clone().val('');
            $input.replaceWith($targetForEvent);
            getServerPathInput($uploadContainer).val('');
        }

        $fileBlock.slideUp(100, function () {
            // update display type (with/without preview)
            var c = 'nc-upload-with-preview';
            if ($uploadContainer.hasClass(c) && $uploadContainer.find('.nc-upload-file:visible .nc-upload-file-preview-image').length == 0) {
                $uploadContainer.removeClass(c);
            }

            // нужно для вызова обработчика nc_fields_form_inherited_value_div()
            var event = $.Event('change');
            event.target = $targetForEvent[0];
            $(document).trigger(event);

            checkMaxFileNumber($uploadContainer);
        });
    }

    /**
     * Отмечает файл как удалённый.
     * @param $fileBlock
     */
    function markFileAsDeleted($fileBlock) {
        $fileBlock.find('.nc-upload-file-remove-hidden').val(1).trigger('change');
    }

    /**
     * Инициализация обработчика нажатия на «удалить файл»
     * @param $container
     */
    function initFileRemoveClick($container) {
        var event = 'click.nc-upload';
        $container.find('.nc-upload-file-remove').off(event).on(event, onFileRemoveClick);
    }

    // -------------------------------------------------------------------------

    $.fn.upload = function() {

        return this.each(function () {
            var $uploadContainer = $(this);

            if ($uploadContainer.hasClass('nc-upload-applied')) { return; }
            $uploadContainer.addClass('nc-upload-applied');

            var multifile = isMultifile($uploadContainer),
                $filesContainer = $uploadContainer.find('.nc-upload-files'),
                $allFiles = $filesContainer.find('.nc-upload-file');

            if ($allFiles.length > 0 && !multifile) {
                $uploadContainer.find('.nc-upload-input').hide();
            }

            // Добавление превьюшек ко всем файлам
            $allFiles.each(function() {
                var $fileBlock = $(this),
                    fileType = $fileBlock.data('type'),
                    a = $fileBlock.find('.nc-upload-file-name'),
                    fileName = a.html(),
                    fileUrl = a.data('preview-url') ? a.data('preview-url') : a.prop('href');
                addPreviewBlockAndDragIcon($fileBlock, fileType, fileName, fileUrl, false);
            });

            // checkMaxFileNumber проверяет видимость элементов, а на момент выполнения
            // this.each() они скорее всего невидимы все
            setTimeout(function() {
                checkMaxFileNumber($uploadContainer);
            }, 10);

            // Обработка изменения input’а
            $uploadContainer.on('change', '.nc-upload-input', function () {
                if (multifile) {
                    processMultipleFileInputChange(this);
                }
                else {
                    processSingleFileInputChange(this);
                }
            });

            // Убираем onclick, добавленный в HTML для случая, когда этот скрипт не инициализирован
            $filesContainer.find('.nc-upload-file-remove').attr('onclick', '');

            // Обработка нажатия на «удалить файл»
            $filesContainer.on('click', '.nc-upload-file-remove', onFileRemoveClick)

            // Изменение поля со строкой с путём файла, загруженного в папку tmp (иконки из библиотеки)
            getServerPathInput($uploadContainer).change(function() {
                addFileBlock(this, 0);
            });

            // Флаг, подсказывающий серверу, что этот скрипт был инициализирован
            if (multifile) {
                $uploadContainer.append(makeHiddenInput('multifile_js[' + $uploadContainer.data('fieldName') + ']', 1));
            }

            // Обработка тащи-и-бросай
            if (multifile) {
                var $draggedFile;

                // Начало перетаскивания
                $filesContainer.on('mousedown', '.nc-upload-file-drag-handle', function (e) {
                    e.preventDefault();
                    $filesContainer.addClass('nc--dragging');

                    $draggedFile = $(this).closest('.nc-upload-file').addClass('nc--dragged');

                    // Окончание перетаскивания
                    $(window).on('mouseup.nc-upload', function () {
                        $filesContainer.removeClass('nc--dragging');
                        $draggedFile.removeClass('nc--dragged');
                        $(window).off('mouseup.nc-upload');
                        $draggedFile = null;
                    });
                });

                // Перетаскивание
                $filesContainer.on('mousemove', '.nc-upload-file', function (e) {
                    var $hoveredFile = $(this),
                        insertBefore;

                    if (!$draggedFile || $hoveredFile.hasClass('nc--dragged')) {
                        return;
                    }

                    // определяем положение для элемента: до или после того, над которым мышь
                    if ($hoveredFile.css('float') == 'none') {  // тащат вертикально
                        var y = (e.pageY - $hoveredFile.offset().top),
                            height = $hoveredFile.height();
                        insertBefore = (y < (height / 2));
                    }
                    else {  // тащат горизонтально
                        var x = (e.pageX - $hoveredFile.offset().left),
                            width = $hoveredFile.width();
                        insertBefore = (x < (width / 2));
                    }

                    if (insertBefore) {
                        $draggedFile.insertBefore($hoveredFile);
                    }
                    else {
                        $draggedFile.insertAfter($hoveredFile);
                    }

                });
            }

        });
    };

    $(document).on('apply-upload', function (e) {
        $('.nc-upload').upload();
    });

})(window.$nc || window.jQuery);
