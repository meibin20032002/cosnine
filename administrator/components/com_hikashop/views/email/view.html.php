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
class EmailViewEmail extends hikashopView
{
	var $type = '';
	var $ctrl= 'email';
	var $nameListing = 'EMAILS';
	var $nameForm = 'EMAILS';
	var $icon = 'inbox';

	public function display($tpl = null) {
		$this->paramBase = HIKASHOP_COMPONENT.'.'.$this->getName();
		$function = $this->getLayout();
		if(method_exists($this,$function))
			$this->$function();
		return parent::display($tpl);
	}

	public function form() {
		$config = hikashop_config();

		$mail_name = hikaInput::get()->getString('mail_name');
		$this->assignRef('mail_name', $mail_name);

		$emailtemplateType = hikashop_get('type.emailtemplate');
		$this->assignRef('emailtemplateType', $emailtemplateType);
		$data = true;
		$mailClass = hikashop_get('class.mail');
		$mail = $mailClass->get($mail_name, $data);
		if(empty($mail)) {
			$mail->from_name = ''; // $config->get('from_name');
			$mail->from_email = ''; // $config->get('from_email');
			$mail->reply_name = ''; // $config->get('reply_name');
			$mail->reply_email = ''; // $config->get('reply_email');
			$mail->subject = '';
			$mail->html = 1;
			$mail->published = 1;
			$mail->body = '';
			$mail->altbody = '';
			$mail->preload = '';
			$mail->mail = $mail_name;
			$mail->email_log_published = 1;
		}

		$mail->default_values = new stdClass();

		$config_values = array('from_name', 'from_email', 'reply_name', 'reply_email');
		foreach($config_values as $k) {
			$mail->default_values->$k = $config->get($k);
			if($mail->$k === $mail->default_values->$k)
				$mail->$k = '';
		}

		$this->assignRef('mail', $mail);

		$values = new stdClass();
		$values->maxupload = (hikashop_bytes(ini_get('upload_max_filesize')) > hikashop_bytes(ini_get('post_max_size'))) ? ini_get('post_max_size') : ini_get('upload_max_filesize');
		$this->assignRef('values',$values);

		$email_history_plugin = JPluginHelper::getPlugin('hikashop', 'email_history');
		$this->assignRef('email_history_plugin', $email_history_plugin);

		$this->loadRef(array(
			'toggleClass' => 'helper.toggle',
			'editor' => 'helper.editor',
			'uploaderType' => 'type.uploader',
			'popup' => 'helper.popup',
		));

		$js = '
function updateEditor(htmlvalue) {
	var el = document.getElementById("htmlfieldset");
	if(!el) return;
	el.style.display = (htmlvalue == "0") ? "none" : "block";
}
window.addEvent("load", function(){ updateEditor('.$mail->html.'); });';

		$script = '
function addFileLoader() {
	var divfile = window.document.getElementById("loadfile");
	var input = document.createElement("input");
	input.type = "file";
	input.size = "30";
	input.name = "attachments[]";
	divfile.appendChild(document.createElement("br"));
	divfile.appendChild(input);
}
function submitbutton(pressbutton) {
	if(pressbutton == "cancel") {
		submitform( pressbutton );
		return;
	}
	if(window.document.getElementById("subject").value.length < 2) {
		alert("'.JText::_('ENTER_SUBJECT',true).'");
		return false;
	}
	submitform(pressbutton);
}
';
		$doc = JFactory::getDocument();
		$doc->addScriptDeclaration( $js . $script );

		if(!empty($mail->attach)) {
			$upload_dir = $config->get('uploadsecurefolder');
			$upload_dir = rtrim(JPath::clean(html_entity_decode($upload_dir)), DS.' ').DS;
			if(!preg_match('#^([A-Z]:)?/.*#',$upload_dir) && (substr($upload_dir, 0, 1) != '/' || !is_dir($upload_dir))) {
				$upload_dir = JPath::clean(HIKASHOP_ROOT.DS.trim($upload_dir, DS.' ').DS);
			}
			foreach($mail->attach as $k => &$v) {
				$v->file_name = $v->filename;
				$v->file_path = $v->filename;
				$v->file_size = @filesize($upload_dir . $v->filename);
				$v->delete = true;
			}
		}

		if(hikaInput::get()->getString('tmpl') != 'component') {

			$this->toolbar = array(
				'save',
				'apply',
				'cancel',
				'|',
				array('name' => 'pophelp', 'target' => $this->ctrl.'-form')
			);

			$previewMaker = $mailClass->getPreviewMaker($mail_name);

			if($previewMaker) {
				$url = 'index.php?option=com_hikashop&ctrl=email&task=preview&tmpl=component&mail_name='.$mail_name;
				array_unshift( $this->toolbar, array('name' => 'popup','icon'=>'mail','alt'=>JText::_('PREVIEW'),'url'=>$url) );
			}

			hikashop_setTitle(JText::_($this->nameForm),$this->icon,$this->ctrl.'&task=edit&mail_name='.$mail_name);
		}
	}

	function preview() {

		$this->mail_name = hikaInput::get()->getCmd('mail_name');
		$this->mailClass = hikashop_get('class.mail');
		$this->previewMaker = $this->mailClass->getPreviewMaker($this->mail_name);

		if(!$this->previewMaker)
			return;

		$type = $this->mail_name;
		if(!empty($this->previewMaker->type))
			$type = $this->previewMaker->type;
		$this->formData = hikaInput::get()->getVar('data', array(), 'array');
		$app = JFactory::getApplication();
		if(empty($this->formData))
			$this->formData = $app->getUserState('email_preview_'.$type);
		else
			$app->setUserState('email_preview_'.$type, $this->formData);

		if(method_exists($this->previewMaker, 'prepareMail')) {
			$this->mail = $this->previewMaker->prepareMail($this->formData);
		} else {
			$data = null;
			if($method_exists($this->previewMaker, 'prepareData'))
				$data = $this->previewMaker->prepareData($this->formData);

			$this->mail = $this->mailClass->get($this->mail_name, $data);
			if(method_exists($this->previewMaker, 'beforeDisplay'))
				$this->previewMaker->beforeDisplay($this->mail);
		}

		if($this->mail)
			$this->mailClass->preProcessMail($this->mail);
	}


	public function listing() {
		$app = JFactory::getApplication();
		$pageInfo = new stdClass();
		$pageInfo->filter = new stdClass();
		$pageInfo->filter->order = new stdClass();
		$pageInfo->limit = new stdClass();
		$config =& hikashop_config();

		$pageInfo->search = $app->getUserStateFromRequest( $this->paramBase.".search", 'search', '', 'string' );

		$pageInfo->limit->value = $app->getUserStateFromRequest($this->paramBase.'.limit', 'limit', $app->getCfg('list_limit'), 'int');
		$pageInfo->limit->start = $app->getUserStateFromRequest($this->paramBase.'.limitstart', 'limitstart', 0, 'int');
		$pageInfo->filter->order->value = $app->getUserStateFromRequest( $this->paramBase.".filter_order", 'filter_order',	'a.user_id','cmd' );
		$pageInfo->filter->order->dir	= $app->getUserStateFromRequest( $this->paramBase.".filter_order_Dir", 'filter_order_Dir',	'desc',	'word' );

		jimport('joomla.filesystem.file');
		$mail_folder = rtrim( str_replace( '{root}', JPATH_ROOT, $config->get('mail_folder',HIKASHOP_MEDIA.'mail'.DS)), '/\\').DS;

		$mailClass = hikashop_get('class.mail');
		$files = $mailClass->getFiles();

		$emails = array();
		foreach($files as $file){
			$folder = $mail_folder;
			$filename = $file;

			$email = new stdClass();

			if(is_array($file)) {
				$folder = $file['folder'];
				if(!empty($file['name']))
					$email->name = $file['name'];
				$filename = $file['filename'];
				$file = $file['file'];
			}

			$email->file = $file;
			$email->overriden_text = JFile::exists($folder.$filename.'.text.modified.php');
			$email->overriden_html = JFile::exists($folder.$filename.'.html.modified.php');
			$email->overriden_preload = JFile::exists($folder.$filename.'.preload.modified.php');
			$email->published = $config->get($file.'.published');
			$emails[] = $email;
		}

		$pageInfo->elements = new stdClass();
		$pageInfo->elements->total = count($emails);

		$emails = array_slice($emails, $pageInfo->limit->start, $pageInfo->limit->value);
		$pageInfo->elements->page = count($emails);

		$this->assignRef('rows',$emails);
		$this->assignRef('pageInfo',$pageInfo);
		hikashop_setTitle(JText::_($this->nameListing),$this->icon,$this->ctrl);
		$this->getPagination();

		$this->toolbar = array(
			array('name' => 'pophelp', 'target' => $this->ctrl.'-listing'),
			'dashboard'
		);

		$manage = true; $delete = false;
		$manage = hikashop_isAllowed($config->get('acl_email_manage','all'));
		$delete = hikashop_isAllowed($config->get('acl_email_delete','all'));

		jimport('joomla.client.helper');
		$ftp = JClientHelper::setCredentialsFromRequest('ftp');
		$this->assignRef('ftp', $ftp);
		$this->assignRef('manage',$manage);
		$this->assignRef('delete',$delete);

		$toggle = hikashop_get('helper.toggle');
		$this->assignRef('toggleClass', $toggle);
	}

	public function emailtemplate() {
		$mailClass = hikashop_get('class.mail');

		$email_name = hikaInput::get()->getCmd('email_name');
		$file = hikaInput::get()->getCmd('file', '');
		$content = hikaInput::get()->getRaw('templatecontent', '');

		$filename = '';
		if(!empty($file))
			$filename = $mailClass->getTemplatePath($file, $email_name);

		if(!empty($file) && empty($content) && !empty($filename) && file_exists($filename))
			$content = file_get_contents($filename);

		if(empty($file)) {
			$i = 1;
			$file = 'custom_';
			$filename = JPath::clean(HIKASHOP_MEDIA.'mail'.DS.'template'.DS.$file.'.html.modified.php');
			while(file_exists($filename)) {
				$file = 'custom_'.$i;
				$filename = JPath::clean(HIKASHOP_MEDIA.'mail'.DS.'template'.DS.$file.'.html.modified.php');
				$i++;
			}
		}

		$this->assignRef('content', $content);
		$this->assignRef('fileName', $file);
		$this->assignRef('email_name', $email_name);
		$editor = hikashop_get('helper.editor');
		$this->assignRef('editor', $editor);
	}
}
