<?php

namespace Eyja\RestBundle\QueryParams;

use JMS\Parser\SyntaxErrorException;

/**
 * Parse oData-like filter string
 *
 * @see http://www.odata.org/documentation/uri-conventions#45_Filter_System_Query_Option_filter
 */
class OrderParser {
	public function parse($string) {
		$result = preg_match_all('#(?:(?:\s*,\s*)?(?<field>[a-z]+)\s+(?<direction>asc|desc))+?#si', $string, $matches);
		if (!$result) {
			throw new SyntaxErrorException('Error matching order value');
		}
		$results = array();
		for ($i=0, $n=count($matches[0]); $i<$n; $i++) {
			$results[] = array('field' => $matches['field'][$i], 'direction' => $matches['direction'][$i]);
		}
		return $results;
	}
}
