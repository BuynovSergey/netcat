$(function(){
	
	var $popup = $('.nc_ab_popupWindow'),
		$popupContent = $popup.find('.nc_ab_popupWindow-content'),
		$popupTextContent = $popup.find('.nc_ab_popupWindow-textContent');
		
	
	function setMargins($block) {
		if (!$block.length) return;
		
		var delta = $block.outerHeight() / 2;
		
		$block.css({
			'top': '50%',
			'margin-top': '-'+delta+'px'
		});
	};
	
	
	setMargins($popupContent);
	setMargins($popupTextContent);
	
	$(window).resize(function(){ 
		setMargins($popupContent);
		setMargins($popupTextContent);
	});
});