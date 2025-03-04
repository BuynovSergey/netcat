/* $Id: nc_cookies.class.js 2342 2008-10-29 17:34:32Z vadim $ */

var nc_cookies = {

  /**
   * Set cookie variable
   * @param string variable name
   * @param mixed variable value
   * @param int actuality days
   */
  set: function (name, value, expiresecs) {
    var exdate = new Date();
    exdate.setTime( exdate.getTime() + expiresecs * 1000 );
    document.cookie = name + '=' + escape(value) +
    ';path=/' +
    ( expiresecs==null ? '' : ';expires=' + exdate.toString() );
  },
  
  /**
   * Get cookie variable by variable name
   * @param string variable name
   * 
   * @return string variable value or empty string
   */
  get: function (name) {
    if (document.cookie.length > 0) {
    var c_start = document.cookie.indexOf(name + '=');
      if (c_start!=-1) { 
        c_start = c_start + name.length + 1; 
        var c_end = document.cookie.indexOf(';', c_start);
        if (c_end==-1) c_end = document.cookie.length;
        return unescape( document.cookie.substring(c_start, c_end) );
      } 
    }
    return '';
  }

};