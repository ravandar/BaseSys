<?php namespace AliKhaleghi\BaseSys\Authentication;
 
class JWTManager
{
    private array $sessions;

    // -------------------------------------------------------------------

    /**
     * Fill up the Manager with the Data required.
     * 
     * @param array $sessions   Array of Sessions to be validated.
     */
    public function __construct(array $sessions) {
        $this->sessions = $sessions;
    }

    // -------------------------------------------------------------------

    public function add($session) {
		// Add New Session
		$this->sessions[] = $session;
        return $this;
    }

    // -------------------------------------------------------------------
    
    public function getSessions() {
        return $this->sessions;
    }
}
