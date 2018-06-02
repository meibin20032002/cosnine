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
class CartViewCart extends HikaShopView {
	var $type = 'main';
	var $ctrl= 'cart';
	var $nameListing = 'CARTS';
	var $nameForm = 'CARTS';
	var $icon = 'cart';
	var $module = false;

	public function display($tpl = null, $params = array()) {
		$this->paramBase = HIKASHOP_COMPONENT.'.'.$this->getName();
		$function = $this->getLayout();
		$this->params =& $params;
		if(method_exists($this, $function))
			$this->$function();
		parent::display($tpl);
	}

	 public function share() {
		$config = hikashop_config();
		$this->assignRef('config', $config);
	 	$cart_id = hikashop_getCID('cart_id');
		$this->cartClass = hikashop_get('class.cart');
		$cart = $this->cartClass->getFullCart($cart_id);
		$this->assignRef('cart', $cart);
		$emails = hikaInput::get()->getVar('emails','');
		$this->assignRef('emails', $emails);
		$copy = hikaInput::get()->getInt('copy');
		$this->assignRef('copy', $copy);
		$cart_share_url = $this->cartClass->getShareUrl($cart);
		$this->assignRef('cart_share_url', $cart_share_url);

	 }

	public function show() {
		$app = JFactory::getApplication();
		$db = JFactory::getDBO();

		$user_id = hikashop_loadUser(false);

		$config = hikashop_config();
		$this->assignRef('config', $config);

		$this->loadRef(array(
			'imageHelper' => 'helper.image',
			'popupHelper' => 'helper.popup',
			'currencyClass' => 'class.currency',
			'cartClass' => 'class.cart',
			'productClass' => 'class.product',
			'dropdownHelper' => 'helper.dropdown',
		));

		$this->currencyHelper =& $this->currencyClass;

		$cart_id = hikashop_getCID('cart_id');
		if(empty($cart_id)){
			$type = 'cart';
			global $Itemid;
			$menus	= $app->getMenu();
			$menu = $menus->getActive();
			if(empty($menu)){
				if(!empty($Itemid)){
					$menus->setActive($Itemid);
					$menu = $menus->getItem($Itemid);
				}
			}

			if (is_object( $menu) && is_object( $menu->params ))
				$type = $menu->params->get('cart_type');
			if(empty($type))
				$type = hikaInput::get()->getString('cart_type','cart');
			if(!in_array($type, array('cart','wishlist')))
				$type = 'cart';
			$cart_id = $this->cartClass->getCurrentCartId($type) ;
		}
		$cart = $this->cartClass->getFullCart($cart_id);
		$this->assignRef('cart', $cart);

		$title = (!empty($cart) && $cart->cart_type == 'wishlist') ? 'HIKASHOP_WISHLIST': 'HIKASHOP_CART';
		hikashop_setPageTitle( JText::_($title) );

		if(empty($cart))
			return false;

		if($cart->cart_type == 'wishlist') {
			if($cart->user_id != $user_id) {
				$user = !empty($cart->user->username) ? $cart->user->username : $cart->user->user_email;
				hikashop_setPageTitle( JText::sprintf('HIKASHOP_USER_WISHLIST', $user) );
			}

			$this->loadRef(array(
				'cartShareType' => 'type.cart_share',
			));
		}

		$manage = ($cart->cart_type == 'cart' || $cart->user_id == $user_id);
		$this->assignRef('manage', $manage);

		$juser = JFactory::getUser();
		$this->assignRef('guest', $juser->guest);

		$print_cart = (hikaInput::get()->getBool('print_cart', false) === true) && $config->get('print_cart');
		if($print_cart)
			$manage = false;
		$this->assignRef('print_cart', $print_cart);

		if(hikashop_level(2)) {
			$fieldsClass = hikashop_get('class.field');
			$this->assignRef('fieldsClass', $fieldsClass);

			$null = null;
			$itemFields = $fieldsClass->getFields('frontcomp', $null, 'item', 'checkout&task=state');
			$this->assignRef('itemFields', $itemFields);

			$null = null;
			$productFields = $fieldsClass->getFields('display:front_cart_details=1', $null, 'product');
			$this->assignRef('productFields', $productFields);

			$usefulFields = array();
			foreach($productFields as $field){
				$fieldname = $field->field_namekey;
				foreach($cart->products as $product) {
					if(!empty($product->$fieldname)) {
						$usefulFields[] = $field;
						break;
					}
				}
			}
			$productFields = $usefulFields;
		}
		if($cart->cart_type == 'wishlist') {
			$confirmed_status = $config->get('invoice_order_statuses', 'confirmed,shipped');
			if(empty($confirmed_status))
				$confirmed_status = 'confirmed,shipped';
			$confirmed_status = explode(',', trim($confirmed_status, ','));
			foreach($confirmed_status as &$status) {
				$status = $db->Quote($status);
			}
			unset($status);

			$filters = array(
				'hk_order_product.order_product_wishlist_id = -' . (int)$cart_id
			);

			if(!empty($cart->cart_products)) {
				$p = array_keys($cart->cart_products);
				JArrayHelper::toInteger($p);
				if(in_array(0, $p))
					$p = array_diff($p, array(0));
				$filters[] = 'hk_order_product.order_product_wishlist_product_id IN ('.implode(',', $p).')';
			}

			$query = 'SELECT hk_order.order_id, hk_order.order_user_id, hk_user.user_email, hk_order.order_status, hk_order_product.* '.
				' FROM '.hikashop_table('order').' AS hk_order '.
				' LEFT JOIN '.hikashop_table('order_product').' AS hk_order_product ON hk_order.order_id = hk_order_product.order_id '.
				' LEFT JOIN '.hikashop_table('user').' AS hk_user ON hk_user.user_id = hk_order.order_user_id '.
				' WHERE hk_order.order_status IN ('.implode(',', $confirmed_status).') AND hk_order.order_type = '.$db->Quote('sale').' AND ('.implode(' OR ', $filters).')';
			$db->setQuery($query);
			$related_orders = $db->loadObjectList();

			if(!empty($related_orders)) {

				foreach($related_orders as &$related_order) {

					if(!empty($related_order->order_product_wishlist_product_id) && isset($cart->products[(int)$related_order->order_product_wishlist_product_id])) {
						$product =& $cart->products[(int)$related_order->order_product_wishlist_product_id];

						if(empty($product->bought))
							$product->bought = 0;
						$product->bought += (int)$related_order->order_product_quantity;

						if($manage) {
							if(empty($product->buyers))
								$product->related_orders = array();
							$product->related_orders[] = $related_order;
						}

						unset($product);

						$related_order->done = true;

						continue;
					}

					if(empty($related_order->order_product_wishlist_product_id)) {
						foreach($cart->products as &$product) {
							if((int)$related_order->product_id != (int)$product->product_id)
								continue;

							if(empty($product->bought))
								$product->bought = 0;
							$product->bought += (int)$related_order->order_product_quantity;

							if($manage) {
								if(empty($product->buyers))
									$product->related_orders = array();
								$product->related_orders[] = $related_order;
							}

							$related_order->done = true;
						}
						unset($product);
					}

					if(!empty($related_order->done))
						continue;

				}
				unset($related_order);
			}
		}

		$cart_share_url = $this->cartClass->getShareUrl($cart);
		$this->assignRef('cart_share_url', $cart_share_url);

		$menuClass = hikashop_get('class.menus');
		$url_checkout = $menuClass->getCheckoutURL();
		$this->assignRef('checkout_url', $url_checkout);

		foreach($cart->products as &$product) {
			$this->productClass->addAlias($product);
		}

		$user_carts = array();
		if((int)$config->get('enable_multicart') && !empty($user_id)) {
			$query = 'SELECT cart_id, cart_name, cart_modified, cart_current '.
					' FROM '.hikashop_table('cart').' AS cart WHERE cart.user_id = '.(int)$user_id.' AND cart.cart_type = '.$db->Quote('cart').' AND cart.cart_id != '.(int)$cart->cart_id;
			$db->setQuery($query);
			$user_carts = $db->loadObjectList();
		}
		$this->assignRef('user_carts', $user_carts);

		$user_wishlists = array();
		if((int)$config->get('enable_wishlist')) {
			$query = 'SELECT cart_id, cart_name, cart_modified, cart_current '.
					' FROM '.hikashop_table('cart').' AS cart WHERE cart.user_id = '.(int)$user_id.' AND cart.cart_type = '.$db->Quote('wishlist').' AND cart.cart_id != '.(int)$cart->cart_id;
			$db->setQuery($query);
			$user_wishlists = $db->loadObjectList();
		}
		$this->assignRef('user_wishlists', $user_wishlists);

		$checkbox_column = ((int)$config->get('enable_multicart') || (int)$config->get('enable_wishlist')) && empty($print_cart);
		$this->assignRef('checkbox_column', $checkbox_column);

		$params = new hikaParameter();
		$default_params = $config->get('default_params');
		foreach($default_params as $k => $v) {
			$params->set($k, $v);
		}
		$params->set('show_delete', $config->get('checkout_cart_delete', 1));
		$this->assignRef('params', $params);

		$toolbar_array = array();
		global $Itemid;
		if($cart->cart_type == 'wishlist' && !empty($this->manage)) {
			$share = array(
				'icon' => 'email',
				'name' => JText::_('SHARE'),
				'url' => hikashop_completeLink(
					'cart&task=share=&cart_id='.$cart->cart_id.'&Itemid='.$Itemid, true),
				'popup' => array(
					'id' => 'hikashop_share_cart',
					'width' => 360,
					'height' => 360
					)
			);
			$toolbar_array['share'] = $share;
		}
		if($config->get('print_cart')) {
			$print = array(
				'icon' => 'print',
				'name' => JText::_('HIKA_PRINT'),
				'url' => hikashop_completeLink(
					'cart&task=show&print_cart=1&cart_id='.$cart->cart_id.'&Itemid='.$Itemid, true),
				'popup' => array(
					'id' => 'hikashop_print_cart',
					'width' => 760,
					'height' => 480
					)
			);
			$toolbar_array['print'] = $print;
		}
		if($cart->cart_type != 'wishlist') {
			$toolbar_array['cart'] = array(
				'icon' => 'cart',
				'name' => JText::_('PROCEED_TO_CHECKOUT'),
				'url' => $url_checkout,
			);
		}
		else {
			$toolbar_array['cart'] = array(
				'icon' => 'cart',
				'name' => JText::_('ADD_TO_CART'),
				'javascript' => 'return window.cartMgr.moveProductsToCart(0)',
			);
		}
		if($this->config->get('enable_multicart') && !$juser->guest) {
			$dropData = array();
			foreach($user_carts as $user_cart) {
				$cart_name = !empty($user_cart->cart_name) ? $user_cart->cart_name : '';
				if(empty($cart_name))
					$cart_name = !empty($user_cart->cart_current) ? JText::_('CURRENT_CART') : hikashop_getDate($user_cart->cart_modified);
				$dropData[] = array(
					'name' => '<span class="btnName">'.$cart_name.'</span>',
					'link' => '#move-to-cart',
					'click' => 'return window.cartMgr.moveProductsToCart('.(int)$user_cart->cart_id.');'
				);
			}
			if(!empty($dropData))
				$dropData[] = '-';

			$dropData[JText::_('NEW_CART')] = array(
				'name' => JText::_('NEW_CART'),
				'link' => '#new-cart',
				'click' => 'return window.cartMgr.moveProductsToCart(-1);'
			);
			$dropdown_name = !empty($manage) ? JText::_('MOVE_TO_CART') : JText::_('ADD_TO_CART');
			$toolbar_array[$dropdown_name] = array('dropdown' => array(
					'label' => !empty($manage) ? JText::_('MOVE_TO_CART') : JText::_('ADD_TO_CART'),
					'data' => $dropData,
					'options' => array('type' => 'link', 'right' => true, 'up' => false, 'hkicon' => 'icon-32-go-cart')
				)
			);
		}
		if($config->get('enable_wishlist') && !$juser->guest) {
			$dropData = array();
			foreach($user_wishlists as $user_wishlist) {
				$dropData[] = array(
					'name' => !empty($user_wishlist->cart_name) ? $user_wishlist->cart_name : hikashop_getDate($user_wishlist->cart_modified),
					'link' => '#move-to-wishlist',
					'click' => 'return window.cartMgr.moveProductsToWishlist('.(int)$user_wishlist->cart_id.');'
				);
			}

			if(!empty($dropData))
				$dropData[] = '-';

			$dropData[] = array(
				'name' => JText::_('NEW_WISHLIST'),
				'link' => '#new-wishlist',
				'click' => 'return window.cartMgr.moveProductsToWishlist(-1);'
			);
			$dropdown_name = !empty($manage) ? JText::_('MOVE_TO_WISHLIST') : JText::_('ADD_TO_WISHLIST');
			$toolbar_array[$dropdown_name] = array('dropdown' => array(
					'label' => !empty($manage) ? JText::_('MOVE_TO_WISHLIST') : JText::_('ADD_TO_WISHLIST'),
					'data' => $dropData,
					'options' => array('type' => 'link', 'right' => true, 'up' => false, 'hkicon' => 'icon-32-wishlist', 'main_class' => 'hikabtn')
				)
			);
		}
		if(!empty($manage)) {
			$toolbar_array['save'] = array(
				'icon' => 'save',
				'name' => JText::_('HIKA_SAVE'),
				'javascript' => "return window.hikashop.submitform('apply','hikashop_show_cart_form');"
			);
		}
		if(!$juser->guest) {
			$link = hikashop_completeLink('user&task=cpanel');
			if($this->config->get('enable_multicart')) {
				$link = hikashop_completeLink('cart&task=listing&cart_type=' . $cart->cart_type.'&Itemid='.$Itemid);
			}
			$toolbar_array['back'] = array(
				'icon' => 'back',
				'name' => JText::_('HIKA_BACK'),
				'url' => $link
			);
		}
		$this->toolbar = $toolbar_array;

		if($cart->cart_type == 'wishlist')
			$this->title = JText::_('HIKASHOP_WISHLIST');
		else
			$this->title = JText::_('HIKASHOP_CART');
	}

	public function listing() {
		$app = JFactory::getApplication();
		$db = JFactory::getDBO();

		$user_id = hikashop_loadUser(false);

		$config = hikashop_config();
		$this->assignRef('config', $config);
		global $Itemid;

		$cart_type = hikaInput::get()->getCmd('cart_type', '');
		if(empty($cart_type)){
			$menus	= $app->getMenu();
			$menu	= $menus->getActive();
			if(empty($menu)){
				if(!empty($Itemid)){
					$menus->setActive($Itemid);
					$menu	= $menus->getItem($Itemid);
				}
			}
			if (is_object( $menu ) && in_array($menu->link, array('index.php?option=com_hikashop&view=cart&layout=showcarts', 'index.php?option=com_hikashop&view=cart&layout=listing'))) {
				jimport('joomla.html.parameter');
				$menu_params = new HikaParameter( $menu->params );
				$cart_type = $menu_params->get('cart_type');
			}
		}
		if(!in_array($cart_type, array('cart','wishlist')))
			$cart_type = 'cart';
		$this->assignRef('cart_type', $cart_type);

		$title = ($cart_type == 'wishlist') ? 'WISHLISTS': 'CARTS';
		hikashop_setPageTitle( JText::_($title) );

		$this->loadRef(array(
			'cartClass' => 'class.cart',
			'currencyClass' => 'class.currency'
		));

		$pageInfo = $this->getPageInfo('cart.cart_id');

		$filters = array(
			'cart.cart_type = ' . $db->Quote($cart_type),
			'cart.user_id = ' . (int)$user_id
		);
		$orderingAccept = array(
			'cart.cart_id'
		);
		$order = ' ORDER BY cart.cart_id ASC';
		$searchMap = array();
		$this->processFilters($filters, $order, $searchMap, $orderingAccept);

		$query = ' FROM ' . hikashop_table('cart') . ' AS cart ' . $filters . $order;
		$this->getPageInfoTotal($query, '*');
		$db->setQuery('SELECT cart.cart_id' . $query, $pageInfo->limit->start, $pageInfo->limit->value);
		$rows = $db->loadObjectList('cart_id');

		foreach($rows as &$row) {
			$row = $this->cartClass->getFullCart($row->cart_id);
		}
		unset($row);
		$this->toolbar = array(
			'back' => array(
				'icon' => 'back',
				'name' => JText::_('HIKA_BACK'),
				'url' => hikashop_completeLink('user&task=cpanel&Itemid='.$Itemid)
			),
		);
		if($cart_type == 'wishlist')
			$this->title = JText::_('WISHLISTS');
		else
			$this->title = JText::_('CARTS');

		$this->assignRef('carts', $rows);

		$this->getPagination();
		$this->getOrdering('cart.cart_id', true);
	}

	function showcart() {
		$app = JFactory::getApplication();
		$user = hikashop_loadUser();
		$database = JFactory::getDBO();
		$config =& hikashop_config();

		$image = hikashop_get('helper.image');
		$this->assignRef('image',$image);

		$popup = hikashop_get('helper.popup');
		$this->assignRef('popup',$popup);

		$module = hikashop_get('helper.module');
		$module->initialize($this);

		$currencyClass = hikashop_get('class.currency');
		$cartClass = hikashop_get('class.cart');
		$productClass = hikashop_get('class.product');

		$main_currency = (int)$config->get('main_currency',1);
		$currency_id = hikashop_getCurrency();

		if($config->get('tax_zone_type','shipping') == 'billing') {
			$zone_id = hikashop_getZone('billing');
		} else {
			$zone_id = hikashop_getZone('shipping');
		}
		$discount_before_tax = (int)$config->get('discount_before_tax', 0);

		$menus = $app->getMenu();
		$menu = $menus->getActive();

		global $Itemid;
		if(empty($menu) && !empty($Itemid)) {
			$menus->setActive($Itemid);
			$menu = $menus->getItem($Itemid);
		}

		if(isset($menu->params) && is_object($menu->params))
			$cart_type = $menu->params->get('cart_type');

		if(!isset($cart_type) || $cart_type == null || empty($cart_type)) {
			if(isset($this->params) && is_object($this->params)) {
				$cart_type = $this->params->get('cart_type','cart');
			} else {
				$cart_type = hikaInput::get()->getVar('cart_type','cart');
			}
		}

		$cart_id = hikashop_getCID();
		if($cart_id == 0)
			$cart_id = hikaInput::get()->getInt('cart_id',0);

		if(empty($cart_id) || $cart_id == 0)
			$cart_id = $cartClass->getCurrentCartId($cart_type);

		$fullCart= $cartClass->getFullCart($cart_id);

		if(!isset($fullCart->products)) {
			if($config->get('enable_multicart',0)) {
				global $Itemid;
				$url = hikashop_contentLink('cart&task=showcarts&cart_type='.$cart_type.'&Itemid='.$Itemid);
				$app->redirect($url);
			}

			$this->fullCart = new stdClass;
			$this->fullCart->display = false;
			return;
		}

		$rows = $fullCart->products;

		$confirmedStatus = $config->get('invoice_order_statuses','confirmed,shipped');
		if(empty($confirmedStatus)) $confirmedStatus = 'confirmed,shipped';
		$confirmedStatus = explode(',', trim($confirmedStatus, ','));
		foreach($confirmedStatus as &$status) {
			$status = $database->Quote($status);
		}
		unset($status);

		if($fullCart->cart_type == 'wishlist' && $user == $fullCart->user_id){
			$query='SELECT a.*,b.* FROM '.hikashop_table('order').' AS a LEFT JOIN '.hikashop_table('order_product').' AS b ON a.order_id=b.order_id WHERE a.order_status IN ('.implode(',',$confirmedStatus).') AND b.order_product_wishlist_id ='.(int)$cart_id;
			$database->setQuery($query);
			$buyers = $database->loadObjectList();

			foreach($buyers as $j => $buyer){
				foreach($rows as $k => $row){
					if($row->product_id == $buyer->product_id){
						if($buyer->order_user_id == $user){
							$rows[$k]->bought[$j] = JText::_('ORDER_NUMBER').": ".$buyer->order_id.' - '.$buyer->order_product_quantity.' '.JText::_('HIKASHOP_ITEM');
						}else{
							$userClass = hikashop_get('class.user');
							$user = $userClass->get($buyer->order_user_id);
							if(!empty($user->username)){
								$rows[$k]->bought[$j] = $user->username.' - '.$buyer->order_product_quantity.' '.JText::_('HIKASHOP_ITEM');
							}else if(!empty($user->user_email)){
								$rows[$k]->bought[$j] = $user->user_email.' - '.$buyer->order_product_quantity.' '.JText::_('HIKASHOP_ITEM');
							}else{
								$rows[$k]->bought[$j] = JText::_('HKASHOP_USER_ID').": ".$buyer->order_user_id.' - '.$buyer->order_product_quantity.' '.JText::_('HIKASHOP_ITEM');
							}
						}
						$rows[$k]->cart_product_quantity -= $buyer->order_product_quantity;
						if($rows[$k]->cart_product_quantity < 0)
							$rows[$k]->cart_product_quantity = 0;
					}
				}
			}
		}

		if($cart_type=='wishlist'){
			if( $fullCart->cart_share == 'registered'){
				$fullCart->display = 'registered';
			}
			else if($fullCart->cart_share == 'public'){
				$fullCart->display = 'public';
			}
			else if(in_array($user,explode(',',$fullCart->cart_share))){
				$fullCart->display = $fullCart->cart_share;
			}
			else if(hikaInput::get()->getString('link','link') == $fullCart->cart_share || strlen($fullCart->cart_share) == 20){
				$fullCart->display = $fullCart->cart_share;
			}
			elseif($fullCart->cart_share == 'nobody' && $fullCart->user_id != $user){
				$fullCart->display = false;
			}
			else{
				$fullCart->display = 'main';
			}
		}else{
			$session = JFactory::getSession();
			if(!empty($fullCart->user_id) && $fullCart->user_id != $user || empty($fullCart->user_id) && $session->getId() != $fullCart->session_id){
				$fullCart->display = false;
			}else{
				$fullCart->display = 'main';
			}
		}

		if(!empty($rows)){
			$variants = false;
			$ids = array();
			foreach($rows as $k => $row){
				$ids[] = (int)$row->product_id;
				if(isset($row->product_type) && $row->product_type=='variant') {
					$variants = true;
					foreach($rows as $k2 => $row2) {
						if($row->product_parent_id == $row2->product_id) {
							$rows[$k2]->variants[] =& $rows[$k];
						}
					}
				}
			}
			if($variants) {
				$this->selected_variant_id = 0;
				$query = 'SELECT a.*,b.* FROM '.hikashop_table('variant').' AS a LEFT JOIN '.hikashop_table('characteristic').' AS b ON a.variant_characteristic_id=b.characteristic_id WHERE a.variant_product_id IN ('.implode(',',$ids).') ORDER BY a.ordering ASC,b.characteristic_value ASC';
				$database->setQuery($query);
				$characteristics = $database->loadObjectList();
				if(!empty($characteristics)){
					foreach($rows as $k => $row){
						$element =& $rows[$k];
						$product_id=$row->product_id;
						if($row->product_type=='variant'){
							continue;
						}
						$mainCharacteristics = array();
						foreach($characteristics as $characteristic){
							if($product_id==$characteristic->variant_product_id){
								$mainCharacteristics[$product_id][$characteristic->characteristic_parent_id][$characteristic->characteristic_id]=$characteristic;
							}
							if(!empty($element->options)){
								foreach($element->options as $k => $optionElement){
									if($optionElement->product_id==$characteristic->variant_product_id){
										$mainCharacteristics[$optionElement->product_id][$characteristic->characteristic_parent_id][$characteristic->characteristic_id]=$characteristic;
									}
								}
							}
						}
						if(!empty($element->variants)){
							$this->addCharacteristics($element,$mainCharacteristics,$characteristics);
						}
						if(!empty($element->options)){
							foreach($element->options as $k => $optionElement){
								if(!empty($optionElement->variants)){
									$this->addCharacteristics($element->options[$k],$mainCharacteristics,$characteristics);
								}
							}
						}
					}
				}
			}
			$product_quantities = array();
			foreach($rows as $row){
				if(empty($product_quantities[$row->product_id])){
					$product_quantities[$row->product_id] = (int)@$row->cart_product_quantity;
				}else{
					$product_quantities[$row->product_id]+=(int)@$row->cart_product_quantity;
				}
			}
			foreach($rows as $k => $row){
				$rows[$k]->cart_product_total_quantity = $product_quantities[$row->product_id];
			}
			$currencyClass->getPrices($rows,$ids,$currency_id,$main_currency,$zone_id,$discount_before_tax);
			foreach($rows as $k => $row){
				if(!empty($row->variants)){
					foreach($row->variants as $k2 => $variant){
						$productClass->checkVariant($rows[$k]->variants[$k2],$row);
					}
				}
			}
			$cids = array();
			foreach($rows as $k => $row){
				$currencyClass->calculateProductPriceForQuantity($rows[$k]);

				if($cart_type!='wishlist'){
					if($row->cart_product_quantity == 0){
						$rows[$k]->hide = 1;
					}
				}else if(isset($row->product_type) && $row->product_type=='variant' && !empty($row->cart_product_parent_id) && isset($rows[$row->cart_product_parent_id])){
					$rows[$row->cart_product_parent_id]->hide = 1;
				}
				$cids[] = (int)$row->product_id;
			}
			$total=new stdClass();
			$currencyClass->calculateTotal($rows,$total,$currency_id);

			$query = 'SELECT * FROM '.hikashop_table('file').' WHERE file_ref_id IN ('.implode(',',$cids).') AND file_type IN (\'product\',\'file\') ORDER BY file_ref_id ASC, file_ordering ASC, file_id ASC';
			$database->setQuery($query);
			$product_files = $database->loadObjectList();
			if(!empty($product_files)){
				foreach($rows as $k => $row) {
					$productClass->addFiles($rows[$k],$product_files);
					if(in_array($row->product_id,array_keys($product_files))){
						$row->images[] = $product_files[$row->product_id];
					}elseif(in_array($row->product_parent_id,array_keys($product_files))){
						$row->images[] = $product_files[$row->product_parent_id];
					}
				}
			}

			$mainIds = array();
			foreach($rows as $product){
				if($product->product_parent_id == '0')
					$mainIds[]=(int)$product->product_id;
				else
					$mainIds[]=(int)$product->product_parent_id;
			}
			$query = 'SELECT a.*, b.* FROM '.hikashop_table('product_category').' AS a LEFT JOIN '.hikashop_table('category').' AS b ON a.category_id = b.category_id WHERE a.product_id IN('.implode(',',$mainIds).') ORDER BY a.ordering ASC';
			$database->setQuery($query);
			$categories = $database->loadObjectList();
			$quantityDisplayType = hikashop_get('type.quantitydisplay');
			foreach($rows as $k => $row){
				if($row->product_parent_id != 0 && $row->cart_product_parent_id != '0'){
					$row->product_quantity_layout = $rows[$row->cart_product_parent_id]->product_quantity_layout;
					$row->product_min_per_order = $rows[$row->cart_product_parent_id]->product_min_per_order;
					$row->product_max_per_order = $rows[$row->cart_product_parent_id]->product_max_per_order;
				}
				if(empty($row->product_quantity_layout) || $row->product_quantity_layout == 'inherit'){
					$categoryQuantityLayout = '';
					if(!empty($categories) ) {
						foreach($categories as $category) {
							if($category->product_id == $row->product_id && !empty($category->category_quantity_layout) && $quantityDisplayType->check($category->category_quantity_layout, $app->getTemplate())) {
								$categoryQuantityLayout = $category->category_quantity_layout;
								break;
							}
						}
					}
				}
				if(!empty($row->product_quantity_layout) &&  $row->product_quantity_layout != 'inherit'){
					$qLayout = $row->product_quantity_layout;
				}elseif(!empty($categoryQuantityLayout) && $categoryQuantityLayout != 'inherit'){
					$qLayout = $categoryQuantityLayout;
				}else{
					$qLayout = $config->get('product_quantity_display','show_default');
				}
				$rows[$k]->product_quantity_layout = $qLayout;
			}
		}

		$js="function checkAll(){
			var toCheck = document.getElementById('hikashop_cart_product_listing').getElementsByTagName('input');
			for (i = 0 ; i < toCheck.length ; i++) {
				if (toCheck[i].type == 'checkbox') {
					toCheck[i].checked = true;
				}
			}
		}";

		if(!HIKASHOP_PHP5) {
			$doc =& JFactory::getDocument();
		} else {
			$doc = JFactory::getDocument();
		}
		$doc->addScriptDeclaration( "<!--\n".$js."\n//-->\n" );

		$this->assignRef('total',$total);
		$this->assignRef('rows',$rows);
		$this->assignRef('fullCart',$fullCart);
		$this->assignRef('config',$config);
		$cart=hikashop_get('helper.cart');
		$this->assignRef('cart',$cart);
		$this->assignRef('currencyHelper',$currencyClass);
		$cart->cartCount(true);

		$params = new hikaParameter;
		$default_params = $config->get('default_params');
		foreach($default_params as $k => $v){
			$params->set($k,$v);
		}
		$params->set('show_delete',$config->get('checkout_cart_delete',1));
		$this->assignRef('params',$params);

		ob_start();
		$cart->getJS($url,false);
		$notice_html = ob_get_clean();
		$this->assignRef('notice_html',$notice_html);
		if(hikashop_level(2)){
			$null=null;
			$fieldsClass=hikashop_get('class.field');
			$itemFields = $fieldsClass->getFields('frontcomp',$null,'item','checkout&task=state');
			$this->assignRef('itemFields',$itemFields);
			$this->assignRef('fieldsClass',$fieldsClass);
		}
		JHTML::_('behavior.tooltip');
		if($cart_type == 'cart'){
			$title = JText::_('CARTS');
		}else{
			$title = JText::_('WISHLISTS');
		}
		hikashop_setPageTitle($title);
	}

	function showcarts(){
		$app = JFactory::getApplication();
		$config = hikashop_config();
		$menus	= $app->getMenu();
		$menu	= $menus->getActive();
		global $Itemid;
		if(empty($menu)){
			if(!empty($Itemid)){
				$menus->setActive($Itemid);
				$menu = $menus->getItem($Itemid);
			}
		}

		if (is_object( $menu) && is_object( $menu->params )) {
			$cart_type = $menu->params->get('cart_type');
		}
		if(!empty($cart_type)){
			hikaInput::get()->set('cart_type',$cart_type);
		}else{
			$cart_type = hikaInput::get()->getString('cart_type','cart');
			if(!in_array($cart_type, array('cart','wishlist'))) $cart_type = 'cart';
		}

		$this->assignRef('cart_type', $cart_type);

		$pageInfo = new stdClass();
		$pageInfo->filter = new stdClass();
		$pageInfo->filter->order = new stdClass();
		$pageInfo->limit = new stdClass();
		$pageInfo->filter->order->value = $app->getUserStateFromRequest( $this->paramBase.".filter_order", 'filter_order',	'a.cart_id','cmd' );
		$pageInfo->filter->order->dir	= $app->getUserStateFromRequest( $this->paramBase.".filter_order_Dir", 'filter_order_Dir',	'desc',	'word' );
		$pageInfo->search = $app->getUserStateFromRequest( $this->paramBase.".search", 'search', '', 'string' );
		$pageInfo->search = JString::strtolower(trim($pageInfo->search));
		$pageInfo->limit->start = $app->getUserStateFromRequest( $this->paramBase.'.limitstart', 'limitstart', 0, 'int' );
		$oldValue = $app->getUserState($this->paramBase.'.list_limit');
		if(empty($oldValue)){
			$oldValue =$app->getCfg('list_limit');
		}
		$pageInfo->limit->value = $app->getUserStateFromRequest( $this->paramBase.'.list_limit', 'limit', $app->getCfg('list_limit'), 'int' );
		if($oldValue!=$pageInfo->limit->value){
			$pageInfo->limit->start = 0;
			$app->setUserState($this->paramBase.'.limitstart',0);
		}

		$database = JFactory::getDBO();
		$searchMap = array('a.cart_id','a.cart_name','a.cart_type');

		if(hikashop_loadUser() == null){
			global $Itemid;
			$url = '';
			if(!empty($Itemid)){
				$url='&Itemid='.$Itemid;
			}
			if(!HIKASHOP_J16){
				$url = 'index.php?option=com_user&view=login'.$url;
			}else{
				$url = 'index.php?option=com_users&view=login'.$url;
			}
			if($config->get('enable_multicart','0'))
				$app->redirect(JRoute::_($url.'&return='.urlencode(base64_encode(hikashop_currentUrl('',false))),false));
			else
				$app->redirect(JRoute::_($url.'&return='.base64_encode(hikashop_completeLink('cart&task=showcart&cart_type='.$cart_type.'&Itemid='.$Itemid,false,false,true)),false));
			return false;
		}

		$user = hikashop_loadUser(true);
		if(isset($user->user_id))
			$user->id = $user->user_id;
		else {
			if(empty($user)) $user = new stdClass();
			$user->id = 0 ;
		}
		$session = JFactory::getSession();
		if($session->getId()){
			$user->session = $session->getId();
		}else{
			$user->session = '';
		}
		if(hikashop_loadUser() == null){
			$filters = array('a.session_id='.$database->Quote($user->session).' AND a.cart_type ='.$database->quote($cart_type));
		}else{
			$filters = array('(a.user_id='.(int)$user->id.' OR a.session_id='.$database->Quote($user->session).') AND a.cart_type ='.$database->quote($cart_type));
		}
		$groupBy = 'GROUP BY a.cart_id';
		$order = '';
		if(!empty($pageInfo->filter->order->value)){
			$order = 'ORDER BY a.cart_id ASC';
		}
		if(!empty($pageInfo->search)){
			$searchVal = '\'%'.hikashop_getEscaped(JString::strtolower(trim($pageInfo->search)),true).'%\'';
			$filter = implode(" LIKE $searchVal OR ",$searchMap)." LIKE $searchVal";
			$filters[] =  $filter;
		}
		$from = 'FROM '.hikashop_table('cart').' AS a';
		$cartProduct = 'LEFT JOIN '.hikashop_table('cart_product').' AS b ON a.cart_id=b.cart_id';
		$where = 'WHERE ('.implode(') AND (',$filters).') AND a.cart_type ='.$database->quote($cart_type);
		$query = $from.' '.$where.' '.$groupBy.' '.$order; //'.$cartProduct.'
		$database->setQuery('SELECT a.* '.$query);
		$rows = $database->loadObjectList();
		$database->setQuery('SELECT COUNT(*) '.$from.' '.$where);
		$currencyClass = hikashop_get('class.currency');
		$this->assignRef('currencyHelper',$currencyClass);


		$module = hikashop_get('helper.module');
		$module->initialize($this);
		$currencyClass = hikashop_get('class.currency');
		$class = hikashop_get('class.cart');
		$productClass = hikashop_get('class.product');
		$main_currency = (int)$config->get('main_currency',1);
		$currency_id = hikashop_getCurrency();
		if($config->get('tax_zone_type','shipping')=='billing'){
			$zone_id = hikashop_getZone('billing');
		}else{
			$zone_id = hikashop_getZone('shipping');
		}
		$discount_before_tax = (int)$config->get('discount_before_tax',0);

		$cids = array();
		foreach($rows as $row){
			if($row->cart_id != null)
				$cids[] = $row->cart_id;
		}
		$filters = '';
		$filters = array('a.cart_id IN('.implode(",",$cids).')');
		$order = '';
		if(!empty($pageInfo->filter->order->value)){
			$order = ' ORDER BY cart_id ASC';
		}

		$product = ' LEFT JOIN '.hikashop_table('product').' AS b ON a.product_id=b.product_id';
		$query = 'FROM '.hikashop_table('cart_product').' AS a '.$product.' WHERE ('.implode(') AND (',$filters).') '.$order;
		$database->setQuery('SELECT a.*,b.* '.$query);
		if(!empty($cids)){
			$products = $database->loadObjectList();

			$ids = array();
			foreach($products as $row){
				$ids[] = $row->product_id;
			}
			$row_1 = 0;
			foreach($products as $k => $row){
				$currencyClass->getPrices($row,$ids,$currency_id,$main_currency,$zone_id,$discount_before_tax);

				if(!isset($row->prices[0]->price_value)){
					if(isset($row_1->prices[0]))
						$row->prices[0] = $row_1->prices[0];
				}
				$products[$k]->hide = 0;
				if($row->product_type == 'variant'){
					$l = --$k;
					if(isset($products[$l])){
						if(!isset($products[$l]) || !is_object($products[$l])){
							$products[$l] = new stdClass();
						}
						$products[$l]->hide = 1;
					}
				}
				$row_1 = $row;
			}

			$currentId = 0;
			$values = null;
			$price = 0;
			$price_with_tax = 0;
			$quantity = 0;
			$currency = hikashop_getCurrency();
			foreach($products as $product){
				if(isset($product->cart_id) && isset($product->product_id)){
					if($product->cart_id != $currentId){
						$price = 0;
						$price_with_tax = 0;
						$quantity = 0;
						$currentId = $product->cart_id;
						if(isset($product->prices[0]->price_currency_id))
							$currency = $product->prices[0]->price_currency_id;
					}

					if(isset($product->prices[0])){
						$price += $product->cart_product_quantity * $product->prices[0]->price_value;
					}
					if(isset($product->prices[0]->price_value_with_tax)){
						$price_with_tax += $product->cart_product_quantity * $product->prices[0]->price_value_with_tax;
					}
					if(!isset($product->prices[0]->price_value)){
						$variant = new stdClass();
						$variant->product_parent_id = $product->product_parent_id;
						$variant->quantity = $product->cart_product_quantity;
					}
					if(isset($variant) && isset($product->prices[0]) && $product->product_id == $variant->product_parent_id){
						$price += $variant->quantity * $product->prices[0]->price_value;
						$price_with_tax += $variant->quantity * $product->prices[0]->price_value_with_tax;
					}
					$quantity += $product->cart_product_quantity;
					if(!isset($values[$currentId])) $values[$currentId] = new stdClass();
					$values[$currentId]->price = $price;
					$values[$currentId]->price_with_tax = isset($price_with_tax)?$price_with_tax:$price;
					$values[$currentId]->quantity = $quantity;
					$values[$currentId]->currency = $currency;
				}
			}
			$totalCart = 0;
			$limit = 0;
			foreach($rows as $k => $row){
				if($limit >= (int)$pageInfo->limit->start && $limit <(int)$pageInfo->limit->value && isset($values[$row->cart_id]) && $values[$row->cart_id] != null){
					$rows[$k]->price = $values[$row->cart_id]->price;
					$rows[$k]->price_with_tax = $values[$row->cart_id]->price_with_tax;
					$rows[$k]->quantity = $values[$row->cart_id]->quantity;
					$rows[$k]->currency = $values[$row->cart_id]->currency;
					$totalCart++;
				}else{
					unset($rows[$k]);
					$limit--;
				}
				$limit++;
			}
		}

		$pageInfo->elements = new stdClass();
		$pageInfo->elements->total = count($rows);
		if(!empty($pageInfo->search)){
			$rows = hikashop_search($pageInfo->search,$rows,'cart_id');
		}
		$pageInfo->elements->page = count($rows);
		if(!$pageInfo->elements->page){
			if(hikashop_loadUser()!= null){
				$app = JFactory::getApplication();
				if($cart_type == 'cart')
					$app->enqueueMessage(JText::_('HIKA_NO_CARTS_FOUND'));
				else
					$app->enqueueMessage(JText::_('HIKA_NO_WISHLISTS_FOUND'));
			}
		}
		jimport('joomla.html.pagination');
		$pagination = hikashop_get('helper.pagination', $pageInfo->elements->total, $pageInfo->limit->start, $pageInfo->limit->value);
		$pagination->hikaSuffix = '';
		$this->assignRef('pagination',$pagination);
		$this->assignRef('pageInfo',$pageInfo);

		$cart=hikashop_get('helper.cart');
		$this->assignRef('cart',$cart);
		$this->assignRef('config',$config);
		$this->assignRef('carts',$rows);
		if($cart_type == 'cart'){
			$title = JText::_('CARTS');
		}else{
			$title = JText::_('WISHLISTS');
		}
		hikashop_setPageTitle($title);
	}

	function printcart(){
		$this->showcart();
	}

	function _getCheckoutURL(){
		global $Itemid;
		$url_itemid='';
		if(!empty($Itemid)){
			$url_itemid='&Itemid='.$Itemid;
		}
		return hikashop_completeLink('checkout'.$url_itemid,false,true);
	}

	function init($cart=false){
		$config =& hikashop_config();
		$url = $config->get('redirect_url_after_add_cart','stay_if_cart');
		switch($url){
			case 'checkout':
				$url = $this->_getCheckoutURL();
				break;
			case 'stay_if_cart':
				$url='';
				if(!$cart){
					$url = $this->_getCheckoutURL();
					break;
				}
			case 'ask_user':
			case 'stay':
				$url='';
			case '':
			default:
				if(empty($url)){
					$url = hikashop_currentURL('return_url',false);
				}
				break;
		}
		return urlencode($url);
	}

	function addCharacteristics(&$element,&$mainCharacteristics,&$characteristics){
		$element->characteristics = @$mainCharacteristics[$element->product_id][0];
		if(!empty($element->characteristics) && is_array($element->characteristics)){
			foreach($element->characteristics as $k => $characteristic){
				if(!empty($mainCharacteristics[$element->product_id][$k])){
					$element->characteristics[$k]->default=end($mainCharacteristics[$element->product_id][$k]);
				}else{
					$app = JFactory::getApplication();
					$app->enqueueMessage('The default value of one of the characteristics of that product isn\'t available as a variant. Please check the characteristics and variants of that product');
				}
			}
		}
		if(!empty($element->variants)){
			foreach($characteristics as $characteristic){
				foreach($element->variants as $k => $variant){
					if($variant->product_id==$characteristic->variant_product_id){
						$element->variants[$k]->characteristics[$characteristic->characteristic_parent_id]=$characteristic;
						$element->characteristics[$characteristic->characteristic_parent_id]->values[$characteristic->characteristic_id]=$characteristic;
						if($this->selected_variant_id && $variant->product_id==$this->selected_variant_id){
							$element->characteristics[$characteristic->characteristic_parent_id]->default=$characteristic;
						}
					}
				}
			}
			if(isset($_REQUEST['hikashop_product_characteristic'])){
				if(is_array($_REQUEST['hikashop_product_characteristic'])){
					JArrayHelper::toInteger($_REQUEST['hikashop_product_characteristic']);
					$chars = $_REQUEST['hikashop_product_characteristic'];
				}else{
					$chars = hikaInput::get()->getCmd('hikashop_product_characteristic','');
					$chars = explode('_',$chars);
				}
				if(!empty($chars)){
					foreach($element->variants as $k => $variant){
						$chars = array();
						foreach($variant->characteristics as $val){
							$i = 0;
							$ordering = @$element->characteristics[$val->characteristic_parent_id]->ordering;
							while(isset($chars[$ordering])&& $i < 30){
								$i++;
								$ordering++;
							}
							$chars[$ordering] = $val;
						}
						ksort($chars);
						$element->variants[$k]->characteristics=$chars;
						$variant->characteristics=$chars;
						$choosed = true;
						foreach($variant->characteristics as $characteristic){
							$ok = false;
							foreach($chars as $k => $char){
								if(!empty($char)){
									if($characteristic->characteristic_id==$char){
										$ok = true;
										break;
									}
								}
							}
							if(!$ok){
								$choosed=false;
							}else{
								$element->characteristics[$characteristic->characteristic_parent_id]->default=$characteristic;
							}
						}
						if($choosed){
							break;
						}
					}
				}
			}
			foreach($element->variants as $k => $variant){
				$temp=array();
				foreach($element->characteristics as $k2 => $characteristic2){
					if(!empty($variant->characteristics)){
						foreach($variant->characteristics as $k3 => $characteristic3){
							if($k2==$k3){
								$temp[$k3]=$characteristic3;
								break;
							}
						}
					}
				}
				$element->variants[$k]->characteristics=$temp;
			}
		}
	}
}
