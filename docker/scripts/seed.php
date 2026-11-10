<?php

if (PHP_SAPI !== 'cli') {
    die("CLI only\n");
}

$_SERVER['DOCUMENT_ROOT'] = '/var/www/html';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_NAME'] = 'localhost';

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;
use Bitrix\Highloadblock\HighloadBlockTable;

Loader::includeModule('iblock');
Loader::includeModule('catalog');
Loader::includeModule('sale');
Loader::includeModule('highloadblock');

foreach (['tq.tools', 'asd.subscribequick'] as $moduleId) {
    if (!\Bitrix\Main\ModuleManager::isModuleInstalled($moduleId)) {
        RegisterModule($moduleId);
    }
}

$reset = in_array('--reset', $argv, true);
$docRoot = $_SERVER['DOCUMENT_ROOT'];
$imgRoot = $docRoot . '/local/templates/template/img/static';

function out(string $message): void
{
    echo $message, PHP_EOL;
}

function fail(string $message): void
{
    fwrite(STDERR, 'ERROR: ' . $message . PHP_EOL);
    exit(1);
}

function image(string $relative): ?array
{
    global $imgRoot;
    $path = $imgRoot . '/' . ltrim($relative, '/');
    return is_file($path) ? CFile::MakeFileArray($path) : null;
}

function ensureIblockType(string $id, string $name): void
{
    if (CIBlockType::GetByID($id)->Fetch()) {
        return;
    }
    $ob = new CIBlockType;
    $result = $ob->Add([
        'ID' => $id,
        'SECTIONS' => 'Y',
        'IN_RSS' => 'N',
        'SORT' => 100,
        'LANG' => [
            'ru' => ['NAME' => $name, 'SECTION_NAME' => 'Раздел', 'ELEMENT_NAME' => 'Элемент'],
        ],
    ]);
    if (!$result) {
        fail("cannot create iblock type {$id}: " . $ob->LAST_ERROR);
    }
}

function ensureIblock(int $expectedId, array $cfg): void
{
    if (CIBlock::GetByID($expectedId)->Fetch()) {
        return;
    }
    $ob = new CIBlock;
    $id = $ob->Add([
        'ACTIVE' => 'Y',
        'NAME' => $cfg['name'],
        'CODE' => $cfg['code'],
        'IBLOCK_TYPE_ID' => $cfg['type'],
        'SITE_ID' => ['s1'],
        'SORT' => $cfg['sort'],
        'LIST_PAGE_URL' => $cfg['list'] ?? '',
        'DETAIL_PAGE_URL' => $cfg['detail'] ?? '',
        'SECTION_PAGE_URL' => $cfg['section'] ?? '',
        'INDEX_ELEMENT' => 'Y',
        'INDEX_SECTION' => 'Y',
        'WORKFLOW' => 'N',
        'BIZPROC' => 'N',
        'GROUP_ID' => [1 => 'X', 2 => 'R'],
    ]);
    if (!$id) {
        fail("cannot create iblock {$expectedId}: " . $ob->LAST_ERROR);
    }
    if ((int)$id !== $expectedId) {
        fail("iblock id mismatch: expected {$expectedId}, got {$id}. Run with --reset on a clean DB.");
    }
}

function ensureProperty(int $iblockId, string $code, string $name, string $type = 'S', array $options = []): int
{
    $existing = CIBlockProperty::GetList([], ['IBLOCK_ID' => $iblockId, 'CODE' => $code])->Fetch();
    if ($existing) {
        return (int)$existing['ID'];
    }
    $ob = new CIBlockProperty;
    $id = $ob->Add(array_merge([
        'IBLOCK_ID' => $iblockId,
        'NAME' => $name,
        'CODE' => $code,
        'PROPERTY_TYPE' => $type,
        'ACTIVE' => 'Y',
        'SORT' => 500,
        'MULTIPLE' => 'N',
    ], $options));
    if (!$id) {
        fail("cannot create property {$code} in iblock {$iblockId}: " . $ob->LAST_ERROR);
    }
    return (int)$id;
}

function ensureListProperty(int $iblockId, string $code, string $name, array $values): int
{
    $propId = ensureProperty($iblockId, $code, $name, 'L', ['MULTIPLE' => 'N']);
    foreach ($values as $xmlId => $value) {
        $exists = CIBlockPropertyEnum::GetList([], ['PROPERTY_ID' => $propId, 'XML_ID' => $xmlId])->Fetch();
        if (!$exists) {
            CIBlockPropertyEnum::Add([
                'PROPERTY_ID' => $propId,
                'VALUE' => $value,
                'XML_ID' => $xmlId,
                'SORT' => 500,
            ]);
        }
    }
    return $propId;
}

function ensureHlBlock(string $name, string $tableName, array $fields, array $rows): string
{
    $hl = HighloadBlockTable::getList(['filter' => ['=NAME' => $name]])->fetch();
    if (!$hl) {
        $result = HighloadBlockTable::add([
            'NAME' => $name,
            'TABLE_NAME' => $tableName,
        ]);
        if (!$result->isSuccess()) {
            fail("cannot create HL block {$name}: " . implode('; ', $result->getErrorMessages()));
        }
        $hl = HighloadBlockTable::getById($result->getId())->fetch();
    }
    $hlId = (int)$hl['ID'];
    $fieldTypes = array_merge(['UF_XML_ID' => 'string'], $fields);
    foreach ($fieldTypes as $fieldName => $fieldType) {
        $exists = CUserTypeEntity::GetList([], [
            'ENTITY_ID' => 'HLBLOCK_' . $hlId,
            'FIELD_NAME' => $fieldName,
        ])->Fetch();
        if ($exists) {
            continue;
        }
        $userType = new CUserTypeEntity;
        $userType->Add([
            'ENTITY_ID' => 'HLBLOCK_' . $hlId,
            'FIELD_NAME' => $fieldName,
            'USER_TYPE_ID' => $fieldType,
            'MANDATORY' => 'N',
            'SHOW_FILTER' => 'N',
            'SHOW_IN_LIST' => 'Y',
            'EDIT_IN_LIST' => 'Y',
            'EDIT_FORM_LABEL' => ['ru' => $fieldName, 'en' => $fieldName],
            'LIST_COLUMN_LABEL' => ['ru' => $fieldName, 'en' => $fieldName],
            'LIST_FILTER_LABEL' => ['ru' => $fieldName, 'en' => $fieldName],
            'XML_ID' => $fieldName,
        ]);
    }

    $entity = HighloadBlockTable::compileEntity($hl);
    $dataClass = $entity->getDataClass();
    foreach ($rows as $row) {
        $exists = $dataClass::getList([
            'filter' => ['=UF_XML_ID' => $row['UF_XML_ID']],
            'limit' => 1,
        ])->fetch();
        if ($exists) {
            continue;
        }
        $file = $row['_FILE'] ?? null;
        unset($row['_FILE']);
        if ($file) {
            $row['UF_FILE'] = image($file);
        }
        $result = $dataClass::add($row);
        if (!$result->isSuccess()) {
            fail("cannot add HL row {$row['UF_XML_ID']}: " . implode('; ', $result->getErrorMessages()));
        }
    }
    return $hl['TABLE_NAME'];
}

function ensureSection(int $iblockId, string $code, string $name, int $sort = 500, int $parentId = 0, ?string $picture = null): int
{
    $existing = CIBlockSection::GetList([], ['IBLOCK_ID' => $iblockId, 'CODE' => $code], false, ['ID'])->Fetch();
    if ($existing) {
        return (int)$existing['ID'];
    }
    $ob = new CIBlockSection;
    $fields = [
        'IBLOCK_ID' => $iblockId,
        'NAME' => $name,
        'CODE' => $code,
        'SORT' => $sort,
        'ACTIVE' => 'Y',
        'IBLOCK_SECTION_ID' => $parentId ?: false,
    ];
    if ($picture && ($file = image($picture))) {
        $fields['PICTURE'] = $file;
    }
    $id = $ob->Add($fields);
    if (!$id) {
        fail("cannot create section {$code}: " . $ob->LAST_ERROR);
    }
    return (int)$id;
}

function ensureElement(int $iblockId, string $xmlId, array $fields, array $props = []): int
{
    $existing = CIBlockElement::GetList([], ['IBLOCK_ID' => $iblockId, 'XML_ID' => $xmlId], false, false, ['ID'])->Fetch();
    if ($existing) {
        return (int)$existing['ID'];
    }
    $ob = new CIBlockElement;
    $fields = array_merge([
        'IBLOCK_ID' => $iblockId,
        'XML_ID' => $xmlId,
        'ACTIVE' => 'Y',
        'ACTIVE_FROM' => '01.01.2024 00:00:00',
        'SORT' => 500,
        'PREVIEW_TEXT_TYPE' => 'text',
        'DETAIL_TEXT_TYPE' => 'text',
    ], $fields);
    foreach (['PREVIEW_PICTURE_FILE', 'DETAIL_PICTURE_FILE'] as $key) {
        if (!empty($fields[$key])) {
            $file = image($fields[$key]);
            unset($fields[$key]);
            if ($file) {
                $fields[str_replace('_FILE', '', $key)] = $file;
            }
        }
    }
    $id = $ob->Add($fields);
    if (!$id) {
        fail("cannot create element {$xmlId}: " . $ob->LAST_ERROR);
    }
    if ($props) {
        CIBlockElement::SetPropertyValuesEx($id, $iblockId, $props);
    }
    return (int)$id;
}

function addPrice(int $elementId, float $price): void
{
    global $baseGroupId;
    if (!CCatalogProduct::GetByID($elementId)) {
        CCatalogProduct::Add([
            'ID' => $elementId,
            'QUANTITY' => 10,
            'VAT_ID' => 0,
            'VAT_INCLUDED' => 'N',
            'AVAILABLE' => 'Y',
        ]);
    }
    $hasPrice = CPrice::GetList([], [
        'PRODUCT_ID' => $elementId,
        'CATALOG_GROUP_ID' => $baseGroupId,
    ])->Fetch();
    if (!$hasPrice) {
        CPrice::Add([
            'PRODUCT_ID' => $elementId,
            'CATALOG_GROUP_ID' => $baseGroupId,
            'PRICE' => $price,
            'CURRENCY' => 'RUB',
        ]);
    }
}

if ($reset) {
    for ($id = 1; $id <= 14; $id++) {
        if (CIBlock::GetByID($id)->Fetch()) {
            CIBlock::Delete($id);
        }
    }
    $DB->Query('ALTER TABLE b_iblock AUTO_INCREMENT = 1');
    $DB->Query('ALTER TABLE b_iblock_element AUTO_INCREMENT = 1');
    out('Existing ib locks 1-14 removed');
}

$maxElementId = (int)$DB->Query('SELECT MAX(ID) AS M FROM b_iblock_element')->Fetch()['M'];
if ($maxElementId > 0 && !CIBlockElement::GetByID(8)->Fetch()) {
    fail('Element auto-increment is not clean. Run: docker compose exec -T php php /var/www/html/docker/scripts/seed.php --reset');
}

$site = new CSite;
$site->Update('s1', [
    'SITE_NAME' => 'Архитектура мебели',
    'EMAIL' => 'arhimeb@inbox.ru',
    'TEMPLATE' => [['TEMPLATE' => 'template', 'CONDITION' => '', 'SORT' => 500]],
]);

ensureIblockType('content', 'Контент');
ensureIblockType('catalog', 'Каталог');

$iblocks = [
    1 => ['type' => 'content', 'code' => 'brands', 'name' => 'Бренды', 'sort' => 10, 'list' => '/brands/', 'detail' => '/brands/#ELEMENT_CODE#/'],
    2 => ['type' => 'content', 'code' => 'news', 'name' => 'Новости', 'sort' => 20, 'list' => '/news/', 'detail' => '/news/#ELEMENT_ID#/'],
    3 => ['type' => 'catalog', 'code' => 'catalog', 'name' => 'Каталог', 'sort' => 30, 'list' => '/catalog/', 'section' => '/catalog/#SECTION_CODE#/', 'detail' => '/catalog/#SECTION_CODE#/#ELEMENT_CODE#/'],
    4 => ['type' => 'catalog', 'code' => 'tovary-pod-zakaz', 'name' => 'Товары под заказ', 'sort' => 40, 'list' => '/tovary-pod-zakaz/', 'section' => '/tovary-pod-zakaz/#SECTION_CODE#/', 'detail' => '/tovary-pod-zakaz/#SECTION_CODE#/#ELEMENT_CODE#/'],
    5 => ['type' => 'content', 'code' => 'promo', 'name' => 'Акции', 'sort' => 50, 'list' => '/promo/', 'detail' => '/promo/#ELEMENT_ID#/'],
    6 => ['type' => 'content', 'code' => 'about', 'name' => 'О компании', 'sort' => 60],
    7 => ['type' => 'content', 'code' => 'contacts', 'name' => 'Контакты', 'sort' => 70],
    8 => ['type' => 'content', 'code' => 'catalog-slider', 'name' => 'Слайдер в каталоге', 'sort' => 80],
    9 => ['type' => 'content', 'code' => 'callback-requests', 'name' => 'Заявки: обратный звонок', 'sort' => 90],
    10 => ['type' => 'content', 'code' => 'feedback-requests', 'name' => 'Заявки: обратная связь', 'sort' => 100],
    11 => ['type' => 'content', 'code' => 'collections', 'name' => 'Коллекции', 'sort' => 110],
    12 => ['type' => 'content', 'code' => 'instagram', 'name' => 'Instagram', 'sort' => 120],
    13 => ['type' => 'content', 'code' => 'main-slider', 'name' => 'Слайдер на главной', 'sort' => 130],
    14 => ['type' => 'content', 'code' => 'interior-ideas', 'name' => 'Идеи для интерьера', 'sort' => 140],
];
foreach ($iblocks as $id => $cfg) {
    ensureIblock($id, $cfg);
}

foreach ([3, 4] as $catalogIblockId) {
    if (!CCatalog::GetByID($catalogIblockId)) {
        CCatalog::Add([
            'IBLOCK_ID' => $catalogIblockId,
            'YANDEX_EXPORT' => 'N',
            'SUBSCRIPTION' => 'N',
            'VAT_ID' => 0,
        ]);
    }
}

$baseGroup = CCatalogGroup::GetList([], ['NAME' => 'BASE'])->Fetch();
if ($baseGroup) {
    $baseGroupId = (int)$baseGroup['ID'];
} else {
    $baseGroupId = (int)CCatalogGroup::Add([
        'NAME' => 'BASE',
        'SORT' => 100,
        'BASE' => 'Y',
        'USER_LANG' => ['ru' => 'Базовая цена', 'en' => 'Base price'],
        'USER_GROUP' => [1, 2],
        'USER_GROUP_BUY' => [1, 2],
    ]);
}
out('Base price type id: ' . $baseGroupId);

$manufacturersTable = ensureHlBlock('Manufacturers', 'b_hlbd_manufacturers', [
    'UF_NAME' => 'string',
    'UF_FILE' => 'file',
    'UF_SORT' => 'integer',
    'UF_DESCRIPTION' => 'string',
], [
    ['UF_XML_ID' => 'bb-italia', 'UF_NAME' => 'B&B Italia', 'UF_SORT' => 100, '_FILE' => 'brand-logo/01.jpg'],
    ['UF_XML_ID' => 'minotti', 'UF_NAME' => 'Minotti', 'UF_SORT' => 200, '_FILE' => 'brand-logo/02.png'],
    ['UF_XML_ID' => 'poliform', 'UF_NAME' => 'Poliform', 'UF_SORT' => 300, '_FILE' => 'brand-logo/02.jpg'],
    ['UF_XML_ID' => 'cassina', 'UF_NAME' => 'Cassina', 'UF_SORT' => 400, '_FILE' => 'brand-logo/01.jpg'],
    ['UF_XML_ID' => 'flexform', 'UF_NAME' => 'Flexform', 'UF_SORT' => 500, '_FILE' => 'brand-logo/02.png'],
    ['UF_XML_ID' => 'armani-casa', 'UF_NAME' => 'Armani/Casa', 'UF_SORT' => 600, '_FILE' => 'brand-logo/02.jpg'],
    ['UF_XML_ID' => 'giorgetti', 'UF_NAME' => 'Giorgetti', 'UF_SORT' => 700, '_FILE' => 'brand-logo/01.jpg'],
]);

$colorsTable = ensureHlBlock('Colors', 'b_hlbd_colors', [
    'UF_NAME' => 'string',
    'UF_COLOR' => 'string',
    'UF_SORT' => 'integer',
], [
    ['UF_XML_ID' => 'white', 'UF_NAME' => 'Белый', 'UF_COLOR' => '#F5F5F0', 'UF_SORT' => 100],
    ['UF_XML_ID' => 'black', 'UF_NAME' => 'Чёрный', 'UF_COLOR' => '#1C1C1C', 'UF_SORT' => 200],
    ['UF_XML_ID' => 'oak', 'UF_NAME' => 'Дуб', 'UF_COLOR' => '#B08D57', 'UF_SORT' => 300],
    ['UF_XML_ID' => 'grey', 'UF_NAME' => 'Серый', 'UF_COLOR' => '#808080', 'UF_SORT' => 400],
    ['UF_XML_ID' => 'blue', 'UF_NAME' => 'Синий', 'UF_COLOR' => '#2F4F7F', 'UF_SORT' => 500],
    ['UF_XML_ID' => 'beige', 'UF_NAME' => 'Бежевый', 'UF_COLOR' => '#D8C3A5', 'UF_SORT' => 600],
]);

foreach ([3, 4] as $catalogIblockId) {
    ensureProperty($catalogIblockId, 'ARTNUMBER', 'Артикул');
    ensureProperty($catalogIblockId, 'MANUFACTURER', 'Производитель', 'S', [
        'USER_TYPE' => 'directory',
        'USER_TYPE_SETTINGS' => ['TABLE_NAME' => $manufacturersTable],
    ]);
    ensureProperty($catalogIblockId, 'COUNTRY', 'Страна');
    ensureProperty($catalogIblockId, 'MATERIAL', 'Материал');
    ensureProperty($catalogIblockId, 'LEG_MATERIAL', 'Материал ножек');
    ensureProperty($catalogIblockId, 'USB', 'USB');
    ensureProperty($catalogIblockId, 'REMOVABLE_COVER', 'Съёмный чехол');
    ensureProperty($catalogIblockId, 'DECORATIVE_PILOWS', 'Декоративные подушки');
    ensureProperty($catalogIblockId, 'FINISH_OPTIONS', 'Варианты отделки', 'S', ['MULTIPLE' => 'Y']);
    ensureProperty($catalogIblockId, 'MORE_PHOTO', 'Дополнительные фото', 'F', ['MULTIPLE' => 'Y']);
    ensureProperty($catalogIblockId, 'ADDITIONALS_PHOTOS', 'Фото в интерьере', 'F', ['MULTIPLE' => 'Y']);
    ensureProperty($catalogIblockId, 'LENGTH', 'Длина', 'N');
    ensureProperty($catalogIblockId, 'WIDTH', 'Ширина', 'N');
    ensureProperty($catalogIblockId, 'HEIGHT', 'Высота', 'N');
    ensureProperty($catalogIblockId, 'SEAT_DEPTH', 'Глубина сиденья', 'N');
    ensureProperty($catalogIblockId, 'SEAT_HEIGHT', 'Высота сиденья', 'N');
    ensureProperty($catalogIblockId, 'SIZE', 'Размер');
    ensureProperty($catalogIblockId, 'ORDER_DATE', 'Дата заказа');
    ensureProperty($catalogIblockId, 'COLOR', 'Цвет', 'S', [
        'USER_TYPE' => 'directory',
        'USER_TYPE_SETTINGS' => ['TABLE_NAME' => $colorsTable],
    ]);
    ensureProperty($catalogIblockId, 'INTERESED', 'Похожие товары', 'E', [
        'MULTIPLE' => 'Y',
        'LINK_IBLOCK_ID' => $catalogIblockId,
    ]);
}

ensureProperty(1, 'MORE_PHOTO', 'Фотографии', 'F', ['MULTIPLE' => 'Y']);
ensureProperty(1, 'COLLECTION_PICTURE', 'Фото коллекции', 'F');
ensureProperty(1, 'OTHER_BRANDS', 'Другие бренды', 'E', ['MULTIPLE' => 'Y', 'LINK_IBLOCK_ID' => 1]);

ensureProperty(11, 'BRANDS', 'Бренды', 'E', ['MULTIPLE' => 'Y', 'LINK_IBLOCK_ID' => 1]);

ensureProperty(5, 'GALLERY', 'Галерея', 'F', ['MULTIPLE' => 'Y']);
ensureProperty(5, 'CAN_BE_INTERESED', 'Товары', 'E', ['MULTIPLE' => 'Y', 'LINK_IBLOCK_ID' => 3]);

ensureProperty(12, 'LINK', 'Ссылка');
ensureProperty(8, 'LINK', 'Ссылка');
ensureProperty(14, 'URL', 'Ссылка');
ensureProperty(13, 'URL', 'Ссылка');
ensureProperty(13, 'URL_TEXT', 'Текст ссылки');
ensureListProperty(13, 'MODAL', 'Открывать в модальном окне', ['Y' => 'Да', 'N' => 'Нет']);

ensureProperty(7, 'PHONES', 'Телефоны', 'S', ['MULTIPLE' => 'Y']);
ensureProperty(7, 'ADDRESS', 'Адрес', 'S', ['USER_TYPE' => 'HTML']);
ensureProperty(7, 'WORK_TIME', 'Режим работы', 'S', ['MULTIPLE' => 'Y']);
ensureProperty(7, 'EMAIL', 'Email');
ensureProperty(7, 'COORDINATES', 'Координаты');

foreach ([9, 10] as $formIblockId) {
    ensureProperty($formIblockId, 'NAME', 'Имя');
    ensureProperty($formIblockId, 'PHONE', 'Телефон');
    ensureProperty($formIblockId, 'EMAIL', 'Email');
    ensureProperty($formIblockId, 'PREVIEW_TEXT', 'Сообщение', 'S', ['USER_TYPE' => 'HTML']);
}

// Catalog sections
$sections = [];
$sections['dveri'] = ensureSection(3, 'dveri', 'Двери', 10, 0, 'category/01.jpg');
$sections['dekor'] = ensureSection(3, 'dekor', 'Декор', 20, 0, 'category/02.jpg');
$sections['kukhni'] = ensureSection(3, 'kukhni', 'Кухни', 30, 0, 'category/03.jpg');
$sections['mebel'] = ensureSection(3, 'mebel', 'Мебель', 40, 0, 'category/04.jpg');
$sections['dekorativnye-podushki'] = ensureSection(3, 'dekorativnye-podushki', 'Декоративные подушки', 50, $sections['dekor'], 'category/05.jpg');
$sections['zerkala'] = ensureSection(3, 'zerkala', 'Зеркала', 60, $sections['dekor'], 'category/06.jpg');
$sections['posuda'] = ensureSection(3, 'posuda', 'Посуда', 70, $sections['dekor'], 'category/07.jpg');
$sections['svetilniki'] = ensureSection(3, 'svetilniki', 'Светильники', 80, $sections['dekor'], 'category/08.jpg');
$sections['napolnye-pokrytiya'] = ensureSection(3, 'napolnye-pokrytiya', 'Напольные покрытия', 90, $sections['dekor'], 'category/09.jpg');
$sections['kukhni-v-nalichii'] = ensureSection(3, 'kukhni-v-nalichii', 'Кухни в наличии', 100, $sections['kukhni'], 'category/10.jpg');
$sections['order-kukhni'] = ensureSection(4, 'kukhni', 'Кухни', 10, 0, 'category/11.jpg');

// Elements. First seven elements go to brands so the contacts element gets ID 8.
$brands = [
    ['bb-italia', 'B&B Italia', 'brand-logo/01.jpg'],
    ['minotti', 'Minotti', 'brand-logo/02.png'],
    ['poliform', 'Poliform', 'brand-logo/02.jpg'],
    ['cassina', 'Cassina', 'brand-logo/01.jpg'],
    ['flexform', 'Flexform', 'brand-logo/02.png'],
    ['armani-casa', 'Armani/Casa', 'brand-logo/02.jpg'],
    ['giorgetti', 'Giorgetti', 'brand-logo/01.jpg'],
];
$brandIds = [];
foreach ($brands as [$xmlId, $name, $logo]) {
    $brandIds[$xmlId] = ensureElement(1, 'seed-brand-' . $xmlId, [
        'NAME' => $name,
        'CODE' => $xmlId,
        'PREVIEW_TEXT' => 'Итальянская мебель и предметы интерьера ' . $name . '.',
        'PREVIEW_PICTURE_FILE' => $logo,
        'DETAIL_PICTURE_FILE' => $logo,
        'DETAIL_TEXT' => $name . ' — официальное представительство в Санкт-Петербурге.',
    ], [
        'MORE_PHOTO' => [image('photo/01.jpg'), image('photo/02.jpg')],
        'COLLECTION_PICTURE' => image('category/01.jpg'),
    ]);
}

$contactsId = ensureElement(7, 'seed-contacts', [
    'NAME' => 'Контакты',
    'CODE' => 'contacts',
], [
    'PHONES' => ['+7 (812) 997-10-56', '+7 (812) 997-10-48'],
    'ADDRESS' => [
        'VALUE' => ['TEXT' => '197198, Санкт-Петербург, Зоологический переулок, д. 3', 'TYPE' => 'text'],
    ],
    'WORK_TIME' => ['Пн–Пт: 10:00–20:00', 'Сб–Вс: 11:00–19:00'],
    'EMAIL' => 'arhimeb@inbox.ru',
    'COORDINATES' => '59.9386,30.3141',
]);
if ($contactsId !== 8) {
    out("WARNING: contacts element id is {$contactsId}, expected 8 (ELEMENT_CONTACTS)");
}
unset($contactsId);

// Other brands relations
foreach ($brandIds as $xmlId => $id) {
    $others = [];
    foreach ($brandIds as $otherXmlId => $otherId) {
        if ($otherXmlId !== $xmlId) {
            $others[] = $otherId;
        }
    }
    CIBlockElement::SetPropertyValuesEx($id, 1, ['OTHER_BRANDS' => array_slice($others, 0, 3)]);
}

// Collections
$collectionIds = [];
$collections = [
    ['milano', 'Коллекция Milano', 'photo/01.jpg', ['minotti', 'poliform', 'cassina']],
    ['toscana', 'Коллекция Toscana', 'photo/02.jpg', ['bb-italia', 'flexform']],
    ['roma', 'Коллекция Roma', 'photo/03.jpg', ['armani-casa', 'giorgetti']],
];
foreach ($collections as [$code, $name, $picture, $brandLinks]) {
    $collectionIds[$code] = ensureElement(11, 'seed-collection-' . $code, [
        'NAME' => $name,
        'CODE' => 'collection-' . $code,
        'PREVIEW_TEXT' => 'Мебель и аксессуары в коллекции ' . $name . '.',
        'PREVIEW_PICTURE_FILE' => $picture,
        'DETAIL_PICTURE_FILE' => $picture,
    ], [
        'BRANDS' => array_map(static fn($key) => $brandIds[$key], $brandLinks),
    ]);
}

// Catalog products
$products = [
    ['divan-milano', 'Диван Milano', 'mebel', 349000, 'photo/01.jpg', 'minotti', 'grey', 'AR-1001', 240, 95, 80],
    ['kreslo-toscana', 'Кресло Toscana', 'mebel', 129000, 'photo/02.jpg', 'flexform', 'beige', 'AR-1002', 90, 85, 75],
    ['kuhnya-poliform', 'Кухня Poliform', 'kukhni', 590000, 'photo/03.jpg', 'poliform', 'white', 'AR-1003', 320, 60, 220],
    ['kuhnya-onda', 'Кухня в наличии Onda', 'kukhni-v-nalichii', 450000, 'photo/04.jpg', 'poliform', 'oak', 'AR-1004', 280, 60, 210],
    ['dver-classica', 'Дверь Classica', 'dveri', 89000, 'photo/05.jpg', 'giorgetti', 'oak', 'AR-1005', 90, 5, 210],
    ['zerkalo-eclipse', 'Зеркало Eclipse', 'zerkala', 56000, 'photo/06.jpg', 'cassina', 'black', 'AR-1006', 100, 4, 180],
    ['svetilnik-luce', 'Светильник Luce', 'svetilniki', 34000, 'photo/07.jpg', 'armani-casa', 'white', 'AR-1007', 40, 40, 60],
    ['podushka-velvet', 'Подушка Velvet', 'dekorativnye-podushki', 7900, 'photo/08.jpg', 'bb-italia', 'blue', 'AR-1008', 45, 15, 45],
    ['vaza-ceramica', 'Ваза Ceramica', 'posuda', 12500, 'category/09.jpg', 'armani-casa', 'beige', 'AR-1009', 20, 20, 35],
    ['kover-terra', 'Ковёр Terra', 'napolnye-pokrytiya', 78000, 'category/10.jpg', 'flexform', 'beige', 'AR-1010', 300, 200, 1],
    ['stol-cassina', 'Стол Cassina', 'mebel', 215000, 'category/11.jpg', 'cassina', 'oak', 'AR-1011', 180, 90, 75],
    ['komod-giorgetti', 'Комод Giorgetti', 'mebel', 178000, 'category/02.jpg', 'giorgetti', 'black', 'AR-1012', 120, 50, 85],
];
$productIds = [];
foreach ($products as [$code, $name, $sectionCode, $price, $picture, $manufacturer, $color, $article, $length, $width, $height]) {
    $id = ensureElement(3, 'seed-product-' . $code, [
        'NAME' => $name,
        'CODE' => $code,
        'PREVIEW_TEXT' => $name . ' от фабрики ' . $manufacturer . '. Доступен в шоуруме.',
        'DETAIL_TEXT' => 'Элегантный предмет интерьера в современном исполнении. Производство Италия.',
        'PREVIEW_PICTURE_FILE' => $picture,
        'DETAIL_PICTURE_FILE' => $picture,
        'IBLOCK_SECTION' => [$sections[$sectionCode]],
    ], [
        'ARTNUMBER' => $article,
        'MANUFACTURER' => $manufacturer,
        'COUNTRY' => 'Италия',
        'MATERIAL' => 'Массив дуба, текстиль',
        'LEG_MATERIAL' => 'Металл',
        'COLOR' => $color,
        'LENGTH' => $length,
        'WIDTH' => $width,
        'HEIGHT' => $height,
        'SIZE' => $length . ' × ' . $width . ' × ' . $height . ' см',
        'MORE_PHOTO' => [image('photo/05.jpg'), image('photo/06.jpg')],
        'ADDITIONALS_PHOTOS' => [image('photo/07.jpg'), image('photo/08.jpg'), image('category/01.jpg')],
    ]);
    $productIds[$code] = $id;
    addPrice($id, $price);
}

$orderProducts = [
    ['kuhnya-aurora', 'Кухня на заказ Aurora', 690000, 'photo/03.jpg', 'poliform', 'white', 'AR-2001', 340, 65, 230],
    ['kuhnya-verde', 'Кухня на заказ Verde', 820000, 'photo/04.jpg', 'minotti', 'oak', 'AR-2002', 360, 70, 240],
    ['kuhnya-bianco', 'Кухня на заказ Bianco', 760000, 'category/03.jpg', 'cassina', 'beige', 'AR-2003', 300, 60, 220],
];
foreach ($orderProducts as [$code, $name, $price, $picture, $manufacturer, $color, $article, $length, $width, $height]) {
    $id = ensureElement(4, 'seed-order-' . $code, [
        'NAME' => $name,
        'CODE' => $code,
        'PREVIEW_TEXT' => $name . ' — изготовление под размеры вашего помещения.',
        'DETAIL_TEXT' => 'Кухня выполняется по индивидуальному проекту. Срок изготовления — 45 дней.',
        'PREVIEW_PICTURE_FILE' => $picture,
        'DETAIL_PICTURE_FILE' => $picture,
        'IBLOCK_SECTION' => [$sections['order-kukhni']],
    ], [
        'ARTNUMBER' => $article,
        'MANUFACTURER' => $manufacturer,
        'COUNTRY' => 'Италия',
        'MATERIAL' => 'МДФ, шпон дуба',
        'COLOR' => $color,
        'LENGTH' => $length,
        'WIDTH' => $width,
        'HEIGHT' => $height,
        'SIZE' => $length . ' × ' . $width . ' × ' . $height . ' см',
        'MORE_PHOTO' => [image('photo/01.jpg'), image('photo/02.jpg')],
        'ADDITIONALS_PHOTOS' => [image('photo/03.jpg'), image('photo/04.jpg')],
    ]);
    $productIds[$code] = $id;
    addPrice($id, $price);
}

// Cross links between products
$allProducts = array_values($productIds);
foreach ($allProducts as $index => $productId) {
    $related = [];
    for ($i = 1; $i <= 3; $i++) {
        $related[] = $allProducts[($index + $i) % count($allProducts)];
    }
    CIBlockElement::SetPropertyValuesEx($productId, 3, ['INTERESED' => $related]);
}

// Main slider
ensureElement(13, 'seed-slide-1', [
    'NAME' => 'Мебель для вашего дома',
    'CODE' => 'slide-1',
    'PREVIEW_PICTURE_FILE' => 'banner/01.jpg',
], [
    'URL' => '/catalog/',
    'URL_TEXT' => 'Смотреть каталог',
    'MODAL' => 'N',
]);
ensureElement(13, 'seed-slide-2', [
    'NAME' => 'Итальянские кухни',
    'CODE' => 'slide-2',
    'PREVIEW_PICTURE_FILE' => 'banner/02.jpg',
], [
    'URL' => '/catalog/kukhni/',
    'URL_TEXT' => 'Выбрать кухню',
    'MODAL' => 'N',
]);

// Interior ideas
$interiors = [
    ['gostinaya', 'Гостиная в тёплых тонах', 'category-2/01.jpg'],
    ['spalnya', 'Спальня с характером', 'category-2/02.jpg'],
    ['kabinet', 'Кабинет для работы', 'category-2/03.jpg'],
];
foreach ($interiors as $index => [$code, $name, $picture]) {
    ensureElement(14, 'seed-interior-' . $code, [
        'NAME' => $name,
        'CODE' => 'interior-' . $code,
        'PREVIEW_PICTURE_FILE' => $picture,
    ], [
        'URL' => '/catalog/',
    ]);
}

// About
$aboutBlocks = [
    ['istoriya', 'О компании', 'photo/04.jpg', 'Мы занимаемся итальянской мебелью с 2008 года.'],
    ['shourum', 'Шоурум в центре города', 'photo/05.jpg', 'Более 2000 м² экспозиции в Санкт-Петербурге.'],
    ['proizvodstvo', 'Собственное производство', 'photo/06.jpg', 'Работаем с проверенными фабриками Италии.'],
];
foreach ($aboutBlocks as [$code, $name, $picture, $text]) {
    ensureElement(6, 'seed-about-' . $code, [
        'NAME' => $name,
        'CODE' => 'about-' . $code,
        'PREVIEW_TEXT' => $text,
        'PREVIEW_PICTURE_FILE' => $picture,
    ]);
}

// News
$news = [
    ['novaya-kollekciya', 'Новая коллекция Milano', 'category/01.jpg', 'В шоуруме представлена новая коллекция мягкой мебели.'],
    ['otkrytie-shouruma', 'Открытие обновлённого шоурума', 'category/02.jpg', 'Мы расширили экспозицию и добавили новые бренды.'],
    ['skidki-na-kuhni', 'Скидки на кухни', 'category/03.jpg', 'До конца месяца действуют специальные цены на кухни.'],
];
foreach ($news as [$code, $name, $picture, $text]) {
    ensureElement(2, 'seed-news-' . $code, [
        'NAME' => $name,
        'CODE' => $code,
        'PREVIEW_TEXT' => $text,
        'DETAIL_TEXT' => $text . ' Приходите в шоурум, чтобы увидеть всё вживую.',
        'PREVIEW_PICTURE_FILE' => $picture,
        'DETAIL_PICTURE_FILE' => $picture,
    ]);
}

// Promo
$promos = [
    ['divany-15', 'Скидка 15% на диваны', 'category-2/04.jpg', ['divan-milano', 'kreslo-toscana']],
    ['kuhni-pod-zakaz', 'Кухни под заказ со скидкой', 'category-2/05.jpg', ['kuhnya-poliform', 'kuhnya-onda']],
    ['dekor-2-po-1', 'Декор: 2 по цене 1', 'category-2/06.jpg', ['podushka-velvet', 'vaza-ceramica']],
];
foreach ($promos as [$code, $name, $picture, $linked]) {
    ensureElement(5, 'seed-promo-' . $code, [
        'NAME' => $name,
        'CODE' => $code,
        'PREVIEW_TEXT' => $name . ' — предложение ограничено по времени.',
        'DETAIL_TEXT' => $name . '. Подробности в шоуруме и по телефону.',
        'PREVIEW_PICTURE_FILE' => $picture,
        'DETAIL_PICTURE_FILE' => $picture,
    ], [
        'GALLERY' => [image('photo/01.jpg'), image('photo/02.jpg')],
        'CAN_BE_INTERESED' => array_map(static fn($key) => $productIds[$key], $linked),
    ]);
}

// Catalog slider
ensureElement(8, 'seed-cat-slide-1', [
    'NAME' => 'Новые поступления',
    'CODE' => 'cat-slide-1',
    'PREVIEW_PICTURE_FILE' => 'banner/01.jpg',
], ['LINK' => '/catalog/']);
ensureElement(8, 'seed-cat-slide-2', [
    'NAME' => 'Кухни под заказ',
    'CODE' => 'cat-slide-2',
    'PREVIEW_PICTURE_FILE' => 'banner/02.jpg',
], ['LINK' => '/tovary-pod-zakaz/']);

// Instagram
foreach (['01', '02', '03', '04'] as $number) {
    ensureElement(12, 'seed-instagram-' . $number, [
        'NAME' => 'Instagram ' . $number,
        'CODE' => 'instagram-' . $number,
        'PREVIEW_PICTURE_FILE' => 'instagram/' . $number . '.jpg',
    ], ['LINK' => 'https://www.instagram.com']);
}

// Sale: person type and order properties expected by 2quick:order
$personType = CSalePersonType::GetList([], ['LID' => 's1', 'NAME' => 'Физическое лицо'])->Fetch();
if ($personType) {
    $personTypeId = (int)$personType['ID'];
} else {
    $personTypeId = (int)CSalePersonType::Add([
        'LID' => 's1',
        'NAME' => 'Физическое лицо',
        'ACTIVE' => 'Y',
        'SORT' => 100,
    ]);
}

$ensurePropGroup = static function (string $name, int $sort) use ($personTypeId): int {
    $existing = CSaleOrderPropsGroup::GetList([], [
        'PERSON_TYPE_ID' => $personTypeId,
        'NAME' => $name,
    ])->Fetch();
    if ($existing) {
        return (int)$existing['ID'];
    }
    return (int)CSaleOrderPropsGroup::Add([
        'PERSON_TYPE_ID' => $personTypeId,
        'NAME' => $name,
        'SORT' => $sort,
    ]);
};

$ensureOrderProp = static function (int $groupId, string $code, string $name, array $fields) use ($personTypeId): void {
    $existing = CSaleOrderProps::GetList([], [
        'PERSON_TYPE_ID' => $personTypeId,
        'CODE' => $code,
    ])->Fetch();
    if ($existing) {
        return;
    }
    CSaleOrderProps::Add(array_merge([
        'PERSON_TYPE_ID' => $personTypeId,
        'NAME' => $name,
        'TYPE' => 'TEXT',
        'REQUIRED' => 'N',
        'SORT' => 100,
        'CODE' => $code,
        'USER_PROPS' => 'Y',
        'PROPS_GROUP_ID' => $groupId,
        'ACTIVE' => 'Y',
    ], $fields));
};

$ensurePropGroup('Дополнительно', 50);
$deliveryGroupId = $ensurePropGroup('Регион доставки', 100);
$buyerGroupId = $ensurePropGroup('Покупатель', 200);

$ensureOrderProp($deliveryGroupId, 'LOCATION', 'Город', ['REQUIRED' => 'Y', 'SORT' => 100]);
$ensureOrderProp($deliveryGroupId, 'INDEX', 'Индекс', ['IS_ZIP' => 'Y', 'SORT' => 200]);
$ensureOrderProp($deliveryGroupId, 'ADDRESS', 'Адрес', ['IS_ADDRESS' => 'Y', 'REQUIRED' => 'Y', 'SORT' => 300]);
$ensureOrderProp($buyerGroupId, 'NAME', 'Имя', ['IS_PROFILE_NAME' => 'Y', 'REQUIRED' => 'Y', 'SORT' => 100]);
$ensureOrderProp($buyerGroupId, 'EMAIL', 'Email', ['IS_EMAIL' => 'Y', 'REQUIRED' => 'Y', 'SORT' => 200]);
$ensureOrderProp($buyerGroupId, 'PHONE', 'Телефон', ['IS_PHONE' => 'Y', 'REQUIRED' => 'Y', 'SORT' => 300]);

// Clear caches
if (isset($GLOBALS['CACHE_MANAGER'])) {
    $GLOBALS['CACHE_MANAGER']->CleanAll();
}
if (function_exists('BXClearCache')) {
    BXClearCache(true, '/');
}

out('Seed completed');
