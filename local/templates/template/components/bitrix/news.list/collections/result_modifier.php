<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

$intBrandsIblockID = false;

foreach ($arResult['ITEMS'] as $arItem) {
    if (!empty($arItem['PROPERTIES']['BRANDS']['LINK_IBLOCK_ID'])) {
        $intBrandsIblockID = $arItem['PROPERTIES']['BRANDS']['LINK_IBLOCK_ID'];
        break;
    }
}

if ($intBrandsIblockID) {
    $select = Array(
        'ID',
        'IBLOCK_ID',
        'NAME',
        'DETAIL_PAGE_URL',
        'PREVIEW_PICTURE',
        'PROPERTY_COLLECTION_PICTURE'
    );
    $filter = Array('IBLOCK_ID' => $intBrandsIblockID, 'ACTIVE_DATE' => 'Y', 'ACTIVE' => 'Y');
    $res = CIBlockElement::GetList(Array(), $filter, false, false, $select);
    while ($fields = $res->GetNext()) {
        if (!empty($fields['PROPERTY_COLLECTION_PICTURE_VALUE'])) {
            $arFileTmp = CFile::ResizeImageGet(
                $fields['PROPERTY_COLLECTION_PICTURE_VALUE'],
                ['width' => 1173, 'height' => 690],
                BX_RESIZE_IMAGE_PROPORTIONAL
            );
            $fields['COLLECTION_PICTURE'] = $arFileTmp['src'];
        }
        $arResult['BRANDS'][$fields['ID']] = $fields;
    }
}

$arResult['BRANDS'] = $arResult['BRANDS'] ?? [];
foreach ($arResult['ITEMS'] as &$arItem) {
    foreach (($arItem['PROPERTIES']['BRANDS']['VALUE'] ?? []) as $brandId) {
        if (isset($arResult['BRANDS'][$brandId])) {
            $arItem['BRANDS'][$brandId] = $arResult['BRANDS'][$brandId];
        }
    }
}
unset($arItem, $brandId);
$arResult['BRANDS_COUNT'] = count($arResult['BRANDS']);
?>