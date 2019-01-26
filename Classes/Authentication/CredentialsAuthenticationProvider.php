<?php
declare(strict_types=1);

namespace Cundd\Rest\Authentication;

use Cundd\Rest\Handler\AuthHandler;
use Cundd\Rest\Http\RestRequestInterface;

/**
 * Authentication Provider for requests authenticated through the login route (/auth/login)
 */
class CredentialsAuthenticationProvider extends AbstractAuthenticationProvider
{
    /**
     * @var \Cundd\Rest\SessionManager
     * @inject
     */
    protected $sessionManager;

    /**
     * Tries to authenticate the current request
     *
     * @param RestRequestInterface $request
     * @return bool Returns if the authentication was successful
     */
    public function authenticate(RestRequestInterface $request): bool
    {
        return $this->sessionManager->valueForKey('loginStatus') === AuthHandler::STATUS_LOGGED_IN;
    }
}
