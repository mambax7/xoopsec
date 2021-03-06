<?php
/**
 * Created by JetBrains PhpStorm.
 * Copyright (c): Y.Sakai at bluemooninc / GPL V.3 licence
 * Date: 2013/03/11
 * Time: 11:41
 * To change this template use File | Settings | File Templates.
 */
class BmCart_Search
{
	protected $result = array();

	function __construct(){
	}
	/**
	 * JAN/EAN/UPC/ISBN barcode search
	 *
	 * @param $criteria
	 * @param $keyword
	 */
	function bmcart_make_seqrch_sql(&$criteria,$keyword){
		if ( preg_match("/[0-9]{10,13}/", $keyword )) {
			$criteria->add(new Criteria('barcode', $keyword . '%', 'LIKE'));
		} else {
			$criteria->add(new Criteria('item_name', '%' . $keyword . '%', 'LIKE'));
		}
	}
	/**
	 * Get small image
	 *
	 * @param $item_id
	 * @param $imageHandler
	 * @return string
	 */
	function bmcart_getItemImage($item_id,&$imageHandler){
		$criteria = new CriteriaCompo();
		$criteria->add(new Criteria('item_id', $item_id));
		$criteria->addsort('weight','ASC');
		$imageObjects = $imageHandler->getobjects($criteria);
		if ($imageObjects) {
			return "s_" . $imageObjects[0]->getVar('image_filename');
		}
	}
	function bmcart_getItemInfo($item_id,&$itemHandler){
		$itemObject = $itemHandler->get($item_id);
		return $itemObject ? $itemObject->getVar('item_name') : NULL;
	}

	function &bmcart_commentSearch($uid){
		$criteria = new CriteriaCompo();
		$criteria->add( new Criteria('com_uid',$uid) );
		$criteria->addSort('com_created', "DESC" );
		$commentHandler =& xoops_gethandler('comment');
		$objects = $commentHandler->getObjects($criteria);
		$itemHandler = xoops_getmodulehandler('item', 'bmcart');
		$imageHandler = xoops_getmodulehandler('itemImages', 'bmcart');
		foreach ($objects as $object) {
			$item_id = $object->get('com_itemid');
			$imageLink = $this->bmcart_getItemImage($item_id,$imageHandler);
			$item_name = $this->bmcart_getItemInfo($item_id,$itemHandler);
			$this->result[] = array(
				'image' => $imageLink,
				'link' => 'itemList/itemDetail/' . $item_id,
				'title' => $item_name,
				'body' => $object->getVar('com_text'),
				'time' => $object->getVar('com_created'),
				'time_desc' => _MB_BMCART_COMMENT_ITEM
			);
		}
	}

	/**
	 * For order items using account information
	 *
	 * @param $uid
	 * @return array
	 */
	function &bmcart_orderSearch($uid){
		$criteria = new CriteriaCompo();
		$handler = xoops_getmodulehandler('order', 'bmcart');
		$criteria->add(new Criteria('uid',$uid));
		$criteria->addsort('order_date','DESC');
		$objects = $handler->getobjects($criteria);
		$itemHandler = xoops_getmodulehandler('item', 'bmcart');
		$orderItemsHandler = xoops_getmodulehandler('OrderItems', 'bmcart');
		$imageHandler = xoops_getmodulehandler('itemImages', 'bmcart');
		foreach ($objects as $object) {
			$criteria = new Criteria('order_id', $object->getVar('order_id'));
			$orderItemsObjects = $orderItemsHandler->getobjects($criteria);
			foreach($orderItemsObjects as $orderItemObject){
				$item_id = $orderItemObject->getVar('item_id');
				$imageLink = $this->bmcart_getItemImage($item_id,$imageHandler);
				$item_name = $this->bmcart_getItemInfo($item_id,$itemHandler);
				$this->result[] = array(
					'image' => $imageLink,
					'link' => 'itemList/itemDetail/' . $item_id,
					'title' => $item_name,
					'body' => '',
					'time' => $object->getVar('order_date'),
					'time_desc' => _MB_BMCART_ORDER_ITEM
				);
			}
		}
	}
	function searchByUid($uid){
		$this->bmcart_orderSearch($uid);
		$this->bmcart_commentSearch($uid);
	}
	function &getSearchResults(){
		$time = array();
		foreach($this->result as $key=>$row){
			$time[$key] = $row['time'];
		}
		array_multisort($time,SORT_DESC,$this->result);
		return $this->result;
	}
	/**
	 * title keyword and barcode search
	 *
	 * @param $keywords
	 * @param $andor
	 * @return array
	 */
	function &bmcart_itemSearch($keywords, $andor=""){
		$criteria = new CriteriaCompo();
		// where by keywords
		if (is_array($keywords) && count($keywords) > 0) {
			switch (strtolower($andor)) {
				case "and" :
					foreach ($keywords as $keyword) {
						$this->bmcart_make_seqrch_sql($criteria,$keyword);
					}
					break;
				case "or" :
					foreach ($keywords as $keyword) {
						$this->bmcart_make_seqrch_sql($criteria,$keyword);
					}
					break;
				default :
					$this->bmcart_make_seqrch_sql($criteria,$keywords[0]);
					break;
			}
		}
		$imageHandler = xoops_getmodulehandler('itemImages', 'bmcart');
		$handler = xoops_getmodulehandler('item', 'bmcart');
		$objects = $handler->getobjects($criteria);
		$ret = array();
		foreach ($objects as $object) {
			$criteria = new Criteria('item_id', $object->getVar('item_id'));
			$imageObjects = $imageHandler->getobjects($criteria);
			$smallImageFile = $mediumImageFile = "";
			if ($imageObjects) {
				$smallImageFile = "s_" . $imageObjects[0]->getVar('image_filename');
				$mediumImageFile = "m_" . $imageObjects[0]->getVar('image_filename');
			}
			$title = sprintf("%s (&yen;%s-)", $object->getVar('item_name'), number_format($object->getVar('price')));
			$ret[] = array(
				'image' => $smallImageFile,
				'mediumImage' => $mediumImageFile,
				'link' => 'itemList/itemDetail/' . $object->getVar('item_id'),
				'title' => $title,
				'time' => $object->getVar('last_update')
			);
		}
		return $ret;
	}
}
