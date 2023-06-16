<?php

namespace CRON\DAV\Fobi\Eel\Helper;

use CRON\ObisIntegration\ObisException;
use DateInterval;
use DateTime;
use Exception;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Firebase\JWT\JWT;
use Neos\Flow\Configuration\Exception\InvalidConfigurationException;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Security\Context;
use Neos\Flow\Session\Exception\SessionNotStartedException;
use Psr\Log\LoggerInterface;
use CRON\ObisIntegration\Service\DavUserService;

/** @noinspection PhpUnused */
class FobiHelper implements ProtectedContextAwareInterface
{

    /**
     * @Flow\Inject
     * @var DavUserService
     */
    protected DavUserService $davUserService;

    /**
     * @Flow\Inject
     * @var Context
     */
    protected Context $securityContext;

    /**
     * @Flow\InjectConfiguration(package="CRON.DAV.Fobi")
     * @var array
     */
    protected array $settings = [];

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @param LoggerInterface $logger
     * @return void
     */
    public function injectLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Eel helper: an jwt token identifying the user to be passed to the JS widget
     *
     * @return string A token identifying the user, or an empty string if there is no user
     *
     * @throws InvalidConfigurationException
     * @throws \Neos\Flow\Exception
     * @throws SessionNotStartedException
     * @throws ObisException
     */
    public function getToken(): string
    {
        $username = $this->davUserService->getCurrentUsername();
        if (empty($username)) {
            return '';
        }

        $davUser = $this->davUserService->getCurrentDavUser();
        $obisRoles = $this->davUserService->getCurrentRoles();
        $sessionRoles = $this->securityContext->getRoles();

        // We want only the roles from Neos which we need for FOBI
        $mappedRoles = [];
        foreach ($sessionRoles as $key => $value) {
          if (isset($this->settings['rolesMapping'][$key])) {
              $mappedRoles[] = $key;
            }
        }

        // Ensures after the arrays have been merged that the roles are always unique
        $roles = array_unique($obisRoles + $mappedRoles);

        return $this->createToken(
            $username, // in the latest versions the username is always the mail address
            $davUser->getFirstName(),
            $davUser->getLastName(),
            $this->getFobiRoles($roles)
        );
    }

    /**
     * Creates a valid token for the Fobi widgets
     *
     * @param string|null $email
     * @param string|null $firstName
     * @param string|null $lastName
     * @param array $roles A list of roles (as strings)
     *
     * @return string A token identifying the user, or an empty string if there is no user
     *
     * @throws InvalidConfigurationException
     */
    protected function createToken(?string $email, ?string $firstName, ?string $lastName, array $roles): string
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
                sprintf('FobiHelper::createToken: Error encoding token: %s', $e->getMessage()),
                LogEnvironment::fromMethodName(__METHOD__)
            );
            return '';
        }

        return $jwt;
    }

    /**
     * Map flow roles to strings to be used in DAV.Fobi
     *
     * Use the CRON.DAV.Fobi.rolesMapping setting
     *
     * @param array $roles A list of flow roles
     * @return array
     */
    protected function getFobiRoles(array $roles): array
    {
        if (empty($roles)) {
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
