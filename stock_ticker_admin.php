<?php
namespace stockTicker;
define(__NAMESPACE__ . '\NS', __NAMESPACE__ . '\\');

global $wpdb;

global $list_table; //needs to be created inside stock_ticker_add_screen_options, but utilized within stock_ticker_list_page

global $relevad_plugins;
if (!is_array($relevad_plugins)) {
    $relevad_plugins = array();
}
$relevad_plugins[] = array(
'url'  => admin_url('admin.php?page=stock_ticker_list'),
'name' => 'Custom Stock Ticker'
);

//NOTE: These will automatically be within the namespace
define(NS.'SP_TABLE_NAME', $wpdb->prefix . 'stock_tickers');
define(NS.'SP_CHARSET',    $wpdb->get_charset_collate()); //requires WP v3.5

define(NS.'SP_CURRENT_VERSION', '2.1.1');   //NOTE: should always match Version: ### in the plugin special comment
define(NS.'SP_TYPE', 'ticker');
define(NS.'SP_VALIDATION_PARAMS', <<< DEFINE
{
"max_display":   [1,   20],
"scroll_speed":  [1,   150],
"width":         [200, 2000],
"height":        [10,  100],
"font_size":     [5,   32]
}
DEFINE
);  //access with (array)json_decode(SP_VALIDATION_PARAMS);

// Feature Improvement: think about putting each individual config into a class, does that buy us anything?
// http://stackoverflow.com/questions/1957732/can-i-include-code-into-a-php-class

include plugin_dir_path(__FILE__) . 'stock_plugin_utils.php'; //used to contain validation functions
include plugin_dir_path(__FILE__) . 'relevad_plugin_utils.php';
include plugin_dir_path(__FILE__) . 'stock_plugin_cache.php';
include plugin_dir_path(__FILE__) . 'stock_ticker_display.php';

function stock_ticker_create_db_table() {  //NOTE: for brevity into a function
    $table_name = SP_TABLE_NAME;
    $charset    = SP_CHARSET;
    static $run_once = true; //on first run = true
    if ($run_once === false) return;
    
    //NOTE: later may want: 'default_market'    => 'DOW',   'display_options_strings' 
    $sql = "CREATE TABLE {$table_name} (
    id                      mediumint(9)                    NOT NULL AUTO_INCREMENT,
    name                    varchar(50)  DEFAULT ''         NOT NULL,
    bg_color                varchar(7)   DEFAULT '#000000'  NOT NULL,
    font_color              varchar(7)   DEFAULT '#5DFC0A'  NOT NULL,
    font_family             varchar(20)  DEFAULT 'Arial'    NOT NULL,
    font_size               tinyint(3)   DEFAULT 12         NOT NULL,
    width                   smallint(4)  DEFAULT 400        NOT NULL,
    height                  smallint(4)  DEFAULT 20         NOT NULL,
    scroll_speed            smallint(4)  DEFAULT 60         NOT NULL,
    data_display            tinyint(2)   DEFAULT 30         NOT NULL,
    text_opacity            float(5,4)   DEFAULT 1          NOT NULL,
    bg_opacity              float(5,4)   DEFAULT 1          NOT NULL,
    display_number          tinyint(3)   DEFAULT 2          NOT NULL,
    draw_vertical_lines     tinyint(1)   DEFAULT 1          NOT NULL,
    draw_triangle           tinyint(1)   DEFAULT 1          NOT NULL,
    change_color            tinyint(1)   DEFAULT 1          NOT NULL,
    stock_list              text         NOT NULL,
    advanced_style          text         NOT NULL,
    UNIQUE KEY name (name),
    PRIMARY KEY (id)
    ) {$charset};";
    //NOTE: display_number is depricated. remove later?
    //NOTE: Extra spaces for readability screw up dbDelta, so we remove those
    $sql = preg_replace('/ +/', ' ', $sql);
    //NOTE: WE NEED 2 spaces exactly between PRIMARY KEY and its definition.
    $sql = str_replace('PRIMARY KEY', 'PRIMARY KEY ', $sql);
    
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql ); //this will return an array saying what was done, if we want to output it
    $run_once = false;
}

function stock_ticker_activate() {   
    $current_version = SP_CURRENT_VERSION;
    if (!get_option('stock_ticker_category_stock_list') && !get_option('stock_ticker_per_category_stock_lists')) {
        //if neither of these exist then assume initial install
        stock_ticker_create_db_table();
        $values = array( //NOTE: the rest should all be the defaults
                        'id'             => 1, //explicitly set this or else mysql configs where the default is not 1 will be broken
                        'name'           => 'Default Settings',
                        'advanced_style' => 'margin: auto;'
                        );
        sp_add_row($values);
        add_option('stock_ticker_per_category_stock_lists', array('default' => '^GSPC,^IXIC,^NYA,MMM,AXP,T,BA,CAT,CVX,CSCO,KO,DD,XOM,GE,GS,HD,INTC,IBM,JNJ,JPM,MCD,MRK,MSFT,NKE,PFE,PG,TRV,UNH,UTX,VZ,V,WMT,DIS'));
        add_option('stock_ticker_version',                         $current_version);
        add_option('stock_ticker_version_text', "Initial install v{$current_version}");
    }
}
register_activation_hook( $main_plugin_file, NS.'stock_ticker_activate' ); //references $main_plugin_file from the bootstrap file


//*********cleanup and conversion functions for updating versions *********
function stock_ticker_handle_update() {
    $current_version = SP_CURRENT_VERSION;
    
    $db_version = get_option('stock_ticker_version', '0');

    //NOTE: Don't forget to add each and every version number as a case
    switch($db_version) {
        case '0': //if versioning did not exist yet, then use old method
            //version 1.0 -> 1.1
            if (get_option('stock_ticker_category_stock_list')) {
                stock_plugin_convert_old_category_stock_list('ticker'); 
            }
            //version 1.2 -> 1.3
            if (get_option('stock_ticker_color_scheme')) {
                stock_ticker_convert_old_options(); 
            }

        case '1.3.2': //Added this versioning system in this version
        case '1.3.3':
        case '1.3.4':
        case '1.3.5':
            stock_ticker_create_db_table(); //Added table storage structure in 1.4

            $default_settings = get_option('stock_ticker_default_settings', false);
            if ($default_settings !== false) {
                $default_settings['name'] = 'Default Settings';
                $default_settings['id']   = 1; //force the ID to be 1
                if (false !== sp_add_row($default_settings))
                    delete_option('stock_ticker_default_settings');
            }
            else {
                stock_ticker_activate(); //Recall the activate function and this problem should be fixed
            }
            
        case '1.4':
            stock_ticker_create_db_table(); //bugfix for table storage in 1.4.1

        case '1.4.1':
        case '2.0':
        case '2.0.1':
            //*****************************************************
            //this will always be right above current_version case
            //keep these 2 updates paired
            update_option('stock_ticker_version',      $current_version);
            update_option('stock_ticker_version_text', " updated from v{$db_version} to");
            //NOTE: takes care of add_option() as well
        case $current_version:
            break;
        //NOTE: if for any reason the database entry disapears again we might have a problem updating or performing table modifcations on tables already modified.
        default: //this shouldn't be needed
            //future version? downgrading?
            update_option('stock_ticker_version_text', " found v{$db_version} current version");
            break;
    }
}
//*************************************************************************

function stock_ticker_admin_enqueue($hook) {
    $current_version = SP_CURRENT_VERSION;

    //echo "<!-- testing {$hook} '".strpos($hook, 'stock_ticker')."'-->";
    //example: relevad-plugins_page_stock_ticker_admin
    if (strpos($hook, 'stock_ticker') === false) {return;} //do not run on other admin pages
    
    wp_register_style ('stock_plugin_admin_style',  plugins_url('stock_plugin_admin_style.css', __FILE__), false,             $current_version);
    wp_register_script('stock_plugin_admin_script', plugins_url('stock_plugin_admin_script.js', __FILE__), array( 'jquery' ), $current_version, false);

    wp_enqueue_style ('stock_plugin_admin_style');
    wp_enqueue_script('stock_plugin_admin_script');
    
    stock_ticker_scripts_enqueue(true); //we also need these scripts
}
add_action('admin_enqueue_scripts', NS.'stock_ticker_admin_enqueue');

function stock_ticker_admin_actions() {
    
    relevad_plugin_add_menu_section(); //imported from relevad_plugin_utils.php
    
           //add_submenu_page( 'options-general.php', $page_title,   $menu_title,         $capability,       $menu_slug,           $function ); // do not use __FILE__ for menu_slug
    $hook1 = add_submenu_page('relevad_plugins',     'StockTickers', 'StockTickers',      'manage_options', 'stock_ticker_list',   NS.'stock_ticker_list_page'); 
    $hook2 = add_submenu_page('relevad_plugins',     'New Ticker',   '&rarr; New Ticker', 'manage_options', 'stock_ticker_addnew', NS.'stock_ticker_addnew');     

    add_action( "load-{$hook1}", NS.'stock_ticker_add_screen_options' ); 
    //this adds the screen options dropdown along the top
}
add_action('admin_menu', NS.'stock_ticker_admin_actions');


function stock_ticker_add_screen_options() {
    global $list_table;
    
    $option = 'per_page';
    $args = array(
         'label' => 'Shortcodes',
         'default' => 10,
         'option' => 'shortcodes_per_page'
    );
    add_screen_option( $option, $args );
    
    //placed in this function so that list_table can get the show/hide columns checkboxes automagically
    $list_table = new stock_shortcode_List_Table(); //uses relative namespace automatically
}

function stock_ticker_set_screen_option($status, $option, $value) {
    //https://www.joedolson.com/2013/01/custom-wordpress-screen-options/
    //standard screen options are not filtered in this way
    //if ( 'shortcodes_per_page' == $option ) return $value;
    
    //return $status;
    
    return $value;
}
add_filter('set-screen-option', NS.'stock_ticker_set_screen_option', 10, 3);

//ON default settings, should restore to defaults
//ON other shortcodes, should just reload the page
function stock_ticker_reset_options() {
    update_option('stock_ticker_per_category_stock_lists', array('default' => '^GSPC,^IXIC,^NYA,MMM,AXP,T,BA,CAT,CVX,CSCO,KO,DD,XOM,GE,GS,HD,INTC,IBM,JNJ,JPM,MCD,MRK,MSFT,NKE,PFE,PG,TRV,UNH,UTX,VZ,V,WMT,DIS')); //Important no spaces  
   
    $stock_ticker_default_settings = Array(
        //'name'                  => 'Default Settings', //redundant
        'data_display'          => array(0,1,1,1,1,0),
        //'default_market'      => 'DOW',
        //'display_options_strings' => array("Market", "Symbol", "Last value", "Change value", "Change percentage", "Last trade"),
        'font_color'            => '#5DFC0A', 
        'bg_color'              => 'black',
        'text_opacity'          => 1,
        'bg_opacity'            => 1, 
        'width'                 => 400,
        'height'                => 20,
        'font_size'             => 12,
        'font_family'           => 'Arial',
        'scroll_speed'          => 60,
        'advanced_style'        => 'margin:auto;',
        'draw_vertical_lines'   => true,
        'draw_triangle'         => true,
        'change_color'          => 1 //0-none 1-some 2-all
        );
        
    sp_update_row($stock_ticker_default_settings, array('name' => 'Default Settings'));
    
    stock_plugin_notice_helper("Reset 'Default Settings' to initial install values.");
}

function stock_ticker_addnew() { //default name is the untitled_id#
    
    stock_ticker_handle_update();
    
    //Add row to DB with defaults, name = same as row id
    $values = array( //NOTE: the rest should all be the defaults
                        //'name'           => 'Default Settings', //no name to start with, have to do an update after
                        'advanced_style' => 'margin: auto;'
                        );
    $new_id = sp_add_row($values);
    
    if ($new_id !== false) {
        stock_plugin_notice_helper("Added New ticker");
        stock_ticker_admin_page($new_id);
    }
    else {
        stock_plugin_notice_helper("ERROR: Unable to create new ticker. <a href='javascript:history.go(-1)'>Go back</a>", 'error');
    }
    
    return;
}

// Default Admin page.
// PAGE for displaying all previously saved tickers.
function stock_ticker_list_page() {
    global $list_table;
    
    stock_ticker_handle_update();

    //This page is referenced from all 3 options: copy, edit, delete and will transfer control to the appropriate function
    $action = (isset($_GET['action'])    ? $_GET['action']    : '');
    $ids    = (isset($_GET['shortcode']) ? $_GET['shortcode'] : false); //form action post does not clear url params

    //action = -1 is from the search query
    if (!empty($action) && $action !== '-1' && !is_array($ids) && !is_numeric($ids)) {
        stock_plugin_notice_helper("ERROR: No shortcode ID for action: {$action}.", 'error');
        $action = ''; //clear the action so we skip to default switch action
    }
    
    switch ($action) {
        case 'copy':
            if (is_array($ids)) $ids = $ids[0];
            $old_id = $ids;
            $ids = sp_clone_row((int)$ids);
            if ($ids === false) {
                stock_plugin_notice_helper("ERROR: Unable to clone shortcode {$old_id}. <a href='javascript:history.go(-1)'>Go back</a>", 'error');
                return;
            }
            stock_plugin_notice_helper("Cloned {$old_id} to {$ids}");
        case 'edit':
            if (is_array($ids)) $ids = $ids[0];
            stock_ticker_admin_page((int)$ids);
            break;

        case 'delete': //fall through to display the list as normal
            if (! isset($_GET['shortcode'])) {
                stock_plugin_notice_helper("ERROR: No shortcodes selected for deletion.", 'error');
            }
            else {
                $ids = $_GET['shortcode'];
                if (!is_array($ids)) {
                    $ids = (array)$ids; //make it an array
                }
                sp_delete_rows($ids); //NOTE: no error checking needed, handled inside
            }
        default:
            $current_version = SP_CURRENT_VERSION;
            
            $version_txt = get_option('stock_ticker_version_text', '') . " v{$current_version}";
            update_option('stock_ticker_version_text', ''); //clear the option after we display it once
        
            $list_table->prepare_items();
            
            //$thescreen = get_current_screen();
            
            echo <<<HEREDOC
            <div id="sp-options-page">
                <h1>Custom Stock Ticker</h1><sub>{$version_txt}</sub>
                <p>The Custom Stock ticker plugin allows you to create and run your own custom stock tickers.</p>
                <p>To configure a ticker, click the edit button below that ticker's name. Or add a new ticker using the link below.</p>
                <p>To place a ticker onto your site, copy a shortcode from the table below, or use the default shortcode of <code>[stock-ticker]</code>, and paste it into a post, page, or <a href="https://wordpress.org/plugins/shortcode-widget/" ref="external nofollow" target="_blank">Shortcode Widget</a>.<br />
                Alternatively, you can use <code>&lt;?php echo do_shortcode('[stock-ticker]'); ?&gt;</code> inside your theme files or a <a href="https://wordpress.org/plugins/php-code-widget/" ref="external nofollow" target="_blank">PHP Code Widget</a>.</p>
            </div>
                <div id='sp-list-table-page' class='wrap'>
HEREDOC;
            echo "<h2>Available Stock Tickers <a href='" . esc_url( menu_page_url( 'stock_ticker_addnew', false ) ) . "' class='add-new-h2'>" . esc_html( 'Add New' ) . "</a>";

            if ( ! empty( $_REQUEST['s'] ) ) {
                echo sprintf( '<span class="subtitle">Search results for &#8220;%s&#8221;</span>', esc_html( $_REQUEST['s'] ) );
            }
            echo "</h2>";
          
            echo "<form method='get' action=''>"; //this arrangement of display within the form, is copied from contactform7
                echo "<input type='hidden' name='page' value='" . esc_attr( $_REQUEST['page'] ) . "' />";
                $list_table->search_box( 'Search Stock Tickers', 'stock-ticker' ); 
                $list_table->display();  //this actually renders the table itself
            echo "</form></div>";
            
            break;
    }
}

/** Creates the admin page. **/
function stock_ticker_admin_page($id = '') {
    
    if ($id === '') {
        stock_plugin_notice_helper("ERROR: No shortcode ID found", 'error'); return; //This should never happen
    }
    
    $ds_flag = false; //flag used for handling specifics of default settings
    if ($id === 1) {
        $ds_flag = true;
    }
    
    if (isset($_POST['save_changes'])) {
        if ($ds_flag) stock_plugin_update_per_category_stock_lists();
        stock_ticker_update_display_options($id); //pass in the unchanged settings
        stock_plugin_notice_helper("Changes saved");
    }
    elseif (isset($_POST['reset_options'])) {
        if ($ds_flag)
            stock_ticker_reset_options();
        else
            stock_plugin_notice_helper("Reverted all changes");
    }

    $shortcode_settings = sp_get_row($id, 'id'); //NOTE: have to retrieve AFTER update
    if ($shortcode_settings === null) {
        stock_plugin_notice_helper("ERROR: No shortcode ID '{$id}' exists. <a href='javascript:history.go(-1)'>Go back</a>", 'error');
        return;
    }

    $the_action = '';
    if (!isset($_GET['action']) || $_GET['action'] != 'edit') {
        $the_action = '?page=stock_ticker_list&action=edit&shortcode=' . $id; //for turning copy -> edit
    }
    
    $reset_btn    = "Revert Changes";
    $reset_notice = "";
    if ($ds_flag) {
        $reset_btn    = "Reset to Defaults";
        $reset_notice = "<sup>*</sup><br /><sup>* NOTE: 'Reset to Defaults' also clears all default stock lists.</sup>";
    }

    echo <<<HEREDOC
<div id="sp-options-page">
    <h1>Edit Custom Stock Ticker</h1>
    <p>Choose your stocks and display settings below.</p>
    <form action='{$the_action}' method='POST'>
HEREDOC;

    echo "<div id='sp-form-div' class='postbox-container sp-options'>
            <div id='normal-sortables' class='meta-box-sortables ui-sortable'>
                <div id='referrers' class='postbox'>";
    if (!$ds_flag) {
        echo      "<div class='inside'>";
        stock_ticker_create_name_field($shortcode_settings);
    }
    else {
        echo "     <h3 class='hndle'>Default Shortcode Settings</h3>
                    <div class='inside'>";
    }
                        stock_ticker_create_template_field();
    echo "              <div class='sp-options-subsection'>
                            <h4>Ticker Config</h4>";
                                stock_plugin_cookie_helper(1);
                                stock_ticker_create_ticker_config($shortcode_settings); 
    echo "                  </div>
                        </div>
                        <div class='sp-options-subsection'>
                            <h4>Text Config</h4>";
                                stock_plugin_cookie_helper(2);
                                stock_ticker_create_text_config($shortcode_settings);
    echo "                  </div>
                        </div>
                        <div class='sp-options-subsection'>
                            <h4>Stock Display Config</h4>";
                                stock_plugin_cookie_helper(3);
                                stock_ticker_create_display_config($shortcode_settings);
    echo "                  </div>
                        </div>
                        <div class='sp-options-subsection'>
                            <h4>Advanced Styling</h4>";
                                stock_plugin_cookie_helper(4);
                                stock_ticker_create_style_field($shortcode_settings);
    echo "                  </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id='publishing-actions'>
                <input type='submit' name='save_changes'  value='Save'              class='button-primary' />
                <input type='submit' name='reset_options' value='{$reset_btn}' class='button-primary' />
                {$reset_notice}
            </div>
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
                         <p>For Nasdaq, use <code>^IXIC</code>. For S&amp;P500, use <code>^GSPC</code>. For NYSE Composite use <code>^NYA</code>. Unfortunately, DOW is currently not available.</p>"; 
                        if ($ds_flag) stock_plugin_create_per_category_stock_lists();
                        else          stock_plugin_create_stock_list_section($shortcode_settings);
    echo "           </div>
                     </div>
                 </div>
             </div>";

    $the_name = '';
    if (!$ds_flag) $the_name = " name='{$shortcode_settings['name']}'";
    echo <<<HEREDOC
   </form>
   <div id="sp-preview" class="postbox-container sp-options">
      <div id="normal-sortables" class="meta-box-sortables ui-sortable">
         <div id="referrers" class="postbox">
            <h3 class="hndle"><span>Preview</span></h3>
            <div class="inside">
               <p>Based on the last saved settings, this is what the shortcode <code>[stock-ticker{$the_name}]</code> will generate:</p>
HEREDOC;

    echo do_shortcode("[stock-ticker{$the_name}]");
    echo <<<HEREDOC
               <p>To preview your latest changes you must first save changes.</p>
            </div>
         </div>
      </div>
   </div>
   <div class="clear"></div>
</div><!-- end options page -->
HEREDOC;
}//End Stock_ticker_admin_page

//NOTE: not moved to the top as a define, because if we have to call json_decode anyways whats the point
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

    echo "<label for='input_default_settings'>Template: </label>
          <select id='input_default_settings' name='template' style='width:205px;'>
          <option selected> ------- </option>";

          foreach($all_settings as $key=>$setting){
             echo "<option value='{$key}'>{$setting['name']}</option>";
          }

    echo "</select>
        <input type='submit' name='save_changes'  value='Apply' class='button-primary' />&nbsp;<sup>*</sup>
        <br/>
        <sup>* NOTE: Not all options are over-written by template</sup>";

}

function stock_ticker_update_display_options($id) {
    
    $unchanged = sp_get_row($id, 'id');
    $validation_params = (array)json_decode(SP_VALIDATION_PARAMS);
    
    $selected_template = $_POST['template'];  //NOTE: if this doesn't exist it'll be NULL
    $all_templates     = stock_ticker_templates();

    $template_settings = array(); 
    if(array_key_exists($selected_template, $all_templates)) {
        
        $template_settings = $all_templates[$selected_template];
        unset($template_settings['name']); //throw out the name or we'll end up overwriting this shortcode's name

    }

    $settings_new = array();
    
    //NOTE: these won't exist in the post if they are unchecked
    //NOTE: wp_options stores booleans as "on" or "" within mysql
    $settings_new['draw_vertical_lines'] = (array_key_exists('create_vertical_dash',     $_POST) ? 1 : 0); //true -> 1    'on' in mysql
    $settings_new['draw_triangle']       = (array_key_exists('create_triangle',          $_POST) ? 1 : 0); //false -> 0   '' in mysql
    $settings_new['change_color']        = $_POST['change_color']; //radio button, so will 0/1/2
    
    //these will return either the cleaned up value, or a minimum, or maximum value, or the default (arg2)
    //If returns false, it will NOT update them, and the display creation function will continue to use the most recently saved value
    //IN FUTURE: this will be replaced with AJAX and javascript validation
    
    //NOTE: stock_ticker_validate_integer($new_val, $min_val, $max_val, $default)
        
    $tmp = relevad_plugin_validate_integer($_POST['scroll_speed'], $validation_params['scroll_speed'][0], $validation_params['scroll_speed'][1], false);
    if ($tmp) { $settings_new['scroll_speed']   = $tmp; }
    
    $settings_new['width']  = relevad_plugin_validate_integer($_POST['width'],       $validation_params['width'][0],        $validation_params['width'][1],   $unchanged['width']);
    $settings_new['height'] = relevad_plugin_validate_integer($_POST['height'],      $validation_params['height'][0],       $validation_params['height'][1],  $unchanged['height']);
    
    // VALIDATE fonts
    $settings_new['font_size']   = relevad_plugin_validate_integer($_POST['font_size'],   $validation_params['font_size'][0], $validation_params['font_size'][1],  $unchanged['font_size']);
    $settings_new['font_family'] = relevad_plugin_validate_font_family($_POST['font_family'], $unchanged['font_family']);
    
    // VALIDATE COLORS
    $settings_new['font_color'] = relevad_plugin_validate_color($_POST['text_color'],        $unchanged['font_color']);
    $settings_new['bg_color']   = relevad_plugin_validate_color($_POST['background_color1'], $unchanged['bg_color']);
    
    $settings_new['text_opacity'] = relevad_plugin_validate_opacity($_POST['text_opacity'],       $unchanged['text_opacity']);
    $settings_new['bg_opacity']   = relevad_plugin_validate_opacity($_POST['background_opacity'], $unchanged['bg_opacity']);
    
    $tmp = trim($_POST['ticker_advanced_style']); //strip spaces
    if ($tmp != '' && substr($tmp, -1) != ';') { $tmp .= ';'; } //poormans making of a css rule
    $settings_new['advanced_style'] = $tmp;
    
    //last handle this shortcode's stock list and name if either exist
    if (isset($_POST['stocks_for_shortcode'])) {
        $settings_new['stock_list'] = stock_plugin_validate_stock_list($_POST['stocks_for_shortcode']);
    }
    
    if (isset($_POST['shortcode_name']) && $_POST['shortcode_name'] !== $unchanged['name']) {
        //check if other than - and _  if the name is alphanumerics
        if (! ctype_alnum(str_replace(array(' ', '-', '_'), '', $_POST['shortcode_name'])) ) {
            stock_plugin_notice_helper("<b class='sp-warning'>Warning:</b> Allowed only alphanumerics and - _ in shortcode name.<br/>Name reverted!", 'error');
        }
        elseif (sp_name_used($_POST['shortcode_name'])) {
            stock_plugin_notice_helper("<b class='sp-warning'>Warning:</b> Name '{$_POST['shortcode_name']}' is already in use by another shortcode<br/>Name reverted!", 'error');
        }
        else {
            $settings_new['name'] = $_POST['shortcode_name'];
        }
        //NOTE: 50 chars limit but this will be auto truncated by mysql, and enforced by html already
    }
    
    //now merge template settings > post changes > old unchanged settings in that order
    sp_update_row(array_replace($unchanged, $settings_new, $template_settings), array('id' => $id));
}

function stock_ticker_create_name_field($shortcode_settings) {
    echo "<label for='input_shortcode_name'>Shortcode Name:</label> <sub>(limit 50 chars) (alphanumeric and - and _ only)</sub><br/>
    <input id='input_shortcode_name' name='shortcode_name' type='text' maxlength='50' value='{$shortcode_settings['name']}' class='shortcode_name'/>";
}

function stock_ticker_create_ticker_config($shortcode_settings) {

    echo <<< HEREDOC
    <label for="input_stock_tickerwidth">Width: </label>
    <input  id="input_stock_tickerwidth"   name="width"   type="text" value="{$shortcode_settings['width']}" class="itxt"/>
    <label for="input_stock_ticker_height">Height: </label>
    <input  id="input_stock_ticker_height" name="height"  type="text" value="{$shortcode_settings['height']}" class="itxt"/>
    
    <br />
    <label for="input_scroll_speed">Scroll speed (Pixels per second): </label>
    <input  id="input_scroll_speed" name="scroll_speed"  type="text" value="{$shortcode_settings['scroll_speed']}" class="itxt" />
    
    <br />
    <label for="input_background_color">Background color: </label>
    <input  id="input_background_color" name="background_color1"     type="text" value="{$shortcode_settings['bg_color']}" class="itxt color_input" style="width:101px" />
    <sup id="background_color_picker_help"><a href="http://www.w3schools.com/tags/ref_colorpicker.asp" ref="external nofollow" target="_blank" title="Use hex to pick colors!" class="color_q">[?]</a></sup>
    <script type="text/javascript">enhanceTypeColor("input_background_color", "background_color_picker_help");</script>
    <br />
    <label for="input_background_opacity">Background opacity<span id="background_opacity_val0"> (0-1)</span>: </label>
    <span id="background_opacity_val1"></span>
    <input  id="input_background_opacity" name="background_opacity"  type="text" value="{$shortcode_settings['bg_opacity']}" class="itxt"/>
    <span id="background_opacity_val2"></span>
    <script type="text/javascript">enhanceTypeRange("input_background_opacity", "background_opacity_val");</script> 
HEREDOC;

}

function stock_ticker_create_text_config($shortcode_settings) {
        $default_fonts = array("Arial", "cursive", "Gadget", "Georgia", "Impact", "Palatino", "sans-serif", "serif", "Times");

    echo <<< HEREDOC
        <label for="input_text_color">Text color: </label>
        <input  id="input_text_color" name="text_color"     type="text"  value="{$shortcode_settings['font_color']}" class="itxt color_input" style="width:101px" />
    <sup id="text_color_picker_help"><a href="http://www.w3schools.com/tags/ref_colorpicker.asp" ref="external nofollow" target="_blank" title="Use hex to pick colors!" class="color_q">[?]</a></sup>
        
        <script type="text/javascript">enhanceTypeColor("input_text_color", "text_color_picker_help");</script>
        
        <label for="input_font_size">Font size: </label>
        <input  id="input_font_size" name="font_size"       type="text"  value="{$shortcode_settings['font_size']}" class="itxt"   style="width:40px;" />
        <br/>
        
        <label for="input_text_opacity">Text opacity<span id="text_opacity_val0"> (0-1)</span>: </label>
        <span id="text_opacity_val1"></span>
        <input  id="input_text_opacity" name="text_opacity"  type="text" value="{$shortcode_settings['text_opacity']}" class="itxt"/>
        <span id="text_opacity_val2"></span>
        
        <script type="text/javascript">enhanceTypeRange("input_text_opacity", "text_opacity_val");</script> 
        
        <br/>
        <label for="input_font_family">Font family: </label>
        <input  id="input_font_family" name="font_family" list="font_family" value="{$shortcode_settings['font_family']}" autocomplete="on" style="width:125px" />
        <datalist id="font_family"><!-- used as an "autocomplete dropdown" within the input text field -->
HEREDOC;
    //Any real reason to not use a regular dropdown instead?
    foreach($default_fonts as $font) {
        echo "<option value='{$font}'></option>";
    }

    echo "</datalist>";
}

//generates all of the checkboxes in the admin page
function stock_ticker_create_display_config($shortcode_settings) {
    ?>
    <input  id="input_create_vertical_dash"    name="create_vertical_dash"    type="checkbox" <?php checked($shortcode_settings['draw_vertical_lines']); ?>>
    <label for="input_create_vertical_dash">Draw vertical lines</label>
    <br/>

    <input  id="input_create_triangle"         name="create_triangle"         type="checkbox" <?php checked($shortcode_settings['draw_triangle']);?>>
    <label for="input_create_triangle">Draw triangle</label>
    <br/>

    <span title="off=no color change  on=change colors for +/- values only   all=change color for all stock values">Change color (green+ red- gray):</span><br/>
    <input id="input_change_color_off" name="change_color" value="0" type="radio" <?php checked(0, $shortcode_settings['change_color']);?>>Off
    <input id="input_change_color_on"  name="change_color" value="1" type="radio" <?php checked(1, $shortcode_settings['change_color']);?>>On
    <input id="input_change_color_all" name="change_color" value="2" type="radio" <?php checked(2, $shortcode_settings['change_color']);?>>All
<?php
}


function stock_ticker_create_style_field($shortcode_settings) {
    echo "<p>If you have additional CSS rules you want to apply to the entire ticker (such as alignment or borders) you can add them below.</p>
          <p> Example: <code>margin:auto; border:1px solid #000000;</code></p>
          <input id='input_ticker_advanced_style' name='ticker_advanced_style' type='text' value='{$shortcode_settings['advanced_style']}' class='itxt' style='width:90%; text-align:left;' />";

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
