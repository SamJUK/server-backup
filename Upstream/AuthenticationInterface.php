<?php

namespace Upstream;

interface AuthenticationInterface
{
    /**
     * Check if the access token we have is still valid
     */
    public function isAccessTokenValid() : bool;

    /**
     * Get our current access token
     */
    public function getAccessToken() : string;

    /**
     * Fetch a new access token
     */
    public function fetchNewAccessToken() : string;



    public function getConfig() : \stdClass;
}