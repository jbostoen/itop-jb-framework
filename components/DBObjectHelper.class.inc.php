<?php

/**
 * @copyright   Copyright (C) 2019-2020 Jeffrey Bostoen
 * @license     https://www.gnu.org/licenses/gpl-3.0.en.html
 * @version     2020-11-02 19:47:29
 *
 * Definition of DBObjectHelper
 *
 */

namespace jb_itop_extensions\components;

use \DBObject;
use \MetaModel;

/**
 * Class DBObjectHelper
 *
 * @details Used by geometry extension
 */
abstract class DBObjectHelper {

	/**
	 * Gets an array containing the object values (either all or based on a given list).
	 * Automatically adds 'id'.
	 *
	 * @param \DBObject $oObject iTop object
	 * @param \String[]|null $aListOfAttributes List of attributes (filter)
	 *
	 * @return \Array Hash table of object.
	 */
	public function GetValuesAsArray(DBObject $oObject, ?Array $aListOfAttributes = null) {
		
		$aAttributeList = ($aListOfAttributes === null ? Metamodel::GetAttributesList(get_class($oObject)) : $aListOfAttributes);
		
		$aAttributeList[] = 'id';
		
		$aAttributeValues = [];
		foreach($aAttributeList as $sAttCode) {
			$aAttributeValues[$sAttCode] = $oObject->Get($sAttCode);
		}
		
		return $aAttributeValues;
		
	}
	
}
