/**
 * IE 5.5+, Firefox, Opera, Chrome, Safari XHR object
 *
 * @param url string
 * @param callback object
 * @param data mixed
 * @param x null
 */
function ncDemoAjax(url, callback, data, x) {
  try {
    x = new (this.XMLHttpRequest || ActiveXObject)("MSXML2.XMLHTTP.3.0");
    x.open(data ? "POST" : "GET", url, 1);
    x.setRequestHeader("X-Requested-With", "XMLHttpRequest");
    x.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    x.onreadystatechange = function () {
      x.readyState > 3 && callback && callback(x.responseText, x);
    };
    x.send(data)
  } catch (e) {
    window.console && console.log(e);
  }
}

function ncDemoRemoveModal() {
  var ncDemoModal = document.querySelector(".nc-demo-modal");
  var ncDemoModalOverlay = document.querySelector(".nc-demo-modal-overlay");

  ncDemoModal.parentElement.removeChild(ncDemoModal);
  ncDemoModalOverlay.parentElement.removeChild(ncDemoModalOverlay);
  if (window.ADMIN_PATH) {
    ncDemoAjax(ncDemoModal.getAttribute('data-modal-target-url'));
  }
}

if (document.addEventListener) {
  document.addEventListener('DOMContentLoaded', ncDemoInit);
} else {
  window.onload = ncDemoInit;
}

function ncDemoInit() {
  var ncDemoModal = document.querySelector(".nc-demo-modal");
  var ncDemoModalCloseButton = document.querySelector(".nc-demo-modal-close");
  var ncDemoModalActivateLink = document.querySelector(".nc-demo-modal-activation .nc-demo-modal-link");

  if (/^#tools\.activation/.test(window.location.hash)) {
    ncDemoRemoveModal();
  }

  if (ncDemoModal) {
    ncDemoModal.style.marginLeft = (-parseInt(ncDemoModal.offsetWidth, 10) / 2) + "px";
    ncDemoModal.style.marginTop = (-parseInt(ncDemoModal.offsetHeight, 10) / 2) + "px";
    ncDemoModal.style.transform = 'none';
  }

  if (ncDemoModalCloseButton) {
    ncDemoModalCloseButton.onclick = ncDemoRemoveModal;
  }

  if (ncDemoModalActivateLink) {
    ncDemoModalActivateLink.onclick = ncDemoRemoveModal;
  }
}
