<?php
if (!class_exists('nc_core')) {
    die;
}
$core = nc_core::get_object();
/** @var array $libraries */
/** @var string $current_library */
/** @var string $current_icon */
/** @var string $current_color */
?>

<div class='nc-modal-dialog nc-image-dialog' data-confirm-close="no">
    <div class='nc-modal-dialog-header'>
        <h2><?= NETCAT_MODAL_DIALOG_IMAGE_HEADER; ?></h2>
    </div>
    <form action="<?= nc_controller_url('admin.image', 'save'); ?>" method="post" class='nc-modal-dialog-body nc-form'>
        <input type="hidden" name="library" value="">
        <input type="hidden" name="icon" value="">
        <input type="hidden" name="class_id" value="">
        <input type="hidden" name="message_id" value="">
        <input type="hidden" name="field_id" value="">

        <div data-tab-id='tab-edit' data-tab-caption='<?= NETCAT_MODAL_DIALOG_IMAGE_TAB_EDIT_CAPTION; ?>'>
            <div class="nc-image-dialog-panel">
                <div class="nc-image-dialog-panel-side left">
                    <div class="nc-image-dialog-edit-icon"></div>
                </div>

                <div class="nc-image-dialog-panel-side right centerize">
                    <input type="text" name="color" class="nc-image-dialog-edit-icon-colorpicker">
                    <div class="nc-field nc-field-type-string">
                        <input type="text" class="nc-image-dialog-edit-icon-colorpicker-input"
                               value="#000000"
                               placeholder="<?= NETCAT_MODAL_DIALOG_IMAGE_TAB_EDIT_COLORPICKER_INPUT_PLACEHOLDER; ?>">
                    </div>
                </div>
            </div>
        </div>

        <div data-tab-id='tab-icons' data-tab-caption='<?= NETCAT_MODAL_DIALOG_IMAGE_TAB_ICONS_CAPTION; ?>'>
            <div class="nc-image-dialog-panel">
                <div class="nc-image-dialog-panel-side left">
                    <div class="nc-library-icons-main-container">
                        <? foreach ($libraries as $library_keyword => $library_data) { ?>
                            <div class="nc-image-dialog-library" data-keyword="<?= $library_keyword; ?>">
                                <h2 class="nc-image-dialog-library-name"><?= $library_data['name']; ?></h2>
                                <div class="nc-image-dialog-library-icons <?= $library_keyword; ?>">
                                    <? $icons = array_slice( $library_data['icons'], 0, 10 ); ?>
                                    <? foreach ($icons as $icon_keyword => $icon_data) { ?>
                                        <? $selected = $library_keyword == $current_library && $icon_keyword == $current_icon ? 'selected' : null; ?>

                                        <div class="nc-image-dialog-library-icon <?= $selected; ?>"
                                             data-nc-library="<?= $library_keyword; ?>"
                                             data-nc-icon="<?= $icon_keyword; ?>">
                                            <div class="nc-image-dialog-library-icon-img" data-src="<?= $icon_data['http']; ?>">
                                                <img src="<?= $icon_data['http'] ?>" alt="<?= $icon_keyword ?>">
                                            </div>
                                        </div>

                                    <? } ?>
                                    <div class="nc-image-dialog-library-icons-not-found">
                                        <?= NETCAT_MODAL_DIALOG_IMAGE_TAB_ICONS_ICONS_NOT_FOUND; ?>.
                                    </div>
                                </div>
                                <a href="#" id="<?= $library_keyword; ?>" data-font="<?= $library_keyword; ?>" class="nc-image-dialog-library-show-all"><?= NETCAT_MODAL_DIALOG_IMAGE_TAB_ICONS_SHOW_ALL ?></a>
                            </div>
                        <? } ?>
                    </div>
					
                    <div class="nc-library-icons-search-block nc--hide"></div>
                </div>
                
                <div class="nc-image-dialog-panel-side right">
                    <div class="nc-field nc-field-type-string nc-image-dialog-search">
                        <input type="text" name="search" value="" class="nc-image-dialog-search-input" id="search"
                               placeholder="<?= NETCAT_MODAL_DIALOG_IMAGE_TAB_ICONS_ICONS_SEARCH_INPUT_PLACEHOLDER; ?>"
                               autocomplete="off">
                        <ul class="nc-image-dialog-search-dropdown"></ul>
                    </div>
                    
                    <div class="nc_radio_modal">
                        <div>
                            <label>
                                <input type="radio" name="selected_library" value="all"
                                       class="nc-image-dialog-library-choose" checked>
                                <?= NETCAT_MODAL_DIALOG_IMAGE_TAB_ICONS_LIBRARY_CHOOSE; ?>
                            </label>
                        </div>

                        <? foreach ($libraries as $library_keyword => $library_data) { ?>
                            <div>
                                <label>
                                    <input type="radio" name="selected_library" value="<?= $library_keyword; ?>"
                                           class="nc-image-dialog-library-choose">
                                    <?= $library_data['name']; ?>
                                </label>
                            </div>
                        <? } ?>
                    </div>
                </div>
            </div>
        </div>

        <? /* to be continued... */ ?>
        <? if (false) { ?>
            <div data-tab-id='tab-upload' data-tab-caption='<?= NETCAT_MODAL_DIALOG_IMAGE_TAB_UPLOAD_CAPTION; ?>'>
                <?= NETCAT_MODAL_DIALOG_IMAGE_TAB_UPLOAD_CAPTION; ?>
            </div>

            <div data-tab-id='tab-from-web' data-tab-caption='<?= NETCAT_MODAL_DIALOG_IMAGE_TAB_WEB_CAPTION; ?>'>
                <?= NETCAT_MODAL_DIALOG_IMAGE_TAB_WEB_CAPTION; ?>
            </div>
        <? } ?>
    </form>

    <div class='nc-modal-dialog-footer'>
        <button data-action='submit' class="nc-image-dialog-submit"><?= NETCAT_MODAL_DIALOG_IMAGE_BUTTON_SAVE; ?></button>
        <button data-action='close'><?= NETCAT_MODAL_DIALOG_IMAGE_BUTTON_CLOSE; ?></button>
    </div>

    <script>
        $nc(function() {
            var current_library = '';
            var current_icon = '';
            var current_color = '<?= htmlspecialchars($current_color) ?>';
            var current_dialog = nc.ui.modal_dialog.get_current_dialog();
            var array_json = <?= json_encode($libraries) ?>;
            // По-умолчанию скрываем редактирование
            current_dialog.hide_tab('tab-edit');

            // Установим данные диалога (если есть)
            var items = ['class_id', 'message_id', 'field_id'];
            for (var i in items) {
                var item = items[i];
                var id = current_dialog.get_option(item);
                if (id) {
                    current_dialog.find('input[name="' + item + '"]').val(id);
                }
            }

            // ColorPicker
            function updateSvgColor(color) {
                current_dialog.find('.nc-image-dialog-edit-icon svg path').css('fill', color);
            }
            function updateInputColor(color) {
                current_dialog.find('.nc-image-dialog-edit-icon-colorpicker-input').val(color);
            }
            function updatePickerColor(color) {
                colorpicker.minicolors('value', {color: color, opacity: 1});
            }
            var colorpicker = current_dialog.find('.nc-image-dialog-edit-icon-colorpicker').minicolors({
                inline: 'true',
                change: function(value, opacity) {
                    current_color = value;
                    updateSvgColor(value);
                    var $colorInput = current_dialog.find('.nc-image-dialog-edit-icon-colorpicker-input:not(:focus)');
                    if ($colorInput.length) {
                        updateInputColor(value);
                    }
                }
            });
            current_dialog.find('.minicolors-panel').click(function () {
                current_dialog.find('.nc-image-dialog-edit-icon-colorpicker-input').blur();
            });
            current_dialog.find('.nc-image-dialog-edit-icon-colorpicker-input').on('keyup change', function () {
                var color = $nc(this).val();
                if (color) {
                    updatePickerColor(color);
                }
            });

            // Кэш для поиска иконок
            var icons_cache = [];

            $nc.each(array_json, function (library, libraryObject) {
                $nc.each(libraryObject.icons, function () {
                    var currentIcon = $nc(this)[0];
                    icons_cache.push({
                        library_keyword: library,
                        icon_keyword: currentIcon['keyword'],
                    });
                });
            });

            // Поиск иконок и выпадайка
            var search_icons_input = current_dialog.find('.nc-image-dialog-search-input');
            var search_icons_dropdown = current_dialog.find('.nc-image-dialog-search-dropdown');
            var search_icons_previous_query = null;
            var search_icons_timestamp_first = Date.now();
            var search_icons_timestamp_last = Date.now();
            var search_icons_timeout = null;


            search_icons_input.on('keyup change', function () {
                var query = $nc(this).val().toLowerCase();
                processSearchIcons(query);
            });

            search_icons_input.focusin(function () {
                search_icons_dropdown.show();
            });
            search_icons_input.focusout(function () {
                // Задержка, чтобы успел сработать dropdownItem.click();
                setTimeout(function () {
                    search_icons_dropdown.hide();
                }, 250);
            });

            function setCursorProcessingState() {
                $nc(document).find('body').css('cursor', 'progress');
            }

            function resetCursorState() {
                $nc(document).find('body').css('cursor', 'default');
            }

            function processSearchIcons(query, byTimeout) {
                setCursorProcessingState();
                // Входные данные
                byTimeout = typeof byTimeout !== 'undefined' ?  byTimeout : false;
                if (query === search_icons_previous_query) {
                    resetCursorState();
                    return;
                }
                // Валидируем только после N-мсек после последнего ввода данных
                let timestamp = Date.now();
                let delayBetweenActions = 1000;
                if (!byTimeout) {
                    search_icons_timestamp_first = timestamp;
                }
                search_icons_timestamp_last = timestamp;
                if (search_icons_timestamp_last - search_icons_timestamp_first <= delayBetweenActions) {
                    if (search_icons_timeout) {
                        clearTimeout(search_icons_timeout);
                        search_icons_timeout = null;
                    }
                    search_icons_timeout = setTimeout(function () {
                        processSearchIcons(query, true);
                    }.bind(this), delayBetweenActions);
                    return false;
                }
                search_icons_previous_query = query;

                // Покажем или скроем поисковые подсказки при совпадении или не совпадении ключевого слова с запросом
                processSearchIconsDropdown(query);

                // Покажем или скроем иконки при совпадении или не совпадении ключевого слова с запросом
                processSearchIconsIcons(query);

                resetCursorState();
            }

            function processSearchIconsDropdown(query) {
                if (!query.length) {
                    search_icons_dropdown.hide();
                    search_icons_dropdown.html('');
                    return;
                }
                var founded_icon_keywords = [];
                for (var iconCache in icons_cache) {
                    let cacheItem = icons_cache[iconCache];
                    var icon_keyword = cacheItem.icon_keyword;
                    if (icon_keyword.indexOf(query) === -1) {
                        continue;
                    }
                    if (founded_icon_keywords.indexOf(icon_keyword) >= 0) {
                        continue;
                    }
                    if (icon_keyword.localeCompare(query) === 0) {
                        continue;
                    }
                    founded_icon_keywords.push(icon_keyword);
                    if (founded_icon_keywords.length >= 5) {
                        break;
                    }
                }
                var dropdown_html = [];
                if (founded_icon_keywords.length) {
                    for (var iconKeyword in founded_icon_keywords) {
                        let keywordItem = founded_icon_keywords[iconKeyword];
                        dropdown_html.push('<li class="nc-image-dialog-search-dropdown-item">' + keywordItem + '</li>');
                    }
                }
                search_icons_dropdown.html(dropdown_html.join("\n"));
                if (!founded_icon_keywords.length) {
                    return;
                }
                search_icons_dropdown.find('.nc-image-dialog-search-dropdown-item').click(function () {
                    var value = $nc(this).text();
                    search_icons_input.val(value);
                    search_icons_dropdown.hide();
                    search_icons_dropdown.html('');
                    setCursorProcessingState();
                    search_icons_input.trigger('change');
                });
                search_icons_dropdown.show();
            }

            function processSearchIconsIcons(query) {
                // поиск иконок среди выведенных в модалке
                var shownIcons = [];
                current_dialog.find('.nc-image-dialog-library-icons').each(function () {
                    var $icons = $nc(this);
                    var currentLibraryKeyword = $nc(this).closest('.nc-image-dialog-library').data('keyword');

                    // Покажем или скроем иконки
                    shownIcons[currentLibraryKeyword] = [];
                    $icons.find('.nc-image-dialog-library-icon').each(function () {
                        var $icon = $nc(this);
                        var iconKeyword = $icon.data('nc-icon');
                        if (iconKeyword.indexOf(query) !== -1) {
                            $icon.show();
                            shownIcons[currentLibraryKeyword].push(iconKeyword);
                        } else {
                            $icon.hide();
                        }
                    });
                });

                if (query) {
                    $nc.each(array_json, function (libraryKeyword, iconsObject) {
                        var collectionContainer = $nc('.nc-image-dialog-library-icons').filter('.' + libraryKeyword);

                        $nc.each(iconsObject.icons, function () {
                            var currentIcon = $nc(this)[0];

                            // проверяем, показана ли уже иконка в результатах поиска
                            if ($nc.inArray(currentIcon['keyword'], shownIcons[libraryKeyword]) !== -1) {
                                return;
                            }

                            // добавляем временную иконку, если удалось найти совпадение
                            if (currentIcon['keyword'].indexOf(query) !== -1) {
                                var iconElem = '<div class="nc-image-dialog-library-icon nc-image-icon-tmp " ' +
                                    'data-nc-library="' + libraryKeyword + '" ' +
                                    'data-nc-icon="' + currentIcon['keyword'] + '">';
                                iconElem += '<div class="nc-image-dialog-library-icon-img" data-src="' + currentIcon['http'] + '">';
                                iconElem += '<img src="' + currentIcon['http'] + '" alt="' + currentIcon['keyword'] + '">';
                                iconElem += '</div>';
                                iconElem += '</div>';
                                collectionContainer.append(iconElem);
                            }
                        });
                    });
                    orderLibraries();
                } else {
                    orderLibraries(true);
                    deleteTmpIcons();
                }

                showWarningIfAllIconsAreHidden();
            }

            function orderLibraries(reset = false) {
                var mainContainer = $nc('.nc-library-icons-main-container');
                var librariesBlocks = mainContainer.find('.nc-image-dialog-library');
                if (!librariesBlocks.length) {
                    return;
                }

                if (reset) {
                    // сортируем по названию библиотеки
                    librariesBlocks.detach();
                    librariesBlocks.sort(function (a, b) {
                        return $nc(a).data('keyword') > $nc(b).data('keyword');
                    }).appendTo(mainContainer);
                } else {
                    // сортируем по количеству совпадений
                    librariesBlocks.detach();
                    librariesBlocks.sort(function (a, b) {
                        var aVisibleIcons = $nc(a).find('.nc-image-dialog-library-icon').filter(function() { return $(this).css('display') !== 'none' });
                        var bVisibleIcons = $nc(b).find('.nc-image-dialog-library-icon').filter(function() { return $(this).css('display') !== 'none' });

                        return aVisibleIcons.length <= bVisibleIcons.length;
                    }).appendTo(mainContainer);
                }
            }

            // Актуализация сообщений о ненахождении иконок
            function showWarningIfAllIconsAreHidden() {
                current_dialog.find('.nc-image-dialog-library-icons').each(function () {
                    var $icons = $nc(this);
                    // Покажем или скроем сообщение о том, что нет иконок
                    var hasVisibleIcons = false;
                    var currentLibraryIconsCount = $icons.find('.nc-image-dialog-library-icon').length;

                    if (currentLibraryIconsCount) {
                        $icons.find('.nc-image-dialog-library-icon').each(function () {
                            var $icon = $nc(this);
                            if ($icon.css('display') !== 'none') {
                                hasVisibleIcons = true;
                            }
                        });
                    }

                    var $iconsNotFound = $icons.find('.nc-image-dialog-library-icons-not-found');
                    if (hasVisibleIcons) {
                        $iconsNotFound.hide();
                    } else {
                        $iconsNotFound.show();
                    }
                });
            }

            // Выбор актуальной библиотеки
            current_dialog.find('.nc-image-dialog-library-choose').change(function () {
                var $libraries = current_dialog.find('.nc-image-dialog-library');
                var libraryKeyword = current_dialog.find('.nc-image-dialog-library-choose:checked').val();
                if (libraryKeyword !== 'all') {
                    $libraries.each(function() {
                        var $library = $nc(this);
                        if ($library.data('keyword') === libraryKeyword) {
                            $library.show();
                        } else {
                            $library.hide();
                        }
                    });
                } else {
                    $libraries.each(function() {
                        $nc(this).show();
                    });
                }
            });

            // Выбор иконки
            $nc('body').on('click', '.nc-image-dialog-library-icon', function () {
                // Снимаем выделение со всех иконок
                var $icons = current_dialog.find('.nc-image-dialog-library-icon.selected');
                $icons.removeClass('selected');
                // Скопируем выбранную иконку в контейнер
                var $selectedIcon = $nc(this);
                current_library = $selectedIcon.data('nc-library');
                current_icon = $selectedIcon.data('nc-icon');
                current_dialog.find('input[name="library"]').val(current_library);
                current_dialog.find('input[name="icon"]').val(current_icon);
                var $selectedIconImg = $selectedIcon.find('.nc-image-dialog-library-icon-img');
                $selectedIcon.addClass('selected');
                var $selectedIconImgTag = $nc('<img/>', {
                    src: $selectedIconImg.data('src'),
                    class: 'nc-image-dialog-edit-icon-img'
                });
                var $editIconContainer = current_dialog.find('.nc-image-dialog-edit-icon');
                $editIconContainer.html('');
                $editIconContainer.append($selectedIconImgTag);
                // Заменим img на inline-svg
                var $img = $editIconContainer.find('img');
                var imgURL = $img.attr('src');
                var attributes = $img.prop('attributes');
                $nc.get(imgURL, function(data) {
                    var $svg = $nc(data).find('svg');
                    $svg = $svg.removeAttr('xmlns:a');
                    $nc.each(attributes, function() {
                        $svg.attr(this.name, this.value);
                    });
                    $img.replaceWith($svg);
                    // Переключимся на редактирование
                    if (current_color) {
                        updatePickerColor(current_color);
                        updateSvgColor(current_color);
                        updateInputColor(current_color);
                    }
                    current_dialog.change_tab('tab-edit');
                }, 'xml');
            });

            // Закрытие диалога
            current_dialog.set_option('on_submit_response', function(response) {
                response = JSON.parse(response);
                var $input = this.get_option('image_dialog_input');
                var tmp_icon_path = response.path;

                // Сохранение адреса выбранной иконки в поле f_*_tmp
                if (tmp_icon_path) {
                    $input.val(tmp_icon_path);
                    $input.trigger('change');
                }
                colorpicker.minicolors('destroy');
                current_dialog.destroy();
            });

            // Выбор таба
            <? if ($current_icon) { ?>
                // Если выбрана какая-либо иконка - переключим на редактирование
                var selectedIcon = current_dialog.find('.nc-image-dialog-library-icon.selected');
                if (selectedIcon.length) {
                    selectedIcon.trigger('click');
                }
            <? } else { ?>
            // Иначе перекинем на таб с выбором иконки
                current_dialog.change_tab('tab-icons');
            <? } ?>

            current_dialog.find('.nc-image-dialog-library-show-all').click(function (event) {
                event.preventDefault();

                var dataset = event.target.dataset;
                var font = dataset.font;
                var resq = '';

                $.each(array_json, function (index,value) {
                    if (index === font) {
                        var font_name = index;
                        var name = value.name;
                        var icons = value.icons;
                        sliced = Object.keys(icons).slice(10).reduce((result, key) => {
                            result[key] = icons[key];
                            return result;
                        }, {});
                        $.each(sliced, function (index, value) {
                            resq += '<div class="nc-image-dialog-library-icon <?= $selected; ?>" data-nc-library="' + font_name + '" data-nc-icon="' + index + '">';
                            resq += '<div class="nc-image-dialog-library-icon-img" data-src="' + value.http + '">';
                            resq += '<img src="' + value.http + '" alt="' + index + '">';
                            resq += '</div>';
                            resq += '</div>';
                        });
                    }

                });
                $('.nc-library-icons-main-container').show();
                $('.nc-library-icons-search-block').hide();
                $('.' + font).append(resq);
                $('#' + font).hide();
            });

            function deleteTmpIcons() {
                var tmpIcons = $nc('.nc-image-icon-tmp');
                if (tmpIcons.length) {
                    tmpIcons.not('.selected').remove();
                }
            }
        });
    </script>
</div>