<?php
/**
 * @package    API_Plugins
 * @copyright  Copyright (C) 2009-2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license    GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link       http://www.techjoomla.com
 */

defined('_JEXEC') or die('Restricted access');

jimport('joomla.user.user');
jimport('simpleschema.category');
jimport('simpleschema.person');
jimport('simpleschema.blog.post');

/** Class EasyblogApiResourceReport
 *
 * @since  1.8.8
 */
class EasyblogApiResourceReport extends ApiResource
{
	/** Get Call
	 *
	 * @return	mixed
	 */
	public function get()
	{
		$this->plugin->setResponse(JText::_('PLG_API_EASYBLOG_USE_METHOD_POST'));
	}

	/** Post Call
	 *
	 * @return	ApiPlugin response object
	 */
	public function post()
	{
		$this->plugin->setResponse($this->reportBlog());
	}

	/** Get report call
	 *
	 * @return	mixed
	 */
	public function reportBlog()
	{
		$app = JFactory::getApplication();

		// Get the composite keys
		$log_user = $this->plugin->get('user')->id;
		$id = $app->input->get('id', 0, 'int');
		$type = $app->input->get('type', '', 'POST');
		$reason = $app->input->get('reason', '', 'STRING');
		$final_result = new stdClass;

		if (!$reason)
		{
			$message = JText::_('PLG_API_EASYBLOG_REASON_EMPTY');
			$final_result->result['message'] = $message;
			$final_result->result['status'] = false;

			return $final_result;
		}

		$report = EB::table('Report');
		$report->obj_id = $id;
		$report->obj_type = $type;
		$report->reason = $reason;
		$report->created = EB::date()->toSql();
		$report->created_by = $log_user;
		$state = $report->store();

		if (!$state)
		{
			$message = JText::_('PLG_API_EASYBLOG_CANT_STORE_REPORT');
			$final_result->result['message'] = $message;
			$final_result->result['status'] = false;

			return $final_result;
		}

		// Notify the site admin when there's a new report made
		$post = EB::post($id);
		$report->notify($post);
		$final_result->result['message'] = JText::_('PLG_API_EASYBLOG_REPORT_LOGGED_SUCCESS');
		$final_result->result['status'] = true;

		return $final_result;
	}
}
