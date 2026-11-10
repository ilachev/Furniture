<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use TQ\Tools\Hiload as TQHiloadTools;

Loader::includeModule("tq.tools");
/**
 * @var CBitrixComponentTemplate $this
 * @var CatalogElementComponent $component
 */

foreach ($arResult['PROPERTIES'] as &$arProp) {
    if (!empty($arProp["USER_TYPE_SETTINGS"]["TABLE_NAME"]) && !empty($arProp['VALUE'])) {
        $arProp['ITEM_VALUE'] = TQHiloadTools::getHLBlockItems(['TABLE_NAME' => $arProp["USER_TYPE_SETTINGS"]["TABLE_NAME"]],
            ['UF_XML_ID' => $arProp['VALUE']]);
        if (!is_array($arProp['VALUE'])) {
            $arProp['ITEM_VALUE'] = $arProp['ITEM_VALUE'][key($arProp['ITEM_VALUE'])];
            if (!empty($arProp['ITEM_VALUE']['UF_FILE'])) {
                $arProp['ITEM_VALUE']['UF_FILE'] = CFile::GetFileArray($arProp['ITEM_VALUE']['UF_FILE']);
            }
        } else {
            foreach ($arProp['ITEM_VALUE'] as &$item) {
                if (!empty($item['UF_FILE'])) {
                    $item['UF_FILE'] = CFile::GetFileArray($item['UF_FILE']);
                }
            }
            unset($item);
        }
    }
}

unset($arProp);
$component = $this->getComponent();
$arParams = $component->applyTemplateModifications();

$arResult['CHUNKED_DISPLAY_PROPERTIES'] = array_chunk($arResult['DISPLAY_PROPERTIES'] ?? [], 3);
if (!empty($arResult['PROPERTIES']['MANUFACTURER']['ITEM_VALUE']['UF_FILE'])) {
    $arFileTmp = CFile::ResizeImageGet(
        $arResult['PROPERTIES']['MANUFACTURER']['ITEM_VALUE']['UF_FILE']['ID'],
        ['width' => 114, 'height' => 66],
        BX_RESIZE_IMAGE_PROPORTIONAL
    );
    $arResult['PROPERTIES']['MANUFACTURER']['ITEM_VALUE']['RESIZED_FILE'] = $arFileTmp['src'];
}
if (!empty($arResult['PROPERTIES']['ADDITIONALS_PHOTOS']['VALUE'])) {
    $pictures = array_chunk($arResult['PROPERTIES']['ADDITIONALS_PHOTOS']['VALUE'], 4);
    foreach ($pictures as $key => $chunk) {
        foreach ($chunk as $picKey => $picID) {
            switch ($picKey) {
                case 0:
                case 3:
                    $sizes = ['width' => 868, 'height' => 1040];
                    break;
                case 1:
                    $sizes = ['width' => 868, 'height' => 485];
                    break;
                case 2:
                    $sizes = ['width' => 868, 'height' => 670];
                    break;
            }
            if (!empty($sizes)) {
                $arFileTmp = CFile::ResizeImageGet(
                    $picID,
                    $sizes,
                    BX_RESIZE_IMAGE_PROPORTIONAL
                );
                if (!empty($arFileTmp['src'])) {
                    $arResult['ADDITIONALS_PHOTOS'][$key][$picKey] = $arFileTmp['src'];
                }
            }
        }
    }
}

if (!empty($arResult['PROPERTIES']['MORE_PHOTO']['VALUE'])) {
    foreach ($arResult['PROPERTIES']['MORE_PHOTO']['VALUE'] as $picID) {
        $arFileTmp = CFile::ResizeImageGet(
            $picID,
            ['width' => 1350, 'height' => 455],
            BX_RESIZE_IMAGE_PROPORTIONAL
        );
        $arResult['SLIDER'][] = [
            'FULL' => CFile::GetPath($picID),
            'PREVIEW' => $arFileTmp['src']
        ];
    }
}

$cp = $this->__component; // объект компонента

if (is_object($cp)) {

    $cp->arResult['INTERESED'] = $arResult['PROPERTIES']['INTERESED']['VALUE'];
    $cp->SetResultCacheKeys(array('INTERESED'));
}
