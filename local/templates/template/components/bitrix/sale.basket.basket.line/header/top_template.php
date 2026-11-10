<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/**
 * @global array $arParams
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @global string $cartId
 */
$compositeStub = (isset($arResult['COMPOSITE_STUB']) && $arResult['COMPOSITE_STUB'] == 'Y');
?>

<div class="row">
	<div class="col-sm-4 col-5">
		<div class="head-logo">
			<a <? if ($page !== '/') { ?>href="/"<? } ?>>
				<img data-src="<?= SITE_TEMPLATE_PATH; ?>/img/static/logo.svg"
				     alt="alt">
			</a>
		</div>
	</div>
	<div class="col-sm-8 col-7 head_right-column">
		<form action="/search/" class="search-form tablet-small_hidden">
			<input type="text" class="search-form_input" placeholder="Поиск" name="q">
			<div class="search-form_wrapper-submit">
				<span class="ico-search"></span>
				<input type="submit" class="search-form_submit" value="">
			</div>
		</form>
		<a href="<?= $arParams['PATH_TO_PERSONAL']; ?>" class="head-user tablet-small_hidden">
			<span class="ico-user"></span>
		</a>
		<a href="/favorites/" class="head-fav">
			<span class="ico-fav"></span>
            <? if (count($_SESSION['FAVORITES'] ?? []) > 0) { ?>
				<span class="head-basket_number"><?= count($_SESSION['FAVORITES'] ?? []); ?></span>
            <? } ?>
		</a>
		<a href="<?= $arParams['PATH_TO_BASKET']; ?>" class="head-basket">
			<span class="head-basket_icon">
				<span class="ico-cart"></span>
				<? if ($arResult['NUM_PRODUCTS'] > 0) { ?>
					<span class="head-basket_number"><?= $arResult['NUM_PRODUCTS']; ?></span>
                <? } ?>
			</span>
			<span class="head-basket_text">
				<span class="text">Корзина:</span>
				<span class="head-basket_sum"><?= $arResult['TOTAL_PRICE']; ?></span>
			</span>
		</a>
		<div class="hamburger hamburger--spring">
			<div class="hamburger-box">
				<div class="hamburger-inner"></div>
			</div>
		</div>
	</div>
</div>
