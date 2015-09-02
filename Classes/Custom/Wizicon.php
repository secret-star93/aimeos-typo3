<?php

/**
 * @license GPLv3, http://www.gnu.org/copyleft/gpl.html
 * @copyright Metaways Infosystems GmbH, 2013
 * @copyright Aimeos (aimeos.org), 2014
 * @package TYPO3_Aimeos
 */


namespace Aimeos\Aimeos\Custom;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Localization\LocalizationFactory;


/**
 * Class that adds the wizard icon.
 *
 * @package TYPO3_Aimeos
 */
class Wizicon
{
	/**
	 * Adds the wizard icon
	 *
	 * @param array Input array with wizard items for plugins
	 * @return array Modified input array, having the item for Aimeos added.
	 */
	public function proc( $wizardItems )
	{
		$path = ExtensionManagementUtility::extPath( 'aimeos' );
		$relpath = ExtensionManagementUtility::extRelPath( 'aimeos' );
		$langfile = $path . 'Resources/Private/Language/extension.xlf';

		$languageFactory = GeneralUtility::makeInstance( LocalizationFactory::class );
		$xml = $languageFactory->getParsedData( $langfile, $GLOBALS['LANG']->lang, '', 0 );

		$wizardItems['plugins_tx_aimeos'] = array(
			'icon' => $relpath . 'Resources/Public/Images/aimeos-wizicon.png',
			'title' => $GLOBALS['LANG']->getLLL( 'ext-wizard-title', $xml ),
			'description' => $GLOBALS['LANG']->getLLL( 'ext-wizard-description', $xml ),
			'params' => '&defVals[tt_content][CType]=list'
		);

		return $wizardItems;
	}
}


if( defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/aimeos/Classes/Custom/Wizicon.php'] ) {
	include_once( $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/aimeos/Classes/Custom/Wizicon.php'] );
}
