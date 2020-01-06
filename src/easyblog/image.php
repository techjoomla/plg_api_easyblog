<?php
/**
 * @package    API_Plugins
 * @copyright  Copyright (C) 2009-2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license    GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link       http://www.techjoomla.com
 */

defined('_JEXEC') or die('Restricted access');

error_reporting(E_ERROR | E_PARSE);

jimport('joomla.user.user');

require_once EBLOG_ADMIN_INCLUDES . '/mediamanager/mediamanager.php';
require_once EBLOG_ADMIN_INCLUDES . '/image/image.php';
require_once EBLOG_ADMIN_INCLUDES . '/blogimage/blogimage.php';
require_once EBLOG_ADMIN_INCLUDES . '/mediamanager/adapters/local.php';
require_once EBLOG_ADMIN_INCLUDES . '/mediamanager/adapters/post.php';
require_once EBLOG_ADMIN_INCLUDES . '/mediamanager/adapters/posts.php';
require_once EBLOG_ADMIN_INCLUDES . '/mediamanager/adapters/abstract.php';

/** Class EasyblogApiResourceImage
 *
 * @since  1.8.8
 */
class EasyblogApiResourceImage extends ApiResource
{
	/** Post Call
	 *
	 * @return	ApiPlugin response object
	 */
	public function post()
	{
		$res = new stdClass;

		// Let's get the path for the current request.
		$file = JRequest::getVar('file', '', 'FILES', 'array');

		if ($file['name'])
		{
			$key = $post->id;
			$place = 'post:' . $key;
			$source	= urldecode('/' . $file['name']);
			$allowed = EasyBlogImage::canUploadFile($file, $message);

			if ($allowed !== true)
			{
				$res->status = 0;
				$res->message = JText::_('PLG_API_EASYBLOG_UPLOAD_DENIED_MESSAGE');

				return $res;
			}

			$media = new EasyBlogMediaManagerAbstractSource('');
			$upload_result = $media->upload($file, $place);
			$upload_result->key = $place . $source;
			$upload_result->group = 'files';
			$upload_result->parentKey = $place . '|/';
			$upload_result->friendlyPath = 'My Media/' . $source;
			unset($upload_result->variations);
			$this->plugin->setResponse($upload_result);

			return $upload_result;
		}
		else
		{
			$this->plugin->setResponse($this->getErrorResponse(404, __FUNCTION__ . JText::_('PLG_API_EASYBLOG_UPLOAD_UNSUCCESSFULL')));
		}
	}

	/** Get Call
	 *
	 * @return	mixed
	 */
	public function get()
	{
		$this->plugin->setResponse($this->getErrorResponse(404, __FUNCTION__ . JText::_('PLG_API_EASYBLOG_NOT_SUPPORTED')));
	}
}
