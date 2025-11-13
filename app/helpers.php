<?php

use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

if (!function_exists('getUserImageInitial')) {
    function getUserImageInitial($userId, $name)
    {
        $colors = ['329af0', 'fc6369', 'ffaa2e', '42c9af', '7d68f0'];

        $color = $colors[$userId % count($colors)];

        return 'https://ui-avatars.com/api/?name=' . urlencode($name)
            . '&size=64&rounded=true&color=fff&background=' . $color;
    }
}

if (! function_exists('getPhoneNumberFormate')) {
    function getPhoneNumberFormate($phoneNumber, $regionCode = null)
    {
        if (empty($phoneNumber)) {
            return 'N/A';
        }

        try {
            $region = $regionCode ?? 'IN';
            $phoneUtil = PhoneNumberUtil::getInstance();
            $parsedNumber = $phoneUtil->parse($phoneNumber, $region);
            return $phoneUtil->format($parsedNumber, PhoneNumberFormat::INTERNATIONAL);
        } catch (\Exception $e) {
            return $phoneNumber;
        }
    }
}
