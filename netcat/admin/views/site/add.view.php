<?php

if (!class_exists('nc_core')) {
    die;
}

?>
<!-- Добавление нового сайта — пустого или из стора -->

<? $category_class = 'nc-site-add-site--category-'; ?>

<form class="nc-form nc--vertical nc-site-add-form" method="post">
    <input type="hidden" name="ctrl" value="admin.site">
    <input type="hidden" name="action" value="create">
    <style scoped>
        .nc-site-add-form label {
            font-size: 100%;
        }
        .nc-site-add-form input[type=text] {
            width: 300px;
        }
        .nc-site-add-site-list-group {
            margin: 5px 0;
        }
        .nc-site-add-site-list-group h3 {
            font-size: 100%;
        }
        .nc-site-add-site-list-demo-link {
            border-bottom: 1px solid rgba(26,135,194,0.5);
            margin-left: 10px;
            display: none;
        }
        .nc-site-add-list-site:hover .nc-site-add-site-list-demo-link {
            display: inline-block;
        }
        .nc-site-add-site-category-filter {
            background: #F5F5F5;
            padding: 10px 15px;
        }
    </style>

    <div class="nc-form-row">
        <label for="form_domain"><?= CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_NAME ?>:</label>
        <input id="form_domain" type="text" name="data[Catalogue_Name]" value=""/>
    </div>
    <div class="nc-form-row">
        <label for="form_domain"><?= CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_DOMAIN ?>:</label>
        <input id="form_domain" type="text" name="data[Domain]" value="<?= htmlspecialchars($domain) ?>"/>
    </div>

    <div class="nc-form-row">
        <label><input type="radio" name="site_type" value="blank" checked> <?= CONTROL_CONTENT_SITE_ADD_EMPTY  ?></input></label>
        <label><input type="radio" name="site_type" value="store_site"> <?= CONTROL_CONTENT_SITE_ADD_WITH_CONTENT ?></input></label>
    </div>

    <div class="nc-site-add-list" style="display: none;">

        <? if (!empty($data['error_text']) || !count($data['data']['sites'])): ?>
            <div class='nc-alert nc--red'><i class='nc-icon-l nc--status-error'></i>
                <?= $data['error_text'] ?>
            </div>

        <? else: ?>

            <div class="nc-site-add-site-category-filter">
                <span style="padding-right: 8px"><?= CONTROL_CONTENT_SITE_CATEGORY ?></span>
                <a class="nc-label nc--blue" href="#"><?= CONTROL_CONTENT_SITE_CATEGORY_ANY ?></a>
                <? foreach ($data['data']['categories'] as $id => $name): ?>
                    <a class="nc-label nc--light" href="#<?= $id ?>"><?= nc_strtolower($name) ?></a>
                <? endforeach; ?>
            </div>

            <? $previous_site_type_id = 0; ?>

            <div class="nc-site-add-site-list-group">
            <? foreach ($data['data']['sites'] as $site): ?>
                <? if ($previous_site_type_id != $site['site_type_id']): ?>
                    <? if ($previous_site_type_id): ?>
                        </div>
                        <div class="nc-site-add-site-list-group">
                    <? endif; ?>
                    <h3>
                        <?= $data['data']['site_types'][$site['site_type_id']] ?>
                    </h3>
                <? endif; ?>
                <? $previous_site_type_id = $site['site_type_id']; ?>

                <div class="nc-site-add-list-site <?= $category_class . join(" $category_class", $site['category_ids']) ?>">
                    <label>
                        <input type="radio" name="site_id" value="<?= $site['id'] ?>">
                        <span class="nc-site-add-list-site-name"><?= $site['name'] ?></span>
                    </label>
                    <a href="<?= htmlspecialchars($site['store_page_url']) ?>"
                       target="_blank" class="nc-site-add-site-list-demo-link">
                        <?= CONTROL_CONTENT_SITE_ADD_PREVIEW ?>
                    </a>
                </div>
            <? endforeach; ?>
            </div>

        <? endif; ?>

    </div>

</form>

<script>
(function() {

    // Показ или скрытие списка сайтов
    $nc('.nc-site-add-form input[name=site_type]').change(function() {
        $nc('.nc-site-add-list').toggle($nc(this).val() == 'store_site');
        check_first_site();
    });

    var filter_buttons = $nc('.nc-site-add-site-category-filter a.nc-label'),
        all_sites = $nc('.nc-site-add-list-site'),
        site_type_groups = $nc('.nc-site-add-site-list-group'),
        radio_buttons = all_sites.find('input:radio'),
        site_name_input = $nc('.nc-site-add-form input[name="data[Catalogue_Name]"]'),
        site_name_input_has_value = false;

    // Запоминание состояния поля «Имя сайта»
    site_name_input.change(function() {
        site_name_input_has_value = this.value.length > 0;
    });

    // Если название сайта не было указано, заполним его автоматически при выборе сайта из списка
    radio_buttons.change(function() {
        if (!site_name_input_has_value) {
            var name = radio_buttons.filter(':checked')
                            .parents('.nc-site-add-list-site')
                            .find('.nc-site-add-list-site-name').text();
            site_name_input.val(name);
        }
    });

    // Выбор первого сайта, когда ничего не выбрано
    function check_first_site() {
        if (!radio_buttons.filter(":checked").length) {
            radio_buttons.filter(":visible").first().prop('checked', true);
        }
    }

    // Фильтрация по категории
    filter_buttons.click(function(event) {
        event.preventDefault();

        var clicked = $nc(this);
        if (clicked.hasClass('nc--blue')) {
            return;
        }

        // Применение фильтра
        filter_buttons.removeClass('nc--blue').addClass('nc--light');
        clicked.removeClass('nc--light').addClass('nc--blue');

        var category_id = clicked.attr('href').substr(1);
        if (category_id.length) {
            all_sites.hide().filter('.<?= $category_class ?>' + category_id).show();
        }
        else {
            all_sites.show();
        }

        // Прячем заголовки для разделов, в которых ничего нет
        site_type_groups.each(function() {
            $nc(this).find('h3').toggle(($nc(this).find('.nc-site-add-list-site:visible').length > 0));
        });

        // Сбрасываем выбранный сайт, если он невидим
        radio_buttons.filter(":hidden").prop('checked', false);
        // и выбираем первый, если ничего не выбрано
        check_first_site();
    });

})();
</script>
