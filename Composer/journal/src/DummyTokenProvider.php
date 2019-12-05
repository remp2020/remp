<?php

namespace Remp\Journal;

class DummyTokenProvider implements TokenProvider
{
    public function getToken(): ?string
    {
        // Always returns empty token
        return null;
    }
}
