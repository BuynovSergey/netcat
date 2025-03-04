document.addEventListener('DOMContentLoaded', function() {

    const page = $nc('meta#nc_page').data();
    if (!page) {
        return; // в режиме редактирования должен быть <meta id="nc_page"> с данными о странице
    }

    const bothButtons = $nc('.nc-navbar .nc-version-button');
    const buttons = {
        undo: bothButtons.filter('[data-type="undo"]'),
        redo: bothButtons.filter('[data-type="redo"]'),
    }
    const title = {
        undo: buttons.undo.find('.nc-version-title'),
        redo: buttons.redo.find('.nc-version-title'),
    };

    const navbar = $nc('.nc-navbar');

    const url = NETCAT_PATH + 'action.php';

    let baseParams = {
        page_action: page.action,
        page_areas: getAreas(),
        site_id: page.siteId,
        subdivision_id: page.subdivisionId,
        infoblock_id: page.infoblockId,
        component_id: page.componentId,
        object_id: page.objectId,
        ctrl: 'admin.version',
        // also need to set controller 'action' for a request
    };

    function getAreas() {
        let areas = [];
        $nc('.nc-area').each((i, el) => areas.push(el.dataset.keyword))
        return areas.join(' ');
    }

    function update() {
        const params = {
            ... baseParams,
            action: 'get_undo_and_redo'
        };
        $nc.get(url, params, function(response) {
            for (let type of ['undo', 'redo']) {
                // показ и скрытие 'title' определяется стилями по .nc--disabled и .nc--hovered
                if (response[type]) {
                    title[type].html(response[type].title)
                    buttons[type].removeClass('nc--disabled').data('changeset_id', response[type].changeset_id);
                } else {
                    title[type].html('');
                    buttons[type].addClass('nc--disabled').data('changeset_id', null);
                }
            }
        }, 'json');
    }

    bothButtons.hover(
        function () { // mouse over
            $nc(this).addClass('nc--hovered');
            navbar.addClass('nc--clicked'); // меняет z-index навбара на максимальный
        },
        function () { // mouse out
            $nc(this).removeClass('nc--hovered');
            navbar.removeClass('nc--clicked');
        }
    );
    bothButtons.click(function (e) {
        const button = $nc(this);
        if (!button.is('.nc--disabled')) {
            const changeset_id = button.data('changeset_id');
            if (changeset_id) {
                const params = {
                    ...baseParams,
                    action: button.data('type') + '_changeset',
                    changeset_id: changeset_id,
                };
                $nc.post(url, params, function (response) {
                    window.location.reload();
                });
            }
        }
        e.preventDefault();
    });

    update();
    bothButtons.parent().hover(update);
    $nc(document).on('ncContentUpdate', update);

});