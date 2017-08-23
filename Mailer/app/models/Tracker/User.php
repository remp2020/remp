<?php

namespace Remp\MailerModule\Tracker;

class User
{
    private $id;

    private $ip_address;

    private $url;

    private $user_agent;

    public function __construct($options = [])
    {
        foreach ($options as $key => $val) {
            $this->{$key} = strval($val);
        }
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'ip_address' => $this->ip_address,
            'url' => $this->url,
            'user_agent' => $this->user_agent,
        ];
    }
}
