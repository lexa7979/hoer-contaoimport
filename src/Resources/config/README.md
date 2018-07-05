This directory takes the configuration files for Symfony, e.g. "services.yml".

	The files of this directory need to be loaded manually by a ConfigurableExtension derivate.
	The related code must be put inside src/DependencyInjection.

	Example "src/DependencyInjection/TimetableExtension.php" to load the file "src/Resources/config/services.yml":

		<?php

		use Symfony\Component\Config\FileLocator;
		use Symfony\Component\DependencyInjection\ContainerBuilder;
		use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
		use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

		class TimetableExtension extends ConfigurableExtension
		{
			protected function loadInternal(array $mergedConfig, ContainerBuilder $container)
			{
				$loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
				$loader->load('services.yml');
			}
		}
