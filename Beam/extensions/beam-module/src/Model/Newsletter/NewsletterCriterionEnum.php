<?php

namespace Remp\BeamModule\Model\Newsletter;

enum NewsletterCriterionEnum: string
{
    case AveragePayment = 'average_payment';
    case TimespentAll = 'timespent_all';
    case PageViewsSignedIn = 'pageviews_signed_in';
    case PageViewsSubscribers = 'pageviews_subscribers';
    case TimespentSubscribers = 'timespent_subscribers';
    case Conversions = 'conversions';
    case TimespentSignedIn = 'timespent_signed_in';
    case PageViewsAll = 'pageviews_all';
    case Bookmarks = 'bookmarks';

    public static function getFriendlyList(): array
    {
        return [
            self::Bookmarks->value => 'Bookmarks',
            self::PageViewsAll->value => 'Pageviews all',
            self::PageViewsSignedIn->value => 'Pageviews signed in',
            self::PageViewsSubscribers->value => 'Pageviews subscribers',
            self::TimespentAll->value => 'Time spent all',
            self::TimespentSignedIn->value => 'Time spent signed in',
            self::TimespentSubscribers->value => 'Time spent subscribers',
            self::Conversions->value => 'Conversions',
            self::AveragePayment->value => 'Average payment'
        ];
    }
}
