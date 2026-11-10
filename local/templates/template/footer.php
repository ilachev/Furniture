<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
?>
<? if (!defined('ERROR_404') || ERROR_404 != 'Y') { ?>
    <? if ($page !== '/' && $notShowViewed !== 'Y') { ?>
        <? $APPLICATION->IncludeComponent(
            "bitrix:catalog.products.viewed",
            "viewed",
            Array(
                "ACTION_VARIABLE" => "action_cpv",
                "ADDITIONAL_PICT_PROP_3" => "-",
                "ADDITIONAL_PICT_PROP_4" => "-",
                "ADD_PROPERTIES_TO_BASKET" => "Y",
                "ADD_TO_BASKET_ACTION" => "ADD",
                "BASKET_URL" => "/basket/",
                "CACHE_GROUPS" => "Y",
                "CACHE_TIME" => "3600",
                "CACHE_TYPE" => "A",
                "COMPONENT_TEMPLATE" => "viewed",
                "CONVERT_CURRENCY" => "N",
                "DEPTH" => "2",
                "DISPLAY_COMPARE" => "N",
                "ENLARGE_PRODUCT" => "STRICT",
                "HIDE_NOT_AVAILABLE" => "Y",
                "HIDE_NOT_AVAILABLE_OFFERS" => "Y",
                "IBLOCK_ID" => "",
                "IBLOCK_MODE" => "multi",
                "IBLOCK_TYPE" => "catalog",
                "LABEL_PROP_3" => array(),
                "LABEL_PROP_4" => array(),
                "LABEL_PROP_MOBILE_4" => "",
                "LABEL_PROP_POSITION" => "top-left",
                "MESS_BTN_ADD_TO_BASKET" => "В корзину",
                "MESS_BTN_BUY" => "Купить",
                "MESS_BTN_DETAIL" => "Подробнее",
                "MESS_BTN_SUBSCRIBE" => "Подписаться",
                "MESS_NOT_AVAILABLE" => "Нет в наличии",
                "PAGE_ELEMENT_COUNT" => "9",
                "PARTIAL_PRODUCT_PROPERTIES" => "N",
                "PRICE_CODE" => array(0 => "BASE",),
                "PRICE_VAT_INCLUDE" => "Y",
                "PRODUCT_BLOCKS_ORDER" => "price,props,sku,quantityLimit,quantity,buttons",
                "PRODUCT_ID_VARIABLE" => "id",
                "PRODUCT_PROPS_VARIABLE" => "prop",
                "PRODUCT_QUANTITY_VARIABLE" => "quantity",
                "PRODUCT_ROW_VARIANTS" => "[{'VARIANT':'2','BIG_DATA':false},{'VARIANT':'2','BIG_DATA':false},{'VARIANT':'2','BIG_DATA':false}]",
                "PRODUCT_SUBSCRIPTION" => "Y",
                "SECTION_CODE" => "",
                "SECTION_ELEMENT_CODE" => "",
                "SECTION_ELEMENT_ID" => $GLOBALS["CATALOG_CURRENT_ELEMENT_ID"],
                "SECTION_ID" => $GLOBALS["CATALOG_CURRENT_SECTION_ID"],
                "SHOW_CLOSE_POPUP" => "N",
                "SHOW_DISCOUNT_PERCENT" => "N",
                "SHOW_FROM_SECTION" => "N",
                "SHOW_MAX_QUANTITY" => "N",
                "SHOW_OLD_PRICE" => "Y",
                "SHOW_PRICE_COUNT" => "1",
                "SHOW_PRODUCTS_3" => "Y",
                "SHOW_PRODUCTS_4" => "Y",
                "SHOW_SLIDER" => "Y",
                "SLIDER_INTERVAL" => "3000",
                "SLIDER_PROGRESS" => "N",
                "TEMPLATE_THEME" => "blue",
                "USE_ENHANCED_ECOMMERCE" => "N",
                "USE_PRICE_COUNT" => "Y",
                "USE_PRODUCT_QUANTITY" => "N"
            )
        ); ?>
    <? } ?>
    <? $APPLICATION->IncludeComponent(
        "bitrix:news.list",
        "instagram",
        Array(
            "ACTIVE_DATE_FORMAT" => "d.m.Y",
            "ADD_SECTIONS_CHAIN" => "N",
            "AJAX_MODE" => "N",
            "AJAX_OPTION_ADDITIONAL" => "",
            "AJAX_OPTION_HISTORY" => "N",
            "AJAX_OPTION_JUMP" => "N",
            "AJAX_OPTION_STYLE" => "Y",
            "CACHE_FILTER" => "N",
            "CACHE_GROUPS" => "Y",
            "CACHE_TIME" => "36000000",
            "CACHE_TYPE" => "A",
            "CHECK_DATES" => "Y",
            "DETAIL_URL" => "",
            "DISPLAY_BOTTOM_PAGER" => "Y",
            "DISPLAY_DATE" => "Y",
            "DISPLAY_NAME" => "Y",
            "DISPLAY_PICTURE" => "Y",
            "DISPLAY_PREVIEW_TEXT" => "Y",
            "DISPLAY_TOP_PAGER" => "N",
            "FIELD_CODE" => array(0 => "", 1 => "",),
            "FILTER_NAME" => "",
            "HIDE_LINK_WHEN_NO_DETAIL" => "N",
            "IBLOCK_ID" => "12",
            "IBLOCK_TYPE" => "content",
            "INCLUDE_IBLOCK_INTO_CHAIN" => "N",
            "INCLUDE_SUBSECTIONS" => "Y",
            "MESSAGE_404" => "",
            "NEWS_COUNT" => "4",
            "PAGER_BASE_LINK_ENABLE" => "N",
            "PAGER_DESC_NUMBERING" => "N",
            "PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
            "PAGER_SHOW_ALL" => "N",
            "PAGER_SHOW_ALWAYS" => "N",
            "PAGER_TEMPLATE" => ".default",
            "PAGER_TITLE" => "Новости",
            "PARENT_SECTION" => "",
            "PARENT_SECTION_CODE" => "",
            "PREVIEW_TRUNCATE_LEN" => "",
            "PROPERTY_CODE" => array(0 => "LINK", 1 => "",),
            "SET_BROWSER_TITLE" => "N",
            "SET_LAST_MODIFIED" => "N",
            "SET_META_DESCRIPTION" => "N",
            "SET_META_KEYWORDS" => "N",
            "SET_STATUS_404" => "N",
            "SET_TITLE" => "N",
            "SHOW_404" => "N",
            "SORT_BY1" => "ACTIVE_FROM",
            "SORT_BY2" => "SORT",
            "SORT_ORDER1" => "DESC",
            "SORT_ORDER2" => "ASC",
            "STRICT_SECTION_CHECK" => "N"
        )
    ); ?>
<? } ?>
</main>
<? if (!defined('ERROR_404') || ERROR_404 != 'Y') { ?>
	<!-- end main-content -->
	<footer class="main-footer">
		<div class="container">
			<div class="row">
				<div class="col-xl-3 col-lg-4 col-md-4 footer_left-column">
					<div class="footer-logo">
						<a href="<?= ($page != SITE_DIR) ? SITE_DIR : 'javascript:void(0)'; ?>">
							<img data-src="<?= SITE_TEMPLATE_PATH; ?>/img/static/logo-2.svg"
							     alt="alt">
						</a>
					</div>
                    <? $APPLICATION->IncludeComponent(
                        "2quick:footer.contacts",
                        "",
                        Array(
                            "ADDRESS" => "Санкт-Петербург, ул. Зоологический переулок 1, офис 3",
                            "EMAIL" => "arhimeb@inbox.ru",
                            "PHONES" => array("+7 (812) 997-10-56", "+7 (812) 997-10-48")
                        )
                    ); ?>
					<a href="#callback" data-toggle="modal" class="main-btn footer_callback-btn">Заказать звонок</a>
				</div>
				<div class="col-xl-9 col-lg-8 col-md-8">
                        <? $APPLICATION->IncludeComponent(
                            "2quick:subscribe.quick.form",
                            "",
                            Array(
                                "AJAX_MODE" => "Y",
                                "FORMAT" => "html",
                                "INC_JQUERY" => "N",
                                "NOT_CONFIRM" => "Y",
                                "RUBRICS" => array(),
                                "SHOW_RUBRICS" => "N"
                            )
                        ); ?>
                    <? $APPLICATION->IncludeComponent(
                        "bitrix:menu",
                        "footer",
                        Array(
                            "ALLOW_MULTI_SELECT" => "N",
                            "CHILD_MENU_TYPE" => "footer",
                            "DELAY" => "N",
                            "MAX_LEVEL" => "1",
                            "MENU_CACHE_GET_VARS" => array(""),
                            "MENU_CACHE_TIME" => "3600",
                            "MENU_CACHE_TYPE" => "N",
                            "MENU_CACHE_USE_GROUPS" => "Y",
                            "ROOT_MENU_TYPE" => "footer",
                            "TITLE" => "Каталог",
                            "USE_EXT" => "N"
                        )
                    ); ?>
				</div>
			</div>
            <? $APPLICATION->IncludeComponent(
                "2quick:footer.socials",
                "",
                Array(
                    "FACEBOOK" => "https://www.facebook.com/",
                    "INSTAGRAM" => "https://www.instagram.com",
                    "LINK_TO_PRIVACY_POLICY" => "/",
                    "LINK_TO_TERMS_OF_USE" => "/"
                )
            ); ?>
		</div>
	</footer>
	<!-- end main-footer -->
<? } ?>
<div class="scroll-to-top"></div>
</div>
<!-- END GLOBAL-WRAPPER -->
<div aria-hidden="true" class="modal fade js-modal" id="callback" role="dialog">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
            <? $APPLICATION->IncludeComponent(
                "2quick:main.feedback",
                "get_call",
                array(
                    "AJAX_MODE" => "Y",
                    "AJAX_OPTION_ADDITIONAL" => "",
                    "AJAX_OPTION_HISTORY" => "N",
                    "AJAX_OPTION_JUMP" => "N",
                    "AJAX_OPTION_STYLE" => "Y",
                    "COMPONENT_TEMPLATE" => "",
                    "EMAIL_TO" => "user@mail.ru",
                    "EVENT_MESSAGE_ID" => array(
                        0 => "53",
                    ),
                    "INFOBLOCKADD" => "Y",
                    "INFOBLOCKID" => "9",
                    "OK_TEXT" => "Ok.",
                    "REQUIRED_FIELDS" => array(
                        0 => "NONE",
                    ),
                    "USE_CAPTCHA" => "N",
                    "MULTI_FILES" => "N",
                    "FILE_SEND" => "N"
                ),
                false
            ); ?>
		</div>
	</div>
</div>
<!-- end callback -->
<div aria-hidden="true" class="modal fade js-modal" role="dialog" id="application-accepted">
	<div class="modal-dialog modal-dialog_mod modal-dialog-centered" role="document">
		<div class="modal-content">
			<button class="close" data-dismiss="modal" type="button"><span class="ico-close"></span></button>
			<div class="popup-icon"><img data-src="<?= SITE_TEMPLATE_PATH; ?>/img/icons/ico-check.svg" alt=""></div>
			<p class="text-center modal-text">ваша заявка успешно отправлена!</p>
		</div>
	</div>
</div>

<div aria-hidden="true" class="modal fade js-modal" role="dialog" id="success-modal">
	<div class="modal-dialog modal-dialog_mod modal-dialog-centered" role="document">
		<div class="modal-content">
			<button class="close" data-dismiss="modal" type="button"><span class="ico-close"></span></button>
			<div class="popup-icon"><img data-src="<?= SITE_TEMPLATE_PATH; ?>/img/icons/ico-check.svg" alt=""></div>
			<p class="text-center modal-text"></p>
		</div>
	</div>
</div>

</body>
</html>
