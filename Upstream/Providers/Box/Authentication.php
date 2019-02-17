<?php

namespace Upstream\Providers\Box;

use Upstream\AuthenticationInterface;

class Authentication implements AuthenticationInterface
{
    /** @inheritdoc */
    public function isAccessTokenValid() : bool
    {
        // TODO: Implement Method isAccessTokenValid()
    }

    /** @inheritdoc */
    public function getAccessToken() : string
    {
        // TODO: Implement Method getAccessToken()
    }

    /** @inheritdoc */
    public function fetchNewAccessToken() : void
    {
        // TODO: Implement Method refreshAccessToken()
    }
}