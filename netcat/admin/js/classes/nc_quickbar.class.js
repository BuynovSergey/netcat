/* $Id: nc_quickbar.class.js 7742 2012-07-22 17:12:25Z alive $ */

nc_quickbar = function (options) {

  this.ADMIN_AUTHTIME = Math.floor(options.ADMIN_AUTHTIME) || 86400;
  this.ADMIN_TEMPLATE =  options.ADMIN_TEMPLATE || ADMIN_PATH + 'skins/default/';

  // main variables for QuickBar
  this.Block = document.getElementById('netcatQuickBar');
  this.Slider = document.getElementById('quickBarSlider');
  this.Logotype = document.getElementById('quickBarLogo');
  this.mainSection = document.getElementById('quickBarMainSection');
  this.addonSection = document.getElementById('quickBarAddonSection');

  // set drag-and-drop events to this block
  nc_drag.init(this.Slider, this.Block);

  // context in variable
  nc_quickbar.obj = this;
}

nc_quickbar.prototype = {

  Restore: function () {
    // restore saved position and visibility if setted
    this.Block.style.left = Math.round( nc_cookies.get('QUICK_BAR_POSX') ) + Math.round(document.body.scrollLeft) + 'px';
    this.Block.style.top = Math.round( nc_cookies.get('QUICK_BAR_POSY') ) + Math.round(document.body.scrollTop) + 'px';
    if ( Math.round( nc_cookies.get('QUICK_BAR_HIDDEN') )==1 ) this.Hide();

  },

/*   Close: function () {
    nc_cookies.set('QUICK_BAR_CLOSED', 1, nc_quickbar.obj.ADMIN_AUTHTIME);
    nc_cookies.set('QUICK_BAR_POSX', 0, 0);
    nc_cookies.set('QUICK_BAR_POSY', 0, 0);
    nc_cookies.set('QUICK_BAR_HIDDEN', 0, 0);
    nc_quickbar.obj.Block.style.display = 'none';
  }, */

  /**
  * Hide QuickBar
  */

  Hide: function () {
    nc_cookies.set('QUICK_BAR_HIDDEN', 1, nc_quickbar.obj.ADMIN_AUTHTIME);
    nc_quickbar.obj.mainSection.style.display = 'none';
    // for opera correct viewing
    nc_quickbar.obj.Block.style.width = nc_quickbar.obj.addonSection.clientWidth + 'px';
    // set new actions
    nc_quickbar.obj.Logotype.onclick = nc_quickbar.obj.Show;
  },

  /**
  * Show QuickBar
  */
  Show: function () {
    nc_cookies.set('QUICK_BAR_HIDDEN', 0, nc_quickbar.obj.ADMIN_AUTHTIME);
    nc_quickbar.obj.mainSection.style.display = 'block';
    // two lines for opera correct viewing
    nc_quickbar.obj.Block.style.width = 'auto';
    nc_quickbar.obj.Block.style.width = nc_quickbar.obj.mainSection.clientWidth + nc_quickbar.obj.addonSection.clientWidth + 'px';
    // set new actions
    nc_quickbar.obj.Logotype.onclick = nc_quickbar.obj.Hide;
  },

  /**
  * Save QuickBar parameters in cookies
  */
  SavePos: function () {
    nc_cookies.set('QUICK_BAR_POSX', nc_quickbar.obj.Block.style.left.replace(/px/,'') - document.body.scrollLeft, nc_quickbar.obj.ADMIN_AUTHTIME);
    nc_cookies.set('QUICK_BAR_POSY', nc_quickbar.obj.Block.style.top.replace(/px/,'') - document.body.scrollTop, nc_quickbar.obj.ADMIN_AUTHTIME);

  },

  /**
  * Show QuickBar rule value
  */
  RuleValue: function (element, event) {
    // quickbar
    var panel = document.getElementById("netcatQuickBar");
    // border width correction
    var correction = (navigator.appName=='Microsoft Internet Explorer' ? 2 : 0);
    //  cursor coordinates - (offset + width + margin)
    element.title = ( Math.round(event.clientX) - ( Math.round( panel.style.left.replace(/px/, "") ) + 39 + 14 + correction ) ) + "px";
  }

}