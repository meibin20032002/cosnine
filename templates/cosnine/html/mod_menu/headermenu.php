<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_menu
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
$id = '';

if ($tagId = $params->get('tag_id', ''))
{
	$id = ' id="' . $tagId . '"';
}

// The menu class is deprecated. Use nav instead
?>
<ul class="nav menu<?php echo $class_sfx; ?>"<?php echo $id; ?>>
<?php foreach ($list as $i => &$item)
{
	$class = 'item-' . $item->id;

	if ($item->id == $default_id)
	{
		$class .= ' default';
	}

	if ($item->id == $active_id || ($item->type === 'alias' && $item->params->get('aliasoptions') == $active_id))
	{
		$class .= ' current';
	}

	if (in_array($item->id, $path))
	{
		$class .= ' active';
	}
	elseif ($item->type === 'alias')
	{
		$aliasToId = $item->params->get('aliasoptions');

		if (count($path) > 0 && $aliasToId == $path[count($path) - 1])
		{
			$class .= ' active';
		}
		elseif (in_array($aliasToId, $path))
		{
			$class .= ' alias-parent-active';
		}
	}

	if ($item->type === 'separator')
	{
		$class .= ' divider';
	}

	if ($item->deeper)
	{
		$class .= ' deeper';
	}

	if ($item->parent)
	{
		$class .= ' parent';
	}

	echo '<li class="' . $class . '">';

	require JModuleHelper::getLayoutPath('mod_menu', 'headermenu_heading');

	// The next item is deeper.
	if ($item->deeper)
	{
		echo '<ul class="nav-child unstyled small">';
	}
	// The next item is shallower.
	elseif ($item->shallower)
	{
		echo '</li>';
		echo str_repeat('</ul></li>', $item->level_diff);
	}
	// The next item is on the same level.
	else
	{
		echo '</li>';
	}
}
?>

    <li>
        <span class="fa fa-shopping-cart"></span>
        <?php
        $document = JFactory::getDocument();
        $renderer = $document->loadRenderer('modules');
        $options = array('style' => 'xhtml');
        $position = 'top_cart';
        echo $renderer->render($position, $options, null);
        ?>
    </li>
    <li class="sns">
        <a href="https://www.facebook.com/Cosnine/" target="_blank">
            <i class="fa fa-facebook" aria-hidden="true"></i>
        </a>
    </li>
    <li class="sns">
        <a href="https://www.instagram.com/Cosnine.Vietnam/" target="_blank">
            <i class="fa fa-instagram" aria-hidden="true"></i>
        </a>
    </li>
    <li class="isearch link_bcPopupSearch">
        <a href="index.php?option=com_search&view=search&Itemid=168">
            <i class="fa fa-search" aria-hidden="true"></i>
        </a>
    </li>
</ul>
<?php
$use = JFactory::getUser();
?>
<script>
    jQuery(function () {
        var name = '<?php echo $use->name?>';
        jQuery('.member .item-171>a').html('<span class="fa fa-user"></span> Hi, ' + name);
    });
</script>