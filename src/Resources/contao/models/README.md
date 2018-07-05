
Models are helper classes that are used to access the database.

The interface "Model" implements a lot of properties and methods:

/**
 * - Values of every field of the given table $strTable ($id, $pid, $sorting, ...):
 * @property $<field>
 *
 * - Find all records:
 * @method static Model|Model\Collection|null		findAll($opt = [])
 *
 * - Find multiple records by their IDs:
 * @method static Model|Model\Collection|null		findMultipleByIds($arrIds, $opt = [])
 *
 * - Find all records with a given value:
 *	(Magic methods for every field of the given table $strTable)
 * @method static Model|Model\Collection|null		findBy<field>($value, $opt = [])
 * @method static Model|Model\Collection|null		findBy(<field>, $value, $opt = [])
 *
 * - Find a single record by its primary key / ID or alias:
 * @method static Model|null		findByPk($id, $opt = [])
 * @method static Model|null		findByIdOrAlias($value, $opt = [])
 *
 * - Find a single record with a given value:
 *	(Magic methods for every field of the given table $strTable)
 * @method static Model|null		findOneBy<field>($value, $opt = [])
 * @method static Model|null		findOneBy(<field>, $value, $opt = [])
 *
 * - Return the number of records with a given value:
 *	(Magic methods for every field of the given table $strTable)
 * @method static Model|null		countBy<field>($value, $opt = array())
 */
class ... extends Model {

	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = '...';

	/**
	 * Methods for special tasks
	 */
	...
}
