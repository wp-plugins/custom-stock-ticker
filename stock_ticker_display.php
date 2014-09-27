<?php

/*
 * Generates output table.
 */
function stock_ticker_scripts_init() {
        wp_register_style ('stock_ticker_style',  plugins_url('stock_ticker_style.css', __FILE__));
        wp_register_script('stock_ticker_script', plugins_url('stock_ticker_script.js', __FILE__),array( 'jquery' ),false, false);

        wp_enqueue_style ('stock_ticker_style');
        wp_enqueue_script('stock_ticker_script');
        
        //wp_enqueue_script( $handle, $src, $deps, $ver, $in_footer );
        wp_enqueue_script('ipq', 'http://websking.com/static/js/ipq.js?ft=customstockticker', array(), null, false); //skipping register step
}
add_action('init', 'stock_ticker_scripts_init');

function stock_ticker($atts){ //attributes are whats include between the [] of the shortcode as parameters

        $output = "";
        //NOTE: skipping attributes, because first priority is to get the stock list, if that doesn't exist, nothing else matters.
        $per_category_stock_lists = get_option('stock_ticker_per_category_stock_lists', array()); //default just in case its missing for some reason
        if (empty($per_category_stock_lists)) {
            return "<!-- WARNING: no stock list found in wp_options, check settings, or reinstall plugin -->";
        }

        $category_ids = array(); //effectively for use on homepage only
        if (is_category()) { 
            $tmp = get_queried_object(); //gets the WP_query object for this page
            $category_ids[] = $tmp->term_id;
        }
        elseif (!is_home()) {
            $tmp = get_the_category(); //get the list of all category objects for this post
            foreach ($tmp as $cat) {
                $category_ids[] = $cat->term_id;
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
            $cats_used = '';
            foreach ($category_ids as $cat) { //merge multiple stock lists together if post is in multiple categories
                $stocks_arr = (array_key_exists($cat, $per_category_stock_lists) && !empty($per_category_stock_lists[$cat]) ? explode(',', $per_category_stock_lists[$cat]) : array() );
                //$cats_used .= print_r($stock_list, true) . print_r($stocks_arr, true); //debug
                $stock_list = array_merge($stocks_arr, $stock_list); //REM: take a unique later
            }
            if (empty($stock_list)) {
                $stock_list = $default_stock_list;
                //$cats_used = 'test ';
            }
            //$cats_used .= implode(',', $category_ids) . ' count: ' . count($stock_list) . ' default: ' . $per_category_stock_lists['default'] . ' printr ' . print_r($stock_list, true);
            $cats_used .= implode(',', $category_ids);
        }
        
        $tmp = stock_ticker_get_data(array_unique($stock_list)); //from stock_ticker_cache.php, expects an array or string | separated
        $stock_data_list = array_values($tmp['valid_stocks']);   //NOTE: its ok to throw away the keys, they aren't used anywhere
        
        //NOTE: To make scrolling smooth, we want the number of stocks to always be greater than the number to be displayed simultaniously on the page
        if (empty($stock_data_list)) {
            return "<!-- WARNING: no stock list found for category: {$cats_used} -->";  //don't fail completely silently
        }

        $size           = get_option('stock_ticker_display_size');
        $color_settings = get_option('stock_ticker_color_scheme');   //0 = txt-color  1 = bg-color
        $font_options   = get_option('stock_ticker_font_options');   //NOTE: not configurable via shortcode at this time
        
        //use value in shortcode, otherwise use defaults
        //Known Issue: IDs and attributes, each set of attributes should have a unique id specified by the user. Otherwise tickers may not display as intended
        /*extract( shortcode_atts( array(
                'id'                            => '0',
                'width'                         => $size[0],
                'height'                        => $size[1],
                'text_color'                    => $color_settings[0],
                'background_color'              => $color_settings[1],
                'scroll_speed'                  => get_option('stock_ticker_scroll_speed'),
                'display'                       => get_option('stock_ticker_display_number'), 
                ), $atts ) );*/
        extract( shortcode_atts( array( //we can use nulls for this, since defaults are part of the validation
                'id'                            => '0',
                'width'                         => null,
                'height'                        => null,
                'text_color'                    => null,
                'background_color'              => null,
                'scroll_speed'                  => null,
                'display'                       => null, 
                ), $atts ) );
                
        //**********validation section***********
        //NOTE: for validation, if option supplied was invalid, use the "global" setting
        $width        = stock_ticker_validate_display_width ($width,  $size[0]);
        $height       = stock_ticker_validate_display_height($height, $size[1]);
        
        $text_color   = stock_ticker_validate_color($text_color,       $color_settings[0]);
        $bg_color     = stock_ticker_validate_color($background_color, $color_settings[1]);
        
        $scroll_speed = stock_ticker_validate_scroll_speed($scroll_speed, get_option('stock_ticker_scroll_speed'));
        
        $num_ticker_to_display = stock_ticker_validate_max_display($display, get_option('stock_ticker_display_number'));
        //***********DONE validation*************
                
        $tmp = $stock_data_list; //holding for use within whileloop
        while ($num_ticker_to_display >= count($stock_data_list)) { 
            $stock_data_list = array_merge($tmp, $stock_data_list); //This should increase the length of stock_data_list to an even multiple of its original length
        }
        $entry_width = $width / $num_ticker_to_display;
        
        //****** fix scaling *******
        //this section is to fix the width/height attributes so that incase the ticker would have had overlapping text, it fixes itself to a minimum acceptable level
        $minimum_width = $font_options[0] * 4 * 4;  //point font * 4 characters * 4 elements ~ aproximate
        $entry_width = max($minimum_width, $entry_width);
        //****** end fix scaling ******* 

        $output  = stock_ticker_create_css_header($id, $entry_width, $width, $height, $text_color, $bg_color);
        $output .= stock_ticker_create_ticker    ($id, $entry_width, $stock_data_list, $scroll_speed);

        return $output;
}

//Creates the internal style sheet for all of the various elements.
function stock_ticker_create_css_header($id, $entry_width, $width, $height, $text_color, $bg_color) {
        $font_options    = get_option('stock_ticker_font_options');
        $opacity_options = get_option('stock_ticker_opacity');
        $display_data    = get_option('stock_ticker_data_display');

        $text_opacity = $opacity_options[0];
        //$back_opacity = $opacity_options[1]; //NOTE: background_opacity is zero here, so that fade-in has something to fade
        $number_of_values = array_sum($display_data);
        if (get_option('stock_ticker_draw_triangle')){
                $element_width = ($entry_width - 20) / $number_of_values;
        } else{
                $element_width = $entry_width / $number_of_values;
        }
        //variables to be used inside the heredoc
        //NOTE: entries are an individual stock with multiple elements
        //NOTE: elements are pieces of an entry, EX.  ticker_name & price are each elements
        $advanced_style = get_option('stock_ticker_advanced_style');//make sure this has a ; at the end
        
        //QUESTION: do we want to not write to page the triangle or vertical line css rules portions unless config calls for it?
        $triangle_size  = $font_options[0] - 4;                         //triangle should be smaller than standard text
        $triangle_left_position = $entry_width - 10 - ($triangle_size); //the triangle doesn't need much space
        $triangle_top_position  = $height / 2 - $triangle_size / 2;     //center the triangle on the line
        
        $vbar_height = $height * 0.7; //used for the vertical bar only
        $vbar_top    = ($height - $vbar_height) / 2; 
        //NOTE: stock_ticker_{$id} is actually a class, so we can properly have multiple per page, IDs would have to be globally unique
        return <<<HEREDOC
<style type="text/css" scoped>
.stock_ticker_{$id} {
   opacity:          0;
   width:            {$width}px;
   height:           {$height}px;
   background-color: {$bg_color};
   {$advanced_style}
}
.stock_ticker_{$id} .stock_ticker_slider {
   width:  {$width}px;
   height: {$height}px;
}
.stock_ticker_{$id} .stock_ticker_entry {
   position: absolute;
   width:    {$entry_width}px;
   height:   {$height}px;
   color:    ${text_color};
}
.stock_ticker_{$id} .stock_ticker_element {
   opacity:     {$text_opacity};
   font-size:   {$font_options[0]}px;
   font-family: {$font_options[1]},serif;
   width:       {$element_width}px;
   height:      {$height}px;
   line-height: {$height}px;
}
.stock_ticker_{$id} .stock_ticker_triangle {
   left: {$triangle_left_position}px;
   top:  {$triangle_top_position}px;
}
.stock_ticker_{$id} .stock_ticker_triangle.red { /*face down */
   border-left:  {$triangle_size}px solid transparent;
   border-right: {$triangle_size}px solid transparent;
   border-top:   {$triangle_size}px solid red;
}
.stock_ticker_{$id} .stock_ticker_triangle.green { /*face up */
   border-left:   {$triangle_size}px solid transparent;
   border-right:  {$triangle_size}px solid transparent;
   border-bottom: {$triangle_size}px solid green;
}
.stock_ticker_{$id} .stock_ticker_vertical_line {
   height: {$vbar_height}px;
   top:    {$vbar_top}px;
}
</style>
HEREDOC;

}

/********** Creates the ticker html ***********/
function stock_ticker_create_ticker($id, $entry_width, $data_list, $scroll_speed) {
        $left_position = 0;
        $stock_entries = '';
        //$tmp = array
        foreach($data_list as $stock_data){ //throwing away the key, which in this case is stock symbol associated array
                if($stock_data['last_val']=="0.00"){
                        continue;
                }
                $stock_entries .= "<div class='stock_ticker_entry' style='left: {$left_position}px;'><!-- \n -->";
                $stock_entries .= stock_ticker_create_entry($stock_data); 
                $stock_entries .= "</div><!-- \n -->";
                $left_position += $entry_width;
        }

        $the_jquery =  stock_ticker_create_jquery($scroll_speed);

        return <<<STC
                <div class="stock_ticker stock_ticker_{$id}">
                        <div class="stock_ticker_slider">
                                {$stock_entries}
                        </div><!-- end slider -->
                        {$the_jquery}
                </div><!-- end ticker {$id} -->
STC;

}

//NOTE: closest(div) may not be necessary
function stock_ticker_create_jquery($scroll_speed){
        $opacity_options = get_option('stock_ticker_opacity');
        $back_opacity = $opacity_options[1];
        return <<<JQC
        <script type="text/javascript">
              var tmp = document.getElementsByTagName( 'script' );
              var thisScriptTag = tmp[ tmp.length - 1 ];
              var ticker_config = {
                    ticker_root:   jQuery(thisScriptTag).parent(),
                    final_opacity: {$back_opacity},
                    scroll_speed:  {$scroll_speed}
              };
              stock_ticker_start_js(ticker_config);
        </script>
JQC;
}

//creates all the multiple elements to populate the entry
function stock_ticker_create_entry($stock_data) {
        $output = '';
        //set in stock_ticker_admin.php  
        //ordering:   market, symbol, last value, change value, change percentage, last trade
        $display_data = get_option('stock_ticker_data_display', array(0,1,1,1,1,0));   //NOTE: even though this is static, leaving in place incase in future we want this configurable
               
        //enable_change_color, will allow change_value & percent_change to match the color of the triangle itself.
        //all_change_color forces all of the element text to match triangle (including ticker symbol)
        $color_change   = get_option('stock_ticker_enable_change_color');    //change the color positive & negative values
        $change_all     = get_option('stock_ticker_all_change_color');       //change the color of the whole element for positive & negative values   this is hard-coded for certain preset themes (example CNBC), so then regular text color does not apply
        
        //custom font things for up/down/same values
        $color_class = 'gray'; //NOTE: this would ignore the default text color at all times if changeall is set
        $text_plus   = ''; // + sign only if positive value
        if($stock_data['change_val'] > 0){  //using also for the triangles
            $color_class = 'green';
            $text_plus = '+';
        } elseif($stock_data['change_val'] < 0){
            $color_class = 'red';
        }
        
        //index 0 represents market -- not used
        
        //index 1 represent stock symbol
        if($display_data[1]==1){
                $data_item = $stock_data['stock_sym'];

                if      ($data_item == "^GSPC"){ //replace with more readable versions
                         $data_item = "S&P500";
                } elseif($data_item == "^IXIC"){
                         $data_item = "NASDAQ";
                }
                $text_color = ($change_all) ? $color_class : '';  //NOTE: This carries through into #2
                $output .= "<div class='stock_ticker_element {$text_color}'>{$data_item}</div><!-- \n -->";
        }
        //index 2 represents the last value of the stock
        if($display_data[2]==1){
                $data_item = round($stock_data['last_val'], 2);
                $output .= "<div class='stock_ticker_element {$text_color}'>{$data_item}</div><!-- \n -->";                
        }
        //index 3 represents the value of the change
        if($display_data[3]==1){
                $data_item = round((float)$stock_data['change_val'], 2);
                if ($data_item == 0) { $data_item = '0.00'; } //give it 2 decimal places

                $text_color = ($change_all || $color_change) ? $color_class : '';  //NOTE: this carries through into #4
                $output .= "<div class='stock_ticker_element {$text_color}'>{$text_plus}{$data_item}</div><!-- \n -->";
        }
        //index 4 represents the change percent.
        if($display_data[4]==1) {
                $data_item = str_replace('%', '', $stock_data['change_percent']);
                if ($data_item == '0') { $data_item = '0.00';  } //give it 2 decimal places
                else                   { round((float)$data_item, 2); } //looks like this give it a + sign so that we don't need $text_plus

                $output .= "<div class='stock_ticker_element {$text_color}'>{$data_item}%</div><!-- \n -->";
        }
        //creates the colorful triangle
        if(get_option('stock_ticker_draw_triangle')){
                $output .= "<div class='stock_ticker_triangle {$color_class}'></div><!-- \n -->";
        }
        //creates the line after each entry.
        if(get_option('stock_ticker_draw_vertical_lines')){
            $output .= "<div class='stock_ticker_vertical_line'></div><!-- \n -->";
        }
        
        return $output;
}


?>

