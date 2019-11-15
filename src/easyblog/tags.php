<?php
/**
 * @package API_Plugins
 * @copyright Copyright (C) 2009-2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link http://www.techjoomla.com
 */
defined('_JEXEC') or die('Restricted access');
jimport('joomla.user.user');
jimport('simpleschema.easyblog.category');
jimport('simpleschema.easyblog.person');
jimport('simpleschema.easyblog.blog.post');

/** Class  EasyblogApiResourceTags
 *
 * @since  1.8.8
 */
class EasyblogApiResourceTags extends ApiResource
{
	/** Get Call
	 *
	 * @return	ApiPlugin response object
	 */
	public function get()
	{
		$this->plugin->setResponse($this->getTags());
	}

	/** Post Call
	 *
	 * @return	mixed
	 */
	public function post()
	{
		$this->plugin->setResponse($this->searchTag());
	}

	/** Get Call
	 *
	 * @return	ApiPlugin response object
	 */
	public function getTags()
	{
		$app = JFactory::getApplication();
		$limitstart = $app->input->get('limitstart', 0, 'INT');
		$limit = $app->input->get('limit', 20, 'INT');
		$input = JFactory::getApplication()->input;
		$keyword = $input->get('title', '', 'STRING');

		// Check EB config for this
		$wordSearch = true;

		$res = new stdClass;
		$allTags = new stdClass;
		$Tagmodel = EasyBlogHelper::getModel('Tags');

		// $allTags = $Tagmodel->getTagCloud();
		$res->count = $Tagmodel->getTotalTags();

		if (!empty($keyword))
		{
			$allTags->result[] = $Tagmodel->search($keyword, $wordSearch);
		}
		else
		{
			$allTags->result[] = $Tagmodel->getTagCloud('', $order = 'title', $sort = 'asc', $checkAccess = false);
		}

		$allTags->result = array_slice($allTags, $limitstart, $limit);

		return $allTags;
	}

	/** Search tags
	 *
	 * @return	mixed
	 */
	public function searchTag()
	{
		$app = JFactory::getApplication();
		$limitstart = $app->input->get('limitstart',0,'INT');
		$limit =  $app->input->get('limit',20,'INT');
		$input = JFactory::getApplication()->input;
		$keyword = $input->get('title','', 'STRING');
		$db = EB::db();
		$query = array();
		$search = $keyword . '%';
		$query[] = 'SELECT * FROM ' . $db->quoteName('#__easyblog_tag');
		$query[] = 'WHERE ' . $db->quoteName('title') . ' LIKE ' . $db->Quote($search);
		$query[] = 'AND ' . $db->quoteName('published') . '=' . $db->Quote(1);
		$query = implode(' ', $query);
		$db->setQuery($query);
		$result = $db->loadObjectList();
		$output = array_slice($result, $limitstart, $limit);

		return $output;
	}
}
