var stock_ticker_start_js=function(config){
	var scroll_id='.stock_ticker_'.concat(config["id"]);
	var scroll_speed=config["scroll_speed"];
	var scroller=jQuery(scroll_id);
	var slider=scroller.find('.stock_ticker_slider');
	var entry=scroller.find('.stock_ticker_entry');
		//entry width used to move the entries as
		//the slider slides beneath them
	var entry_width=entry.width();
	var last_entry=(scroller.find(entry).length-1);

	var slider_scroll=function(target, speed, current_entry, end_entry){
		if(jQuery(target).find(entry).eq(current_entry).length){

		}else{
			current_entry=0;
		}

		var time=entry_width/(speed/1000);
		target.animate({left : "-="+entry_width}, time, 'linear',function(){
			var new_offset=jQuery(target).find(entry).eq(end_entry).position().left+entry_width;
			jQuery(target).find(entry).eq(current_entry).css({left :new_offset});
			var next_entry=current_entry+1;
			slider_scroll(target, speed, next_entry, current_entry);
		});
	}
	scroller.fadeOut(0);
	scroller.fadeIn(500, 'linear');
	slider_scroll(slider, scroll_speed, 0, last_entry);

}


