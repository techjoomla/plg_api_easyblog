<?php
/**
 * @package    API_Plugins
 * @copyright  Copyright (C) 2009-2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license    GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link       http://www.techjoomla.com
 */

defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');

/** Class plgAPIEasyblog
 *
 * @since  1.8.8
 */
class plgAPIEasyblog extends ApiPlugin
{
	/** Construct
	 *
	 * @param   int  $subject   subject
	 * @param   int  $config    config
	 */
	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config = array());

		// Load language file for plugin frontend
		$lang = JFactory::getLanguage();
		$lang->load('plg_api_easyblog', JPATH_ADMINISTRATOR, '', true);
		$easyblog = JPATH_ROOT . '/administrator/components/com_easyblog/easyblog.php';

		if (!JFile::exists($easyblog) || !JComponentHelper::isEnabled('com_easyblog', true))
		{
			ApiError::raiseError(404, 'Easyblog not installed');

			return;
		}

		// Load helper file
		require_once JPATH_SITE . '/plugins/api/easyblog/helper/simpleschema.php';

		// Load Easyblog language & bootstrap files
		$language = JFactory::getLanguage();
		$language->load('com_easyblog');
		$xml = JFactory::getXML(JPATH_ADMINISTRATOR . '/components/com_easyblog/easyblog.xml');
		$version = (string) $xml->version;

		if ($version < 5)
		{
			require_once PATH_ROOT . '/components/com_easyblog/constants.php';
			require_once JPATH_ROOT . '/components/com_easyblog/helpers/helper.php';
			ApiResource::addIncludePath(dirname(__FILE__) . '/easyblog4');
		}
		else
		{
			ApiResource::addIncludePath(dirname(__FILE__) . '/easyblog');
			require_once JPATH_ADMINISTRATOR . '/components/com_easyblog/includes/easyblog.php';
			require_once JPATH_ADMINISTRATOR . '/components/com_easyblog/includes/constants.php';
			require_once JPATH_ADMINISTRATOR . '/components/com_easyblog/includes/date/date.php';
			require_once JPATH_ADMINISTRATOR . '/components/com_easyblog/includes/string/string.php';
			require_once JPATH_ADMINISTRATOR . '/components/com_easyblog/includes/adsense/adsense.php';
		}

		// Set resources & access
		$this->setResourceAccess('latest', 'public', 'get');
		$this->setResourceAccess('category', 'public', 'get');
		$this->setResourceAccess('tags', 'public', 'get');
		$this->setResourceAccess('blog', 'public', 'get');
		$this->setResourceAccess('blog', 'public', 'post');
		$this->setResourceAccess('comments', 'public', 'get');
		$this->setResourceAccess('easyblog_users', 'public', 'get');
		$config = EasyBlogHelper::getConfig();

		if ($config->get('main_allowguestcomment'))
		{
			$this->setResourceAccess('comments', 'public', 'post');
		}
	}
}
