<?php
namespace Remp\MailerModule\Segment;


interface ISegment
{
    public function provider();

    public function list();

    public function users($segment);
}
