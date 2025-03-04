/* $Id: comments.js 8300 2012-10-29 14:42:06Z vadim $ */

/**
 * Constructor function
 */
nc_Comments = function (options) {

  this.message_cc = Math.floor(options.message_cc) || 0;
  this.message_id = Math.floor(options.message_id) || 0;
  this.template_id = Math.floor(options.template_id) || 0;
  this.last_updated = Math.floor(options.last_updated) || 0;
  this.MODULE_PATH = options.MODULE_PATH || NETCAT_PATH + 'modules/comments/';
  this.add_block = options.add_block || "";
  this.edit_block = options.edit_block || "";
  this.delete_block = options.delete_block || "";
  this.LOADING = options.LOADING || "loading...";
  this.UNSUBSCRIBE_FROM_ALL = options.UNSUBSCRIBE_FROM_ALL || "unscribe";
  this.SUBSCRIBE_TO_ALL = options.SUBSCRIBE_TO_ALL || "scribe";
  this.all_comments_id = options.all_comments_id;
  this.show_addform = options.show_addform;
  this.show_name = options.show_name;
  this.show_email = options.show_email;
  this.premoderation = options.premoderation;
  this.sorting = options.sorting;
  this.premodtext = options.premodtext;
  this.new_comments_id = options.new_comments_id || 0;
  this.addblock_first = options.addblock_first;
  this.RATING_NOTIFY = options.rating_notify;

  // IDs value: message_cc and message_id
  this.COMMENT_IDS_VALUE = this.message_cc + '_' + this.message_id;
  // Comment object
  this.COMMENT_OBJECT = this.COMMENT_OBJECT_ID + this.COMMENT_IDS_VALUE;
  // Comment block
  this.COMMENT_ID_PREFIX = this.COMMENT_ID + this.COMMENT_IDS_VALUE + '_';
  // Comment reply link prefix
  this.COMMENT_REPLY_ID_PREFIX = this.COMMENT_REPLY_ID + this.COMMENT_IDS_VALUE + '_';
  // Comment edit link prefix
  this.COMMENT_EDIT_ID_PREFIX = this.COMMENT_EDIT_ID + this.COMMENT_IDS_VALUE + '_';
  // Comment delete link prefix
  this.COMMENT_DELETE_ID_PREFIX = this.COMMENT_DELETE_ID + this.COMMENT_IDS_VALUE + '_';
  // Comment text block prefix
  this.COMMENT_TEXT_PREFIX = this.COMMENT_TEXT_ID + this.COMMENT_IDS_VALUE + '_';
  // Comment ratio value prefix
  this.COMMENT_RATING_ID_PREFIX = this.COMMENT_RATING_ID + this.COMMENT_IDS_VALUE + '_';

}

nc_Comments.prototype = {

  COMMENT_FORM_ID: 'nc_commentsForm',
  COMMENT_TEXTAREA_ID: 'nc_commentTextArea',
  COMMENT_BUTTON_ID: 'nc_commentsSubmitButton',
  COMMENT_CANCEL_BUTTON_ID: 'nc_commentsCancelButton',
  COMMENT_ID: 'nc_commentID',
  COMMENT_TEXT_ID: 'nc_commentText',
  COMMENT_OBJECT_ID: 'nc_commentsObj',
  COMMENT_REPLY_ID: 'nc_commentsReply',
  COMMENT_EDIT_ID: 'nc_commentsEdit',
  COMMENT_DELETE_ID: 'nc_commentsDelete',
  COMMENT_ACTION_FILE: 'add.php',
  COMMENT_SUBSCRIBE_FILE: 'subscribe.php',
  COMMENT_GUEST_NAME_ID: 'nc_comments_guest_name',
  COMMENT_GUEST_EMAIL_ID: 'nc_comments_guest_email',
  COMMENT_RATING_ID: 'nc_commentsRating',

  /**
  * Show form function
  */
  Form: function (parent_mess_id, edit) {

    this.dropElement(this.COMMENT_FORM_ID);

    // save "this" context
    nc_Comments.obj = this;
    this.parent_mess_id = Math.floor(parent_mess_id) || 0;

    var commentBlock = document.getElementById(this.COMMENT_ID_PREFIX + this.parent_mess_id);
    var commentText = document.getElementById(this.COMMENT_TEXT_PREFIX + this.parent_mess_id);

    // reply link
    var replyLink = document.getElementById(this.COMMENT_REPLY_ID_PREFIX + this.parent_mess_id) || commentBlock;
    // edit link
    if (edit==1 || edit==2) {
      var editLink = document.getElementById(this.COMMENT_EDIT_ID_PREFIX + this.parent_mess_id);
    }
    // delete link
    if (edit==-1) {
      var deleteLink = document.getElementById(this.COMMENT_DELETE_ID_PREFIX + this.parent_mess_id);
    }

    if (!commentBlock) return false;

    // form
    var formElement = document.createElement('form');
    formElement.setAttribute('id', this.COMMENT_FORM_ID);
    formElement.setAttribute('name', this.COMMENT_FORM_ID);
    formElement.setAttribute('method', 'post');
    formElement.onsubmit = function () {return false;};
    formElement.setAttribute('enctype', 'multipart/form-data');

    if (edit==1) {
      //replyLink.parentNode.insertBefore(formElement, editLink.previousSibling);
      commentText.appendChild(formElement);
    }
    else {
      //replyLink.parentNode.insertBefore(formElement, replyLink.nextSibling);//nextSibling
      if (commentText) {
        commentText.appendChild(formElement);
      }
      else {
        if (this.addblock_first == 1 && this.parent_mess_id == 0) {
          commentBlock.insertBefore(formElement, commentBlock.firstChild);
        } else {
          replyLink.parentNode.insertBefore(formElement, replyLink.nextSibling);//nextSibling
        }
      }
    }

    // cc
    var mccElement = document.createElement('input');
    mccElement.setAttribute('id', 'message_cc');
    mccElement.setAttribute('type', 'hidden');
    mccElement.setAttribute('name', 'message_cc');
    mccElement.setAttribute('value', this.message_cc);
    formElement.appendChild(mccElement);
    // message
    var midElement = document.createElement('input');
    midElement.setAttribute('id', 'message_id');
    midElement.setAttribute('type', 'hidden');
    midElement.setAttribute('name', 'message_id');
    midElement.setAttribute('value', this.message_id);
    formElement.appendChild(midElement);
    // parent message
    var pidElement = document.createElement('input');
    pidElement.setAttribute('id', 'parent_mess_id');
    pidElement.setAttribute('type', 'hidden')
    pidElement.setAttribute('name', 'parent_mess_id');
    pidElement.setAttribute('value', this.parent_mess_id);
    formElement.appendChild(pidElement);
    // template
    pidElement = document.createElement('input');
    pidElement.setAttribute('id', 'template_id');
    pidElement.setAttribute('type', 'hidden')
    pidElement.setAttribute('name', 'template_id');
    pidElement.setAttribute('value', this.template_id);
    formElement.appendChild(pidElement);
    // last comment time
    var luidElement = document.createElement('input');
    luidElement.setAttribute('id', 'last_updated');
    luidElement.setAttribute('type', 'hidden');
    luidElement.setAttribute('name', 'last_updated');
    luidElement.setAttribute('value', this.last_updated);
    formElement.appendChild(luidElement);
    // edit or delete value
    if (edit==1 || edit==-1 || edit==2) {
      var editElement = document.createElement('input');
      editElement.setAttribute('id', 'comment_edit');
      editElement.setAttribute('type', 'hidden');
      editElement.setAttribute('name', 'comment_edit');
      editElement.setAttribute('value', edit);
      formElement.appendChild(editElement);
    }

    switch (edit) {
      // edit
      case 1:
      // get comment text from base
      case 2:
        var edit_bt = unescape(this.edit_block);
        edit_bt = edit_bt.replace(/%FORM_ID/g, this.COMMENT_FORM_ID);
        edit_bt = edit_bt.replace(/%TEXTAREA_ID/g, this.COMMENT_TEXTAREA_ID);
        edit_bt = edit_bt.replace(/%TEXTAREA_VALUE/g, (edit==2 ? this.LOADING : ""));
        edit_bt = edit_bt.replace(/%CANCEL_BUTTON_ID/g, this.COMMENT_CANCEL_BUTTON_ID);
        edit_bt = edit_bt.replace(/%CANCEL_BUTTON_ACTION/g, this.COMMENT_OBJECT + ".dropElement(" + this.COMMENT_OBJECT + ".COMMENT_FORM_ID, true)");
        edit_bt = edit_bt.replace(/%SUBMIT_BUTTON_ID/g, this.COMMENT_BUTTON_ID);
        formElement.innerHTML+= edit_bt;
      break;
      // delete
      case -1:
        var drop_bt = unescape(this.delete_block);
        drop_bt = drop_bt.replace(/%FORM_ID/g, this.COMMENT_FORM_ID);
        drop_bt = drop_bt.replace(/%CANCEL_BUTTON_ID/g, this.COMMENT_CANCEL_BUTTON_ID);
        drop_bt = drop_bt.replace(/%CANCEL_BUTTON_ACTION/g, this.COMMENT_OBJECT + ".dropElement(" + this.COMMENT_OBJECT + ".COMMENT_FORM_ID, true)");
        drop_bt = drop_bt.replace(/%SUBMIT_BUTTON_ID/g, this.COMMENT_BUTTON_ID);
        formElement.innerHTML+= drop_bt;
      break;
      // append
      default:
        var add_bt = unescape(this.add_block);
        add_bt = add_bt.replace(/%FORM_ID/g, this.COMMENT_FORM_ID);
        add_bt = add_bt.replace(/%TEXTAREA_ID/g, this.COMMENT_TEXTAREA_ID);
        add_bt = add_bt.replace(/%TEXTAREA_VALUE/g, "");
        add_bt = add_bt.replace(/%CANCEL_BUTTON_ID/g, this.COMMENT_CANCEL_BUTTON_ID);
        add_bt = add_bt.replace(/%CANCEL_BUTTON_ACTION/g, this.COMMENT_OBJECT + ".dropElement(" + this.COMMENT_OBJECT + ".COMMENT_FORM_ID, true)");
        add_bt = add_bt.replace(/%SUBMIT_BUTTON_ID/g, this.COMMENT_BUTTON_ID);
        add_bt = add_bt.replace(/%GUEST_NAME_ID/g, this.COMMENT_GUEST_NAME_ID);
        add_bt = add_bt.replace(/%GUEST_EMAIL_ID/g, this.COMMENT_GUEST_EMAIL_ID);
        //formElement.innerHTML+= add_bt;
        jQuery(formElement).append(add_bt);

        // hide nc_comments_guest_name input
        if (this.show_name!=1 && document.getElementById(this.COMMENT_GUEST_NAME_ID)) {
          document.getElementById(this.COMMENT_GUEST_NAME_ID).style.display='none';
        }

        // hide nc_comments_guest_email input
        if (this.show_email!=1 && document.getElementById(this.COMMENT_GUEST_EMAIL_ID)) {
          document.getElementById(this.COMMENT_GUEST_EMAIL_ID).style.display='none';
        }

        // Скрыть кнопку написать отзыв
        jQuery("#" + this.COMMENT_REPLY_ID_PREFIX + "0").hide();
    }

    var butcancelElement = document.getElementById(this.COMMENT_CANCEL_BUTTON_ID);
    // hide cancel button
    if(this.show_addform==1 && this.parent_mess_id==0) butcancelElement.style.display='none';

    // for all submit button
    var butElement = document.getElementById(this.COMMENT_BUTTON_ID);
    butElement.onclick = this.sendData;

    if (edit!=-1 && (this.parent_mess_id!=0 || this.show_addform!=1)) {
      var textElement = document.getElementById(this.COMMENT_TEXTAREA_ID);
      // set focus on textarea
      textElement.focus();
    }
    if (edit==2) {
      // check twice need for GoogleChrome beta browser
      // if click edit and after thet click on any link in browse,
      // and after that click "back" into the browser panel...
      // click on edit link and other - very dangeorus >:-> - random unsubmitted text updating possible
      // in other browser all's OK
      if (document.getElementById("comment_edit").value==2) {
        butElement.disabled = true;
        // get comment text from base
        this.sendData();
      }
    }
  },


  /**
   * Delete form from DOM
   */
  dropElement: function (id, restore_form) {
    if (typeof(restore_form) == 'undefined') {
      restore_form = false;
    }
    var element = document.getElementById(id);
    // delete element if exists
    if (element) {
      element.parentNode.removeChild(element);
      // Показать кнопку написать отзыв
      jQuery("#" + this.COMMENT_REPLY_ID_PREFIX + "0").show();
    }
    if (restore_form && this.show_addform == 1) {
      this.Form(0);
    }

    return false;
  },

  like: function(id) {
    this.rating(id, +1);
  },

  dislike: function(id) {
    this.rating(id, -1);
  },

  rating: function(id, rating) {
    nc_Comments.obj = this;
    var rating_obj = document.getElementById(this.COMMENT_RATING_ID_PREFIX + id);

    nc_Comments.obj.Ajax();
    nc_Comments.obj.xhr.open('POST', nc_Comments.obj.MODULE_PATH + 'ajax.php?message_cc='+nc_Comments.obj.message_cc+'&message_id='+nc_Comments.obj.message_id+'&comment_id='+id+'&rating='+rating, true);
    nc_Comments.obj.xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=utf-8');
    nc_Comments.obj.xhr.onreadystatechange = function (data) {
      var ready = nc_Comments.obj.xhr.readyState;
      if ( ready != 4 ) return;
      var rating = nc_Comments.obj.xhr.responseText;

      if (rating) {
        rating_obj.innerHTML = rating;
      } else {
        confirm(nc_Comments.obj.RATING_NOTIFY);
      }
    };
    nc_Comments.obj.xhr.send();
  },

  /**
   * Post comment in Ajax
   */
  sendData: function () {

    // context is button, need to block
    this.disabled = true;

    // get form data
    var valuesArr = nc_Comments.obj.listChildren(nc_Comments.obj.COMMENT_FORM_ID);
    var textElement = document.getElementById(nc_Comments.obj.COMMENT_TEXTAREA_ID);
    var tosendArr = new Array();
    var textAreaEx = false;
    var filedId = "";
    for (var i = 0; i < valuesArr.length; i++) {
      fieldId = valuesArr[i].id ? valuesArr[i].id : valuesArr[i].name;
      if (fieldId && valuesArr[i].value) {
        tosendArr[i] = fieldId + '=' + encodeURIComponent(valuesArr[i].value);
        if (fieldId==nc_Comments.obj.COMMENT_TEXTAREA_ID) textAreaEx = true;
      }
    }
    // if textarea in <div> or other tag - they are not visible
    if (textAreaEx==false && textElement) {
      tosendArr[i++] = nc_Comments.obj.COMMENT_TEXTAREA_ID + '=' + encodeURIComponent( document.getElementById(nc_Comments.obj.COMMENT_TEXTAREA_ID).value );
    }

    if (jQuery('#'+nc_Comments.obj.COMMENT_GUEST_NAME_ID).length >= 1) tosendArr[i++] = nc_Comments.obj.COMMENT_GUEST_NAME_ID+'='+jQuery('#'+nc_Comments.obj.COMMENT_GUEST_NAME_ID+' input').val();

    if (jQuery('#'+nc_Comments.obj.COMMENT_GUEST_EMAIL_ID).length >= 1) tosendArr[i++] = nc_Comments.obj.COMMENT_GUEST_EMAIL_ID+'='+jQuery('#'+nc_Comments.obj.COMMENT_GUEST_EMAIL_ID+' input').val();

    // captcha
    if ( document.getElementsByName('nc_captcha_hash').length ) {
      tosendArr[i++] = 'nc_captcha_hash=' + document.getElementsByName('nc_captcha_hash')[0].value;
    }
    if ( document.getElementsByName('nc_captcha_code').length ) {
      tosendArr[i++] = 'nc_captcha_code=' + document.getElementsByName('nc_captcha_code')[0].value;
    }

    if (!tosendArr.length) return false;

    // need main context
    nc_Comments.obj.Ajax();
    nc_Comments.obj.xhr.open('POST', nc_Comments.obj.MODULE_PATH + nc_Comments.obj.COMMENT_ACTION_FILE, true);
    nc_Comments.obj.xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=utf-8');
    nc_Comments.obj.xhr.onreadystatechange = nc_Comments.obj.getStatus;
    nc_Comments.obj.xhr.send( tosendArr.join('&') );

    return false;
  },

  Subscribe: function ( comment_id ) {
    nc_Comments.obj = this;
    nc_Comments.obj.Ajax();
    nc_Comments.obj.xhr.open('POST', nc_Comments.obj.MODULE_PATH + nc_Comments.obj.COMMENT_SUBSCRIBE_FILE, true);
    nc_Comments.obj.xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=utf-8');
    nc_Comments.obj.xhr.onreadystatechange = nc_Comments.obj.getStatusSubscribe;
    nc_Comments.obj.xhr.send( "message_cc="+this.message_cc+"&message_id="+this.message_id );
  },

  Unsubscribe: function ( comment_id ) {
    nc_Comments.obj = this;
    nc_Comments.obj.Ajax();
    nc_Comments.obj.xhr.open('POST', nc_Comments.obj.MODULE_PATH + nc_Comments.obj.COMMENT_SUBSCRIBE_FILE, true);
    nc_Comments.obj.xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=utf-8');
    nc_Comments.obj.xhr.onreadystatechange = nc_Comments.obj.getStatusSubscribe;
    nc_Comments.obj.xhr.send( "unsubscribe=1&message_cc="+this.message_cc+"&message_id="+this.message_id );
  },

  listChildren: function (pid) {
    // get child nodes from parent element
    var allChildren = document.getElementById(pid).childNodes;
    // put children nodes in array
    if (allChildren) {
      var resArray = new Array( );
      for (var i = 0; i < allChildren.length; i++) {
        if (allChildren[i].nodeType == 1) {
          resArray[resArray.length] = allChildren[i];
        }
      }
    }

    return resArray;
  },

  /**
   * Create XHR function
   */
  Ajax: function () {

    this.xhr = null;

    // Standart method
    try {
      this.xhr = new XMLHttpRequest();
    }
    catch(e) {
      // Mozilla, IE7
      try {
        this.xhr = new ActiveXObject("Msxml2.XMLHTTP");
      }
      catch(e) {
        // Old IE
        try {
          this.xhr = new ActiveXObject("Microsoft.XMLHTTP");
        }
        catch(e) {
          return false;
        }
      }
    }

    return true;
  },

  in_array: function ( what, where ) {
    var a = false;
    for (var i=0; i < where.length; i++){
      if (what == where[i]){
        a = true;
        break;
      }
    }
    return a;
  },

  /**
   * XHR status check
   */
  getStatus: function () {

    var ready = nc_Comments.obj.xhr.readyState;
    var responseJson = "";
    var updData = Array();
    var status = 0;
    var parentBlock = 0;
    var parentBlockLink = 0;
    var butElement = document.getElementById(nc_Comments.obj.COMMENT_BUTTON_ID);

    // no initialized, open() not executed
    if (ready == 0) { return 0; }
    // in progress, open() executed
    if (ready == 1) { return 1; }
    // in progress, send() executed
    if (ready == 2) { return 2; }
    // interacive, part of data geted from server
    if (ready == 3) { return 3; }
    // operation completed
    if (ready == 4) {
      status = nc_Comments.obj.xhr.status;
      if (status >= 200 && status < 300) {
        // response text from PHP file
        responseJson = nc_Comments.obj.xhr.responseText;
        // return if no result
        //alert(responseJson);
        if (!responseJson) return;
        // JSON result
        responseJson = jQuery.trim(responseJson);
        updData = eval('(' + responseJson.replace(/\n/g, '%NL2BR').replace(/\r/g, '') + ')');
        var dropForm = true;

        //show premoderation text
        if (nc_Comments.obj.premoderation == 0 && !updData.error) {
          var premoderation=document.createElement('div');
          premoderation.id='premoderation';
          if(document.getElementById("premoderation")) document.getElementById("premoderation").parentNode.removeChild(document.getElementById("premoderation"));
          premoderation.innerHTML = unescape(nc_Comments.obj.premodtext).replace(/%NL2BR/g, "\n");
          document.getElementById(nc_Comments.obj.COMMENT_FORM_ID).parentNode.appendChild(premoderation);
          document.getElementById("premoderation").focus();
          if (nc_Comments.obj.show_addform == 1) {
            document.getElementById(nc_Comments.obj.COMMENT_FORM_ID).appendChild(nc_Comments.obj.Form(0));
          }
          else nc_Comments.obj.dropElement(nc_Comments.obj.COMMENT_FORM_ID, true);
        }

        var error = document.createElement('div');
        error.id = 'error';
        if ( updData.error ) {
          if ( updData.captchawrong ) {
            if(document.getElementById("error")) document.getElementById("error").parentNode.removeChild(document.getElementById("error"));
            error.innerHTML = unescape(updData.captchawrong).replace(/%NL2BR/g, "\n");
            document.getElementById(nc_Comments.obj.COMMENT_FORM_ID).insertBefore(error, document.getElementById(nc_Comments.obj.COMMENT_FORM_ID).firstChild);
            document.getElementsByName('nc_captcha_hash')[0].value = updData.hash;
            var img = document.getElementsByName('nc_captcha_img')[0];
            img.setAttribute('src', nc_Comments.obj.MODULE_PATH + '../captcha/img.php?code=' + updData.hash);
            document.getElementById(nc_Comments.obj.COMMENT_BUTTON_ID).disabled = false;
          }

          else if ( updData.unset ) {
            if(document.getElementById("error")) document.getElementById("error").parentNode.removeChild(document.getElementById("error"));
            error.innerHTML = unescape(updData.unset).replace(/%NL2BR/g, "\n");
            document.getElementById(nc_Comments.obj.COMMENT_BUTTON_ID).disabled = false;
            document.getElementById(nc_Comments.obj.COMMENT_FORM_ID).insertBefore(error, document.getElementById(nc_Comments.obj.COMMENT_FORM_ID).firstChild);
          }


          else {
            if(document.getElementById("error")) document.getElementById("error").parentNode.removeChild(document.getElementById("error"));
            error.innerHTML=updData.error;
            //alert(updData.error);
            document.getElementById(nc_Comments.obj.COMMENT_FORM_ID).insertBefore(error, document.getElementById(nc_Comments.obj.COMMENT_FORM_ID).firstChild);
            nc_Comments.obj.dropElement(nc_Comments.obj.COMMENT_FORM_ID, true);
          }

          return;
        }
        for (i = 0; i < updData.length; i++) {
          // update all comments ids and drop deleted blocks
          if (updData[i].all_comments_id && nc_Comments.obj.all_comments_id) {
            for (j = 0; j < nc_Comments.obj.all_comments_id.length; j++) {
              if ( !nc_Comments.obj.in_array(nc_Comments.obj.all_comments_id[j], updData[i].all_comments_id) ) {
                nc_Comments.obj.dropElement(nc_Comments.obj.COMMENT_ID_PREFIX + nc_Comments.obj.all_comments_id[j]);
              }
            }
            nc_Comments.obj.all_comments_id = updData[i].all_comments_id;
            continue;
          }
          // set internal update time
          if (nc_Comments.obj.last_updated < updData[i].updated) nc_Comments.obj.last_updated = updData[i].updated;
          // if comment updated only
          if (updData[i].update==1) {
            // exist comment block, for edition
            commentBlock = document.getElementById(nc_Comments.obj.COMMENT_TEXT_PREFIX + updData[i].id);
            commentBlock.innerHTML = unescape(updData[i].commentHTML).replace(/%NL2BR/g, "\n");
            continue;
          }
          // if get comment text from base insert them into the textarea
          if (updData[i].update==2) {
            if ( document.getElementById(nc_Comments.obj.COMMENT_TEXTAREA_ID) ) {
              commentArea = document.getElementById(nc_Comments.obj.COMMENT_TEXTAREA_ID);
              commentArea.value = unescape(updData[i].commentHTML).replace(/%NL2BR/g, "\n");
              document.getElementById("comment_edit").value = 1;
              document.getElementById(nc_Comments.obj.COMMENT_BUTTON_ID).disabled = false;
              dropForm = false;
            }
            continue;
          }
          // if comment deleted
          if (updData[i].update==-1) {
            // exist comment block, for edition
            nc_Comments.obj.dropElement(nc_Comments.obj.COMMENT_ID_PREFIX + updData[i].id);
            continue;
          }

          // find parent block
          parentBlock = document.getElementById(nc_Comments.obj.COMMENT_ID_PREFIX + updData[i].parent_id);

          // add HTML in document
          if (parentBlock && nc_Comments.obj.show_addform!=1) {
            parentBlockLink = document.getElementById(nc_Comments.obj.COMMENT_REPLY_ID_PREFIX + updData[i].parent_id);
            if ( Math.floor(updData[i].parent_id)!=0 ) {
              parentBlock.innerHTML += unescape(updData[i].commentHTML).replace(/%NL2BR/g, "\n");
              // drop parent links "edit" and "delete" if need it
              if (updData[i].edit_rule=='unreplied') nc_Comments.obj.dropElement(nc_Comments.obj.COMMENT_EDIT_ID_PREFIX + updData[i].parent_id);
              if (updData[i].delete_rule=='unreplied') nc_Comments.obj.dropElement(nc_Comments.obj.COMMENT_DELETE_ID_PREFIX + updData[i].parent_id);
            }
            else {
              // clone comment link
              // if this code past after next line, in IE its doesn't work!
              NEWparentBlockLink = parentBlockLink.cloneNode(true);
              // append comment HTML text
              if (nc_Comments.obj.sorting == 1) {
                parentBlock.innerHTML = unescape(updData[i].commentHTML).replace(/%NL2BR/g, "\n") + parentBlock.innerHTML;
              } else {
                parentBlock.innerHTML += unescape(updData[i].commentHTML).replace(/%NL2BR/g, "\n");
              }
              // drop cloned comment link
              nc_Comments.obj.dropElement(nc_Comments.obj.COMMENT_REPLY_ID_PREFIX + updData[i].parent_id);
              // insert cloned link :)
              parentBlock.appendChild(NEWparentBlockLink);
            }
          }
          else {
            // append comment HTML text
            if (nc_Comments.obj.sorting == 1) {
              parentBlock.innerHTML = unescape(updData[i].commentHTML).replace(/%NL2BR/g, "\n") + parentBlock.innerHTML;
            } else {
              parentBlock.innerHTML += unescape(updData[i].commentHTML).replace(/%NL2BR/g, "\n");
            }
            parentBlock.appendChild(nc_Comments.obj.Form(0));
          }
        }

        // go to the new comment position
        //window.location.href = '#' + nc_Comments.obj.COMMENT_ID_PREFIX + updData.id;

        if (dropForm) {
          // do not drop open form
          nc_Comments.obj.dropElement(nc_Comments.obj.COMMENT_FORM_ID, true);
        }
        else {
          // set last updated hidden field in form to actual value
          if ( document.getElementById(nc_Comments.obj.COMMENT_FORM_ID) ) {
            document.getElementById("last_updated").value = nc_Comments.obj.last_updated;
          }
        }

        return 4;
      }
      else { return -1; }
    }
  },

  getStatusSubscribe: function () {
    var ready = nc_Comments.obj.xhr.readyState;
    if ( ready != 4 ) return;

    var responseJson = nc_Comments.obj.xhr.responseText;
    // return if no result
    if (!responseJson) return;
    // JSON result
    var updData = eval('(' + responseJson.replace(/\n/g, "%NL2BR").replace(/\r/g, "") + ')');

    if ( updData.error ) {
      alert ( updData.error );
      return 0;
    }

    if ( updData.subscribe == 1 ) {
      var link = document.getElementById("nc_comments_subscribe" + nc_Comments.obj.message_cc +"_"+nc_Comments.obj.message_id + "_0");
      link.innerHTML = nc_Comments.obj.UNSUBSCRIBE_FROM_ALL;
      link.onclick = function() { nc_Comments.obj.Unsubscribe(0); return false; };
    }

    if ( updData.unsubscribe == 1 ) {
      var link = document.getElementById("nc_comments_subscribe" + nc_Comments.obj.message_cc +"_"+nc_Comments.obj.message_id + "_0");
      link.innerHTML = nc_Comments.obj.SUBSCRIBE_TO_ALL;
      link.onclick = function() { nc_Comments.obj.Subscribe(0); return false; };
    }

  },

  showAll: function (template_id) {
    nc_Comments.obj = this;
    var maindiv = nc_Comments.obj.COMMENT_ID_PREFIX + 0;
    // need main context
    nc_Comments.obj.Ajax();
    if (typeof(template_id) == 'undefined') {
      template_id = 0;
    }
    nc_Comments.obj.xhr.open('POST', nc_Comments.obj.MODULE_PATH + 'ajax.php?message_cc='+nc_Comments.obj.message_cc+'&message_id='+nc_Comments.obj.message_id+'&show_all='+1+'&template_id='+template_id, true);
    nc_Comments.obj.xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=utf-8');
    nc_Comments.obj.xhr.onreadystatechange = function (data) {
      var ready = nc_Comments.obj.xhr.readyState;
      var showAll = document.getElementById('show_all');
      if ( ready != 4 ) return;
      document.getElementById(maindiv).innerHTML = nc_Comments.obj.xhr.responseText;
      if ( showAll != 'undefined' ) showAll.style.display = 'none';
    };
    nc_Comments.obj.xhr.send();
    return 0;
  },

  navComments: function (curPos) {
    nc_Comments.obj = this;
    var maindiv = nc_Comments.obj.COMMENT_ID_PREFIX + 0;
    // need main context
    nc_Comments.obj.Ajax();
    nc_Comments.obj.xhr.open('POST', nc_Comments.obj.MODULE_PATH + 'ajax.php?message_cc='+nc_Comments.obj.message_cc+'&message_id='+nc_Comments.obj.message_id+'&curPos='+curPos+'&template_id='+nc_Comments.obj.template_id, true);
    nc_Comments.obj.xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=utf-8');
    nc_Comments.obj.xhr.onreadystatechange = function (data) {
      var ready = nc_Comments.obj.xhr.readyState;
      if ( ready != 4 ) return;
      var responseJson = nc_Comments.obj.xhr.responseText;
      // return if no result
      if (!responseJson) return;
      // JSON result
      //alert(responseJson);
      var updData = eval('(' + responseJson.replace(/\n/g, "%NL2BR").replace(/\r/g, "") + ')');
      document.getElementById(maindiv).innerHTML = unescape(updData.main_conteiner).replace(/%NL2BR/g, "\n");
      nc_Comments.obj.Form(0);
    };
    nc_Comments.obj.xhr.send();
    return 0;
  }
}