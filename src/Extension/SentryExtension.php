<?php

declare(strict_types=1);

namespace MatiCore\Sentry;

use Nette\DI\CompilerExtension;
use Nette\PhpGenerator\ClassType;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Tracy\Debugger;

/**
 * Class SentryExtension
 * @package MatiCore\Sentry
 */
class SentryExtension extends CompilerExtension
{
	/**
	 * @return Schema
	 */
	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'dsn' => Expect::string()->required(),
			'environment' => Expect::string()->required()->default('local'),
			'user_fields' => Expect::array(),
			'priority_mapping' => Expect::array(),
		]);
	}

	public function loadConfiguration(): void
	{
		$this->getContainerBuilder()
			->addDefinition($this->prefix('sentryLogger'))
			->setFactory(SentryLogger::class, [Debugger::$logDirectory])
			->addSetup(
				'register',
				[
					$this->config->dsn,
					$this->config->environment,
				]
			)->addSetup(
				'setUserFields',
				[
					$this->config->user_fields,
				]
			)->addSetup(
				'setPriorityMapping',
				[
					$this->config->priority_mapping,
				]
			);
	}

	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();
		if ($builder->hasDefinition('tracy.logger')) {
			$builder->getDefinition('tracy.logger')->setAutowired(false);
		}
		if ($builder->hasDefinition('security.user')) {
			$builder->getDefinition($this->prefix('sentryLogger'))
				->addSetup('setUser', [$builder->getDefinition('security.user')]);
		}
	}

	/**
	 * @param ClassType $class
	 */
	public function afterCompile(ClassType $class): void
	{
		$class->getMethod('initialize')
			->addBody('Tracy\Debugger::setLogger($this->getService(?));', [$this->prefix('sentryLogger')]);
	}
}