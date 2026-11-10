<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}
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
$this->setFrameMode(true);

if (in_array($arResult['PRODUCT']['ID'], (array)($arParams['FAVORITES'] ?? []))) {
    $fav_action = 'compfavdelete';
    $fav_act = ' is-active';
} else {
    $fav_action = 'compfav';
    $fav_act = '';
}

?>
<div class="action-detailed_box-img tab-container">
	<div class="row no-gutters">
		<div class="col-sm-5 order-sm-2">
			<ul class="action_detailed_tab-list">
                <? foreach ($arResult['GALLERY'] as $key => $src) { ?>
					<li class="tab<? if ($key == 0) { ?> active<? } ?>">
						<img data-src="<?= $src; ?>"
						     alt="<?= $arResult['NAME']; ?>">
					</li>
                <? } ?>
			</ul>
		</div>
		<div class="col-sm-7 action-detailed_large-img">
            <? foreach ($arResult['GALLERY'] as $key => $src) { ?>
				<div class="tab-item<? if ($key == 0) { ?> is-visible<? } ?>">
					<img data-src="<?= $src; ?>"
					     alt="<?= $arResult['NAME']; ?>">
				</div>
            <? } ?>
			<span class="action-detailed_title"><?= $arResult['NAME']; ?></span>
		</div>
	</div>
</div>
<? if (!empty($arResult['PRODUCT'])) { ?>
	<div class="action-detailed_desc-box">
		<div class="row">
			<div class="col-lg-8">
				<div class="action_stock-code">
                    <? if ($arResult['PRODUCT']['PRODUCT']['QUANTITY'] > 0) { ?>
						<span class="action-item_stock">В наличии</span>
                    <? } else { ?>
						<span class="action-item_stock on-order">Под заказ</span>
                    <? } ?>
                    <? if (!empty($arResult['PRODUCT']['PROPERTIES']['ARTNUMBER']['VALUE'])) { ?>
						<span class="action-item_code">Арт. <?= $arResult['PRODUCT']['PROPERTIES']['ARTNUMBER']['VALUE']; ?></span>
                    <? } ?>
				</div>
				<div class="action-item_desc">
					<div class="action-item_logo">
						<img alt="<?= $arResult['NAME']; ?>" src="<?= $arResult['DETAIL_PICTURE']['SRC']; ?>">
					</div>
					<div class="right-cell">
						<p>
                            <?= $arResult['DETAIL_TEXT']; ?>
						</p>
					</div>
				</div>
			</div>
			<div class="col-lg-4">
				<span class="special-price_subtitle">Специальная цена!</span>
				<div class="action_price-panel">
					<span class="action_new-price">Цена <?= CurrencyFormat($arResult['PRODUCT']['PRICE']['RESULT_PRICE']['DISCOUNT_PRICE'],
                            $arResult['PRODUCT']['PRICE']['PRICE']['CURRENCY']); ?></span>
					<span class="action_old-price"><?= CurrencyFormat($arResult['PRODUCT']['PRICE']['RESULT_PRICE']['BASE_PRICE'],
                            $arResult['PRODUCT']['PRICE']['PRICE']['CURRENCY']); ?></span>
				</div>
				<div class="product-card_fav-buy">
				<span class="product-card_fav<?= $fav_act; ?>"
				      data-add="FAVORITES"
				      data-id="<?= $arResult['PRODUCT']['ID']; ?>"
				      data-class="tools"
				      data-method="<?= $fav_action; ?>">
					<span class="ico-fav"></span>
					<span class="ico-fav-2"></span>
				</span>
					<a href="javascript:void(0)"
					   data-clas="basket"
					   data-method="add2basket"
					   data-id="<?= $arResult['PRODUCT']['ID']; ?>"
					   class="main-btn product-card_order-btn">
                        <? if ($arResult['PRODUCT']['PRODUCT']['QUANTITY'] > 0) { ?>
							В корзину
                        <? } else { ?>
							Заказать
                        <? } ?>
					</a>
				</div>
			</div>
		</div>
	</div>
<? } ?>
