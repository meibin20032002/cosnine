<?php
/**
 * @package	HikaShop for Joomla!
 * @version	3.3.0
 * @author	hikashop.com
 * @copyright	(C) 2010-2018 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><div class="iframedoc" id="iframedoc"></div>
<form action="<?php echo hikashop_completeLink('email'); ?>" method="post" name="adminForm" id="adminForm" enctype="multipart/form-data">
<?php if(hikaInput::get()->getString('tmpl') == 'component') { ?>
	<fieldset>
		<div class="toolbar" id="toolbar" style="float: right;">
			<button class="btn" type="button" onclick="javascript:submitbutton('apply'); return false;"><img src="<?php echo HIKASHOP_IMAGES; ?>save.png"/><?php echo JText::_('HIKA_SAVE',true); ?></button>
		</div>
	</fieldset>
<?php } ?>
<?php
	echo $this->loadTemplate('param');
?>
	<div class="hikashop_backend_tile_edition">
		<div class="hkc-xl-12 hkc-lg-12 hikashop_tile_block hikashop_mail_edit_html"><div>
			<div class="hikashop_tile_title"><?php echo JText::_('HTML_VERSION'); ?></div>
<?php
				echo $this->editor->displayCode(
					'data[mail][body]',
					@$this->mail->body,
					array('autoFocus' => false)
				);
?>
			</div>
		</div>
		<div class="hkc-xl-12 hkc-lg-12 hikashop_tile_block hikashop_mail_edit_text"><div>
			<div class="hikashop_tile_title"><?php echo JText::_('TEXT_VERSION'); ?></div>
				<textarea style="width:100%" rows="20" name="data[mail][altbody]" id="altbody" ><?php echo @$this->mail->altbody; ?></textarea>
			</div>
		</div>
		<div class="hkc-xl-12 hkc-lg-12 hikashop_tile_block hikashop_mail_edit_preload" id="preloadfieldset"><div>
			<div class="hikashop_tile_title"><?php echo JText::_('PRELOAD_VERSION'); ?></div>
<?php
				echo $this->editor->displayCode(
					'data[mail][preload]',
					@$this->mail->preload,
					array('autoFocus' => false)
				);
?>
			</div>
		</div>
	</div>
	<div class="clr"></div>

	<input type="hidden" name="mail_name" value="<?php echo @$this->mail_name; ?>" />
	<input type="hidden" name="option" value="<?php echo HIKASHOP_COMPONENT; ?>" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="ctrl" value="email" />
	<?php echo JHTML::_('form.token'); ?>
</form>
