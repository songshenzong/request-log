<?php

namespace Songshenzong\Log\DataCollector;

use Songshenzong\Log\DataCollector\DataCollector;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Collector for Laravel's Auth provider
 */
class AuthCollector extends DataCollector
{
    /** @var \Illuminate\Auth\AuthManager */
    protected $auth;
    /** @var bool */
    protected $showName = false;

    /**
     * @param \Illuminate\Auth\AuthManager $auth
     */
    public function __construct($auth)
    {
        $this->auth = $auth;
    }

    /**
     * Set to show the users name/email
     *
     * @param bool $showName
     */
    public function setShowName($showName)
    {
        $this->showName = (bool) $showName;
    }

    /**
     * @{inheritDoc}
     */
    public function collect()
    {
        try {
            $user = $this->auth->user();
        } catch (\Exception $e) {
            $user = null;
        }
        return $this->getUserInformation($user);
    }

    /**
     * Get displayed user information
     *
     * @param \Illuminate\Auth\UserInterface $user
     *
     * @return array
     */
    protected function getUserInformation($user = null)
    {
        // Defaults
        if (null === $user) {
            return [
                'name' => 'Guest',
                'user' => ['guest' => true],
            ];
        }

        // The default auth identifer is the ID number, which isn't all that
        // useful. Try username and email.
        $identifier = $user->getAuthIdentifier();
        if (is_numeric($identifier)) {
            try {
                if ($user->username) {
                    $identifier = $user->username;
                } else if ($user->email) {
                    $identifier = $user->email;
                }
            } catch (\Exception $e) {
            }
        }

        return [
            'name' => $identifier,
            'user' => $user instanceof Arrayable ? $user->toArray() : $user,
        ];
    }

    /**
     * @{inheritDoc}
     */
    public function getName()
    {
        return 'auth';
    }
}
