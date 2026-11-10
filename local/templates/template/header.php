<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Page\Asset;

$page = $APPLICATION->GetCurPage();
$notShowViewed = $APPLICATION->GetProperty('not_show_viewed');

?>
<!DOCTYPE html>
<html class="no-js" lang="ru">
<head>
    <? $APPLICATION->ShowHead(); ?>
    <?
    Asset::getInstance()->addString('<meta content="' . SITE_TEMPLATE_PATH . '/browserconfig.xml" name="msapplication-config" />');
    Asset::getInstance()->addString('<meta http-equiv="X-UA-Compatible" content="IE=edge">');
    Asset::getInstance()->addString('<meta name="viewport" content="width=device-width, initial-scale=1">');
    Asset::getInstance()->addString('<link href="' . SITE_TEMPLATE_PATH . '/img/favicon.ico" rel="icon" type="image/png" />');
    Asset::getInstance()->addString('<link rel="apple-touch-icon" sizes="107x107" href="' . SITE_TEMPLATE_PATH . '/img/apple-touch-icon.png" />');
    ?>
	<title><? $APPLICATION->ShowTitle(); ?></title>
    <?
    Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/min.css");
    Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/main.css");
    Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/costume.css");

    Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . "/js/min.js");
    Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . "/js/main.js");
    Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . "/js/costume.js");

    ?>
</head>
<body>
<? $APPLICATION->ShowPanel(); ?>
<!--[if lt IE 10]>
<p class="browsehappy"><br>Вы используете <strong>устаревший</strong> браузер.
	Пожалуйста, <a href="http://browsehappy.com/">обновите его</a> для корректного
	отображения сайтов.</p>
<![endif]-->
<div class="global-wrapper">
	<div class="wrapper-loader">
		<div class="logo-loader"></div>
		<div class="loader-content"></div>
		<div class="loader-text">Загрузка</div>
	</div>
    <? if (!defined('ERROR_404') || ERROR_404 != 'Y') { ?>
		<header class="ui-header">
            <? $APPLICATION->IncludeComponent(
                "bitrix:sale.basket.basket.line",
                "header",
                Array(
                    "HIDE_ON_BASKET_PAGES" => "N",
                    "PATH_TO_AUTHORIZE" => "",
                    "PATH_TO_BASKET" => SITE_DIR . "basket/",
                    "PATH_TO_ORDER" => SITE_DIR . "order/",
                    "PATH_TO_PERSONAL" => SITE_DIR . "personal/",
                    "PATH_TO_PROFILE" => SITE_DIR . "personal/",
                    "PATH_TO_REGISTER" => SITE_DIR . "login/",
                    "POSITION_FIXED" => "N",
                    "SHOW_AUTHOR" => "N",
                    "SHOW_EMPTY_VALUES" => "Y",
                    "SHOW_NUM_PRODUCTS" => "Y",
                    "SHOW_PERSONAL_LINK" => "Y",
                    "SHOW_PRODUCTS" => "N",
                    "SHOW_REGISTRATION" => "Y",
                    "SHOW_TOTAL_PRICE" => "Y"
                )
            ); ?>
		</header>
		<!-- END UI-HEADER -->
		<div class="head_fixed-menu">
			<span class="fixed-menu_close-btn ico-close"></span>
			<form action="/search/" class="search-form tablet-small_visible">
				<input type="text" class="search-form_input" name="q" placeholder="Поиск">
				<div class="search-form_wrapper-submit">
					<span class="ico-search"></span>
					<input type="submit" class="search-form_submit" value="">
				</div>
			</form>
            <? $APPLICATION->IncludeComponent(
                "bitrix:menu",
                "catalog-top",
                Array(
                    "ALLOW_MULTI_SELECT" => "N",
                    "CHILD_MENU_TYPE" => "catalog_menu",
                    "DELAY" => "N",
                    "MAX_LEVEL" => "1",
                    "MENU_CACHE_GET_VARS" => array(0 => "",),
                    "MENU_CACHE_TIME" => "3600",
                    "MENU_CACHE_TYPE" => "N",
                    "MENU_CACHE_USE_GROUPS" => "Y",
                    "ROOT_MENU_TYPE" => "catalog_menu",
                    "USE_EXT" => "N"
                )
            ); ?>
            <? $APPLICATION->IncludeComponent(
                "bitrix:menu",
                "top",
                Array(
                    "ALLOW_MULTI_SELECT" => "N",
                    "CHILD_MENU_TYPE" => "top",
                    "DELAY" => "N",
                    "MAX_LEVEL" => "1",
                    "MENU_CACHE_GET_VARS" => array(0 => "",),
                    "MENU_CACHE_TIME" => "3600",
                    "MENU_CACHE_TYPE" => "N",
                    "MENU_CACHE_USE_GROUPS" => "Y",
                    "ROOT_MENU_TYPE" => "top",
                    "USE_EXT" => "N"
                )
            ); ?>
			<div class="tablet-small_visible">
                <? if ($USER->IsAuthorized()) { ?>
					<a href="/personal/" class="head-user"><span class="ico-user"></span>Войти в личный кабинет</a>
                <? } else { ?>
					<a href="/auth/" class="head-user"><span class="ico-user"></span>Войти в личный кабинет</a>
                <? } ?>
				<div class="mobile-logo">
					<a href="<?= ($page != SITE_DIR) ? SITE_DIR : ''; ?>">
						<img data-src="<?= SITE_TEMPLATE_PATH; ?>/img/static/logo.svg"
						     alt="alt">
					</a>
				</div>
			</div>
			<div class="fixed-menu_footer">
                <?$APPLICATION->IncludeComponent(
                    "2quick:header.contacts",
                    "",
                    Array(
                        "ADDRESS" => "197198, Санкт-Петербург, Зоологический пер., д.3",
                        "FACEBOOK" => "https://www.facebook.com/",
                        "INSTAGRAM" => "https://www.instagram.com",
                        "LINK_TO_PRIVACY_POLICY" => "/privacy-policy/",
                        "LINK_TO_TERMS_OF_USE" => "/terms-of-use/",
                        "PHONES" => array("+ 7 812 997-10-56", "+ 7 812 997-10-56")
                    )
                );?>
			</div>
		</div>
    <? } ?>
    <? $APPLICATION->ShowViewContent('product-fixed-panel'); ?>
	<!-- end head_fixed-menu -->
	<main class="main-content">
