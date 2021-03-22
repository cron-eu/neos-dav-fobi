<?php

namespace CRON\DAV\Fobi\Eel\Helper;

use CRON\ObisIntegration\Service\ObisService;
use DateInterval;
use DateTime;
use Exception;
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
     * @var ObisService
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
    public function getToken(): string
    {
        try {
            $userData = $this->obis->getUserData();
        } catch (Exception $e) {
            $this->logger->warning(
                sprintf('BCMFobiHelper::getToken: Error while fetching OBIS userData: %s', $e->getMessage()),
                LogEnvironment::fromMethodName(__METHOD__)
            );
            return '';
        }

        if (!$userData) {
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
    protected function createToken(string $email, string $firstName, string $lastName, array $roles): string
    {
        if (empty($email)) {
            return '';
        }

        try {
            $expiresAfter = new DateInterval($this->settings['token']['expiresAfter']);
        } catch (Exception $e) {
            throw new InvalidConfigurationException(
                sprintf('CRON.DAV.Fobi.token.expiresAfter setting is invalid: %s', $this->settings['expiresAfter'])
            );
        }
        $expirationDate = (new DateTime())->add($expiresAfter);
        $issuedDate = new DateTime();

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
        } catch (Exception $e) {
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
     * Use the CRON.DAV.Fobi.rolesMapping setting
     *
     * @param array $roles A list of flow roles
     * @return array
     */
    protected function getRoles($roles): array
    {
        if (!$roles) {
            return [];
        }
        return array_map(function ($role) {
            if (isset($this->settings['rolesMapping'][$role])) {
                $role = $this->settings['rolesMapping'][$role];
            }
            return $role;
        }, $roles);
    }

    /**
     * Allow calling all public methods as Eel functions (only getToken)
     *
     * @param string $methodName
     *
     * @return boolean
     */
    public function allowsCallOfMethod($methodName): bool
    {
        return true;
    }
}
