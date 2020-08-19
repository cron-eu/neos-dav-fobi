<?php

namespace CRON\DAV\Fobi\Eel\Helper;

use Neos\Eel\ProtectedContextAwareInterface;
use /** @noinspection PhpUnusedAliasInspection */ Neos\Flow\Annotations as Flow;
use Firebase\JWT\JWT;
use Neos\Flow\Configuration\Exception\InvalidConfigurationException;
use Neos\Flow\Log\Utility\LogEnvironment;
use Psr\Log\LoggerInterface as LoggerInterface;

class BCMFobiHelper implements ProtectedContextAwareInterface
{

    /**
     * @Flow\Inject
     * @var \CRON\ObisIntegration\Service\ObisService
     */
    protected $obis;

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @Flow\InjectConfiguration(package="CRON.DAV.Fobi")
     * @var array
     */
    protected $settings = [];

    /**
     * Eel helper: an jwt token identifying the user to be passed to the JS widget
     *
     * @return string A token identifying the user, or an empty string if there is no user
     *
     * @throws InvalidConfigurationException
     */
    public function getToken()
    {
        try {
            $userData = $this->obis->getUserData();
        } catch (\Exception $e) {
            $this->logger->warning(
                sprintf('BCMFobiHelper::getToken: Error while fetching OBIS userData: %s', $e->getMessage()),
                LogEnvironment::fromMethodName(__METHOD__)
            );
            return '';
        }

        return $this->createToken(
            $userData['email'],
            $userData['firstName'],
            $userData['lastName'],
            $this->getRoles($userData['roles'])
        );
    }

    /**
     * Creates a valid token for the BCM Fobi widgets
     *
     * @param string $email
     * @param string $firstName
     * @param string $lastName
     * @param array $roles A list of roles (as strings)
     *
     * @return string A token identifying the user, or an empty string if there is no user
     *
     * @throws InvalidConfigurationException
     */
    protected function createToken($email, $firstName, $lastName, $roles)
    {
        if (empty($email)) {
            return '';
        }

        try {
            $expiresAfter = new \DateInterval($this->settings['token']['expiresAfter']);
        } catch (\Exception $e) {
            throw new InvalidConfigurationException(
                sprintf('CRON.DazSite.BCMfobi.expiresAfter setting is invalid: %s', $this->settings['expiresAfter'])
            );
        }
        $expirationDate = (new \DateTime())->add($expiresAfter);
        $issuedDate = new \DateTime();

        $token = [
            "iss" => $this->settings['token']['issuer'],
            "aud" =>  $this->settings['token']['audience'],
            "exp" => $expirationDate->getTimestamp(),
            "iat" => $issuedDate->getTimestamp(),
            "firstname" => $firstName,
            "lastname" => $lastName,
            "email" => $email,
            "roles" => $roles
        ];

        try {
            $key = $this->settings['token']['secret'];
            $jwt = JWT::encode($token, $key);
        } catch (\Exception $e) {
            $this->logger->warning(
                sprintf('BCMFobiHelper::createToken: Error encoding token: %s', $e->getMessage()),
                LogEnvironment::fromMethodName(__METHOD__)
            );
            return '';
        }

        return $jwt;
    }

    /**
     * Map flow roles to strings to be used in BCMFobi
     *
     * Use the CRON.DazSite.BCMfobi.rolesMapping setting
     *
     * @param array $roles A list of flow roles
     * @return array
     */
    protected function getRoles($roles)
    {
        if (!$roles) {
            return [];
        }
        $rolesMapped = array_map(function ($role) {
            if (isset($this->settings['token']['rolesMapping'][$role])) {
                $role = $this->settings['token']['rolesMapping'][$role];
            }
            return $role;
        }, $roles);

        return $rolesMapped;
    }

    /**
     * Allow calling all public methods as Eel functions (only getToken)
     *
     * @param string $methodName
     *
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
