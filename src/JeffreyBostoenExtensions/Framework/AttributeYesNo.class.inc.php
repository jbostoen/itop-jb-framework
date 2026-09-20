<?php

/**
 * @copyright   Copyright (c) 2019-2026 Jeffrey Bostoen
 * @license     https://www.gnu.org/licenses/gpl-3.0.en.html
 * @version     3.2.260920
 *
 * Definition of AttributeYesNo
 */

/**
 * Thin wrapper around AttributeEnum, restricted to exactly two possible values.
 *
 * Unless overridden through the 'allowed_values' / 'default_value' parameters,
 * the stored values default to 'yes' / 'no'.
 *
 * Value labels are resolved the same way as for AttributeEnum, but an extra
 * fallback tier is inserted before the raw-value fallback: a generic,
 * type-level dictionary entry such as 'Core:AttributeYesNo/Value:yes' => 'Yes',
 * following the same 'Core:<class>/Value:<value>' convention used by core's
 * own AttributeBoolean. This avoids having to repeat a
 * 'Class:X/Attribute:Y/Value:yes' => 'Yes' dictionary entry for every single
 * attribute using yes/no-like values.
 */
class AttributeYesNo extends AttributeEnum {

	const DEFAULT_TRUE_VALUE = 'yes';
	const DEFAULT_FALSE_VALUE = 'no';

	/**
	 * @param string $sCode
	 * @param array $aParams
	 *
	 * @throws \CoreException
	 */
	public function __construct($sCode, $aParams) {

		$aParams += array(
			'allowed_values' => new ValueSetEnum(static::DEFAULT_TRUE_VALUE.','.static::DEFAULT_FALSE_VALUE),
			'default_value' => static::DEFAULT_FALSE_VALUE,
			'is_null_allowed' => false,
			'depends_on' => array(),
		);

		$aValues = array_keys($aParams['allowed_values']->GetValues(array(), ''));
		if(count($aValues) !== 2) {
			throw new CoreException("AttributeYesNo '$sCode': exactly two allowed values are expected, ".count($aValues)." given.");
		}

		parent::__construct($sCode, $aParams);

	}

	/**
	 * @inheritDoc
	 *
	 * Inserts a generic, type-level dictionary fallback (e.g. 'Core:AttributeYesNo/Value:yes')
	 * between the class/attribute-specific lookup and the raw-value fallback.
	 *
	 * @param string|null $sValue
	 *
	 * @return string
	 */
	public function GetValueLabel($sValue) {

		if(is_null($sValue)) {
			return parent::GetValueLabel($sValue);
		}

		// - Class/attribute specific override, current user language only.
		$sLabel = $this->SearchLabel('/Attribute:'.$this->GetCode().'/Value:'.$sValue, null, true);
		if(!is_null($sLabel)) {
			return $sLabel;
		}

		// - Generic, type-level fallback, before falling back to the raw value.
		$sTypeLabel = Dict::S('Core:'.get_class($this).'/Value:'.$sValue, '', false);
		$sDefault = (strlen($sTypeLabel) > 0) ? $sTypeLabel : str_replace('_', ' ', $sValue);

		// - Browse the class hierarchy again, accepting default (english) translations.
		return $this->SearchLabel('/Attribute:'.$this->GetCode().'/Value:'.$sValue, $sDefault, false);

	}

	/**
	 * @inheritDoc
	 *
	 * Inserts a generic, type-level dictionary fallback (e.g. 'Core:AttributeYesNo/Value:yes+')
	 * after the class/attribute-specific lookup, when nothing more specific is found.
	 *
	 * @param string|null $sValue
	 *
	 * @return string
	 */
	public function GetValueDescription($sValue) {

		$sDescription = parent::GetValueDescription($sValue);
		if(strlen($sDescription) === 0 && !is_null($sValue)) {
			$sDescription = Dict::S('Core:'.get_class($this).'/Value:'.$sValue.'+', '', false);
		}

		return $sDescription;

	}

}
