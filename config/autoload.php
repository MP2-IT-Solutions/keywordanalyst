<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package Keywordanalyst
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */


/**
 * Register the namespaces
 */
ClassLoader::addNamespaces(array
(
	'keywordanalyst',
));


/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
	// Modules
	'keywordanalyst\ModuleKeywordAnalyst' => 'system/modules/keywordanalyst/modules/ModuleKeywordAnalyst.php',

	// Classes
	'keywordanalyst\keywordAnalyst'       => 'system/modules/keywordanalyst/classes/keywordAnalyst.php',
));


/**
 * Register the templates
 */
TemplateLoader::addFiles(array
(
	'mod_keywordanalyst' => 'system/modules/keywordanalyst/templates',
));
