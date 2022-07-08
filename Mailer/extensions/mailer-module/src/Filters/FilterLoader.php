<?php
declare(strict_types=1);

namespace Remp\MailerModule\Filters;

class FilterLoader
{
    /** All registered filters */
    private array $filters = [];

    /**
     * Check if filter is registered, call filter if is registered
     *
     * @param string $helper
     * @return ?callable
     */
    public function load(string $helper): ?callable
    {
        return $this->filters[$helper] ?? null;
    }

    /**
     * Registers new filter
     *
     * @param string $name
     * @param callable $callback
     */
    public function register(string $name, callable $callback): void
    {
        $this->filters[$name] = $callback;
    }
}
