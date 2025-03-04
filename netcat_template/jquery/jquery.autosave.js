(function($) {

    $.fn.autosave = function(options) {
        var identifier = $.map(this, function(obj, i) {
            return $(obj).attr("id") + $(obj).attr("name")
        }).join();

        var autosave = Autosave.getInstance(identifier);
        autosave.protect(this, options);
        return autosave;
    };

    var dataSender = {};

    dataSender.set = function(key, value) {
    };

    dataSender.get = function(key) {
    };

    dataSender.remove = function(key) {
    };

    Autosave = (function() {
        var params = {
            instantiated: [],
            started: []
        };

        function init() {

            return {
                stopTimer: function() {
                    this.targets = [];
                    clearTimeout(this.timeoutId);
                    clearTimeout(this.timeoutInnerId);
                },
                setInstanceIdentifier: function(identifier) {
                    this.identifier = identifier
                },
                getInstanceIdentifier: function() {
                    return this.identifier;
                },
                /**
                 * Set plugin initial options
                 *
                 * @param [Object] options
                 *
                 * @return void
                 */
                setInitialOptions: function(options) {
                    var defaults = {
                        excludeFields: [],
                        customKeySuffix: "",
                        locationBased: false,
                        timeout: 0,
                        autoRelease: true,
                        onSave: function() {
                        },
                        onBeforeRestore: function() {
                        },
                        onRestore: function() {
                        },
                        onRelease: function() {
                        }
                    };
                    this.options = this.options || $.extend(defaults, options);
                    this.dataSender = dataSender;
                },
                /**
                 * Set plugin options
                 *
                 * @param [Object] options
                 *
                 * @return void
                 */
                setOptions: function(options) {
                    this.options = this.options || this.setInitialOptions(options);
                    this.options = $.extend(this.options, options);
                },
                /**
                 * Protect specified forms, store it's fields data to local storage and restore them on page load
                 *
                 * @param [Object] targets		forms object(s), result of jQuery selector
                 * @param Object options			plugin options
                 *
                 * @return void
                 */
                protect: function(targets, options) {
                    this.setOptions(options);
                    targets = targets || {};
                    var self = this;
                    this.targets = this.targets || [];
                    this.href = location.hostname + location.pathname + location.search + location.hash;
                    this.targets = $.merge(this.targets, targets);
                    this.targets = $.unique(this.targets);
                    this.targets = $(this.targets);

                    if (self.options.restore == true) {
                        self.restoreAllData();
                    }
                    /*
                     var callback_result = self.options.onBeforeRestore.call(self);
                     if (callback_result === undefined || callback_result) {
                     self.restoreAllData();
                     }
                     
                     if (this.options.autoRelease) {
                     self.bindReleaseData();
                     }
                     */
//                    if (!params.started[ this.getInstanceIdentifier() ]) {
                    if (self.isCKEditorPresent()) {
                        var intervalId = setInterval(function() {
                            if (CKEDITOR.isLoaded) {
                                clearInterval(intervalId);
                                self.bindSaveData();
                                params.started[ self.getInstanceIdentifier() ] = true;
                            }
                        }, 100);
                    } else {
                        self.bindSaveData();
                        params.started[ self.getInstanceIdentifier() ] = true;
                    }
//                    }
                },
                isCKEditorPresent: function() {
                    if (this.isCKEditorExists()) {
                        CKEDITOR.isLoaded = false;
                        CKEDITOR.on('instanceReady', function() {
                            CKEDITOR.isLoaded = true;
                        });
                        var self = this;
                        var cke_element_exsts = false;
                        //cke exists but instances not found
                        self.targets.each(function() {
                            self.findFieldsToProtect($(this)).each(function() {
                                var field = $(this);
                                if ($("#cke_" + field.attr('id')).length) {
                                    cke_element_exsts = true;
                                }
                            });
                        });
                        return cke_element_exsts;
                    } else {
                        return false;
                    }
                },
                isCKEditorExists: function() {
                    return typeof CKEDITOR != "undefined";
                },
                findFieldsToProtect: function(target) {
                    return target.find(":input").not(":submit").not(":reset").not(":button").not(":file").not(":password").not(":disabled").not("[readonly]");
                },
                /**
                 * Bind saving data
                 *
                 * @return void
                 */
                bindSaveData: function() {
                    var self = this;

                    if (self.options.timeout) {
                        self.saveDataByTimeout();
                        if (self.options.noactive == 1) {
                            self.targets.each(function() {
                                self.findFieldsToProtect($(this)).each(function() {
                                    if ($.inArray(this, self.options.excludeFields) !== -1) {
                                        return true;
                                    }
                                    var field = $(this);
                                    self.bindResetTimerOnChange(field);
                                });
                            });
                        }
                    }
                    if (self.options.onkeys)
                        self.targets.each(function() {
                            var targetFormIdAndName = $(this).attr("id") + $(this).attr("name");
                            self.findFieldsToProtect($(this)).each(function() {
                                if ($.inArray(this, self.options.excludeFields) !== -1) {
                                    // Returning non-false is the same as a continue statement in a for loop; it will skip immediately to the next iteration.
                                    return true;
                                }
                                var field = $(this);
                                var prefix = (self.options.locationBased ? self.href : "") + targetFormIdAndName + field.attr("name") + self.options.customKeySuffix;
                                if (field.is(":text") || field.is("textarea")) {
                                    if (!self.options.timeout) {
                                        self.bindSaveDataImmediately(field, prefix);
                                    }
                                }
                                self.bindSaveDataOnChange(field);
                            });
                        });
                },
                /**
                 * Save all protected forms data to Local Storage.
                 * Common method, necessary to not lead astray user firing 'data is saved' when select/checkbox/radio
                 * is changed and saved, while text field data is saved only by timeout
                 *
                 * @return void
                 */
                saveAllData: function() {
                    var self = this;
                    self.targets.each(function() {
                        var targetFormIdAndName = $(this).attr("id") + $(this).attr("name");
                        var multiCheckboxCache = {};

                        self.findFieldsToProtect($(this)).each(function() {
                            var field = $(this);
                            if ($.inArray(this, self.options.excludeFields) !== -1 || field.attr("name") === undefined) {
                                // Returning non-false is the same as a continue statement in a for loop; it will skip immediately to the next iteration.
                                return true;
                            }
                            var prefix = (self.options.locationBased ? self.href : "") + targetFormIdAndName + field.attr("name") + self.options.customKeySuffix;
                            var value = field.val();

                            if (field.is(":checkbox")) {
                                if (field.attr("name").indexOf("[") !== -1) {
                                    if (multiCheckboxCache[ field.attr("name") ] === true) {
                                        return;
                                    }
                                    value = [];
                                    $("[name='" + field.attr("name") + "']:checked").each(function() {
                                        value.push($(this).val());
                                    });
                                    multiCheckboxCache[ field.attr("name") ] = true;
                                } else {
                                    value = field.is(":checked");
                                }
                                self.saveToStorage(prefix, value, false);
                            } else if (field.is(":radio")) {
                                if (field.is(":checked")) {
                                    value = field.val();
                                    self.saveToStorage(prefix, value, false);
                                }
                            } else {
                                if (self.isCKEditorExists()) {
                                    var editor;
                                    if (editor = CKEDITOR.instances[ field.attr("name") ] || CKEDITOR.instances[ field.attr("id") ]) {
                                        editor.updateElement();
                                        self.saveToStorage(prefix, field.val(), false);
                                    } else {
                                        self.saveToStorage(prefix, value, false);
                                    }
                                } else {
                                    self.saveToStorage(prefix, value, false);
                                }
                            }
                        });
                    });
                    self.options.onSave.call(self);
                },
                /**
                 * Restore forms data from Local Storage
                 *
                 * @return void
                 */
                restoreAllData: function() {
                    var self = this;
                    var restored = false;

                    self.targets.each(function() {
                        var target = $(this);
                        var targetFormIdAndName = $(this).attr("id") + $(this).attr("name");

                        self.findFieldsToProtect(target).each(function() {
                            if ($.inArray(this, self.options.excludeFields) !== -1) {
                                // Returning non-false is the same as a continue statement in a for loop; it will skip immediately to the next iteration.
                                return true;
                            }
                            var field = $(this);

                            var prefix = (self.options.locationBased ? self.href : "") + targetFormIdAndName + field.attr("name") + self.options.customKeySuffix;
                            var resque = (typeof self.options.fields_to_restore[field.attr("name")] !== 'undefined') ? self.options.fields_to_restore[field.attr("name")] : null;
                            if (resque !== null) {
                                self.restoreFieldsData(field, resque);
                                restored = true;
                            }
                        });
                    });

//                    if (restored) {
//                        self.options.onRestore.call(self);
//                    }
                },
                /**
                 * Restore form field data from local storage
                 *
                 * @param Object field		jQuery form element object
                 * @param String resque	 previously stored fields data
                 *
                 * @return void
                 */
                restoreFieldsData: function(field, resque) {
                    if (field.attr("name") === undefined) {
                        return false;
                    }
                    if (field.is(":checkbox") && resque !== "false" && field.attr("name").indexOf("[") === -1) {
                        field.attr("checked", "checked");
                    } else if (field.is(":checkbox") && resque === "false" && field.attr("name").indexOf("[") === -1) {
                        field.removeAttr("checked");
                    } else if (field.is(":radio")) {
                        if (field.val() === resque) {
                            field.attr("checked", "checked");
                        }
                    } else if (field.attr("name").indexOf("[") === -1) {
                        field.val(resque);
                    } else {
                        resque = resque.split(",");
                        field.val(resque);
                    }
                },
                /**
                 * Bind immediate saving (on typing/checking/changing) field data to local storage when user fills it
                 *
                 * @param Object field		jQuery form element object
                 * @param String prefix	 prefix used as key to store data in local storage
                 *
                 * @return void
                 */
                bindSaveDataImmediately: function(field, prefix) {
                    var self = this;
                    if ('onpropertychange' in field) {
                        field.get(0).onpropertychange = function() {
                            self.saveToStorage(prefix, field.val());
                        };
                    } else {
                        field.get(0).oninput = function() {
                            self.saveToStorage(prefix, field.val());
                        };
                    }
                    if (this.isCKEditorExists()) {
                        var editor;
                        if (editor = CKEDITOR.instances[ field.attr("name") ] || CKEDITOR.instances[ field.attr("id") ]) {
                            editor.document.on('keyup', function() {
                                editor.updateElement();
                                self.saveToStorage(prefix, field.val());
                            });
                        }
                    }
                },
                /**
                 * Save data to Local Storage and fire callback if defined
                 *
                 * @param String key
                 * @param String value
                 * @param Boolean [true] fireCallback
                 *
                 * @return void
                 */
                saveToStorage: function(key, value, fireCallback) {
                    // if fireCallback is undefined it should be true
                    fireCallback = fireCallback === undefined ? true : fireCallback;
                    this.dataSender.set(key, value);
                    if (fireCallback && value !== "") {
                        this.options.onSave.call(this);
                    }
                },
                /**
                 * Bind saving field data on change
                 *
                 * @param Object field		jQuery form element object
                 *
                 * @return void
                 */
                bindSaveDataOnChange: function(field) {
                    var self = this;
                    field.change(function() {
                        self.saveAllData();
                    });
                },
                resetTimer: function(self) {
                    if (nc_autosave_type === 'timer') {
                        clearTimeout(self.timeoutId);
                        clearTimeout(self.timeoutInnerId);
                        var targetForms = self.targets;
                        self.timeoutId = setTimeout((function() {
                            function timeout() {
                                self.saveAllData();
                                self.timeoutInnerId = setTimeout(timeout, self.options.timeout * 1000);
                            }
                            return timeout;
                        })(targetForms), self.options.timeout * 1000);
                    }
                },
                bindResetTimerOnChange: function(field) {
                    if (nc_autosave_type === 'timer') {
                        var self = this;
                        if ('onpropertychange' in field) {
                            field.get(0).onpropertychange = function() {
                                self.resetTimer(self);
                            };
                        } else {
                            field.get(0).oninput = function() {
                                self.resetTimer(self);
                            };
                        }
                        if (this.isCKEditorExists()) {
                            CKEDITOR.on('instanceReady', function(evt){
                                var editor = evt.editor;
                                if (editor = CKEDITOR.instances[ field.attr("name") ] || CKEDITOR.instances[ field.attr("id") ]) {
                                    editor.document.on('keyup', function() {
                                        editor.updateElement();
                                        self.resetTimer(self);
                                    });
                                }
                            });
                        }
                    }
                },
                /**
                 * Saving (by timeout) field data to local storage when user fills it
                 *
                 * @return void
                 */
                saveDataByTimeout: function() {
                    var self = this;
                    var targetForms = self.targets;
                    self.timeoutId = setTimeout((function() {
                        function timeout() {
                            self.saveAllData();
                            self.timeoutInnerId = setTimeout(timeout, self.options.timeout * 1000);
                        }
                        return timeout;
                    })(targetForms), self.options.timeout * 1000);
                },
                /**
                 * Bind release form fields data from local storage on submit/reset form
                 *
                 * @return void
                 */
                bindReleaseData: function() {
                    var self = this;
                    self.targets.each(function() {
                        var target = $(this);
                        var formIdAndName = target.attr("id") + target.attr("name");
                        $(this).bind("submit reset", function() {
                            self.releaseData(formIdAndName, self.findFieldsToProtect(target));
                        });
                    });
                },
                /**
                 * Manually release form fields
                 *
                 * @return void
                 */
                manuallyReleaseData: function() {
                    var self = this;
                    self.targets.each(function() {
                        var target = $(this);
                        var formIdAndName = target.attr("id") + target.attr("name");
                        self.releaseData(formIdAndName, self.findFieldsToProtect(target));
                    });
                },
                /**
                 * Bind release form fields data from local storage on submit/resett form
                 *
                 * @param String targetFormIdAndName	a form identifier consists of its id and name glued
                 * @param Object fieldsToProtect		jQuery object contains form fields to protect
                 *
                 * @return void
                 */
                releaseData: function(targetFormIdAndName, fieldsToProtect) {
                    var released = false;
                    var self = this;

                    // Released form, are not started anymore. Fix for ajax loaded forms.
                    params.started[ self.getInstanceIdentifier() ] = false;

                    fieldsToProtect.each(function() {
                        if ($.inArray(this, self.options.excludeFields) !== -1) {
                            // Returning non-false is the same as a continue statement in a for loop; it will skip immediately to the next iteration.
                            return true;
                        }
                        var field = $(this);
                        var prefix = (self.options.locationBased ? self.href : "") + targetFormIdAndName + field.attr("name") + self.options.customKeySuffix;
                        self.dataSender.remove(prefix);
                        released = true;
                    });

                    if (released) {
                        self.options.onRelease.call(self);
                    }
                }

            };
        }

        return {
            getInstance: function(identifier) {
                if (!params.instantiated[ identifier ]) {
                    params.instantiated[ identifier ] = init();
                    params.instantiated[ identifier ].setInstanceIdentifier(identifier);
                    params.instantiated[ identifier ].setInitialOptions();
                }
                if (identifier) {
                    return params.instantiated[ identifier ];
                }
                return params.instantiated[ identifier ];
            },
            free: function() {
                params = {
                    instantiated: [],
                    started: []
                };
                return null;
            }
        };
    })();
})(jQuery);
