<?php

/*
    Plugin Name: Custom Stock Ticker
    Plugin URI: http://relevad.com/wp-plugins/
    Description: Create customizable moving stock tickers that can be placed anywhere on a site using shortcodes.
    Author: Relevad
    Version: 1.1.1
    Author URI: http://relevad.com/

*/

/*  Copyright 2014 Relevad Corporation (email: stock-ticker@relevad.com) 
 
    This program is free software; you can redistribute it and/or modify 
    it under the terms of the GNU General Public License as published by 
    the Free Software Foundation; either version 3 of the License, or 
    (at your option) any later version. 
 
    This program is distributed in the hope that it will be useful, 
    but WITHOUT ANY WARRANTY; without even the implied warranty of 
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the 
    GNU General Public License for more details. 
 
    You should have received a copy of the GNU General Public License 
    along with this program; if not, write to the Free Software 
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA 
*/

if (!defined('STOCK_PLUGIN_UTILS') ) {
    include WP_CONTENT_DIR . '/plugins/custom-stock-ticker/stock_plugin_utils.php'; //contains validation functions
}
if (!defined('STOCK_PLUGIN_CACHE') ) {
    include WP_CONTENT_DIR . '/plugins/custom-stock-ticker/stock_plugin_cache.php';
}
    include WP_CONTENT_DIR . '/plugins/custom-stock-ticker/stock_ticker_display.php';

$stock_ticker_vp = array( //validation_parameters
'max_display'  => array(1,20),
'scroll_speed' => array(1,150),
'width'        => array(200,2000),
'height'       => array(10,100),
'font_size'    => array(5,32)
);

function stock_ticker_activate() {

    /********************** Defaults for the plugin  ****************************/
    //NOTE: add_option only adds if option does not already exist
    add_option('stock_ticker_per_category_stock_lists', array('default' => 'GOOG,YHOO,AAPL')); //Important no spaces
    //add_option('stock_ticker_default_market',           "DOW"); //unused but maybe in future

    //data display option: Market, Symbol, Last value, change value, change percentage, last trade
    add_option('stock_ticker_data_display',             array(0,1,1,1,1,0)); //NOTE: Hardcoded flags for which stock elements to display in a stock entry
    //add_option('stock_ticker_display_option_strings',   array("Market", "Symbol", "Last value", "Change value", "Change percentage", "Last trade")); //In Future may allow user config

    add_option('stock_ticker_color_scheme',             array("#5DFC0A", "black")); //(Text, Background)
    add_option('stock_ticker_opacity',                  array(1, 1));               //(Text_opacity, Background_opacity)
    add_option('stock_ticker_display_size',             array(400, 20));            //(width, height)
    add_option('stock_ticker_font_options',             array(12, "Arial"));        //(size, family)
    //add_option('stock_ticker_default_fonts',            array("Arial", "cursive", "Gadget", "Georgia", "Impact", "Palatino", "sans-serif", "Times")); //Example fonts to use in dropdown
    add_option('stock_ticker_scroll_speed',             60);
    add_option('stock_ticker_display_number',           2);              //default number of stocks to show within the ticker window at the same time (determines scaling)
    add_option('stock_ticker_advanced_style',           "margin:auto;"); //Advanced styling options (applies only to the root element of this stock ticker
    add_option('stock_ticker_draw_vertical_lines',      true);
    add_option('stock_ticker_draw_triangle',            true);
    add_option('stock_ticker_enable_change_color',      true);    //changes the color of the stock/triangle red/green for -/+
    add_option('stock_ticker_all_change_color',         false);   //Flag to allow the color change (above) to re-color all elements in a stock entry

    add_option('stock_ticker_default_settings', array( //this is specifically for preset theme template configs
        'Classic'=>array(
            'name'              =>'Classic (black/white)', 
            'font_family'       =>'Arial', 
            'font_color'        =>'#FFFFFF',
            'back_color'        =>'#000000',
            'text_opacity'      =>1,
            'background_opacity'=>1, 
            'verti_lines'       =>true, 
            'draw_triangle'     =>true, 
            'change_color'      =>false,
            'all_change'        =>false
            ),
        'CNN'=>array(
            'name'              =>'CNN (black/green)', 
            'font_family'       =>'Arial', 
            'font_color'        =>'#99FF99', 
            'back_color'        =>'#000000',
            'text_opacity'      =>1,
            'background_opacity'=>1, 
            'verti_lines'       =>true, 
            'draw_triangle'     =>false, 
            'change_color'      =>false,
            'all_change'        =>false
            ),
        'Market'=>array(
            'name'              =>'Market (black/orange)', 
            'font_family'       =>'sans-serif', 
            'font_color'        =>'#FF6600', 
            'back_color'        =>'#000066',
            'text_opacity'      =>1,
            'background_opacity'=>1, 
            'verti_lines'       =>true, 
            'draw_triangle'     =>true, 
            'change_color'      =>false, 
            'all_change'        =>false
            ),
        'CNBC'=>array(
            'name'              =>'CNBC (white/green)', 
            'font_family'       =>'Arial', 
            'font_color'        =>'#33CC33', 
            'back_color'        =>'#FFFFFF',
            'text_opacity'      =>1,
            'background_opacity'=>1, 
            'verti_lines'       =>false, 
            'draw_triangle'     =>true, 
            'change_color'      =>true,
            'all_change'        =>true
            ),
        )
    ); //end add_option
}

register_activation_hook( __FILE__, 'stock_ticker_activate' );


//*********cleanup and conversion functions for updating versions *********
if (get_option('stock_ticker_category_stock_list')) { //this old option exists
    stock_ticker_convert_old_category_stock_list();
}
//*************************************************************************

function stock_ticker_admin_enqueue($hook) {
    if ($hook != 'settings_page_stock_ticker_admin') {return;} //do not run on other admin pages
    
    wp_register_style ('stock_ticker_admin_style', plugins_url('stock_ticker_admin_style.css', __FILE__));
    wp_register_script('stock_ticker_admin_script',plugins_url('stock_ticker_admin_script.js', __FILE__) , array( 'jquery' ), false, false);

    wp_enqueue_style ('stock_ticker_admin_style');
    wp_enqueue_script('stock_ticker_admin_script');
    
    stock_ticker_scripts_enqueue(true); //we also need these scripts
}
add_action('admin_enqueue_scripts', 'stock_ticker_admin_enqueue');



function stock_ticker_admin_actions(){
    $hook = add_options_page('StockTicker', 'StockTicker', 'manage_options', 'stock_ticker_admin', 'stock_ticker_admin_page'); //wrapper for add_submenu_page specifically into "settings"
    //add_submenu_page( 'options-general.php', $page_title, $menu_title, $capability, $menu_slug, $function ); // do not use __FILE__ for menu_slug
}
add_action('admin_menu', 'stock_ticker_admin_actions');


// This function is to reset all options inserted into wordpress DB by stock ticker to their default first run initialization values
function stock_ticker_reset_options() {

   update_option('stock_ticker_per_category_stock_lists', array('default' => 'GOOG,YHOO,AAPL')); //Important no spaces
   //update_option('stock_ticker_default_market',           "DOW");  //unused

   //data display option: Market, Symbol, Last value, change value, change percentage, last trade
   update_option('stock_ticker_data_display',           array(0,1,1,1,1,0));
   //update_option('stock_ticker_display_option_strings', array("Market", "Symbol", "Last value", "Change value", "Change percentage", "Last trade"));

   update_option('stock_ticker_color_scheme',        array("#5DFC0A", "black"));
   update_option('stock_ticker_opacity',             array(1, 1));
   update_option('stock_ticker_display_size',        array(400, 20));
   update_option('stock_ticker_font_options',        array(12, "Arial"));
   //update_option('stock_ticker_default_fonts',       array("Arial", "cursive", "Gadget", "Georgia", "Impact", "Palatino", "sans-serif", "serif", "Times"));
   update_option('stock_ticker_scroll_speed',        60);
   update_option('stock_ticker_display_number',      2);
   update_option('stock_ticker_advanced_style',      "margin:auto;");
   update_option('stock_ticker_draw_vertical_lines', true);
   update_option('stock_ticker_draw_triangle',       true);
   update_option('stock_ticker_enable_change_color', true);
   update_option('stock_ticker_all_change_color',    false);
}

/*
*This is what displays on the admin page. 
*
*/
function stock_ticker_admin_page() {

    echo <<<HEREDOC
<div id="ticker-options-page" style="max-width:850px;">
    <h1>Custom Stock Ticker</h1>
    <p>The stock ticker plugin allows you to create and run your own custom stock tickers.</p>
    <p>Choose your stocks and display settings below.<br />
    Then place your the shortcode <code>[stock-ticker]</code> inside a post, page, or <a href="https://wordpress.org/plugins/shortcode-widget/" ref="external nofollow" target="_blank">Shortcode Widget</a>.<br />
    Or, you can use <code>&lt;?php echo do_shortcode('[stock-ticker]'); ?&gt;</code> inside your theme files or <a href="https://wordpress.org/plugins/php-code-widget/" ref="external nofollow" target="_blank">PHP Code Widget</a>.</p>
HEREDOC;

    //Feature Improvement: see tracker #22775
    if (isset($_POST['save_changes'])) {
        stock_ticker_update_display_options();
    }
    elseif (isset($_POST['reset_options'])) {
        stock_ticker_reset_options();
    }
    stock_ticker_create_display_options();
    echo <<<HEREDOC
   <div class="postbox-container ticker-options" style="display:block; clear:both; width:818px;">
      <div id="normal-sortables" class="meta-box-sortables ui-sortable">
         <div id="referrers" class="postbox">
            <h3 class="hndle"><span>Preview</span></h3>
            <div class="inside">
               <p>The following ticker uses the default shortcode:<code>[stock-ticker]</code></p>
HEREDOC;
    echo do_shortcode('[stock-ticker]'); //Feature Improvement: see tracker #22775
    $example = "[stock-ticker id='example' display='2' width='700' height='40' bgcolor='#000' text_color='yellow' scroll_speed='75']";
    echo <<<HEREDOC
               <p>Note: To preview your settings, you must save changes.</p>
            </div>
         </div>
      </div>
   </div>
   <div class="postbox-container ticker-options" style="display:block; clear:both; width:818px;">
      <div id="normal-sortables" class="meta-box-sortables ui-sortable">
         <div id="referrers" class="postbox">
            <h3 class="hndle"><span>Advanced</span></h3>
            <div class="inside">
               <p>If you want to run a custom style or run additional tickers aside from the default [stock-ticker], 
               you can specify the style parameters in the shortcode. See the example below:</p>
               <textarea onclick="this.select();" readonly="readonly" class="shortcode-in-list-table wp-ui-text-highlight code" style="width: 100%; font-size: smaller;">{$example}</textarea>
               <br/><br/>
HEREDOC;
    echo do_shortcode($example);
    echo <<<HEREDOC
               <p>Note: In order to display tickers with different settings on the same page, one <b>must</b> assign a unique id in the shortcode for each ticker.</p>
            </div>
         </div>
      </div>
   </div>
</div><!-- end options page -->
HEREDOC;
}//End Stock_ticker_admin_page


//Creates the entire options page. Useful for formatting.
function stock_ticker_create_display_options(){
    echo "<form action='' method='POST'>
             <div class='postbox-container ticker-options' style='width: 50%; margin-right: 10px; clear:left;'>
                <div id='normal-sortables' class='meta-box-sortables ui-sortable'>
                    <div id='referrers' class='postbox'>
                        <h3 class='hndle'>Default Ticker Display Settings</h3>
                        <div class='inside'>";
                            stock_ticker_create_default_settings_field();
        echo "              <p>All options below are <b>optional</b>.<br/>All are reset by choosing a styles above.</p>
                            <div class='ticker-options-subsection'>
                                <h4>Ticker Config</h4><br/>";
                                stock_ticker_create_size_field();
                                stock_ticker_create_max_display_field();
        echo "                  <br/>";
                                stock_ticker_create_background_color_field();  //FOR FUTURE: add in a color swatch of some sort
        echo "              </div>
                            <div class='ticker-options-subsection'>
                                <h4>Text Config</h4><br/>";
                                stock_ticker_create_font_field();
        echo "              </div>
                            <div class='ticker-options-subsection'>
                                <h4>Stock Display Config</h4><br/>";
                                stock_ticker_create_draw_lines_field(); //TODO: rename these create functions to the section names and merge functions into the sections
        echo "              </div>
                            <div class='ticker-options-subsection'>
                                <h4>Advanced Styling</h4>
                                <div class='ticker_admin_toggle'>+</div>
                                <div class='ticker-options-display'>";
                                    stock_ticker_create_style_field();
        echo "                  </div>
                            </div>
                        </div>
                    </div>
                </div>
                <input type='submit' style='margin-bottom:20px;' name='save_changes' value='Save Changes' class='button-primary'/>
                <input type='submit' style='margin-bottom:20px;' name='reset_options' value='Reset to Defaults' class='button-primary'/>
             </div>
        
             <div class='postbox-container ticker-options' style='width: 45%; clear:right;'>
                 <div id='normal-sortables' class='meta-box-sortables ui-sortable'>
                     <div id='referrers' class='postbox'>
                         <h3 class='hndle'><span>Stocks</span></h3>
                         <div class='inside'>
                             <p>Type in your stocks as a comma-separated list.<br/> 
                             Example: <code>GOOG,YHOO,AAPL</code>.</p>
                             <p>
                                 When a page loads with a ticker, the stocks list of the category of that page is loaded. 
                                 If that category has no stocks associated with it, the default list is loaded.
                             </p>
                             <p>For Nasdaq, use <code>^IXIC</code>. For S&amp;P500, use <code>^GSPC</code>. Unfortunately, DOW is currently not available.</p>
                             <p>Here are some example stocks you can try:<br/>
                             BAC, CFG, AAPL, YHOO, SIRI, VALE, QQQ, GE, MDR, RAD, BABA, SUNE, FB, BBRY, MSFT, MU, PFE, F, GOOG</p>"; 
                             stock_ticker_create_per_category_stock_lists();
        echo "           </div>
                     </div>
                 </div>
             </div>
          </form>";
    return;
}

function stock_ticker_update_display_options(){
    stock_ticker_update_per_category_stock_lists();
    
    $apply_template = $_POST['default_settings'];
    if($apply_template != '-------') {
        stock_ticker_update_default_settings_field($apply_template); //this is actually apply template
    }
    else { //all of these settings are handled by the template, therefore don't bother updating them if 
   
        //NOTE: these won't exist in the post if they are unchecked
        update_option('stock_ticker_draw_vertical_lines', array_key_exists('create_vertical_dash', $_POST));
        update_option('stock_ticker_draw_triangle',       array_key_exists('create_triangle',      $_POST));
        update_option('stock_ticker_enable_change_color', array_key_exists('enable_change_color',  $_POST));
        
        global $stock_ticker_vp;
        //these will return either the cleaned up value, or a minimum, or maximum value, or the default (arg2)
        //If returns false, it will NOT update them, and the display creation function will continue to use the most recently saved value
        //IN FUTURE: this will be replaced with AJAX and javascript validation
        
        //NOTE: stock_ticker_validate_integer($new_val, $min_val, $max_val, $default)
        $tmp = stock_plugin_validate_integer($_POST['max_display'],  $stock_ticker_vp['max_display'][0],  $stock_ticker_vp['max_display'][1],  false);
        if ($tmp) { update_option('stock_ticker_display_number', $tmp); }
        
        $tmp = stock_plugin_validate_integer($_POST['scroll_speed'], $stock_ticker_vp['scroll_speed'][0], $stock_ticker_vp['scroll_speed'][1], false);
        if ($tmp) { update_option('stock_ticker_scroll_speed', $tmp); }

        $current_display = get_option('stock_ticker_display_size');
        $tmp1 = stock_plugin_validate_integer($_POST['width'],       $stock_ticker_vp['width'][0],        $stock_ticker_vp['width'][1],   $current_display[0]);
        $tmp2 = stock_plugin_validate_integer($_POST['height'],      $stock_ticker_vp['height'][0],       $stock_ticker_vp['height'][1],  $current_display[1]);
        update_option('stock_ticker_display_size', array($tmp1, $tmp2));
        
        // VALIDATE fonts
        $current_font_opts = get_option('stock_ticker_font_options');
        $tmp1 = stock_plugin_validate_integer($_POST['font_size'],   $stock_ticker_vp['font_size'][0], $stock_ticker_vp['font_size'][1],  $current_font_opts[0]);
        $tmp2 = stock_plugin_validate_font_family($_POST['font_family'], $current_font_opts[1]);
        update_option('stock_ticker_font_options', array($tmp1, $tmp2));
       
        // VALIDATE COLORS
        $current_colors = get_option('stock_ticker_color_scheme');
        $tmp1 = stock_plugin_validate_color($_POST['text_color'],        $current_colors[0]);
        $tmp2 = stock_plugin_validate_color($_POST['background_color1'], $current_colors[1]);
        update_option('stock_ticker_color_scheme', array($tmp1, $tmp2));
        
        
        $current_opacity = get_option('stock_ticker_opacity');
        $tmp1 = stock_plugin_validate_opacity($_POST['text_opacity'],       $current_opacity[0]);
        $tmp2 = stock_plugin_validate_opacity($_POST['background_opacity'], $current_opacity[1]);
        update_option('stock_ticker_opacity', array($tmp1, $tmp2));
        
        update_option('stock_ticker_advanced_style', $_POST['ticker_advanced_style']); //no validation needed
    }
}

function stock_ticker_create_category_stock_list($id, $stocks_string) { //this is a helper function for stock_ticker_create_per_category_stock_lists()
    $name = ($id == 'default') ? 'Default' : get_cat_name($id);
    echo <<<LABEL
        <label for="input_{$id}_stocks">{$name}</label><br/>
        <input id="input_{$id}_stocks" name="stocks_for_{$id}" type="text" value="{$stocks_string}" style="width:100%; font-size:14px" />
        
LABEL;
}

//Generates the html input lines for the list of stocks in each category
function stock_ticker_create_per_category_stock_lists() {
    
    $per_category_stock_lists = get_option('stock_ticker_per_category_stock_lists'); 
    //this is a sparce array indexed by category ID, the values will be a string of stocks
    // Array('default'=>'blah,blah,blah', '132'=>'foo,bar') etc
    
    stock_ticker_create_category_stock_list('default', $per_category_stock_lists['default']);
    echo "<br/><span style='font-weight:bold;'>WARNING:</span><br/>If Default is blank, Stock Tickers on pages without categories will be disabled.<br/>";
    
    $category_terms = get_terms('category');
    if (count($category_terms)) { //NOTE: this may display without any categories below IF and only if there is only the uncategorized category
        echo "<h4 style='display:inline-block;'>Customize Categories</h4>
              <div id='ticker_category_toggle' class='ticker_admin_toggle'>+</div>
                   <div class='ticker-options-display'>";
        
        foreach ($category_terms as $term) {
            if ($term->slug == 'uncategorized') { continue; }
            $cat_id = $term->term_id; 
            $stocks_string = (array_key_exists($cat_id, $per_category_stock_lists) ? $per_category_stock_lists[$cat_id] : '');
            stock_ticker_create_category_stock_list($cat_id, $stocks_string);
        }
        echo "</div>";
    }
    else {
        echo "<p> Your site does not appear to have any categories to display.</p>";
    }
}

function get_post_vars_for_category_stock_lists() {
    $arr_result = array(); //to be returned
    foreach ($_POST as $key => $value) {    
        if(substr($key, 0, 11)  == 'stocks_for_') {
             $arr_result[substr($key, 11)] = $value; //use the portion of the key that isn't stocks_for_
        }
    }
    return $arr_result;
}

function stock_ticker_update_per_category_stock_lists() {

    //Start with what is already in the database, so that we don't erase what is there in the case where categories get removed then added back in later
    $per_category_stock_lists  = get_option('stock_ticker_per_category_stock_lists', array()); //defaults to empty array
    $all_stock_list            = array();
    $category_stock_input_list = get_post_vars_for_category_stock_lists();
    
    foreach ($category_stock_input_list as $key => $value) {
        if (empty($value)) {
            $per_category_stock_lists[$key]  = $value;  //final string value and nothing more needed
            $category_stock_input_list[$key] = array(); //for future
        }
        else {
            $stock_str = preg_replace('/\s+/', '', strtoupper($value)); //capitalize the stock values, and remove spaces
            $stock_arr = explode(',', $stock_str);
            $category_stock_input_list[$key] = $stock_arr; //replace the string with an array for future use
            $all_stock_list = array_merge($all_stock_list, $stock_arr);
        }
    }
    $cache_output = stock_plugin_get_data(array_unique($all_stock_list)); //from stock_plugin_cache.php
    $invalid_stocks = $cache_output['invalid_stocks']; //we only need the invalid_stocks for validation
    foreach ($category_stock_input_list as $key => $stock_list) {
        //remove any invalid_stocks from the stock_list, then convert back to string for storage
        $per_category_stock_lists[$key] = implode(',', array_diff($stock_list, $invalid_stocks)); //NOTE: we need to do this even if invalid stocks are empty
    }
    echo "<p style='font-size:14px;font-weight:bold;'>The following stocks were not found:" . implode(', ', $invalid_stocks) . "</p>";
    update_option('stock_ticker_per_category_stock_lists', $per_category_stock_lists); //shove the updated option back into database
}

function stock_ticker_create_max_display_field(){
    ?>
        <label for="input_max_display">Number of stocks displayed on the screen at one time: </label>
        <input  id="input_max_display"  name="max_display"   type="text" value="<?php echo get_option('stock_ticker_display_number'); ?>" style="width:29px; font-size:14px; text-align:center" />
        <label for="input_scroll_speed">Scroll speed (Pixels per second): </label>
        <input  id="input_scroll_speed" name="scroll_speed"  type="text" value="<?php echo get_option('stock_ticker_scroll_speed'); ?>"   style="width:36px; font-size:14px; text-align:center" />

    <?php
}


function stock_ticker_create_background_color_field(){
    //$color_sets=get_option('stock_ticker_default_color_scheme');
    $current_colors = get_option('stock_ticker_color_scheme');
    $opacity_set    = get_option('stock_ticker_opacity');
    ?>
    <label for="input_background_color">Background color: </label>
    <input  id="input_background_color" name="background_color1"     type="text" value="<?php echo $current_colors[1]; ?>" style="width:70px" />
    <br/>
    <label for="input_background_opacity">Background opacity (0-1): </label>
    <input  id="input_background_opacity" name="background_opacity"  type="text" value="<?php echo $opacity_set[1]; ?>"    style="width:29px; font-size:14px; text-align:center" />


<?php

}


function stock_ticker_create_size_field(){
    $size = get_option('stock_ticker_display_size');
    ?>
        <label for="input_stock_tickerwidth">Width: </label>
        <input  id="input_stock_tickerwidth"   name="width"   type="text" value="<?php echo $size[0]; ?>" style="width:60px; font-size:14px" />
        <label for="input_stock_ticker_height">Height: </label>
        <input  id="input_stock_ticker_height" name="height"  type="text" value="<?php echo $size[1]; ?>" style="width:60px; font-size:14px" />
    <?php

}

function stock_ticker_create_font_field(){
    $font_options  = get_option('stock_ticker_font_options');
    //$default_fonts = get_option('stock_ticker_default_fonts');
    //TODO: why not use a regular dropdown instead of this datalist? We don't have an authentication, nor enough fonts to make it worth while
    $default_fonts = array("Arial", "cursive", "Gadget", "Georgia", "Impact", "Palatino", "sans-serif", "serif", "Times");
    $current_colors= get_option('stock_ticker_color_scheme');
    $opacity_set   = get_option('stock_ticker_opacity');
    ?>
        <label for="input_text_color">Text color: </label>
        <input  id="input_text_color" name="text_color"     type="text"  value="<?php echo $current_colors[0]; ?>" style="width:70px" />
        
        <label for="input_font_size">Font size: </label>
        <input  id="input_font_size" name="font_size"       type="text"  value="<?php echo $font_options[0]; ?>"   style="width:29px;  font-size:14px; text-align:center;" />
        <br/>
        
        <label for="input_text_opacity">Text opacity (0-1): </label>
        <input  id="input_text_opacity" name="text_opacity"  type="text" value="<?php echo $opacity_set[0]; ?>"    style="width:29px; font-size:14px; text-align:center" />
        
        <label for="input_font_family">Font family: </label>
        <input  id="input_font_family" name="font_family" list="font_family" value="<?php echo $font_options[1]; ?>" autocomplete="on" style="width:70px" />
        <datalist id="font_family"><!-- used as an "autocomplete dropdown" within the input text field -->
        <?php
            foreach($default_fonts as $font) {
                echo "<option value='{$font}'></option>";
            }
        ?>
        </datalist>
    <?php
}

//generates all of the checkboxes in the admin page
function stock_ticker_create_draw_lines_field(){
    ?>
    <input  id="input_create_vertical_dash" name="create_vertical_dash" type="checkbox" <?php echo (get_option('stock_ticker_draw_vertical_lines') ? 'checked' : '')?>>
    <label for="input_create_vertical_dash">Draw vertical lines</label>   
    <br/>

    <input  id="input_create_triangle"      name="create_triangle"      type="checkbox" <?php echo (get_option('stock_ticker_draw_triangle') ? 'checked' : '');?>>
    <label for="input_create_triangle">Draw triangle</label>  
    <br/>

    <input  id="input_enable_change_color"  name="enable_change_color"  type="checkbox" <?php echo (get_option('stock_ticker_enable_change_color') ? 'checked' : '');?>>
    <label for="input_enable_change_color">Enable change color</label>    
<?php
}


function stock_ticker_create_default_settings_field(){

    $all_settings = get_option('stock_ticker_default_settings');
    ?>
        <label for="input_default_settings">Template: </label>
        <select id="input_default_settings" name="default_settings" style="width:180px;">
        <option selected> ------- </option>
        <?php 
            foreach($all_settings as $key=>$setting){
                echo "<option value='{$key}'>{$setting['name']}</option>";
            }
        ?>
        </select>
    <?php
}

function stock_ticker_update_default_settings_field($selected_template) { //this is actually apply template
    
    $all_settings      = get_option('stock_ticker_default_settings'); //get the preset templates
    $template_settings = $all_settings[$selected_template]; 

    //update font style
    $option_holder    = get_option('stock_ticker_font_options'); 
    $option_holder[1] = $template_settings['font_family'];       //keep current font size, but apply the font family (eg arial)
    update_option('stock_ticker_font_options', $option_holder);

    //updates opacity settings
    update_option('stock_ticker_opacity', array($template_settings['text_opacity'], $template_settings['background_opacity']));
    //update color scheme
    update_option('stock_ticker_color_scheme', array($template_settings['font_color'], $template_settings['back_color']));

    //update vertical lines, triangles, and change color
    update_option('stock_ticker_draw_vertical_lines', $template_settings['verti_lines']);
    update_option('stock_ticker_draw_triangle',       $template_settings['draw_triangle']);
    update_option('stock_ticker_enable_change_color', $template_settings['change_color']);
    update_option('stock_ticker_all_change_color',    $template_settings['all_change']);

}

function stock_ticker_create_style_field(){
    echo "<p>If you have additional CSS rules you want to apply to the entire ticker (such as alignment or borders) you can add them below.</p>
          <p> Example: <code>margin:auto; border:1px solid #000000;</code></p>";
    $advanced_styling = get_option('stock_ticker_advanced_style');
    echo "<input id='input_ticker_advanced_style' name='ticker_advanced_style' type='text' value='{$advanced_styling}' style='width:90%; font-size:14px' />";

}


/* //unused
function stock_ticker_create_display_type_field(){
    $all_types    = get_option('stock_ticker_all_display_types');
    $current_type = get_option('stock_ticker_display_type');
    ?>
        <label for="display_type">Type of display: </label>
        <select name="display_type" id="display_type">
            <option selected>
        <?php 
            echo $current_type;
            foreach($all_types as $type){
                if($type == $current_type){
                    continue;
                }
                echo "<option>".$type;
            }
        ?>
        </select>
    <?php
}


function stock_ticker_update_display_type_field(){
    update_option('stock_ticker_display_type', $_POST['display_type']);
}
*/

//Generates the html for the listbox of markets -- no longer used
/*function stock_ticker_create_market_list(){
    ?>
        Default Market:
            <select name="markets">
                <?php
                $default_mark=get_option('stock_ticker_default_market');
                echo '<option selected>'.$default_mark;
                $markets=get_option('stock_ticker_all_markets');
                if(!empty($markets)){
                    foreach($markets as $market){
                        if($default_mark!=$market){
                        echo "<option >".$market;
                        }
                    }
                }   
                ?>

            </select>

    <?php
}
function stock_ticker_update_market_list(){

        $market = $_POST['markets'];
        update_option('stock_ticker_default_market', $market);
}*/

function stock_ticker_convert_old_category_stock_list() {
    $new_category_stock_list = array();
    $old_category_stock_list = get_option('stock_ticker_category_stock_list');
    $category_terms          = get_terms('category');
    foreach ($old_category_stock_list as $old_category => $old_stock_list) {
        if ($old_category == 'Default') { $new_category = 'default'; }
        else {
            foreach ($category_terms as $term) {
                if (preg_replace('/\s+/', '', $term->name) == $old_category) {
                    $new_category = $term->term_id;
                    break; //break out of inner loop
                }
            }
        }
        //NOTE: if we didn't find a new_category, just throw it out and continue
        $new_stock_list = implode(',', $old_stock_list);
        $new_category_stock_list[$new_category] = $new_stock_list;
    }
    
    update_option('stock_ticker_per_category_stock_lists', $new_category_stock_list); //can't use add because add would have run on initialize
    delete_option('stock_ticker_category_stock_list');
}


?>
