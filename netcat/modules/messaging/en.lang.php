<?php

// General
const NETCAT_MODULE_MESSAGING_ENABLED = "Messaging enabled";
const NETCAT_MODULE_MESSAGING_PROVIDER = "Service";
const NETCAT_MODULE_MESSAGING_PROVIDERS = "Services";
const NETCAT_MODULE_MESSAGING_SETTINGS = "Settings";
const NETCAT_MODULE_MESSAGING_ADD_NUMBER = "Add phone";
const NETCAT_MODULE_MESSAGING_SETTINGS_ALLOWED_PHONE_PREFIXES = "Phone prefixes allowed for sending";
const NETCAT_MODULE_MESSAGING_SETTINGS_FORBIDDEN_PHONE_PREFIXES = "Phone prefixes prohibited for sending";
const NETCAT_MODULE_MESSAGING_AUTH = "Two-factor authentication";
const NETCAT_MODULE_MESSAGING_DEFAULT = "Default service (if not selected explicitly)";
const NETCAT_MODULE_MESSAGING_SECURITY = "Incoming data filter alerts";
const NETCAT_MODULE_MESSAGING_REQUESTS = "Requests";
const NETCAT_MODULE_MESSAGING_MESSAGE = "Message text";
const NETCAT_MODULE_MESSAGING_PHONE = "Phone number";
const NETCAT_MODULE_MESSAGING_MESSAGE_SENT_SUCCESS = "Message sent successfully";
const NETCAT_MODULE_MESSAGING_MESSAGE_SENT_ERROR = "For technical reasons, the message could not be sent";
const NETCAT_MODULE_MESSAGING_SINGLE_FIELD_SELECT_PLACEHOLDER = 'Select a field';
const NETCAT_MODULE_MESSAGING_FIELD_SELECT_PLACEHOLDER = 'Select the fields you want...';
const NETCAT_MODULE_MESSAGING_FIELD_NOT_FOUND = 'No fields found for the request';


// Services/services/service providers
const NETCAT_MODULE_MESSAGING_PROVIDER_NAME = "Service Provider";
const NETCAT_MODULE_MESSAGING_SERVICE_NAME = "Service name";
const NETCAT_MODULE_MESSAGING_SERVICE_API_TOKEN = "Access key";
const NETCAT_MODULE_MESSAGING_SERVICE_LOGIN = "Login";
const NETCAT_MODULE_MESSAGING_SERVICE_PASSWORD = "Password";
const NETCAT_MODULE_MESSAGING_SERVICE_SENDER_ID = "Sender name";
const NETCAT_MODULE_MESSAGING_SERVICE_MANUAL_SEND = "Send message";
define ("NETCAT_MODULE_MESSAGING_SERVICE_CONFIGURE_SERVICE_TEXT",
'Select, configure and enable <a href="'.nc_core::get_object()->ADMIN_PATH.'#module.messaging.service(index)" target="_top">service</a>'
);
// Errors
const NETCAT_MODULE_MESSAGING_UPDATE_ERROR = "Unable to update object";
const NETCAT_MODULE_MESSAGING_SETTINGS_SAVE_ERROR = "Failed to save settings: %s";
const NETCAT_MODULE_MESSAGING_SERVICE_NOT_FOUND_ERROR = "The requested service was not found";
const NETCAT_MODULE_MESSAGING_SERVICE_AUTH_PARAMETERS_DID_NOT_PROVIDED_ERROR = "Authorization parameters were not passed (login+password or api key)";
const NETCAT_MODULE_MESSAGING_SERVICE_CONFIRMATION_CODE_LENGTH_VALIDATION_ERROR = "The confirmation code must be at least %s and no more than %s characters";
const NETCAT_MODULE_MESSAGING_API_UNSUPPORTED_IMPLEMENTATION_ERROR = "The class must implement nc_messaging_api";
const NETCAT_MODULE_MESSAGING_SERVICE_UNSUPPORTED_IMPLEMENTATION_ERROR = "The class must implement interface nc_messaging_api";
const NETCAT_MODULE_MESSAGING_SERVICE_NO_AVAILABLE_SERVICES_ERROR = "There are no configured services";
const NETCAT_MODULE_MESSAGING_SERVICE_NO_SELECTED_ERROR = "Service not selected";
const NETCAT_MODULE_MESSAGING_SERVICE_FORBIDDEN_PREFIX_ERROR = "The prefix for the specified phones: %s is in the prohibited list";
const NETCAT_MODULE_MESSAGING_INVALID_PHONE_FORMAT = "Invalid phone format";
const NETCAT_MODULE_MESSAGING_UNSUPPORTED_MULTI_MESSAGING = "The provider does not support multiple sending";
const NETCAT_MODULE_MESSAGING_SERVICE_DISABLED_ERROR = "The provider is disabled";
const NETCAT_MODULE_MESSAGING_DISABLED_ERROR = "The messaging module is disabled";

// The event log
const NETCAT_MODULE_MESSAGING_LOG_STATUS = "Status";
const NETCAT_MODULE_MESSAGING_LOG_RECIPIENTS = "Recipients";
const NETCAT_MODULE_MESSAGING_LOG_RESPONSE_TEXT = "Message from server";
