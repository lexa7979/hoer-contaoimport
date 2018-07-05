<?php

/**
 * Taken from:
 * - https://www.youtube.com/watch?v=34R7buoM_cc
 * - https://www.cyberspectrum.de/files/downloads/talks/c4extension_cnt2017.pdf (page 26)
 **/

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
	public function getConfigTreeBuilder()
	{
		$treeBuilder = new TreeBuilder();
		$rootNode = $treeBuilder->root('hoer-contaoimport');

		return $treeBuilder;
	}
}
