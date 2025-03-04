urlDispatcher.addRoutes({
    "module.messaging": NETCAT_PATH + "modules/messaging/admin/?controller=settings&action=%1",
    "module.messaging.settings": NETCAT_PATH + "modules/messaging/admin/?controller=settings",
    "module.messaging.service": NETCAT_PATH + "modules/messaging/admin/?controller=service&action=%1",
    "module.messaging.service.edit": NETCAT_PATH + "modules/messaging/admin/?controller=service&action=edit&id=%1",
    "module.messaging.log": NETCAT_PATH + "modules/messaging/admin/?controller=log&action=%1",
    "module.messaging.send": NETCAT_PATH + "modules/messaging/admin/?controller=send&action=%1",

    1: ""
});
