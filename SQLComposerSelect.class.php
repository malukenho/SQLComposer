<?php
require_once 'SQLComposer.class.php';

/**
 * SQLComposerSelect
 *
 * A SELECT query
 */
class SQLComposerSelect extends SQLComposerWhere {

	/**
	 * SELECT DISTINCT ...
	 *
	 * @var bool
	 */
	protected $distinct = false;

	/**
	 * OFFSET clause
	 *
	 * @var int
	 */
	protected $offset = null;

	/**
	 * GROUP BY clause
	 *
	 * @var array
	 */
	protected $group_by = array( );

	/**
	 * GROUP BY ... WITH ROLLUP
	 *
	 * @var bool
	 */
	protected $with_rollup = false;

	/**
	 * HAVING clause
	 *
	 * @var array
	 */
	protected $having = array( );

	/**
	 * ORDER BY clause
	 *
	 * @var array
	 */
	protected $order_by = array( );

	/**
	 * LIMIT clause
	 *
	 * @var int
	 */
	protected $limit = null;

	/*******************
	 **  CONSTRUCTOR  **
	 *******************/

	/**
	 * Constructor.
	 *
	 * Calls select()
	 *
	 * @see select()
	 * @param string|array $select
	 * @param array $params
	 * @param string $mysqli_types
	 */
	public function __construct($select = null, array $params = null, $mysqli_types = "") {
		if (isset($select)) {
			$this->select($select, $params, $mysqli_types);
		}
	}

	/**
	 * Add a statement for SELECT
	 *
	 * @param string|array $select
	 * @param array $params
	 * @param string $mysqli_types
	 * @return SQLComposerSelect
	 */
	public function select($select, array $params = null, $mysqli_types = "") {
		$this->columns = array_merge($this->columns, (array)$select);
		$this->_add_params('select', $params, $mysqli_types);
		return $this;
	}

	/**
	 * DISTINCT
	 *
	 * @param bool $distinct
	 * @return SQLComposerSelect
	 */
	public function distinct($distinct = true) {
		$this->distinct = (bool)$distinct;
		return $this;
	}

	/**
	 * Add a statement for GROUP BY
	 *
	 * @param string|array $group_by
	 * @param array $params
	 * @param string $mysqli_types
	 * @return SQLComposerSelect
	 */
	public function group_by($group_by, array $params = null, $mysqli_types = "") {
		$this->group_by = array_merge($this->group_by, (array)$group_by);
		$this->_add_params('group_by', $params, $mysqli_types);
		return $this;
	}

	/**
	 * Add WITH ROLLUP after the GROUP BY
	 *
	 * @param bool $with_rollup
	 * @return SQLComposerSelect
	 */
	public function with_rollup($with_rollup = true) {
		$this->with_rollup = $with_rollup;
		return $this;
	}

	/**
	 * Add an ORDER BY statement
	 *
	 * @param string|array $order_by
	 * @param array $params
	 * @param string $mysqli_types
	 * @return SQLComposerSelect
	 */
	public function order_by($order_by, array $params = null, $mysqli_types = "") {
		$this->order_by = array_merge($this->order_by, (array)$order_by);
		$this->_add_params('order_by', $params, $mysqli_types);
		return $this;
	}

	/**
	 * LIMIT clause
	 *
	 * @param int $limit
	 * @return SQLComposerSelect
	 */
	public function limit($limit) {
		$this->limit = (int)$limit;
		return $this;
	}

	/**
	 * OFFSET
	 *
	 * @param int $offset
	 * @return SQLComposerSelect
	 */
	public function offset($offset) {
		$this->offset = (int)$offset;
		return $this;
	}

	/**
	 * Add a HAVING statement
	 *
	 * @param string|array $having
	 * @param array $params
	 * @param string $mysqli_types
	 * @return SQLComposerSelect
	 */
	public function having($having, array $params = null, $mysqli_types = "") {
		$this->having = array_merge($this->having, (array)$having);
		$this->_add_params('having', $params, $mysqli_types);
		return $this;
	}

	/**
	 * Add a HAVING expression by using SQLComposer::in()
	 *
	 * @see SQLComposer::in()
	 * @param string $having
	 * @param array $params
	 * @param string $mysqli_types
	 * @return SQLComposerSelect
	 */
	public function having_in($having, array $params, $mysqli_types = "") {
		if (!is_string($having)) throw new SQLComposerException("Method having_in must be called with a string as first argument.");
		list($having, $params, $mysqli_types) = SQLComposer::in($having, $params, $mysqli_types);
		return $this->having($having, $params, $mysqli_types);
	}

	/**
	 * Open a paranthesis for sub-expressions using 'AND'
	 *
	 * @return SQLComposerSelect
	 */
	public function open_having_and() {
		$this->having[] = array( '(', 'AND' );
		return $this;
	}

	/**
	 * Open a paranthesis for sub-expressions using 'OR'
	 *
	 * @return SQLComposerSelect
	 */
	public function open_having_or() {
		$this->having[] = array( '(', 'OR' );
		return $this;
	}

	/**
	 * Close a paranthesis for sub-expressions
	 *
	 * @return SQLComposerSelect
	 */
	public function close_having() {
		$this->having[] = array( ')' );
		return $this;
	}


	/**************
	 **  HAVING  **
	 **************/

	/**
	 * Render the having clause (without the starting 'HAVING')
	 *
	 * @return string
	 */
	protected function _render_having() {
		return SQLComposerBase::_render_bool_expr($this->having);
	}

	/**
	 * Get the rendered SQL query
	 *
	 * @return string
	 */
	public function render() {
		$columns = empty($this->columns) ? "*" : implode(", ", $this->columns);

		$distinct = $this->distinct ? "DISTINCT" : "";

		$from = "\nFROM " . implode("\n\t", $this->tables);

		$where = empty($this->where) ? "" : "\nWHERE " . $this->_render_where();

		$group_by = empty($this->group_by) ? "" : "\nGROUP BY " . implode(", ", $this->group_by);

		$with_rollup = $this->with_rollup ? "WITH ROLLUP" : "";

		$having = empty($this->having) ? "" : "\nHAVING " . $this->_render_having();

		$order_by = empty($this->order_by) ? "" : "\nORDER BY " . implode(", ", $this->order_by);

		$limit = "";
		if ($this->limit) {
			$limit = "\nLIMIT {$this->limit}";
			if ($this->offset) {
				$limit .= "\nOFFSET {$this->offset}";
			}
		}

		return "SELECT {$distinct} {$columns} {$from} {$where} {$group_by} {$with_rollup} {$having} {$order_by} {$limit}";
	}

	/**
	 * Get the parameters
	 *
	 * @return array
	 */
	public function getParams() {
		return $this->_get_params('select', 'tables', 'where', 'group_by', 'having', 'order_by');
	}

}
