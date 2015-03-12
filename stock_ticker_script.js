//NOTE: only used if modernizer fails
function slider_scroll(target, speed, current_entry, end_entry){
        var entry = target.find('.stock_ticker_entry');
        //entry width is used to move the entries as the slider slides beneath them
        var entry_width = entry.first().width(); //NOTE: all entries are the same width, so just pick 1

        if (! entry.eq(current_entry).length) { //jquery object -> get all entries -> get indexed entry, jquery always retuns a list
                current_entry = 0; //if current entry goes out of bounds
        }

        var time = entry_width / (speed / 1000);
        //animate does a specific set of css transformations over a set duration smoothly
        target.animate({left : "-=" + entry_width}, time, 'linear',function(){ // move slider div  to the left
                var new_offset = entry.eq(end_entry).position().left + entry_width;  //after animation complete, move the first child to be last child by left offset
                entry.eq(current_entry).css({left :new_offset});
                slider_scroll(target, speed, current_entry + 1, current_entry); //and issue a new animate command
                //NOTE: the entries are cycling around, so first its 0 1 2 3, next its 1 2 3 0 then its 2 3 0 1 etc
        });
}

//Since tickers can be called multiple times per page, no sense checking the if conditions each time the function is invoked
if ((Modernizr.cssanimations) && (Modernizr.csstransforms)) { // If animation & transforms are supported
    var stock_ticker_start_js = function(config) {
        config['ticker_root'].find('.ticker-main').addClass("css3-ticker-scroll"); // Then apply the class which starts the CSS3 animation
        config['ticker_root'].find('.ticker-second').addClass("css3-ticker-scroll");
        jQuery(config['ticker_root']).fadeTo(1000, config['final_opacity']);
    };
} else { // If animation & transforms are not supported
    var stock_ticker_start_js = function(config) {
        config['ticker_root'].find('.ticker-second').remove(); // remove the second ticker copy
        var scroller = config['ticker_root'];
        var slider   = scroller.find('.stock_ticker_slider');
        var entry    = scroller.find('.stock_ticker_entry'); //saves a list of these elms
        var last_entry = entry.length - 1;

        scroller.fadeTo(1000, config['final_opacity']);
        slider_scroll(slider, config["scroll_speed"], 0, last_entry);
    };
}
