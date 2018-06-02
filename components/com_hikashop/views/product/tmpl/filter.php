<?php
/**
 * @package	HikaShop for Joomla!
 * @version	3.3.0
 * @author	hikashop.com
 * @copyright	(C) 2010-2018 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php
if(!empty($this->filters)){
	$count=0;
	$filterActivated=false;
	$widthPercent=(100/$this->maxColumn)-1;
	$widthPercent=round($widthPercent);
	static $i = 0;
	$i++;
	$filters = array();
	$url = '';
	if(!empty($this->params) && $this->params->get('module') == 'mod_hikashop_filter' && ($this->params->get('force_redirect',0) || (hikaInput::get()->getVar('force_using_filters', 0) !== 1 && empty($this->currentId) && (hikaInput::get()->getVar('option','')!='com_hikashop'|| !in_array(hikaInput::get()->getVar('ctrl','product'),array('product','category')) ||hikaInput::get()->getVar('task','listing')!='listing')))){
		$type = 'category';
		if(!HIKASHOP_J30){
			$menuClass = hikashop_get('class.menus');
			$menuData = $menuClass->get($this->params->get('itemid',0));
			if(@$menuData->hikashop_params['content_type']=='product')
				$type = 'product';
		}else{
			$app = JFactory::getApplication();
			$oldActiveMenu = $app->getMenu()->getActive();
			$app->getMenu()->setActive($this->params->get('itemid',0));
			$menuItem = $app->getMenu()->getActive();
			if (isset($oldActiveMenu) )
				$app->getMenu()->setActive($oldActiveMenu->id);
			$hkParams = false;
			$params = @$menuItem->params;
			if(!empty($params)) {
				$hkParams = $menuItem->params->get('hk_category',false);
				if($hkParams){
					$url = JRoute::_('index.php?option=com_hikashop&Itemid='.$this->params->get('itemid',0));
				}else{
					$type = 'product';
					$hkParams = $menuItem->params->get('hk_product',false);
					if($hkParams)
						$url = JRoute::_('index.php?option=com_hikashop&Itemid='.$this->params->get('itemid',0));
				}
			}
		}
		if(empty($url))
			$url = hikashop_completeLink($type.'&task=listing&Itemid='.$this->params->get('itemid',0));
	}else{
		$url = preg_replace('#&return_url=[^&]+#i','',hikashop_currentURL());
	}

	foreach($this->filters as $filter){
		if((empty($this->displayedFilters) || in_array($filter->filter_namekey,$this->displayedFilters)) && ($this->filterClass->cleanFilter($filter))){
			$filters[]=$filter;
		}
		$selected[]=$this->filterTypeClass->display($filter, '', $this);
	}

	foreach($selected as $data){
		if(!empty($data)) $filterActivated=true;
	}

	if(!$filterActivated && empty($this->rows) && $this->params->get('module') != 'mod_hikashop_filter') return;

	if(!count($filters)) return;

	$content_classes = 'hikashop_filter_main_div hikashop_filter_main_div_'.$this->params->get('main_div_name');
	$extra_attributes = '';

	if($this->collapsable){
		$content_classes .= ' hikashop_filter_collapsable_content';
		$title_classes = 'hikashop_filter_collapsable_title';
		$display_title_class = '_mobile';

		if($this->collapsable == 'always'){
			$display_title_class = '_always';
			$extra_attributes = 'style="display: none;"';
		}
?>
<div class="<?php echo $title_classes.$display_title_class; ?>">
	<div
		class="<?php echo $title_classes; ?>"
		onclick="if(window.hikashop.toggleOverlayBlock('hikashop_filter_main_div_<?php echo $this->params->get('main_div_name'); ?>', 'toggle')) return false;">
		<div class="<?php echo $title_classes.'_icon';?>"></div>
		<div class="hikashop_filter_fieldset">
			<h3><?php echo JText::_('FILTERS'); ?></h3>
		</div>
	</div>
</div>
<?php
	}
?>
<div id="hikashop_filter_main_div_<?php echo $this->params->get('main_div_name'); ?>" class="<?php echo $content_classes.$display_title_class; ?>" <?php echo $extra_attributes; ?>>
			<?php
		$datas = array();
		if(isset($this->listingQuery)){
			$html=array();
			$datas=$this->filterClass->getProductList($this, $filters);
		}

		foreach($filters as $key => $filter){
			$html[$key]=$this->filterClass->displayFilter($filter, $this->params->get('main_div_name'), $this, $datas);
		}

		if($this->displayFieldset){ ?>
	<div class="hikashop_filter_fieldset<?php echo $display_title_class ?>">
		<h3><?php echo JText::_('FILTERS'); ?></h3>
		<?php } ?>

		<form action="<?php echo $url; ?>" method="post" name="<?php echo 'hikashop_filter_form_' . $this->params->get('main_div_name'); ?>" enctype="multipart/form-data">
<?php
		while($count<$this->maxFilter+1){
			$height='';
			if(!empty($filters[$count]->filter_height)){
				$height='min-height:'.$filters[$count]->filter_height.'px;';
			}else if(!empty($this->heightConfig)){
				$height='min-height:'.$this->heightConfig.'px;';
			}
			if(!empty($html[$count])){
				if($filters[$count]->filter_options['column_width']>$this->maxColumn) $filters[$count]->filter_options['column_width'] = $this->maxColumn;
				 ?>
			<div class="hikashop_filter_main hikashop_filter_main_<?php echo $filters[$count]->filter_namekey; ?>" style="<?php echo $height; ?> float:left; width:<?php echo $widthPercent*$filters[$count]->filter_options['column_width']?>%;" >
				<?php echo '<div class="hikashop_filter_'.$filters[$count]->filter_namekey.'">'.$html[$count].'</div>'; ?>
			</div>
				<?php
			}
			$count++;
		}
		if($this->buttonPosition=='inside'){
			if($this->showButton ){
?>
			<div class="hikashop_filter_button_inside" style="float:left; margin-right:10px;">
				<input type="submit" id="hikashop_filter_button_<?php echo $this->params->get('main_div_name'); ?>" class="<?php echo $this->config->get('css_button', 'hikabtn'); ?>" onclick="document.getElementById('hikashop_filtered_<?php echo $this->params->get('main_div_name'); ?>').value='1';document.forms['hikashop_filter_form_<?php echo $this->params->get('main_div_name'); ?>'].submit(); return false;" value="<?php echo JText::_('FILTER'); ?>" />
			</div>
<?php
			}
			if($this->showResetButton ){
?>
			<div class="hikashop_reset_button_inside" style="float:left;">
				<a href="#" id="hikashop_reset_button_<?php echo $this->params->get('main_div_name'); ?>" class="<?php echo $this->config->get('css_button', 'hikabtn'); ?>" onclick="document.getElementById('hikashop_reseted_<?php echo $this->params->get('main_div_name'); ?>').value='1';document.forms['hikashop_filter_form_<?php echo $this->params->get('main_div_name'); ?>'].submit(); return false;"><?php echo JText::_('RESET'); ?></a>
			</div>
<?php
			}
		}
?>
			<input type="hidden" name="return_url" value="<?php echo $url;?>"/>
			<input type="hidden" name="filtered" id="hikashop_filtered_<?php echo $this->params->get('main_div_name');?>" value="1" />
			<input type="hidden" name="reseted" id="hikashop_reseted_<?php echo $this->params->get('main_div_name');?>" value="0" />
		</form>
<?php
		if($this->displayFieldset){
?>
	</div>
<?php
		}
		if($this->buttonPosition!='inside'){
			$style='style="margin-right:10px;"';
			if($this->buttonPosition=='right'){ $style='style="float:right; margin-left:10px;"'; }
			if($this->showButton){
?>
	<span class="hikashop_filter_button_outside" <?php echo $style; ?>>
		<input type="submit" id="hikashop_filter_button_<?php echo $this->params->get('main_div_name'); ?>" class="<?php echo $this->config->get('css_button', 'hikabtn'); ?>" onclick="document.getElementById('hikashop_filtered_<?php echo $this->params->get('main_div_name'); ?>').value='1';document.forms['hikashop_filter_form_<?php echo $this->params->get('main_div_name'); ?>'].submit(); return false;" value="<?php echo JText::_('FILTER'); ?>" />
	</span>
<?php
			}
			if($this->showResetButton){
?>
	<span class="hikashop_reset_button_outside" <?php echo $style; ?>>
		<a href="#" id="hikashop_reset_button_<?php echo $this->params->get('main_div_name'); ?>" class="<?php echo $this->config->get('css_button', 'hikabtn'); ?>" onclick="document.getElementById('hikashop_reseted_<?php echo $this->params->get('main_div_name'); ?>').value='1';document.forms['hikashop_filter_form_<?php echo $this->params->get('main_div_name'); ?>'].submit(); return false;"><?php echo JText::_('RESET'); ?></a>
	</span>
<?php
			}
		}
?>
</div>
<?php } ?>
