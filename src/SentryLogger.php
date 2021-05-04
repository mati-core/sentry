<?php

declare(strict_types=1);

namespace MatiCore\Sentry;

use MatiCore\User\BaseUser;
use MatiCore\User\IUser;
use MatiCore\User\StorageIdentity;
use Nette\Http\Session;
use Nette\Security\IIdentity;
use Nette\Security\User;
use Sentry\Integration\RequestIntegration;
use Sentry\Severity;
use Sentry\State\Scope;
use Tracy\Debugger;
use Tracy\Dumper;
use Tracy\ILogger;
use Tracy\Logger;
use function Sentry\captureException;
use function Sentry\captureMessage;
use function Sentry\configureScope;
use function Sentry\init;

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
	 * @var Session|null
	 */
	private Session|null $session;

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
		init([
			'dsn' => $dsn,
			'environment' => $environment,
			'attach_stacktrace' => true,
			'default_integrations' => false,
			'integrations' => [
				new RequestIntegration(),
			],
		]);

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
	 * @param Session $session
	 */
	public function setSession(Session $session): void
	{
		$this->session = $session;
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

		configureScope(function (Scope $scope) use ($severity) {
			if ($severity === null) {
				return;
			}

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

			if ($this->session) {
				$data = [];
				foreach ($this->session->getIterator() as $section) {
					foreach ($this->session->getSection($section)->getIterator() as $key => $val) {
						$data[$section][$key] = $val;
					}
				}
				$scope->setExtra('session', $data);
			}
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