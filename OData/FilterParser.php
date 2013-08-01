<?php

namespace Eyja\RestBundle\OData;

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
        self::T_OPERATOR_JOIN => '(and)',
        self::T_OPERATOR => '(eq|ne|gt|lt|ge|le)',
        self::T_PROPERTY => '([a-z]+)',
        self::T_VALUE => '(\d+(?:.\d+)?|\'[^\']*\')',
    );

    public function __construct() {
        $regexp = '/'.join('|', array_values(self::$regExps)).'|\s*/ix';
        parent::__construct(new SimpleLexer($regexp,
            array(self::T_UNKNOWN => 'T_UNKNOWN', self::T_PROPERTY => 'T_PROPERTY', self::T_OPERATOR => 'T_OPERATOR',
                  self::T_VALUE => 'T_VALUE', self::T_OPERATOR_JOIN => 'T_OPERATOR_JOIN'),
            function($value) {
                foreach (FilterParser::$regExps as $id => $regExp) {
                    if (preg_match('/^'.$regExp.'$/ix', $value)) {
                        if (in_array($id,
                            array(FilterParser::T_PROPERTY, FilterParser::T_OPERATOR_JOIN, FilterParser::T_OPERATOR))) {
                            return array($id, strtolower($value));
                        }
                        return array($id, $value);
                    }
                }
                return array(FilterParser::T_UNKNOWN, 0);
            }
        ));
    }

    protected function parseInternal() {
        $result = array('and'=>array(), 'or'=>array());
        $firstCondition = $this->parseCondition();
        if (!$this->lexer->isNext(self::T_OPERATOR_JOIN)) {
            $result['and'][] = $firstCondition;
        }
        while ($this->lexer->isNext(self::T_OPERATOR_JOIN)) {
            $join = $this->match(self::T_OPERATOR_JOIN);
            if ($join == 'or') {
                $result[$join] = $this->parseInternal();
            } else {
                $condition = $this->parseCondition();
                $result[$join][] = $condition;
            }
            if ($firstCondition) {
                array_unshift($result[$join], $firstCondition);
                $firstCondition = null;
            }
        }
        return $result;
    }

    protected function parseCondition() {
        $property = $this->match(self::T_PROPERTY);
        $operator = $this->match(self::T_OPERATOR);
        $value = $this->match(self::T_VALUE);
        $condition = array($property, $operator, $value);
        return $condition;
    }
}
