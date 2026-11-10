<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use \Bitrix\Main\Localization\Loc;

/**
 * @global CMain $APPLICATION
 * @var array $arParams
 * @var array $item
 * @var array $actualItem
 * @var array $minOffer
 * @var array $itemIds
 * @var array $price
 * @var array $measureRatio
 * @var bool $haveOffers
 * @var bool $showSubscribe
 * @var array $morePhoto
 * @var bool $showSlider
 * @var bool $itemHasDetailUrl
 * @var string $imgTitle
 * @var string $productTitle
 * @var string $buttonSizeClass
 * @var CatalogSectionComponent $component
 */

if (in_array($item['ID'], (array)($arParams['FAVORITES'] ?? []))) {
    $fav_action = 'compfavdelete';
    $fav_act = ' is-active';
} else {
    $fav_action = 'compfav';
    $fav_act = '';
}
?>
<div class="product-item">
	<div class="product-item_img">
		<a href="<?= $item['DETAIL_PAGE_URL'] ?>">
			<img data-src="<?= $item['PREVIEW_PICTURE']['SRC'] ?>" alt="<?= $item['NAME']; ?>">
		</a>
		<span class="product-item_fav<?= $fav_act; ?>"
		      data-add="FAVORITES"
		      data-id="<?= $item['ID']; ?>"
		      data-class="tools"
		      data-method="<?= $fav_action; ?>">
			<span class="ico-fav"></span>
			<span class="ico-fav-2"></span>
		</span>
	</div>
    <? if ($item['CATALOG_QUANTITY'] > 0 && $item['CAN_BUY'] == 'Y') { ?>
		<span class="product-item_stock">В наличии</span>
    <? } else { ?>
		<span class="product-item_stock on-order">Под заказ</span>
    <? } ?>
	<span class="product-item_title">
			<a href="<?= $item['DETAIL_PAGE_URL'] ?>"><?= $item['NAME']; ?></a>
		</span>
    <? if (!empty($item['PROPERTIES']['ARTNUMBER']['VALUE'])) { ?>
		<span class="product-item_code">Арт. <?= $item['PROPERTIES']['ARTNUMBER']['VALUE']; ?></span>
    <? } ?>

    <? if ($item['CATALOG_QUANTITY'] > 0 && $item['CAN_BUY'] == 'Y') { ?>
	<span class="product-item_price"><?= $item['MIN_PRICE']['PRINT_DISCOUNT_VALUE_NOVAT']; ?></span>
	<?} else {?>
	    <span class="product-item_price">от <?= $item['MIN_PRICE']['PRINT_DISCOUNT_VALUE_NOVAT']; ?></span>
	<?}?>
	<a href="javascript:void(0)"
	   data-class="basket"
	   data-method="add2basket"
	   data-id="<?= $item['ID']; ?>"
	   class="main-btn product-item_btn">
        <? if ($item['CATALOG_QUANTITY'] > 0 && $item['CAN_BUY'] == 'Y') { ?>
			В корзину
        <? } else { ?>
			Заказать
        <? } ?>
	</a>
</div>
