<?php

/**
 * @copyright   Copyright (c) 2019-2026 Jeffrey Bostoen
 * @license     https://www.gnu.org/licenses/gpl-3.0.en.html
 * @version     3.2.261001
 *
 * Definition of HelpContainer
 */

namespace JeffreyBostoenExtensions\Framework;

// iTop internals.
use AttributeEnum;
use AttributeText;
use Combodo\iTop\Application\TwigBase\Twig\TwigHelper;
use Combodo\iTop\Application\WebPage\WebPage;
use DBObject;
use Dict;
use InvalidArgumentException;
use MetaModel;
use utils;

if(!class_exists('JeffreyBostoenExtensions\Framework\HelpContainer')) {

	/**
	 * Enum eContainerPosition. Defines the possible positions for a help container.
	 */
	enum eContainerPosition: string {
		case Before = 'before';
		case After = 'after';
	}


	/**
	 * Enum eButtonType. Defines the possible types (color schemes) of an action link in a help container.
	 * The values match the states (color schemes) of the iTop core buttons.
	 */
	enum eButtonType: string {
		case Neutral = 'neutral';
		case Primary = 'primary';
		case Secondary = 'secondary';
		case Success = 'success';
		case Danger = 'danger';
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

		/**
		 * Adds a help container that spans the full width of the object details (all columns),
		 * positioned before (or after) the row of columns that contains the given attribute.
		 * Falls back to positioning it relative to the attribute itself when the attribute is not part of a row of columns.
		 *
		 * @param WebPage $oPage
		 * @param DBObject $oObj
		 * @param string $sAttCode The attribute code that determines the position.
		 * @param string $sHelpText The help text (plain text; it is escaped, line breaks are kept).
		 * @param array $aLinks Optional action links. Each link is an array with the keys:
		 *  - 'label' (string, required).
		 *  - 'url' (string, required; http or https only).
		 *  - 'icon' (string, optional; CSS classes of an icon, e.g. 'fas fa-plus'). Defaults to an "external link" icon.
		 *  - 'type' (eButtonType or its string value, optional; the color scheme of the button). Defaults to eButtonType::Neutral.
		 * @param eContainerPosition $ePosition
		 *
		 * @return void
		 *
		 * @throws InvalidArgumentException When a link has no label, an invalid URL or an unknown type.
		 */
		public static function AddFullWidth(WebPage $oPage, DBObject $oObj, string $sAttCode, string $sHelpText, array $aLinks = [], eContainerPosition $ePosition = eContainerPosition::Before): void {

			// - Validate the links before anything is added to the page.

				$aValidLinks = [];

				foreach($aLinks as $aLink) {

					$sLabel = (string)($aLink['label'] ?? '');
					$sUrl = (string)($aLink['url'] ?? '');

					if($sLabel === '') {
						throw new InvalidArgumentException('Each help container link needs a label.');
					}

					$sScheme = strtolower((string)parse_url($sUrl, PHP_URL_SCHEME));

					if(!filter_var($sUrl, FILTER_VALIDATE_URL) || !in_array($sScheme, ['http', 'https'], true)) {
						throw new InvalidArgumentException(sprintf('Invalid URL for help container link "%1$s".', $sLabel));
					}

					// - The type is either an eButtonType, or its string value.
					$mType = $aLink['type'] ?? eButtonType::Neutral;
					$eType = ($mType instanceof eButtonType ? $mType : eButtonType::tryFrom((string)$mType));

					if($eType === null) {
						throw new InvalidArgumentException(sprintf('Unknown type "%1$s" for help container link "%2$s".', (string)$mType, $sLabel));
					}

					$aValidLinks[] = [
						'sLabel' => $sLabel,
						'sUrl' => $sUrl,
						'sIcon' => (string)($aLink['icon'] ?? 'fas fa-external-link-alt'),
						'sType' => $eType->value,
					];

				}

			// - Render.

				$oPage->add_saas('env-'.utils::GetCurrentEnvironment().'/jb-framework/assets/css/help-container.scss');

				$oTwig = TwigHelper::GetTwigEnvironment(MODULESROOT.'jb-framework/templates');
				$sRenderedText = $oTwig->render('HelpContainer_FullWidth.html', [
					'sHelpText' => $sHelpText,
					'aLinks' => $aValidLinks,
				]);

			// - Position: relative to the row of columns that contains the attribute, so the container spans all columns.
			//   The selector and the rendered HTML are passed as JSON-encoded strings, so they cannot break out of the generated JS.

				$sSelector = sprintf('[data-object-class="%1$s"][data-object-id="%2$s"] [data-role="ibo-field"][data-attribute-code="%3$s"]',
					$oObj::class,
					$oObj->GetKey(),
					$sAttCode
				);

				$sJsonSelector = json_encode($sSelector);
				$sJsonRenderedText = json_encode($sRenderedText);
				$sPosition = $ePosition->value;

				$oPage->add_ready_script(<<<JS
						(function() {
							const oField = $($sJsonSelector).first();
							const oRow = oField.closest('[data-role="ibo-multi-column"]');
							(oRow.length > 0 ? oRow : oField).$sPosition($sJsonRenderedText);
						})();
					JS
				);

		}

	}

}

// - Backward compatibility alias for the pre-2.7 namespace.
if(!class_exists('jb_itop_extensions\components\HelpContainer')) {
	class_alias(HelpContainer::class, 'jb_itop_extensions\components\HelpContainer');
}
