<?php

namespace SMW\MediaWiki;

use SMW\DBConnectionProvider;
use ResultWrapper;
use UnexpectedValueException;

/**
 * This adapter class covers MW DB specific operations. Changes to the
 * interface are likely therefore this class should not be used other than by
 * SMW itself.
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class Database {

	protected $dbConnection;

	public function __construct( DBConnectionProvider $dbConnection ) {
		$this->dbConnection = $dbConnection;
	}

	/**
	 * @since 1.9.0.2
	 *
	 * @return DatabaseBase
	 */
	public function getDB() {
		return $this->dbConnection->getConnection();
	}

	/**
	 * @see DatabaseBase::tableName
	 *
	 * @since 1.9.0.2
	 *
	 * @param string $tableName
	 *
	 * @return string
	 */
	public function tableName( $tableName ) {

		if ( $this->getDB()->getType() == 'sqlite' ) {
			return $tableName;
		}

		return $this->getDB()->tableName( $tableName );
	}

	/**
	 * @see DatabaseBase::addQuotes
	 *
	 * @since 1.9.0.2
	 *
	 * @param string $tableName
	 *
	 * @return string
	 */
	public function addQuotes( $value ) {
		return $this->getDB()->addQuotes( $value );
	}

	/**
	 * @see DatabaseBase::fetchObject
	 *
	 * @since 1.9.0.3
	 *
	 * @param ResultWrapper $res
	 *
	 * @return string
	 */
	public function fetchObject( $res ) {
		return $this->getDB()->fetchObject( $res );
	}

	/**
	 * @see DatabaseBase::numRows
	 *
	 * @since 1.9.0.2
	 *
	 * @param mixed $results
	 *
	 * @return integer
	 */
	public function numRows( $results ) {
		return $this->getDB()->numRows( $results );
	}

	/**
	 * @see DatabaseBase::freeResult
	 *
	 * @since 1.9.0.2
	 *
	 * @param ResultWrapper $res
	 */
	public function freeResult( $res ) {
		$this->getDB()->freeResult( $res );
	}

	/**
	 * @see DatabaseBase::select
	 *
	 * @since 1.9.0.2
	 *
	 * @param string $tableName
	 * @param $fields
	 * @param $conditions
	 * @param array $options
	 *
	 * @return ResultWrapper
	 * @throws UnexpectedValueException
	 */
	public function select( $tableName, $fields, $conditions = '', $fname, array $options = array() ) {

		$results = $this->getDB()->select(
			$tableName,
			$fields,
			$conditions,
			$fname,
			$options
		);

		if ( $results instanceof ResultWrapper ) {
			return $results;
		}

		throw new UnexpectedValueException(
			'Expected a ResultWrapper instance as query result for ' .
			$tableName . '#' .
			$fields . '#' .
			$conditions
		);
	}

	/**
	 * @see DatabaseBase::query
	 *
	 * @since 1.9.0.3
	 *
	 * @param string $sql
	 * @param $fname
	 * @param $ignoreException
	 *
	 * @return ResultWrapper
	 * @throws MWException
	 */
	public function query( $sql, $fname = __METHOD__, $ignoreException = false ) {
		return $this->getDB()->query( $sql, $fname, $ignoreException );
	}

	/**
	 * @see DatabaseBase::affectedRows
	 *
	 * @since 1.9.0.3
	 *
	 * @return int
	 */
	function affectedRows() {
		return $this->getDB()->affectedRows();
	}

	/**
	 * @see DatabaseBase::makeSelectOptions
	 *
	 * @since 1.9.0.3
	 *
	 * @param array $options
	 *
	 * @return array
	 */
	public function makeSelectOptions( $options ) {
		return $this->getDB()->makeSelectOptions( $options );
	}

}