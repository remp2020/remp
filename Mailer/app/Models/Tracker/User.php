<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Tracker;

class User
{
    private $id;

    private $ip_address;

    private $url;

    private $user_agent;

    public function __construct(array $options = [])
    {
        foreach ($options as $key => $val) {
            $this->{$key} = (string)$val;
        }
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'ip_address' => $this->ip_address,
            'url' => $this->url,
            'user_agent' => $this->user_agent,
        ];
    }
}
