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

/** Class EasyblogApiResourceRating
 *
 * @since  1.8.8
 */
class EasyblogApiResourceRating extends ApiResource
{
	/** Post Call
	 *
	 * @return	mixed
	 */
	public function post()
	{
		$this->plugin->setResponse($this->setRatings());
	}

	/** Get Call
	 *
	 * @return	mixed
	 */
	public function setRatings()
	{
		 $input = JFactory::getApplication()->input;
		 $user_id = $input->get('uid', 0, 'INT');
		 $blog_id = $input->get('blogid', 0, 'INT');
		 $values = $input->get('values', 0, 'INT');
		 $model = EasyBlogHelper::table('Ratings');
		 $model->uid = $blog_id;
		 $model->created_by = $user_id;
		 $model->value = $values;
		 $model->type = 'entry';
		 $ratingValue = $model->store();

		 return $ratingValue;
	}
}
