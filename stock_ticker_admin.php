<?php

/*
	Plugin Name: Custom Stock Ticker
	Plugin URI: http://relevad.com/wp-plugins/
	Description: Create customizable moving stock tickers that can be placed anywhere on a site using shortcodes.
	Author: Relevad
	Version: 1.0
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

include WP_CONTENT_DIR."/plugins/custom-stock-ticker/stock_ticker_display.php";
include WP_CONTENT_DIR.'/plugins/custom-stock-ticker/stock_ticker_cache.php';
add_shortcode('stock-ticker', 'stock_ticker');

add_option("stock_ticker_category_stock_list", array('Default'=>array('GOOG','YHOO','AAPL')));
add_option('stock_ticker_default_market', "DOW");
//data display option: Market, Symbol, Last value, change value, change percentage, last trade
add_option('stock_ticker_data_display',array(0,1,1,1,1,0));
add_option('stock_ticker_display_option_strings', array("Market","Symbol","Last value","Change value","Change percentage","Last trade"));

//Controls the color scheme the array holds [Text,Background]
add_option('stock_ticker_color_scheme', array('#5DFC0A','black'));

//Controls the opacity of the text and background [Text_opacity,Background_opacity]
add_option('stock_ticker_opacity', array(1,1));
//the size option holds (width,height)
add_option('stock_ticker_display_size', array(400,20));
//Font options are (size, family)
add_option('stock_ticker_font_options', array(12, "Arial"));
//Default font types
add_option('stock_ticker_default_fonts', array('Arial','cursive','Gadget','Georgia','Impact','Palatino','sans-serif','serif','Times'));

add_option('stock_ticker_scroll_speed', 60);

add_option('stock_ticker_display_number', 2);

add_option('stock_ticker_draw_vertical_lines', true);

add_option('stock_ticker_draw_triangle', true);

add_option('stock_ticker_enable_change_color', true);

add_option('stock_ticker_all_change_color', false);

add_option('stock_ticker_advanced_style', 'margin:auto;');

add_option('stock_ticker_default_settings',array(
	'Classic'=>array(
		'name'=>'Classic (black/white)', 
		'font_family' =>'Arial', 
		'font_color'=>'white',
		'back_color'=> 'black',
		'text_opacity'=>1,
		'background_opacity'=>1, 
		'verti_lines'=>true, 
		'draw_triangle'=>true, 
		'change_color'=>false,
		'all_change'=>false),
	'CNN'=>array(
		'name'=>'CNN (black/green)', 
		'font_family' =>'Arial', 
		'font_color'=>'#99FF99', 
		'back_color'=> 'black',
		'text_opacity'=>1,
		'background_opacity'=>1, 
		'verti_lines'=>true, 
		'draw_triangle'=>false, 
		'change_color'=>false,
		'all_change'=>false),
	'Market'=>array(
		'name'=>'Market (black/orange)', 
		'font_family' =>'sans-serif', 
		'font_color'=>'#FF6600', 
		'back_color'=> '#000066',
		'text_opacity'=>1,
		'background_opacity'=>1, 
		'verti_lines'=>true, 
		'draw_triangle'=>true, 
		'change_color'=>false, 
		'all_change'=>false),
	'CNBC'=>array(
		'name'=>'CNBC (white/green)', 
		'font_family' =>'Arial', 
		'font_color'=>'#33CC33', 
		'back_color'=> 'white',
		'text_opacity'=>1,
		'background_opacity'=>1, 
		'verti_lines'=>false, 
		'draw_triangle'=>true, 
		'change_color'=>true,
		'all_change'=>true),

		));

function stock_ticker_admin_init() {
	wp_register_style('stock_ticker_admin_style', plugins_url('stock_ticker_admin_style.css', __FILE__));
	wp_enqueue_style('stock_ticker_admin_style');
	wp_register_script('stock_ticker_admin_script',plugins_url('stock_ticker_admin_script.js', __FILE__) ,array( 'jquery' ),false, false);
	wp_enqueue_script('stock_ticker_admin_script');
}
add_action('init', 'stock_ticker_admin_init');


add_action('admin_menu', 'stock_ticker_admin_actions');

 function stock_ticker_admin_actions(){

 	add_options_page('StockTicker', 'StockTicker', 'manage_options', __FILE__, 'stock_ticker_admin_page');

}




/*
*This is the admin page. 
*
*/
function stock_ticker_admin_page(){

?>
<div id="ticker-options-page" style="max-width:850px;">
 	<h1>Custom Stock Ticker</h1>
	<p>The stock ticker plugin allows you to create and run your own custom stock tickers.</p>
	<p>Choose your stocks and display settings below.<br />
	Then place your the shortcode <code>[stock-ticker]</code> inside a post, page, or <a href="https://wordpress.org/plugins/shortcode-widget/" ref="external nofollow" target="_blank">Shortcode Widget</a>.<br />
	Or, you can use <code>&lt;?php echo do_shortcode('[stock-ticker]'); ?&gt;</code> inside your theme files or <a href="https://wordpress.org/plugins/php-code-widget/" ref="external nofollow" target="_blank">PHP Code Widget</a>.</p>

	<?php
	//to do: make the updates using ajax
	if(isset($_POST['save_changes'])){
		stock_ticker_update_display_options();
		stock_ticker_create_display_options();
	}else{
		stock_ticker_create_display_options();
	}
	echo '	<div class="postbox-container ticker-options" style="display:block; clear:both; width:750px;">
				<div id="normal-sortables" class="meta-box-sortables ui-sortable">
					<div id="referrers" class="postbox">
						<h3 class="hndle"><span>Preview</span></h3>
						<div class="inside">
	';
	echo '<p>The following ticker uses the default shortcode:<code>[stock-ticker]</code></p>';
	echo do_shortcode('[stock-ticker]');
	echo '				<p>
							Note: To preview your settings, you must save changes.
						</p>
						</div>
					</div>
				</div>
			</div>
		';
	echo '<div class="postbox-container ticker-options" style="display:block; clear:both; width:750px;">
			<div id="normal-sortables" class="meta-box-sortables ui-sortable">
				<div id="referrers" class="postbox">
					<h3 class="hndle"><span>Advanced</span></h3>
					<div class="inside">
						<p>If you want to run a custom style or run additional tickers aside from the default [stock-ticker], you can specify the style parameters in the shortcode. See the example below:</p>
	
						<input type="text" onclick="this.select();" readonly="readonly" value="[stock-ticker id=&quot;example_id_01&quot; display=&quot;3&quot; width=&quot;700&quot; height=&quot;40&quot; background_color=&quot;black&quot; text_color=&quot;yellow&quot; scroll_speed=&quot;60&quot;]" class="shortcode-in-list-table wp-ui-text-highlight code" style="width: 100%; font-size: smaller;">
						<br><br>';
	echo do_shortcode('[stock-ticker id="example_id_01" display="3" width="700" height="40" background_color="black" text_color="yellow" scroll_speed="60"]');

	echo 			'<p>Note: In order to display tickers with different settings on the same page, one <b>must</b> assign a unique id in the shortcode for each ticker.</p>
					</div>
				</div>
			</div>
		</div>';
}

//Creates the entire options page. Useful for formatting.
function stock_ticker_create_display_options(){
	echo '<form action="" method="POST">';
	echo '<div class="postbox-container ticker-options" style="width: 50%; margin-right: 10px; clear:left;">
				<div id="normal-sortables" class="meta-box-sortables ui-sortable">
					<div id="referrers" class="postbox">
						<h3 class="hndle">Display Settings</h3>
						<div class="inside">';
							stock_ticker_create_default_settings_field();
		echo '				<p>All options below are <b>optional</b>.<br>All are reset by choosing a styles above.</p>
							<div class="ticker-options-subsection">
								<h4>Ticker Settings</h4><br>';
								stock_ticker_create_size_field();
								stock_ticker_create_max_display_field();
		echo'					<br>';
								stock_ticker_create_background_color_field();
		echo '				</div>';
		echo '				<div class="ticker-options-subsection">
								<h4>Text Settings</h4><br>';
								stock_ticker_create_font_field();
		echo '				</div>';
		echo '				<div class="ticker-options-subsection">
								<h4>Ticker Features</h4><br>';
								stock_ticker_create_draw_lines_field();


		echo '				</div>';
		echo '				<div class="ticker-options-subsection">
								<h4>Advanced Styling</h4>
								<div class="ticker_admin_toggle">+</div>
								<div class="ticker-options-display">';
									stock_ticker_create_style_field();
		echo '					</div>
							</div>';
		echo '			</div>
					</div>
				</div>
				<input type="submit" style="margin-bottom:20px;" name="save_changes" value="Save Changes" class="button-primary"/>
			</div>';
		
		echo '	<div class="postbox-container ticker-options" style="width: 45%; clear:right;">
					<div id="normal-sortables" class="meta-box-sortables ui-sortable">
						<div id="referrers" class="postbox">
							<h3 class="hndle"><span>Stocks</span></h3>
							<div class="inside">
								<p>Type in your stocks as a comma-separated list.<br> 
								Example: <code>GOOG,YHOO,AAPL</code>.</p>
								<p>
									When a page loads with a ticker, the stocks list of the category of that page is loaded. 
									If that category has no stocks associated with it, the default list is loaded.
								</p>
								<p>For Nasdaq, use <code>^IXIC</code>. For S&amp;P500, use <code>^GSPC</code>. Unfortunately, DOW is currently not available.</p>
								';
								stock_ticker_create_category_stock_list();
		echo '				</div>
						</div>
					</div>
				</div>';
	echo '</form>';
	return;
}

function stock_ticker_update_display_options(){
		stock_ticker_update_category_stock_list();
		stock_ticker_update_draw_lines_field();
		stock_ticker_update_max_display_field();
		stock_ticker_update_color_field();
		stock_ticker_update_size_font_field();
		stock_ticker_update_default_settings_field();
		stock_ticker_update_style_field();
		return;
}

//Generates the html for the listbox of markets
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


//Generates the html for the list of stocks in each category
function stock_ticker_create_category_stock_list(){
	$category_stock_list=get_option('stock_ticker_category_stock_list');
	$category_ids = get_all_category_ids();
	array_unshift ($category_ids,-1);
	$no_cat=false;
	foreach($category_ids as $id){
		if($id==-1){
			$cat_id='Default';
			$name='';
		}else{
			$name = get_cat_name($id);
			$cat_id=preg_replace('/\s+/', '', $name);
			if($cat_id=='Uncategorized'){
				continue;
			}
		}

		$stock_list=$category_stock_list[$cat_id];
		$stocks_string="";
		//if the list of this category is not empty, built the list of stocks for the output
		if(!empty($stock_list)){		
			//Append each stock to the output string. Check to see if the stock is in the default market
			//(in which case do not output the market.)	
				
			$stocks_string=implode(',',$stock_list);
		}elseif($cat_id=='Default'){
			echo '<h4>Warning: Leaving this field blank may cause some tickers to not show up.</h4>';

		}

?>
		
		<?php echo $name; ?>
		<br>
		<input style="width:100%; font-size:14px" type="text" name="<?php echo $cat_id; ?>_stocks" id="<?php echo $cat_id; ?>_stock_list" value="<?php echo $stocks_string; ?>"/>
		
			
	
	<?php
		if($cat_id=='Default'){
			echo '	<h4 style="display:inline-block;">Customize Categories</h4>
					<div id="ticker_category_toggle" class="ticker_admin_toggle">+</div>
					
						<div class="ticker-options-display">';
			//if default is the only category, this variable will become true, indicating that the
			//site does not have any categories. Used to display a little message
			$nocat=true;
		}else{
			$nocat=false;
		}
	}
	if($nocat){
		echo '<p> Your site does not appear to have any categories to display.</p>';
	}
	echo '</div>';
}

function stock_ticker_update_category_stock_list(){

	$category_stock_list=array();
	$category_ids = get_all_category_ids();
	array_unshift ($category_ids,-1);
	$all_bad_stock_list=array();
	foreach($category_ids as $id){
		if($id==-1){
			$cat_id='Default';
		}else{
			$name = get_cat_name($id);
			$cat_id=preg_replace('/\s+/', '', $name);
			if($cat_id=='Uncategorized'){
				continue;
			}
		}
		$input = strtoupper ($_POST[$cat_id."_stocks"]);
		$input=preg_replace('/\s+/', '', $input);
		$input_list = explode(",", $input);
		if(empty($input_list)){
			$category_stock_list[$cat_id]=array();
			continue;
		}
		$input_list=array_unique($input_list);
		//runs the caching function on the given stocks list to see if any of the stocks were invalid.
		$cache_output=stock_ticker_get_data($input_list);
		$bad_stock_list=$cache_output['invalid_stocks'];
		if(!empty($bad_stock_list)){
			//get the difference of the two arrays, filter the empty values, and condense the array
			$stock_list_difference=array_diff($input_list, $bad_stock_list);
			$validated_stock_list=array_values($stock_list_difference);
			$all_bad_stock_list=array_merge($bad_stock_list, $all_bad_stock_list);
		}else{
			$validated_stock_list=array_filter($input_list);
		}
		$category_stock_list[$cat_id]=$validated_stock_list;

	}
	if(!empty($all_bad_stock_list)){
		?>
			<p style="font-size:14px;font-weight:bold;">
				The following stocks were not found: 
				<?php
					echo implode(', ',$all_bad_stock_list);
				?>.
			</p>
		<?php
	}
	update_option('stock_ticker_category_stock_list', $category_stock_list);	
}

function stock_ticker_create_max_display_field(){
	?>
		<label for="max_display">Number of stocks displayed at one time: </label>
		<input style="width:29px; font-size:14px; text-align:center" type="text" name="max_display" id="max_display" value="<?php echo get_option('stock_ticker_display_number'); ?>"/>
		<label for="scroll_speed">Scroll speed (Pixels per second): </label>
		<input style="width:36px; font-size:14px; text-align:center" type="text" name="scroll_speed" id="scroll_speed" value="<?php echo get_option('stock_ticker_scroll_speed'); ?>"/>

	<?php
}

function stock_ticker_update_max_display_field(){
	$new_max=intval($_POST['max_display']);
	if($new_max>20){
		$new_max=20;
	}
	$new_scroll_speed=$_POST['scroll_speed'];
	if($new_scroll_speed>150){
		$new_scroll_speed=150;
	}elseif ($new_scroll_speed<1) {
		$new_scroll_speed=1;
	}
	if($new_max){
		update_option('stock_ticker_display_number', $new_max);
	}	
	if(is_numeric($new_scroll_speed)){
		update_option('stock_ticker_scroll_speed', $new_scroll_speed);
	}
}

function stock_ticker_create_display_type_field(){
	$all_types=get_option('stock_ticker_all_display_types');
	$current_type=get_option('stock_ticker_display_type');
	?>
		<label for="display_type">Type of display: </label>
		<select name="display_type" id="display_type">
			<option selected>
		<?php 
			echo $current_type;
			foreach($all_types as $type){
				if($type==$current_type){
					continue;
				}
				echo "<option>".$type;
			}
		?>
		</select>
	<?php
}

function stock_ticker_update_display_type_field(){
	update_option('stock_ticker_display_type',$_POST['display_type']);
}

function stock_ticker_create_background_color_field(){
	$color_sets=get_option('stock_ticker_default_color_scheme');
	$current_colors=get_option('stock_ticker_color_scheme');
	$opacity_set=get_option('stock_ticker_opacity');
	?>
	Background color:
	<input style="width:70px" name="background_color1" type="text" value="<?php echo $current_colors[1]; ?>"/>
	<br>
	<label for="background_opacity">Background opacity (0-1): </label>
	<input style="width:29px; font-size:14px; text-align:center" type="text" name="background_opacity" id="background_opacity" value="<?php echo $opacity_set[1]; ?>"/>


<?php

}

function stock_ticker_update_color_field(){
	$new_colors=array($_POST['text_color'],$_POST['background_color1']);
	$default_colors=get_option('stock_ticker_color_scheme');
	if($new_colors[0]==""){
		$new_colors[0]=$default_colors[0];
	}
	if($new_colors[1]==""){
		$new_colors[1]=$default_colors[1];
	}

	update_option('stock_ticker_color_scheme', $new_colors);

	$new_opacity=array($_POST['text_opacity'],$_POST['background_opacity']);
	$default_opacity=get_option('stock_ticker_opacity');
	if($new_opacity[0]==""){
		$new_opacity[0]=$default_opacity[0];
	}
	if($new_opacity[1]==""){
		$new_opacity[1]=$default_opacity[1];
	}

	update_option('stock_ticker_opacity', $new_opacity);
}

function stock_ticker_create_size_field(){
	$size=get_option('stock_ticker_display_size');

	?>
		<label for="stock_tickerwidth">Width: </label>
		<input style="width:60px; font-size:14px" type="text" name="width" id="stock_tickerwidth" value="<?php echo $size[0]; ?>"/>
		<label for="stock_ticker_height">Height: </label>
		<input style="width:60px; font-size:14px" type="text" name="height" id="stock_ticker_height" value="<?php echo $size[1]; ?>"/>
	<?php

}

function stock_ticker_create_font_field(){
	$font_options=get_option('stock_ticker_font_options');
	$default_fonts=get_option('stock_ticker_default_fonts');
	$current_colors=get_option('stock_ticker_color_scheme');
	$opacity_set=get_option('stock_ticker_opacity');
	?>
		<label for="text_color">Text color: </label>
		<input style="width:70px" name="text_color" id="text_color" type="text" value="<?php echo $current_colors[0]; ?>"/>
		<label for="font_size">Font size: </label>
		<input style="width:29px;  font-size:14px; text-align:center;" type="text" name="font_size" id="font_size" value="<?php echo $font_options[0]; ?>"/>
		<br>
		<label for="text_opacity">Text opacity (0-1): </label>
		<input style="width:29px; font-size:14px; text-align:center" type="text" name="text_opacity" id="text_opacity" value="<?php echo $opacity_set[0]; ?>"/>
		<label for="font_family">Font family: </label>
		<input style="width:70px" name="font_family" id="font_family" list="font_family" autocomplete="on"/>
		<datalist id="font_family">
		<?php
			foreach($default_fonts as $font){
				echo '<option value="'.$font.'">';
			}
		?>
		</datalist>
	<?php
}

function stock_ticker_update_size_font_field(){
	$display_size=get_option('stock_ticker_display_size');
	$font_options=get_option('stock_ticker_font_options');
	$old_data=array($display_size[0],$display_size[1],$font_options[0],$font_options[1]);
	$new_data=array($_POST['width'],$_POST['height'],$_POST['font_size'],$_POST['font_family']);
	//escapes if the data is invalid.
	if(!is_numeric($new_data[0])){
		return;
	}
	if(!is_numeric($new_data[1])){
		return;
	}
	if(!is_numeric($new_data[2])){
		return;
	}
	for($i=0;$i<count($new_data);$i++){
		if($new_data[$i]==""){
			$new_data[$i]=$old_data[$i];
		}
	}
	if($new_data[2]>32||$new_data[2]<1){
		$new_data[2]=14;
	}
	update_option('stock_ticker_display_size', array($new_data[0],$new_data[1]));
	update_option('stock_ticker_font_options', array($new_data[2],$new_data[3]));
}

//generates all of the checkboxes in the admin page
function stock_ticker_create_draw_lines_field(){
	?>
	<input name="create_vertical_dash" type="checkbox" id="create_vertical_dash" <?php echo (get_option('stock_ticker_draw_vertical_lines') ? 'checked' : '')?>>
	<label for="create_vertical_dash">Draw vertical lines</label>	
	<br>

	<input name="create_triangle" type="checkbox" id="create_triangle" <?php echo (get_option('stock_ticker_draw_triangle') ? 'checked' : '');?>>
	<label for="create_triangle">Draw triangle</label>	
	<br>

	<input name="enable_change_color" type="checkbox" id="enable_change_color" <?php echo (get_option('stock_ticker_enable_change_color') ? 'checked' : '');?>>
	<label for="enable_change_color">Enable change color</label>	
<?php
}


function stock_ticker_update_draw_lines_field(){

        update_option('stock_ticker_draw_vertical_lines',$_POST['create_vertical_dash']);
        update_option('stock_ticker_draw_triangle',      $_POST['create_triangle']);
        update_option('stock_ticker_enable_change_color',$_POST['enable_change_color']);
}

function stock_ticker_create_default_settings_field(){

	$all_settings=get_option('stock_ticker_default_settings');
	?>
		<label for="default_settings">Template: </label>
		<select name="default_settings" id="default_settings"style="width:180px;">
		<option selected> ------- </option>
		<?php 
			foreach($all_settings as $key=>$setting){
				echo '<option value="'.$key.'">'.$setting['name'].'</option>';
			}
		?>
		</select>
	<?php
}
function stock_ticker_update_default_settings_field(){
	$selected_setting=$_POST['default_settings'];
	if($selected_setting=='-------'){
		return;
	}
	$all_settings=get_option('stock_ticker_default_settings');
	$selected_setting=$all_settings[$selected_setting];

	//update font style
	$option_holder=get_option('stock_ticker_font_options');
	$option_holder[1]=$selected_setting['font_family'];
	update_option('stock_ticker_font_options', $option_holder);

	//updates opacity settings
	$option_holder=get_option('stock_ticker_opacity');
	$option_holder[0]=$selected_setting['text_opacity'];
	$option_holder[1]=$selected_setting['background_opacity'];	
	update_option('stock_ticker_opacity', $option_holder);
	//update color scheme
	$option_holder=get_option('stock_ticker_color_scheme');
	$option_holder[0]=$selected_setting['font_color'];
	$option_holder[1]=$selected_setting['back_color'];
	update_option('stock_ticker_color_scheme',$option_holder);

	//update vertical lines, triangles, and change color
	update_option('stock_ticker_draw_vertical_lines', $selected_setting['verti_lines']);

	update_option('stock_ticker_draw_triangle', $selected_setting['draw_triangle']);

	update_option('stock_ticker_enable_change_color', $selected_setting['change_color']);

	update_option('stock_ticker_all_change_color', $selected_setting['all_change']);

}

function stock_ticker_create_style_field(){
	echo '
		<p>
			If you have additional CSS rules you want to apply to the
			entire ticker (such as alignment or borders) you can add them below.
		</p>
		<p>
			Example: <code>margin:auto; border:1px solid #000000;</code>
		</p>';
	$previous_setting=get_option('stock_ticker_advanced_style');
	echo 
	'<input style="width:90%; font-size:14px" type="text" 
	name="ticker_advanced_style" id="ticker_advanced_style" value="'.$previous_setting.'"/>';

}

function stock_ticker_update_style_field(){
	update_option('stock_ticker_advanced_style',$_POST['ticker_advanced_style']);
}

?>