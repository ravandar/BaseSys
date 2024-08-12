<?php
namespace AliKhaleghi\BaseSys\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use AliKhaleghi\BaseSys\Config\Api as ApiConfig;
class JWTLoggedInFilter Extends JWTBaseFilter implements FilterInterface
{

    /**
     * Verifies that a user is logged in
     *
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
    }

    /**
     * @param array|null $arguments
     *
     * @return void
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        
    } 
}