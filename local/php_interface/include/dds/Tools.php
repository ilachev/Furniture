<?php

namespace DDS;

use Bitrix\Main\Loader;
use Bitrix\Main\Security\Password;

class Tools
{
    private static function favoriteKey($request): string
    {
        $key = (string)$request->getPost('add');
        return $key === 'COMPARE' ? 'COMPARE' : 'FAVORITES';
    }

    public static function compfav($request)
    {
        $id = (int)$request->getPost('id');
        if ($id > 0) {
            $key = self::favoriteKey($request);
            $_SESSION[$key][$id] = $id;
            if ($key === 'FAVORITES') {
                $_SESSION['FAVORITE'][$id] = $id;
            }
        }

        return 'success';
    }

    public static function compfavdelete($request)
    {
        $id = (int)$request->getPost('id');
        $key = self::favoriteKey($request);
        unset($_SESSION[$key][$id], $_SESSION['FAVORITE'][$id]);

        return 'success';
    }

    public static function userRegister($data)
    {
        $user = new \CUser;
        $id = $user->Add([
            'NAME' => $data['NAME'],
            'LAST_NAME' => $data['LAST_NAME'],
            'EMAIL' => $data['EMAIL'],
            'LOGIN' => $data['LOGIN'] ?: $data['EMAIL'],
            'PERSONAL_PHONE' => $data['PERSONAL_PHONE'],
            'PASSWORD' => $data['PASSWORD'],
            'CONFIRM_PASSWORD' => $data['CONFIRM_PASSWORD'],
            'ACTIVE' => 'Y',
        ]);

        if ((int)$id > 0) {
            return ['status' => true, 'result' => (int)$id];
        }

        return ['status' => false, 'msg' => strip_tags($user->LAST_ERROR)];
    }

    public static function userUpdate($id, $data)
    {
        $user = new \CUser;
        $result = $user->Update((int)$id, [
            'NAME' => $data['NAME'],
            'LAST_NAME' => $data['LAST_NAME'],
            'PERSONAL_PHONE' => $data['PERSONAL_PHONE'],
            'EMAIL' => $data['EMAIL'],
            'UF_ADDRES' => $data['UF_ADDRES'],
        ]);

        if ((int)$result > 0) {
            return ['status' => true, 'msg' => 'Информация обновлена'];
        }

        return ['status' => false, 'msg' => strip_tags($user->LAST_ERROR)];
    }

    public static function savePasswordCheckOld($oldPassword, $newPassword, $confirmPassword, $id)
    {
        $userData = \CUser::GetByID((int)$id)->Fetch();
        if (!$userData || !Password::equals($userData['PASSWORD'], $oldPassword)) {
            return ['status' => false, 'msg' => 'Старый пароль введён неверно!'];
        }
        if ($newPassword !== $confirmPassword) {
            return ['status' => false, 'msg' => 'Пароли не совпадают'];
        }

        $user = new \CUser;
        $result = $user->Update((int)$id, [
            'PASSWORD' => $newPassword,
            'CONFIRM_PASSWORD' => $confirmPassword,
        ]);

        if ((int)$result > 0) {
            return ['status' => true, 'msg' => 'Пароль изменён'];
        }

        return ['status' => false, 'msg' => strip_tags($user->LAST_ERROR)];
    }

    public static function oneclick($id)
    {
        return Basketclass::add2basket($id, 1);
    }

    public static function declOfNum($number, $titles)
    {
        $cases = [2, 0, 1, 1, 1, 2];
        return $number . ' ' . $titles[($number % 100 > 4 && $number % 100 < 20) ? 2 : $cases[min($number % 10, 5)]];
    }
}
