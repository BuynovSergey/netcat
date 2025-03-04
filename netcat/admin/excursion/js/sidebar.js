$(function(){

	function initScrollpane() {
	    $('.nc_ab_sidebarWindow .scrollpane').jScrollPane();
	}

	// Уменьшение высоты панели экскурсии на высоту панели с отсчётом срока действия демо-версии
    function fixHeight() {
        var sidebar = $('.nc_ab_sidebarWindow'),
            demoPanelHeight = $('.nc-demo-panel').outerHeight();

        sidebar.css('bottom', (parseInt(sidebar.css('bottom'), 10) + demoPanelHeight) + 'px');
    }

    fixHeight();
	initScrollpane();
	$(window).resize(initScrollpane);
	
});
