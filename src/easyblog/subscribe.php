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

require_once JPATH_ADMINISTRATOR . '/components/com_easyblog/models/subscription.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easyblog/models/blog.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easyblog/models/category.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easyblog/models/blogger.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easyblog/models/subscriptions.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easyblog/models/categories.php';

/** Class EasyblogApiResourceSubscribe
 *
 * @since  1.8.8
 */
class EasyblogApiResourceSubscribe extends ApiResource
{
	/** Get Call
	 *
	 * @return	mixed
	 */
	public function get()
	{
		$this->plugin->setResponse($this->getSubscribers());
	}

	/** Post Call
	 *
	 * @return	mixed
	 */
	public function post()
	{
		$this->plugin->setResponse($this->addSubscription());
	}

	/** Get subscribers
	 *
	 * @return	mixed
	 */
	public function getSubscribers()
	{
		$app = JFactory::getApplication();
		$type = $app->input->get('type', '', 'STRING');

		switch ($type)
		{
			case 'site': $res1 = $this->getSitesubscribers();

						 return $res1;
			break;
			case 'blog': $res2 = $this->getBlogsubscribers();

						 return $res2;
			break;
			case 'cat': $res3 = $this->getCatsubscribes();

						 return $res3;
			break;
		}
	}

	/** Get subscribers
	 *
	 * @return	mixed
	 */
	public function getSitesubscribers()
	{
		$ssmodel = new EasyBlogModelSubscriptions;
		$result['count'] = $ssmodel->getTotal();
		$smodel = new EasyBlogModelSubscription;
		$result['data'] = $smodel->getSiteSubscribers();

		return $result;
	}

	/** Get subscribers
	 *
	 * @return	mixed
	 */
	public function getBlogsubscribers()
	{
		$app = JFactory::getApplication();
		$blogid = $app->input->get('blogid', 0, 'INT');
		$db = EasyBlogHelper::db();
		$where = array();

		// Making query for getting count of blog subscription.
		$query = 'select count(1) from `#__easyblog_post_subscription` as a';
		$query .= ' where  a.post_id = ' . $db->Quote($blogid);
		$db->setQuery($query);
		$val = $db->loadResult();
		$result['count'] = $val;
		$btable = EasyBlogHelper::table('Blog');

		// Try to save blog id in table
		$btable->load($blogid);
		$result['data'] = $btable->getSubscribers(array());

		return $result;
	}

	/** Get category subscribers
	 *
	 * @return	mixed
	 */
	public function getCatsubscribes()
	{
		$app = JFactory::getApplication();
		$catid = $app->input->get('catid', 0, 'INT');
		$db = EasyBlogHelper::db();
		$where = array();

		// Making query for getting count of category subscription.
		$query = 'select count(1) from `#__easyblog_category_subscription` as a';
		$query .= ' where  a.category_id = ' . $db->Quote($catid);
		$db->setQuery($query);
		$val = $db->loadResult();
		$result['count'] = $val;
		$cmodel = new EasyBlogModelCategory;
		$result['data'] = $cmodel->getCategorySubscribers($catid);

		return $result;
	}

	/** Get subscription
	 *
	 * @return	mixed
	 */
	public function addSubscription()
	{
		$app = JFactory::getApplication();
		$type = $app->input->get('type', '', 'STRING');

		switch ($type)
		{
			case 'site': $res1 = $this->addToSitesubscribe();

						 return $res1;
			break;
			case 'blog': $res2 = $this->addToBlogsubscribe();

						 return $res2;
			break;
			case 'cat': $res3 = $this->addToCategorysubscribe();

						 return $res3;
			break;
			case 'author': $res4 = $this->addToAuthorsubscribe();

						 return $res4;
			break;
		}
	}

	/** Get subscribers
	 *
	 * @return	mixed
	 */
	public function addToSitesubscribe()
	{
		$app = JFactory::getApplication();
		$email = $app->input->get('email', '', 'STRING');
		$userid = $app->input->get('userid', '', 'STRING');
		$name = $app->input->get('name', '', 'STRING');
		$smodel = new EasyBlogModelSubscription;
		$status = $smodel->isSiteSubscribedEmail($email);

		if (!$status)
		{
			$result = $smodel->addSiteSubscription($email, $userid, $name);
		}
		else
		{
			return false;
		}

		return $result;
	}

	/** Get subscribers
	 *
	 * @return	mixed
	 */
	public function addToBlogsubscribe()
	{
		$app = JFactory::getApplication();
		$email = $app->input->get('email', '', 'STRING');
		$userid = $app->input->get('userid', '', 'STRING');
		$name = $app->input->get('name', '', 'STRING');
		$blogid = $app->input->get('blogid', 0, 'INT');
		$usr = FD::user($userid);
		$res = new stdClass;
		$bmodel = new EasyBlogModelBlog;
		$status = $bmodel->isBlogSubscribedUser($blogid, $userid, $email);

		if (!$status)
		{
			$result = $bmodel->addBlogSubscription($blogid, $email, $userid, $usr->name);
			$res->status = 1;
			$res->message = JText::_('PLG_API_EASYBLOG_SUBSCRIPTION_SUCCESS');
		}
		else
		{
			$res->status = 0;
			$res->message = JText::_('PLG_API_EASYBLOG_ALREADY_SUBSCRIBED');

			return $res;
		}

		return $res;
	}

	/** Get category subscribers
	 *
	 * @return	mixed
	 */
	public function addToCategorysubscribe()
	{
		$app = JFactory::getApplication();
		$email = $app->input->get('email', '', 'STRING');
		$userid = $app->input->get('userid', '', 'STRING');
		$name   = $app->input->get('name', '', 'STRING');
		$catid  = $app->input->get('catid', 0, 'INT');
		$cmodel = new EasyBlogModelCategory;
		$status = $cmodel->isCategorySubscribedUser($catid, $userid, $email);

		if (!$status)
		{
			$result = $cmodel->addCategorySubscription($catid, $email, $userid, $name);
		}
		else
		{
			return false;
		}

		return $result;
	}

	/** Get authors subscribers
	 *
	 * @return	mixed
	 */
	public function addToAuthorsubscribe()
	{
		$app = JFactory::getApplication();
		$email = $app->input->get('email', '', 'STRING');
		$userid = $app->input->get('userid', '', 'STRING');
		$name = $app->input->get('name', '', 'STRING');
		$bloggerid = $app->input->get('bloggerid', 0, 'INT');
		$bmodel = new EasyBlogModelBlogger;
		$status = $bmodel->isBloggerSubscribedUser($bloggerid, $userid, $email);

		if (!$status)
		{
			$result->result = $bmodel->addBloggerSubscription($bloggerid, $email, $userid, $name);
		}
		else
		{
			return false;
		}

		return $result;
	}
}
