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
use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$item = reset($arResult['ITEMS']);
if (!$item){
    ShowError('no items');
    return;
}

?><div class="container-fluid">
    <div class="row rover-s-vm">
        <div class="col-sm-3 rover-s-vm__image" style="background-image: url('<?=$item['PREVIEW_PICTURE']['SRC']?>')">
            <div class="rover-s-vm__image-bg"></div>
            <div class="rover-s-vm__date-container">
                <div class="rover-s-vm__date-day"><?=$item['DATE_DAY']?></div>
                <div class="rover-s-vm__date-month"><?=Loc::getMessage('rover-s-vm__month-' . intval($item['DATE_MONTH']))?></div>
            </div>
        </div>
        <div class="col-sm-9 rover-s-vm__body">
            <div class=" rover-s-vm__text"><?=$item['PREVIEW_TEXT']?></div>
            <div class=" rover-s-vm__button">
                <a class="btn btn-mclass" href="<?=$item['LINK']?>"><?=Loc::getMessage('rover-s-vm__more')?></a>
            </div>
        </div>
    </div>
</div>