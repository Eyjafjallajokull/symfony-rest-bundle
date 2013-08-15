<?php

namespace Eyja\RestBundle\Tests;

use Eyja\RestBundle\QueryParams\FilterParser;

class FilterParserTest extends \PHPUnit_Framework_TestCase {
	/** @var FilterParser */
	public $fp;

	public function setUp() {
		$this->fp = new FilterParser();
	}

	private function execParser($expression, $expected) {
		$result = $this->fp->parse($expression);
		$this->assertEquals($expected, $result);
	}

	private function cExpr($field, $operator, $value) {
		return array('type'=>'expression', 'field' => $field, 'operator' => $operator, 'value' => $value);
	}

	private function cAnd($children) {
		return array('type'=>'and', 'children'=> $children);
	}

	private function cOr($children) {
		return array('type'=>'or', 'children'=> $children);
	}

	public function simpleExpressionDataProvider() {
		$simple = array();
		foreach (array('lt', 'gt', 'ge', 'le', 'eq', 'ne') as $operator) {
			$simple[] = array("id $operator 10", $this->cExpr('id', $operator, 10));
		}
		$simple[] = array("name eq 'string'", $this->cExpr('name', 'eq', 'string'));
		$simple[] = array("name EQ 'string'", $this->cExpr('name', 'eq', 'string'));
		return $simple;
	}

	/**
	 * @dataProvider simpleExpressionDataProvider
	 */
	public function testSimple($expression, $expected) {
		$this->execParser($expression, $expected);
	}

	public function testAnd() {
		$expression = 'id gt 10 AND id lt 20';
		$expected = $this->cAnd(array($this->cExpr('id', 'gt', 10), $this->cExpr('id', 'lt', 20)));
		$this->execParser($expression, $expected);
	}

	public function testAndAnd() {
		$expression = 'id gt 10 and id lt 20 AND price eq 15';
		$expected = $this->cAnd(array($this->cExpr('id', 'gt', 10), $this->cExpr('id', 'lt', 20),
			$this->cExpr('price', 'eq', 15)));
		$this->execParser($expression, $expected);
	}

	public function testOr() {
		$expression = 'id gt 10 OR id lt 20';
		$expected = $this->cOr(array($this->cExpr('id', 'gt', 10), $this->cExpr('id', 'lt', 20)));
		$this->execParser($expression, $expected);
	}

	public function testAndOrAnd() {
		$expression = 'id gt 10 AND id lt 20 OR price gt 100 AND price lt 200';
		$expected = $this->cOr(array(
			$this->cAnd(array($this->cExpr('id', 'gt', 10), $this->cExpr('id', 'lt', 20))),
			$this->cAnd(array($this->cExpr('price', 'gt', 100), $this->cExpr('price', 'lt', 200)))
		));
		$this->execParser($expression, $expected);
	}

	public function testOrAndOr() {
		$expression = 'id gt 10 or id lt 20 AND price gt 100 or price lt 200';
		$expected = $this->cOr(array(
			$this->cExpr('id', 'gt', 10),
			$this->cAnd(array($this->cExpr('id', 'lt', 20), $this->cExpr('price', 'gt', 100))),
			$this->cExpr('price', 'lt', 200)
		));
		$this->execParser($expression, $expected);
	}
}
