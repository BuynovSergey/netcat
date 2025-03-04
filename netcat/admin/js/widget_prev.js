nc_widget_prev = function () {
  this.widgetclass_id = 0;

  this.xhr = null;
  this.ajax();
  instance = this;
}

nc_widget_prev.prototype = {

  ajax: function () {
    this.xhr = null;
    
    try { 
      this.xhr = new XMLHttpRequest();
    }
    catch(e) {
      try {
        this.xhr = new ActiveXObject("Msxml2.XMLHTTP");
      }
      catch(e) {
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

  change: function (keyword) {
    this.keyword = keyword;
    block = document.getElementById('prev_'+this.keyword);
    if (block.style.display == 'none') {
      this.xhr.open("POST", "index.php?phase=91&keyword="+this.keyword, true);
      this.xhr.onreadystatechange = instance.response;
      this.xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=utf-8");
      this.xhr.send(''); 
    } else {
      block.style.display = 'none';
    }
   return;
  },

  response : function () {
    load = document.getElementById('prev_load_'+instance.keyword);
    load.style.display='block';
    if (instance.xhr.readyState == 4) {
      block = document.getElementById('prev_'+instance.keyword);
      block.innerHTML = instance.xhr.responseText;
      load.style.display = 'none';
      block.style.display = 'block';
    }
    return;
  }

}
