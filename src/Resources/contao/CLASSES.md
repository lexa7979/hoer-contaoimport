# Contao's base classes

## Database

*	Path: `vendor/contao/contao-bundle/src/Resources/contao/library/Contao/Database.php`

	```php
	/**
	 * Handle the database communication
	 *
	 * The class is responsible for connecting to the database, listing tables and
	 * fields, handling transactions and locking tables. It also creates the related
	 * Database\Statement and Database\Result objects.
	 *
	 * Usage:
	 *
	 *     $db   = Database::getInstance();
	 *     $stmt = $db->prepare("SELECT * FROM tl_user WHERE id=?");
	 *     $res  = $stmt->execute(4);
	 *
	 * @property string $error The last error message
	 *
	 * @author Leo Feyer <https://github.com/leofeyer>
	 */
	```

*	Enables usage of the database where no direct access is possible, e.g. in static functions.

	```php
	use Contao\Database;

	class ... extends Backend {
		static function test() {
			$objDatabase = Database::getInstance();

			$list = [];
			$data = $objDatabase->execute('SELECT id FROM table');
			while ($r_data = $data->next()) {
				$list[] = $r_data->id;
			}
		}
	}
	```

## Model

*	Path: `vendor/contao/contao-bundle/src/Resources/contao/library/Contao/Model.php`

	```php
	/**
	 * Reads objects from and writes them to the database
	 *
	 * The class allows you to find and automatically join database records and to
	 * convert the result into objects. It also supports creating new objects and
	 * persisting them in the database.
	 *
	 * Usage:
	 *
	 *     // Write
	 *     $user = new UserModel();
	 *     $user->name = 'Leo Feyer';
	 *     $user->city = 'Wuppertal';
	 *     $user->save();
	 *
	 *     // Read
	 *     $user = UserModel::findByCity('Wuppertal');
	 *
	 *     while ($user->next())
	 *     {
	 *         echo $user->name;
	 *     }
	 *
	 * @property integer $id The ID
	 *
	 * @author Leo Feyer <https://github.com/leofeyer>
	 */
	```

## Module

*	Path: `vendor/contao/contao-bundle/src/Resources/contao/modules/Module.php`
*	Parent class for front end modules

## BackendModule

*	Set `$this->strTemplate` in your derived class to determine which template shall be rendered if `$this->generate()` is not overloaded.
*	The function `compile()` can be overloaded to set custom properties in $this->Template. This data can be accessed in the template as part of $this.
	```php
	protected function compile() {
		$this->Template->hello = "World";
		return;
	}
	```
	```html
	<p>Hello <?= $this->hello ?></p>
	```


