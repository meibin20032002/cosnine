<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_content
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

// Create some shortcuts.
$params    = &$this->item->params;
$n         = count($this->items);
$listOrder = $this->escape($this->state->get('list.ordering'));

// Check for at least one editable article
?>
<div class="blog<?php echo $this->pageclass_sfx; ?>">
    <div class="box-relative">
		<h3 class="h-title"><?php echo $this->escape($this->params->get('page_heading')); ?></h3>
	</div>
    
	<form action="<?php echo htmlspecialchars(JUri::getInstance()->toString()); ?>" method="post" name="adminForm" id="adminForm" class="form-inline">
		<?php foreach ($this->items as $i => $article) : ?>
		<?php 
			$images = json_decode($article->images);
			$link = JRoute::_(ContentHelperRoute::getArticleRoute($article->slug, $article->catid, $article->language));
			$link_category = JRoute::_(ContentHelperRoute::getCategoryRoute($article->catslug));
		?>
		<div class="item">
			<div class="col-md-4" style="padding-left:0">
				<a href="<?php echo  $link?>">
					<img src="<?php echo htmlspecialchars($images->image_intro); ?>" alt="<?php echo htmlspecialchars($images->image_intro_alt); ?>"/>
				</a>
			</div>
			<div class="col-md-8">
				<a href="<?php echo  $link?>"><h3 class="title"><?php echo $article->title?></h3></a>
				<a href="<?php echo  $link_category?>"><h4 class="tag"><span><?php echo $article->category_title?></span></h4></a>
				<?php echo $article->introtext?>
			</div>
		</div>
		<?php endforeach;?>
		
    
        <?php if (($this->params->def('show_pagination', 1) == 1 || ($this->params->get('show_pagination') == 2)) && ($this->pagination->get('pages.total') > 1)) : ?>
		<div class="pagination">
			<?php if ($this->params->def('show_pagination_results', 1)) : ?>
				<p class="counter pull-right"> <?php echo $this->pagination->getPagesCounter(); ?> </p>
			<?php endif; ?>
			<?php echo $this->pagination->getPagesLinks(); ?> 
        </div>
	    <?php endif; ?>


		<input type="hidden" class="filter_order" name="filter_order" value="<?php echo $listOrder; ?>" />
        <input type="hidden" name="filter_order_Dir" value="ASC" />
		<input type="hidden" name="limitstart" value="" />
		<input type="hidden" name="task" value="" />
		<?php echo JHtml::_('form.token'); ?>
	</form>
</div>