window.onload = function() {
	var windowWidth, windowHeight;
    if (window.innerHeight) { // all except Explorer
		windowWidth = window.innerWidth;
		windowHeight = window.innerHeight;
    } else if (document.body) { // other Explorers
		windowWidth = document.body.clientWidth;
		windowHeight = document.body.clientHeight;
    }
    var date = new Date();
    if ((windowWidth < 640) && (windowHeight < 480) ) {
	date.setTime(date.getTime() + (365 * 24 * 60 * 60 * 1000));
        document.cookie="mobile=true; path=/; expires=" + date.toGMTString();
    }
    else {
	date.setTime(date.getTime() -  1000);
        document.cookie="mobile=; path=/; expires=" + date.toGMTString();
    }
}