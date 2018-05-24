<?php
/**
 * @package    API_Plugins
 * @copyright  Copyright (C) 2009-2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license    GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link       http://www.techjoomla.com
 */

defined('_JEXEC') or die('Restricted access');
jimport('joomla.user.user');
jimport('simpleschema.easyblog.category');
jimport('simpleschema.easyblog.person');
jimport('simpleschema.easyblog.blog.post');

require_once JPATH_ADMINISTRATOR . '/components/com_easyblog/includes/date/date.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easyblog/includes/string/string.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easyblog/includes/adsense/adsense.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easyblog/includes/formatter/formatter.php';

/** Class EasyblogApiResourceLatest
 *
 * @since  1.8.8
 */
class EasyblogApiResourceLatest extends ApiResource
{
	/** Constructor
	 *
	 * @param   STRING  $ubject    error message
	 * @param   INT     $config    error code
	 *
	 */
	public function __construct(&$ubject, $config = array())
	{
		parent::__construct($ubject, $config = array());
	}

	/** Get call
	 *
	 * @return	mixed
	 */
	public function get()
	{
		$input = JFactory::getApplication()->input;
		$model = EasyBlogHelper::getModel('Blog');

		// $id = $input->get('id', null, 'INT');
		$id = 0;
		$search = $input->get('search', '', 'STRING');
		$featured = $input->get('featured', 0, 'INT');
		$tags = $input->get('tags', 0, 'INT');
		$user_id = $input->get('user_id', 0, 'INT');
		$limitstart = $input->get('limitstart', 0, 'INT');
		$limit = $input->get('limit', 10, 'INT');
		$posts = array();

		// If we have an id try to fetch the user
		$blog = EasyBlogHelper::table('Blog');
		$blog->load($id);
		$modelPT = EasyBlogHelper::getModel('PostTag');

		if ($tags)
		{
			$rows = $model->getTaggedBlogs($tags);
		}
		elseif ($featured)
		{
			if ($search)
			{
				$rows = $model->getBlogsBy('', '', 'latest', 1000, EBLOG_FILTER_PUBLISHED, $search,
				true, array(), false, false, true, '', '', null, '', false);
			}
			else
			{
				$rows = $this->getFeatureBlog();
			}
		}
		elseif ($user_id)
		{
			$blogs = EasyBlogHelper::getModel('Blog');
			$rows = $blogs->getBlogsBy('blogger', $user_id, 'latest');
		}
		else
		{
			$rows = $model->getBlogsBy('', '', 'latest', 1000, EBLOG_FILTER_PUBLISHED, $search,
			true, array(), false, false, true, '', '', null, '', false);
			$temp = array();

			foreach ($rows as $row)
			{
				// $temp[] = $model->isFeatured($row->id);
				if ($model->isFeatured($row->id) != 1)
				{
					$temp[] = $row;
				}
			}

			$rows = $temp;

			// $rows = EB::formatter('list', $rows, false);
		}

		$rows = EB::formatter('list', $rows, false);

		// Data mapping
		foreach ($rows as $k => $v)
		{
			$scm_obj = new EasyBlogSimpleSchema_plg;
			$item = $scm_obj->mapPost($v, '', 100, array('text'));
			$item->tags = $modelPT->getBlogTags($item->postid);
			$item->isowner = ($v->created_by == $this->plugin->get('user')->id ) ? true : false;

			if ($v->blogpassword != '')
			{
				$item->ispassword = true;
			}
			else
			{
				$item->ispassword = false;
			}

			$item->blogpassword = $v->blogpassword;
			$model = EasyBlogHelper::getModel('Ratings');
			$ratingValue = $model->getRatingValues($item->postid, 'entry');
			$item->rate = $ratingValue;
			$item->isVoted = $model->hasVoted($item->postid, 'entry', $this->plugin->get('user')->id);

			if ($item->rate->ratings == 0)
			{
				$item->rate->ratings = -2;
			}

			if ($featured)
			{
				$item->featured = true;
			}
			else
			{
				$item->featured = false;
			}

			$posts[] = $item;
		}

		$res = new stdClass;
		$res->result = array_slice($posts, $limitstart, $limit);

		// $res->result = $posts
		$this->plugin->setResponse($res);
	}

	/** Get featured blog
	 *
	 * @return	mixed
	 */
	public function getFeatureBlog()
	{
		$app = JFactory::getApplication();
		$limit = $app->input->get('limit', 10, 'INT');
		$blogss = new EasyBlogModelBlog;
		$blogss->setState('limit', $limit);

		return $blogss->getFeaturedBlog(array(), $limit);
	}

	/** Get name
	 *
	 * @return	mixed
	 */
	public static function getName()
	{

	}

	/** Get describe
	 *
	 * @return	mixed
	 */
	public static function describe()
	{

	}
}
