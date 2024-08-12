<?php

namespace AliKhaleghi\BaseSys\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Myth\Auth\Exceptions\PermissionException;

class JWTPermissionFilter extends JWTBaseFilter implements FilterInterface
{
    /**
     * @param array|null $arguments
     *
     * @return RedirectResponse|void
     */
    public function before(RequestInterface $request, $arguments = null)
    { 
        try {
            // If no user is logged in then send them some errors
            if (! $this->authenticate->check()) {
                return $this->failed(401);
            }
        }
        catch(\DomainException $e) {
            return $this->failed(406);
        }

        if (empty($arguments)) {
            return;
        }

        $result = true;
        // Check each requested permission
        foreach ($arguments as $permission) {
            $result = ($result && $this->authorize->hasPermission($permission, $this->authenticate->id()));
        }
        if (! $result) {
            
            // user is not allowed this route
            return $this->failed(401);
        }
    }

    /**
     * Allows After filters to inspect and modify the response
     * object as needed. This method does not allow any way
     * to stop execution of other after filters, short of
     * throwing an Exception or Error.
     *
     * @param array|null $arguments
     *
     * @return void
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
