<?php

declare(strict_types=1);

namespace MatiCore\Sentry;

use MatiCore\User\BaseUser;
use MatiCore\User\StorageIdentity;
use Nette\Security\IIdentity;
use Nette\Security\User;
use Sentry\ClientBuilder;
use Sentry\Integration\RequestIntegration;
use Sentry\SentrySdk;
use Sentry\Severity;
use Sentry\State\Scope;
use Tracy\Debugger;
use Tracy\Dumper;
use Tracy\ILogger;
use Tracy\Logger;
use function Sentry\captureException;
use function Sentry\captureMessage;

/**
 * Class SentryLogger
 * @package MatiCore\Sentry
 */
class SentryLogger extends Logger
{
	/**
	 * @var IIdentity|null
	 */
	private IIdentity|null $identity;

	/**
	 * @var array
	 */
	private array $userFields = [];

	/**
	 * @var array
	 */
	private array $priorityMapping = [];

	/**
	 * @param string $dsn
	 * @param string $environment
	 */
	public function register(string $dsn, string $environment): void
	{
		$client = ClientBuilder::create([
			'dsn' => $dsn,
			'environment' => $environment,
			'attach_stacktrace' => true,
			'default_integrations' => false,
			'integrations' => [
				new RequestIntegration(),
			],
		])->getClient();

		SentrySdk::init()->bindClient($client);

		$this->email = &Debugger::$email;
		$this->directory = Debugger::$logDirectory;
	}

	/**
	 * @param User $user
	 */
	public function setUser(User $user): void
	{
		if ($user->isLoggedIn()) {
			$this->identity = $user->getIdentity();
		}
	}

	/**
	 * @param array $userFields
	 */
	public function setUserFields(array $userFields): void
	{
		$this->userFields = $userFields;
	}

	/**
	 * @param array $priorityMapping
	 */
	public function setPriorityMapping(array $priorityMapping): void
	{
		$this->priorityMapping = $priorityMapping;
	}

	/**
	 * @param mixed $value
	 * @param string $priority
	 * @return string|null
	 */
	public function log($value, $priority = ILogger::INFO): ?string
	{
		$response = parent::log($value, $priority);
		$severity = $this->tracyPriorityToSentrySeverity($priority);

		if ($severity === null) {
			$mappedSeverity = $this->priorityMapping[$priority] ?? null;
			if ($mappedSeverity) {
				$severity = new Severity((string) $mappedSeverity);
			}
		}

		if ($severity === null) {
			return $response;
		}

		SentrySdk::getCurrentHub()->configureScope(function (Scope $scope) use ($severity) {
			$scope->setLevel($severity);
			if ($this->identity !== null) {
				$userFields = [
					'id' => $this->identity->getId(),
				];

				if ($this->identity instanceof StorageIdentity) {
					$user = $this->identity->getUser();

					if ($user instanceof BaseUser) {
						$userFields['entity_id'] = $user->getId();
						$userFields['name'] = $user->getName();
						$userFields['email'] = $user->getEmail();
					}
				}

				foreach ($this->userFields as $name) {
					$userFields[$name] = $this->identity->{$name} ?? null;
				}

				$scope->setUser($userFields);
			}


			$scope->setContext('app', [
				'app_name' => 'APP Universe CMS',
				'app_version' => \MatiCore\Cms\CmsHelper::getCMSVersion(),
			]);
		});

		if ($value instanceof \Throwable) {
			captureException($value);
		} else {
			captureMessage(is_string($value) ? $value : Dumper::toText($value));
		}

		return $response;
	}

	/**
	 * @param string $priority
	 * @return Severity|null
	 */
	private function tracyPriorityToSentrySeverity(string $priority): ?Severity
	{
		return match ($priority) {
			ILogger::DEBUG => Severity::debug(),
			ILogger::INFO => Severity::info(),
			ILogger::WARNING => Severity::warning(),
			ILogger::ERROR, ILogger::EXCEPTION => Severity::error(),
			ILogger::CRITICAL => Severity::fatal(),
			default => null,
		};
	}
}