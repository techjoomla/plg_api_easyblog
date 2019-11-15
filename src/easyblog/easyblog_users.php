<?php
/**
 * @package    API_Plugins
 * @copyright  Copyright (C) 2009-2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license    GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link       http://www.techjoomla.com
 */

defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');
jimport('joomla.html.html');
jimport('joomla.application.component.controller');
jimport('joomla.application.component.model');
jimport('joomla.user.helper');
jimport('joomla.user.user');
jimport('joomla.application.component.helper');
jimport('simpleschema.easyblog.blog.post');
jimport('simpleschema.easyblog.category');
jimport('simpleschema.easyblog.person');

JModelLegacy::addIncludePath(JPATH_SITE . 'components/com_api/models');
require_once JPATH_ADMINISTRATOR . '/components/com_easyblog/models/users.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easyblog/models/blogger.php';

/** Class EasyblogApiResourceEasyblog_users
 *
 * @since  1.8.8
 */
class EasyblogApiResourceEasyblog_users extends ApiResource
{
	/** Get Call
	 *
	 * @return	mixed
	 */
	public function get()
	{
		$this->plugin->setResponse($this->getEasyBlogUser());
	}

	/** Post Call
	 *
	 * @return	mixed
	 */
	public function post()
	{
		$this->plugin->setResponse();
	}

	/** Get easyblog user
	 *
	 * @return	mixed
	 */
	public function getEasyBlogUser()
	{
		$app = JFactory::getApplication();
		$limitstart = $app->input->get('limitstart', 0, 'INT');
		$limit = $app->input->get('limit', 0, 'INT');
		$search = $app->input->get('search', '', 'STRING');
		$userid = $app->input->get('userid', '', 'INT');
		$user = JFactory::getUser($this->plugin->get('user')->id);
		$ob1 = new EasyBlogModelBlogger;
		$ob1->setState('limitstart', $limitstart);
		$bloggers = new stdClass;
		$bloggers->result = $ob1->getBloggers('latest', $limit, $filter = 'showbloggerwithpost', $search);
		$blogger = EasyBlogHelper::table('Profile');

		foreach ($bloggers->result as $usr)
		{
			$blogger->load($usr->id);
			$usr->avatar = $blogger->getAvatar();
			$usr->status = $ob1->isBloggerSubscribedUser($usr->id, $userid);
			$usr->email = $user->email;

			if ($usr->status)
			{
				$usr->isFollowed = true;
			}
			else
			{
				$usr->isFollowed = false;
			}
		}

		return $bloggers;
	}
}
