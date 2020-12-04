<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Tracker;

class EventOptions
{
    private $user;

    private $fields = [];

    private $value;

    public function getUser(): User
    {
        return $this->user;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function __construct()
    {
        $this->user = new User;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function setFields(array $fields): void
    {
        $this->fields = $fields;
    }

    public function setValue(float $value): void
    {
        $this->value = $value;
    }
}
