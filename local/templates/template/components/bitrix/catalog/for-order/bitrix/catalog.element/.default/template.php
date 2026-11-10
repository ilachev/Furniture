<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use \Bitrix\Main\Localization\Loc;

/**
 * @global CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 * @var CatalogSectionComponent $component
 * @var CBitrixComponentTemplate $this
 * @var string $templateName
 * @var string $componentPath
 * @var string $templateFolder
 */
$this->setFrameMode(true);

if (in_array($arResult['ID'], (array)($arParams['FAVORITES'] ?? []))) {
    $fav_action = 'compfavdelete';
    $fav_act = ' is-active';
} else {
    $fav_action = 'compfav';
    $fav_act = '';
}
?>

<? $this->SetViewTarget('product-fixed-panel'); ?>
<div class="product-card_fixed-panel">
	<a href="javascript:void(0)"
	   data-clas="basket"
	   data-method="add2basket"
	   data-id="<?= $arResult['ID']; ?>"
	   class="product-card_fixed-btn">
        <? if ($arResult['CATALOG_QUANTITY'] > 0 && $arResult['CAN_BUY'] == 'Y') { ?>
			В корзину
        <? } else { ?>
			Заказать
        <? } ?>
	</a>

    <? if ($arResult['CATALOG_QUANTITY'] > 0 && $arResult['CAN_BUY'] == 'Y') { ?>
		<span class="product-card_fixed-price">Цена <?= $arResult['MIN_PRICE']['PRINT_DISCOUNT_VALUE_NOVAT']; ?> </span>
    <? } else { ?>
		<span class="product-card_fixed-price">Цена от <?= $arResult['MIN_PRICE']['PRINT_DISCOUNT_VALUE_NOVAT']; ?> </span>
    <? } ?>

	<span class="product-card_fixed-fav<?= $fav_act; ?>"
	      data-add="FAVORITES"
	      data-id="<?= $arResult['ID']; ?>"
	      data-class="tools"
	      data-method="<?= $fav_action; ?>">
		<span class="ico-fav"></span>
		<span class="ico-fav-2"></span>
	</span>
</div>
<? $this->EndViewTarget(); ?>

<?if(!empty($arResult['SLIDER'])){?>
<div class="product-card_slider<? if ($arResult['CATALOG_QUANTITY'] <= 0 || $arResult['CAN_BUY'] != 'Y') echo ' product-card_slider-mar' ?>">
	<?foreach ($arResult['SLIDER'] as $arPicture){?>
	<div class="product-card_item">
		<a href="<?=$arPicture['FULL'];?>" class='fancybox'>
			<img data-src="<?=$arPicture['PREVIEW'];?>" alt="<?=$arResult['NAME'];?>">
			<span class="item-title"><?=$arResult['NAME'];?></span>
		</a>
	</div>
	<?}?>
</div>
<?}?>
<div class="row">
	<div class="col-md-8 product-card_left-cell">
		<div class="product-card_stock-code">
            <? if ($arResult['CATALOG_QUANTITY'] > 0 && $arResult['CAN_BUY'] == 'Y') { ?>
				<span class="product-card_stock">В наличии</span>
            <? } ?>
            <? if (!empty($arResult['PROPERTIES']['ARTNUMBER']['VALUE'])) { ?>
				<span class="product-card_code">Арт. <?= $arResult['PROPERTIES']['ARTNUMBER']['VALUE']; ?></span>
            <? } ?>
		</div>
        <? if (!empty($arResult['PROPERTIES']['MANUFACTURER']['ITEM_VALUE'])) { ?>
			<div class="product-card_middle-panel">
				<div class="product-card_brand">
					<img src="<?= $arResult['PROPERTIES']['MANUFACTURER']['ITEM_VALUE']['RESIZED_FILE']; ?>"
					     alt="<?= $arResult['PROPERTIES']['MANUFACTURER']['ITEM_VALUE']['UF_NAME']; ?>">
				</div>
                <? if (!empty($arResult['PROPERTIES']['MANUFACTURER']['ITEM_VALUE']['UF_DESCRIPTION'])) { ?>
					<div class="text-column">
						<p><?= $arResult['PROPERTIES']['MANUFACTURER']['ITEM_VALUE']['UF_DESCRIPTION']; ?></p>
					</div>
                <? } ?>
			</div>
        <? } ?>
		<div class="product-card_features-panel">
            <? if (!empty($arResult['PROPERTIES']['LENGTH']['VALUE']) ||
                !empty($arResult['PROPERTIES']['WIDTH']['VALUE']) ||
                !empty($arResult['PROPERTIES']['HEIGHT']['VALUE'])) { ?>
				<div class="features-panel_column">
					<span class="top-subtitle">Размеры</span>
					<ul class="product-card_size-list">
                        <? if (!empty($arResult['PROPERTIES']['LENGTH']['VALUE'])) { ?>
							<li>
								<span class="small-subtitle">Длина</span>
                                <?= $arResult['PROPERTIES']['LENGTH']['VALUE']; ?>
							</li>
                        <? } ?>
                        <? if (!empty($arResult['PROPERTIES']['WIDTH']['VALUE'])) { ?>
                            <? if (!empty($arResult['PROPERTIES']['LENGTH']['VALUE'])) { ?>
								<li class="multiply">&#215;</li>
                            <? } ?>
							<li>
								<span class="small-subtitle">Ширина</span>
                                <?= $arResult['PROPERTIES']['WIDTH']['VALUE']; ?>
							</li>
                        <? } ?>
                        <? if (!empty($arResult['PROPERTIES']['HEIGHT']['VALUE'])) { ?>
                            <? if (!empty($arResult['PROPERTIES']['LENGTH']['VALUE']) || !empty($arResult['PROPERTIES']['LENGTH']['VALUE'])) { ?>
								<li class="multiply">&#215;</li>
                            <? } ?>
							<li>
								<span class="small-subtitle">Высота</span>
                                <?= $arResult['PROPERTIES']['HEIGHT']['VALUE']; ?>
							</li>
                        <? } ?>
					</ul>
				</div>
            <? } ?>

            <? if (!empty($arResult['PROPERTIES']['SEAT_DEPTH']['VALUE']) || !empty($arResult['PROPERTIES']['SEAT_HEIGHT']['VALUE'])) { ?>
				<div class="features-panel_column">
					<div class="column-content">
						<span class="top-subtitle">Посадочное место</span>
						<ul class="product-card_size-list">
                            <? if (!empty($arResult['PROPERTIES']['SEAT_DEPTH']['VALUE'])) { ?>
								<li>
									<span class="small-subtitle">Глубина</span>
                                    <?= $arResult['PROPERTIES']['SEAT_DEPTH']['VALUE']; ?>
								</li>
                            <? } ?>
                            <? if (!empty($arResult['PROPERTIES']['SEAT_HEIGHT']['VALUE'])) { ?>
                                <? if (!empty($arResult['PROPERTIES']['SEAT_HEIGHT']['VALUE'])) { ?>
									<li class="multiply">&#215;</li>
                                <? } ?>
								<li>
									<span class="small-subtitle">Высота</span>
                                    <?= $arResult['PROPERTIES']['SEAT_HEIGHT']['VALUE']; ?>
								</li>
                            <? } ?>
						</ul>
					</div>
				</div>
            <? } ?>

            <? if (!empty($arResult['PROPERTIES']['COLOR']['ITEM_VALUE'])) { ?>
				<div class="features-panel_column">
					<div class="column-content">
						<span class="top-subtitle">Цвет ножек</span>
						<div class="product-card_color-item">
						<span class="circle-color"
						      style="background: <?= $arResult['PROPERTIES']['COLOR']['ITEM_VALUE']['UF_COLOR'] ?>"></span>
							<span class="title-color"><?= $arResult['PROPERTIES']['COLOR']['ITEM_VALUE']['UF_NAME'] ?></span>
						</div>
					</div>
				</div>
            <? } ?>
		</div>
        <? if (!empty($arResult['DISPLAY_PROPERTIES'])) { ?>
			<div class="product-card_specifications-panel">
                <? foreach ($arResult['CHUNKED_DISPLAY_PROPERTIES'] as $chunk) { ?>
					<table class="product-card_specifications-table">
                        <? foreach ($chunk as $arProp) { ?>
							<tr>
								<td><?= $arProp['NAME']; ?> :</td>
								<td><?= $arProp['DISPLAY_VALUE']; ?></td>
							</tr>
                        <? } ?>
					</table>
                <? } ?>
			</div>
        <? } ?>
	</div>
	<div class="col-md-4 product-card_right-cell">
        <? if ($arResult['CATALOG_QUANTITY'] > 0 && $arResult['CAN_BUY'] == 'Y') { ?>
			<span class="product-card_price">Цена <?= $arResult['MIN_PRICE']['PRINT_DISCOUNT_VALUE_NOVAT']; ?> </span>
        <? } else { ?>
			<span class="product-card_price">Цена от <?= $arResult['MIN_PRICE']['PRINT_DISCOUNT_VALUE_NOVAT']; ?> </span>
        <? } ?>
		<div class="product-card_fav-buy">
			<span class="product-card_fav<?= $fav_act; ?>"
			      data-add="FAVORITES"
			      data-id="<?= $arResult['ID']; ?>"
			      data-class="tools"
			      data-method="<?= $fav_action; ?>">
				<span class="ico-fav"></span>
				<span class="ico-fav-2"></span>
			</span>
			<a href="javascript:void(0)"
			   data-class="basket"
			   data-method="add2basket"
			   data-id="<?= $arResult['ID']; ?>"
			   class="main-btn product-card_order-btn">
                <? if ($arResult['CATALOG_QUANTITY'] > 0 && $arResult['CAN_BUY'] == 'Y') { ?>
					В корзину
                <? } else { ?>
					Заказать
                <? } ?>
			</a>
		</div>
	</div>
</div>

<!-- end product-card_section -->


<? $this->SetViewTarget('product-variables'); ?>
<div class="product-card_section-body<? if ($arResult['CATALOG_QUANTITY'] <= 0 || $arResult['CAN_BUY'] != 'Y') echo ' body-mod' ?>">
    <? if (!empty($arResult['PROPERTIES']['FINISH_OPTIONS']['VALUE'])) { ?>
		<div class="container">
			<h2>Варианты отделки</h2>
			<div class="row finishing-row">
                <? foreach ($arResult['PROPERTIES']['FINISH_OPTIONS']['VALUE'] as $arOption) { ?>
					<div class="col-lg-3 col-6">
						<div class="finishing-item">
							<span class="finishing-item_color"
							      style="background-color: <?= $arOption['COLOR']; ?>"></span>
							<span class="finishing-item_title"><?= $arOption['TITLE']; ?></span>
							<p><?= $arOption['DESCRIPTION']; ?></p>
						</div>
					</div>
                <? } ?>
			</div>
		</div>
    <? } ?>

    <? if (!empty($arResult['ADDITIONALS_PHOTOS'])) { ?>
        <? foreach ($arResult['ADDITIONALS_PHOTOS'] as $group) { ?>
            <? if (!empty($group[0]) || !empty($group[1])) { ?>
				<div class="product_photo-box row align-items-center">
                    <? if (!empty($group[0])) { ?>
						<div class="col-sm-6 order-sm-2">
							<div class="product_photo-item photo-item_right">
								<img data-src="<?= $group[0] ?>" alt="alt">
							</div>
						</div>
                    <? } ?>
                    <? if (!empty($group[1])) { ?>
						<div class="col-sm-6">
							<div class="product_photo-item item-first photo-item_left">
								<img data-src="<?= $group[1] ?>" alt="alt">
							</div>
						</div>
                    <? } ?>
                    <? if (!empty($group[2])) { ?>
						<div class="product_photo-box_circle"></div>
                    <? } ?>
				</div>
            <? } ?>
            <? if (!empty($group[2]) || !empty($group[3])) { ?>
				<div class="product_photo-box row">
                    <? if (!empty($group[2])) { ?>
						<div class="col-sm-6 order-sm-1">
							<div class="product_photo-item photo-item_right">
								<img data-src="<?= $group[2] ?>" alt="alt">
							</div>
						</div>
                    <? } ?>
                    <? if (!empty($group[3])) { ?>
						<div class="col-sm-6">
							<div class="product_photo-item photo-item_left">
								<img data-src="<?= $group[3] ?>" alt="alt">
							</div>
						</div>
                    <? } ?>
				</div>
            <? } ?>
        <? } ?>
    <? } ?>
</div>
<? $this->EndViewTarget(); ?>

