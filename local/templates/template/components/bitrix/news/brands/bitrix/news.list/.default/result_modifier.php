<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arParams */
/** @var array $arResult */

$arResult['CURRENT_SUMBOL'] = $arParams['SYMBOL'];

$select = Array('ID', 'NAME');
$filter = Array('IBLOCK_ID' => $arParams['IBLOCK_ID'], 'ACTIVE_DATE' => 'Y', 'ACTIVE' => 'Y');
$res = CIBlockElement::GetList(Array(), $filter, false, false, $select);
while ($ob = $res->Fetch()) {
    $symbol = mb_substr($ob["NAME"], 0, 1, "UTF-8");
    $arResult["ABCALF"][$symbol] = [
        'symbol' => $symbol,
        'link' => $APPLICATION->GetCurPageParam("symbol=$symbol", array("symbol"))
    ];
}
$arResult["ABCALF"] = $arResult["ABCALF"] ?? [];
ksort($arResult["ABCALF"]);

foreach ($arResult['ITEMS'] as &$arItem) {
    $arFileTmp = CFile::ResizeImageGet(
        $arItem['PREVIEW_PICTURE']['ID'],
        ['width' => 250, 'height' => 250],
        BX_RESIZE_IMAGE_PROPORTIONAL
    );
    $arItem['PREVIEW_PICTURE_RESIZED'] = $arFileTmp['src'];
}
unset($arItem);
?>
