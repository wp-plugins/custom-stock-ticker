function slider_scroll(slider, ticker_width, time){ // this is the jQuery animation function
    slider.css("left", "0px"); // reset offset of main ticker
    //.animate({property: value}, duration, callback)
    slider.animate({left: -ticker_width}, time, 'linear', // animate the slider to move to the left
        function(){slider_scroll(slider, ticker_width, time)} // then callback to this function to start again
    );
}

function set_wrapper_width(root) {
    ticker_width=parseInt(root.find('.ticker-main').css('width')); // Get the computed width of the entire ticker
    root.find('.ticker-wrapper').attr('style', "width:" + (ticker_width * 2 + 10) + "px"); // Set the width of the wrapper element to be twice the length of a ticker (so they both sit on one line)
    return ticker_width;
}

var stock_ticker_start = function(config) {
    var root = config['ticker_root']; //this will return the base element for the current ticker, e.g. [stock_ticker_4]
    var slider = root.find('.stock_ticker_slider'); //get both sliders within that ticker
    var entry  = root.find('.stock_ticker_entry'); //get one full list of stock entries
    var maxLoop = 0;
    while (parseInt(slider.css('width')) < parseInt(root.css('width')) && maxLoop < 40) {
    //is the slider too short? (most be equal to or greather than ticker width)
        slider = root.find('.stock_ticker_slider');
        slider.append(entry.clone()); //slider too short, stick a copy of all the stock entries onto both sliders, then we'll check again
        maxLoop++; // !IMPORTANT! this ensures that the while loop will not infinitely loop in the event something goes wrong somehow
    }
    var ticker_width = set_wrapper_width(root); // gets the width of 1 slider; also sets the width of the wrapper element
    var time = Math.ceil(ticker_width / config['scroll_speed']); // Define the animation time in seconds
    if ((Modernizr.cssanimations) && (Modernizr.csstransforms)) {
    // Modernizr is an array created elsewhere, we are checking if these values are true or false. true = supported
        slider.addClass("css3-ticker-scroll"); // add the class that has the css3 animation on it
        slider.css("animation-duration", (time) + "s"); // set the total animation time based on computed width
    } else { // if either animation or transforms are not supported, use jQuery animate
        slider_scroll(slider, ticker_width, time*1000); // this function begins jQuery animate. Time is in miliseconds, so MULTIPLY by 1000
    }
    root.fadeTo(1000, config['final_opacity']); // fade-in ticker
}
