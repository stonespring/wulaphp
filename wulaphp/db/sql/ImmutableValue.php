<?php

namespace wulaphp\db\sql;

use wulaphp\db\dialect\DatabaseDialect;

/**
 * Immutable value for a value which references to field or a function
 *
 * @author guangfeng.ning
 *
 */
class ImmutableValue {
	private $value;
	private $alias;
	private $nquote = false;
	/**
	 * @var DatabaseDialect
	 */
	private $dialect;

	/**
	 * ImmutableValue constructor.
	 *
	 * @param string      $value
	 * @param string|null $alias
	 */
	public function __construct(string $value, string $alias = null) {
		$this->value = $value;
		$this->alias = $alias;
	}

	/**
	 * 数据库驱动.
	 *
	 * @param \wulaphp\db\dialect\DatabaseDialect $dialect
	 *
	 * @return \wulaphp\db\sql\ImmutableValue
	 */
	public function setDialect(DatabaseDialect $dialect): ImmutableValue {
		$this->dialect = $dialect;

		return $this;
	}

	/**
	 * 别名.
	 *
	 * @param string $alias
	 *
	 * @return \wulaphp\db\sql\ImmutableValue
	 */
	public function alias(string $alias): ImmutableValue {
		$this->alias = $alias;

		return $this;
	}

	/**
	 * 不转义字符.
	 *
	 * @return \wulaphp\db\sql\ImmutableValue
	 */
	public function noquote(): ImmutableValue {
		$this->nquote = true;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getValue(): string {
		return $this->__toString();
	}

	public function __toString(): string {
		$dialect = $this->dialect;
		if ($dialect == null || $this->nquote) {
			$value = $this->value;
		} else {
			$value = trim($dialect->quote($this->value), "'");
		}
		if ($this->alias) {
			return $value . ' AS `' . $this->alias . '`';
		} else {
			return $value;
		}
	}
}