<?php

/*
    Plugin Name: Custom Stock Ticker
    Plugin URI: http://relevad.com/wp-plugins/
    Description: Create customizable moving stock tickers that can be placed anywhere on a site using shortcodes.
    Author: Relevad
    Version: 1.3.3
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

// Feature Improvement: think about putting each individual config into a class, does that buy us anything?

if (!defined('STOCK_PLUGIN_UTILS') ) {
    include WP_CONTENT_DIR . '/plugins/custom-stock-ticker/stock_plugin_utils.php'; //used to contain validation functions
    
    if (!defined('RELEVAD_PLUGIN_UTILS')) {
        include WP_CONTENT_DIR . '/plugins/custom-stock-ticker/relevad_plugin_utils.php';
    }
}
if (!defined('STOCK_PLUGIN_CACHE') ) {
    include WP_CONTENT_DIR . '/plugins/custom-stock-ticker/stock_plugin_cache.php';
}

    include WP_CONTENT_DIR . '/plugins/custom-stock-ticker/stock_ticker_display.php';

$st_current_version = '1.3.3';
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
    //add_option('stock_ticker_version', $st_current_version); //DO NOT add this here, it could break versioning

    $stock_ticker_default_settings = Array(
        'data_display'      => array(0,1,1,1,1,0),
        //'default_market'    => 'DOW',
        //'display_options_strings' => array("Market", "Symbol", "Last value", "Change value", "Change percentage", "Last trade"),
        'font_color'        => '#5DFC0A', 
        'bg_color'          => 'black',
        'text_opacity'      => 1,
        'bg_opacity'        => 1, 
        'width'             => 400,
        'height'            => 20,
        'font_size'         => 12,
        'font_family'       => 'Arial',
        'scroll_speed'      => 60,
        'display_number'    => 2,
        'advanced_style'    => 'margin:auto;',
        'draw_vertical_lines' => true,
        'draw_triangle'     => true,
        'change_color'      => 1 //0-none 1-some 2-all
        );
    add_option('stock_ticker_default_settings', $stock_ticker_default_settings); //one option to rule them all
}

register_activation_hook( __FILE__, 'stock_ticker_activate' );


//*********cleanup and conversion functions for updating versions *********
$st_db_version = get_option('stock_ticker_version', '0');

//NOTE: Don't forget to add each and every version number as a case
switch($st_db_version) {
    case '0': //if versioning did not exist yet, then use old method

        //version 1.0 -> 1.1
        if (get_option('stock_ticker_category_stock_list')) {
            stock_plugin_convert_old_category_stock_list('ticker'); 
        }
        //version 1.2 -> 1.3
        if (get_option('stock_ticker_color_scheme')) {
            stock_ticker_convert_old_options(); 
        }

    case '1.3.2':
        update_option('stock_ticker_version', $st_current_version); //this will always be right above st_current_version case
        update_option('stock_ticker_version_text', " updated from v{$st_db_version} to"); //keep these 2 updates paired
        //NOTE: takes care of add_option() as well
    case $st_current_version:
        break;
    //NOTE: if for any reason the database entry disapears again we might have a problem updating or performing table modifcations on tables already modified.
    default: //this shouldn't be needed
        //future version? downgrading?
        update_option('stock_ticker_version_text', " found v{$st_db_version} current version");
        break;
}
//*************************************************************************

function stock_ticker_admin_enqueue($hook) {
    global $st_current_version;
    //if ($hook != 'settings_page_stock_ticker_admin') {return;} //do not run on other admin pages
    if ($hook != 'relevad-plugins_page_stock_ticker_admin') {return;} //do not run on other admin pages
    
    wp_register_style ('stock_plugin_admin_style', plugins_url('stock_plugin_admin_style.css', __FILE__), false, $st_current_version);
    wp_register_script('stock_plugin_admin_script',plugins_url('stock_plugin_admin_script.js', __FILE__) , array( 'jquery' ), $st_current_version, false);

    wp_enqueue_style ('stock_plugin_admin_style');
    wp_enqueue_script('stock_plugin_admin_script');
    
    stock_ticker_scripts_enqueue(true); //we also need these scripts
}
add_action('admin_enqueue_scripts', 'stock_ticker_admin_enqueue');



function stock_ticker_admin_actions() {
    
    relevad_plugin_add_menu_section(); //imported from relevad_plugin_utils.php
    
    //$hook = add_options_page('StockTicker', 'StockTicker', 'manage_options', 'stock_ticker_admin', 'stock_ticker_admin_page'); //wrapper for add_submenu_page specifically into "settings"
    $hook = add_submenu_page('relevad_plugins', 'StockTicker', 'StockTicker', 'manage_options', 'stock_ticker_admin', 'stock_ticker_admin_page'); 
    //add_submenu_page( 'options-general.php', $page_title, $menu_title, $capability, $menu_slug, $function ); // do not use __FILE__ for menu_slug
}
add_action('admin_menu', 'stock_ticker_admin_actions');


// This function is to reset all options inserted into wordpress DB by stock ticker to their default first run initialization values
// debug only
function stock_ticker_reset_options() {

   update_option('stock_ticker_per_category_stock_lists', array('default' => 'GOOG,YHOO,AAPL')); //Important no spaces  
   
   $stock_ticker_default_settings = Array(
    'data_display'      => array(0,1,1,1,1,0),
    //'default_market'    => 'DOW',
    //'display_options_strings' => array("Market", "Symbol", "Last value", "Change value", "Change percentage", "Last trade"),
    'font_color'        => '#5DFC0A', 
    'bg_color'          => 'black',
    'text_opacity'      => 1,
    'bg_opacity'        => 1, 
    'width'             => 400,
    'height'            => 20,
    'font_size'         => 12,
    'font_family'       => 'Arial',
    'scroll_speed'      => 60,
    'display_number'    => 2,
    'advanced_style'    => 'margin:auto;',
    'draw_vertical_lines' => true,
    'draw_triangle'     => true,
    'change_color'      => 1 //0-none 1-some 2-all
    );
    update_option('stock_ticker_default_settings', $stock_ticker_default_settings); //one option to rule them all
}

//This is what displays on the admin page. 
function stock_ticker_admin_page() {
    global $st_current_version;
    $version_txt = get_option('stock_ticker_version_text', '') . " v{$st_current_version}";
    update_option('stock_ticker_version_text', ''); //clear the option after we display it once
    
    echo <<<HEREDOC
<div id="sp-options-page">
    <h1>Custom Stock Ticker</h1><sub>{$version_txt}</sub>
    <p>The stock ticker plugin allows you to create and run your own custom stock tickers.</p>
    <p>Choose your stocks and display settings below.<br />
    Then place your the shortcode <code>[stock-ticker]</code> inside a post, page, or <a href="https://wordpress.org/plugins/shortcode-widget/" ref="external nofollow" target="_blank">Shortcode Widget</a>.<br />
    Or, you can use <code>&lt;?php echo do_shortcode('[stock-ticker]'); ?&gt;</code> inside your theme files or <a href="https://wordpress.org/plugins/php-code-widget/" ref="external nofollow" target="_blank">PHP Code Widget</a>.</p>
HEREDOC;

    //Feature Improvement: see tracker #22775
    if (isset($_POST['save_changes'])) {
        stock_plugin_update_per_category_stock_lists('ticker');
        stock_ticker_update_display_options();
    }
    elseif (isset($_POST['reset_options'])) {
        stock_ticker_reset_options();
    }
    stock_ticker_create_display_options();
    echo <<<HEREDOC
   <div id="sp-preview" class="postbox-container sp-options">
      <div id="normal-sortables" class="meta-box-sortables ui-sortable">
         <div id="referrers" class="postbox">
            <h3 class="hndle"><span>Preview</span></h3>
            <div class="inside">
               <p>The following ticker uses the default shortcode:<code>[stock-ticker]</code></p>
HEREDOC;
    echo do_shortcode('[stock-ticker]'); //Feature Improvement: see tracker #22775
    echo <<<HEREDOC
               <p>Note: To preview your settings, you must save changes.</p>
            </div>
         </div>
      </div>
   </div>
   <div class="clear"></div>
</div><!-- end options page -->
HEREDOC;
}//End Stock_ticker_admin_page


//Creates the entire options page. Useful for formatting.
function stock_ticker_create_display_options() {
    $st_ds = get_option('stock_ticker_default_settings');
    echo "<form action='' method='POST'>
             <div id='sp-form-div' class='postbox-container sp-options'>
                <div id='normal-sortables' class='meta-box-sortables ui-sortable'>
                    <div id='referrers' class='postbox'>
                        <h3 class='hndle'>Default Ticker Display Settings</h3>
                        <div class='inside'>";
                            stock_ticker_create_template_field();
        echo "              <div class='sp-options-subsection'>
                                <h4>Ticker Config</h4>
                                <div class='section_toggle'>+</div>
                                <div class='section-options-display'>";
                                    stock_ticker_create_ticker_config($st_ds); //FOR FUTURE: add in a color swatch of some sort
        echo "                  </div>
                            </div>
                            <div class='sp-options-subsection'>
                                <h4>Text Config</h4>
                                <div class='section_toggle'>+</div>
                                <div class='section-options-display'>";
                                    stock_ticker_create_text_config($st_ds);
        echo "                  </div>
                            </div>
                            <div class='sp-options-subsection'>
                                <h4>Stock Display Config</h4>
                                <div class='section_toggle'>+</div>
                                <div class='section-options-display'>";
                                    stock_ticker_create_display_config($st_ds);
        echo "                  </div>
                            </div>
                            <div class='sp-options-subsection'>
                                <h4>Advanced Styling</h4>
                                <div class='section_toggle'>+</div>
                                <div class='section-options-display'>";
                                    stock_ticker_create_style_field($st_ds);
        echo "                  </div>
                            </div>
                        </div>
                    </div>
                </div>
                <input type='submit' name='save_changes'  value='Save'              class='button-primary' />
                <input type='submit' name='reset_options' value='Reset to Defaults' class='button-primary' /><sup>*</sup>
                <br />
                <sup>* NOTE: 'Reset to Defaults' also clears all stock lists.</sup>
             </div>
        
             <div id='sp-cat-stocks-div' class='postbox-container sp-options'>
                 <div id='normal-sortables' class='meta-box-sortables ui-sortable'>
                     <div id='referrers' class='postbox'>
                         <h3 class='hndle'><span>Stocks</span></h3>
                         <div class='inside'>
                             <p>Type in your stocks as a comma-separated list.<br /> 
                             Example: <code>GOOG,YHOO,AAPL</code>.</p>
                             <p>
                                 When a page loads with a ticker, the stocks list of the category of that page is loaded. 
                                 If that category has no stocks associated with it, the default list is loaded.
                             </p>
                             <p>For Nasdaq, use <code>^IXIC</code>. For S&amp;P500, use <code>^GSPC</code>. Unfortunately, DOW is currently not available.</p>
                             <p>Here are some example stocks you can try:<br/>
                             BAC, CFG, AAPL, YHOO, SIRI, VALE, QQQ, GE, MDR, RAD, BABA, SUNE, FB, BBRY, MSFT, MU, PFE, F, GOOG</p>"; 
                             stock_plugin_create_per_category_stock_lists('ticker');
        echo "           </div>
                     </div>
                 </div>
             </div>
          </form>";
    return;
}

function stock_ticker_templates() { //helper function to avoid global variables
    return array( //this is specifically for preset theme template configs
    
        'Default' => array(
            'name'                => 'Default (green on black)', 
            'font_family'         => 'Arial', 
            'font_color'          => '#5DFC0A',
            'bg_color'            => 'black',
            'text_opacity'        => 1,
            'bg_opacity'          => 1, 
            'draw_vertical_lines' => true, 
            'draw_triangle'       => true, 
            'change_color'        => 1
            ),
        'Classic' => array(
            'name'                => 'Classic (white on black)', 
            'font_family'         => 'Arial', 
            'font_color'          => '#FFFFFF',
            'bg_color'            => '#000000',
            'text_opacity'        => 1,
            'bg_opacity'          => 1, 
            'draw_vertical_lines' => true, 
            'draw_triangle'       => true, 
            'change_color'        => 0
            ),
        'CNN' => array(
            'name'                => 'CNN (light-green on black)', 
            'font_family'         => 'Arial', 
            'font_color'          => '#99FF99', 
            'bg_color'            => '#000000',
            'text_opacity'        => 1,
            'bg_opacity'          => 1, 
            'draw_vertical_lines' => true, 
            'draw_triangle'       => false, 
            'change_color'        => 0
            ),
        'Market' => array(
            'name'                => 'Market (orange on black)', 
            'font_family'         => 'sans-serif', 
            'font_color'          => '#FF6600', 
            'bg_color'            => '#000066',
            'text_opacity'        => 1,
            'bg_opacity'          => 1, 
            'draw_vertical_lines' => true, 
            'draw_triangle'       => true, 
            'change_color'        => 0
            ),
        'CNBC' => array(
            'name'                => 'CNBC (green on white)', 
            'font_family'         => 'Arial', 
            'font_color'          => '#33CC33', 
            'bg_color'            => '#FFFFFF',
            'text_opacity'        => 1,
            'bg_opacity'          => 1, 
            'draw_vertical_lines' => false, 
            'draw_triangle'       => true, 
            'change_color'        => 2
            )
        );
}

function stock_ticker_create_template_field() {

    $all_settings = stock_ticker_templates();
    ?>
        <label for="input_default_settings">Template: </label>
        <select id="input_default_settings" name="template" style="width:205px;">
        <option selected> ------- </option>
        <?php 
            foreach($all_settings as $key=>$setting){
                echo "<option value='{$key}'>{$setting['name']}</option>";
            }
        ?>
        </select>
        <input type="submit" name="save_changes"  value="Apply" class="button-primary" />&nbsp;<sup>*</sup>
        <br/>
        <sup>* NOTE: Not all options are over-written by template</sup>
    <?php
}

function stock_ticker_update_display_options() {
    
    global $stock_ticker_vp;
    $st_ds             = get_option('stock_ticker_default_settings');
    $selected_template = $_POST['template'];  //NOTE: if this doesn't exist it'll be NULL
    $all_templates     = stock_ticker_templates();

    $template_settings = array(); 
    if(array_key_exists($selected_template, $all_templates)) {
        
        $template_settings = $all_templates[$selected_template];
        unset($template_settings['name']); //throw out the name or we'll end up adding it to default settings (which we don't need)

    }

    $st_ds_new = array();
    
    //NOTE: these won't exist in the post if they are unchecked
    //NOTE: wp_options stores booleans as "on" or "" within mysql
    $st_ds_new['draw_vertical_lines'] = (array_key_exists('create_vertical_dash',     $_POST) ? 1 : 0); //true -> 1    'on' in mysql
    $st_ds_new['draw_triangle']       = (array_key_exists('create_triangle',          $_POST) ? 1 : 0); //false -> 0   '' in mysql
    $st_ds_new['change_color']        = $_POST['change_color']; //radio button, so will 0/1/2
    
    //these will return either the cleaned up value, or a minimum, or maximum value, or the default (arg2)
    //If returns false, it will NOT update them, and the display creation function will continue to use the most recently saved value
    //IN FUTURE: this will be replaced with AJAX and javascript validation
    
    //NOTE: stock_ticker_validate_integer($new_val, $min_val, $max_val, $default)
    $tmp = relevad_plugin_validate_integer($_POST['max_display'],  $stock_ticker_vp['max_display'][0],  $stock_ticker_vp['max_display'][1],  false);
    if ($tmp) {
    $st_ds_new['display_number'] = $tmp;}
    
    $tmp = relevad_plugin_validate_integer($_POST['scroll_speed'], $stock_ticker_vp['scroll_speed'][0], $stock_ticker_vp['scroll_speed'][1], false);
    if ($tmp) {
    $st_ds_new['scroll_speed']   = $tmp;}
    
    $st_ds_new['width']  = relevad_plugin_validate_integer($_POST['width'],       $stock_ticker_vp['width'][0],        $stock_ticker_vp['width'][1],   $st_ds['width']);
    $st_ds_new['height'] = relevad_plugin_validate_integer($_POST['height'],      $stock_ticker_vp['height'][0],       $stock_ticker_vp['height'][1],  $st_ds['height']);
    
    // VALIDATE fonts
    $st_ds_new['font_size']   = relevad_plugin_validate_integer($_POST['font_size'],   $stock_ticker_vp['font_size'][0], $stock_ticker_vp['font_size'][1],  $st_ds['font_size']);
    $st_ds_new['font_family'] = relevad_plugin_validate_font_family($_POST['font_family'], $st_ds['font_family']);
    
    // VALIDATE COLORS
    $st_ds_new['font_color'] = relevad_plugin_validate_color($_POST['text_color'],        $st_ds['font_color']);
    $st_ds_new['bg_color']   = relevad_plugin_validate_color($_POST['background_color1'], $st_ds['bg_color']);
    
    $st_ds_new['text_opacity'] = relevad_plugin_validate_opacity($_POST['text_opacity'],       $st_ds['text_opacity']);
    $st_ds_new['bg_opacity']   = relevad_plugin_validate_opacity($_POST['background_opacity'], $st_ds['bg_opacity']);
    
    $tmp = trim($_POST['ticker_advanced_style']); //strip spaces
    if ($tmp != '' && substr($tmp, -1) != ';') { $tmp .= ';'; } //poormans making of a css rule
    $st_ds_new['advanced_style'] = $tmp;
    
    //issue warning if scaling is going to lead to overlap.
    $minimum_width = $st_ds_new['font_size'] * 4 * 4;  //point font * 4 characters * 4 elements ~ aproximate
    $entry_width   = $st_ds_new['width'] / $st_ds_new['display_number'];
    if ($minimum_width > $entry_width) {
        echo "<div id='sp-warning'><h1>Warning:</h1>";
        echo "Chosen font size of " . $st_ds_new['font_size'] . " when used with width of " . $st_ds_new['width'] . " would cause overlap of text.";
        echo "Ignoring display_number of " . $st_ds_new['display_number'] . " to compensate</div>";
    }
    
    //now merge template settings + post changes + old unchanged settings
    update_option('stock_ticker_default_settings', array_replace($st_ds, $st_ds_new, $template_settings));
}

function stock_ticker_create_ticker_config($st_ds) {
    ?>
        <label for="input_stock_tickerwidth">Width: </label>
        <input  id="input_stock_tickerwidth"   name="width"   type="text" value="<?php echo $st_ds['width']; ?>" class="itxt"/>
        <label for="input_stock_ticker_height">Height: </label>
        <input  id="input_stock_ticker_height" name="height"  type="text" value="<?php echo $st_ds['height']; ?>" class="itxt"/>
        
        <br />
        <label for="input_max_display">Number of stocks displayed on the screen at one time: </label>
        <input  id="input_max_display"  name="max_display"   type="text" value="<?php echo $st_ds['display_number']; ?>" class="itxt" style="width:40px;" />
        <label for="input_scroll_speed">Scroll speed (Pixels per second): </label>
        <input  id="input_scroll_speed" name="scroll_speed"  type="text" value="<?php echo $st_ds['scroll_speed']; ?>" class="itxt" />
        
        <br />
        <label for="input_background_color">Background color: </label>
        <input  id="input_background_color" name="background_color1"     type="text" value="<?php echo $st_ds['bg_color']; ?>" class="itxt color_input" style="width:101px" />
        <sup id="background_color_picker_help"><a href="http://www.w3schools.com/tags/ref_colorpicker.asp" ref="external nofollow" target="_blank" title="Use hex to pick colors!" class="color_q">[?]</a></sup>
		<script>enhanceTypeColor("input_background_color", "background_color_picker_help");</script>
        <br />
       	<label for="input_background_opacity">Background opacity<span id="background_opacity_val0"> (0-1)</span>: </label>
        <span id="background_opacity_val1"></span>
		<input  id="input_background_opacity" name="background_opacity"  type="text" value="<?php echo $st_ds['bg_opacity']; ?>" class="itxt"/>
		<span id="background_opacity_val2"></span>
		<script>enhanceTypeRange("input_background_opacity", "background_opacity_val");</script> 
    <?php
}

function stock_ticker_create_text_config($st_ds) {
	    $default_fonts = array("Arial", "cursive", "Gadget", "Georgia", "Impact", "Palatino", "sans-serif", "serif", "Times");
    ?>
        <label for="input_text_color">Text color: </label>
        <input  id="input_text_color" name="text_color"     type="text"  value="<?php echo $st_ds['font_color']; ?>" class="itxt color_input" style="width:101px" />
    <sup id="text_color_picker_help"><a href="http://www.w3schools.com/tags/ref_colorpicker.asp" ref="external nofollow" target="_blank" title="Use hex to pick colors!" class="color_q">[?]</a></sup>
		
		<script>enhanceTypeColor("input_text_color", "text_color_picker_help");</script>
        
        <label for="input_font_size">Font size: </label>
        <input  id="input_font_size" name="font_size"       type="text"  value="<?php echo $st_ds['font_size']; ?>" class="itxt"   style="width:40px;" />
        <br/>
        
        <label for="input_text_opacity">Text opacity<span id="text_opacity_val0"> (0-1)</span>: </label>
		<span id="text_opacity_val1"></span>
        <input  id="input_text_opacity" name="text_opacity"  type="text" value="<?php echo $st_ds['text_opacity']; ?>" class="itxt"/>
		<span id="text_opacity_val2"></span>
		
		<script>enhanceTypeRange("input_text_opacity", "text_opacity_val");</script> 
		
        <br/>
        <label for="input_font_family">Font family: </label>
        <input  id="input_font_family" name="font_family" list="font_family" value="<?php echo $st_ds['font_family']; ?>" autocomplete="on" style="width:125px" />
        <datalist id="font_family"><!-- used as an "autocomplete dropdown" within the input text field -->
        <?php  //Any real reason to not use a regular dropdown instead?
            foreach($default_fonts as $font) {
                echo "<option value='{$font}'></option>";
            }
        ?>
        </datalist>
    <?php
}

//generates all of the checkboxes in the admin page
function stock_ticker_create_display_config($st_ds) {
    ?>
    <input  id="input_create_vertical_dash"    name="create_vertical_dash"    type="checkbox" <?php checked($st_ds['draw_vertical_lines']); ?>>
    <label for="input_create_vertical_dash">Draw vertical lines</label>
    <br/>

    <input  id="input_create_triangle"         name="create_triangle"         type="checkbox" <?php checked($st_ds['draw_triangle']);?>>
    <label for="input_create_triangle">Draw triangle</label>
    <br/>

    <span title="off=no color change  on=change colors for +/- values only   all=change color for all stock values">Change color (green+ red- gray):</span><br/>
    <input id="input_change_color_off" name="change_color" value="0" type="radio" <?php checked(0, $st_ds['change_color']);?>>Off
    <input id="input_change_color_on"  name="change_color" value="1" type="radio" <?php checked(1, $st_ds['change_color']);?>>On
    <input id="input_change_color_all" name="change_color" value="2" type="radio" <?php checked(2, $st_ds['change_color']);?>>All
<?php
}


function stock_ticker_create_style_field($st_ds) {
    $AS    = $st_ds['advanced_style'];
    echo "<p>If you have additional CSS rules you want to apply to the entire ticker (such as alignment or borders) you can add them below.</p>
          <p> Example: <code>margin:auto; border:1px solid #000000;</code></p>
          <input id='input_ticker_advanced_style' name='ticker_advanced_style' type='text' value='{$AS}' class='itxt' style='width:90%; text-align:left;' />";

}

function stock_ticker_convert_old_options() {
    $tmp1 = get_option('stock_ticker_color_scheme');
    $tmp2 = get_option('stock_ticker_opacity');
    $tmp3 = get_option('stock_ticker_display_size');
    $tmp4 = get_option('stock_ticker_font_options');
    
    $counter = 0; //none
    if (get_option('stock_ticker_enable_change_color')) { 
        $counter++;  //1-some
        if (get_option('stock_ticker_all_change_color') ) {
            $counter++; //2-all
        }
    }
    
    $stock_ticker_default_settings = Array(
        'data_display'        => get_option('stock_ticker_data_display'),
        'font_color'          => $tmp1[0], 
        'bg_color'            => $tmp1[1],
        'text_opacity'        => $tmp2[0],
        'bg_opacity'          => $tmp2[1], 
        'width'               => $tmp3[0],
        'height'              => $tmp3[1],
        'font_size'           => $tmp4[0],
        'font_family'         => $tmp4[1],
        'scroll_speed'        => get_option('stock_ticker_scroll_speed'),
        'display_number'      => get_option('stock_ticker_display_number'),
        'advanced_style'      => get_option('stock_ticker_advanced_style'),
        'draw_vertical_lines' => get_option('stock_ticker_draw_vertical_lines'),
        'draw_triangle'       => get_option('stock_ticker_draw_triangle'),
        'change_color'        => $counter
        );
    delete_option('stock_ticker_color_scheme'); //cleanup the old stuff
    delete_option('stock_ticker_opacity');
    delete_option('stock_ticker_display_size');
    delete_option('stock_ticker_font_options');
    
    delete_option('stock_ticker_data_display');
    delete_option('stock_ticker_scroll_speed');
    delete_option('stock_ticker_display_number');
    delete_option('stock_ticker_advanced_style');
    
    delete_option('stock_ticker_draw_vertical_lines');
    delete_option('stock_ticker_draw_triangle');
    delete_option('stock_ticker_enable_change_color');
    delete_option('stock_ticker_all_change_color');
    
    //never used so cleanup now anyways
    delete_option('stock_ticker_default_market');
    delete_option('stock_ticker_default_fonts');
    delete_option('stock_ticker_display_option_strings');
    
    update_option('stock_ticker_default_settings', $stock_ticker_default_settings); //NOTE: update_option will add if does not exist
}

/* //unused
function stock_ticker_create_display_type_field() {
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


function stock_ticker_update_display_type_field() {
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
?>
