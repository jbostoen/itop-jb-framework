<?php

/**
 * @copyright   Copyright (c) 2019-2026 Jeffrey Bostoen
 * @license     https://www.gnu.org/licenses/gpl-3.0.en.html
 * @version     2.7.260917
 *
 * Definition of HelpContainer
 */

namespace jb_itop_extensions\components;

// iTop internals.
use AttributeEnum;
use AttributeText;
use Combodo\iTop\Application\TwigBase\Twig\TwigHelper;
use Combodo\iTop\Application\WebPage\WebPage;
use DBObject;
use Dict;
use MetaModel;
use utils;


/**
 * Enum eContainerPosition. Defines the possible positions for a help container.
 */
enum eContainerPosition: string {
	case Before = 'before';
	case After = 'after';
}


/**
 * Class HelpContainer. Groups methods to add a "help container" (dictionary help text) to the iTop UI.
 */
abstract class HelpContainer {

	/**
	 * Adds a help container to an iTop page, rendered from a Twig template belonging to the calling module.
	 *
	 * @param WebPage $oPage
	 * @param string $sModuleCode Module code owning the "templates" directory the template lives in. Defaults to jb-framework's own templates.
	 * @param string $sTemplate Template file name, relative to the module's "templates" directory.
	 * @param string $sSelector The selector of the HTML element the help container should be positioned relative to.
	 * @param eContainerPosition $ePosition
	 * @param array $aData The data to be passed to the Twig template.
	 *
	 * @return void
	 */
	public static function Add(WebPage $oPage, string $sModuleCode, string $sTemplate, string $sSelector, eContainerPosition $ePosition = eContainerPosition::After, array $aData = []): void {

		$sPath = MODULESROOT.$sModuleCode.'/templates';
		$oTwig = TwigHelper::GetTwigEnvironment($sPath);

		$sRenderedText = $oTwig->render($sTemplate, $aData);
		$sPosition = $ePosition->value;

		// - Rendered HTML is passed as a JSON-encoded string, not interpolated into a JS template literal,
		//   so it cannot break out of (or inject into) the generated JS if it contains a backtick or `${...}`.
		$sJsonRenderedText = json_encode($sRenderedText);

		$oPage->add_ready_script(<<<JS
				$(`$sSelector`).$sPosition($sJsonRenderedText);
			JS
		);

	}

	/**
	 * Adds a help container for a specific attribute of an object to an iTop page.
	 * Uses jb-framework's own default templates and stylesheet.
	 *
	 * @param WebPage $oPage
	 * @param DBObject $oObj
	 * @param string $sAttCode
	 *
	 * @return void
	 */
	public static function AddForAttCode(WebPage $oPage, DBObject $oObj, string $sAttCode): void {

		$oPage->add_saas('env-'.utils::GetCurrentEnvironment().'/jb-framework/assets/css/help-container.scss');

		$oAttDef = MetaModel::GetAttributeDef($oObj::class, $sAttCode);
		$sAttOriginClass = MetaModel::GetAttributeOrigin($oObj::class, $sAttCode);

		$sObjAttContainer = sprintf('[data-object-class="%1$s"][data-object-id="%2$s"] [data-attribute-code="%3$s"]',
			$oObj::class,
			$oObj->GetKey(),
			$sAttCode
		);

		// - Positioning.

			switch(true) {

				// - To be extended.

					// - Covers AttributeText, AttributeHTML, AttributeOQL, ...
					//   These attributes have a label, and the input below them.
					//   The help container should be between the label & input.
					case is_a($oAttDef, AttributeText::class):
						$sSelector = sprintf('%1$s .ibo-field--label', $sObjAttContainer);
						break;

					// - Covers other elements.
					//   The help container should be after the input.
					//   E.g. AttributeExternalKey.
					default:
						$sSelector = sprintf('%1$s .attribute-edit', $sObjAttContainer);

			}

		// - Template.

			$aData = [
				'sAttLabel' => $oAttDef->GetLabel(),
				'sHelpText' => Dict::S('Class:'.$sAttOriginClass.'/Attribute:'.$sAttCode.'+'),
			];

			switch(true) {

				// - To be extended.

					case is_a($oAttDef, AttributeEnum::class):
						$sTemplate = 'HelpContainer_AttributeEnum.html';
						$aData['aValues'] = array_map(function($sValue) use ($sAttCode, $sAttOriginClass) {
							return [
								'sValue' => $sValue,
								'sValueLabel' => Dict::S('Class:'.$sAttOriginClass.'/Attribute:'.$sAttCode.'/Value:'.$sValue),
								'sValueLabelExtra' => Dict::S('Class:'.$sAttOriginClass.'/Attribute:'.$sAttCode.'/Value:'.$sValue.'+'),
							];
						}, array_keys($oAttDef->GetAllowedValues()));
						break;

					default:
						$sTemplate = 'HelpContainer.html';

			}

		static::Add($oPage, 'jb-framework', $sTemplate, $sSelector, eContainerPosition::After, $aData);

	}

}
