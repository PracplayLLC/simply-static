<?php
/**
 * @package Simply_Static
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Simply Static url request class
 */
class Simply_Static_Url_Request
{
	/**
	 * The URI resource
	 * @var string
	 */
	protected $url;

	/**
	 * The raw response from the HTTP request
	 * @var string
	 */
	protected $response;

	/**
	 * Constructor
	 * @param string $url URI resource
	 */
	public function __construct($url)
	{
		$this->url = filter_var($url, FILTER_VALIDATE_URL);
		// TODO: Set this as a const or an option
		$response = wp_remote_get($this->url,array('timeout'=>300)); //set a long time out
		$this->response = (is_wp_error($response) ? '' : $response);
	}

	/**
	 * Returns the sanitized url
	 * @return string
	 */
	public function get_url()
	{
		return $this->url;
	}

	/**
	 * Allows to override the HTTP response body
	 * @param string $newBody
	 * @return void
	 */
	public function set_response_body($new_body)
	{
		if (is_array($this->response))
		{
			$this->response['body'] = $new_body;
		}
	}

	/**
	 * Returns the HTTP response body
	 * @return string
	 */
	public function get_response_body()
	{
		return isset($this->response['body']) ? $this->response['body'] : '';
	}

	/**
	 * Returns the content type
	 * @return string
	 */
	public function get_content_type()
	{
		return isset($this->response['headers']['content-type']) ? $this->response['headers']['content-type'] : null;
	}

	/**
	 * Checks if content type is html
	 * @return bool
	 */
	public function is_html()
	{
		return stripos($this->get_content_type(), 'html') !== false;
	}

	/**
	 * Removes WordPress-specific meta tags
	 * @return void
	 */
	public function cleanup()
	{
		if ($this->is_html())
		{
			$response_body = preg_replace('/<link rel=["\' ](pingback|alternate|EditURI|wlwmanifest|index|profile|prev)["\' ](.*?)>/si', '', $this->get_response_body());
			$response_body = preg_replace('/<meta name=["\' ]generator["\' ](.*?)>/si', '', $response_body);
			$this->set_response_body($response_body);
		}
	}

	/**
	 * Extracts the list of unique urls
	 * @param string $base_url Base url of site. Used to extract urls that relate only to the current site.
	 * @return array
	 */
	public function extract_all_urls($base_url)
	{
		$all_urls = array();

		if ($this->is_html() && preg_match_all('/' . str_replace('/', '\/', $base_url) . '[^"\'#\? ]+/i', $this->response['body'], $matches))
		{
			$all_urls = array_unique($matches[0]);
		}

		return $all_urls;
	}

	/**
	 * Replaces base url
	 * @param string $old_base_url
	 * @param string $new_base_url
	 * @return void
	 */
	public function replace_base_url($old_base_url, $new_base_url)
	{
		if ($this->is_html())
		{
			$response_body = str_replace($old_base_url, $new_base_url, $this->get_response_body());
			$response_body = str_replace('<head>', "<head>\n<base href=\"" . esc_attr($new_base_url) . "\" />\n", $response_body);
			$this->set_response_body($response_body);
		}
	}
}