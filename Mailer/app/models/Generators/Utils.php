<?php

namespace Remp\MailerModule\Generators;

class Utils
{
    public static function removeRefUrlAttribute($url)
    {
        return preg_replace('/\\?ref=(.*)/', '', $url);
    }
}
