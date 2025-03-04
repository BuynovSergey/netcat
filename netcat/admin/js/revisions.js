var autosave = null;

function InitAutosave(form_id) {

    var restore = false;
    var fields_to_restore = null;
    if (typeof restoredFields !== 'undefined' && restoredFields !== null) { 
        restore = true;
        fields_to_restore = restoredFields;
        restoredFields = null;
    }
    autosave = $nc("#" + form_id).autosave({
        timeout: ((nc_autosave_type === 'timer' && nc_autosave_period > 0) ? nc_autosave_period : 0),
        noactive: ((typeof nc_autosave_noactive !== 'undefined') ? nc_autosave_noactive : 0),
        restore: restore,
        fields_to_restore: fields_to_restore,
        customKeySuffix: 'nc_',
        // чтобы избежать автозаполнения черновыми данными
        onBeforeRestore: function() {
            return false;
        },
        onSave: function(obj) {
            var self = this;
            var post_data = {};
            self.targets.each(function() {
                var targetId = $nc(this).attr("id");
                var multiCheckboxCache = {};

                self.findFieldsToProtect($nc('#' + targetId)).each(function() {
                    var field = $nc(this);
                    if ($nc.inArray(this, self.options.excludeFields) !== -1 || field.attr("name") === undefined) {
                        // Returning non-false is the same as a continue statement in a for loop; it will skip immediately to the next iteration.
                        return true;
                    }
                    var value = field.val();

                    if (field.is(":checkbox")) {
                        if (field.attr("name").indexOf("[") !== -1) {
                            if (multiCheckboxCache[ field.attr("name") ] === true) {
                                return;
                            }
                            value = [];
                            $nc("[name='" + field.attr("name") + "']:checked").each(function() {
                                value.push($nc(this).val());
                            });
                            multiCheckboxCache[ field.attr("name") ] = true;
                        } else {
                            value = field.is(":checked");
                        }
                        post_data[field.attr("name")] = value;
                    } else if (field.is(":radio")) {
                        if (field.is(":checked")) {
                            value = field.val();
                            post_data[field.attr("name")] = value;
                        }
                    } else {
                        if (self.isCKEditorExists()) {
                            var editor;
                            if (editor = CKEDITOR.instances[ field.attr("name") ] || CKEDITOR.instances[ field.attr("id") ]) {
                                editor.updateElement();
                                post_data[field.attr("name")] = field.val();
                            } else {
                                post_data[field.attr("name")] = value;
                            }
                        } else {
                            post_data[field.attr("name")] = value;
                        }
                    }
                });
            });
            $nc.ajax({
                'type': 'POST',
                'url': NETCAT_PATH + 'message.php?isVersion=1&cc=' + post_data.cc,
                'data': post_data,
                success: function(response) {
                    if ($nc('.nc_draft_btn').length) {
                        $nc('.nc_draft_btn').removeClass('nc--loading');
                    }
                }
            });
        }
    });

}
