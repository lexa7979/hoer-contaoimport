<?php

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class ContaoImportExtension extends ConfigurableExtension
{
	protected function loadInternal(array $mergedConfig, ContainerBuilder $container)
	{
		// $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
		// $loader->load('services.yml');
	}
}
