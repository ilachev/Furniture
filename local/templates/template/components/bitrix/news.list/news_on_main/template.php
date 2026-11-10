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
if (!empty($arResult['ITEMS'])) {
    ?>
	<div class="news-section">
		<div class="container">
			<div class="news-section_header">
				<div class="section-title">Новости дизайна</div>
				<a href="/news/" class="see-all_btn mobile-hidden">Смотреть все<span class="ico-arrow"></span></a>
			</div>
			<div class="row news-content">
                <? foreach ($arResult["ITEMS"] as $key => $arItem): ?>
                    <?
                    if (!$arItem['PREVIEW_PICTURE']['SRC']) {
                        continue;
                    }
                    $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'],
                        CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
                    $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'],
                        CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"),
                        array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
                    $picture = CFile::ResizeImageGet($arItem['PREVIEW_PICTURE']['ID'],
                                ['width' => 657, 'height' => 509],
                                BX_RESIZE_IMAGE_PROPORTIONAL
                            )['src'];
                    ?>
	                <div class="col-md-6" id="<?= $this->GetEditAreaId($arItem['ID']); ?>">
		                <div class="news-item">
			                <a href="<?= $arItem['DETAIL_PAGE_URL'];?>">
				                <div class="news-item_img">
					                <img src="<?= $picture;?>" alt="alt">
				                </div>
				                <div class="news-item_desc">
					                <span class="news-item_title"><?= $arItem['NAME'];?></span>
					                <p>
                                        <?= $arItem['PREVIEW_TEXT'];?>
					                </p>
					                <span class="news-item_date"><?= $arItem['DISPLAY_ACTIVE_FROM'];?></span>
				                </div>
			                </a>
		                </div>
	                </div>
                <? endforeach; ?>
			</div>
			<div class="mobile-visible">
				<a href="/news/" class="see-all_mobile-btn main-btn">Все новости</a>
			</div>
		</div>
	</div>
<? } ?>