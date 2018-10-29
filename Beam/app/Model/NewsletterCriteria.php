<?php
namespace App\Model;

class NewsletterCriteria
{
    const AVERAGE_PAYMENT = 'average_payment';
    const TIMESPENT_ALL = 'timespent_all';
    const PAGEVIEWS_SIGNED_IN = 'pageviews_signed_in';
    const PAGEVIEWS_SUBSCRIBERS = 'pageviews_subscribers';
    const TIMESPENT_SUBSCRIBERS = 'timespent_subscribers';
    const CONVERSIONS = 'conversions';
    const TIMESPENT_SIGNED_IN = 'timespent_signed_in';
    const PAGEVIEWS_ALL = 'pageviews_all';

    public static function allCriteriaConcatenated($glue = ',')
    {
        return implode($glue, [
            self::PAGEVIEWS_ALL,
            self::PAGEVIEWS_SIGNED_IN,
            self::PAGEVIEWS_SUBSCRIBERS,
            self::TIMESPENT_ALL,
            self::TIMESPENT_SIGNED_IN,
            self::TIMESPENT_SUBSCRIBERS,
            self::CONVERSIONS,
            self::AVERAGE_PAYMENT,
        ]);
    }
}
