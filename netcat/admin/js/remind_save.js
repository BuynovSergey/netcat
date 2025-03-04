/* Файл содержит фунцкии для добавления\удаления хэндлеров в редактировании компонента */

var docum = '';
if ( top.frames['mainViewIframe'] ) {
    docum = top.frames['mainViewIframe'].document;
} else {
	docum = document;
}

// Add handler - cross-browser
function addHandler (object, event, handler) {
	if (object === null) {
		return;
	}
    if (typeof object.addEventListener != 'undefined') { //For FireFox, Opera :)
        object.addEventListener(event, handler, false);
    }
    else { //For IE :(
        object.attachEvent('on' + event, handler);
    }

    return;
}


// Function. Delete handler - cross-browser
function removeHandler (object, event, handler) {
    if (typeof object.removeEventListener != 'undefined') {
        object.removeEventListener(event, handler, false);
    }
    else {
        object.detachEvent('on' + event, handler);
    }

    return;
}


// Initiasilation -  no changes.
function initRemind () {
	if (!parent.mainView) {
		return;
	}
    parent.mainView.remindSaveTrigger = true;
    parent.mainView.displayStar(0);
    parent.mainView.chan = 0;

    return;
}


// widget save remind
function remind_widgetclass () {
    var Flied  = new Array('PageBody', 'Settings');
    var Action = new Array('keypress', 'change');
    var i, j;

    initRemind();

    for (i = 0; i < Flied.length; i++) {
        for (j = 0; j < Action.length; j++) {
            addHandler(docum.getElementById(Flied[i]), Action[j], chn_redaction );
        }
    }

    return;
}


//For tab 'Edit component'
function remind_redaction () {
    var Flied  = new Array('ListPrefix', 'ListBody', 'ListSuffix', 'PageBody', 'Settings' );
    var Action = new Array('keypress', 'change');
    var i, j;

    initRemind();

    for (i = 0; i < Flied.length; i++) {
        for (j = 0; j < Action.length; j++) {
            addHandler(document.getElementById(Flied[i]), Action[j], chn_redaction );
        }
    }

    return;
}


//For tab 'Edit component' , when action is be
function chn_redaction (event) {
    var Flied  = new Array('ListPrefix', 'ListBody', 'ListSuffix', 'PageBody', 'Settings' );
    var Action = new Array('keypress', 'change');

    if(event.ctrlKey) return;

    parent.mainView.chan = 1;
    parent.mainView.displayStar(1);

    for (i = 0; i < Flied.length; i++) {
        for (j = 0; j < Action.length; j++) {
            removeHandler(document.getElementById(Flied[i]), Action[j], chn_redaction );
        }
    }

    return;
}


//For tab 'Add'
function remind_add () {
    var Flied  = new Array('AddTemplate', 'AddCond', 'AddActionTemplate');
    var Action = new Array('keypress', 'change');
    var i, j;

    initRemind();

    for (i = 0; i < Flied.length; i++) {
        for (j = 0; j < Action.length; j++) {
            addHandler(document.getElementById(Flied[i]), Action[j], chn_add );
        }
    }

    return;
}


//For tab 'Add' , when action is be
function chn_add (event) {
    var Flied  = new Array('AddTemplate', 'AddCond', 'AddActionTemplate');
    var Action = new Array('keypress', 'change');

    if (event.ctrlKey) return;

    parent.mainView.chan = 1;
    parent.mainView.displayStar(1);

    for (i = 0; i < Flied.length; i++) {
        for (j = 0; j < Action.length; j++) {
            removeHandler(document.getElementById(Flied[i]), Action[j], chn_add );
        }
    }

    return;
}



//For tab 'Change'
function remind_edit () {
    var Flied  = new Array('EditTemplate', 'EditCond', 'EditActionTemplate', 'CheckActionTemplate');
    var Action = new Array('keypress', 'change');
    var i, j;

    initRemind();

    for (i = 0; i < Flied.length; i++) {
        for (j = 0; j < Action.length; j++) {
            addHandler(document.getElementById(Flied[i]), Action[j], chn_edit );
        }
    }

    return;
}


//For tab 'Change' , when action is be
function chn_edit (event) {
    var Flied  = new Array('EditTemplate', 'EditCond', 'EditActionTemplate', 'CheckActionTemplate');
    var Action = new Array('keypress', 'change');

    if (event.ctrlKey) return;

    parent.mainView.chan = 1;
    parent.mainView.displayStar(1);

    for (i = 0; i < Flied.length; i++) {
        for (j = 0; j < Action.length; j++) {
            removeHandler(document.getElementById(Flied[i]), Action[j], chn_edit );
        }
    }

    return;
}


//For tab 'Delete'
function remind_delete () {
    var Flied  = new Array('DeleteTemplate', 'DeleteCond', 'DeleteActionTemplate');
    var Action = new Array('keypress', 'change');
    var i, j;

    initRemind();

    for (i = 0; i < Flied.length; i++) {
        for (j = 0; j < Action.length; j++) {
            addHandler(document.getElementById(Flied[i]), Action[j], chn_delete );
        }
    }

    return;
}


//For tab 'Delete' , when action is be
function chn_delete (event) {
    var Flied  = new Array('DeleteTemplate', 'DeleteCond', 'DeleteActionTemplate');
    var Action = new Array('keypress', 'change');

    if (event.ctrlKey) return;

    parent.mainView.chan = 1;
    parent.mainView.displayStar(1);

    for (i = 0; i < Flied.length; i++) {
        for (j = 0; j < Action.length; j++) {
            removeHandler(document.getElementById(Flied[i]), Action[j], chn_delete );
        }
    }

    return;
}


//For tab 'Search'
function remind_search () {
    var Flied  = new Array('FullSearchTemplate', 'SearchTemplate');
    var Action = new Array('keypress', 'change');
    var i, j;

    initRemind();

    for (i = 0; i < Flied.length; i++) {
        for (j = 0; j < Action.length; j++) {
            addHandler(document.getElementById(Flied[i]), Action[j], chn_search );
        }
    }

    return;
}


//For tab 'Search' , when action is be
function chn_search (event) {
    var Flied  = new Array('FullSearchTemplate', 'SearchTemplate');
    var Action = new Array('keypress', 'change');

    if (event.ctrlKey) return;

    parent.mainView.chan = 1;
    parent.mainView.displayStar(1);

    for (i = 0; i < Flied.length; i++) {
        for (j = 0; j < Action.length; j++) {
            removeHandler(document.getElementById(Flied[i]), Action[j], chn_search );
        }
    }

    return;
}


//For tab 'Subscrib'
function remind_subscrib () {
    var Flied  = new Array('SubscribeCond', 'SubscribeTemplate');
    var Action = new Array('keypress', 'change');
    var i, j;

    initRemind();

    for (i = 0; i < Flied.length; i++) {
        for (j = 0; j < Action.length; j++) {
            addHandler(document.getElementById(Flied[i]), Action[j], chn_subscrib );
        }
    }

    return;
}


//For tab 'Subscrib' , when action is be
function chn_subscrib (event) {
    var Flied  = new Array('SubscribeCond', 'SubscribeTemplate');
    var Action = new Array('keypress', 'change');

    if (event.ctrlKey ) return;

    parent.mainView.chan = 1;
    parent.mainView.displayStar(1);

    for (i = 0; i < Flied.length; i++) {
        for (j = 0; j < Action.length; j++) {
            removeHandler(document.getElementById(Flied[i]), Action[j], chn_subscrib );
        }
    }

    return;
}

//For template editing
function remind_template_edit () {
    initRemind();

    $nc('TEXTAREA').bind('keypress change', chn_template_edit);

    return;
}


//For template editing , when action is be
function chn_template_edit (event) {
    if (event.ctrlKey) return;

    parent.mainView.chan = 1;
    parent.mainView.displayStar(1);

    $nc('TEXTAREA').unbind('keypress change');

    return;
}