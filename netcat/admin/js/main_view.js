/* $Id: main_view.js 8377 2012-11-08 14:18:24Z lemonade $ */

mainView = {
    TopTitle: {},
    chan: 0,  // 1 - есть изменения, 0 - нету
    remindSaveTrigger: false,
    to_tab_id: 0, // таб, на которой надо переключиться
    count: 0,
    showMainView: function() {},
    showStartScreen: function() {},
    //ckeditors_state: Array(),  // состояния визуальных редакторов

    setHeader: function(headerText, subheaderText) {
        headerText = headerText || '';
        subheaderText = subheaderText || '';
        if (subheaderText) {
            subheaderText = ' <small>' + subheaderText.toString() + '</small>';
        }
        var header = headerText + subheaderText;
        document.getElementById('mainViewHeader').innerHTML = header ? "<h1>" + header + "</h1>" : '';

        this.TopTitle["headerText"] = headerText.toString().replace(/<.*?>/ig, '');
    },

    activeTab: null,

    addTab: function(name, caption, action, isActive, unactive, tab_settings) {
        if (caption === ncLang.NetcatCMS) {
            $nc('div.slider_block').hide();
        } else {
            $nc('div.slider_block').show();
        }
        var tabContainer = $nc('#mainViewTabs');
        var tabContainerTray = $nc('#mainViewTabsTray');

        var icon = 'sprite' in tab_settings ? '<i class="nc-icon nc--' + tab_settings.sprite + '"></i>' : '';
        var tab = $nc('<li id="mainViewTab_' + name + '"><span>' + icon + caption + '</span></li>');

        //var spacer = document.createElement('DIV');
        //spacer.className = 'between_tabs';

        //var tab = document.createElement('DIV');
        //tab.id = 'mainViewTab_'+name;
        //var liclass = '';

        if (isActive) {
            //$nc('#' + tab.id + ' li').addClass('sel');
            this.activeTab = tab.attr('id');
            this.TopTitle["activeTab"] = caption;
            this.updateTopTitle();
            tab.addClass('sel');
            //var liclass = 'sel';
        }

        //tab.innerHTML = "<li class='"+liclass+"'>" + caption + "</li>";
        if (!unactive) {
            tab.click(function() {
                mainView.switchTab(this.id);
            });
        }
        tab.get(0).action = action;

        //var tabContainer = document.getElementById('mainViewTabs');

        if ('pull_right' in tab_settings) {
            tabContainerTray.append(tab);
        } else {
            tabContainer.append(tab);
        }

        //.append('<div class="between_tabs"></div>')
        //tabContainer.appendChild(tab);
        //tabContainer.appendChild(spacer);
    },

    removeAllTabs: function() {
        nc('#mainViewTabs').html('');
        nc('#mainViewTabsTray').html('');
        nc('#mainViewToolbar').html('');
        this.activeTab = null;
        this.TopTitle["activeTab"] = "";
    },

    switchTab: function(tabId, dontEvalAction, dontCheck) {
        if (tabId == 'mainViewTab_view') {
            var el = document.getElementById(tabId);
            if (el && el.action) {
                eval(el.action);
            }
            return true;
        }

        to_tab_id = tabId;
        if (!dontCheck && this.chan && REMIND_SAVE == '1' && this.remindSaveTrigger) {
            this.checkRemindSave();
            return true;
        }

        if (this.activeTab) {
            if (this.activeTab == tabId) {
                if (!dontEvalAction) { //also: call from "onlick",  but no from "update"
                    urlDispatcher.process(window.location.hash);
                }
                return true;
            }
            var oActiveTab = document.getElementById(this.activeTab);
            $nc('li#' + oActiveTab.id + '').removeClass('sel');
        }
        this.displayStar(0);
        this.chan = 0;

        if (!document.getElementById(tabId)) {
            return;
        }

        if (null != document.getElementById(this.activeTab)) {
            document.getElementById(this.activeTab).title = '';
        }

        document.getElementById(tabId).title = TEXT_REFRESH;
        var oTab = document.getElementById(tabId);
        if (!oTab) return false;
        if (!dontEvalAction && oTab.action) {
            eval(oTab.action);
        }

        $nc('#' + tabId).addClass('sel');

        this.activeTab = tabId;
        this.TopTitle["activeTab"] = oTab.firstChild.textContent;
        this.updateTopTitle();
    },

    toolbar: null, // to be created during initialization on window load
    init: function() {
        this.toolbar = new toolbar('mainViewToolbar');
        this.oStartScreen = document.getElementById('startScreen');
        this.oMainView = document.getElementById('mainView');
        this.oIframe = document.getElementById('mainViewIframe');
        this.TopTitle["Netcat"] = ncLang.SystemName;
    },

    loadIframe: function(url) {
        // FF restores frame locations on reload -- prevent it
        this.showMainView();
        var fullURL = window.location.protocol + '//' + window.location.host + url;
        if (this.oIframe) {
            this.oIframe.contentWindow.location.replace(url);
        }

        nc.process_start('loadIframe()', this.oIframe);
        $nc(this.oIframe).load(function() {
            nc.process_stop('loadIframe()', this.oIframe);
        });
    },

    refreshIframe: function() {
        var loc = this.oIframe.contentWindow.location.href,
            newLoc = loc.replace(/([&?]rand=)[^&]+/, "$1" + Math.random());

        if (loc == newLoc) {
            newLoc += (loc.match(/\?/) ? '&' : '?') + "rand=" + Math.random();
        }

        this.oIframe.contentWindow.location.href = newLoc;
    },

    submitIframeForm: function(formId) {
        var oForm;

        if (formId) {
            oForm = document.getElementById('mainViewIframe').contentWindow.document.getElementById(formId);
        }

        if (!oForm) {
            oForm = document.getElementById('mainViewIframe').contentWindow.document.forms.item(0);
        }

        var iframeCkeditor = document.getElementById('mainViewIframe').contentWindow.CKEDITOR;

        if (iframeCkeditor != 'undefined' && iframeCkeditor && iframeCkeditor.hasOwnProperty('instances')) {
            for (var instance_name in iframeCkeditor.instances) {
                iframeCkeditor.instances[instance_name].updateElement();
            }
        }

        // don’t submit if there’ an onsubmit handler and it returns false
        if (typeof oForm.onsubmit == "function" && oForm.onsubmit() === false) {
            return;
        }

        oForm.submit();
    },

    submitIframeFormWithJson: function() {
        var oForm;

        oForm = document.getElementById('mainViewIframe').contentWindow.document.forms.item(0);

        var $oForm = $nc(oForm);

        if ($oForm.find('INPUT[name=collect_post]').length) {
            var $input = $nc('<input type="hidden" name="collected_post"/>');
            $input.val($oForm.serialize());
            $input.prependTo($oForm);

            $oForm.find('INPUT,SELECT').not('[name=collected_post]').attr('name', '');
        }

        this.submitIframeForm();
    },

    resetIframeForm: function() {
        document.getElementById('mainViewIframe').contentWindow.document.forms.item(0).reset();
    },

    /* { headerText
     *   tabs: { id
     *           caption
     *           location
     *           unactive
     *         }
     *   toolbar: [ { id
     *                caption
     *                action | location
     *                group
     *               }
     *            ]
     *   actionButtons:  [ { id
     *                       caption
     *                       action | location
     *                       align
     *                     }
     *                   ]
     *
     *   activeTab
     *   activeToolbarButtons: [ btnname1, btnname2 ]
     *
     *   locationHash
     *
     *   tabsCrc
     *   toolbarCrc
     *   actionButtonsCrc
     *
     *   treeMode
     *   treeSelectedNode
     *
     *   treeChanges { 'addNode': [ <nodeData>+ ],
     *                 'updateNode': [ <nodeData>+ ],
     *                 'deleteNode': [ nodeId+ ],
     *                 'moveNode': [ nodeId, position, refNodeId ]
     *     // both addNode and updateNode call tree.addNode()
     *
     */
    currentSettings: {
        headerText: null,
        tabs: null,
        toolbar: null,
        actionButtions: null,
        activeTab: null,
        locationHash: null,
        tabsCrc: null,
        toolbarCrc: null,
        actionButtonsCrc: null
    },

    updateSettings: function(newSettings) {
        if (!newSettings) return false;

        this.chan = 0;
        this.remindSaveTrigger = false;

        var currentSettings = this.currentSettings; // 'for short'

        this.setHeader(newSettings.headerText, newSettings.subheaderText);

        // hash has changed
        if (newSettings.locationHash) {
            urlDispatcher.updateHash(newSettings.locationHash);
        }
        // of hash has changed


        if (newSettings.tabsCrc != currentSettings.tabsCrc) {

            this.removeAllTabs();
            for (var i = 0; i < newSettings.tabs.length; i++) {
                var action = "";
                if (newSettings.tabs[i].location && !newSettings.tabs[i].unactive) {
                    action = 'urlDispatcher.load("' + newSettings.tabs[i].location + '")';
                }
                if (newSettings.tabs[i].action) {
                    action = newSettings.tabs[i].action;
                }
                var isActive = (newSettings.activeTab == newSettings.tabs[i].id);
                this.addTab(newSettings.tabs[i].id, newSettings.tabs[i].caption, action, isActive, newSettings.tabs[i].unactive, newSettings.tabs[i]);
            }

            if (null != document.getElementById('mainViewTab_' + newSettings.activeTab)) {
                document.getElementById('mainViewTab_' + newSettings.activeTab).title = TEXT_REFRESH;
            }
        } else {
            this.switchTab('mainViewTab_' + newSettings.activeTab, true);
        }

        if (typeof newSettings.tabs.length == 'undefined' || newSettings.tabs.length < 2) {
            $nc('#tabs').hide();
            this.TopTitle["headerText"] = ('' + (newSettings.headerText || '') + (newSettings.subheaderText ? ' / ' + newSettings.subheaderText : '')).replace(/<.*?>/ig, '');

            this.updateTopTitle();
        }

        // toolbar has been changed

        if (!newSettings.toolbar.length) {
            try {
                this.toolbar.makeEmptyToolbar();
            } catch (e) {}
            $nc('#sub_tabs').hide();
        } else if (this.toolbar) {
            this.toolbar.clear();
            $nc('#sub_tabs').css("display", "block");
            for (var i = 0; i < newSettings.toolbar.length; i++) {
                var btn = newSettings.toolbar[i];
                if (btn.location) btn.action = 'urlDispatcher.load("' + btn.location + '")';
                this.toolbar.addButton(btn);
            }
        }

        // tabs changed

        if (newSettings.activeToolbarButtons && newSettings.activeToolbarButtons.length && this.toolbar) {
            for (var i = 0; i < newSettings.activeToolbarButtons.length; i++) {
                var oBtn = document.getElementById(this.toolbar.toolbarId + '_' + newSettings.activeToolbarButtons[i]);
                if (oBtn) {
                    oBtn.turnOn();
                    this.TopTitle["activeButton"] = oBtn.textContent;
                    this.updateTopTitle();
                }
            }
        }

        // ActionButtons have been changed
        if (newSettings.actionButtonsCrc != currentSettings.actionButtonsCrc) {
            var html = '';
            for (var i = 0; i < newSettings.actionButtons.length; i++) {
                var btn = newSettings.actionButtons[i];
                var style = 'save';
                if (null != btn.style) {
                    style += ' ' + btn.style;
                }
                var align = (btn.align == 'left') ? 'left' : 'right';

                if (btn.location) {
                    btn.action = "urlDispatcher.load('" + btn.location + "')";
                }

                var border = typeof(btn.red_border) != 'undefined' && btn.red_border ? 'border: 2px solid red;' : '';

                html += '<div class=\"' + style + '\" style=\"' + border + 'float: ' + align + ';\" onclick=\"' + btn.action + '\" title = \"' + btn.caption + '\">' + btn.caption + '</div>';
            }
            var mvb = $nc('#mainViewButtons');
            mvb.html(html);
            if ($nc('div', mvb).length == 0) {
                mvb.parent().hide();
                $nc('.clear_footer').hide();
                $nc('.middle_border').css({top: '0px'});
            } else {
                // В Firefox элемент с `display: none;` в CSS иногда не отображается через метод `jQuery.show();`
                mvb.parent().css('display', 'block');
                $nc('.clear_footer').show();
                //$nc('.middle_border').css({top:'-45px'});
            }
        }

        // changes in the tree: addNode, updateNode, deleteNode
        // ANY OTHER tree METHODS CAN BE ADDED TO newSettings WITHOUT CHANGES
        // TO THIS CODE
        // drawback: must be changed in case tree methods are refactored!
        var tree;
        if (newSettings.treeChanges && (tree = document.getElementById('treeIframe').contentWindow.tree)) {
            for (var method in newSettings.treeChanges) {
                if (typeof tree[method] == 'function' && newSettings.treeChanges[method].length) {
                    for (var i = 0; i < newSettings.treeChanges[method].length; i++) {
                        // call method in the tree
                        tree[method](newSettings.treeChanges[method][i]);
                    }
                }
            }
        } // of if "there are tree changes and tree exists"

        // treeMode determines type of content in the tree
        if (newSettings.treeMode) {
            treeSelector.changeMode(newSettings.treeMode, newSettings.treeSelectedNode);
        }

        if (newSettings.addNavBarCatalogue) {
            var $navBar = $nc('BODY.nc-admin .nc-navbar');
            if ($navBar.length != 0) {
                var $newSite = $nc('<li><a href="' + newSettings.addNavBarCatalogue.href + '"><i class="nc-icon nc--site"></i> ' + newSettings.addNavBarCatalogue.name + '</a></li>');
                var $dropdown = $navBar.find('LI.nc--dropdown').eq(0);
                var $divider = $dropdown.find('LI.nc-divider');
                if ($divider.length) {
                    $divider.before($newSite);
                } else {
                    var $ul = $dropdown.find('UL');
                    $ul.prepend($nc('<li class="nc-divider"></li>'));
                    $ul.prepend($newSite);
                }
            }
        }

        if (newSettings.deleteNavBarCatalogue) {
            var $navBar = $nc('BODY.nc-admin .nc-navbar');
            if ($navBar.length != 0) {
                var $dropdown = $navBar.find('LI.nc--dropdown').eq(0);
                for (var index in newSettings.deleteNavBarCatalogue) {
                    var id = newSettings.deleteNavBarCatalogue[index];
                    $dropdown.find('A').each(function() {
                        if ($nc(this).attr('href') == '#site.map(' + id + ')') {
                            $nc(this).closest('LI').remove();
                            return false;
                        }
                        return true;
                    });
                }

                var $divider = $dropdown.find('LI.nc-divider');
                if ($divider.prev('LI').length == 0) {
                    $divider.remove();
                }
            }
        }

        // SAVE SETTINGS
        this.currentSettings = newSettings;


        if (!document.all) { // reflow bugs in mozilla & opera
            triggerResize();
        }
    },

    updateTopTitle: function() {
        var title = "";
        var i, value, value_bak;
        for (i in this.TopTitle) {
            value = this.TopTitle[i];
            if ((value_bak && value_bak == value) || value == "" || value == undefined) continue;
            title += "" + (value_bak ? " / " : "") + value + "";
            value_bak = value;
        }
        this.TopTitle["activeButton"] = "";
        top.window.document.title = title;
    }, // updateTopTitle

    displayStar: function(visible) { //Показать звездочку. 1 - есть, 0 - нету.
        var $activeTab = $nc('#' + this.activeTab);
        if ($activeTab.length) {
            $activeTab.find('.star').remove();
            if (visible) {
                var $star = $nc('<span class="star"> *</span>').css('color', 'red');
                $star.appendTo($activeTab);
            }
        }
    }, //displayStar

    checkRemindSave: function() {
        if (this.chan) {
            if (confirm(ncLang.RemindSaveWarning)) {
                mainView.rsExit()
            }
        } else {
            this.switchTab(to_tab_id, 0, 1);
        }
    },

    rsExit: function() {
        this.displayStar(0);
        this.switchTab(to_tab_id, 0, 1);
    },


    rsCancel: function() {
        document.getElementById('remindSave').style.display = 'none';
    },


    rsSave: function() {
        var oIframe = top.frames['mainViewIframe'];
        var docum = (oIframe.contentWindow || oIframe.contentDocument || oIframe.document);

        oForm = docum.forms.item(0) || docum.document.forms.item(0);

        if (typeof oIframe.formAsyncSave == 'function') {
            oIframe.formAsyncSave(oForm, {
                '*': 'top.mainView.rsStatus(this.xhr)'
            });
        } else if (typeof docum.formAsyncSave == 'function') {
            docum.formAsyncSave(oForm, {
                '*': 'top.mainView.rsStatus(this.xhr)'
            });
        } else {
            this.rsExit();
        }

        document.getElementById('remindSave').style.display = 'none';
    },

    rsStatus: function(xhr) {
        if (xhr.status != "200" || xhr.readyState == 4) {
            this.rsExit();
        }
    },

    attachClickHandler: function(selector, handler) {
        $nc(selector).attr('onclick', '').unbind('click').bind('click', handler);
    }
}; // of MAINVIEW

/** INIT **/
bindEvent(window, 'load', function() {
    mainView.init()
});
