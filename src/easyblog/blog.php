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

// For image upload
require_once EBLOG_ADMIN_INCLUDES . '/mediamanager/mediamanager.php';
require_once EBLOG_ADMIN_INCLUDES . '/blogimage/blogimage.php';
require_once EBLOG_ADMIN_INCLUDES . '/mediamanager/adapters/local.php';
require_once EBLOG_ADMIN_INCLUDES . '/mediamanager/adapters/post.php';
require_once EBLOG_ADMIN_INCLUDES . '/mediamanager/adapters/posts.php';
require_once EBLOG_ADMIN_INCLUDES . '/mediamanager/adapters/abstract.php';

/**
 * Esyblog API class
 *
 * @since  1.0
 */
class EasyblogApiResourceBlog extends ApiResource
{
	/**
	 * Function to delete blog
	 *
	 * @return mixed
	 */
	public function delete()
	{
		$this->plugin->setResponse($this->deleteBlog());
	}

	/**
	 * Function for CU blogs
	 *
	 * @return array|object
	 */
	public function post()
	{
		$input = JFactory::getApplication()->input;
		$data = $input->post->getArray(array());
		$log_user = $this->plugin->get('user')->id;
		$uid = $input->getInt('uid');

		// If no id given, create a new post.
		$post = EB::post($uid);
		$key = 'post:' . $post->id;

		// If there's no id provided, we will need to create the initial revision for the post.
		if (! $uid)
		{
			$post->create();
		}

		$data['image'] = basename($data['image']);
		$data['image'] = $key . '/' . $data['image'];

		// Document needs to get from app
		$data['content'] = urldecode($input->get('content', '', 'raw'));

		$data['document'] = null;
		$data['published'] = $input->get('published', 1, 'INT');
		$data['created_by'] = $log_user;
		$data['doctype'] = 'legacy';

		$post->bind($data, array());

		// Default options
		$options = array();

		// Since this is a form submit and we knwo the date that submited already with the offset timezone. we need to reverse it.
		$options['applyDateOffset'] = true;

		// For autosave requests we do not want to run validation on it.
		$autosave = $input->post->get('autosave', false, 'bool');

		if ($autosave)
		{
			$options['validateData'] = false;
		}

		// Save post
		try
		{
			$post->save($options);
		}
		catch (EasyBlogException $exception)
		{
			$this->plugin->setResponse($this->getErrorResponse($exception->getCode(), $exception->getMessage()));

			return;
		}

		$bpost = EB::post($post->id);
		$item = EB::formatter('entry', $bpost);
		$scm_obj = new EasyBlogSimpleSchema_plg;
		$item = new stdClass;
		$item->result[] = $scm_obj->mapPost($item, '<p><br><pre><a><blockquote><strong><h2><h3><em><ul><ol><li><iframe>');

		$this->plugin->setResponse($item);
	}

	/**
	 * Function to get blog details
	 *
	 * @return mixed
	 */
	public function get()
	{
		$input = JFactory::getApplication()->input;
		$id = $input->get('id', null, 'INT');

		// If we have an id try to fetch the user
		$blog = EasyBlogHelper::table('Blog');
		$blog->load($id);

		if (! $id)
		{
			$this->plugin->setResponse($this->getErrorResponse(404, JText::_('PLG_API_EASYBLOG_BLOG_ID_MESSAGE')));

			return;
		}

		if (! $blog->id)
		{
			$this->plugin->setResponse($this->getErrorResponse(404, JText::_('PLG_API_EASYBLOG_BLOG_NOT_FOUND_MESSAGE')));

			return;
		}

		// Format data for get image using function
		$post = EB::post($blog->id);
		$post = EB::formatter('entry', $post);
		$scm_obj = new EasyBlogSimpleSchema_plg;
		$item = new stdClass;
		$item->result = $scm_obj->mapPost($post, '<p><br><pre><a><blockquote><strong><h2><h3><em><ul><ol><li><iframe>');
		$item->result->isowner = ($blog->created_by == $this->plugin->get('user')->id) ? true : false;
		$item->result->author = $this->plugin->get('user')->name;
		$item->result->allowcomment = $blog->allowcomment;

		$item->result->allowsubscribe = $blog->subscription;

		// Tags
		$modelPT = EasyBlogHelper::getModel('PostTag');
		$item->result->tags = $modelPT->getBlogTags($blog->id);
		$this->plugin->setResponse($item);
	}

	/**
	 * Function to delete blog
	 *
	 * @return mixed
	 */
	public function deleteBlog()
	{
		$app = JFactory::getApplication();
		$id = $app->input->get('id', 0, 'INT');
		$blog = EasyBlogHelper::table('Blog', 'Table');
		$blog->load($id);
		$res = new stdClass;

		if (! $blog->id || ! $id)
		{
			$res->status = 0;
			$res->message = JText::_('PLG_API_EASYBLOG_BLOG_NOT_EXISTS_MESSAGE');

			return $res;
		}
		else
		{
			$val = $blog->delete($id);
			$res->status = $val;
			$res->message = JText::_('PLG_API_EASYBLOG_DELETE_MESSAGE');

			return $res;
		}
	}

	/**
	 * Function to upload image
	 *
	 * @param   array  $key  key
	 *
	 * @return mixed
	 */
	public function uploadImage($key)
	{
		// Load up media manager
		$mm = EB::mediamanager();

		// Get the target folder
		$placeId = EBMM::getUri($key);

		// Get the file input
		$file = JRequest::getVar('file', '', 'FILES', 'array');

		// Check if the file is really allowed to be uploaded to the site.
		$state = EB::image()->canUploadFile($file);

		if ($state instanceof Exception)
		{
			// Add error code
			return $state;
		}

		// MM should check if the user really has access to upload to the target folder
		$allowed = EBMM::hasAccess($placeId);

		if ($allowed instanceof Exception)
		{
			// Add error code
			return $state;
		}

		// Check the image name is it got contain space, if yes need to replace to '-'
		$fileName = $file['name'];
		$file['name'] = str_replace(' ', '-', $fileName);

		// Upload the file now
		$file = $mm->upload($file, $placeId);

		/*
		 * Response object is intended to also include
		 * other properties like status message and status code.
		 * Right now it only inclues the media item.
		 */
		$response = new stdClass;
		$response->media = EBMM::getMedia($file->uri);

		return $response;
	}
}
