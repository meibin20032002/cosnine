<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_articles_latest
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
?>
<div class="latestnews">
<?php foreach ($list as $item) : 
    $images = json_decode($item->images);
    $date = new DateTime($item->created);
    ?>
	<div class="item">
        <div class="row">
		<div class="col-xs-4">
			<a href="<?php echo $item->link; ?>">
				<img src="<?php echo htmlspecialchars($images->image_intro); ?>" alt="<?php echo htmlspecialchars($images->image_intro_alt); ?>"/>
			</a>
		</div>
		<div class="col-xs-8">
			<time><?php echo $date->format(JText::_('DATE_FORMAT_LC3'))?></time>
			<div class="title"><a href="<?php echo $item->link; ?>"><?php echo ($item->title); ?></a></div>
		</div>
        </div>
	</div>
<?php endforeach; ?>
</div>
