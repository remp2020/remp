<?php

namespace Remp\Journal;

interface TokenProvider
{
    public function getToken(): ?string;
}
