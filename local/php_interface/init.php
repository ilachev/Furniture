<?
$arLibs = [
    $_SERVER['DOCUMENT_ROOT'].'/local/php_interface/include/defines.php',
    $_SERVER['DOCUMENT_ROOT'].'/local/php_interface/include/filters.php',
];

foreach($arLibs as $lib){
    if(file_exists($lib)){
        require_once($lib);
    }
}

foreach (['Basketclass', 'Tools'] as $ddsClass) {
    $ddsFile = __DIR__ . '/include/dds/' . $ddsClass . '.php';
    if (!class_exists('DDS\\' . $ddsClass) && is_file($ddsFile)) {
        require_once $ddsFile;
    }
}

AddEventHandler("iblock", "OnIBlockPropertyBuildList", array("CUserTypeSectionLink", "GetUserTypeDescription"));

class CUserTypeSectionLink
{

    private static $Script_included = false;

    public static function GetUserTypeDescription()
    {
        return array(
            "USER_TYPE_ID" => "SECTION_LINK",
            "CLASS_NAME" => "CUserTypeSectionLink",
            "DESCRIPTION" => "Ссылка на раздел",
            "BASE_TYPE" => "string",
            "PROPERTY_TYPE" => "N",
            "USER_TYPE" => "SECTION_LINK",
            "GetPublicViewHTML" => array("CUserTypeSectionLink","GetPublicViewHTML"),
            "GetPropertyFieldHtml" => array("CUserTypeSectionLink","GetPropertyFieldHtml"),
        );
    }

    public static function GetPublicViewHTML($arProperty, $value, $strHTMLControlName)
    {
        return $value['VALUE'];
    }

    public static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
    {

        $sectionsList=array();
        $ibNames=array();

        $rsIB = CIBlock::GetList();
        while ($ib = $rsIB->GetNext()) {
            $ibNames[$ib['ID']] = $ib['NAME'];
        }

        $rsBindValues = CIBlockSection::GetList(
            array("SORT" => "ASC"),
            array('IBLOCK_ID' => $_GET['IBLOCK_ID']),
            false,
            array(
                "ID",
                "IBLOCK_ID",
                "IBLOCK_NAME",
                "NAME"
            ),
            false
        );
        while ($bind_value = $rsBindValues->GetNext()) {
            $sectionsList[$bind_value['IBLOCK_ID']]['NAME'] = $ibNames[$bind_value['IBLOCK_ID']];
            $sectionsList[$bind_value['IBLOCK_ID']]['SECTIONS'][] = $bind_value;
        }

        $optionsHTML='<option value=""> -=( не выбрано )=- </option>';

        foreach($sectionsList as $ib){

            $optionsHTML .= '<optgroup label="'.$ib['NAME'].'">';

            foreach($ib['SECTIONS'] as $s){
                $optionsHTML .= '<option value="'.$s["ID"].'"'.
                    ( $value["VALUE"]==$s['ID'] ? ' selected' : '' ).
                    '>'.
                    $s['NAME'].' ['.$s['ID'].']'.
                    '</option>';
            }

            $optionsHTML .= '</optgroup>';

        }

        return  '<select name="'.$strHTMLControlName["VALUE"].'">'.$optionsHTML.'</select>';

    }

}