urlDispatcher.route = {
    "blank": "about:blank",
    "index": ADMIN_PATH + "index_page.php",

    "catalogue.edit": ADMIN_PATH + "catalogue/index.php?action=edit&phase=2&CatalogueID=%1",
    "catalogue.design": ADMIN_PATH + "catalogue/index.php?action=design&phase=2&CatalogueID=%1",
    "catalogue.seo": ADMIN_PATH + "catalogue/index.php?action=seo&phase=2&CatalogueID=%1",
    "catalogue.system": ADMIN_PATH + "catalogue/index.php?action=system&phase=2&CatalogueID=%1",
    "catalogue.fields": ADMIN_PATH + "catalogue/index.php?action=fields&phase=2&CatalogueID=%1",

    // Создание раздела (parent_sub_id, catalogue_id)
    "subdivision.add": ADMIN_PATH + "subdivision/index.php?phase=2&ParentSubID=%1&CatalogueID=%2",

    // Редактирование раздела
    "subdivision.edit": ADMIN_PATH + "subdivision/index.php?phase=5&SubdivisionID=%1&view=edit",
    "subdivision.design": ADMIN_PATH + "subdivision/index.php?phase=5&SubdivisionID=%1&view=design",
    "subdivision.seo": ADMIN_PATH + "subdivision/index.php?phase=5&SubdivisionID=%1&view=seo",
    "subdivision.system": ADMIN_PATH + "subdivision/index.php?phase=5&SubdivisionID=%1&view=system",
    "subdivision.fields": ADMIN_PATH + "subdivision/index.php?phase=5&SubdivisionID=%1&view=fields",

    // Удаление раздела
    "subdivision.delete": ADMIN_PATH + "subdivision/index.php?phase=7&Delete%1=%1",

    // Информация о разделе
    "subdivision.info": ADMIN_PATH + "subdivision/index.php?phase=4&SubdivisionID=%1",

    // Удаленные объекты
    "subdivision.trashed_objects": ADMIN_PATH + "subdivision/index.php?phase=8&SubdivisionID=%1",

    // История в разделе
    "subdivision.versions": NETCAT_PATH + "action.php?ctrl=admin.subdivision&action=show_versions&subdivision_id=%1",
    "subdivision.versions.infoblock": NETCAT_PATH + "action.php?ctrl=admin.subdivision&action=show_versions&subdivision_id=%1&entity=infoblock",
    "subdivision.versions.object": NETCAT_PATH + "action.php?ctrl=admin.subdivision&action=show_versions&subdivision_id=%1&entity=object",

    // Список подразделов
    "subdivision.sublist": ADMIN_PATH + "subdivision/index.php?phase=1&ParentSubID=%1",

    // Список шаблонов в разделе
    // Права пользователей на действия в разделе
    "subdivision.userlist": ADMIN_PATH + "subdivision/index.php?phase=15&SubdivisionID=%1",

    // Просмотр раздела
    "subdivision.view": ADMIN_PATH + "subdivision/index.php?phase=14&SubdivisionID=%1",

    // Используемые шаблоны
    "subdivision.subclass": ADMIN_PATH + "subdivision/SubClass.php?SubdivisionID=%1",

    // Список сквозных инфоблоков
    "site.area": NETCAT_PATH + "action.php?ctrl=admin.infoblock&action=show_area_infoblocks&site_id=%1",

    // Демо контент
    "site.demo_content": NETCAT_PATH + "action.php?ctrl=admin.demo_content.demo_content&action=show_list_demo_content&site_id=%1",

    // Список шаблонов в разделе
    "subclass.list": ADMIN_PATH + "subdivision/SubClass.php?SubdivisionID=%1",

    // Создание шаблона в разделе
    "subclass.add": ADMIN_PATH + "subdivision/SubClass.php?phase=1&SubdivisionID=%1",

    // Редактирование шаблона в разделе
    "subclass.edit": ADMIN_PATH + "subdivision/SubClass.php?phase=3&SubClassID=%1&SubdivisionID=%2",

    // Удаление всех объектов в шаблоне-в-разделе
    "subclass.purge": NETCAT_PATH + "message.php?cc=%1&delete=1&inside_admin=1",

    // Просмотр шаблона раздела
    "subclass.view": ADMIN_PATH + "subdivision/index.php?phase=14&SubClassID=%2",

    // Список объектов
    "object.list": NETCAT_PATH + "?inside_admin=1&cc=%1",

    // Список объектов в виде таблицы
    "object.switch_view": NETCAT_PATH + "action.php?ctrl=admin.component&action=switch_view&cc=%1",

    // Отображение объекта
    "object.view": NETCAT_PATH + "full.php?inside_admin=1&cc=%1&message=%2",

    // Редактирование объекта
    "object.edit": NETCAT_PATH + "message.php?inside_admin=1&classID=%1&message=%2",

    // Удаление объекта
    "object.delete": NETCAT_PATH + "message.php?inside_admin=1&cc=%1&message=%2&delete=1",

    // Создание объекта
    "object.add": NETCAT_PATH + "add.php?inside_admin=1&cc=%1",

    // Список всех сайтов
    "site.list": ADMIN_PATH + "catalogue/index.php",

    // Создание сайта
    "site.add": NETCAT_PATH + "action.php?ctrl=admin.site&action=show_add_form",

    // Настройки сайта
    "site.edit": ADMIN_PATH + "catalogue/index.php?phase=2&type=2&CatalogueID=%1",

    // Удаление сайта
    "site.delete": ADMIN_PATH + "catalogue/index.php?phase=4&Delete%1=%1",

    // Карта сайта
    "site.map": ADMIN_PATH + "subdivision/full.php?CatalogueID=%1",

    // Статистика
    "site.stat.nc_stat": NETCAT_PATH + "modules/stats/admin.php?phase=9&cat_id=%1",
    "site.stat.openstat": NETCAT_PATH + "modules/stats/openstat/admin.php?catalog_page=%1",

    // Информация
    "site.info": ADMIN_PATH + "catalogue/index.php?phase=6&CatalogueID=%1",

    // Список разделов
    "site.sublist": ADMIN_PATH + "subdivision/index.php?phase=1&CatalogueID=%1&ParentSubID=%2",

    // Мастер создания сайта
    "site.wizard": ADMIN_PATH + "wizard/wizard_site.php?phase=%1&CatalogueID=%2",


    // ------------------------Работа с группами шаблонов---------------------
    // Добавление группы шаблонов
    // Редактирование группы шаблонов
    "classgroup.edit": ADMIN_PATH + "class/index.php?phase=1&ClassGroup=%1",
    "classgroup_fs.edit": ADMIN_PATH + "class/index.php?fs=1&phase=1&ClassGroup=%1",


    // ---------------------Работа с шаблонами-------------------------------
    // Вывод списка шаблонов
    "dataclass.list": ADMIN_PATH + "class/",
    "dataclass_fs.list": ADMIN_PATH + "class/?fs=1",

    // Информация о шаблоне
    "dataclass.info": ADMIN_PATH + "class/index.php?phase=13&ClassID=%1",
    "dataclass_fs.info": ADMIN_PATH + "class/index.php?fs=1&phase=13&ClassID=%1",

    // Добавление шаблона
    "dataclass.add": ADMIN_PATH + "class/index.php?phase=10&ClassGroup=%1",
    "dataclass_fs.add": ADMIN_PATH + "class/index.php?fs=1&phase=10&ClassGroup=%1",

    // Редактирование шаблона
    "dataclass.edit": ADMIN_PATH + "class/index.php?phase=4&ClassID=%1",
    "dataclass_fs.edit": ADMIN_PATH + "class/index.php?fs=1&phase=4&ClassID=%1",

    // Удаление шаблона
    "dataclass.delete": ADMIN_PATH + "class/index.php?phase=6&Delete%1=%1",
    "dataclass_fs.delete": ADMIN_PATH + "class/index.php?fs=1&phase=6&Delete%1=%1",

    // Шаблоны действий
    "dataclass.classaction": ADMIN_PATH + "class/index.php?phase=8&myaction=1&ClassID=%1",
    "dataclass_fs.classaction": ADMIN_PATH + "class/index.php?fs=1&phase=8&myaction=1&ClassID=%1",

    // Редактирование альтернативного шаблона добавления
    "dataclass.customadd": ADMIN_PATH + "class/index.php?phase=8&myaction=1&ClassID=%1",
    "dataclass_fs.customadd": ADMIN_PATH + "class/index.php?fs=1&phase=8&myaction=1&ClassID=%1",

    // Редактирование альтернативного шаблона изменения
    "dataclass.customedit": ADMIN_PATH + "class/index.php?phase=8&myaction=2&ClassID=%1",
    "dataclass_fs.customedit": ADMIN_PATH + "class/index.php?fs=1&phase=8&myaction=2&ClassID=%1",

    // Редактирование альтернативного шаблона поиска
    "dataclass.customsearch": ADMIN_PATH + "class/index.php?phase=8&myaction=3&ClassID=%1",
    "dataclass_fs.customsearch": ADMIN_PATH + "class/index.php?fs=1&phase=8&myaction=3&ClassID=%1",

    // Редактирование альтернативного шаблона подписки
    // Редактирование альтернативного шаблона удаления
    "dataclass.customdelete": ADMIN_PATH + "class/index.php?phase=8&myaction=5&ClassID=%1",
    "dataclass_fs.customdelete": ADMIN_PATH + "class/index.php?fs=1&phase=8&myaction=5&ClassID=%1",

    // Список полей шаблона
    "dataclass.fields": ADMIN_PATH + "field/index.php?ClassID=%1",
    "dataclass_fs.fields": ADMIN_PATH + "field/index.php?fs=1&ClassID=%1",

    // Пользовательские настройки
    "dataclass.custom": ADMIN_PATH + "class/index.php?phase=24&ClassID=%1",
    "dataclass_fs.custom": ADMIN_PATH + "class/index.php?fs=1&phase=24&ClassID=%1",

    // Редактирование одной настройки
    "dataclass.custom.edit": ADMIN_PATH + "class/index.php?phase=25&ClassID=%1&param=%2",
    "dataclass_fs.custom.edit": ADMIN_PATH + "class/index.php?fs=1&phase=25&ClassID=%1&param=%2",

    // Создание новой настройки
    "dataclass.custom.new": ADMIN_PATH + "class/index.php?phase=25&ClassID=%1",
    "dataclass_fs.custom.new": ADMIN_PATH + "class/index.php?fs=1&phase=25&ClassID=%1",

    // Ручное редактирование
    "dataclass.custom.manual": ADMIN_PATH + "class/index.php?phase=26&ClassID=%1",
    "dataclass_fs.custom.manual": ADMIN_PATH + "class/index.php?fs=1&phase=26&ClassID=%1",
    "dataclass_fs.custom_fs.edit": ADMIN_PATH + "class/index.php?fs=1&phase=25&ClassID=%1&param=%2",
    "dataclass_fs.custom_fs.new": ADMIN_PATH + "class/index.php?fs=1&phase=25&ClassID=%1",
    "dataclass_fs.custom_fs.manual": ADMIN_PATH + "class/index.php?fs=1&phase=26&ClassID=%1",

    // Импорт шаблона
    "dataclass.import": ADMIN_PATH + "class/import.php?ClassGroup=%1",
    "dataclass_fs.import": ADMIN_PATH + "backup.php?type=class&mode=import&ClassGroup=%1",

    // Конвертирование шаблона 4->5
    "dataclass.convert": ADMIN_PATH + "class/convert.php?fs=0&ClassID=%1",

    // Отмена конвертирования шаблона 4->5
    "dataclass_fs.convertundo": ADMIN_PATH + "class/convert.php?fs=1&ClassID=%1&phase=3",

    // Мастер создания шаблона
    "dataclass.wizard": ADMIN_PATH + "wizard/wizard_class.php?phase=%1&Class_Type=%2&ClassID=%3",
    "dataclass_fs.wizard": ADMIN_PATH + "wizard/wizard_class.php?fs=1&phase=%1&Class_Type=%2&ClassID=%3",


    // -----------------------Работа с шаблонами компонентов----------------------
    // Редактирование группы шаблонов компонента
    "classtemplates.edit": ADMIN_PATH + "class/index.php?phase=20&ClassID=%1",
    "classtemplates_fs.edit": ADMIN_PATH + "class/index.php?fs=1&phase=20&ClassID=%1",

    // Добавление шаблона компонента
    "classtemplate.add": ADMIN_PATH + "class/index.php?phase=14&ClassID=%1",
    "classtemplate_fs.add": ADMIN_PATH + "class/index.php?fs=1&phase=14&ClassID=%1",

    // Инфо и Редактирование настроек шаблона компонента
    "classtemplate.info": ADMIN_PATH + "class/index.php?phase=131&ClassID=%1",
    "classtemplate_fs.info": ADMIN_PATH + "class/index.php?fs=1&phase=131&ClassID=%1",

    // Редактирование шаблона компонента
    "classtemplate.edit": ADMIN_PATH + "class/index.php?phase=16&ClassID=%1",
    "classtemplate_fs.edit": ADMIN_PATH + "class/index.php?fs=1&phase=16&ClassID=%1",

    // Удаление шаблона компонента
    "classtemplate.delete": ADMIN_PATH + "class/index.php?phase=18&Delete%1=%1&ClassTemplate=%2",
    "classtemplate_fs.delete": ADMIN_PATH + "class/index.php?fs=1&phase=18&Delete%1=%1&ClassTemplate=%2",

    // Шаблоны действий
    "classtemplate.classaction": ADMIN_PATH + "class/index.php?phase=22&myaction=1&ClassID=%1",
    "classtemplate_fs.classaction": ADMIN_PATH + "class/index.php?fs=1&phase=22&myaction=1&ClassID=%1",

    // Редактирование альтернативного блока добавления
    "classtemplate.customadd": ADMIN_PATH + "class/index.php?phase=22&myaction=1&ClassID=%1",
    "classtemplate_fs.customadd": ADMIN_PATH + "class/index.php?fs=1&phase=22&myaction=1&ClassID=%1",

    // Редактирование альтернативного блока изменения
    "classtemplate.customedit": ADMIN_PATH + "class/index.php?phase=22&myaction=2&ClassID=%1",
    "classtemplate_fs.customedit": ADMIN_PATH + "class/index.php?fs=1&phase=22&myaction=2&ClassID=%1",

    // Редактирование альтернативного блока поиска
    "classtemplate.customsearch": ADMIN_PATH + "class/index.php?phase=22&myaction=3&ClassID=%1",
    "classtemplate_fs.customsearch": ADMIN_PATH + "class/index.php?fs=1&phase=22&myaction=3&ClassID=%1",

    // Редактирование альтернативного блока подписки
    // Редактирование альтернативного блока удаления
    "classtemplate.customdelete": ADMIN_PATH + "class/index.php?phase=22&myaction=5&ClassID=%1",
    "classtemplate_fs.customdelete": ADMIN_PATH + "class/index.php?fs=1&phase=22&myaction=5&ClassID=%1",

    // Пользовательские настройки
    "classtemplate.custom": ADMIN_PATH + "class/index.php?phase=240&ClassID=%1",
    "classtemplate_fs.custom": ADMIN_PATH + "class/index.php?fs=1&phase=240&ClassID=%1",

    // Редактирование одной настройки
    "classtemplate.custom.edit": ADMIN_PATH + "class/index.php?phase=250&ClassID=%1&param=%2",
    "classtemplate_fs.custom.edit": ADMIN_PATH + "class/index.php?fs=1&phase=250&ClassID=%1&param=%2",

    // Создание новой настройки
    "classtemplate.custom.new": ADMIN_PATH + "class/index.php?phase=250&ClassID=%1",
    "classtemplate_fs.custom.new": ADMIN_PATH + "class/index.php?fs=1&phase=250&ClassID=%1",

    // Ручное редактирование
    "classtemplate.custom.manual": ADMIN_PATH + "class/index.php?phase=260&ClassID=%1",
    "classtemplate_fs.custom.manual": ADMIN_PATH + "class/index.php?fs=1&phase=260&ClassID=%1",

    "classtemplate_fs.custom_fs.edit": ADMIN_PATH + "class/index.php?fs=1&phase=250&ClassID=%1&param=%2",
    "classtemplate_fs.custom_fs.new": ADMIN_PATH + "class/index.php?fs=1&phase=250&ClassID=%1",
    "classtemplate_fs.custom_fs.manual": ADMIN_PATH + "class/index.php?fs=1&phase=260&ClassID=%1",


    // ------------------------Работа с виджетами-------------------
    "widgetclass.list": ADMIN_PATH + "widget/",
    "widgetclass_fs.list": ADMIN_PATH + "widget/?fs=1",
    "widgetclass.add": ADMIN_PATH + "widget/index.php?phase=20&widget_group=%1",
    "widgetclass_fs.add": ADMIN_PATH + "widget/index.php?fs=1&phase=20&widget_group=%1",
    "widgetclass.edit": ADMIN_PATH + "widget/index.php?phase=30&widgetclass_id=%1",
    "widgetclass_fs.edit": ADMIN_PATH + "widget/index.php?fs=1&phase=30&widgetclass_id=%1",

    "widgetclass.info": ADMIN_PATH + "widget/index.php?phase=40&widgetclass_id=%1",
    "widgetclass.action": ADMIN_PATH + "widget/index.php?phase=50&widgetclass_id=%1",
    "widgetclass.drop": ADMIN_PATH + "widget/index.php?phase=60&widgetclass_id=%1&from_tree=%2",
    "widgetclass.fields": ADMIN_PATH + "field/index.php?widgetclass_id=%1",
    "widgetclass.import": NETCAT_PATH + "action.php?ctrl=admin.backup&action=import",

    "widgetclass_fs.info": ADMIN_PATH + "widget/index.php?fs=1&phase=40&widgetclass_id=%1",
    "widgetclass_fs.action": ADMIN_PATH + "widget/index.php?fs=1&phase=50&widgetclass_id=%1",
    "widgetclass_fs.drop": ADMIN_PATH + "widget/index.php?fs=1&phase=60&widgetclass_id=%1&from_tree=%2",
    "widgetclass_fs.fields": ADMIN_PATH + "field/index.php?isWidget=1&fs=1&widgetclass_id=%1",
    "widgetclass_fs.import": NETCAT_PATH + "action.php?ctrl=admin.backup&action=import",

    "widgetgroup.edit": ADMIN_PATH + "widget/index.php?phase=10&category=%1",
    "widgetfield.add": ADMIN_PATH + "field/index.php?isWidget=1&phase=2&widgetclass_id=%1",
    "widgetfield.edit": ADMIN_PATH + "field/index.php?isWidget=1&phase=4&FieldID=%1&widgetclass_id=%2",
    "widgetfield.delete": ADMIN_PATH + "field/index.php?isWidget=1&phase=6&widgetclass_id=%1&Delete[]=%2",
    "widgetgroup_fs.edit": ADMIN_PATH + "widget/index.php?fs=1&phase=10&category=%1",
    "widgetfield_fs.add": ADMIN_PATH + "field/index.php?isWidget=1&fs=1&phase=2&widgetclass_id=%1",
    "widgetfield_fs.edit": ADMIN_PATH + "field/index.php?isWidget=1&fs=1&phase=4&FieldID=%1&widgetclass_id=%2",
    "widgetfield_fs.delete": ADMIN_PATH + "field/index.php?isWidget=1&fs=1&phase=6&widgetclass_id=%1&Delete[]=%2",
    "widgets": ADMIN_PATH + "widget/admin.php",
    "widgets.add": ADMIN_PATH + "widget/admin.php?phase=20&widget_id=%1",
    "widgets.edit": ADMIN_PATH + "widget/admin.php?phase=30&widget_id=%1",
    "widgets.delete": ADMIN_PATH + "widget/admin.php?phase=60&widget_id=%1",


    // ------------------------Работа с системными таблицами-----------------------
    // Список системных таблиц
    "systemclass.list": ADMIN_PATH + "field/system.php",
    "systemclass_fs.list": ADMIN_PATH + "field/system.php?fs=1",

    // Редактирование системной таблицы
    "systemclass.edit": ADMIN_PATH + "field/system.php?phase=2&SystemTableID=%1",
    "systemclass_fs.edit": ADMIN_PATH + "field/system.php?fs=1&phase=2&SystemTableID=%1",

    // Редактирование альтернативного шаблона добавления
    "systemclass.customadd": ADMIN_PATH + "field/system.php?phase=4&myaction=1&SystemTableID=%1",
    "systemclass_fs.customadd": ADMIN_PATH + "field/system.php?fs=1&phase=4&myaction=1&SystemTableID=%1",

    // Редактирование альтернативного шаблона измениения
    "systemclass.customedit": ADMIN_PATH + "field/system.php?phase=4&myaction=2&SystemTableID=%1",
    "systemclass_fs.customedit": ADMIN_PATH + "field/system.php?fs=1&phase=4&myaction=2&SystemTableID=%1",

    // Редактирование альтернативного шаблона поиска
    "systemclass.customsearch": ADMIN_PATH + "field/system.php?phase=4&myaction=3&SystemTableID=%1",
    "systemclass_fs.customsearch": ADMIN_PATH + "field/system.php?fs=1&phase=4&myaction=3&SystemTableID=%1",

    // Список полей шаблона
    "systemclass.fields": ADMIN_PATH + "field/index.php?isSys=1&SystemTableID=%1",
    "systemclass_fs.fields": ADMIN_PATH + "field/index.php?fs=1&isSys=1&SystemTableID=%1",

    // ------------------------Работа с полями шаблона---------------------
    // Добавление поля шаблона
    "field.add": ADMIN_PATH + "field/index.php?phase=2&ClassID=%1",
    "field_fs.add": ADMIN_PATH + "field/index.php?fs=1&phase=2&ClassID=%1",

    // Редактирование поля шаблона
    "field.edit": ADMIN_PATH + "field/index.php?phase=4&FieldID=%1",
    "field_fs.edit": ADMIN_PATH + "field/index.php?fs=1&phase=4&FieldID=%1",

    // Удаление поля шаблона
    "field.delete": ADMIN_PATH + "field/index.php?phase=6&ClassID=%1&Delete[]=%2",
    "field_fs.delete": ADMIN_PATH + "field/index.php?fs=1&phase=6&ClassID=%1&Delete[]=%2",

    // --------------Работа с системными полями--------------------
    // Добавление поля шаблона
    "systemfield.add": ADMIN_PATH + "field/index.php?phase=2&isSys=1&SystemTableID=%1",
    "systemfield_fs.add": ADMIN_PATH + "field/index.php?fs=1&phase=2&isSys=1&SystemTableID=%1",

    // Редактирование поля шаблона
    "systemfield.edit": ADMIN_PATH + "field/index.php?phase=4&isSys=1&FieldID=%1",
    "systemfield_fs.edit": ADMIN_PATH + "field/index.php?fs=1&phase=4&isSys=1&FieldID=%1",

    // Удаление поля шаблона
    "systemfield.delete": ADMIN_PATH + "field/index.php?phase=6&isSys=1&SystemTableID=%1&Delete[]=%2",
    "systemfield_fs.delete": ADMIN_PATH + "field/index.php?fs=1&phase=6&isSys=1&SystemTableID=%1&Delete[]=%2",


    // ---------------------Работа со списками-------------------
    // Вывод списка
    "classificator.list": ADMIN_PATH + "classificator.php",

    // Добавление списка
    "classificator.add": ADMIN_PATH + "classificator.php?phase=1",

    // Редактирование списка
    "classificator.edit": ADMIN_PATH + "classificator.php?phase=4&ClassificatorID=%1",

    // Редактирование пользовательских настроек списка
    "classificator.custom": NETCAT_PATH + "action.php?ctrl=admin.list&action=custom&classificator_id=%1",

    // Добавление поля в пользовательские настройки списка
    "classificator.custom.add": NETCAT_PATH + "action.php?ctrl=admin.list&action=custom_add&classificator_id=%1",

    // Редактирование поля в пользовательских настройках списка
    "classificator.custom.edit": NETCAT_PATH + "action.php?ctrl=admin.list&action=custom_edit&classificator_id=%1&param=%2",

    // Ручное редактирование пользовательских настроек списка
    "classificator.custom.manual": NETCAT_PATH + "action.php?ctrl=admin.list&action=custom_manual&classificator_id=%1",

    // Удаление списка
    "classificator.delete": ADMIN_PATH + "classificator.php?phase=3&Delete%1=%1",

    // Импорт списка
    "classificator.import": ADMIN_PATH + "classificator.php?phase=12",

    // Редактирование элемента списка
    "classificator.item.edit": ADMIN_PATH + "classificator.php?phase=10&ClassificatorID=%1&IdInClassificator=%2",

    // Добавление элемента списка
    "classificator.item.add": ADMIN_PATH + "classificator.php?phase=8&ClassificatorID=%1",


    // ------------------Работа с макетами------------------
    "template.list": ADMIN_PATH + "template/index.php",
    "template_fs.list": ADMIN_PATH + "template/index.php?fs=1",

    // Добавление макета
    "template.add": ADMIN_PATH + "template/index.php?phase=20&ParentTemplateID=%1",
    "template_fs.add": ADMIN_PATH + "template/index.php?fs=1&phase=20&ParentTemplateID=%1",

    // Редактирование макета
    "template.edit": ADMIN_PATH + "template/index.php?phase=4&TemplateID=%1",
    "template_fs.edit": ADMIN_PATH + "template/index.php?fs=1&phase=4&TemplateID=%1",

    // Удаление макета
    "template.delete": ADMIN_PATH + "template/index.php?phase=6&Delete%1=%1",

    "template_fs.delete": ADMIN_PATH + "template/index.php?fs=1&phase=6&Delete%1=%1",
    // Импорт макета
    "template.import": ADMIN_PATH + "template/import.php?TemplateID=%1",
    "template_fs.import": ADMIN_PATH + "template/import.php?fs=1&TemplateID=%1",

    // Пользовательские настройки
    "template.custom": ADMIN_PATH + "template/index.php?phase=8&TemplateID=%1",
    "template_fs.custom": ADMIN_PATH + "template/index.php?fs=1&phase=8&TemplateID=%1",

    // Редактирование одной настройки
    "template.custom.edit": ADMIN_PATH + "template/index.php?phase=9&TemplateID=%1&param=%2",
    "template_fs.custom.edit": ADMIN_PATH + "template/index.php?fs=1&phase=9&TemplateID=%1&param=%2",

    // Создание новой настройки
    "template.custom.new": ADMIN_PATH + "template/index.php?phase=9&TemplateID=%1",
    "template_fs.custom.new": ADMIN_PATH + "template/index.php?fs=1&phase=9&TemplateID=%1",

    // Ручное редактирование
    "template.custom.manual": ADMIN_PATH + "template/index.php?phase=10&TemplateID=%1",
    "template_fs.custom.manual": ADMIN_PATH + "template/index.php?fs=1&phase=10&TemplateID=%1",
    "template_fs.custom_fs.edit": ADMIN_PATH + "template/index.php?fs=1&phase=9&TemplateID=%1&param=%2",
    "template_fs.custom_fs.new": ADMIN_PATH + "template/index.php?fs=1&phase=9&TemplateID=%1",
    "template_fs.custom_fs.manual": ADMIN_PATH + "template/index.php?fs=1&phase=10&TemplateID=%1",

    // Partials
    "template_fs.partials_list": NETCAT_PATH + "action.php?ctrl=admin.template_partials&action=list&fs=1&TemplateID=%1",
    "template_fs.partials_add": NETCAT_PATH + "action.php?ctrl=admin.template_partials&action=add&fs=1&TemplateID=%1",
    "template_fs.partials_edit": NETCAT_PATH + "action.php?ctrl=admin.template_partials&action=edit&fs=1&TemplateID=%1&partial=%2",
    "template_fs.partials_remove": NETCAT_PATH + "action.php?ctrl=admin.template_partials&action=remove&fs=1&TemplateID=%1&partial=%2",

    // Стандартные модули
    // адреса для пользовательских модулей могут быть заданы в файле modules/имяМодуля/url_routes.js:
    // urlDispatcher.route['url'] = 'path';
    "module.auth": NETCAT_PATH + "modules/auth/admin.php",
    "module.banner": NETCAT_PATH + "modules/banner/admin.php",
    "module.forum": NETCAT_PATH + "modules/forum/admin.php",
    "module.linkmanager": NETCAT_PATH + "modules/linkmanager/admin.php?page=%1",
    "module.netshop": NETCAT_PATH + "modules/netshop/admin.php",
    "module.search": NETCAT_PATH + "modules/search/admin.php?page=%1",
    "module.searchold": NETCAT_PATH + "modules/searchold/admin.php?page=%1",
    "module.stats": NETCAT_PATH + "modules/stats/admin.php?phase=9&cat_id=%1",
    "module.openstat": NETCAT_PATH + "modules/stats/admin.php?phase=11",
    "module.subscriber": NETCAT_PATH + "modules/subscriber/admin.php",
    "module.tagscloud": NETCAT_PATH + "modules/tagscloud/admin.php",
    "module.blog": NETCAT_PATH + "modules/blog/admin.php",
    "module.calendar": NETCAT_PATH + "modules/calendar/admin.php",

    // Действия с пользователями
    "user.list": ADMIN_PATH + "user/",
    "user.add": ADMIN_PATH + "user/register.php",
    "user.edit": ADMIN_PATH + "user/index.php?phase=4&UserID=%1",
    "user.password": ADMIN_PATH + "user/index.php?phase=6&UserID=%1",
    "user.rights": ADMIN_PATH + "user/index.php?phase=8&UserID=%1",
    "user.subscribers": ADMIN_PATH + "user/index.php?phase=15&UserID=%1",
    "user.support": ADMIN_PATH + "user/index.php?phase=30&duration=%1",

    // Рассылка по базе
    "user.mail": ADMIN_PATH + "user/MessageToAll.php",

    // Действия с группами пользователей
    "usergroup.list": ADMIN_PATH + "user/group.php",
    "usergroup.add": ADMIN_PATH + "user/group.php?phase=5",
    "usergroup.edit": ADMIN_PATH + "user/group.php?phase=3&PermissionGroupID=%1",
    "usergroup.rights": ADMIN_PATH + "user/group.php?phase=8&PermissionGroupID=%1",

    // Инструменты
    "tools.sql": ADMIN_PATH + "sql/index.php",
    "tools.usermail": ADMIN_PATH + "user/MessageToAll.php",
    "tools.backup": ADMIN_PATH + "dump.php?phase=%1",
    "tools.databackup": ADMIN_PATH + "backup.php?mode=export",
    "tools.databackup.export": NETCAT_PATH + "action.php?ctrl=admin.backup&action=export",
    "tools.databackup.import": NETCAT_PATH + "action.php?ctrl=admin.backup&action=import",
    "tools.csv.export": NETCAT_PATH + "action.php?ctrl=admin.csv.csv&action=export",
    "tools.csv.import": NETCAT_PATH + "action.php?ctrl=admin.csv.csv&action=import",
    "tools.csv.delete": NETCAT_PATH + "action.php?ctrl=admin.csv.csv&action=delete&file=%1",
    "tools.csv.import_history": NETCAT_PATH + "action.php?ctrl=admin.csv.csv&action=import_history",
    "tools.csv.rollback": NETCAT_PATH + "action.php?ctrl=admin.csv.csv&action=rollback&id=%1",
    "tools.patch": ADMIN_PATH + "patch/?phase=%1",
    "tools.activation": ADMIN_PATH + "patch/activation.php?phase=%1",
    "tools.installmodule": ADMIN_PATH + "modules/index.php?phase=5",
    "tools.totalstat": ADMIN_PATH + "report/index.php?phase=%1",
    //'tools.lastchanges': ADMIN_PATH + 'report/last.php',
    "tools.systemmessages": ADMIN_PATH + "report/system.php",
    //'tools.reportstatus': ADMIN_PATH + 'report/report.php',
    "tools.html": ADMIN_PATH + "html/",
    "tools.copy": ADMIN_PATH + "subdivision/copy.php?copy_type=%1&catalogue_id=%2&sub_id=%3",
    "trash.list": ADMIN_PATH + "trash/index.php",
    "trash.settings": ADMIN_PATH + "trash/index.php?phase=3",

    // Справка
    "help.about": ADMIN_PATH + "about/index.php",

    // Настройки системы
    "system.settings": ADMIN_PATH + "settings.php?phase=1",
    "system.edit": ADMIN_PATH + "settings.php?phase=1",

    //Настройки WYSIWYG
    "wysiwyg.ckeditor.settings": ADMIN_PATH + "wysiwyg/index.php?editor=ckeditor",
    "wysiwyg.ckeditor.panels": ADMIN_PATH + "wysiwyg/index.php?phase=3",
    "wysiwyg.ckeditor.panels.add": ADMIN_PATH + "wysiwyg/index.php?phase=4",
    "wysiwyg.ckeditor.panels.edit": ADMIN_PATH + "wysiwyg/index.php?phase=5&Wysiwyg_Panel_ID=%1",
    "wysiwyg.fckeditor.settings": ADMIN_PATH + "wysiwyg/index.php?editor=fckeditor",

    // Переадресации
    "redirect.list": NETCAT_PATH + "action.php?ctrl=admin.redirect.redirect&action=list&group=%1",
    "redirect.add": NETCAT_PATH + "action.php?ctrl=admin.redirect.redirect&action=edit&group=%1",
    "redirect.edit": NETCAT_PATH + "action.php?ctrl=admin.redirect.redirect&action=edit&id=%1",
    "redirect.delete": NETCAT_PATH + "action.php?ctrl=admin.redirect.redirect&action=delete&dgroup=%1",
    "redirect.group.add": NETCAT_PATH + "action.php?ctrl=admin.redirect.redirect&action=edit_group",
    "redirect.group.edit": NETCAT_PATH + "action.php?ctrl=admin.redirect.redirect&action=edit_group&group=%1",
    "redirect.import": NETCAT_PATH + "action.php?ctrl=admin.redirect.redirect&action=import",

    // Управление задачами
    "cron.settings": ADMIN_PATH + "crontasks.php",
    "cron.add": ADMIN_PATH + "crontasks.php?phase=1",
    "cron.edit": ADMIN_PATH + "crontasks.php?phase=4&CronID=%1",

    // Модули
    "module.list": ADMIN_PATH + "modules/index.php",
    "module.settings": ADMIN_PATH + "modules/index.php?phase=2&module_name=%1",
    "modules.settings": ADMIN_PATH + "modules/index.php?phase=2&module_name=%1", // SAME

    // Избранное
    "favorite.other": ADMIN_PATH + "subdivision/full.php?CatalogueID=%1",
    "favorite.add": ADMIN_PATH + "subdivision/favorites.php?phase=1",
    "favorite.list": ADMIN_PATH + "subdivision/favorites.php?phase=1",

    // Безопасность

    'security.filter.settings': NETCAT_PATH + 'action.php?ctrl=security_filter_admin_settings&action=show_settings&site_id=%1',
    'security.filter.log': NETCAT_PATH + 'action.php?ctrl=security_filter_admin_log&action=show_log',
    'security.filter.log.record': NETCAT_PATH + 'action.php?ctrl=security_filter_admin_log&action=log_record&security_filterlog_id=%1',
    'security.filter.log.group': NETCAT_PATH + 'action.php?ctrl=security_filter_admin_log&action=show_log&filter[Hash]=%1',

    'security.auth.captcha': NETCAT_PATH + 'action.php?ctrl=security_admin_captcha&action=show_settings&site_id=%1',
    'security.auth.2fa': NETCAT_PATH + 'action.php?ctrl=security_2fa_admin&action=show_settings&site_id=%1',

    // Диагностика сайта
    "selftest": NETCAT_PATH + "action.php?ctrl=selftest&action=index",

    1: "" // dummy entry
};
