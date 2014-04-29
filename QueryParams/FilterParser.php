<?php

namespace Eyja\RestBundle\QueryParams;

use JMS\Parser\AbstractParser;
use JMS\Parser\SimpleLexer;

/**
 * Parse oData-like filter string
 *
 * @see http://www.odata.org/documentation/uri-conventions#45_Filter_System_Query_Option_filter
 */
class FilterParser extends AbstractParser {
	const T_UNKNOWN = 0;
	const T_PROPERTY = 1;
	const T_OPERATOR = 2;
	const T_OPERATOR_JOIN = 4;
	const T_VALUE = 3;

	public static $regExps = array(
		self::T_OPERATOR_JOIN => '(and|or)',
		self::T_OPERATOR => '(eq|ne|gt|lt|ge|le|like)',
		self::T_PROPERTY => '([a-z]+)',
		self::T_VALUE => '(-?\d+(?:\.\d+)?|\'[^\']*\')',
	);

	public function __construct() {
		$regexp = '/' . join('|', array_values(self::$regExps)) . '|\s*/ix';
		parent::__construct(new SimpleLexer($regexp,
			array(self::T_UNKNOWN => 'T_UNKNOWN',
				self::T_PROPERTY => 'T_PROPERTY',
				self::T_OPERATOR => 'T_OPERATOR',
				self::T_VALUE => 'T_VALUE',
				self::T_OPERATOR_JOIN => 'T_OPERATOR_JOIN'),
			function ($value) {
				foreach (FilterParser::$regExps as $id => $regExp) {
					if (preg_match('/^' . $regExp . '$/ix', $value)) {
						if (in_array($id,
							array(FilterParser::T_OPERATOR_JOIN, FilterParser::T_OPERATOR))
						) {
							return array($id, strtolower($value));
						}
						if (FilterParser::T_VALUE) {
							$value = trim($value, '\'');
						}
						return array($id, $value);
					}
				}
				return array(FilterParser::T_UNKNOWN, 0);
			}
		));
	}

	protected function parseInternal($children = array(), $previousOperator = null) {
		$result = null;
		if ($this->lexer->isNext(self::T_PROPERTY)) {
			$result = $this->parseExpression();
			$children[] = $result;
		}
		$nextOperator = $previousExpression = null;
		while ($this->lexer->isNext(self::T_OPERATOR_JOIN)) {
			$nextOperator = $this->match(self::T_OPERATOR_JOIN);
			if ($previousOperator && $previousOperator !== $nextOperator) {
				if ($nextOperator == 'or' && $previousExpression) {
					$children[] = $previousExpression;
					$previousExpression = null;
				}
				break;
			}
			if ($previousExpression) {
				$children[] = $previousExpression;
				$previousExpression = null;
			}
			$previousOperator = $nextOperator;
			$previousExpression = $this->parseExpression();
		}

		if (($previousOperator == $nextOperator && $previousOperator) || $previousOperator == 'and') {
			if ($previousExpression) {
				$children[] = $previousExpression;
				$previousExpression = null;
			}
			$result = array('type' => $previousOperator, 'children' => $children);
		}

		if ($nextOperator && $this->lexer->next) {
			if ($nextOperator == 'or') {
				$whateverIsNext = $this->parseInternal();
				$result = array('type' => 'or', 'children' => array($result, $whateverIsNext));
			} else {
				if ($previousExpression) {
					$children = array($previousExpression);
				}
				$whateverIsNext = $this->parseInternal($children, $nextOperator);
				array_unshift($whateverIsNext['children'], $result);
				$result = $whateverIsNext;
			}
		}

		return $result;
	}

	protected function parseExpression() {
		$property = $this->match(self::T_PROPERTY);
		$operator = $this->match(self::T_OPERATOR);
		$value = $this->match(self::T_VALUE);
		$condition = array('type' => 'expression', 'field' => $property, 'operator' => $operator, 'value' => $value);
		return $condition;
	}
}
