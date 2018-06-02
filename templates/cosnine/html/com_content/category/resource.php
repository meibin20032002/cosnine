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
<div class="resource">
    <div class="container">
    	<div class="row">
        	<div class="col-md-4">
        		<!-- menu resource left -->
        		<div class="menu-resource">			
        			<?php 
        				$document	= JFactory::getDocument();
        				$renderer	= $document->loadRenderer('modules');
        				$options	= array('style' => 'xhtml');
        				$position	= 'resoure-left';
        				echo $renderer->render($position, $options, null);
        			?>
        		</div>
        		<!-- resource popular article -->
        		<div class="menu-resource">
        			<?php 
        				$document	= JFactory::getDocument();
        				$renderer	= $document->loadRenderer('modules');
        				$options	= array('style' => 'xhtml');
        				$position	= 'resoure-popular';
        				echo $renderer->render($position, $options, null);
        			?>
        		</div>
        	</div>
        	<div class="col-md-8 list-article-resource">
        		<div class="box-relative">
        			<h3 class="h-title">Resources</h3>
        			<select id="sort" name="sort" class="inputbox">
        				<option <?php echo ($listOrder == '')?'selected':''?> value="">Sort by</option>
        				<option <?php echo ($listOrder == 'a.created')?'selected':''?> value="a.created">Created Date</option>
        				<option <?php echo ($listOrder == 'a.title')?'selected':''?> value="a.title">Title</option>
        			</select>
        		</div>
        		<form action="<?php echo htmlspecialchars(JUri::getInstance()->toString()); ?>" method="post" name="adminForm" id="adminForm" class="form-inline">
        		<div class="ajaxResult">
        			<!--Start Ajax-->
        			<?php foreach ($this->items as $i => $article) : ?>
        			<?php 
        				$images = json_decode($article->images);
        				$link = JRoute::_(ContentHelperRoute::getArticleRoute($article->slug, $article->catid, $article->language));
        				$link_category = JRoute::_(ContentHelperRoute::getCategoryRoute($article->catslug));
        			?>
        			<div class="box">
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
        			<!--End Ajax-->
        		</div>
                
                <?php /*if (($this->params->def('show_pagination', 1) == 1 || ($this->params->get('show_pagination') == 2)) && ($this->pagination->get('pages.total') > 1)) : ?>
            		<div class="pagination">
            			<?php if ($this->params->def('show_pagination_results', 1)) : ?>
            				<p class="counter pull-right"> <?php echo $this->pagination->getPagesCounter(); ?> </p>
            			<?php endif; ?>
            			<?php echo $this->pagination->getPagesLinks(); ?> </div>
            	<?php endif; */?>
                
        		<?php if($this->pagination->pagesTotal > 1):?>    
        		<div class="load-more">
        			<a onclick="loadmore()">Load More</a>
        			<div class="iconLoad"><img src="images/load.gif"/></div>
        		</div>
        		<?php endif; ?>
        	
            
        		<input type="hidden" class="filter_order" name="filter_order" value="<?php echo $listOrder; ?>" />
                <input type="hidden" name="filter_order_Dir" value="ASC" />
        		<input type="hidden" name="limitstart" value="" />
        		<input type="hidden" name="task" value="" />
        		<?php echo JHtml::_('form.token'); ?>
        	</form>
        	</div>
    	</div>
    </div>	
</div>	
<?php 
// The current page url
$uri = JFactory::getURI(); 
$pageURL = $uri->toString()
?>
<script type="text/javascript">
	
	jQuery( document ).ready(function($) {
        
        // Load more
        var url = '<?php echo $pageURL ?>';
        var page = 1;
		var limit = <?php echo $this->pagination->limit?>;
		var pageTotal = <?php echo $this->pagination->pagesTotal; ?>;
  
		window.loadmore = function (){
			jQuery('.iconLoad').show();   
			jQuery.ajax({
				url: url,
				data:{
					limitstart: page*limit,
					format:"ajax",
					tmpl:"component"
				},
				type: "GET",
				dataType:"html",
				success: function(data){
					jQuery('.iconLoad').hide();
					jQuery('.ajaxResult').append(data);
					
					page++;
					if(page == pageTotal)
					jQuery('.load-more').remove();
				}
			})
		}	
        // End load more
        
        // Sort date
        $('#sort option[value="'+listOrder+'"]').prop("selected", true);		
		$("#sort").change(function() {
			$('.filter_order').val( $(this).find(":checked").val() );
            $( "#adminForm" ).submit();
		})	
		var listOrder = <?php echo json_encode($listOrder); ?>;
		jQuery("#sort").chosen({
			"disable_search": true
		});		


        // Max heght row
		var maxh = 0;
		$(".mod-package .box .desc").each(function() {
			var h = $(this).height();
			if ( h > maxh ) maxh = h;
			
		});	
		
		if ( maxh != 0 ){
			$(".mod-package .box .desc").each(function() {
				$(this).css("height",maxh+'px');
				$(this).css("max-height",maxh+'px');
			});		
			
			$(".mod-package .box").each(function() {
				var maxbox = maxh + 270;
				if (window.matchMedia("screen and (max-width: 1200px)").matches) maxbox += 30; 
				$(this).css("height",maxbox+'px');
				$(this).css("max-height",maxbox+'px');
			});	
		}
	
	})
</script>	