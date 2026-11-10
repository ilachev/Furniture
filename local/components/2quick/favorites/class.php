<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Application,
    Bitrix\Main\Loader,
    Bitrix\Main\Engine\Contract\Controllerable;

CJSCore::Init(["fx", "ajax"]);

class TqFavorites extends \CBitrixComponent implements Controllerable
{
    private $componentPage = '';

    public function configureActions()
    {
        return [];
    }

    private function getProducts()
    {
        CModule::IncludeModule('iblock');
        global $USER;
        $_SESSION['FAVORITES'] = array_diff((array)($_SESSION['FAVORITES'] ?? []), array(''));
        if (!empty($_SESSION['FAVORITES'])) {
            $select = Array(
                'ID',
                'IBLOCK_ID',
                'NAME',
                'PREVIEW_PICTURE',
                'PROPERTY_*',
                'DETAIL_PAGE_URL',
                'CATALOG_PRICE_1'
            );
            $filter = Array('ID' => $_SESSION['FAVORITES'], 'ACTIVE_DATE' => 'Y', 'ACTIVE' => 'Y');
            $res = \CIBlockElement::GetList(Array(), $filter, false, false, $select);
            while ($ob = $res->GetNextElement()) {
                $fields = $ob->GetFields();
                $arFileTmp = CFile::ResizeImageGet(
                    $fields['PREVIEW_PICTURE'],
                    ['width' => 93, 'height' => 90],
                    BX_RESIZE_IMAGE_PROPORTIONAL
                );
                $fields['PREVIEW_PICTURE_SRC'] = $arFileTmp['src'];
                $fields ['PROPERTIES'] = $ob->GetProperties();
                $fields['PRICE'] = \CCatalogProduct::GetOptimalPrice($fields['ID'], 1, $USER->GetUserGroupArray());
                $this->arResult['ITEMS'][$fields['ID']] = $fields;
            }
        }
    }

    public function executeComponent()
    {
        $this->getProducts();
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $GLOBALS['APPLICATION']->RestartBuffer();
        }
        $this->includeComponentTemplate($this->componentPage);
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            die();
        }
    }

}
