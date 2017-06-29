<?php
use \Rover\Params;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Loader;
use \Bitrix\Main\SystemException;
use \Bitrix\Iblock\PropertyTable;
/**
 * @var array $arCurrentValues
 */
Loc::loadMessages(__FILE__);

if(!Loader::includeModule('rover.params'))
    throw new SystemException('Module rover.params not found');

if(!Loader::includeModule('iblock'))
    throw new SystemException('Module iblock not found');

$props = Params\Iblock::getProps($arCurrentValues['IBLOCK_ID'], [
    'add_filter' => ['PROPERTY_TYPE' => PropertyTable::TYPE_STRING],
    'template'  => ['{CODE}' => '[{CODE}] {NAME}']
]);

$arTemplateParameters = [
    "LINK_PROP"     => Array(
        "NAME"      => Loc::getMessage("rover-s__link-prop"),
        "TYPE"      => "LIST",
        "VALUES"    => $props,
    ),
    "DATE_PROP"     => Array(
        "NAME"      => Loc::getMessage("rover-s__date-prop"),
        "TYPE"      => "LIST",
        "VALUES"    => $props,
    ),
];