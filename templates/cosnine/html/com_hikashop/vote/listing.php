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
$row =& $this->rows;
if($row->comment_enabled != 1)
	return;

if(($row->hikashop_vote_con_req_list == 1 && hikashop_loadUser() != "") || $row->hikashop_vote_con_req_list == 0) {
?>

<div class="hikashop_listing_comment">
<?php
	if($row->vote_comment_sort_frontend) {
		$sort = hikaInput::get()->getString('sort_comment','');
?>
<div class="filter-comment">
	<span class="hikashop_sort_listing_comment">
		Sort by <select name="sort_comment" onchange="refreshCommentSort(this.value); return false;">
			<option <?php if($sort == 'date')echo "selected"; ?> value="date"><?php echo JText::_('HIKASHOP_COMMENT_ORDER_DATE');?></option>
			<option <?php if($sort == 'helpful')echo "selected"; ?> value="helpful"><?php echo JText::_('HIKASHOP_COMMENT_ORDER_HELPFUL');?></option>
		</select>
	</span>
</div>
<?php
	}

	$i = 0;
	foreach($this->elts as $elt) {
		if(empty($elt->vote_comment))
			continue;
?>
<div class="item-comment">
	<h4>A lot of different features are available</h4>
	<p><?php

		$nb_star_vote = $elt->vote_rating;
		hikaInput::get()->set("nb_star", $nb_star_vote);
		$nb_star_config = $row->vote_star_number;
		hikaInput::get()->set("nb_max_star", $nb_star_config);

		
			for($k = 0; $k < $nb_star_vote; $k++) {
				?><span class="hika_comment_listing_full_stars hk-rate-star state-full"></span><?php
			}
			$nb_star_empty = $nb_star_config - $nb_star_vote;
			for($k = 0; $k < $nb_star_empty; $k++) {
				?><span class="hika_comment_listing_empty_stars hk-rate-star state-empty"></span><?php
			}
?>
			<span style="display:none;" itemprop="reviewRating" itemscope itemtype="http://schema.org/Rating">
				<span itemprop="bestRating"><?php echo $nb_star_config; ?></span>
				<span itemprop="worstRating">1</span>
				<span itemprop="ratingValue"><?php echo $nb_star_vote; ?></span>
			</span>
<?php
		
?></p>
	
	<p class="date">By <?php echo $elt->vote_pseudo == '0'?$elt->username:$elt->vote_pseudo ?> on <?php $voteClass = hikashop_get('class.vote');
			$vote = $voteClass->get($elt->vote_id);
			echo date('F d, Y',$vote->vote_date);
			?></p>
	<p><?php echo nl2br($this->escape($elt->vote_comment)); ?></p>
</div>
<?php
}

if(!count($this->elts)) {
?>
<table class="hika_comment_listing">
	<tr>
		<td class="hika_comment_listing_empty"><?php
			echo JText::_('HIKASHOP_NO_COMMENT_YET');
		?></td>
	</tr>
</table>
<?php
	} else {
		$this->pagination->form = '_hikashop_comment_form';
?>
<div class="pagination"><?php
	echo $this->pagination->getPagesLinks();
?></div>
<?php
	}
?>
<input type="hidden" id="num-comment" value="<?php echo count($this->elts) ?>">
</div>
<?php
}
if($row->vote_comment_sort_frontend) {
	$jconfig = JFactory::getConfig();
	$sef = (HIKASHOP_J30 ? $jconfig->get('sef') : $jconfig->getValue('config.sef'));

	$sortUrl = $sef ? '/sort_comment-' : '&sort_comment=';
?>
<script type="text/javascript">
function refreshCommentSort(value){
	var url = window.location.href;
	if(url.match(/sort_comment/g)){
		url = url.replace(/\/sort_comment.?[a-z]*/g,'').replace(/&sort_comment.?[a-z]*/g,'');
	}
	url = url+'<?php echo $sortUrl; ?>'+value;
	document.location.href = url;
}
jQuery(function(){
	var num=jQuery('#num-comment').val();
	jQuery('.num-comment').text(num);
})
</script>
<?php }
