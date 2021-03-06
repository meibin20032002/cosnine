<?php
/**
 * @package	HikaShop for Joomla!
 * @version	3.3.0
 * @author	hikashop.com
 * @copyright	(C) 2010-2018 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?>
<div class="row">

	<div id="hikashop_product_left_part" class="hikashop_product_left_part col-md-6">
<?php if(!empty($this->element->extraData->leftBegin)) { echo implode("\r\n",$this->element->extraData->leftBegin); } ?>
<?php
	$this->row =& $this->element;
	$this->setLayout('show_block_img');
	echo $this->loadTemplate();
?>
<?php if(!empty($this->element->extraData->leftEnd)) { echo implode("\r\n",$this->element->extraData->leftEnd); } ?>
	</div>

	<div id="hikashop_product_right_part" class="hikashop_product_right_part col-md-6">
        <div id="hikashop_product_top_part" class="hikashop_product_top_part">
        <?php if(!empty($this->element->extraData->topBegin)) { echo implode("\r\n",$this->element->extraData->topBegin); } ?>
        	<h1>
        		<span id="hikashop_product_name_main" class="hikashop_product_name_main" itemprop="name"><?php
        			if(hikashop_getCID('product_id') != $this->element->product_id && isset($this->element->main->product_name))
        				echo $this->element->main->product_name;
        			else
        				echo $this->element->product_name;
        		?></span>
        <?php if ($this->config->get('show_code')) { ?>
        		<span id="hikashop_product_code_main" class="hikashop_product_code_main" itemprop="sku"><?php
        			echo $this->element->product_code;
        		?></span>
        <?php } ?>
        	</h1>
        <?php if(!empty($this->element->extraData->topEnd)) { echo implode("\r\n", $this->element->extraData->topEnd); } ?>
        
        <?php
        	$this->setLayout('show_block_social');
        	echo $this->loadTemplate();
        ?>
        </div>

<?php if(!empty($this->element->extraData->rightBegin)) { echo implode("\r\n",$this->element->extraData->rightBegin); } ?>

		<div id="hikashop_product_vote_mini" class="hikashop_product_vote_mini"><?php
	if($this->params->get('show_vote_product')) {
		$js = '';
		$this->params->set('vote_type', 'product');
		$this->params->set('vote_ref_id', isset($this->element->main) ? (int)$this->element->main->product_id : (int)$this->element->product_id );
		echo hikashop_getLayout('vote', 'mini', $this->params, $js);
	}
		?></div>
		<span id="hikashop_product_price_main" class="hikashop_product_price_main" itemprop="offers" itemscope itemtype="http://schema.org/Offer">
<?php
	$main =& $this->element;
	if(!empty($this->element->main))
		$main =& $this->element->main;
	if(!empty($main->product_condition)){
?>
			<meta itemprop="itemCondition" itemtype="http://schema.org/OfferItemCondition" content="http://schema.org/<?php echo $main->product_condition; ?>" />
<?php
	}
	if($this->params->get('show_price') && (empty($this->displayVariants['prices']) || $this->params->get('characteristic_display') != 'list')) {
		$this->row =& $this->element;
		$this->setLayout('listing_price');
		echo $this->loadTemplate();
?>
			<meta itemprop="availability" content="http://schema.org/<?php echo ($this->row->product_quantity != 0) ? 'InStock' : 'OutOfstock' ;?>" />
			<meta itemprop="priceCurrency" content="<?php echo $this->currency->currency_code; ?>" />
<?php
	}
?>
		</span><br />

<?php if(!empty($this->element->extraData->rightMiddle)) { echo implode("\r\n",$this->element->extraData->rightMiddle); } ?>

<?php
	$this->setLayout('show_block_dimensions');
	echo $this->loadTemplate();
?>
<?php
	if($this->params->get('characteristic_display') != 'list') {
		$this->setLayout('show_block_characteristic');
		echo $this->loadTemplate();
?>
<?php } ?>

<?php
	$form = ',0';
	if(!$this->config->get('ajax_add_to_cart', 1)) {
		$form = ',\'hikashop_product_form\'';
	}

	if(hikashop_level(1) && !empty ($this->element->options)) {
?>
		<div id="hikashop_product_options" class="hikashop_product_options"><?php
			$this->setLayout('option');
			echo $this->loadTemplate();
		?></div>
<?php
		$form = ',\'hikashop_product_form\'';
		if($this->config->get('redirect_url_after_add_cart', 'stay_if_cart') == 'ask_user') {
?>
		<input type="hidden" name="popup" value="1"/>
<?php
		}
	}

	if(!$this->params->get('catalogue') && ($this->config->get('display_add_to_cart_for_free_products') || ($this->config->get('display_add_to_wishlist_for_free_products', 1) && hikashop_level(1) && $this->params->get('add_to_wishlist') && $this->config->get('enable_wishlist', 1)) || !empty($this->element->prices))) {
		if(!empty($this->itemFields)) {
			$form = ',\'hikashop_product_form\'';

			if ($this->config->get('redirect_url_after_add_cart', 'stay_if_cart') == 'ask_user') {
?>
		<input type="hidden" name="popup" value="1"/>
<?php
			}

			$this->setLayout('show_block_custom_item');
			echo $this->loadTemplate();
		}
	}

	$this->formName = $form;
	if($this->params->get('show_price')) {
?>
		<span id="hikashop_product_price_with_options_main" class="hikashop_product_price_with_options_main">
		</span>
<?php
	}

	if(empty($this->element->characteristics) || $this->params->get('characteristic_display') != 'list') {
?>
		<div id="hikashop_product_quantity_main" class="hikashop_product_quantity_main"><?php
			$this->row =& $this->element;
			$this->ajax = 'if(hikashopCheckChangeForm(\'item\',\'hikashop_product_form\')){ return hikashopModifyQuantity(\'' . (int)$this->element->product_id . '\',field,1' . $form . ',\'cart\'); } else { return false; }';
			$this->setLayout('quantity');
			echo $this->loadTemplate();
		?></div>
<?php
	}
?>

		<div id="hikashop_product_contact_main" class="hikashop_product_contact_main"><?php
	$contact = (int)$this->config->get('product_contact', 0);
	if(hikashop_level(1) && ($contact == 2 || ($contact == 1 && !empty($this->element->product_contact)))) {
		$css_button = $this->config->get('css_button', 'hikabtn');
?>
			<a href="<?php echo hikashop_completeLink('product&task=contact&cid=' . (int)$this->element->product_id . $this->url_itemid); ?>" class="<?php echo $css_button; ?>"><?php
				echo JText::_('CONTACT_US_FOR_INFO');
			?></a>
<?php
	}
?>
		</div>

<?php
	if(!empty($this->fields)) {
		$this->setLayout('show_block_custom_main');
		echo $this->loadTemplate();
	}

	if(HIKASHOP_J30) {
		$this->setLayout('show_block_tags');
		echo $this->loadTemplate();
	}
?>
	<span id="hikashop_product_id_main" class="hikashop_product_id_main">
		<input type="hidden" name="product_id" value="<?php echo (int)$this->element->product_id; ?>" />
	</span>

<?php if(!empty($this->element->extraData->rightEnd)) { echo implode("\r\n",$this->element->extraData->rightEnd); } ?>

    <div class="productSupport">
		<h5>
			HOTLINE TƯ VẤN:
		</h5>
		<a href="tel:02837059559">02837059559 </a> 
        <a href="tel:0837079551">- 0837079551 </a>
        <a href="tel:0907124647">- 0907124647 </a>
	</div>
    <div class="pd_policy">
		<ul>
			<li><i class="fa fa-truck" aria-hidden="true"></i> <span>Giao hàng trong 2 - 5 ngày</span></li>
			<li><i class="fa fa-money" aria-hidden="true"></i> <span>Miễn phí giao hàng cho đơn hàng từ 300.000đ</span></li>
			<li><i class="fa fa-refresh" aria-hidden="true"></i> <span>Đổi trả theo chính sách linh hoạt</span></li>
		</ul>
	</div>

</div>
</div>

