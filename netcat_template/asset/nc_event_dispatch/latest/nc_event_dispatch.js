// Поддерживается IE9+

/**
 * Инициирует кастомное событие
 *
 * @param {String} name
 * @param {Object} detail
 */
function nc_event_dispatch(name, detail) {
    var event,
        doc = document;
    if (typeof CustomEvent !== 'function') { // IE не поддерживает конструктор new CustomEvent
        event = doc.createEvent('CustomEvent');
        event.initCustomEvent(name, false, false, detail);
    } else { // на MDN написано: «не используйте event.initCustomEvent» (хотя работает)
        event = new CustomEvent(name, { detail: detail });
    }
    doc.dispatchEvent(event);
}
