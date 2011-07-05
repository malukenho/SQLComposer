<?php
/**
 * SQLComposer
 *
 * @package SQLComposer
 * @author Shane Smith
 * @version 0.1
 */

require_once "SQLComposerBase.class.php";
require_once "SQLComposerWhere.class.php";

require_once "SQLComposerSelect.class.php";
require_once "SQLComposerInsert.class.php";
require_once "SQLComposerReplace.class.php";
require_once "SQLComposerUpdate.class.php";
require_once "SQLComposerDelete.class.php";

/**
 * SQLComposer
 *
 * A factory class for queries.
 *
 * ex:
 *  SQLComposer::select(array("id", "name", "role"))->from("users");
 *
 * @package SQLComposer
 * @author Shane Smith
 */
abstract class SQLComposer {

	/**
	 * A useful list of valid SQL operator
	 *
	 * @var array
	 */
	public static $operators = array(
		'greater than' => '>',
		'greater than or equal' => '>=',
		'less than' => '<',
		'less than or equal' => '<=',
		'equal' => '=',
		'not equal' => '!=',
		'between' => 'between',
		'in' => 'in'
	);

	/**************
	 **  SELECT  **
	 **************/

	/**
	 * Start a new SELECT statement
	 *
	 * @see SQLComposerSelect::__construct()
	 * @param array $params
	 * @param string|array $select
	 * @param string $mysqli_types
	 * @return SQLComposerSelect
	 */
	public static function select($select = null, array $params = null, $mysqli_types = null) {
		return new SQLComposerSelect($select, $params, $mysqli_types);
	}


	/**************
	 **  INSERT  **
	 **************/

	/**
	 * Start a new INSERT statement
	 *
	 * @see SQLComposerInsert::__construct()
	 * @param string $table
	 * @return SQLComposerInsert
	 */
	public static function insert($table=null) {
		return self::insert_into($table);
	}

	/**
	 * Start a new INSERT statement
	 *
	 * @see SQLComposerInsert::__construct()
	 * @param string $table
	 * @return SQLComposerInsert
	 */
	public static function insert_into($table = null) {
		return new SQLComposerInsert($table);
	}


	/***************
	 **  REPLACE  **
	 ***************/

	/**
	 * Start a new REPLACE statement
	 *
	 * @see SQLComposerReplace::__construct()
	 * @param string $table
	 * @return SQLComposerReplace
	 */
	public static function replace($table = null) {
		return self::replace_into($table);
	}

	/**
	 * Start a new REPLACE statement
	 *
	 * @see SQLComposerReplace::__construct()
	 * @param string $table
	 * @return SQLComposerReplace
	 */
	public static function replace_into($table = null) {
		return new SQLComposerReplace($table);
	}

	/**************
	 **  UPDATE  **
	 **************/

	/**
	 * Start a new UPDATE statement
	 *
	 * @see SQLComposerUpdate::__construct()
	 * @param string|array $table
	 * @return SQLComposerUpdate
	 */
	public static function update($table=null) {
		return new SQLComposerUpdate($table);
	}


	/**************
	 **  DELETE  **
	 **************/

	/*
	 *  Left out delete($table) to enforce the DELETE FROM ... USING ... style of query
	 */

	/**
	 * Start a new DELETE statement
	 *
	 * @see SQLComposerDelete::__construct()
	 * @param string|array $table
	 * @return SQLComposerDelete
	 */
	public static function delete_from($table=null) {
		return new SQLComposerDelete($table);
	}


	/***************
	 **  HELPERS  **
	 ***************/

	/**
	 * Given an sql snippet in the form "column in (?)"
	 * and an array of parameters to be used as operands,
	 * will return an array of the form array(sql, params, mysqli_types)
	 * with the sql's '?' expanded to the number of parameters.
	 * If the given mysqli_types is only one character, it will be repeated
	 * the number of parameters.
	 *
	 * ex:
	 *  $sizes = array(24, 64, 84, 13, 95);
	 *  SQLComposer::in("size in (?)", $sizes, "i");
	 *
	 * will return
	 *
	 *  array("size in (?, ?, ?, ?, ?)", array(24, 64, 84, 13, 95), "iiiii")
	 *
	 * @param string $sql
	 * @param array $params
	 * @param string $mysqli_types
	 * @return array
	 */
	public static function in($sql, array $params, $mysqli_types="") {
		$given_params = $params;

		$placeholders = array( );
		$params = array();

		foreach ($given_params as $p) {
			if ($p instanceof SQLComposerExpr) {
				$placeholders[] = $p->value;
				if (!empty($p->params)) {
					$params = array_merge($params, $p->params);
				}
			} else {
				$placeholders[] = "?";
				$params[] = $p;
			}
		}

		if (strlen($mysqli_types) == 1) {
			$mysqli_types = str_repeat($mysqli_types, sizeof($params));
		}

		$placeholders = implode(", ", $placeholders);
		$sql = str_replace("?", $placeholders, $sql);
		return array($sql, $params, $mysqli_types);
	}

	/**
	 * Whether the given array is associative
	 *
	 * @param $array
	 * @return bool
	 */
	public static function is_assoc($array) {
		return (array_keys($array) !== range(0, count($array) - 1));
	}

	/**
	 * Whether the given operator is a valid SQL operator
	 *
	 * @param string $op
	 * @return bool
	 */
	public static function isValidOperator($op) {
		return in_array($op, self::$operators);
	}

	/**
	 * Returns the SQL relating to the operator
	 *
	 * @param string $column
	 * @param string $op
	 * @param array $params
	 * @param string $mysqli_types
	 * @return string
	 */
	public static function applyOperator($column, $op, array $params=null, $mysqli_types="") {
		switch ($op) {
			case '>': case '>=':
			case '<': case '<=':
			case '=': case '!=':
				return array("{$column} {$op} ?", $params, $mysqli_types);
			case 'in':
				return self::in("{$column} in (?)", $params, $mysqli_types);
			case 'between':
				$sql = "{$column} between ";
				$p = array_shift($params);
				if ($p instanceof SQLComposerExpr) {
					$sql .= $p->value;
				} else {
					$sql .= "?";
					array_push($params, $p);
				}
				$sql .= " and ";
				$p = array_shift($params);
				if ($p instanceof SQLComposerExpr) {
					$sql .= $p->value;
				} else {
					$sql .= "?";
					array_push($params, $p);
				}
				return array($sql, $params, $mysqli_types);
			default:
				throw new SQLComposerException("Invalid operator: {$op}");
		}
	}

	/**
	 * A factory for SQLComposerExpr
	 *
	 * @param string $val
	 * @param array $params
	 * @param string $mysqli_types
	 * @return SQLComposerExpr
	 */
	public static function expr($val, array $params=array(), $mysqli_types="") {
		return new SQLComposerExpr($val, $params, $mysqli_types);
	}
}

/**
 * A container to denote an expression to be directly embedded
 */
class SQLComposerExpr {
	public $value, $params, $mysqli_types;
	public function __construct($val, array $params=array(), $mysqli_types="") {
		$this->value = $val;
		$this->params = $params;
		$this->mysqli_types = $mysqli_types;
	}
}

/**
 * SQLComposerException
 *
 * The main exception to be used within these classes
 */
class SQLComposerException extends Exception {}

?>