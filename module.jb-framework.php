<?php

/**
 * @copyright   Copyright (c) 2019-2026 Jeffrey Bostoen
 * @license     https://www.gnu.org/licenses/gpl-3.0.en.html
 * @version     3.2.260912
 *
 * iTop module definition file
 */

SetupWebPage::AddModule(
        __FILE__, // Path to the current file, all other file names are relative to the directory containing this file
        'jb-framework/3.2.260912',
        array(
                // Identification
                //
                'label' => 'Framework (for extensions by Jeffrey Bostoen)',
                'category' => 'tools',

                // Setup
                //
                'dependencies' => array(
                        'itop-structure/>=3.2.0 && itop-structure/<3.4.0',
                ),
                'mandatory' => true,
                'visible' => false,

                // Components
                //
                'datamodel' => array(
					'src/JeffreyBostoenExtensions/Framework/CMDBChangeHelper.class.inc.php',
					'src/JeffreyBostoenExtensions/Framework/ormCustomCaseLog.class.inc.php',
                ),
                'webservice' => array(

                ),
                'data.struct' => array(
					// add your 'structure' definition XML files here,
                ),
                'data.sample' => array(
					// add your sample data XML files here,
                ),

                // Documentation
                //
                'doc.manual_setup' => '', // hyperlink to manual setup documentation, if any
                'doc.more_information' => '', // hyperlink to more information, if any

                // Default settings
                //
                'settings' => array(
                        // Module specific settings go here, if any
                ),
        )
);

