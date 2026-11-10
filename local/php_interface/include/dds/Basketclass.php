<?php

namespace DDS;

use Bitrix\Main\Loader;
use Bitrix\Sale\Basket;
use Bitrix\Sale\DiscountCouponsManager;
use Bitrix\Sale\Fuser;
use Bitrix\Main\Context;

class Basketclass
{
    private static function basket()
    {
        Loader::includeModule('sale');
        Loader::includeModule('catalog');

        return Basket::loadItemsForFUser(
            Fuser::getId(),
            Context::getCurrent()->getSite()
        );
    }

    private static function findItem($basket, $id)
    {
        $id = (int)$id;
        foreach ($basket as $item) {
            if ((int)$item->getId() === $id || (int)$item->getProductId() === $id) {
                return $item;
            }
        }
        return null;
    }

    public static function add2basket($id, $quantity = 1)
    {
        Loader::includeModule('sale');
        Loader::includeModule('catalog');

        $id = (int)$id;
        $quantity = max(1, (int)$quantity);
        if ($id <= 0) {
            return ['message' => 'Товар не найден'];
        }

        $basket = self::basket();
        $item = $basket->getExistsItem('catalog', $id);
        if (!$item) {
            $item = $basket->createItem('catalog', $id);
        }
        $item->setFields([
            'QUANTITY' => $quantity,
            'CURRENCY' => \Bitrix\Currency\CurrencyManager::getBaseCurrency(),
            'LID' => Context::getCurrent()->getSite(),
            'PRODUCT_PROVIDER_CLASS' => 'CCatalogProductProvider',
        ]);
        $basket->save();

        return ['message' => 'Товар добавлен в корзину!'];
    }

    public static function updatebasket($id, $quantity = 1)
    {
        $quantity = max(1, (int)$quantity);
        $basket = self::basket();
        $item = self::findItem($basket, $id);
        if (!$item) {
            return false;
        }

        $product = \CCatalogProduct::GetByID($item->getProductId());
        if ($product && (float)$product['QUANTITY'] > 0) {
            $quantity = min($quantity, (int)$product['QUANTITY']);
        }
        $item->setField('QUANTITY', $quantity);
        $basket->save();

        return $quantity;
    }

    public static function delete($id)
    {
        $basket = self::basket();
        $item = self::findItem($basket, $id);
        if ($item) {
            $result = $item->delete();
            $basket->save();
            return $result;
        }

        $id = (int)$id;
        return $id > 0 ? \CSaleBasket::Delete($id) : false;
    }

    public static function clearBasket()
    {
        Loader::includeModule('sale');
        return \CSaleBasket::DeleteAll(\CSaleBasket::GetBasketUserID());
    }

    public static function setCoupon($coupon)
    {
        Loader::includeModule('sale');
        DiscountCouponsManager::clear(true);
        DiscountCouponsManager::add($coupon);

        return true;
    }
}
