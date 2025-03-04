var ncLang = {
  // управление правами пользователя
  UserSelectRights : "Choose the type of rights.",
  UserHelpDirector : "Director has rights to manage all the sites, users and instruments (tools) in the system.",
  UserHelpSupervisor : "Supervisor has all Director related permissions, but can't access the Director panel.",
  UserHelpEditor : "Editor can modify/edit/manage only the site, section or section component to which he/she is assigned. " +
      "Permissions for changing, checking and deleting are applied to the objects that users have created themselves.<br><br>" +
      "User with the access to site content will be granted permissions for the blocks that are linked to site sections only " +
      "(blocks that are placed at the main content area of the site), while ‘Access to site’ grants corresponding permissions to any blocks.",
  UserHelpModerator : "Directors and supervisors have permissions to perform all operations with the users (such as creation, editing, permission assignment, deletion).<br><br>" +
      "Add, edit and delete permissions allow to perform these operations on users without any permissions in the system.<br><br>" +
      "Moderator permissions allow to change user properties (including password) for users whose permissions are not wider than those of the moderator.<br><br>" +
      "Administrator permissions allow to assign permissions (which administrator has him or herself) to other users.",
  UserHelpGroup: "Group administration rights give permissions to add, rename and delete user groups. Administration permission allow to assign and remove permissions to the group.",
  UserHelpClassificator : "Administrator can manage lists.",
  UserHelpBanned : "Banner can forbid users the action listed above.",
  UserHelpGuest : "Guest can look (view, see) at the system but can't manage it.",
  UserHelpSubscriber : "Subscriber can sign up for site's newsletters and alerts.",
  UserPasswordsMismatch : "Passwords mismatch!",
  WarnAddTemplate : "Alternate object addition form not empty! Replace old text?",
  WarnEditTemplate : "Alternate object modification form not empty! Replace old text?",
  WarnDeleteTemplate : "Alternate object deleting form not empty! Replace old text?",
  WarnSearchTemplate : "Field \"Search form\" not empty! Replace old text?",
  WarnFullSearchTemplate : "Field \"Search form in object list\" not empty! Replace old text?",
  WarnAddCond : "Field \"Object addition conditions\" not empty!е! Replace old text?",
  WarnEditCond : "Field \"Object modification conditions\" not empty! Replace old text?",
  WarnAddActionTemplate : "Field \"Action after addition of object\" not empty! Replace old text?",
  WarnEditActionTemplate : "Field \"Action after modification of object\" not empty! Replace old text?",
  WarnCheckActionTemplate : "Field \"Action after turning object ON or OFF\" not empty! Replace old text?",
  WarnDeleteActionTemplatee : "Field \"Action after deleting object\" not empty! Replace old text?", 
  WarnAuthMail : "Restore the default value?",
  WarnReplace : "Current value of a template will be replaced. Continue?",
  FieldFromUser : "Fields from system table User",
  Drop : "Delete",
  DropHard : "Delete?",
  Cancel : "Cancel",
  AddSubsection: "add subsection",
  DebugCheckData: "Check data",
  MessageError: "Error",
  SystemName: "Netcat",
  NetcatCMS: "Content Management System " + this.SystemName,
  RemindSaveWarning: "You have unsaved changes. Continue without saving?",
  Close: "Close",
  DragAndDropConfirm: {
    buttons: { inside: 'Move', default: 'OK' },
    site_below_site: { title: 'Site order change', text: 'The “%1” site will be positioned after the “%2” site in site lists.' },
    sub_inside_sub: { title: 'Subdivision relocation', text: 'The “%1” subdivision will be moved inside the “%2” subdivision.' },
    sub_below_sub: { title: 'Subdivision order change', text: 'The “%1” subdivision will be positioned after the “%2” subdivision in subdivision lists.' },
    sub_firstIn_sub: { title: 'Subdivision order change', text: 'The “%1” subdivision will be the first in the “%2” subdivision.' },
    sub_inside_site: { title: 'Subdivision relocation', text: 'The “%1” subdivision will be moved to the “%2” site root.' },
    sub_firstIn_site: { title: 'Subdivision order change', text: 'The “%1” subdivision will the first in the “%2” site root.' },
    subclass_inside_sub: { title: 'Infoblock relocation', text: 'The “%1” infoblock will be moved to the “%2” subdivision.' },
    message_inside_sub: { title: 'Object relocation', text: '“%1” will be moved to an infoblock inside the “%2” subdivision.' },
    message_inside_message: { title: 'Object order change', text: '“%1” will be positioned after “%2” when default sorting is used.', button: 'OK' },
    template_inside_template: { title: 'Template hierarchy change', text: 'The “%1” template will become a descendant of the “%2” template.', button: 'OK' },
    template_below_template: { title: 'Template order change', text: 'The “%1” template will be positioned after the “%2” template in template lists.' },
    template_firstIn_template: { title: 'Template order change', text: 'The “%1” template will be first in descendant lists of the “%2” template.' },
    field_below_field: { title: 'Field order change', text: 'The “%1” field will be positioned after the “%2” field in field list and standard object forms.' },
    systemfield_below_systemfield: { title: 'Field order change', text: 'The “%1” field will be positioned after the “%2” field in field list and standard object forms.' },
    dataclass_below_dataclass: { title: 'Component order change', text: 'The “%1” component will be positioned after the “%2” component in the component lists.' },
    dataclass_inside_group: { title: 'Component group change', text: 'The “%1” component will be moved to the “%2” group.' }
  },
  DisallowMoveAndDeleteInformation: {
    disallow_move_sub: { title: 'Disallow move subdivision', text: 'The subdivision disallow move.' }
  }
};