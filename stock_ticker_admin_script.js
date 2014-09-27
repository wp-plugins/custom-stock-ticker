jQuery(document).ready(function(){
	var admin_options_toggle=jQuery('.ticker_admin_toggle');
	var option_display=jQuery('.ticker-options-display');
	option_display.toggle();
	var ticker_toggle_option=function(target){
		if(target.text()=="+"){
			target.text('-');
		}else{
			target.text('+');
		}
		target.next('.ticker-options-display').toggle(200);
	}
	admin_options_toggle.click(function(){
		ticker_toggle_option(jQuery(this));
	});


});
//function(){
		//	
				
		//	
//		}
