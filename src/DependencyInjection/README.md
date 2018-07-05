

Information about services:
(from https://www.youtube.com/watch?v=34R7buoM_cc, https://www.cyberspectrum.de/files/downloads/talks/c4extension_cnt2017.pdf)

- Services can be designed in dependency-injection container.
- Single responsibilty principle: Every action should be done by a separate service.
- Services have to be stateless.
- Every service does exactly one task.
- Good for unit-testing.

Example:
	- src/DependencyInjection/DownloadViaMailExtension.php:
			use Symfony\Component\Config\FileLocator;
			use Symfony\Component\DependencyInjection\ContainerBuilder;
			use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
			use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

			class DownloadViaMailExtension extends ConfigurableExtension {
				protected function loadInternal(array $mergedConfig, ContainerBuilder $container) {
					$loader = new YamlFileLoader($container, new FileLocator(__DIR__.?/../Resources/config?));
					$loader->load(?services.yml?);
				}
			}

	- src/Resources/contao/config/config.php:
			$GLOBALS['TL_HOOKS']['processFormData'][] =['mybundle.listener.process_form_data', 'onProcessFormData'];

	- src/Resources/config/services.yml:
			services:
				mybundle.listener.process_form_data:
					class: 'MyBundle\EventListener\ProcessFormsListener'
					arguments:
						- "@database_connection"
						- "@swiftmailer.mailer"

	- src/EventListener/ProcessFormsListener.php:
			class ProcessFormsListener {
				private $database;
				private $mailer;
				public function __construct(Connection $database, Swift_Mailer $mailer) {
					$this->database = $database;
					$this->mailer = $mailer;
				}
				public function onProcessFormData($arrPost, $arrForm, $arrFiles) {
					$this->database->insert(...);
					$this->mailer->send(...);
				}
			}
