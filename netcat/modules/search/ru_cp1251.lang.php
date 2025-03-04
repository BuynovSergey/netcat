<?php

// Права доступа к модулю
const NC_PERM_MODULE_SEARCH_BROKEN_LINKS_VIEW = 'просмотр списка битых ссылок';
const NC_PERM_MODULE_SEARCH_QUERY_LOG_VIEW = 'просмотр списка запросов';
const NC_PERM_MODULE_SEARCH_INDEXING_START = 'запуск индексирования';
const NC_PERM_MODULE_SEARCH_RULE_EDIT = 'управление правилами индексирования';
const NC_PERM_MODULE_SEARCH_SETTINGS_EDIT = 'изменение настроек модуля'; // фактически даёт полный доступ

// Языковые константы
const NETCAT_MODULE_SEARCH_TITLE = 'Поиск по сайту';
const NETCAT_MODULE_SEARCH_DESCRIPTION = 'Индексирование и поиск по сайту.';

const NETCAT_MODULE_SEARCH_SUBDIVISION_NAME = 'Поиск';
const NETCAT_MODULE_SEARCH_INFOBLOCK_FORM_NAME = 'Форма поиска';
const NETCAT_MODULE_SEARCH_INFOBLOCK_OTHERS_NAME = 'По страницам';
const NETCAT_MODULE_SEARCH_INFOBLOCK_SUBDIVISIONS_NAME = 'По разделам';
const NETCAT_MODULE_SEARCH_INFOBLOCK_GOODS_NAME = 'По товарам';

const NETCAT_MODULE_SEARCH_EVENT = 'Событие модуля поиска';

const NETCAT_MODULE_SEARCH_ADMIN_INVALID_REQUEST = "Невозможно отобразить страницу: ошибка в запросе (параметр '%s')";

const NETCAT_MODULE_SEARCH_ADMIN_INFO = 'Информация';
const NETCAT_MODULE_SEARCH_ADMIN_INDEXING = 'Индексирование';
const NETCAT_MODULE_SEARCH_ADMIN_LISTS = 'Списки';
const NETCAT_MODULE_SEARCH_ADMIN_SETTINGS = 'Настройки';

const NETCAT_MODULE_SEARCH_ADMIN_QUERIES = 'Запросы';
const NETCAT_MODULE_SEARCH_ADMIN_SYNONYMS = 'Синонимы';
const NETCAT_MODULE_SEARCH_ADMIN_BROKENLINKS = 'Битые ссылки';

const NETCAT_MODULE_SEARCH_ADMIN_GENERAL_SETTINGS = 'Общие';
const NETCAT_MODULE_SEARCH_ADMIN_VIEW_SETTINGS = 'Отображение';
const NETCAT_MODULE_SEARCH_ADMIN_RULES_SETTINGS = 'Правила';
const NETCAT_MODULE_SEARCH_ADMIN_SYSTEM_SETTINGS = 'Системные';

const NETCAT_MODULE_SEARCH_ADMIN_STAT_CHECK_CRONTAB = 'В очереди индексирования имеются невыполненные задания. Проверьте, запускается ли в cron скрипт переиндексирования.';

const NETCAT_MODULE_SEARCH_WIDGET_BROKEN_LINKS = 'битых<br>ссылок';
const NETCAT_MODULE_SEARCH_WIDGET_CHECK_CRONTAB = 'в очереди невыполненные задания';
const NETCAT_MODULE_SEARCH_WIDGET_NO_RULES = 'нет правил индексирования';
const NETCAT_MODULE_SEARCH_WIDGET_CONFIGURATION_ERRORS = 'имеются ошибки конфигурации';

const NETCAT_MODULE_SEARCH_ADMIN_STAT_HEADER = 'Статистика';
const NETCAT_MODULE_SEARCH_ADMIN_STAT_NUM_DOCUMENTS = 'Проиндексировано cтраниц';
const NETCAT_MODULE_SEARCH_ADMIN_STAT_NUM_TERMS = 'Слов в индексе';
const NETCAT_MODULE_SEARCH_ADMIN_STAT_NUM_SITEMAP_URLS = 'Страниц в sitemap.xml';
const NETCAT_MODULE_SEARCH_ADMIN_STAT_NUM_QUERIES_TODAY = 'Обращений к поиску сегодня';
const NETCAT_MODULE_SEARCH_ADMIN_STAT_NUM_QUERIES_YESTERDAY = 'Обращений к поиску вчера';
const NETCAT_MODULE_SEARCH_ADMIN_SHOW_QUERY_LOG = 'показать все';
const NETCAT_MODULE_SEARCH_ADMIN_STAT_LAST_QUERIES = 'Последние запросы к поисковой системе';
const NETCAT_MODULE_SEARCH_ADMIN_STAT_MOST_POPULAR = 'Самые популярные запросы к поисковой системе';
const NETCAT_MODULE_SEARCH_ADMIN_STAT_MOST_POPULAR_NO_RESULTS = 'Самые популярные запросы к поисковой системе, по которым ничего не найдено';
const NETCAT_MODULE_SEARCH_ADMIN_STAT_INDEXING = 'Индексирование';
const NETCAT_MODULE_SEARCH_ADMIN_STAT_INDEX = 'Индексировать';
const NETCAT_MODULE_SEARCH_ADMIN_STAT_INDEXING_NOW = 'идёт индексирование';
const NETCAT_MODULE_SEARCH_ADMIN_STAT_INDEX_IN_BACKGROUND = 'в фоне';
const NETCAT_MODULE_SEARCH_ADMIN_STAT_INDEX_IN_BROWSER = 'в окне';
const NETCAT_MODULE_SEARCH_ADMIN_STAT_INDEX_AREA = 'Индексировать область';
const NETCAT_MODULE_SEARCH_ADMIN_STAT_INDEX_AREA_IN_BACKGROUND = 'Индексировать в фоне';
const NETCAT_MODULE_SEARCH_ADMIN_STAT_INDEX_AREA_IN_BROWSER = 'Индексировать в окне';

const NETCAT_MODULE_SEARCH_ADMIN_STATUS_DELETED = 'Записи удалены.';

const NETCAT_MODULE_SEARCH_ADMIN_QUERY_FILTER = 'Фильтр запросов';
const NETCAT_MODULE_SEARCH_ADMIN_QUERY_FRAGMENT = 'Фрагмент';
const NETCAT_MODULE_SEARCH_ADMIN_QUERY_TIME_PERIOD = 'Временной промежуток';
const NETCAT_MODULE_SEARCH_ADMIN_QUERY_TIME_PERIOD_FROM = 'с';
const NETCAT_MODULE_SEARCH_ADMIN_QUERY_TIME_PERIOD_TO = 'по';
const NETCAT_MODULE_SEARCH_ADMIN_QUERY_TIME_PERIOD_CLEAR = 'очистить';
const NETCAT_MODULE_SEARCH_ADMIN_QUERY_RESULTS = 'Результаты';
const NETCAT_MODULE_SEARCH_ADMIN_QUERY_RESULTS_ALL = 'не важно';
const NETCAT_MODULE_SEARCH_ADMIN_QUERY_RESULTS_NONE = 'нет';
const NETCAT_MODULE_SEARCH_ADMIN_QUERY_RESULTS_MATCHED = 'есть';
const NETCAT_MODULE_SEARCH_ADMIN_QUERY_SUBMIT_FILTER = 'Показать';
const NETCAT_MODULE_SEARCH_ADMIN_QUERY_PER_PAGE = 'по %s';
const NETCAT_MODULE_SEARCH_ADMIN_QUERY_SORT_BY_RESULT_COUNT = 'по количеству запросов';
const NETCAT_MODULE_SEARCH_ADMIN_QUERY_SORT_BY_TIME = 'по хронологии';
const NETCAT_MODULE_SEARCH_ADMIN_QUERY_SORT_BY_QUERY = 'по алфавиту';
const NETCAT_MODULE_SEARCH_ADMIN_QUERY_STRING = 'Запрос';
const NETCAT_MODULE_SEARCH_ADMIN_QUERY_COUNT = 'Запросов';
const NETCAT_MODULE_SEARCH_ADMIN_QUERY_LAST_QUERY = 'Последний запрос';
const NETCAT_MODULE_SEARCH_ADMIN_QUERY_LAST_QUERY_TIME = 'Время';
const NETCAT_MODULE_SEARCH_ADMIN_QUERY_LAST_QUERY_RESULT_COUNT = 'Найдено';
const NETCAT_MODULE_SEARCH_ADMIN_QUERY_LAST_QUERY_USER = 'IP, пользователь';
const NETCAT_MODULE_SEARCH_ADMIN_QUERY_NO_ENTRIES = 'Запросов, удовлетворяющих указанным параметрам, не найдено';
const NETCAT_MODULE_SEARCH_ADMIN_QUERY_PREV_PAGE = 'Предыдущая страница';
const NETCAT_MODULE_SEARCH_ADMIN_QUERY_NEXT_PAGE = 'Следующая страница';
const NETCAT_MODULE_SEARCH_ADMIN_QUERY_BACK_TO_LIST = 'Назад к списку';

const NETCAT_MODULE_SEARCH_ADMIN_QUERY_ALL_QUERIES = "Все запросы &laquo;<span class='q'>%s</span>&raquo;";
const NETCAT_MODULE_SEARCH_ADMIN_QUERY_OPEN_RESULTS_HINT = 'для просмотра результатов запроса нажмите на число в колонке &laquo;Найдено&raquo;';
const NETCAT_MODULE_SEARCH_ADMIN_QUERY_OPEN_RESULTS_LINK_HINT = 'перейти к результатам поиска';
const NETCAT_MODULE_SEARCH_ADMIN_QUERY_OPEN_LOG_LINK_HINT = 'показать все обращения по этому запросу';
const NETCAT_MODULE_SEARCH_ADMIN_QUERY_AREA = 'Область поиска';
const NETCAT_MODULE_SEARCH_ADMIN_QUERY_AREA_INCLUDED = 'Включить в результаты';
const NETCAT_MODULE_SEARCH_ADMIN_QUERY_AREA_EXCLUDED = 'Исключить из результатов';
const NETCAT_MODULE_SEARCH_ADMIN_QUERY_TIME = 'Время';
const NETCAT_MODULE_SEARCH_ADMIN_QUERY_RESULTS_COUNT = 'Найдено';
const NETCAT_MODULE_SEARCH_ADMIN_QUERY_IP = 'IP';
const NETCAT_MODULE_SEARCH_ADMIN_QUERY_USER = 'Пользователь';
const NETCAT_MODULE_SEARCH_ADMIN_QUERY_LOG_DISABLED = "Сохранение истории запросов отключено в <a href='%s' target='_top'>настройках модуля</a>.";

const NETCAT_MODULE_SEARCH_ADMIN_EXTENSION = 'Расширение';
const NETCAT_MODULE_SEARCH_ADMIN_EXTENSIONS = 'Расширения';
const NETCAT_MODULE_SEARCH_ADMIN_EXTENSION_INTERFACE = 'Интерфейс расширения';
const NETCAT_MODULE_SEARCH_ADMIN_SEARCH_PROVIDER = 'Служба поиска';
const NETCAT_MODULE_SEARCH_ADMIN_SEARCH_PROVIDER_ANY = 'Любая';
const NETCAT_MODULE_SEARCH_ADMIN_EXTENSION_ACTION = 'Действие';
const NETCAT_MODULE_SEARCH_ADMIN_EXTENSION_ACTION_ANY = 'Поиск и индексирование';
const NETCAT_MODULE_SEARCH_ADMIN_EXTENSION_ACTION_SEARCHING = 'Поиск';
const NETCAT_MODULE_SEARCH_ADMIN_EXTENSION_ACTION_INDEXING = 'Индексирование';
const NETCAT_MODULE_SEARCH_ADMIN_EXTENSION_CLASS = 'Класс расширения';
const NETCAT_MODULE_SEARCH_ADMIN_EXTENSION_CONTENT_TYPE = 'MIME-тип';
const NETCAT_MODULE_SEARCH_ADMIN_EXTENSION_CONTENT_TYPE_ANY = 'Любой';
const NETCAT_MODULE_SEARCH_ADMIN_EXTENSION_PRIORITY = 'Приоритет';
const NETCAT_MODULE_SEARCH_ADMIN_EXTENSION_ENABLED = 'Расширение включено';
const NETCAT_MODULE_SEARCH_ADMIN_EXTENSIONS_EMPTY_LIST = 'Расширений нет.';
const NETCAT_MODULE_SEARCH_ADMIN_EXTENSIONS_ADD = 'Добавить расширение';
const NETCAT_MODULE_SEARCH_ADMIN_EXTENSIONS_CONFIRM_DELETE_WARNING =
    "<h3>ВНИМАНИЕ!</h3><p><b>Удаление записи о расширении может привести к полной потере работоспособности 
    модуля поиска или неправильным результатам при поиске.</b></p>Пожалуйста, подтвердите действие.";

const NETCAT_MODULE_SEARCH_ADMIN_EXTENSIONS_CONFIRM_DELETE_OK = 'Я нахожусь в здравом уме и твердой памяти';
const NETCAT_MODULE_SEARCH_ADMIN_EXTENSION_NOT_FOUND = 'Расширение (ID=%s) не найдено';

const NETCAT_MODULE_SEARCH_ADMIN_SYNONYMS_FIELD_CAPTION = 'Синонимы (по одному в строке, заглавными буквами)';
const NETCAT_MODULE_SEARCH_ADMIN_SYNONYMS_DO_NOT_APPLY_FILTERS = 'Не обрабатывать введённые слова (не рекомендуется)';
const NETCAT_MODULE_SEARCH_ADMIN_SYNONYMS_DO_NOT_APPLY_FILTERS_HELP =
    "<p>При сохранении списка синонимов к нему будут применены фильтры для текущего языка,
    например фильтр стоп-слов и морфологический анализатор (или стеммер).</p>
    <p>Таким образом, слова будут приведены к базовым формам, а игнорируемые при поиске слова
    будут удалены из списка, например список слов <code>ЗДАНИЯ; ДОМА</code>
    при включенном морфологическом анализаторе будет сохранён как <code>ЗДАНИЕ; ДОМ; ДОМА</code>
    (слову <code>ДОМА</code> соответствуют две базовые формы — наречие и существительное).</p>
    <p>Обратите внимание, что при настройках по умолчанию синонимы подставляются в запросы
    после фильтрации стоп-слов и приведения к базовой форме, поэтому включение данной опции
    <strong>не&nbsp;рекомендуется</strong>.";

const NETCAT_MODULE_SEARCH_ADMIN_SYNONYM_LIST_MUST_HAVE_AT_LEAST_TWO_WORDS =
    "Список синонимов должен содержать по крайней мере два слова. Если вы указали несколько слов, возможно, какие-то из них внесены в список стоп-слов.";

const NETCAT_MODULE_SEARCH_ADMIN_SYNONYM_SAVE_RESULT =
    "Список слов после преобразований был сохранён как &laquo;%s&raquo;. Если результат Вас 
    не устраивает, <a href='%s' target='_top'>отредактируйте список слов</a>, перед сохранением отметив 
    опцию &laquo;Не обрабатывать введённые слова&raquo;.";


const NETCAT_MODULE_SEARCH_ADMIN_RECORD_NOT_FOUND = 'Неправильный идентификатор (ID=%s)';
const NETCAT_MODULE_SEARCH_ADMIN_LANGUAGE_ANY = 'Любой';
const NETCAT_MODULE_SEARCH_ADMIN_LANGUAGE_ANY_LANGUAGE = 'Любой язык';

const NETCAT_MODULE_SEARCH_ADMIN_STOPWORDS = 'Стоп-слова';
const NETCAT_MODULE_SEARCH_ADMIN_STOPWORD = 'Стоп-слово';
const NETCAT_MODULE_SEARCH_ADMIN_STOPWORD_FIELD_CAPTION = 'Стоп-слово (ЗАГЛАВНЫМИ буквами)';
const NETCAT_MODULE_SEARCH_ADMIN_STOPWORD_HAS_NO_BASEFORM =
    "Введённое вами слово <code>%s</code> отфильтровывается до применения фильтра стоп-слов (вероятно, является слишком коротким). Хотите сохранить это слово?";
const NETCAT_MODULE_SEARCH_ADMIN_STOPWORD_HAS_ONE_BASEFORM = 'Введённое вами слово <code>%s</code> имеет базовую форму <code>%s</code>.';
const NETCAT_MODULE_SEARCH_ADMIN_STOPWORD_HAS_SEVERAL_BASEFORMS = 'Введённое вами слово <code>%s</code> имеет несколько базовых форм.';
const NETCAT_MODULE_SEARCH_ADMIN_STOPWORD_BASEFORM_QUESTION = 'Какую форму слова следует сохранить в качестве стоп-слова?';
const NETCAT_MODULE_SEARCH_ADMIN_STOPWORD_AS_ENTERED = '(введённый вариант &mdash; не рекомендуется)';

const NETCAT_MODULE_SEARCH_ADMIN_RULE = 'Правило';
const NETCAT_MODULE_SEARCH_ADMIN_RULE_NAME = 'Название';
const NETCAT_MODULE_SEARCH_ADMIN_RULE_SITE = 'Сайт';
const NETCAT_MODULE_SEARCH_ADMIN_RULE_AREA = 'Область';
const NETCAT_MODULE_SEARCH_ADMIN_RULE_SCHEDULE = 'Расписание';
const NETCAT_MODULE_SEARCH_ADMIN_RULE_MUST_HAVE_INTERVAL_TYPE = 'Не указан интервал выполнения расписания';
const NETCAT_MODULE_SEARCH_ADMIN_RULE_AREA_TO_INDEX = 'Индексировать';
const NETCAT_MODULE_SEARCH_ADMIN_RULE_AREA_WHOLE_SITE = 'весь сайт';
const NETCAT_MODULE_SEARCH_ADMIN_RULE_AREA_AREAS = 'области сайта';
const NETCAT_MODULE_SEARCH_ADMIN_RULE_FREQUENCY = 'Частота переиндексирования';
const NETCAT_MODULE_SEARCH_ADMIN_RULE_INTERVAL_DAILY = 'ежедневно в %s';
const NETCAT_MODULE_SEARCH_ADMIN_RULE_INTERVAL_EVERY_N_MINUTES = 'каждые %s минут, начиная с %s';
const NETCAT_MODULE_SEARCH_ADMIN_RULE_INTERVAL_EVERY_N_HOURS = 'каждые %s часов, начиная с %s';
const NETCAT_MODULE_SEARCH_ADMIN_RULE_INTERVAL_EVERY_N_DAYS = 'каждые %s дней в %s';
const NETCAT_MODULE_SEARCH_ADMIN_RULE_INTERVAL_EVERY_X_DAY = 'каждое %s число месяца в %s';
const NETCAT_MODULE_SEARCH_ADMIN_RULE_INTERVAL_ON_REQUEST = 'только по запросу';
const NETCAT_MODULE_SEARCH_ADMIN_RULE_START_URL = 'Начальный адрес';
const NETCAT_MODULE_SEARCH_ADMIN_RULE_NONEXISTENT_SITE = 'Несуществующий сайт (ID=%s)';

const NETCAT_MODULE_SEARCH_ADMIN_RULE_AREA_DESCRIPTION_ALLSITES = 'Все сайты';
const NETCAT_MODULE_SEARCH_ADMIN_RULE_AREA_DESCRIPTION_SITE = "Сайт &laquo;<a href='%s' target='_top'>%s</a>&raquo;";
const NETCAT_MODULE_SEARCH_ADMIN_RULE_AREA_DESCRIPTION_SUB_ONLY = "Основную страницу раздела &laquo;<a href='%s' target='_top'>%s</a>&raquo;";
const NETCAT_MODULE_SEARCH_ADMIN_RULE_AREA_DESCRIPTION_SUB_CHILDREN = "Все страницы раздела &laquo;<a href='%s' target='_top'>%s</a>&raquo;";
const NETCAT_MODULE_SEARCH_ADMIN_RULE_AREA_DESCRIPTION_SUB_DESCENDANTS = "Все страницы и подразделы раздела &laquo;<a href='%s' target='_top'>%s</a>&raquo;";
const NETCAT_MODULE_SEARCH_ADMIN_RULE_AREA_DESCRIPTION_PAGE = "Страницу &laquo;<a href='%s' target='_blank'>%s</a>&raquo;";
const NETCAT_MODULE_SEARCH_ADMIN_RULE_AREA_DESCRIPTION_INCLUDED = 'Индексировать';
const NETCAT_MODULE_SEARCH_ADMIN_RULE_AREA_DESCRIPTION_EXCLUDED = 'Не индексировать';

const NETCAT_MODULE_SEARCH_ADMIN_RULE_INTERVAL_MINUTE = 'минуту минут минуты';
const NETCAT_MODULE_SEARCH_ADMIN_RULE_INTERVAL_HOUR = 'час часов часа';
const NETCAT_MODULE_SEARCH_ADMIN_RULE_INTERVAL_DAY = 'день дней дня';
const NETCAT_MODULE_SEARCH_ADMIN_RULE_EVERY_SEVERAL = 'каждые';
const NETCAT_MODULE_SEARCH_ADMIN_RULE_EVERY_SINGLE_MASCULINE = 'каждый';
const NETCAT_MODULE_SEARCH_ADMIN_RULE_EVERY_SINGLE_FEMININE = 'каждую';

const NETCAT_MODULE_SEARCH_ADMIN_NO_RULES = "Отсутствуют правила индексирования. Для работы модуля необходимо <a href='%s' target='_top'>создать</a> хотя бы одно правило.";
const NETCAT_MODULE_SEARCH_ADMIN_ADD_RULE = 'Добавить правило';

const NETCAT_MODULE_SEARCH_ADMIN_UNNAMED_RULE = 'Без названия';
const NETCAT_MODULE_SEARCH_ADMIN_RULE_SHOW_DETAILS = 'подробнее';
const NETCAT_MODULE_SEARCH_ADMIN_RULE_LAST_RUN = 'Последнее индексирование';
const NETCAT_MODULE_SEARCH_ADMIN_RULE_NEVER_RUN = 'Правило ни разу не выполнялось';
const NETCAT_MODULE_SEARCH_ADMIN_RULE_LAST_RUN_DURATION = 'продолжительность: ';
const NETCAT_MODULE_SEARCH_ADMIN_RULE_LAST_RUN_NOT_FINISHED = 'не окончено';
const NETCAT_MODULE_SEARCH_ADMIN_RULE_RUN_IN_BROWSER = 'Индексировать в реальном времени';
const NETCAT_MODULE_SEARCH_ADMIN_RULE_RUN_IN_BACKGROUND = 'Индексировать в фоновом режиме';
const NETCAT_MODULE_SEARCH_ADMIN_RULE_EDIT_LINK = 'Редактировать';
const NETCAT_MODULE_SEARCH_ADMIN_RULE_QUEUE_LOADING = 'Загрузка...';
const NETCAT_MODULE_SEARCH_ADMIN_RULE_QUEUED = 'Правило будет выполнено в фоновом режиме';
const NETCAT_MODULE_SEARCH_ADMIN_RULE_QUEUE_ERROR = 'Возникла ошибка при постановке правила в очередь переиндексирования';

const NETCAT_MODULE_SEARCH_ADMIN_RULE_STATISTICS = 'Проиндексировано документов: %d, удалено документов: %d, проверено ссылок: %d';

const NETCAT_MODULE_SEARCH_ADMIN_INDEXING_TITLE = "Индексирование сайта";
const NETCAT_MODULE_SEARCH_ADMIN_INDEXING_IN_PROGRESS_ERROR =
        "В настоящее время производится переиндексирование области &laquo;%s&raquo;.<br />
        Запуск другого процесса индексирования невозможен; попробуйте позднее.";
const NETCAT_MODULE_SEARCH_ADMIN_INDEXING_IN_PROGRESS = 'Идёт переиндексирование. Не закрывайте это окно до завершения процесса.';
const NETCAT_MODULE_SEARCH_ADMIN_INDEXING_DONE = 'Индексирование завершено.';
const NETCAT_MODULE_SEARCH_ADMIN_INDEXING_DONE_STATS =
    "Затраченное время: %s<br />
    Проиндексировано документов: %d<br />
    Проверено ссылок: %d<br />
    Ссылок на несуществующие документы: %d<br />
    Удалено документов: %d<br />";

const NETCAT_MODULE_SEARCH_ADMIN_RESULTS_MANY = 'Показаны результаты %d&mdash;%d из %d';
const NETCAT_MODULE_SEARCH_ADMIN_RESULTS_ONE = 'Показан результат %d из %d';

const NETCAT_MODULE_SEARCH_ADMIN_MINUTES = '%d мин';
const NETCAT_MODULE_SEARCH_ADMIN_HOURS_MINUTES = '%d ч %d мин';

const NETCAT_MODULE_SEARCH_ADMIN_BULLET = '&mdash;';

const NETCAT_MODULE_SEARCH_ADMIN_BACK = 'Назад';
const NETCAT_MODULE_SEARCH_ADMIN_ADD = 'Добавить';
const NETCAT_MODULE_SEARCH_ADMIN_SAVE = 'Сохранить';
const NETCAT_MODULE_SEARCH_ADMIN_EDIT = 'Изменить';
const NETCAT_MODULE_SEARCH_ADMIN_COPY = 'Скопировать';
const NETCAT_MODULE_SEARCH_ADMIN_DELETE = 'Удалить';
const NETCAT_MODULE_SEARCH_ADMIN_DELETE_SELECTED = 'Удалить выбранное';
const NETCAT_MODULE_SEARCH_ADMIN_ID = 'ID';
const NETCAT_MODULE_SEARCH_ADMIN_ACTIONS = 'Действия';

const NETCAT_MODULE_SEARCH_ADMIN_FILTER = 'Найти';
const NETCAT_MODULE_SEARCH_ADMIN_FILTER_RESET = 'Сбросить фильтр';

const NETCAT_MODULE_SEARCH_ADMIN_LANGUAGE = 'Язык';
const NETCAT_MODULE_SEARCH_ADMIN_EMPTY_LIST = 'Список пуст';

const NETCAT_MODULE_SEARCH_ADMIN_SAVE_OK = 'Данные сохранены';
const NETCAT_MODULE_SEARCH_ADMIN_SAVE_ERROR = 'Ошибка при сохранении данных';

const NETCAT_MODULE_SEARCH_ADMIN_SETTING_SEARCH_DISABLED = "Поиск по сайту отключен. Для индексирования сайта необходимо <a href='%s' target='%s'>разрешить индексирование и поиск по сайту</a>.";

const NETCAT_MODULE_SEARCH_ADMIN_SETTING_INDEXING = 'Настройки индексирования';

const NETCAT_MODULE_SEARCH_ADMIN_SETTING_ENABLE_SEARCH = 'разрешить индексирование и поиск по сайту';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_DISABLE_SECTION_INDEXING = 'Запретить индексирование страниц по регулярному выражению';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_MINIMUM_WORD_LENGTH = 'Минимальная длина слова для индексирования %s символов';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_USE_STOPWORDS = "использовать списки <a href='%s' target='_top'>стоп-слов</a>";
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_USE_ROBOTS_TXT = 'учитывать инструкции robots.txt';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_MAX_DOCUMENT_LENGTH = 'Максимальный размер индексируемых страниц %s байт';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_CRAWLER_DELAY = 'Пауза между запросами поискового робота %s секунд';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_IGNORE_NUMBERS = 'не учитывать цифры при индексировании и поиске';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_CHECK_LINKS = 'проверять ссылки на сайте';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_CHECK_EXTERNAL_LINKS = 'проверять внешние ссылки';

const NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_CORRECTION = 'Исправление запросов, не давших результатов';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_CORRECTION_ENABLED = 'Исправлять запросы, не давшие результатов';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_CORRECTION_PHRASES = 'искать фразы как обычный набор слов';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_CORRECTION_BREAK_WORDS_UP = 'искать пропущенные пробелы';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_CORRECTION_LAYOUT = 'исправлять раскладку клавиатуры';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_CORRECTION_FUZZY = "использовать нечёткий поиск для слов, отсутствующих в словарях (должен быть включён <a href='#fuzzy' class='internal on_page'>нечёткий поиск</a>)";
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_CORRECTION_MAXIMUM_QUERY_LENGTH = 'Максимальная длина запроса для исправления %s слов';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_CORRECTION_SIMILARITY_FACTOR = 'Коэффициент похожести, используемый при исправлении запросов: %s';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_LOG = 'История запросов';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_LOG_ENABLED = 'Включить историю запросов';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_LOG_PURGE = 'Удалять запросы';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_LOG_PURGE_NEVER = 'никогда';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_LOG_PURGE_BEFORE = 'раньше';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_LOG_PURGE_BEFORE_HOURS = 'часов';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_LOG_PURGE_BEFORE_DAYS = 'дней';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_LOG_PURGE_BEFORE_MONTHS = 'месяцев';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_LOG_PURGE_NOW = 'Очистить список поисковых запросов';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_LOG_PURGE_NOW_EVERYTHING = 'полностью';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_LOG_PURGE_NOW_SUBMIT = 'Очистить';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_LOG_PURGED = 'История запросов очищена.';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_FEATURES = 'Пользовательские поисковые запросы';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_DEFAULT_OPERATOR = 'Оператор по умолчанию';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_DEFAULT_OPERATOR_AND = 'логическое И';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_DEFAULT_OPERATOR_OR = 'логическое ИЛИ';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_TERM_BOOST = 'разрешить указывать вес слов';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_PROXIMITY_SEARCH = 'разрешить поиск с указанием расстояния между словами';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_WILDCARD_SEARCH = 'разрешить поиск по шаблону';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_RANGE_SEARCH = 'разрешить поиск по интервалу значений';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_FUZZY_SEARCH = 'разрешить нечёткий поиск';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_FIELD_SEARCH = 'разрешить поиск по полю';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_FIELD_INVALID_NAME =
    "Введено некорректное имя поля.\n\nИмя поля может состоять только из латинских букв, цифр и символа подчёркивания, и не должно совпадать с зарезервированными именами (title, content, last_updated, language).";

const NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_RESULTS = 'Отображение результатов';
const NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_SHOW_MATCHED_FRAGMENT = 'показывать фрагмент текста в списке найденных страниц';
const NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_HIGHLIGHT_MATCHED = 'выделять слова из запроса полужирным начертанием';
const NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_ALLOW_FIELD_SORT = 'разрешать сортировку по полям (по дате/времени)';
const NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_OPEN_LINKS_IN_NEW_WINDOW = 'открывать ссылки в новом окне';
const NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_RESULTS_TITLE_WORD_COUNT = 'Максимальное количество слов в заголовке результатов';
const NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_RESULTS_FRAGMENT_WORD_COUNT = 'Максимальное количество слов в фрагменте текста';
const NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_MAX_PREVIEW_TEXT_LENGTH = 'Максимальная длина текста, сохраняемого для показа совпавших фрагментов';
const NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_KBYTES = 'КБайт';

const NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_ADVANCED_SEARCH = 'Расширенный поиск';
const NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_ENABLE_ADVANCED_SEARCH_FORM = 'разрешить расширенный поиск';
const NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_ADVANCED_SEARCH_FORM_OPTIONS = 'Включить в форму расширенного поиска поля';
const NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_ADVANCED_SEARCH_EXCLUDE = 'исключить страницы, где встречаются слова';
const NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_ADVANCED_SEARCH_FIELD = 'расположение слов';
const NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_ADVANCED_SEARCH_DATETIME = 'дата обновления';

const NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_QUERY_SUGGEST = 'Автозаполнение строки запроса';
const NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_QUERY_SUGGEST_TITLES = 'заголовки страниц';
const NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_QUERY_SUGGEST_QUERIES = 'запросы';
const NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_QUERY_SUGGEST_DISABLED = 'не применять';
const NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_QUERY_SUGGEST_MINIMUM_LENGTH = 'Минимальная длина запроса для срабатывания автозаполнения';
const NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_QUERY_SUGGEST_NUMBER_OF_HITS = 'Количество результатов в выпадающем списке';
const NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_QUERY_SUGGEST_TITLES_SEARCH_IN_INDEX = 'искать все формы введённых слов';
const NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_QUERY_SUGGEST_TITLES_SEARCH_AS_PHRASE = 'строгий порядок слов (искать как фразу)';

const NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_FORM_TEMPLATES = 'Шаблоны отображения для формы поиска по умолчанию';
const NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_ADVANCED_FORM_TEMPLATE = 'Шаблон отображения расширенной формы поиска';
const NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_FORM_TEMPLATES_MOBILE = 'Шаблоны отображения для формы поиска по умолчанию (мобильный)';
const NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_ADVANCED_FORM_TEMPLATE_MOBILE = 'Шаблон отображения расширенной формы поиска (мобильный)';
const NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_FORM_TEMPLATES_RESPONSIVE = 'Шаблоны отображения для формы поиска по умолчанию (адаптивный)';
const NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_ADVANCED_FORM_TEMPLATE_RESPONSIVE = 'Шаблон отображения расширенной формы поиска (адаптивный)';

const NETCAT_MODULE_SEARCH_ADMIN_SETTINGS_SAVE = 'Сохранить настройки';
const NETCAT_MODULE_SEARCH_ADMIN_SETTINGS_SAVED = 'Настройки сохранены.';

const NETCAT_MODULE_SEARCH_ADMIN_SETTINGS_INCORRECT_PROVIDER_CLASS = 'SearchProvider: класс %s должен реализовывать интерфейс nc_search_provider!';
const NETCAT_MODULE_SEARCH_ADMIN_SETTINGS_PROVIDER_CLASS_NOT_FOUND = 'SearchProvider: класс %s не найден';
const NETCAT_MODULE_SEARCH_ADMIN_SETTINGS_PROVIDER_CLASS_INITIALIZATION_ERROR = 'SearchProvider: возникла ошибка при инициализации класса %s (%s)';

const NETCAT_MODULE_SEARCH_ADMIN_FIELDS = 'HTML-документы';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_DOCUMENT_AREAS = 'Область индексирования';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_DOCUMENT_CONTENT = 'Область, индексируемая на HTML-страницах (используется первое совпавшее правило)';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_DOCUMENT_NOINDEX = 'Области, содержимое которых не индексируется';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_TAG_WEIGHT = 'Веса тэгов';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_TAGS = 'Тэги';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_TAGS_HAVE_WEIGHT = 'имеют вес';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_TAGS_DELETE = 'удалить';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_TITLE_TAG_HAS_WEIGHT = 'Тэг <code>TITLE</code> имеет вес';
const NETCAT_MODULE_SEARCH_ADMIN_SETTING_FIELDS_SAVED = "Изменения вступят в силу при следующем переиндексировании. <a href='%s' target='_top'>Перейти в раздел &laquo;Индексирование&raquo;</a>.";
const NETCAT_MODULE_SEARCH_ADMIN_DATA_EXTRACTION = 'Извлечение данных';
const NETCAT_MODULE_SEARCH_ADMIN_DATA_EXTRACTION_FIELD_NAME = 'Имя поля';
const NETCAT_MODULE_SEARCH_ADMIN_DATA_EXTRACTION_FIELD_QUERY = 'Область HTML-документа';
const NETCAT_MODULE_SEARCH_ADMIN_DATA_EXTRACTION_FIELD_WEIGHT = 'Вес';
const NETCAT_MODULE_SEARCH_ADMIN_DATA_EXTRACTION_FIELD_TYPE = 'Тип данных';
const NETCAT_MODULE_SEARCH_ADMIN_DATA_EXTRACTION_FIELD_TYPE_STRING = 'Строка';
const NETCAT_MODULE_SEARCH_ADMIN_DATA_EXTRACTION_FIELD_TYPE_INTEGER = 'Целое число';
const NETCAT_MODULE_SEARCH_ADMIN_DATA_EXTRACTION_ADD_FIELD = 'Добавить поле';
const NETCAT_MODULE_SEARCH_ADMIN_DATA_EXTRACTION_FIELD_IS_INDEXED = 'поле участвует в поиске';
const NETCAT_MODULE_SEARCH_ADMIN_DATA_EXTRACTION_FIELD_IS_SORTABLE = 'разрешить сортировку по полю';
const NETCAT_MODULE_SEARCH_ADMIN_DATA_EXTRACTION_FIELD_IS_NORMALIZED = 'нормализовывать текст';
const NETCAT_MODULE_SEARCH_ADMIN_DATA_EXTRACTION_FIELD_IS_RETRIEVABLE = 'значение доступно в результатах поиска';

const NETCAT_MODULE_SEARCH_ADMIN_NO_BROKEN_LINKS = 'При индексировании не было обнаружено некорректных ссылок.';
const NETCAT_MODULE_SEARCH_ADMIN_BROKEN_LINK_GROUP_BY = 'Группировка по';
const NETCAT_MODULE_SEARCH_ADMIN_BROKEN_LINK_GROUP_BY_URL = 'ссылкам';
const NETCAT_MODULE_SEARCH_ADMIN_BROKEN_LINK_GROUP_BY_REFERRER = 'по ссылающимся страницам';
const NETCAT_MODULE_SEARCH_ADMIN_BROKEN_LINK_PREV_PAGE = 'Предыдущая страница';
const NETCAT_MODULE_SEARCH_ADMIN_BROKEN_LINK_NEXT_PAGE = 'Следующая страница';
const NETCAT_MODULE_SEARCH_ADMIN_BROKEN_LINK_EDIT = 'редактировать';
const NETCAT_MODULE_SEARCH_ADMIN_BROKEN_LINKS_MENU_ITEM = 'Неработающие ссылки';
const NETCAT_MODULE_SEARCH_ADMIN_BROKEN_LINKS_REFERRER_LIMIT = 'Показано только %s ссылающихся страниц.';

const NETCAT_MODULE_SEARCH_ADMIN_EVENT_LOG = 'События';
const NETCAT_MODULE_SEARCH_ADMIN_EVENT_LOG_FILTER = 'Журнал событий';
const NETCAT_MODULE_SEARCH_ADMIN_EVENT_LOG_SETTINGS = 'Настройки журнала событий';
const NETCAT_MODULE_SEARCH_ADMIN_EVENT_LOG_SHOW_SETTINGS = 'Показать настройки журнала событий';
const NETCAT_MODULE_SEARCH_ADMIN_EVENT_LOG_LEVEL = 'Сохранять в журнале события следующих типов';
const NETCAT_MODULE_SEARCH_ADMIN_EVENT_LOG_DELETE_PERIOD = 'Хранить записи в течение %s дней';
const NETCAT_MODULE_SEARCH_ADMIN_EVENT_LOG_DELETE_ALL = 'Очистить журнал';
const NETCAT_MODULE_SEARCH_ADMIN_EVENT_LOG_DELETED = 'Журнал событий очищен.';
const NETCAT_MODULE_SEARCH_ADMIN_EVENT_LOG_EMPTY = 'Журнал событий пуст.';
const NETCAT_MODULE_SEARCH_ADMIN_EVENT_LOG_TIME = 'Время';
const NETCAT_MODULE_SEARCH_ADMIN_EVENT_LOG_TYPE = 'Тип события';
const NETCAT_MODULE_SEARCH_ADMIN_EVENT_LOG_MESSAGE = 'Сообщение';
const NETCAT_MODULE_SEARCH_ADMIN_EVENT_LOG_PREV_PAGE = 'Ранее';
const NETCAT_MODULE_SEARCH_ADMIN_EVENT_LOG_NEXT_PAGE = 'Позднее';

const NETCAT_MODULE_SEARCH_ADMIN_EVENT_ERROR = 'Ошибка в работе модуля';
const NETCAT_MODULE_SEARCH_ADMIN_EVENT_PHP_EXCEPTION = 'Исключение';
const NETCAT_MODULE_SEARCH_ADMIN_EVENT_PHP_ERROR = 'Ошибка PHP';
const NETCAT_MODULE_SEARCH_ADMIN_EVENT_PHP_WARNING = 'Предупреждение PHP';
const NETCAT_MODULE_SEARCH_ADMIN_EVENT_INDEXING_BEGIN_END = 'Начало, окончание индексирования';
const NETCAT_MODULE_SEARCH_ADMIN_EVENT_INDEXING_NO_SUB = 'Невозможность определения раздела документа';
const NETCAT_MODULE_SEARCH_ADMIN_EVENT_CRAWLER_REQUEST = 'HTTP-запрос';
const NETCAT_MODULE_SEARCH_ADMIN_EVENT_PARSER_DOCUMENT_BRIEF = 'Краткие сведения о полученном документе';
const NETCAT_MODULE_SEARCH_ADMIN_EVENT_PARSER_DOCUMENT_VERBOSE = 'Полные сведения о полученном документе';
const NETCAT_MODULE_SEARCH_ADMIN_EVENT_PARSER_DOCUMENT_LINKS = 'Добавление ссылки в очередь';
const NETCAT_MODULE_SEARCH_ADMIN_EVENT_SCHEDULER_START = 'Запуск планировщика';
const NETCAT_MODULE_SEARCH_ADMIN_EVENT_INDEXING_CONTENT_ERROR = 'Ошибка при индексировании документа';

// Общие
const NETCAT_MODULE_SEARCH_DATETIME_FORMAT = '%d.%m.%Y %H:%M';

// Поиск на сайте

const NETCAT_MODULE_SEARCH_SUBMIT_BUTTON_TEXT = 'Найти';
const NETCAT_MODULE_SEARCH_ADVANCED_LINK_TEXT = 'Расширенный поиск';

const NETCAT_MODULE_SEARCH_FIND_CAPTION = 'Я ищу';
const NETCAT_MODULE_SEARCH_EXCLUDE_CAPTION = 'Исключая';
const NETCAT_MODULE_SEARCH_FIELD_CAPTION = 'Слова';
const NETCAT_MODULE_SEARCH_FIELD_ANY = 'в любой части страницы';
const NETCAT_MODULE_SEARCH_FIELD_TITLE = 'только в заголовке';
const NETCAT_MODULE_SEARCH_TIME_CAPTION = 'Дата обновления';
const NETCAT_MODULE_SEARCH_TIME_ANY = 'все';
const NETCAT_MODULE_SEARCH_TIME_LAST = 'последние';
const NETCAT_MODULE_SEARCH_TIME_LAST_HOURS = 'часов';
const NETCAT_MODULE_SEARCH_TIME_LAST_DAYS = 'дней';
const NETCAT_MODULE_SEARCH_TIME_LAST_WEEKS = 'недель';
const NETCAT_MODULE_SEARCH_TIME_LAST_MONTHS = 'месяцев';

const NETCAT_MODULE_SEARCH_NO_RESULTS = 'По вашему запросу ничего не найдено.';
const NETCAT_MODULE_SEARCH_RESULTS_RANGE = 'Результаты %d&mdash;%d из %d';
const NETCAT_MODULE_SEARCH_RESULTS_ONE = 'Результат %d из %d';
const NETCAT_MODULE_SEARCH_RESULTS_PREV = 'предыдущая';
const NETCAT_MODULE_SEARCH_RESULTS_NEXT = 'следующая';
const NETCAT_MODULE_SEARCH_SORT_BY = 'Сортировать по: ';
const NETCAT_MODULE_SEARCH_SORT_BY_LAST_MODIFIED = 'дате изменения';
const NETCAT_MODULE_SEARCH_SORT_BY_RELEVANCE = 'релевантности';
const NETCAT_MODULE_SEARCH_QUERY_ERROR = 'Ошибка в запросе.';
const NETCAT_MODULE_SEARCH_NO_TITLE = 'Без заголовка';

// Подсказки при исправлении запросов
const NETCAT_MODULE_SEARCH_CORRECTION_GENERIC = "По запросу &laquo;<span class='nc_query'>%s</span>&raquo; ничего не найдено. <span class='nc_correction_suggesion'>Показаны результаты по запросу &laquo;<span class='nc_query'>%s</span>&raquo;.</span>";
const NETCAT_MODULE_SEARCH_CORRECTION_QUOTES = "По запросу &laquo;<span class='nc_query'>%s</span>&raquo; ничего не найдено. <span class='nc_correction_suggesion'>Показаны результаты по запросу без кавычек: &laquo;<span class='nc_query'>%s</span>&raquo;.</span>";

const NETCAT_MODULE_SEARCH_PAGES = 'Страницы';
const NETCAT_MODULES_SEARCH_FROM = 'из';

// Ошибки конфигурации PHP
const NETCAT_MODULE_SEARCH_NO_DOM_EXTENSION_ERROR = "Для работы модуля необходимо расширение <a href='http://php.net/DOM'>dom</a>.";
const NETCAT_MODULE_SEARCH_NO_MBSTRING_EXTENSION_ERROR = "Для работы модуля необходимо расширение <a href='http://php.net/mbstring'>mbstring</a>.";
const NETCAT_MODULE_SEARCH_NO_OPENSSL_EXTENSION_ERROR = "Для работы модуля необходимо расширение <a href='http://php.net/openssl'>openssl</a>.";
const NETCAT_MODULE_SEARCH_MB_OVERLOAD_ENABLED_ERROR =
    "Для работы модуля необходимо отключить перезагрузку строковых функций расширением <i>mbstring</i>. Измените в настройках PHP значение опции <i>mbstring.func_overload</i> на <i>0</i>.";
const NETCAT_MODULE_SEARCH_PCRE_UTF_ERROR =
    "Для работы модуля необходима поддержка работы регулярных выражений в кодировке UTF-8. При компиляции PHP следует использовать встроенную библиотеку PCRE или библиотеку PCRE, скомпилированную с опцией <i>--enable-unicode-properties</i>.";
const NETCAT_MODULE_SEARCH_INDEX_DIRECTORY_NOT_WRITABLE_ERROR = "Папка <i>%s</i> недоступна для записи. Убедитесь, что указанный путь существует и на папку установлены права, позволяющие запись в неё.";
const NETCAT_MODULE_SEARCH_CANNOT_OPEN_INDEX_ERROR = "Невозможно открыть поисковый индекс. Пожалуйста, сотрите все файлы в папке <i>/netcat_files/Search/Lucene/</i>, после чего запустите полное переиндексирование всех сайтов. ";

// Ошибки конфигурации сайтов
const NETCAT_MODULE_SEARCH_SITE_WITHOUT_LANGUAGE_ERROR = 'В настройках сайта %s не указан язык сайта. Корректное индексирование и поиск по данному сайту невозможны.';
const NETCAT_MODULE_SEARCH_SITES_WITHOUT_LANGUAGE_ERROR = 'В настройках сайтов %s не указан язык сайта. Корректное индексирование и поиск по данным сайтам невозможны.';
