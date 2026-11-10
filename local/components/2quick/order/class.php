<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Application,
    Bitrix\Main\Loader,
    \Bitrix\Iblock\Component\Tools,
    Bitrix\Sale,
    Bitrix\Main\Context,
    Bitrix\Currency\CurrencyManager,
    Bitrix\Sale\Order,
    Bitrix\Sale\Basket,
    Bitrix\Sale\Delivery,
    Bitrix\Sale\PaySystem,
    Bitrix\Iblock\ElementTable,
    Bitrix\Sale\Fuser,
    Bitrix\Main\Config\Option,
    Bitrix\Sale\DiscountCouponsManager,
    Bitrix\Main\Engine\ActionFilter\Authentication,
    Bitrix\Main\Engine\ActionFilter,
    Bitrix\Main\Grid\Declension;

Loader::includeModule('iblock');
Loader::includeModule('sale');


use Bitrix\Main\Engine\Contract\Controllerable;

CJSCore::Init(array("fx", "ajax"));

class tqOrder extends \CBitrixComponent implements Controllerable
{

    private $order;
    private $arUserProps = [
        'EMAIL' => 'EMAIL',
        'NAME' => 'NAME',
        'PHONE' => 'PERSONAL_PHONE',
        'LOCATION' => 'PERSONAL_CITY',
        'INDEX' => 'PERSONAL_ZIP',
    ];
    private $delivery_id = 2;

    /**
     * @return array
     */
    private function getCouponInfoCustom()
    {
        $result = array(
            'COUPON' => '',
            'COUPON_LIST' => array()
        );

        $coupons = DiscountCouponsManager::get(true, array(), true, true);
        if (!empty($coupons)) {
            foreach ($coupons as &$coupon) {
                if ($result['COUPON'] == '') {
                    $result['COUPON'] = $coupon['COUPON'];
                }

                if ($coupon['STATUS'] == DiscountCouponsManager::STATUS_NOT_FOUND || $coupon['STATUS'] == DiscountCouponsManager::STATUS_FREEZE) {
                    $coupon['JS_STATUS'] = 'BAD';
                } elseif ($coupon['STATUS'] == DiscountCouponsManager::STATUS_NOT_APPLYED || $coupon['STATUS'] == DiscountCouponsManager::STATUS_ENTERED) {
                    $coupon['JS_STATUS'] = 'ENTERED';

                    if ($coupon['STATUS'] == DiscountCouponsManager::STATUS_NOT_APPLYED) {
                        $coupon['STATUS_TEXT'] = DiscountCouponsManager::getCheckCodeMessage(DiscountCouponsManager::COUPON_CHECK_OK);
                        $coupon['CHECK_CODE_TEXT'] = array($coupon['STATUS_TEXT']);
                    }
                } else {
                    $coupon['JS_STATUS'] = 'APPLYED';
                }

                $coupon['JS_CHECK_CODE'] = '';

                if (isset($coupon['CHECK_CODE_TEXT'])) {
                    $coupon['JS_CHECK_CODE'] = is_array($coupon['CHECK_CODE_TEXT'])
                        ? implode('<br>', $coupon['CHECK_CODE_TEXT'])
                        : $coupon['CHECK_CODE_TEXT'];
                }

                $result['COUPON_LIST'][] = $coupon;
            }

            unset($coupon);
        }
        unset($coupons);

        return $result;

    }

    /**
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\NotImplementedException
     * @throws \Bitrix\Main\NotSupportedException
     * @throws \Bitrix\Main\ObjectNotFoundException
     * @throws \Bitrix\Main\SystemException
     */
    private function setOrder()
    {
        global $USER;

        if ($USER->IsAuthorized()) {
            $rsUser = CUser::GetByID($USER->GetID());
            $arUser = $rsUser->Fetch();
            $this->arResult['USER_INFO'] = $arUser;
        }
        $result = null;
        \Bitrix\Main\Loader::includeModule('catalog');
        \Bitrix\Main\Loader::includeModule('sale');
        $basket = \Bitrix\Sale\Basket::loadItemsForFUser(\Bitrix\Sale\Fuser::getId(),
            Bitrix\Main\Context::getCurrent()->getSite());
        $this->order = \Bitrix\Sale\Order::create(SITE_ID, $USER->GetId() ?: \CSaleUser::GetAnonymousUserID());
        $this->order->setPersonTypeId(1);
        $this->order->setBasket($basket);

        // Создаём одну отгрузку и устанавливаем способ доставки - "Без доставки" (он служебный)
        $shipmentCollection = $this->order->getShipmentCollection();
        $shipment = $shipmentCollection->createItem();
        $service = Delivery\Services\Manager::getById($this->delivery_id);
        $shipment->setFields(array(
            'DELIVERY_ID' => $service['ID'],
            'DELIVERY_NAME' => $service['NAME'],
        ));

        $paymentCollection = $this->order->getPaymentCollection();
        $extPayment = $paymentCollection->createItem();
        $extPayment->setField("SUM", $this->order->getPrice());
        $arPaySystemServiceAll = Sale\PaySystem\Manager::getListWithRestrictions($extPayment);


        foreach ($arPaySystemServiceAll as $item) {
            if ($item["ACTION_FILE"] == 'inner') {
                continue;
            }
            $this->arResult['PAYMENT'][] = $item;
        }

        $discountPrice = Sale\PriceMaths::roundPrecision($this->order->getDiscountPrice() + ($basket->getBasePrice() - $basket->getPrice()));
        $arWeightParams = [
            'WEIGHT_UNIT' => htmlspecialcharsbx(Option::get('sale', 'weight_unit', false, SITE_ID)),
            'WEIGHT_KOEF' => htmlspecialcharsbx(Option::get('sale', 'weight_koef', 1, SITE_ID)),
        ];

        $arDeliv = CSaleDelivery::GetByID($this->delivery_id);
        $deliveryPrice = $arDeliv['PRICE'];
        if (empty($deliveryPrice)) {
            $deliveryPrice = 0;
        }

        $pattern = '/([\d,]+\.)(\d+)(.*)$/';
        $FORMATED_BASKET_SUM = str_replace('.', ' ', preg_replace($pattern, '$1<sup>$2</sup>$3', $basket->getPrice()));
        $FORMATED_SUM = str_replace('.', ' ',
            preg_replace($pattern, '$1<sup>$2</sup>$3', $this->order->getPrice() + $deliveryPrice));
        $FORMATED_DISCOUNT_PRICE = str_replace('.', ' ', preg_replace($pattern, '$1<sup>$2</sup>$3', $discountPrice));
        $FORMATED_DELIVERY_PRICE = str_replace('.', ' ', preg_replace($pattern, '$1<sup>$2</sup>$3', $deliveryPrice));

        $percentBase = $this->order->getPrice() + $deliveryPrice;
        $DISCOUNT_PERCENT = $percentBase > 0 ? ($discountPrice / $percentBase) * 100 : 0;
        $CURRENCY = str_replace(0, '', CCurrencyLang::CurrencyFormat(0, $this->order->getCurrency(), true));


        $this->arResult['INFO_ORDER'] = [
            'BASKET_SUM' => $basket->getPrice(),
            'FORMATED_BASKET_SUM' => $FORMATED_BASKET_SUM . $CURRENCY,

            'SUM' => $this->order->getPrice() + $deliveryPrice,
            'FORMATED_SUM' => $FORMATED_SUM . $CURRENCY,

            'PRICE_FORMATED' => CurrencyFormat($basket->getBasePrice() + $deliveryPrice, $this->order->getCurrency()),

            'DISCOUNT_PRICE' => $discountPrice,
            'FORMATED_DISCOUNT_PRICE' => $FORMATED_DISCOUNT_PRICE . $CURRENCY,
            'DISCOUNT_PERCENT' => $DISCOUNT_PERCENT,
            'DISCOUNT_PERCENT_FORMATED' => number_format($DISCOUNT_PERCENT, 0, '', '') . '%',

            'DELIVERY_PRICE' => $deliveryPrice,
            'FORMATED_DELIVERY_PRICE' => $FORMATED_DELIVERY_PRICE . $CURRENCY,

            'CURRENCY' => $this->order->getCurrency(),

            'BASKET_COUNT' => $this->declOfNum(count($basket->getBasketItems()), ['товар', 'товара', 'товаров']),

            'BASKET_WEIGHT' => roundEx(doubleval($basket->getWeight() / $arWeightParams["WEIGHT_KOEF"]),
                    SALE_WEIGHT_PRECISION) . " " . $arWeightParams["WEIGHT_UNIT"],

            'DELIVERY_ID' => $this->delivery_id
        ];

        $propertyCollection = $this->order->getPropertyCollection();
        $arProps = $propertyCollection->getArray();
        foreach ($arProps['properties'] as $arProp) {
            if ($arProp['UTIL'] == 'Y') {
                continue;
            }
            if ($this->arUserProps[$arProp['CODE']]) {
                $arProp['VALUE'] = $this->arResult['USER_INFO'][$this->arUserProps[$arProp['CODE']]];
            }
            $this->arResult['PROPERTIES'][$arProp['PROPS_GROUP_ID']][] = $arProp;
        }


    }

    /**
     * @param $number
     * @param $titles
     * @return string
     */
    private function declOfNum($number, $titles)
    {
        $cases = array(2, 0, 1, 1, 1, 2);
        return $number . " " . $titles[($number % 100 > 4 && $number % 100 < 20) ? 2 : $cases[min($number % 10, 5)]];
    }

    /**
     * @param $type
     * @return Declension|bool
     */
    private function getPeriodDeclension($type)
    {
        $declension = false;
        if ($type == 'MIN') {
            $declension = new Declension('минуты', 'минут', 'минут');
        }
        if ($type == 'H') {
            $declension = new Declension('часа', 'часов', 'часов');
        }
        if ($type == 'D') {
            $declension = new Declension('дня', 'дней', 'дней');
        }
        if ($type == 'M') {
            $declension = new Declension('месяца', 'месяцев', 'месяцев');
        }
        return $declension;
    }

    /**
     * @param $config
     * @return string
     */
    private function getDeliveryPeriod($config)
    {
        $period = '';
        if (!empty($config['MAIN']['ITEMS']['PERIOD']['ITEMS']['FROM']['VALUE']) || !empty($config['MAIN']['ITEMS']['PERIOD']['ITEMS']['TO']['VALUE'])) {
            $periodDeclantion = $this->getPeriodDeclension($config['MAIN']['ITEMS']['PERIOD']['ITEMS']['TYPE']['VALUE']);
            $period = '(';
            if (!empty($config['MAIN']['ITEMS']['PERIOD']['ITEMS']['FROM']['VALUE'])) {
                $period .= sprintf('от %d %s', $config['MAIN']['ITEMS']['PERIOD']['ITEMS']['FROM']['VALUE'],
                    $periodDeclantion->get($config['MAIN']['ITEMS']['PERIOD']['ITEMS']['FROM']['VALUE']));
            }
            if (!empty($config['MAIN']['ITEMS']['PERIOD']['ITEMS']['TO']['VALUE'])) {
                $period .= sprintf(' до %d %s', $config['MAIN']['ITEMS']['PERIOD']['ITEMS']['TO']['VALUE'],
                    $periodDeclantion->get($config['MAIN']['ITEMS']['PERIOD']['ITEMS']['TO']['VALUE']));
            }
            $period .= ')';
        }

        return $period;
    }

    /**
     * @throws \Bitrix\Main\SystemException
     */
    private function getDeliveries()
    {

        $shipmentCollection = $this->order->getShipmentCollection();
        $shipment = $shipmentCollection->createItem();
        $shipmentItemCollection = $shipment->getShipmentItemCollection();
        $shipment->setField('CURRENCY', $this->order->getCurrency());
        foreach ($this->order->getBasket() as $item) {
            $shipmentItem = $shipmentItemCollection->createItem($item);
            $shipmentItem->setQuantity($item->getQuantity());
        }
        $arRestricted = Delivery\Services\Manager::getRestrictedObjectsList($shipment);


        foreach ($arRestricted as $arItem) {
            $arStores = Delivery\ExtraServices\Manager::getStoresList($arItem->getId());
            foreach ($arStores as $val) {
                $arStoreId[$val] = $val;
            }

            $config = $arItem->getConfig();

            if ($arItem->getParentId() > 0) {

                $this->arResult['DELIVERIES'][] = [
                    'ID' => $arItem->getId(),
                    'CODE' => $arItem->getCode(),
                    'NAME' => $arItem->getName(),
                    'DESCRIPTION' => $arItem->getDescription(),
                    'STORES' => $arStores,
                    'CHECKED' => $this->delivery_id == $arItem->getId() ? 'Y' : 'N',
                    'PRICE' => $config['MAIN']['ITEMS']['PRICE']['VALUE'],
                    'PRICE_FORMATED' => CurrencyFormat($config['MAIN']['ITEMS']['PRICE']['VALUE'],
                        $arItem->getCurrency()),
                    'CURRENCY' => $arItem->getCurrency(),
                    'PERIOD' => $this->getDeliveryPeriod($config),
                    'LOGO_PATH' => $arItem->getLogotipPath()
                ];
            } else {
                $this->arResult['DELIVERIES'][] = [
                    'ID' => $arItem->getId(),
                    'NAME' => $arItem->getName(),
                    'DESCRIPTION' => $arItem->getDescription(),
                    'STORES' => $arStores,
                    'CHECKED' => $this->delivery_id == $arItem->getId() ? 'Y' : 'N',
                    'PRICE' => $config['MAIN']['ITEMS']['PRICE']['VALUE'],
                    'PRICE_FORMATED' => CurrencyFormat($config['MAIN']['ITEMS']['PRICE']['VALUE'],
                        $arItem->getCurrency()),
                    'CURRENCY' => $arItem->getCurrency(),
                    'PERIOD' => $this->getDeliveryPeriod($config),
                    'LOGO_PATH' => $arItem->getLogotipPath()
                ];
            }
            if ($this->delivery_id == $arItem->getId()) {
                $this->arResult['ADDITIONAL_SERVICES'] = $arItem->getExtraServices()->getItems();
            }
        }
        if ($arStoreId) {
            $dbList = CCatalogStore::GetList(
                array("SORT" => "DESC", "ID" => "DESC"),
                array("ACTIVE" => "Y", "ID" => $arStoreId, "ISSUING_CENTER" => "Y", "+SITE_ID" => $this->getSiteId()),
                false,
                false,
                array(
                    "ID",
                    "TITLE",
                    //"ADDRESS",
                    "DESCRIPTION",
                    "IMAGE_ID",
                    "PHONE",
                    "SCHEDULE",
                    "GPS_N",
                    "GPS_S",
                    "ISSUING_CENTER",
                    "SITE_ID"
                )
            );
            while ($arStoreTmp = $dbList->Fetch()) {
                $this->arResult['STORES'][$arStoreTmp["ID"]] = $arStoreTmp;
            }
        }


    }

    /**
     * @return array
     */
    public function configureActions()
    {
        // Сбрасываем фильтры по-умолчанию (ActionFilter\Authentication и ActionFilter\HttpMethod)
        // Предустановленные фильтры находятся в папке /bitrix/modules/main/lib/engine/actionfilter/
        return [
            'GetUpdateDeliveries' => [ // Ajax-метод
                'prefilters' => [],
            ],
            'GetDeliveryPrice' => [ // Ajax-метод
                'prefilters' => [],
            ],
            'CreateOrder' => [ // Ajax-метод
                'prefilters' => [],
            ],
            'SetCoupon' => [ // Ajax-метод
                'prefilters' => [],
            ],
            'DeleteCoupon' => [ // Ajax-метод
                'prefilters' => [],
            ],
            'GetSdek' => [ // Ajax-метод
                'prefilters' => [],
            ],
            'RemoveItem' => [ // Ajax-метод
                'prefilters' => [],
            ],
        ];
    }

    /**
     * @param $userCityId
     * @param $deliveryId
     * @param $paySystemId
     * @return array
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     * @throws \Bitrix\Main\ArgumentTypeException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\NotImplementedException
     * @throws \Bitrix\Main\NotSupportedException
     * @throws \Bitrix\Main\ObjectNotFoundException
     * @throws \Bitrix\Main\SystemException
     */
    public function GetDeliveryPriceAction($userCityId, $deliveryId, $paySystemId)
    {


        global $USER;
        \Bitrix\Main\Loader::includeModule('catalog');
        \Bitrix\Main\Loader::includeModule('sale');
        $basket = \Bitrix\Sale\Basket::loadItemsForFUser(\Bitrix\Sale\Fuser::getId(),
            Bitrix\Main\Context::getCurrent()->getSite());
        $order = \Bitrix\Sale\Order::create(SITE_ID, $USER->GetId() ?: \CSaleUser::GetAnonymousUserID());
        $order->setPersonTypeId(1);
        $order->setBasket($basket);

        /** @var \Bitrix\Sale\PropertyValueCollection $orderProperties */
        $orderProperties = $order->getPropertyCollection();
        /** @var \Bitrix\Sale\PropertyValue $orderDeliveryLocation */
        $orderDeliveryLocation = $orderProperties->getDeliveryLocation();


        $orderDeliveryLocation->setValue(CSaleLocation::getLocationCODEbyID($userCityId)); // В какой город "доставляем" (куда доставлять).

        /** @var \Bitrix\Sale\ShipmentCollection $shipmentCollection */
        $shipmentCollection = $order->getShipmentCollection();

        $delivery = \Bitrix\Sale\Delivery\Services\Manager::getObjectById($deliveryId);
        /** @var \Bitrix\Sale\Shipment $shipment */
        $shipment = $shipmentCollection->createItem($delivery);

        /** @var \Bitrix\Sale\ShipmentItemCollection $shipmentItemCollection */
        $shipmentItemCollection = $shipment->getShipmentItemCollection();
        /** @var \Bitrix\Sale\BasketItem $basketItem */
        foreach ($basket as $basketItem) {
            $item = $shipmentItemCollection->createItem($basketItem);
            $item->setQuantity($basketItem->getQuantity());
        }

        /** @var \Bitrix\Sale\PaymentCollection $paymentCollection */
        $paymentCollection = $order->getPaymentCollection();
        /** @var \Bitrix\Sale\Payment $payment */
        $payment = $paymentCollection->createItem(
            \Bitrix\Sale\PaySystem\Manager::getObjectById($paySystemId)
        );
        $payment->setField("SUM", $order->getPrice());
        $payment->setField("CURRENCY", $order->getCurrency());
        $arPaySystemServiceAll = Sale\PaySystem\Manager::getListWithRestrictions($payment);


        foreach ($arPaySystemServiceAll as $item) {
            if ($item["ACTION_FILE"] == 'inner') {
                continue;
            }
            $arPayments[] = $item;
        }
        $payment_html = '';
        $arAvailPaymentsIDs = array_map(function ($item) {
            return $item['ID'];
        }, $arPayments);;

        foreach ($arPayments as $key => $arPayment) {
            $isActive = '';
            $checked = '';
            if (!empty($paySystemId) && in_array($paySystemId, $arAvailPaymentsIDs)) {
                if ($arPayment['ID'] == $paySystemId) {
                    $checked = 'checked';
                    $isActive = ' is-active';
                }
            } else {
                if ($key == 0) {
                    $checked = 'checked';
                    $isActive = ' is-active';
                }
            }
            $payment_html .= '<div class="col-md-4">
							<div class="payment-item' . $isActive . '">
								<label class="unified-radio">
									<input value="' . $arPayment['ID'] . '"
									       type="radio"
									       class="input-radio"
                                           ' . $checked . '
									       name="payment">
									<span class="radio-text"></span>
								</label>
								<span class="payment-item_icon">
								    <img data-src="' . CFile::GetPath($arPayment['LOGOTIP']) . '" alt="alt">
								</span>
                                ' . $arPayment['NAME'] . '
							</div>
						</div>';
        }
        $result = [
            'basket_sum' => sprintf('%s <small>руб</small>', number_format($basket->getPrice(), '0', ' ', ' ')),
            'delivery_price' => CurrencyFormat($order->getDeliveryPrice(), "RUB"),
            'total' => CurrencyFormat($order->getPrice(), "RUB"),
            'delivery' => $order->getDeliveryPrice(),
            'payment' => $payment_html
        ];
        return $result;
    }

    /**
     * @param $userCityId
     * @param $current_group
     * @return array|null
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     * @throws \Bitrix\Main\ArgumentTypeException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\NotSupportedException
     * @throws \Bitrix\Main\ObjectNotFoundException
     */
    public function GetUpdateDeliveriesAction($userCityId)
    {
        global $USER;
        $result = null;
        \Bitrix\Main\Loader::includeModule('catalog');
        \Bitrix\Main\Loader::includeModule('sale');
        $basket = \Bitrix\Sale\Basket::loadItemsForFUser(\Bitrix\Sale\Fuser::getId(),
            Bitrix\Main\Context::getCurrent()->getSite());
        $order = \Bitrix\Sale\Order::create(SITE_ID, $USER->GetId() ?: \CSaleUser::GetAnonymousUserID());
        $order->setPersonTypeId(1);
        $order->setBasket($basket);

        $orderProperties = $order->getPropertyCollection();
        $orderDeliveryLocation = $orderProperties->getDeliveryLocation();
        $orderDeliveryLocation->setValue(CSaleLocation::getLocationCODEbyID($userCityId));

        $shipmentCollection = $order->getShipmentCollection();
        $shipment = $shipmentCollection->createItem();
        $shipmentItemCollection = $shipment->getShipmentItemCollection();
        $shipment->setField('CURRENCY', $order->getCurrency());
        foreach ($order->getBasket() as $item) {
            $shipmentItem = $shipmentItemCollection->createItem($item);
            $shipmentItem->setQuantity($item->getQuantity());
        }
        $arDelivery = Delivery\Services\Manager::getRestrictedObjectsList($shipment);


        foreach ($arDelivery as $key => $itemShipment) {
            /** @var \Bitrix\Sale\ShipmentCollection $shipmentCollection */
            $shipmentCollection = $order->getShipmentCollection();
            $shipmentCollection->clearCollection();
            /** @var \Bitrix\Sale\Shipment $shipment */
            $shipment = $shipmentCollection->createItem($itemShipment);

            /** @var \Bitrix\Sale\ShipmentItemCollection $shipmentItemCollection */
            $shipmentItemCollection = $shipment->getShipmentItemCollection();
            /** @var \Bitrix\Sale\BasketItem $basketItem */
            foreach ($basket as $basketItem) {
                $item = $shipmentItemCollection->createItem($basketItem);
                $item->setQuantity($basketItem->getQuantity());
            }


            $calculationResult = $order->getShipmentCollection()->calculateDelivery();
            if ($calculationResult->isSuccess()) {
                $item = $calculationResult->getData();
                if (!empty($item["CALCULATED_DELIVERIES"])) {
                    $current = $item["CALCULATED_DELIVERIES"][0];
                    $period = $current->getPeriodDescription();
                }
                $deliveryPrice = $order->getDeliveryPrice();
            } else {
                $deliveryPrice = '';
            }
            if ($deliveryPrice === '') {
                $deliveryPrice = null;
            }
            $delinfo[$key] = ['PRICE' => $deliveryPrice, 'PERIOD' => $period];

        }

        $arDeliveries = \Bitrix\Sale\Delivery\Services\Manager::getActiveList();

        foreach ($arDeliveries as &$arItem) {
            /* if ($arItem['ID'] == 3) {
                 unset($arDeliveries[$arItem['ID']]);
                 continue;
             }*/
            if ($arItem['CLASS_NAME'] == '\Bitrix\Sale\Delivery\Services\Group') {
                $arDelivs[$arItem['ID']] = $arItem;
                unset($arDeliveries[$arItem['ID']]);
                continue;
            }
            if ($arItem['PARENT_ID'] > 0 && $arDeliveries[$arItem['PARENT_ID']] && $arItem['CLASS_NAME'] == '\Bitrix\Sale\Delivery\Services\AutomaticProfile') {
                $parent = $arItem['PARENT_ID'];
                $arItem['PARENT_ID'] = $arDeliveries[$arItem['PARENT_ID']]['PARENT_ID'];
                unset($arDeliveries[$parent]);
            }


        };
        unset($arItem);

        $arcnt = [];
        foreach ($arDeliveries as $arItem) {
            $arDelivs[$arItem['PARENT_ID']]['DELIVERIES'][] = $arItem;
        }
        $html = '';
        foreach ($arDelivs as $arGroup) {
            if (!$arGroup['DELIVERIES']) {
                continue;
            }

            ?>
            <?
            $avail = 0;

            foreach ($arGroup['DELIVERIES'] as $key => $arDelivery) {
                if (!$delinfo[$arDelivery['ID']]) {
                    continue;
                }
                $avail++;
                $arDelivery['INFO'] = $delinfo[$arDelivery['ID']];
                if ($arDelivery['INFO']['PRICE']) {
                    $cost = CurrencyFormat($arDelivery['INFO']['PRICE'], "RUB");
                } else {
                    $cost = '';
                }
                if ($arDelivery['INFO']['PERIOD']) {
                    $period = $arDelivery['INFO']['PERIOD'];
                } else {
                    $period = '';
                }
                $name = sprintf('%s - %s %s (%s) ', $arDelivery['NAME'], $cost, '₽', $period);

                if ($arDelivery['CODE'] == 'sdek:pickup') {
                    $sdek_btn = '
                                  <a href="#points" data-fancybox="points" class="cart__point-button">
                                <svg width="13" height="17" viewBox="0 0 13 17" class="cart__point-icon">
                                    <use xlink:href="' . SITE_TEMPLATE_PATH . '/img/sprite.svg#placeholder"></use>
                                </svg>
                                <span class="cart__point-text">' . GetMessage('TQ_ORDER_SELECT_DERLIVERY_POSTOMAT') . '</span>
                            </a>
                            <input name="PVZ_ID" type="hidden" class="cart__point-input">
                            <span class="cart__point-title"
                                  data-error="' . GetMessage('TQ_ORDER_SELECT_DERLIVERY_POSTOMAT_NEEDLE') . '"></span>
                                  
                                  ';
                } else {
                    $sdek_btn = '';
                }
                if ($avail == 1) {
                    $checked = 'checked';
                    $isActive = ' is-active';
                } else {
                    $checked = '';
                    $isActive = '';
                }

                $delPriceFormated = ($arDelivery['INFO']['PRICE'] == 0) ? 'Бесплатно' : CurrencyFormat($arDelivery['INFO']['PRICE'],
                    $arDelivery['CURRENCY']);

                $html .= '<div class="col-md-4">
							<div class="delivery-item' . $isActive . '">
								<div class="delivery-item_header">
									<span class="delivery-item_icon">
										<img src="' . CFile::GetPath($arDelivery['LOGOTIP']) . '"
										     alt="alt">
									</span>
									<div class="delivery-item_price">
                                        ' . $delPriceFormated . '
									</div>
								</div>
								<label class="unified-radio">
									<input value="' . $arDelivery['ID'] . '"
                                           ' . $checked . '
									       type="radio"
									       class="input-radio"
									       name="delivery">
									<span class="radio-text">' . $arDelivery['NAME'] . '</span>
								</label>
								<p>' . $arDelivery['DESCRIPTION'] . '</p>
							</div>
						</div>';

            } ?>
            <?
            /*$arcnt[$arGroup['ID']] = sprintf('(доступно %s',
                DDS\Tools::declOfNum($avail, ['метод', 'метода', 'методов']));*/
        }
        //$sdek_html = $this->GetSdekAction($userCityId);

        $result = ['html' => $html, /*'sdek' => $sdek_html*/];
        return $result;
    }

    /**
     * Получение торговых других размеров
     * @param $intMainProduct
     * @param $color
     * @return mixed
     */
    private function getProductSizesByColor($intMainProduct, $color)
    {
        $arSizes = [];
        $select = Array('ID', 'IBLOCK_ID', 'PROPERTY_SIZE');
        $filter = Array(
            'PROPERTY_CML2_LINK' => $intMainProduct,
            'PROPERTY_COLOR_VALUE' => $color,
            'ACTIVE_DATE' => 'Y',
            'ACTIVE' => 'Y',
            'CATALOG_AVAILABLE' => 'Y'
        );
        $res = CIBlockElement::GetList(Array(), $filter, false, false, $select);
        while ($ob = $res->Fetch()) {
            $arSizes[$ob['ID']] = [
                'ID' => $ob['ID'],
                'SIZE' => $ob['PROPERTY_SIZE_VALUE']
            ];
        }
        return $arSizes;
    }

    /**
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     */
    private function getBasketItems()
    {
        $result = [];
        $basket = Sale\Basket::loadItemsForFUser(Sale\Fuser::getId(), Bitrix\Main\Context::getCurrent()->getSite());
        $context = new \Bitrix\Sale\Discount\Context\Fuser($basket->getFUserId());
        $discounts = \Bitrix\Sale\Discount::buildFromBasket($basket, $context);
        if ($discounts) {
            $r = $discounts->calculate();

            $resdisk = $r->getData();
            if (isset($resdisk['BASKET_ITEMS'])) {
                $basket->applyDiscount($resdisk['BASKET_ITEMS']);
            }
        }
        $basketItems = $basket->getBasketItems();
        foreach ($basketItems as $basketItem) {
            $arProducts[] = $basketItem->getProductId();

            $mxResult = CCatalogSku::GetProductInfo(
                $basketItem->getProductId()
            );
            if (is_array($mxResult)) {
                $mainID = $mxResult['ID'];
            } else {
                $mainID = '';
            }
            $result[$basketItem->getProductId()] = [
                'ID' => $basketItem->getId(),
                'PRODUCT_ID' => $basketItem->getProductId(),
                'PRICE' => $basketItem->getPrice(),
                'BASE_PRICE' => $basketItem->getBasePrice(),
                'DISCOUNT_PRICE' => $basketItem->getDiscountPrice(),
                'QUANTITY' => $basketItem->getQuantity(),
                'SUM' => $basketItem->getFinalPrice(),
                'NAME' => $basketItem->getField('NAME'),
                'CURRENCY' => $basketItem->getField('CURRENCY'),
                'MEASURE_NAME' => $basketItem->getField('MEASURE_NAME'),
                'MAIN_PRODUCT_ID' => $mainID,
                'PROPS' => $basketItem->getPropertyCollection()->getPropertyValues()
            ];

        }
        $prod_ids = array_map(function ($item) {
            return $item['MAIN_PRODUCT_ID'];
        }, $result);

        $select = Array('ID', 'IBLOCK_ID', 'PREVIEW_PICTURE', 'PROPERTY_ART_NUMBER');
        $filter = Array('=ID' => $prod_ids, 'ACTIVE_DATE' => 'Y', 'ACTIVE' => 'Y');
        $res = CIBlockElement::GetList(Array(), $filter, false, false, $select);
        while ($ob = $res->Fetch()) {
            $mainProducts[$ob['ID']] = $ob;
        }

        if ($result) {
            $arSelect = Array(
                "ID",
                "IBLOCK_ID",
                "DETAIL_PAGE_URL",
                "NAME",
                "DATE_ACTIVE_FROM",
                "PROPERTY_*",
            );
            $arFilter = Array('=ID' => array_keys($result));
            $res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
            while ($ob = $res->GetNextElement()) {
                $el = $ob->GetFields();
                $el ['PROPERTIES'] = $ob->GetProperties();
                $mainProdcutID = $result[$el['ID']]['MAIN_PRODUCT_ID'];
                if ($el['PREVIEW_PICTURE']) {
                    $result[$el['ID']]['PREVIEW_PICTURE'] = CFile::ResizeImageGet($el['PREVIEW_PICTURE'],
                        array("width" => 126, "height" => 126), BX_RESIZE_IMAGE_PROPORTIONAL);
                } elseif (!empty($mainProducts[$mainProdcutID]['PREVIEW_PICTURE'])) {
                    $result[$el['ID']]['PREVIEW_PICTURE'] = CFile::ResizeImageGet($mainProducts[$mainProdcutID]['PREVIEW_PICTURE'],
                        array("width" => 126, "height" => 126), BX_RESIZE_IMAGE_PROPORTIONAL);
                }
                if (!empty($el['PROPERTIES']['ARTNUMBER']['VALUE'])) {
                    $result[$el['ID']]['ARTNUMBER'] = $el['PROPERTIES']['ARTNUMBER']['VALUE'];
                } elseif (!empty($mainProducts[$mainProdcutID]['PROPERTY_ARTNUMBER_VALUE'])) {
                    $result[$el['ID']]['ARTNUMBER'] = $mainProducts[$mainProdcutID]['PROPERTY_ARTNUMBER_VALUE'];
                }
                if (!empty($el['PROPERTIES']['SIZE']['VALUE'])) {
                    $result[$el['ID']]['SIZE'] = $el['PROPERTIES']['SIZE']['VALUE'];
                }
                if (!empty($el['PROPERTIES']['COLOR']['VALUE'])) {
                    $result[$el['ID']]['COLOR'] = $el['PROPERTIES']['COLOR']['VALUE'];
                    $result[$el['ID']]['COLOR_HEX'] = $el['PROPERTIES']['COLOR']['VALUE_XML_ID'];
                }

                $result[$el['ID']]['DETAIL_PAGE_URL'] = $el['DETAIL_PAGE_URL'];
                if (!empty($result[$el['ID']]['MAIN_PRODUCT_ID'])) {
                    $result[$el['ID']]['SIZES'] = $this->getProductSizesByColor($result[$el['ID']]['MAIN_PRODUCT_ID'],
                        $result[$el['ID']]['COLOR']);
                }
            }

            $filter = ['=ID' => array_keys($result)];
            $obProducts = ElementTable::getList([
                'select' => ['PREVIEW_PICTURE', 'ID'],
                'filter' => $filter,
            ]);
            while ($el = $obProducts->fetch()) {
                if ($el['PREVIEW_PICTURE']) {
                    $result[$el['ID']]['PREVIEW_PICTURE'] = CFile::ResizeImageGet($el['PREVIEW_PICTURE'],
                        array("width" => 126, "height" => 126), BX_RESIZE_IMAGE_PROPORTIONAL);
                }
            }
        }

        return $result;
    }

    /**
     * @param $coupon
     * @return array
     */
    public function SetCouponAction($coupon)
    {
        \CCatalogDiscountCoupon::SetCoupon($coupon);
        $html = '';
        $arCoupons = $this->getCouponInfoCustom();
        if ($arCoupons) {
            foreach ($arCoupons['COUPON_LIST'] as $arCoupon) {
                if ($arCoupon['JS_STATUS'] == 'BAD' || 'ENTERED') {
                    $class = 'text-danger';
                } else {
                    $class = 'text-muted';
                }

                $html .= '<div class="basket-coupon-alert ' . $class . '">
						<span class="basket-coupon-text">
							<strong>' . $arCoupon['COUPON'] . '</strong>-' . $arCoupon['STATUS_TEXT'] . '</span>
    <span class="close-link tq_delete_coupon"  data-coupon="' . $arCoupon['COUPON'] . '">Удалить</span>
</div>';
            }

        }
        return ['COUPON' => $html, 'BASKET' => $this->getBasketInfoAction()];
    }

    /**
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\NotImplementedException
     * @throws \Bitrix\Main\NotSupportedException
     * @throws \Bitrix\Main\ObjectNotFoundException
     * @throws \Bitrix\Main\SystemException
     */
    public function getBasketInfoAction()
    {
        $arResult['ITEMS'] = $this->getBasketItems();
        if (count($arResult['ITEMS']) > 0) {
            $this->setOrder();

            ob_start();
            include $_SERVER['DOCUMENT_ROOT'] . $this->__path . '/templates/.default/basket-items.php';
            $basket = ob_get_contents();
            return [
                'IS_EMPTY' => 'N',
                'BASKET' => $basket,
                'RESULT_PRICE' => CurrencyFormat($this->arResult['INFO_ORDER']['SUM'],
                    $this->arResult['INFO_ORDER']['CURRENCY'])
            ];
        } else {
            ob_start();
            include $_SERVER['DOCUMENT_ROOT'] . $this->__path . '/templates/.default/empty.php';
            $basket = ob_get_contents();
            return [
                'IS_EMPTY' => 'Y',
                'BASKET' => $basket,
                'RESULT_PRICE' => CurrencyFormat($this->arResult['INFO_ORDER']['SUM'],
                    $this->arResult['INFO_ORDER']['CURRENCY'])
            ];
        }


    }


    /**
     * @param $productID
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\NotImplementedException
     * @throws \Bitrix\Main\NotSupportedException
     * @throws \Bitrix\Main\ObjectNotFoundException
     * @throws \Bitrix\Main\SystemException
     */
    public function RemoveItemAction($productID)
    {
        \DDS\Basketclass::delete($productID);
        return $this->getBasketInfoAction();
    }

    /**
     * @param $productID
     * @param $wishId
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\NotImplementedException
     * @throws \Bitrix\Main\NotSupportedException
     * @throws \Bitrix\Main\ObjectNotFoundException
     * @throws \Bitrix\Main\SystemException
     */
    public function WishItemAction($productID, $wishId)
    {
        global $USER;
        $_SESSION['FAVORITES'][$wishId] = $wishId;
        if ($USER->IsAuthorized()) {
            $rsUser = CUser::GetByID($USER->GetId());
            $arUser = $rsUser->Fetch();
            $arWish = json_decode($arUser['UF_WISHLIST'], true);
            $arWish[$wishId] = $wishId;
            $user = new CUser;
            $fields = ['UF_WISHLIST' => json_encode($arWish)];
            $user->Update($USER->GetID(), $fields);
        }
        \DDS\Basketclass::delete($productID);
        return $this->getBasketInfoAction();
    }

    /**
     * @param $location
     * @return string
     */
    public function GetSdekAction($location)
    {
        $location_item = CSaleLocation::GetByID($location, "ru");
        ob_start();
        global $APPLICATION;
        $APPLICATION->IncludeComponent(
            "ipol:ipol.sdekPickup",
            "tq_order",
            Array(
                "CITIES" => array(
                    0 => $location_item['CITY_NAME_LANG'],
                ),
                "CNT_BASKET" => "N",
                "CNT_DELIV" => "N",
                "COUNTRIES" => array(),
                "FORBIDDEN" => array(),
                "NOMAPS" => "N",
                "PAYER" => "",
                "PAYSYSTEM" => ""
            )
        );
        $arBufer = ob_get_clean();
        return mb_convert_encoding($arBufer, 'UTF-8', 'UTF-8');
    }

    /**
     * @param $coupon
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\NotImplementedException
     * @throws \Bitrix\Main\NotSupportedException
     * @throws \Bitrix\Main\ObjectNotFoundException
     * @throws \Bitrix\Main\SystemException
     */
    public function DeleteCouponAction($coupon)
    {
        $html = '';
        \Bitrix\Sale\DiscountCouponsManager::delete($coupon);
        $arCoupons = $this->getCouponInfoCustom();
        if ($arCoupons) {
            foreach ($arCoupons['COUPON_LIST'] as $arCoupon) {
                if ($arCoupon['JS_STATUS'] == 'BAD' || 'ENTERED') {
                    $class = 'text-danger';
                } else {
                    $class = 'text-muted';
                }

                $html .= '<div class="basket-coupon-alert ' . $class . '">
						<span class="basket-coupon-text">
							<strong>' . $arCoupon['COUPON'] . '</strong>-' . $arCoupon['STATUS_TEXT'] . '</span>
    <span class="close-link tq_delete_coupon"  data-coupon="' . $arCoupon['COUPON'] . '">Удалить</span>
</div>';
            }

        }
        return ['COUPON' => $html, 'BASKET' => $this->getBasketInfoAction()];
    }

    /**
     * @param $data
     * @return array|int
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\NotSupportedException
     * @throws \Bitrix\Main\ObjectNotFoundException
     * @throws \Bitrix\Main\SystemException
     */
    public function CreateOrderAction($data)
    {
        $siteId = SITE_ID;
        global $USER;
        Bitrix\Main\Loader::includeModule("sale");
        Bitrix\Main\Loader::includeModule("catalog");
        foreach ($data as $input) {
            $props[$input['name']] = $input['value'];
        }

        $personType = !empty($props['payer']) ? $props['payer'] : 1;
        $db_props = CSaleOrderProps::GetList(array("SORT" => "ASC"),
            array("PERSON_TYPE_ID" => $personType, 'ACTIVE' => 'Y'), false,
            false, array());
        while ($prop = $db_props->GetNext()) {
            $properties[$prop['CODE']] = $prop;
        }

        $deliveryID = intval($props['delivery']);
        //$deliveryID = $this->delivery_id;
        $paymentID = intval($props['payment']);
        $arResult['ERROR'] = [];
        foreach ($properties as $key => $property) {
            if ($property['REQUIED'] == 'Y' && empty($props[$property['CODE']])) {
                $arResult['ERROR'][] = 'Заполните поле "' . $property['NAME'] . '" <br>';
            }
        }
        if (empty($deliveryID)) {
            $arResult['ERROR'][] = "Выберите Доставку <br>";
        }
        if (empty($paymentID)) {
            $arResult['ERROR'][] = "Выберите систему оплаты <br>";
        }

        if (!empty($arResult['ERROR'])) {
            return array("STATUS" => "ERROR", "HTML" => $arResult['ERROR'], 'STEP' => $arResult['ERROR_PAGE']);
        }
        if (!$USER->isAuthorized()) {
            global $USER;
            $filter = Array("EMAIL" => $props['EMAIL']);
            $by = "NAME";
            $order = "desc";
            $rsUsers = CUser::GetList($by, $order, $filter);
            $arUser = $rsUsers->Fetch();
            if (!$arUser) {
                $pass = randString(7);
                $validData = [
                    'NAME' => $props['NAME'],
                    'LAST_NAME' => $props['SECOND_NAME'],
                    'PERSONAL_PHONE' => $props['PHONE'],
                    'EMAIL' => $props['EMAIL'],
                    'LOGIN' => $props['EMAIL'],
                    'PASSWORD' => $pass,
                    'CONFIRM_PASSWORD' => $pass,
                    'PERSONAL_ZIP' => $props['INDEX'],
                    'PERSONAL_CITY' => $props['LOCATION'],
                    'PERSONAL_STREET' => $props['STREET'],
                    'UF_HOUSE' => $props['HOUSE'],
                    'UF_APPARTAMENTS' => $props['APPARTAMENTS'],
                ];
                global $USER;
                $user = new \CUser;
                $def_group = \COption::GetOptionString("main", "new_user_registration_def_group", "");
                if ($def_group != "") {
                    $validData["GROUP_ID"] = explode(",", $def_group);
                }
                $ID = $user->Add($validData);
                if (intval($ID) > 0) {
                    $USER->Authorize($ID);
                    $arEventFields = [
                        'USER_ID' => $ID,
                        'LOGIN' => $validData['LOGIN'],
                        'PASSWORD' => $validData['PASSWORD'],
                        'EMAIL' => $validData['EMAIL'],
                        'NAME' => $validData['NAME'],
                        'PHONE' => $validData['PERSONAL_PHONE']
                    ];
                    CEvent::Send("AUTO_REGISTRATION", SITE_ID, $arEventFields);
                    $result = ['STATUS' => 'SUCCESS', 'MESSAGE' => 'Регистрация завершена', 'ID' => intval($ID)];
                } else {
                    $result = ['STATUS' => 'ERROR', 'MESSAGE' => $user->LAST_ERROR];
                }

                if ($result['STATUS'] != 'ERROR') {
                    $arUser['ID'] = $result['ID'];
                } else {
                    $arUser['ID'] = \CSaleUser::GetAnonymousUserID();
                }
            }
        }

        $order = Order::create($siteId, $USER->isAuthorized() ? $USER->GetID() : $arUser['ID']);
        $order->setPersonTypeId(!empty($personType) ? $personType : 1);

        $basket = Bitrix\Sale\Basket::loadItemsForFUser(Bitrix\Sale\Fuser::getId(),
            Bitrix\Main\Context::getCurrent()->getSite());
        $order->setBasket($basket);

        $shipmentCollection = $order->getShipmentCollection();
        $shipment = $shipmentCollection->createItem();
        if (!empty($deliveryID)) {
            $service = Delivery\Services\Manager::getById($deliveryID);
        } else {
            $service = Delivery\Services\Manager::getById(Delivery\Services\EmptyDeliveryService::getEmptyDeliveryServiceId());
        }
        $shipment->setFields(array(
            'DELIVERY_ID' => $service['ID'],
            'DELIVERY_NAME' => $service['NAME'],
        ));
        if ($props['store']) {
            $deliveryStoreList = Delivery\ExtraServices\Manager::getStoresList($deliveryID);
            if (!empty($deliveryStoreList)) {
                if ($props['store'] <= 0 || !in_array($props['store'], $deliveryStoreList)) {
                    $props['store'] = current($deliveryStoreList);
                }

                $shipment->setStoreId($props['store']);
            }
        }

        $paymentCollection = $order->getPaymentCollection();
        $payment = $paymentCollection->createItem();
        $paySystemService = PaySystem\Manager::getObjectById($paymentID);
        $deliveryPrice = 0;
        if (!empty($deliveryID)) {
            $deliveryPrice = $this->GetDeliveryPriceAction($props['LOCATION'], $deliveryID, $paymentID)['delivery'];
            //$arDeliv = CSaleDelivery::GetByID($deliveryID);
            // $deliveryPrice = $arDeliv['PRICE'];
        }
        $payment->setFields(array(
            'PAY_SYSTEM_ID' => $paySystemService->getField("PAY_SYSTEM_ID"),
            'PAY_SYSTEM_NAME' => $paySystemService->getField("NAME"),
        ));
        $payment->setField("SUM", $basket->getPrice() + $deliveryPrice);

        $shipment->setField("BASE_PRICE_DELIVERY", $deliveryPrice);
        $shipment->setField("CUSTOM_PRICE_DELIVERY", "Y");


        if ($props['comment'] || $props['comment_mob']) {
            $order->setField('USER_DESCRIPTION',
                $props['comment'] ?: $props['comment_mob']); // Устанавливаем поля комментария покупателя
        }
        $phone = str_replace(' ', '', str_replace('-', '', $props['PHONE']));
        $propertyCollection = $order->getPropertyCollection();
        $propertyCollection->getPhone()->setValue($phone);
        $propertyCollection->getPayerName()->setValue($props['NAME']);
        $propertyCollection->getUserEmail()->setValue($props['EMAIL']);

        foreach ($properties as $key => $property) {

            if ($props[$key]) {
                if ($key == 'PHONE') {
                    $props[$key] = $phone;
                }
                $propertyCollection->getItemByOrderPropertyId($property['ID'])->setValue($props[$key]);
            }
        }

        if ($props['LOCATION']) {
            $propertyCollection->getDeliveryLocation()->setValue(CSaleLocation::getLocationCODEbyID($props['LOCATION']));
        }
        $order->doFinalAction(true);

        $result = $order->save();
        $orderId = $order->getId();
        if ($orderId > 0) {
            $_SESSION['SALE_ORDER_ID'][] = $orderId;
            \Bitrix\Sale\DiscountCouponsManager::clear(true);
            return $orderId;
        } else {
            $arResult['ERROR'][] = "Вонзникла неизвестная ошибка, попробуйте оформить заказ снова <br>";
            return array("STATUS" => "ERROR", "HTML" => $arResult['ERROR']);

        }
    }

    /**
     * @param $orderID
     * @return array
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\NotImplementedException
     */
    private function getOrderInfo($orderID)
    {
        global $USER;
        $result = [];

        $order = Sale\Order::load($orderID);

        if (empty($order)) {
            return ['STATUS' => 'ERROR', 'MESSAGE' => 'Номер заказа не найден в системе'];
        }
        if (!in_array($orderID, $_SESSION['SALE_ORDER_ID']) && $order->getUserId() != $USER->GetID()) {
            return [
                'STATUS' => 'ERROR',
                'MESSAGE' => 'Этот заказ вам не пренадлежит, авторизуйтесь под пользователем оформившим заказ!'
            ];
        }

        $result['ORDER_ID'] = $orderID;
        $result['ORDER_SUM'] = $order->getPrice();
        $result['ORDER_DISCOUNT_PRICE'] = $order->getDiscountPrice();
        $result['DELIVERY_PRICE'] = $order->getDeliveryPrice();
        $result['CURRENCY'] = $order->getCurrency();
        $basket = $order->getBasket();
        $result['BASKET_SUM'] = $basket->getPrice();
        $result['BASKET_FULL_PRICE'] = $basket->getBasePrice();
        $basketItems = $basket->getBasketItems();
        foreach ($basketItems as $basketItem) {
            $arProducts[] = $basketItem->getProductId();

            $mxResult = CCatalogSku::GetProductInfo(
                $basketItem->getProductId()
            );
            if (is_array($mxResult)) {
                $mainID = $mxResult['ID'];
                $arProducts[] = $mainID;
            } else {
                $mainID = '';
            }
            $result['ITEMS'][] = [
                'ID' => $basketItem->getId(),
                'PRODUCT_ID' => $basketItem->getProductId(),
                'PRICE' => $basketItem->getPrice(),
                'BASE_PRICE' => $basketItem->getBasePrice(),
                'DISCOUNT_PRICE' => $basketItem->getDiscountPrice(),
                'QUANTITY' => $basketItem->getQuantity(),
                'SUM' => $basketItem->getFinalPrice(),
                'NAME' => $basketItem->getField('NAME'),
                'MAIN_PRODUCT_ID' => $mainID,
            ];

        }
        if (!empty($arProducts)) {
            $select = Array('ID', 'IBLOCK_ID', 'NAME', 'PREVIEW_PICTURE', 'PROPERTY_PREVIEW_PICTURE_2X');
            $filter = Array('ID' => $arProducts);
            $res = CIBlockElement::GetList(Array(), $filter, false, false, $select);
            while ($ob = $res->Fetch()) {
                $arProductsInfo[$ob['ID']] = $ob;

            }
        }

        foreach ($result['ITEMS'] as &$arItem) {
            if (!empty($arProductsInfo[$arItem['PRODUCT_ID']])) {
                $arItem['PRODUCT_INFO'] = $arProductsInfo[$arItem['PRODUCT_ID']];
                if (!empty($arItem['MAIN_PRODUCT_ID'])) {
                    $arItem['MAIN_PRODUCT_INFO'] = $arProductsInfo[$arItem['MAIN_PRODUCT_ID']];
                }
            }
        }
        return $result;
    }

    /**
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\NotImplementedException
     * @throws \Bitrix\Main\SystemException
     */
    private function getPage()
    {
        $this->getDeliveries();
        $this->arResult['ITEMS'] = $this->getBasketItems();
        $request = Application::getInstance()->getContext()->getRequest();
        if ($request->get('ORDER_ID')) {
            $this->arResult['ORDER'] = $this->getOrderInfo($request->get('ORDER_ID'));
            $this->componentPage = 'confirm';
        } else {
            if (empty($this->arResult['ITEMS'])) {
                $this->componentPage = 'empty';
            }
        }
        global $APPLICATION;

        $this->arResult['TITLE'] = $APPLICATION->GetTitle();
        $this->arResult['STORE_ID'] = $_COOKIE['STORE'];

    }

    /**
     * @throws \Bitrix\Main\ArgumentException
     */
    private function getCities()
    {
        $res = \Bitrix\Sale\Location\LocationTable::getList(array(
            'filter' => array('=NAME.LANGUAGE_ID' => LANGUAGE_ID, 'TYPE_CODE' => 'CITY'),
            'select' => array('*', 'NAME_RU' => 'NAME.NAME', 'TYPE_CODE' => 'TYPE.CODE')
        ));
        while ($item = $res->fetch()) {
            $this->arResult['CITIES'][] = $item;
        }
    }

    /**
     * @throws \Bitrix\Main\SystemException
     */
    private function getDelProps()
    {

        if ($this->arResult['DELIVERIES']) {

            foreach ($this->arResult['DELIVERIES'] as $arItem) {
                $shipmentCollection = $this->order->getShipmentCollection();
                $shipmentCollection->clearCollection();
                $shipment = $shipmentCollection->createItem();
                $service = Delivery\Services\Manager::getById($arItem['ID']);

                $shipment->setFields(array(
                    'DELIVERY_ID' => $service['ID'],
                    'DELIVERY_NAME' => $service['NAME'],
                ));
                $propertyCollection = $this->order->getPropertyCollection();
                $arProps = $propertyCollection->getArray();
                foreach ($arProps['properties'] as $arProp) {
                    if ($arProp['PROPS_GROUP_ID'] == 2 && $arProp['UTIL'] != 'Y') {
                        if ($this->arUserProps[$arProp['CODE']]) {
                            $arProp['VALUE'] = $this->arResult['USER_INFO'][$this->arUserProps[$arProp['CODE']]];
                        }
                        $this->arResult['DELIVERIES_PROPS'][$arItem['ID']][] = $arProp;
                    }

                }
            }

        }

        if (!empty($this->arResult['DELIVERIES_PROPS'])) {
            foreach ($this->arResult['DELIVERIES_PROPS'] as &$arDelProps) {
                $arMultilineProps = [];
                foreach ($arDelProps as $key => $arDelProp) {
                    if ($arDelProp['MULTILINE'] == 'Y') {
                        $arMultilineProps[] = $arDelProp;
                        unset($arDelProps[$key]);
                    }
                }
                $arDelProps = [
                    'FIRST' => array_shift($arDelProps),
                    'MULTILINES' => $arMultilineProps,
                    'OTHERS' => array_chunk($arDelProps, 3)
                ];
            }
        }
    }

    /**
     * @return mixed|void
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\NotImplementedException
     * @throws \Bitrix\Main\NotSupportedException
     * @throws \Bitrix\Main\ObjectNotFoundException
     * @throws \Bitrix\Main\SystemException
     */
    public function executeComponent()
    {

        $this->arResult['COUPON_LIST'] = $this->getCouponInfoCustom();
        $this->getCities();
        $this->setOrder();
        $this->getPage();

        $this->getDelProps();
        $this->includeComponentTemplate($this->componentPage);

    }

}
