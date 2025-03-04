<?php

global $ADMIN_PATH;

const NC_PERM_MODULE_AUTH_SETTINGS_EDIT = 'change module settings (except templates)';

const NETCAT_MODULE_AUTH_DESCRIPTION = '   
The module allows to manage such features as:  
<br/>- registration on the site
<br/>- change user profile
<br/>- change user password 
<br/>- password recovery 
<br/>You may integrate this module with others system components (modules, classes, etc.).';
const NETCAT_MODULE_AUTH_REG_OK = 'Registration was confirmed.';
const NETCAT_MODULE_AUTH_REG_ERROR = 'Error! Registration was NOT confirmed.';
const NETCAT_MODULE_AUTH_REG_INVALIDLINK = 'Invalid link.';
const NETCAT_MODULE_AUTH_ERR_NEEDAUTH = 'Need authorize.';
const NETCAT_MODULE_AUTH_CHANGEPASS_NOTEQUAL = 'Passwords are not equal. Try again.';
const NETCAT_MODULE_AUTH_ERR_NOFIELDSET = 'Field not set.';
const NETCAT_MODULE_AUTH_ERR_NOUSERFOUND = 'User not found';
const NETCAT_MODULE_AUTH_MSG_FILLFIELD = 'Fill one of these fields';
const NETCAT_MODULE_AUTH_MSG_BADEMAIL = 'Invalid E-mail';
const NETCAT_MODULE_AUTH_MSG_NEWPASSSENDED = 'The confirmation letter has been sent to your email. You should click on the link to confirm your request.';
const NETCAT_MODULE_AUTH_MSG_INVALID_LOGIN_FORMAT = 'Invalid &laquo;Login&raquo; field format, you should use alphabet, numbers, underline, hyphen and space simbol.';
const NETCAT_MODULE_AUTH_MSG_INVALID_EMAIL_FORMAT = 'Invalid &laquo;Email&raquo; field format, you should use alphabet, numbers, underline, hyphen and dot simbol.';
const NETCAT_MODULE_AUTH_NEWPASS_SUCCESS = 'The password is successfully changed.';
const NETCAT_MODULE_AUTH_NEWPASS_ERROR = 'Error while password change.';

const NETCAT_MODULE_AUTH_FORM_AND_MAIL_TEMPLATES = 'Mail templates';
const NETCAT_MODULE_AUTH_EXTERNAL_AUTH = 'Authorization through the external services';

const NETCAT_MODULE_AUTH_LOGIN = 'Login';
const NETCAT_MODULE_AUTH_ENTER = 'Enter';
const NETCAT_MODULE_AUTH_REGISTER = 'Register';
const NETCAT_MODULE_AUTH_INCORRECT_LOGIN_OR_RASSWORD = 'Username or password is incorrect';
const NETCAT_MODULE_AUTH_AUTHORIZATION_UPPER = 'AUTHORIZATION';
const NETCAT_MODULE_AUTH_AUTHORIZATION = 'Authorization';
const NETCAT_MODULE_AUTH_FORGOT = 'Forgot password';
const NETCAT_MODULE_AUTH_PASSWORD = 'Password';
const NETCAT_MODULE_AUTH_PASSWORD_CONFIRMATION = 'Password confirmation';
const NETCAT_MODULE_AUTH_FIRST_NAME = 'Name';
const NETCAT_MODULE_AUTH_LAST_NAME = 'Surname';
const NETCAT_MODULE_AUTH_NICKNAME = 'Nickname';
const NETCAT_MODULE_AUTH_PHOTO = 'Photo';
const NETCAT_MODULE_AUTH_SAVE = 'Save login and password';
const NETCAT_MODULE_AUTH_REMEMBER_ME = 'Remember me';
const NETCAT_MODULE_AUTH_NOT_NEW_MESSAGE = 'No new messages';
const NETCAT_MODULE_AUTH_NEW_MESSAGE = 'New message';
const NETCAT_MODULE_AUTH_HELLO = 'Hello';
const NETCAT_MODULE_AUTH_LOGOUT = 'Logout';
const NETCAT_MODULE_AUTH_BY_TOKEN = 'Authorize by token';

const NETCAT_MODULE_AUTH_LOGIN_WAIT = 'Please, wait';
const NETCAT_MODULE_AUTH_LOGIN_FREE = 'Login is free';
const NETCAT_MODULE_AUTH_LOGIN_BUSY = 'Login busy';
const NETCAT_MODULE_AUTH_LOGIN_INCORRECT = 'Login contains invalid characters';

const NETCAT_MODULE_AUTH_PASS_LOW = 'Low';
const NETCAT_MODULE_AUTH_PASS_MIDDLE = 'Average';
const NETCAT_MODULE_AUTH_PASS_HIGH = 'High';
const NETCAT_MODULE_AUTH_PASS_VHIGH = 'Very high';
const NETCAT_MODULE_AUTH_PASS_EMPTY = "The password can't be empty";
const NETCAT_MODULE_AUTH_PASS_SHORT = 'The password too short';

const NETCAT_MODULE_AUTH_PASS_COINCIDE = 'Passwords coincide';
const NETCAT_MODULE_AUTH_PASS_N_COINCIDE = 'Passwords not coincide';

const NETCAT_MODULE_AUTH_PASS_RELIABILITY = 'Reliability:';

const NETCAT_MODULE_AUTH_CP_NEWPASS = 'New password';
const NETCAT_MODULE_AUTH_CP_CONFIRM = 'Confirm new password';
const NETCAT_MODULE_AUTH_CP_DOBUTT = 'Change password';

const NETCAT_MODULE_AUTH_PRF_LOGIN = 'Enter login';
const NETCAT_MODULE_AUTH_PRF_EMAIL = 'Or Email';
const NETCAT_MODULE_AUTH_PRF_EMAIL_2 = 'Email';
const NETCAT_MODULE_AUTH_PRF_DOBUTT = 'Generate new password';

const NETCAT_MODULE_AUTH_BUT_AUTORIZE = 'Authorize';
const NETCAT_MODULE_AUTH_BUT_BACK = 'Back';
const NETCAT_MODULE_AUTH_MSG_AUTHISOK = 'Authorization successful.';
const NETCAT_MODULE_AUTH_MSG_AUTHUPISOK = 'Session is closed.';

const NETCAT_MODULE_AUTH_MSG_SESSION_CLOSED = 'Session is closed. <a href=\'%s\'>Back</a>';
const NETCAT_MODULE_AUTH_MSG_AUTH_SUCCESS = 'Authorization successful. <a href=\'%s\'>Back</a>';

const NETCAT_MODULE_AUTH_ADMIN_MAIN_SETTINGS_TITLE = 'Main settings';
const NETCAT_MODULE_AUTH_ADMIN_SAVE_OK = 'Settings updated successfully';

define("NETCAT_MODULE_AUTH_ADMIN_INFO", "You may edit HTML of user list and add, edit or delete \"Users\" in section \"<a href=" . $ADMIN_PATH . "field/system.php>System tables</a>\".<br><br>User control in section \"<a href=" . $ADMIN_PATH . "user/>Users and rights</a>\".");

// название вкладкок
const NETCAT_MODULE_AUTH_ADMIN_TAB_INFO = 'Information';
const NETCAT_MODULE_AUTH_ADMIN_TAB_REGANDAUTH = 'Registration and authorization';
const NETCAT_MODULE_AUTH_ADMIN_TAB_TEMPLATES = 'Templates';
const NETCAT_MODULE_AUTH_ADMIN_TAB_MAIL = 'Mail templates';
const NETCAT_MODULE_AUTH_ADMIN_TAB_SETTINGS = 'Settings';
const NETCAT_MODULE_AUTH_ADMIN_TAB_CLASSIC = 'Using login and password';
const NETCAT_MODULE_AUTH_ADMIN_TAB_EXAUTH = 'Through external services';
const NETCAT_MODULE_AUTH_ADMIN_TAB_GENERAL = 'General';
const NETCAT_MODULE_AUTH_ADMIN_TAB_SYSTEM = 'System';

// информация
const NETCAT_MODULE_AUTH_ADMIN_INFO_USER_COUNT = 'The total number of users';
const NETCAT_MODULE_AUTH_ADMIN_INFO_USER_COUNT_UNCHECKED = 'The number of unchecked users';
const NETCAT_MODULE_AUTH_ADMIN_INFO_USER_COUNT_UNCONFIRMED = 'The number of users not confirmed their registration yet';
const NETCAT_MODULE_AUTH_ADMIN_INFO_NONE = 'not';

// using username and password
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_DENY_REG = 'Forbid self-registration';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_DENY_RECOVERY = 'Forbid password self recovery';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_ALLOW_CYRILLIC = 'Allow cyrillic usernames';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_ALLOW_SPECIALCHARS = 'Allow special characters in usernames';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_ALLOW_CHANGE_LOGIN = 'Allow to change username after registration';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_BING_TO_CATALOGUE = 'To adhere the user to a site';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_WITH_SUBDOMAIN = 'Authorize on subdomains';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_AUTH_CAPTCHA = 'Logon CAPTCHA settings';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_PASS_MIN = 'Minimal password length %input symbols';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_REGISTRATION_FORM = 'Registration form';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_CHECK_LOGIN = 'Check username availability automatically (can leak user email addresses)';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_CHECK_PASS = 'Check password safety level automatically';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_CHECK_PASS2 = 'Check for password match automatically';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_CHECK_AGREED = 'Demand agreement with terms of use';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_FIELDS_IN_REG_FORM = 'Fields in registration form';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_FIELDS_IN_REG_FORM_ALL = 'All';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_FIELDS_IN_REG_FORM_CUSTOM = 'selectively';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_ACTIVATION = 'Activation';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_CONFIRM = 'Demand confirmation via email';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_CONFIRM_AFTER_MAIL = 'Send additional mail after successful confirmation';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_PREMODARATION = 'Pre-moderation by Administrator';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_NOTIFY_ADMIN = 'Notify Administrator on user registration';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_AUTHAUTORIZE = 'User authorization right after confirmation';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_CONFIRM_TIME = 'Delete user if registration is not confirmed after %input hours';

// using external services
const NETCAT_MODULE_AUTH_ADMIN_EX_CURL_REQUIRED = "For authorization through external services curl library required <a href='http://www.php.net/manual/ru/book.curl.php'>cURL</a>";
const NETCAT_MODULE_AUTH_ADMIN_EX_JSON_REQUIRED = "For authorization through external services curl library is required <a href='http://ru2.php.net/manual/en/book.json.php'>JSON</a>";
const NETCAT_MODULE_AUTH_ADMIN_EX_VK = 'VKontakte';
const NETCAT_MODULE_AUTH_ADMIN_EX_FB = 'Facebook';
const NETCAT_MODULE_AUTH_ADMIN_EX_TWITTER = 'Twitter';
const NETCAT_MODULE_AUTH_ADMIN_EX_OPENID = 'OpenID';
const NETCAT_MODULE_AUTH_ADMIN_EX_OAUTH = 'OAuth';
const NETCAT_MODULE_AUTH_ADMIN_EX_VK_ENABLED = 'Activate authorization through vkontakte.ru';
const NETCAT_MODULE_AUTH_ADMIN_EX_FB_ENABLED = 'Activate authorization through facebook.com';
const NETCAT_MODULE_AUTH_ADMIN_EX_OPENID_ENABLED = 'Activate authorization through OpenID';
const NETCAT_MODULE_AUTH_ADMIN_EX_OAUTH_ENABLED = 'Activate authorization through OAuth';
const NETCAT_MODULE_AUTH_ADMIN_EX_TWITTER_ENABLED = 'Activate authorization through twitter.com';
const NETCAT_MODULE_AUTH_ADMIN_EX_DATA_VK = 'Data from VKontakte';
const NETCAT_MODULE_AUTH_ADMIN_EX_DATA_FB = 'Data from Facebook';
const NETCAT_MODULE_AUTH_ADMIN_EX_DATA_TWITTER = 'Data from Twitter';
const NETCAT_MODULE_AUTH_ADMIN_EX_DATA_OPENID = 'Data from OpenID';
const NETCAT_MODULE_AUTH_ADMIN_EX_DATA_OAUTH = 'Data from OAuth';


// general settings
const NETCAT_MODULE_AUTH_ADMIN_GENERAL_AUTH_SITE = 'Methods of the authorization for site';
const NETCAT_MODULE_AUTH_ADMIN_GENERAL_AUTH_ADMIN = 'Methods of the authorization for administration system';
const NETCAT_MODULE_AUTH_ADMIN_GENERAL_AUTH_SITE_NO_METHODS_PROVIDED_ERROR = "At least one authorization method for the site must be selected";
const NETCAT_MODULE_AUTH_ADMIN_GENERAL_NO_METHODS_PROVIDED_ERROR = "At least one authorization method for the administration system must be selected";
const NETCAT_MODULE_AUTH_ADMIN_GENERAL_METHOD_LOGIN = 'By username and password';
const NETCAT_MODULE_AUTH_ADMIN_GENERAL_METHOD_TOKEN = 'By usb-token';
const NETCAT_MODULE_AUTH_ADMIN_GENERAL_METHOD_HASH = 'By hash';
const NETCAT_MODULE_AUTH_ADMIN_GENERAL_METHOD_EX = 'Using external services';
const NETCAT_MODULE_AUTH_ADMIN_GENERAL_PM = 'Personal messages';
const NETCAT_MODULE_AUTH_ADMIN_GENERAL_PM_ALLOW = 'Allow to send personal messages';
const NETCAT_MODULE_AUTH_ADMIN_GENERAL_PM_NOTIFY = 'Notify users on new message via email';
const NETCAT_MODULE_AUTH_ADMIN_GENERAL_FRIEND_BANNED = 'Friends and banned users';
const NETCAT_MODULE_AUTH_ADMIN_GENERAL_FRIEND_ALLOW = 'Allow to add users as friends';
const NETCAT_MODULE_AUTH_ADMIN_GENERAL_BANNED_ALLOW = 'Allow user to ban other users';
const NETCAT_MODULE_AUTH_ADMIN_GENERAL_PA = 'Personal account';
const NETCAT_MODULE_AUTH_ADMIN_GENERAL_PA_ALLOW = 'Allow personal account';
const NETCAT_MODULE_AUTH_ADMIN_GENERAL_PA_CURRENCY = 'Personal account currency %input';
const NETCAT_MODULE_AUTH_ADMIN_GENERAL_PA_START = 'Add %input units to new users&#039; personal accounts';

// emails
const NETCAT_MODULE_AUTH_ADMIN_MAIL_SUBJECT = 'Email subject';
const NETCAT_MODULE_AUTH_ADMIN_MAIL_BODY = 'Email body';
const NETCAT_MODULE_AUTH_ADMIN_MAIL_HTML = 'HTML-mail';

// system settings
const NETCAT_MODULE_AUTH_ADMIN_SYSTEM_COMPONENTS_SUBS = 'Components, subdivisions and fields';
const NETCAT_MODULE_AUTH_ADMIN_SYSTEM_COMPONENT_FRIENDS = 'Component &#034;Friend&#034; List';
const NETCAT_MODULE_AUTH_ADMIN_SYSTEM_COMPONENT_PM = 'Personal messages component';
const NETCAT_MODULE_AUTH_ADMIN_SYSTEM_COMPONENT_PA = 'Personal account component';
const NETCAT_MODULE_AUTH_ADMIN_SYSTEM_FIELD_PA = 'Field for personal account balance';
const NETCAT_MODULE_AUTH_ADMIN_SYSTEM_SUB_MATERIALS = "Subdivision for user's ";
const NETCAT_MODULE_AUTH_ADMIN_SYSTEM_SUB_MODIFY = "Users' modification subdivisions";
const NETCAT_MODULE_AUTH_ADMIN_SYSTEM_CC_USER_LIST = 'Component in users list subdivision';
const NETCAT_MODULE_AUTH_ADMIN_SYSTEM_PSEUDO = 'Pseudo users';
const NETCAT_MODULE_AUTH_ADMIN_SYSTEM_PSEUDO_ALLOW = 'Allow pseudo users';
const NETCAT_MODULE_AUTH_ADMIN_SYSTEM_PSEUDO_CHECK_IP = 'Check IP';
const NETCAT_MODULE_AUTH_ADMIN_SYSTEM_PSEUDO_GROUP = 'Pseudo users group';
const NETCAT_MODULE_AUTH_ADMIN_SYSTEM_PSEUDO_FIELD = 'Identification field';
const NETCAT_MODULE_AUTH_ADMIN_SYSTEM_HASH = 'Hash authorization';
const NETCAT_MODULE_AUTH_ADMIN_SYSTEM_HASH_DELETE = 'Delete hash after authorization';
const NETCAT_MODULE_AUTH_ADMIN_SYSTEM_HASH_EXPIRE = 'Hash expires in %input hours';
const NETCAT_MODULE_AUTH_ADMIN_SYSTEM_HASH_DISABLED_SUBS = 'Numbers of subdivision with forbidden hash-authorization, divide with comma %input';
const NETCAT_MODULE_AUTH_ADMIN_SYSTEM_OTHER = 'Other';
const NETCAT_MODULE_AUTH_ADMIN_SYSTEM_OTHER_ONLINE = 'Time user is considered online, in seconds %input';
const NETCAT_MODULE_AUTH_ADMIN_SYSTEM_OTHER_IP = 'Level IP-address check (0-4) %input';
const NETCAT_MODULE_AUTH_ADMIN_NOTIFICATION_EMAIL = 'E-mail for administrator notifications %input';

const NETCAT_MODULE_AUTH_SELFREG_DISABLED = 'Self-registration is forbidden';


const NETCAT_MODULE_AUTH_FORM_TEMPLATES = 'Template forms';
const NETCAT_MODULE_AUTH_FORM_AUTH = 'Authorization form';
const NETCAT_MODULE_AUTH_RESTORE_DEF = 'Restore default settings';
const NETCAT_MODULE_AUTH_FORM_DISABLED = "Don't show the form if registration fails";
const NETCAT_MODULE_AUTH_FORM_CHG_PASS = 'Password change form';
const NETCAT_MODULE_AUTH_FORM_CHG_PASS_AFTER = 'Text entered after password change';
const NETCAT_MODULE_AUTH_FORM_REC_PASS_AFTER = 'Text entered after sending email';
const NETCAT_MODULE_AUTH_FORM_CHG_PASS_WARNBLOCK = 'Display error block';
const NETCAT_MODULE_AUTH_FORM_CHG_PASS_DENY = 'Text entered when password change denied';
const NETCAT_MODULE_AUTH_FORM_REC_PASS = 'Password recovery form';
const NETCAT_MODULE_AUTH_FORM_CONFIRM_AFTER = 'Text entered after registration confirmed';
const NETCAT_MODULE_AUTH_FORM_CONFIRM_AFTER_WARNBLOCK = 'Display error block when registration not confirmed';
const NETCAT_MODULE_AUTH_MAIL_TEMPLATES = 'Mail templates';
const NETCAT_MODULE_AUTH_REG_CONFIRM = 'Registration confirmation';
const NETCAT_MODULE_AUTH_REG_CONFIRM_AFTER = 'Notification after registration confirmation';
const NETCAT_MODULE_AUTH_AS_HTML = 'HTML-text';
const NETCAT_MODULE_AUTH_RECOVERY = 'Password recovery';
const NETCAT_MODULE_AUTH_ADMIN_MAIL_NOTIFY = 'Notification for administrator on user registration';

const NETCAT_MODULE_AUTH_ADD_FRIEND = 'Add to friends';
const NETCAT_MODULE_AUTH_REMOVE_FRIEND = 'Remove from friends';

// OpenID
const NETCAT_MODULE_AUTH_OPEN_ID_ERROR = 'Undefined OpenID';
const NETCAT_MODULE_AUTH_OPEN_ID_INVALID = 'Invalid OpenID';
const NETCAT_MODULE_AUTH_OPEN_ID_ALREADY_EXIST_IN_BASE = 'This OpenID already exist into the base';
const NETCAT_MODULE_AUTH_OPEN_ID_COULD_NOT_REDIRECT_TO_SERVER = 'Could not redirect to server: %s';
const NETCAT_MODULE_AUTH_OPEN_ID_CHECK_CANCELED = 'Auth breaking';
const NETCAT_MODULE_AUTH_OPEN_ID_AUTH_FAILED = 'OpenID auth failed: %s';
const NETCAT_MODULE_AUTH_OPEN_ID_AUTH_COMPLETE_NAME = "Auth successful as <a href='%s'>%s</a> ";
const NETCAT_MODULE_AUTH_OPEN_ID_AUTH_COMPLETE_LOGIN = 'Your login: %s';


const NETCAT_MODULE_AUTH_SETUP_PROFILE = 'User profile';
const NETCAT_MODULE_AUTH_SETUP_REGISTRATION = 'Registration';
const NETCAT_MODULE_AUTH_SETUP_PASSWORD_RECOVERY = 'Password recovery';
const NETCAT_MODULE_AUTH_SETUP_MODIFY = 'modification';
const NETCAT_MODULE_AUTH_SETUP_PASSWORD = 'Password change';
const NETCAT_MODULE_AUTH_SETUP_PM = 'Personal messages';


const NETCAT_MODULE_AUTH_APPLICATION_ID = 'Application ID';
const NETCAT_MODULE_AUTH_APPLICATION_ID_VK = 'ID applications';
const NETCAT_MODULE_AUTH_APPLICATION_ID_FB = 'ID applications (Application ID)';
const NETCAT_MODULE_AUTH_APPLICATION_ID_TWITTER = 'ID applications (Consumer key)';
const NETCAT_MODULE_AUTH_SECRET_KEY = 'Secret Key';
const NETCAT_MODULE_AUTH_PUBLIC_KEY = 'Public Key';
const NETCAT_MODULE_AUTH_SECRET_KEY_VK = 'Protected key';
const NETCAT_MODULE_AUTH_SECRET_KEY_FB = 'Protected key (Application Secret)';
const NETCAT_MODULE_AUTH_SECRET_KEY_TWITTER = 'Protected key (Consumer secret)';

const NETCAT_MODULE_AUTH_GROUPS_WHERE_USER_WILL_BE = 'Groups to which user will be assign';
const NETCAT_MODULE_AUTH_GROUPS_WHERE_USER_WILL_BE_EMPTY = 'No chosen group!';
const NETCAT_MODULE_AUTH_ACTION_BEFORE_FIRST_AUTHORIZATION = "Action before user's first authorization";
const NETCAT_MODULE_AUTH_ACTION_AFTER_FIRST_AUTHORIZATION = "Action after user's first authorization";
const NETCAT_MODULE_AUTH_ACTION_FIELDS_MAPPING = 'Field mapping';
const NETCAT_MODULE_AUTH_ACTION_FIELDS_MAPPING_ADD = 'Add mapping';
const NETCAT_MODULE_AUTH_PROVIDER_ADD = 'Add provider';
const NETCAT_MODULE_AUTH_PROVIDER = 'Provider';
const NETCAT_MODULE_AUTH_PROVIDER_ICON = 'Provider icon';


const NETCAT_MODULE_AUTH_ADMIN_DEF_MAIL_CONFIRM_SUBJECT = 'Confirmation of registration on %SITE_NAME';
const NETCAT_MODULE_AUTH_ADMIN_DEF_MAIL_CONFIRM_BODY =
"Hello, %USER_LOGIN! <br />
You have successfully registered for <a href='%SITE_URL'>%SITE_NAME</a>.<br />
Your username: %USER_LOGIN.<br />
Your password: %PASSWORD.<br />
To activate your account, click this link: <a href='%CONFIRM_LINK'>%CONFIRM_LINK</a>.<br />
This email was sent to you because your address was registered for %SITE_URL.<br />
If you didn't register, ignore this email.<br />
With best regards, Administration of <a href='%SITE_URL'>%SITE_NAME</a>.
";

const NETCAT_MODULE_AUTH_ADMIN_DEF_MAIL_CONFIRM_AFTER_SUBJECT = 'Registration on %SITE_NAME successfully complete';
const NETCAT_MODULE_AUTH_ADMIN_DEF_MAIL_CONFIRM_AFTER_BODY =
"Hello, %USER_LOGIN

Your account was successfully activated? now you can use all features of this site.

With best regards, Administration of <a href='%SITE_URL'>%SITE_NAME</a>.
";

const NETCAT_MODULE_AUTH_ADMIN_DEF_MAIL_PASSWORDRECOVERY_SUBJECT = 'Password recovery for %SITE_NAME';
const NETCAT_MODULE_AUTH_ADMIN_DEF_MAIL_PASSWORDRECOVERY_BODY =
"Hello, %USER_LOGIN!<br />
To recover password for %USER_LOGIN on <a href='%SITE_URL'>%SITE_NAME</a> click this link: <a href='%CONFIRM_LINK'>%CONFIRM_LINK</a>.<br />
If you didn&#039;t ask for password recovery, ignore this email.<br />
With best regards, Administration of <a href='%SITE_URL'>%SITE_NAME</a>.
";
const NETCAT_MODULE_AUTH_ADMIN_DEF_MAIL_ADMIN_NOTIFY_SUBJECT = 'New user on %SITE_NAME';
const NETCAT_MODULE_AUTH_ADMIN_DEF_MAIL_ADMIN_NOTIFY_BODY =
"Hello, Administrator.<br />
New user %USER_LOGIN registered on <a href='%SITE_URL'>%SITE_NAME</a>.<br />
With best regards, Administration of <a href='%SITE_URL'>%SITE_NAME</a>.
";
const NETCAT_MODULE_AUTH_USER_AGREEMENT = "I accept <a href='%USER_AGR'> terms and conditions </a>";
const NETCAT_MODULE_AUTH_AUTHENTICATION_FAILED = 'An error occurred during authentication.';
const NETCAT_MODULE_AUTH_RETRY = 'Please refresh the page or try again later.';

// названия создаваемых разделов
const NETCAT_MODULE_AUTH_PROFILE_SUBDIVISION_NAME = 'Profile';
const NETCAT_MODULE_AUTH_EDIT_PROFILE_SUBDIVISION_NAME = 'My profile';
const NETCAT_MODULE_AUTH_CHANGE_PASS_SUBDIVISION_NAME = 'Change password';
const NETCAT_MODULE_AUTH_RECOVERY_PASS_SUBDIVISION_NAME = 'Recover password';
const NETCAT_MODULE_AUTH_REGISTRATION_SUBDIVISION_NAME = 'Registration';
const NETCAT_MODULE_AUTH_REG_ADMIN = 'Wait for your account to be activated by administrator';
const NETCAT_MODULE_AUTH_REG_AGAIN = 'Error! Registration has already been confirmed';