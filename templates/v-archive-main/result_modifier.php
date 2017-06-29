<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
use \Bitrix\Main\Type\Date;

if (!$arParams['LINK_PROP'] && !$arParams['DATE_PROP'])
    return;

foreach ($arResult['ITEMS'] as &$item){

    $item['LINK'] = isset($item['PROPERTIES'][$arParams['LINK_PROP']]['VALUE'])
        ? $item['PROPERTIES'][$arParams['LINK_PROP']]['VALUE']
        : null;

    $item['DATE'] = null;

    if (isset($item['PROPERTIES'][$arParams['DATE_PROP']]['VALUE']))
        try{
            $item['DATE'] = new Date($item['PROPERTIES'][$arParams['DATE_PROP']]['VALUE']);
        } catch (\Exception $e) {}

    if ($item['DATE'] instanceof Date){
        $item['DATE_DAY']   = $item['DATE']->format('d');
        $item['DATE_MONTH'] = $item['DATE']->format('m');
    } else{
        $item['DATE_DAY']   = null;
        $item['DATE_MONTH'] = null;
    }
}
