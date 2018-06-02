<?php
/**
 * @package	HikaShop for Joomla!
 * @version	3.3.0
 * @author	hikashop.com
 * @copyright	(C) 2010-2018 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><table class="hikashop_product_options_table">
<?php
	$old_show_discount = $this->params->get('show_discount');
	$old_per_unit = $this->params->get('per_unit',1);
	$this->params->set('show_discount',0);
	$this->params->set('per_unit',0);
	$this->params->set('from_module','1');
	$i=0;
	$js="var hikashop_options=Array();";

	foreach($this->element->options as $optionElement) {
?>
	<tr>
<?php
		$this->values = array();
		$value = 0;
		$currency = hikashop_get('class.currency');
		$map = 'hikashop_product_option['.$i.']';
		$id = 'hikashop_product_option_'.$i;
		if(!empty($optionElement->variants)){
			$optionInfo =& $optionElement->main;
		}else{
			$optionInfo =& $optionElement;
		}
		$selectionMethod = $this->config->get('product_selection_method', 'generic');
		if($selectionMethod == 'per_product' && !empty($optionInfo->product_option_method)) {
			$selectionMethod = $optionInfo->product_option_method;
		}
		if(!in_array($selectionMethod, array('generic', 'radio', 'check')))
			$selectionMethod = 'generic';
		if(empty($optionElement->variants)){
			if(!$optionElement->product_published || (!$this->config->get('show_out_of_stock',1) && $optionElement->product_quantity==0)) continue;
			if($selectionMethod != 'check')
				$this->values[] = JHTML::_('select.option', 0,JText::_('HIKASHOP_NO'));
			$text = JText::_('HIKASHOP_YES');
			$this->row =& $optionElement;
			if(!empty($optionElement->prices) && $this->params->get('show_price')){
				$ok = null;
				$positive=1;
				foreach($optionElement->prices as $k => $price){
					if(empty($price->price_min_quantity)){
						$ok = $price;
						if($price->price_value<0) $positive=false;
						break;
					}
				}
				if($this->params->get('price_with_tax')){
					$price = $ok->price_value_with_tax;
				}else{
					$price = $ok->price_value;
				}
				$text.=' ( '.($positive?'+ ':'').$this->currencyHelper->format($price,$ok->price_currency_id).' )';


				$js.="
				hikashop_options[".(int)$optionElement->product_id."]=".(float)str_replace(',','.',$price).";";
			}
			$this->values[] = JHTML::_('select.option', $optionElement->product_id,$text);
		}else{
			if($this->config->get('add_no_to_options', 0) && $selectionMethod != 'check') {
				$this->values[] = JHTML::_('select.option', 0,JText::_('HIKASHOP_NO'));
			}
			if($this->config->get('select_option_default_value', 1) && $selectionMethod != 'check') {
				$defaultValue=array();
				if(isset($optionElement->characteristics) && is_array($optionElement->characteristics) && count($optionElement->characteristics)){
					foreach($optionElement->characteristics as $char){
							$defaultValue[]=$char->characteristic_id;
					}
				}
			}

			foreach($optionElement->variants as $variant){
				if(!$variant->product_published || (!$this->config->get('show_out_of_stock',1) && empty($variant->product_quantity))) continue;
				if($variant->product_sale_start>time()) continue;
				if($variant->product_sale_end!='' && $variant->product_sale_end!='0' && $variant->product_sale_end<time()) continue;
				if(empty($variant->variant_name)){
					if(empty($variant->characteristics_text)){
						$text = $variant->product_name;
					}else{
						$text = $variant->characteristics_text;
					}
				}else{
					$text = $variant->variant_name;
				}
				$this->row =& $variant;

				if(!empty($variant->prices) && $this->params->get('show_price')){
					$ok = null;
					$positive=1;
					foreach($variant->prices as $k => $price){
						if(empty($price->price_min_quantity)){
							$ok = $price;
							if($price->price_value<0) $positive=false;
							break;
						}
					}
					if($this->params->get('price_with_tax')){
						$price = $ok->price_value_with_tax;
					}else{
						$price = $ok->price_value;
					}
					$text.=' ( '.($positive?'+ ':'').$this->currencyHelper->format($price,$ok->price_currency_id).' )';

					$js.="
					hikashop_options[".(int)$variant->product_id."]=".(float)str_replace(',','.',$price).";";
				}

				if(!empty($defaultValue) && isset($variant->characteristics) && is_array($variant->characteristics) && count($variant->characteristics)){
					$default = true;
					foreach($variant->characteristics as $char){
						if(!in_array($char->characteristic_id,$defaultValue)){
							$default = false;
						}
					}
					if($default){
						$value = $variant->product_id;
					}
				}
				$this->values[] = JHTML::_('select.option', $variant->product_id,$text);
			}
		}
		if(!count($this->values)) continue;

		$select = ($selectionMethod == 'check') ? 'radio' : $selectionMethod;
		if($select == 'radio') {
			$map = 'hikashop_product_option[]';
		}
		$html = JHTML::_('select.'.$select.'list', $this->values, $map, 'class="inputbox" size="1" data-product-option="'.$i.'" onchange="hikashopChangeOption();"', 'value', 'text', (int)$value, $id);
		if($selectionMethod == 'check')
			$html = str_replace('type="radio"', 'type="checkbox"', $html);
		$options = '';
		if(!empty($optionInfo->product_description) || !empty($optionInfo->product_url)){
			$description = '';
			if(!empty($optionInfo->product_description)){
				$description = $this->escape(html_entity_decode(strip_tags(JHTML::_('content.prepare',$optionInfo->product_description)), ENT_NOQUOTES | ENT_HTML401, 'UTF-8'));
				$options='<span class="hikashop_option_info" title="'.$description.'" alt="Information"></span>';
			}
			if(!empty($optionInfo->product_url)){
				if(empty($description)){
					$description = $optionInfo->product_name;
				}
				$popup = hikashop_get('helper.popup');
				$options = $popup->display(
						$options,
						$optionInfo->product_name,
						$optionInfo->product_url,
						'hikashop_product option_'.$optionInfo->product_id.'_popup',
						760, 480, '', '', 'link'
					);
			}
		}
		$html = '<span class="hikashop_option_name">'.$optionInfo->product_name.$options.'</span></td><td>' . $html;
?>
		<td><?php
			echo $html;
		?></td>
	</tr>
<?php
		$i++;
	}

	global $Itemid;
	$url_itemid='';
	if(!empty($Itemid)){
		$url_itemid='&Itemid='.$Itemid;
	}
	$baseUrl = hikashop_completeLink('product&task=price',true,true);
	if(strpos($baseUrl,'?') !== false)
		$baseUrl .= '&';
	else
		$baseUrl .= '?';

	$js .= "
function hikashopChangeOption(){
	var j = 0;
	total_option_price = 0;
	var option_price = 0;
	while(true){
		var option = document.getElementById('hikashop_product_option_'+j);
		if(!option) {
			if(!document.querySelectorAll)
				break;

			option = document.querySelectorAll('\[data-product-option=\"'+j+'\"\]');

			if (option.length === 0) {
				break;
			}
			if (option.length > 0) {
				for(var i = 0; i < option.length; i++){
					if(option[i].checked === true){
						option_price = hikashop_options[option[i].value];
						if(option_price){
							total_option_price+=option_price;
						}
					}

				}
			}
			j++
			continue;
		}
		j++;
		option_price = hikashop_options[option.value];
		if(option_price){
			total_option_price+=option_price;
		}
	}

	var arr = new Array();
	arr = document.getElementsByName('hikashop_price_product');
	for(var i = 0; i < arr.length; i++){
		var obj = document.getElementsByName('hikashop_price_product').item(i);
		var id_price = 'hikashop_price_product_' + obj.value;
		var id_price_with_options = 'hikashop_price_product_with_options_' + obj.value;
		var price = document.getElementById(id_price);
		var price_with_options = document.getElementById(id_price_with_options);
		if(price && price_with_options){
			price_with_options.value = parseFloat(price.value) + total_option_price;
		}
	}
	hikashopRefreshOptionPrice();

	if(window.Oby && window.Oby.fireAjax) window.Oby.fireAjax('hkContentChanged');
}

function hikashopRefreshOptionPrice(){
	var price_div = document.getElementById('hikashop_product_id_main');
	var inputs = price_div.getElementsByTagName('input');
	if(inputs[0]){
		var id_price_with_options = 'hikashop_price_product_with_options_' + inputs[0].value;
		var price_with_options = document.getElementById(id_price_with_options);
		if(price_with_options){
			var target = document.getElementById('hikashop_product_price_with_options_main');
			if(target)
				window.Oby.xRequest('".$baseUrl."price='+price_with_options.value+'".$url_itemid."', { mode: 'GET', update: target });
		}

	}
}
window.hikashop.ready( function() { hikashopChangeOption(); });
";
	$doc = JFactory::getDocument();
	$doc->addScriptDeclaration("\n<!--\n".$js."\n//-->\n");
	$this->params->set('show_discount',$old_show_discount);
	$this->params->set('per_unit',$old_per_unit);
	$this->params->set('from_module','');
?>
</table>
