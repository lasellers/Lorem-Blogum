<?php
/*

@author: Lewis A. Sellers <lasellers@gmail.com>
@date: 6/2015
*/
class LoremBlogum_API_Endpoint{
	
	/** Hook WordPress
	*	@return void
	*/
	public function __construct(){
		add_filter('query_vars', array($this, 'add_query_vars'), 0);
		add_action('parse_request', array($this, 'sniff_requests'), 0);
		add_action('init', array($this, 'add_endpoint'), 0);
	}	
	
	/** Add public query vars
	*	@param array $vars List of current public query vars
	*	@return array $vars 
	*/
	public function add_query_vars($vars){
		$vars[] = '__api';
		$vars[] = 'feed_id';
		return $vars;
	}
	
	/** Add API Endpoint
	*	
	*	@return void
	*/
	public function add_endpoint(){
//		add_rewrite_rule('^api/loremblogum/?([0-9]+)?/?','index.php?__api=loremblogum&feed_id=$matches[1]','top');
		add_rewrite_rule('^api/loremblogum/([^/]+)/?$','index.php?__api=loremblogum&feed_id=$matches[1]','top');
		add_rewrite_rule('^api/loremblogum$','index.php?__api=loremblogum','top');
	}

	/**	Sniff Requests
	*	This is where we hijack all API requests
	* 	If $_GET['__api'] is set, we kill WP and serve up pug bomb awesomeness
	*	@return die if API request
	*/
	public function sniff_requests(){
		global $wp;
		if(isset($wp->query_vars['__api'])){
			$this->handle_request();
			exit;
		}
	}
	
	/** Handle Requests
	*	This is where we send off for an intense pug bomb package
	*	@return void 
	*/
	protected function handle_request(){
		global $wp;
		$api=$wp->query_vars['__api'];
		if($api=='loremblogum')
		{
			$mw = new loremblogum();

			$feed_id=$mw->get_feed_id();

			$count=$mw->fetch($feed_id);
			$mw->next_feed_id();
			$this->send_response($mw->feed);
			exit;
		}
		else
		{
			$this->send_response('Unknown api call to '.$api);
			exit;
		}
	}

	/** Response Handler
	*	This sends a JSON response to the browser
	*/
	protected function send_response($msg, $type = 'success'){
		if(is_string($msg))
			$response[$type] = $msg;
		else
			$response=$msg;
		header('content-type: application/json; charset=utf-8');
		echo json_encode($response)."\n";
		exit;
	}
}
new LoremBlogum_API_Endpoint();