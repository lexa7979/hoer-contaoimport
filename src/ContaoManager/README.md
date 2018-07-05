
Example of "Plugin.php":
	<?php

	namespace Cepharum\TimetableBundle\ContaoManager;

	use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
	use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
	use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;

	class Plugin implements BundlePluginInterface
	{
		public function getBundles(ParserInterface $parser)
		{
			return [
				BundleConfig::create('Cepharum\TimetableBundle\CepharumTimetableBundle')
					->setLoadAfter([
						'Contao\CoreBundle\ContaoCoreBundle',
						'Contao\ManagerBundle\ContaoManagerBundle'
					])
					->setReplace(['ceph-timetable'])
			];
		}
	}

excerpt of "BundlePluginInterface.php":
	namespace Contao\ManagerPlugin\Bundle;

	use Contao\ManagerPlugin\Bundle\Config\ConfigInterface;
	use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;

	interface BundlePluginInterface {
		/**
		 * Gets a list of autoload configurations for this bundle.
		 *
		 * @param ParserInterface $parser
		 *
		 * @return ConfigInterface[]
		 */
		public function getBundles(ParserInterface $parser);
	}

excerpt of "ConfigInterface.php":
	namespace Contao\ManagerPlugin\Bundle\Config;
	
	use Symfony\Component\HttpKernel\Bundle\BundleInterface;
	use Symfony\Component\HttpKernel\KernelInterface;

	interface ConfigInterface {
		/**
		 * Sets the replaces.
		 */
		public function setReplace(array $replace);

		/**
		 * Sets the "load after" bundles.
		 */
		public function setLoadAfter(array $loadAfter);

		/**
		 * Sets if bundle should be loaded in "prod" environment.
		 */
		public function setLoadInProduction($loadInProduction);

		/**
		 * Sets if bundle should be loaded in "dev" environment.
		 */
		public function setLoadInDevelopment($loadInDevelopment);

		/**
		 * Returns a bundle instance for this configuration.
		 */
		public function getBundleInstance(KernelInterface $kernel);
	}
