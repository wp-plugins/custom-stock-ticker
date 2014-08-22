<?php
/*
 * Generates output table.
 */

function stock_ticker_scripts_init() {
	wp_register_style('stock_ticker_style', plugins_url('stock_ticker_style.css', __FILE__));
	wp_enqueue_style('stock_ticker_style');
	wp_register_script('stock_ticker_script', plugins_url('stock_ticker_script.js', __FILE__),array( 'jquery' ),false, false);
	wp_enqueue_script('stock_ticker_script');
}
add_action('init', 'stock_ticker_scripts_init');

$ticker_display_num=2;
function stock_ticker($atts){
	$size=get_option('stock_ticker_display_size');
	$color=get_option('stock_ticker_color_scheme');
	$font_options=get_option('stock_ticker_font_options');
	extract( shortcode_atts( array(
	'id'				=> '0',
	'width'				=> $size[0],
	'height'			=> $size[1],
	'background_color' 	=> $color[1],
	'text_color'		=> $color[0],
	'scroll_speed' 		=> get_option('stock_ticker_scroll_speed'),
	'display'			=> get_option('stock_ticker_display_number'),
	), $atts ) );
	global $ticker_display_num;
	$ticker_display_num=$display;
	$stock_categories=get_option('stock_ticker_category_stock_list');
	$category=get_the_category();
	$category_name=$category[0]->name;
	$category_name=str_replace(' ','',$category_name);
	//grab the stock list of the current category. if it doesnt exist, use the default list. 
	$stock_list=(!empty($stock_categories[$category_name]) ? $stock_categories[$category_name] : $stock_categories['Default']);
	//Grabs the default list if a category's stock list is empty
	$data_list=stock_ticker_get_data($stock_list);
	$data_list=$data_list['valid_stocks'];
//	$test_list=array('^IXIC','GOOG','AAPL','YHOO');
//	var_dump(stock_info_get_data($test_list));
	$output=stock_ticker_create_css_header($data_list, $width, $height, $text_color, $id, $background_color);
	$output.=stock_ticker_create_ticker($data_list, $width, $height, $text_color, $id);
	$element_width=stock_ticker_get_element_width($data_list, $width);
	$output.=stock_ticker_create_jquery($id, $element_width, $width, $scroll_speed);
	return $output;
}

function stock_ticker_create_jquery($id, $element_width, $width, $scroll_speed){
    $output.= '
        <script type="text/javascript">	
	        jQuery(document).ready(function(){
	        	var ticker_config = {
					id: "'.$id.'",
					scroll_speed: '.$scroll_speed.',
				}
				stock_ticker_start_js(ticker_config);
			});
        </script>';

	return $output;
}
function stock_ticker_get_element_width($data_list, $width){
	global $ticker_display_num;
	$counter=count($data_list);
	if($counter>$ticker_display_num){
		$counter=$ticker_display_num;
	}elseif($counter>1){
		$counter=$counter-1;
	}
	return $width/$counter;
}
//Creates the internal style sheet for all of the various elements.
//	$ticker_data_css='position:absolute; opacity:'.$text_opacity.'; font-size:'.$font_options[0].
//	'px; font-family:'.$font_options[1].',serif; width:'.$entry_width.'px; height: 100%;text-align:center;line-height:'.$element_height.'px;';
function stock_ticker_create_css_header($data_list, $width, $height, $text_color, $id, $background_color){
	$font_options=get_option('stock_ticker_font_options');
	$opacity_options=get_option('stock_ticker_opacity');
	$text_opacity=$opacity_options[0];
	$back_opacity=$opacity_options[1];
	$element_width=stock_ticker_get_element_width($data_list, $width);
	$display_data=get_option('stock_ticker_data_display');
	$number_of_values=array_sum($display_data);
	if(get_option('stock_ticker_draw_triangle')){
		$entry_width=($element_width-20)/$number_of_values;
	}else{
		$entry_width=$element_width/$number_of_values;
	}
	$output='<style>';
	$output.='.stock_ticker_'.$id.'{
		width:'.$width.'px;
		height: '.$height.'px;
		'.get_option('stock_ticker_advanced_style').'
	}';
	$output.='.stock_ticker_'.$id.' .stock_ticker_background {
		opacity:'.$back_opacity.';
		width:'.$width.'px;
		height: '.$height.'px;
		
		background-color: '.$background_color.';
	}';

	$output.='.stock_ticker_'.$id.' .stock_ticker_slider {
		height: '.$height.'px;
		width: '.$width.'px;
	}';
	$output.='.stock_ticker_'.$id.' .stock_ticker_entry {
		position: absolute;
		height: '.$height.'px;
		width: '.$element_width.'px;
	}';
	$output.='.stock_ticker_'.$id.' .stock_ticker_element {
		position: absolute;
		opacity:'.$text_opacity.';
		font-size:'.$font_options[0].'px;
		font-family:'.$font_options[1].',serif;
		width:'.$entry_width.'px;
		height: '.$height.'px;
		text-align:center;
		line-height:'.$height.'px;
	}';
	$triangle_size=$font_options[0]-4;
	$triangle_left_position=$element_width-10-($triangle_size);
	$triangle_top_position=$height/2-$triangle_size/2;
	$output.='.stock_ticker_'.$id.' .stock_ticker_triangle {
		left:'.$triangle_left_position.'px;
		top:'.$triangle_top_position.'px;
	}';
	
	$output.='.stock_ticker_'.$id.' .stock_ticker_triangle_red {
		border-left: '.$triangle_size.'px solid transparent;
		border-top: '.$triangle_size.'px solid red;
		border-right: '.$triangle_size.'px solid transparent;
	}';
	$output.='.stock_ticker_'.$id.' .stock_ticker_triangle_green {
		border-left: '.$triangle_size.'px solid transparent;
		border-right: '.$triangle_size.'px solid transparent;
		border-bottom: '.$triangle_size.'px solid green;
	}';
	$element_height=$height*.7;
	$top=($height-$element_height)/2;
	$output.='.stock_ticker_'.$id.' .stock_ticker_vertical_line {
		height:'.$element_height.'px;
		top:'.$top.'px;
	}';
	$output.='</style>';
	return $output;
}

/*
* Creates the ticker
*/

function stock_ticker_create_ticker($data_list, $width, $height, $text_color, $id){
	$back_opacity=get_option('stock_ticker_opacity');
	$back_opacity=$back_opacity[1];
	$output='<div class="stock_ticker stock_ticker_'.$id.'">';
	$output.='<div class="stock_ticker_background">';
	$output.='</div>';
	$element_width=stock_ticker_get_element_width($data_list, $width);


	$output.='<div class="stock_ticker_slider">';
	$left_position=0;
	foreach($data_list as $stock_data){
		if($stock_data['last_val']=="0.00"){
			continue;
		}
		$output.=stock_ticker_create_entry($stock_data, $text_color, $element_width, $height, $width, $left_position);
		$left_position+=$element_width;
	}	
	$output.='</div>';
	$output.='</div>';
	return $output;
}
/* background-color: '.$background_color.';border:1px solid '. $border_color.';
 * Builds a single row with the given data.
 */
function stock_ticker_create_entry($stock_data, $text_color, $element_width, $element_height, $ticker_width, $left_pos){
	$output="";
	$display_data=get_option('stock_ticker_data_display');
	$number_of_values=array_sum($display_data);
	$left_position=0;
	//sets the width of each data element. Leaves 12 pixels of space for the triangle
	if(get_option('stock_ticker_draw_triangle')){
		$entry_width=($element_width-20)/$number_of_values;
	}else{
		$entry_width=$element_width/$number_of_values;
	}

	$output.= '<div class="stock_ticker_entry" style="left: '.$left_pos.'px;">';
	$options_array=get_option('stock_ticker_display_option_strings');
	$enable_color_change=get_option('stock_ticker_enable_change_color');
	$triangle_color='';
	$color_settings=get_option('stock_ticker_color_scheme');
	if(get_option('stock_ticker_all_change_color')&& $text_color==$color_settings[0]){
		if($stock_data['change_val']>0){
				$text_color="green";	
			}elseif($stock_data['change_val']<0){
				$text_color="red";
			}else{
				$text_color="grey";
			}
	}
	//index 0 represents market -- unused
	//index 1 represent stock symbol
	if($display_data[1]==1){
		$data_item=$stock_data['stock_sym'];
		if($data_item=="^GSPC"){
			$data_item='S&P500';
		}elseif($data_item=="^IXIC"){
			$data_item="NASDAQ";
		}
		$output.= 		'<div class="stock_ticker_element" style="left:'.$left_position.'px; color: '.$text_color.';">';
		$output.=			$data_item;
		$output.=  		'</div>';

		$left_position+=$entry_width;
	}
	//index 2 represents the last value of the stock
	if($display_data[2]==1){
		$data_item=$stock_data['last_val'];

		$data_item=round($data_item, 2);
		$output.= 		'<div class="stock_ticker_element" style="left:'.$left_position.'px; color: '.$text_color.';">';
		$output.= 				$data_item;
		$output.=  		'</div>';		

		$left_position+=$entry_width;
	}
	//index 3 represents the value of the change
	if($display_data[3]==1){

		$data_item=$stock_data['change_val'];
		if($data_item>0){
			$data_item=round($data_item, 2);
			$data_item="+".$data_item;
			$triangle_color="green";
				
		}elseif($data_item<0){
			$triangle_color="red";
			$data_item=round($data_item, 2);
		}else{
			$triangle_color="grey";
			$data_item=round($data_item, 2);
			$data_item="+".$data_item.".00";
		}
		if($enable_color_change){
			$change_color=$triangle_color;
		}else{
			$change_color=$text_color;
		}
		$output.= 		'<div class="stock_ticker_element" style="left:'.$left_position.'px;color: '.$change_color.';">';
		$output.= 				$data_item;
		$output.=  		'</div>';

		$left_position+=$entry_width;
	}
	//index 4 represents the change percent.
	if($display_data[4]==1){
		$data_item=$stock_data['change_percent'];

		if($data_item>0){
			$data_item=round($data_item, 2);
			$data_item="+".$data_item.'%';
		
		}elseif($data_item<0){
			$data_item=round($data_item, 2);
			$data_item=$data_item.'%';
		}else{
			$data_item=round($data_item, 2);
			$data_item="+".$data_item.".00%";
		}
		$output.= 		'<div class="stock_ticker_element" style="left:'.$left_position.'px; color: '.$change_color.';">';		
		$output.= 				$data_item;
		$output.=  		'</div>';

		$left_position+=$entry_width;
	}
	//creates the colorful triangle
	if(get_option('stock_ticker_draw_triangle')){
		$font_options=get_option('stock_ticker_font_options');
		$output.=stock_ticker_create_triangle($triangle_color, $font_options[0]-4, $element_width, $element_height);
	}
	//creates the line after each entry.
	if(get_option('stock_ticker_draw_vertical_lines')){
		$output.=stock_ticker_draw_vertical_line($element_height, $element_width);

	}
	$output.=  '</div>';
	return $output;

}

//Creates a triangle who's color is the passed string.
function stock_ticker_create_triangle($triangle_color, $triangle_size, $element_width, $element_height){
	if($triangle_color=='red'){
		$triangle_class='stock_ticker_triangle_red';
	}else{
		$triangle_class='stock_ticker_triangle_green';
	}
	$output='<div class="stock_ticker_triangle '.$triangle_class.'"></div>';
	return $output;
}

// this can be done with css border-left/border-right
function stock_ticker_draw_vertical_line($element_height, $element_width){
	$output='<div class="stock_ticker_vertical_line" style="left:'.($element_width-1).'px;"></div>';
	return $output;
}

?>
