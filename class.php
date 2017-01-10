<?php

/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 02.10.2015
 * Time: 19:12
 *
 * @author Shulaev (pavel.shulaev@gmail.com)
 */
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Main;
use \Bitrix\Main\Localization\Loc as Loc;
use \Bitrix\Main\ArgumentNullException;
use Bitrix\Main\SystemException;
use \Bitrix\Iblock\PropertyIndex\Element;

if (!\Bitrix\Main\Loader::includeModule('iblock'))
	throw new SystemException(Loc::getMessage('ROVER_DN_MODULES_NOT_INSTALLED'));

/**
 * Class RoverSlider
 *
 * @author Pavel Shulaev (http://rover-it.me)
 */
class RoverSlider extends CBitrixComponent
{
	const WIDTH_DEFAULT     = 1280;
	const HEIGHT_DEFAULT    = 720;
	const SHARPEN_DEFAULT   = 30;
	const QUALITY_DEFAULT   = 85;

	/**
	 * @throws Main\LoaderException
	 * @throws Main\SystemException
	 * @author Shulaev (pavel.shulaev@gmail.com)
	 */
	public function onIncludeComponentLang()
	{
		$this->includeComponentLang(basename(__FILE__));
		Loc::loadMessages(__FILE__);
	}

	/**
	 * @param $arParams
	 * @return mixed
	 * @author Shulaev (pavel.shulaev@gmail.com)
	 */
	public function onPrepareComponentParams($arParams)
	{
		//element
		$arParams['IBLOCK_TYPE']        = $arParams['IBLOCK_TYPE']
			?: null;
		$arParams['IBLOCK_ID']          = intval($arParams['IBLOCK_ID']);
		$arParams['SECTION_ID']         = intval($arParams['SECTION_ID']);
		$arParams['ELEMENT_ID']         = intval($arParams['ELEMENT_ID']);
		$arParams['FIGURE']             = intval($arParams['FIGURE']);
		$arParams['FACT']               = intval($arParams['FACT']);
		$arParams['LINK_URL']           = intval($arParams['LINK_URL']);
		$arParams['LINK_NAME']          = intval($arParams['LINK_NAME']);

		$arParams['RESIZE']             = $arParams['RESIZE'] == 'Y'
			? 'Y' : 'N';
		$arParams['RESIZE_WIDTH']       = intval($arParams['RESIZE_WIDTH']);
		$arParams['RESIZE_HEIGHT']      = intval($arParams['RESIZE_HEIGHT']);
		$arParams['RESIZE_SHARPEN']     = intval($arParams['RESIZE_SHARPEN'])
			?: self::SHARPEN_DEFAULT;
		$arParams['RESIZE_QUALITY']     = intval($arParams['RESIZE_QUALITY'])
			?: self::QUALITY_DEFAULT;

		if(!isset($arParams["CACHE_TIME"]))
			$arParams["CACHE_TIME"] = 8640000;

		return $arParams;
	}

	/**
	 * @throws Main\ArgumentNullException
	 * @author Shulaev (pavel.shulaev@gmail.com)
	 */
	protected function checkParams()
	{
		//element
		if (!$this->arParams['IBLOCK_ID'])
			throw new ArgumentNullException('IBLOCK_ID');
	}

	/**
	 * @return array
	 * @author Pavel Shulaev (http://rover-it.me)
	 */
	protected function getFilter()
	{
		$filter = [
			'=ACTIVE'       => 'Y',
			'=IBLOCK_ID'    => $this->arParams['IBLOCK_ID']
		];

		if ($this->arParams['SECTION_ID'])
			$filter['=IBLOCK_SECTION_ID'] = $this->arParams['SECTION_ID'];

		if ($this->arParams['ELEMENT_ID'])
			$filter['=ID'] = $this->arParams['ELEMENT_ID'];

		return $filter;
	}

	/**
	 * @return mixed
	 * @throws Main\ArgumentNullException
	 * @author Shulaev (pavel.shulaev@gmail.com)
	 */
	protected function getResult()
	{
		$query = [
			'filter' => $this->getFilter(),
			'order' => ['SORT' => 'ASC'],
			'select' => [
				'ID',
				'NAME',
				'IBLOCK_ID',
				'PREVIEW_PICTURE',
				'DETAIL_PICTURE',
				'PREVIEW_TEXT',
				'DETAIL_TEXT']
		];

		$result = \Bitrix\Iblock\ElementTable::getList($query);
		$this->arResult['ITEMS'] = [];

		while ($item = $result->fetch())
		{
			$item = $this->preparePicture($item);

			// prepare text
			if (strlen($item['DETAIL_TEXT']) && !strlen($item['PREVIEW_TEXT']))
				$item['PREVIEW_TEXT'] = $item['DETAIL_TEXT'];

			// add props
			$item = $this->addProps($item);

			$this->arResult['ITEMS'][] = $item;
		}
	}

	/**
	 * @param $item
	 * @return mixed
	 * @author Pavel Shulaev (http://rover-it.me)
	 */
	protected function preparePicture($item)
	{
		if (empty($item['PREVIEW_PICTURE']) && empty($item['DETAIL_PICTURE']))
			return $item;

		// prepare picture
		if (empty($item['PREVIEW_PICTURE']))
			$item['PREVIEW_PICTURE'] = $item['DETAIL_PICTURE'];

		$item['PREVIEW_PICTURE'] = \CFile::GetFileArray($item['PREVIEW_PICTURE']);

		if ($this->arParams['RESIZE'] == 'Y')
			$item['PREVIEW_PICTURE'] = $this->resize($item['PREVIEW_PICTURE']);

		return $item;
	}

	/**
	 * @param array $item
	 * @return mixed
	 * @throws ArgumentNullException
	 * @author Pavel Shulaev (http://rover-it.me)
	 */
	protected function addProps(array $item)
	{
		if (!isset($item['ID']) || empty($item['ID']))
			throw new ArgumentNullException('elementId');

		$elementId  = $item['ID'];

		if (is_null($elementId))
			throw new ArgumentNullException('elementId');

		$property = new Element($this->arParams['IBLOCK_ID'],
			$elementId);

		$property->loadFromDatabase();

		$item['FIGURE'] = intval($this->arParams['FIGURE'])
			? reset($property->getPropertyValues($this->arParams['FIGURE']))
			: '';

		$item['FACT'] = intval($this->arParams['FACT'])
			? reset($property->getPropertyValues($this->arParams['FACT']))
			: '';

		$item['LINK_URL'] = intval($this->arParams['LINK_URL'])
			? reset($property->getPropertyValues($this->arParams['LINK_URL']))
			: '';

		$item['LINK_NAME'] = intval($this->arParams['LINK_NAME'])
			? reset($property->getPropertyValues($this->arParams['LINK_NAME']))
			: '';

		return $item;
	}

	/**
	 * @param array $picture
	 * @return array
	 * @author Pavel Shulaev (http://rover-it.me)
	 */
	protected function resize(array $picture)
	{
		if ((!$this->arParams['RESIZE_HEIGHT'])
			|| (!$this->arParams['RESIZE_WIDTH']))
			return $picture;

		$result = \CFile::ResizeImageGet(
			$picture,
			[
				"width"     => $this->arParams['RESIZE_WIDTH'],
				"height"    => $this->arParams['RESIZE_HEIGHT']
			],
			BX_RESIZE_IMAGE_PROPORTIONAL_ALT,
			true,
			[
				"name"      => "sharpen",
				"precision" => $this->arParams['RESIZE_SHARPEN']
			],
			false,
			$this->arParams['RESIZE_QUALITY']
		);

		if ($result) {
			$picture['SRC']      = $result["src"];
			$picture['WIDTH']    = $result["width"];
			$picture['HEIGHT']   = $result["height"];
			$picture['SIZE']     = $result["size"];
		}

		return $picture;
	}

	/**
	 * @author Shulaev (pavel.shulaev@gmail.com)
	 */
	public function executeComponent()
	{
		$this->setFrameMode(true);
		if ($this->StartResultCache($this->arParams['CACHE_TIME'])) {
			try {
				$this->setFrameMode(true);
				$this->checkParams();
				$this->getResult();
				$this->includeComponentTemplate();
			} catch (Exception $e) {
				ShowError($e->getMessage());
			}
		}
	}
}