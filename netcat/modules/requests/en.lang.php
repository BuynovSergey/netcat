<?php

// Права доступа к модулю
const NC_PERM_MODULE_REQUESTS_SETTINGS_EDIT = 'change module settings';
const NC_PERM_MODULE_REQUESTS_REQUESTS_VIEW = 'view requests';

// Языковые константы
const NETCAT_MODULE_REQUESTS = 'Requests';
const NETCAT_MODULE_REQUESTS_DESCRIPTION = 'Module for viewing requests and creating forms.';

const NETCAT_MODULE_REQUESTS_FORM_TYPE = 'Form group';
const NETCAT_MODULE_REQUESTS_FORM_SETTINGS_FIELDS_HEADER = 'Form fields';
const NETCAT_MODULE_REQUESTS_FORM_POPUP_BUTTON_SETTINGS_HEADER = 'Open form button';
const NETCAT_MODULE_REQUESTS_FORM_SUBMIT_BUTTON_SETTINGS_HEADER = 'Submit button';
const NETCAT_MODULE_REQUESTS_FORM_BUTTON_DEFAULT_TEXT = 'Submit';
const NETCAT_MODULE_REQUESTS_FORM_OPEN_POPUP_FORM = 'Open the form';
const NETCAT_MODULE_REQUESTS_FORM_HAS_NO_FIELDS = 'No fields are selected for this form';
const NETCAT_MODULE_REQUESTS_FORM_HEADER_CAPTION = 'Form header';
const NETCAT_MODULE_REQUESTS_FORM_TEXT_AFTER_HEADER_CAPTION = 'Form sub-header';
const NETCAT_MODULE_REQUESTS_FORM_NOTIFICATION_EMAIL_CAPTION = 'E-mail data to (for all forms on the page)';
const NETCAT_MODULE_REQUESTS_FORM_NOTIFICATION_EMAIL_PLACEHOLDER = 'For example: mail1@example.com, mail2@example.com...';
const NETCAT_MODULE_REQUESTS_FORM_BUTTON_TEXT = 'Text';
const NETCAT_MODULE_REQUESTS_FORM_BUTTON_TYPE = 'Button type';
const NETCAT_MODULE_REQUESTS_FORM_BUTTON_TYPE_PRIMARY = 'Primary';
const NETCAT_MODULE_REQUESTS_FORM_BUTTON_TYPE_SECONDARY = 'Secondary';
const NETCAT_MODULE_REQUESTS_FORM_BUTTON_COLOR = 'Color (if not set in the mixin for this block)';
const NETCAT_MODULE_REQUESTS_FORM_BUTTON_PRICE = 'Show price (if applicable)';
const NETCAT_MODULE_REQUESTS_FORM_SUBMIT_EVENT_CATEGORIES = 'Google Analytics form submit event categories (several categories can be separated by a comma),<br>Yandex.Metrika goal ids will be &ldquo;<i>category</i>_submit&ldquo; for each of the specified categories';
const NETCAT_MODULE_REQUESTS_FORM_SUBMIT_EVENT_LABELS = 'Google Analytics form submit event labels (several labels can be separated by a comma),<br>for Yandex.Metrika the label will be available in the additional visit parameter &ldquo;event_label&rdquo;';
const NETCAT_MODULE_REQUESTS_FORM_OPEN_EVENT_CATEGORIES = 'Google Analytics form opening event categories (several categories can be separated by a comma),<br>Yandex.Metrika goal ids will be &ldquo;<i>category</i>_click&ldquo; for each of the specified categories';
const NETCAT_MODULE_REQUESTS_FORM_OPEN_EVENT_LABELS = 'Google Analytics form opening event labels (several labels can be separated by a comma),<br>for Yandex.Metrika the label will be available in the additional visit parameter &ldquo;event_label&rdquo;';
const NETCAT_MODULE_REQUESTS_FORM_BUTTON_SCOPE_SUBDIVISION = 'for the whole page';
const NETCAT_MODULE_REQUESTS_FORM_BUTTON_SCOPE_INFOBLOCK = 'extra goals for this block';
const NETCAT_MODULE_REQUESTS_FORM_SUBDIVISION_SYNC_HINT = 'Fields will be set for all forms on the current page.';
const NETCAT_MODULE_REQUESTS_FORM_BUTTON_ACTION_HINT_OPEN_POPUP = 'This button shows the form over the current page.';
const NETCAT_MODULE_REQUESTS_FORM_BUTTON_ACTION_HINT_CREATE_ORDER = 'This button creates an order in the shop.';
const NETCAT_MODULE_REQUESTS_FORM_BUTTON_ACTION_HINT_CREATE_REQUEST = 'This button creates a request.';
const NETCAT_MODULE_REQUESTS_FORM_LABEL_FIELD_NAME = 'Field name';
const NETCAT_MODULE_REQUESTS_FORM_LABEL_FIELD_LABEL = 'Label';
const NETCAT_MODULE_REQUESTS_FORM_LABEL_FIELD_PLACEHOLDER = 'Placeholder';
const NETCAT_MODULE_REQUESTS_FORM_LABEL_FIELD_VISIBILITY = 'On';
const NETCAT_MODULE_REQUESTS_FORM_LABEL_FIELD_REQUIRED = '*';

const NETCAT_MODULE_REQUESTS_FORM_BUTTON_ACTION_HINT_STATS_DISABLED =
    "The 'Statistics' module is disabled, events will be not registered in Google Analytics and Yandex.Metrika.
    <a href='%s' target='_blank'>Change modules settings</a>";
const NETCAT_MODULE_REQUESTS_FORM_BUTTON_ACTION_HINT_ANALYTICS_DISABLED =
    "Google Analytics and Yandex.Metrika handler is disabled in the 'Statistics' module settings, events will be not registered.
    <a href='%s' target='_blank'>Change settings</a>";
const NETCAT_MODULE_REQUESTS_FORM_BUTTON_ACTION_HINT_ANALYTICS_NO_COUNTERS =
    "Neither Google Analytics nor Yandex.Metrika tracking code is set in the 'Statistics' module settings, events will be not registered.
    <a href='/netcat/admin/#module.stats.analytics' target='_blank'>Set tracking code</a>";

const NETCAT_MODULE_REQUESTS_REQUEST_FILTER = 'Фильтр заявок';

const NETCAT_MODULE_REQUESTS_REQUEST_NEW = 'new';
const NETCAT_MODULE_REQUESTS_REQUEST_ANY = 'any';

const NETCAT_MODULE_REQUESTS_REQUEST_SEARCH = 'ID, name, phone, E-mail or source';
const NETCAT_MODULE_REQUESTS_DATE_FILTER = 'Date';
const NETCAT_MODULE_REQUESTS_DATE_FILTER_FROM = 'from';
const NETCAT_MODULE_REQUESTS_DATE_FILTER_TO = 'to';
const NETCAT_MODULE_REQUESTS_REQUEST_FILTER_SUBMIT = 'Search';
const NETCAT_MODULE_REQUESTS_REQUEST_FILTER_RESET = 'Clear form';
const NETCAT_MODULE_REQUESTS_REQUEST_FILTER_RESET_CONFIRM = 'Are you sure you want to clear form?';
const NETCAT_MODULE_REQUESTS_REQUEST_DELETE_SELECTED = 'Delete selected';
const NETCAT_MODULE_REQUESTS_REQUEST_DELETE_SELECTED_CONFIRM = 'Delete selected requests?';

const NETCAT_MODULE_REQUESTS_REQUEST_STATUS = 'Request status';
const NETCAT_MODULE_REQUESTS_CONFIRM_STATUS_CHANGE = 'Confirm status change';
const NETCAT_MODULE_REQUESTS_CONFIRM_STATUS_CHANGE_TO = 'Change request status to «%s»?';

const NETCAT_MODULE_REQUESTS_REQUEST_NUMBER = 'Request №';
const NETCAT_MODULE_REQUESTS_REQUEST_EDIT = 'Edit request';

const NETCAT_MODULE_REQUESTS_ITEM_DISCOUNT = 'Discount (promo page)';
const NETCAT_MODULE_REQUESTS_ITEM_DISCOUNT_DESCRIPTION = 'Discount value is set in the promo page settings';

const NETCAT_MODULE_REQUESTS_DEFAULT_NOTIFICATION_EMAIL = 'Default notification e-mail addresses (used when notification address is not set in the form settings)';
const NETCAT_MODULE_REQUESTS_DEFAULT_NOTIFICATION_PHONE = 'Default notification phones';
const NETCAT_MODULE_REQUESTS_NOTIFICATION_EMAIL_SUBJECT = 'New request from %s (%s)';
const NETCAT_MODULE_REQUESTS_REQUEST_ADMIN_LINK = 'Requests in the admin panel';
const NETCAT_MODULE_REQUESTS_SETTINGS_HEADER = 'Settings';
const NETCAT_MODULE_REQUESTS_SAVE = 'Save';
const NETCAT_MODULE_REQUESTS_SETTINGS_SAVED = 'Settings saved';
