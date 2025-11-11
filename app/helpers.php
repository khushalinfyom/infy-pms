<?php

if (!function_exists('getUserImageInitial')) {
    function getUserImageInitial($userId, $name)
    {
        $colors = ['329af0', 'fc6369', 'ffaa2e', '42c9af', '7d68f0'];

        $color = $colors[$userId % count($colors)];

        return 'https://ui-avatars.com/api/?name=' . urlencode($name)
            . '&size=64&rounded=true&color=fff&background=' . $color;
    }
}
