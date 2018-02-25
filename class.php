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
	const DEFAULT_WIDTH     = 1280;
	const DEFAULT_HEIGHT    = 720;
	const DEFAULT_SHARPEN   = 30;
	const DEFAULT_QUALITY   = 85;

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
	 * @param $params
	 * @return mixed
	 * @author Shulaev (pavel.shulaev@gmail.com)
	 */
	public function onPrepareComponentParams($params)
	{
		//element
		$params['IBLOCK_TYPE']      = trim($params['IBLOCK_TYPE']);
		$params['IBLOCK_ID']        = intval($params['IBLOCK_ID']);
		$params['SECTION_ID']       = intval($params['SECTION_ID']);

		$params['RESIZE']           = $params['RESIZE'] == 'Y' ?: 'N';
		$params['RESIZE_WIDTH']     = intval($params['RESIZE_WIDTH']) ?: self::DEFAULT_WIDTH;
		$params['RESIZE_HEIGHT']    = intval($params['RESIZE_HEIGHT']) ?: self::DEFAULT_HEIGHT;
		$params['RESIZE_SHARPEN']   = intval($params['RESIZE_SHARPEN']) ?: self::DEFAULT_SHARPEN;
		$params['RESIZE_QUALITY']   = intval($params['RESIZE_QUALITY']) ?: self::DEFAULT_QUALITY;

		if(!isset($params["CACHE_TIME"]))
			$params["CACHE_TIME"] = 8640000;

		return $params;
	}

	/**
	 * @throws Main\ArgumentNullException
	 * @author Shulaev (pavel.shulaev@gmail.com)
	 */
	protected function checkParams()
	{
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
			'ACTIVE'       => 'Y',
			'IBLOCK_ID'    => $this->arParams['IBLOCK_ID']
		];

		if ($this->arParams['SECTION_ID'])
			$filter['IBLOCK_SECTION_ID'] = $this->arParams['SECTION_ID'];

		if (!empty($this->arParams['ELEMENT_ID']))
			$filter['ID'] = $this->arParams['ELEMENT_ID'];

		return $filter;
	}

    /**
     * @return array
     * @author Pavel Shulaev (https://rover-it.me)
     */
	protected function getItems()
    {
        $sort   = ['SORT' => 'ASC'];
        $filter = $this->getFilter();
        $select = [
            'ID',
            'NAME',
            'IBLOCK_ID',
            'PREVIEW_PICTURE',
            'DETAIL_PICTURE',
            'PREVIEW_TEXT',
            'DETAIL_TEXT'
        ];

        $items  = \CIBlockElement::GetList($sort, $filter, false, false, $select);
        $result = [];

        while ($itemRaw = $items->GetNextElement())
        {
            $item = $itemRaw->GetFields();
            $item = $this->preparePicture($item);

            // prepare text
            if (strlen($item['DETAIL_TEXT']) && !strlen($item['PREVIEW_TEXT']))
                $item['PREVIEW_TEXT'] = $item['DETAIL_TEXT'];

            $item['PROPERTIES'] = $itemRaw->GetProperties();

            $result[] = $item;
        }

        return $result;
    }

	/**
	 * @return mixed
	 * @throws Main\ArgumentNullException
	 * @author Shulaev (pavel.shulaev@gmail.com)
	 */
	protected function getResult()
	{
	    $this->arResult['ITEMS'] = $this->getItems();
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
	 * @param array $picture
	 * @return array
	 * @author Pavel Shulaev (http://rover-it.me)
	 */
	protected function resize(array $picture)
	{
        if ($this->arParams['RESIZE'] != 'Y')
            return $picture;

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