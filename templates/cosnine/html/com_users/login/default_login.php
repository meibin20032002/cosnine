<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_users
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JHtml::_('behavior.keepalive');
JHtml::_('behavior.formvalidator');
?>

<div class="row">
    <div class="col-md-1"></div>
    <div class="col-md-9">
        <div class="login<?php echo $this->pageclass_sfx; ?>">
            <div class="col-md-6 login_box">
            	<form action="<?php echo JRoute::_('index.php?option=com_users&task=user.login'); ?>" method="post" class="form-validate">
                    <h4>Member Login</h4>
                    
            		<fieldset>
            			<?php foreach ($this->form->getFieldset('credentials') as $field) : ?>
            				<?php if (!$field->hidden) : ?>
            					<div class="control-group">  						
            						<div class="controls">
            							<?php echo $field->input; ?>
            						</div>
            					</div>
            				<?php endif; ?>
            			<?php endforeach; ?>
            
            			<?php if ($this->tfa): ?>
            				<div class="control-group">
            					<div class="controls">
            						<?php echo $this->form->getField('secretkey')->input; ?>
            					</div>
            				</div>
            			<?php endif; ?>
            
                        <div class="control-group">
       					    <div class="controls">
                                <button type="submit" class="btn btn-primary bt-skb">
            						<?php echo JText::_('JLOGIN'); ?>
            					</button>
                            </div>
        				</div>
                            
            			<div class="row list-group">
            				<div class="col-md-6">
            					<a href="<?php echo JRoute::_('index.php?option=com_users&view=remind'); ?>">
                        			<i class="fa fa-chevron-circle-right" aria-hidden="true"></i> 
                                    <?php echo JText::_('COM_USERS_LOGIN_REMIND'); ?>
                                </a> 
            				</div>
                            <div class="col-md-6" style="text-align: right;">
                                <a href="<?php echo JRoute::_('index.php?option=com_users&view=reset'); ?>">
                        			<i class="fa fa-chevron-circle-right" aria-hidden="true"></i> 
                                    <?php echo JText::_('COM_USERS_LOGIN_RESET'); ?>
                                </a>         			                                                      
                            </div>                                                
            			</div>
            
            			<?php if ($this->params->get('login_redirect_url')) : ?>
            				<input type="hidden" name="return" value="<?php echo base64_encode($this->params->get('login_redirect_url', $this->form->getValue('return'))); ?>" />
            			<?php else : ?>
            				<input type="hidden" name="return" value="<?php echo base64_encode($this->params->get('login_redirect_menuitem', $this->form->getValue('return'))); ?>" />
            			<?php endif; ?>
            			<?php echo JHtml::_('form.token'); ?>
            		</fieldset>
            	</form>
                
                <div class="stacked">                    
                    <?php 
        				$document	= JFactory::getDocument();
        				$renderer	= $document->loadRenderer('modules');
        				$options	= array('style' => 'xhtml');
        				$position	= 'social';
        				echo $renderer->render($position, $options, null);
        			?>
                </div>
            </div>
            <div class="col-md-6 login_box">
                <h4>Sign Up</h4>
                <p>Membership offers a variety of special benefits ready.</p>
                <a class="btn btn-signup" href="<?php echo JRoute::_('index.php?option=com_users&view=registration'); ?>">
					Sign Up
				</a>
            </div>
        </div>
    </div>
    <div class="col-md-1"></div>
</div>

