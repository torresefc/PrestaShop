<?php
/*
* 2007-2011 PrestaShop 
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2011 PrestaShop SA
*  @version  Release: $Revision: 6844 $
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class ProfileCore extends ObjectModel
{
 	/** @var string Name */
	public 		$name;

	/**
	 * @see ObjectModel::$definition
	 */
	public static $definition = array(
		'table' => 'profile',
		'primary' => 'id_profile',
		'multilang' => true,
		'fields' => array(
			// Lang fields
			'name' => array('type' => 'FILL_ME', 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 32),
		),
	);

	/**
	* Check then return multilingual fields for database interaction
	*
	* @return array Multilingual fields
	*/
	public function getTranslationsFieldsChild()
	{
		$this->validateFieldsLang();
		return $this->getTranslationsFields(array('name'));
	}
	
	/**
	* Get all available profiles
	*
	* @return array Profiles
	*/
	public static function getProfiles($id_lang)
	{
		return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
		SELECT p.`id_profile`, `name`
		FROM `'._DB_PREFIX_.'profile` p
		LEFT JOIN `'._DB_PREFIX_.'profile_lang` pl ON (p.`id_profile` = pl.`id_profile` AND `id_lang` = '.(int)($id_lang).')
		ORDER BY `id_profile` ASC');
	}

	/**
	* Get the current profile name
	*
	* @return string Profile
	*/
	public static function getProfile($id_profile, $id_lang = NULL)
	{
		if ($id_lang == NULL)
			$id_lang = Configuration::get('PS_LANG_DEFAULT');
		return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
		SELECT `name` 
		FROM `'._DB_PREFIX_.'profile` p 
		LEFT JOIN `'._DB_PREFIX_.'profile_lang` pl ON (p.`id_profile` = pl.`id_profile`) 
		WHERE p.`id_profile` = '.(int)$id_profile.'
		AND pl.`id_lang` = '.(int)$id_lang);
	}

	
	public function add($autodate = true, $nullValues = false)
	{
	 	if (parent::add($autodate, true))
			return (
				Db::getInstance()->execute('INSERT INTO '._DB_PREFIX_.'access (SELECT '.(int)$this->id.', id_tab, 0, 0, 0, 0 FROM '._DB_PREFIX_.'tab)')
				&& Db::getInstance()->execute('INSERT INTO '._DB_PREFIX_.'module_access (`id_profile`, `id_module`, `configure`, `view`) (SELECT '.(int)$this->id.', id_module, 0, 1 FROM '._DB_PREFIX_.'module)')
			);
		return false;
	}
	
	public function delete()
	{
	 	if (parent::delete())
	 	 	return (
				Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'access` WHERE `id_profile` = '.(int)$this->id)
				&& Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'module_access` WHERE `id_profile` = '.(int)$this->id)
			);
		return false;
	}

	public static function getProfileAccess($id_profile, $id_tab)
	{
		// getProfileAccesses is cached so there is no performance leak
		$accesses = self::getProfileAccesses($id_profile);
		return (isset($accesses[$id_tab]) ? $accesses[$id_tab] : false);
	}

	private static $_cache_accesses = array();
	public static function getProfileAccesses($id_profile)
	{
		if (!isset(self::$_cache_accesses[$id_profile]))
		{
			$result = Db::getInstance()->executeS('
			SELECT *
			FROM `'._DB_PREFIX_.'access`
			WHERE `id_profile` = '.(int)$id_profile);

			self::$_cache_accesses[$id_profile] = array();
			foreach ($result AS $row)
			{
				if (!isset(self::$_cache_accesses[$id_profile][$row['id_tab']]))
					self::$_cache_accesses[$id_profile][$row['id_tab']] = array();
				self::$_cache_accesses[$id_profile][$row['id_tab']] = $row;
			}
		}
		return self::$_cache_accesses[$id_profile];
	}
}


