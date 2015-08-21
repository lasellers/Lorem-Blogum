<?php
/*

@author: Lewis A. Sellers <lasellers@gmail.com>
@date: 6/2015
*/
/* so media works */
require_once(ABSPATH . "wp-admin" . '/includes/image.php');
require_once(ABSPATH . "wp-admin" . '/includes/file.php');
require_once(ABSPATH . "wp-admin" . '/includes/media.php');
/* so wp_insert_category works */
require_once(ABSPATH . "wp-admin/includes/taxonomy.php");

/* */
class loremblogum {
	/* */
	public $pluginOptions;
	public $feed;
	public $feed_items;
	public $feed_item;
	public $predefine;
	public $new_item_count;
	public $dom;
	public $raw;
	public $rejects;

	private $maximum_imports_per_call=1;
	private $maximum_rejects_per_call=1;
	private $min_title=8;
	private $min_content=76;

	/* */
	function __construct() {
		$this->load_options();
		$this->dom = new SmartDOMDocument();
		$this->dom->substituteEntities = false;
	}

	/* --------------------------------------------------------------------------- */
	/* */
	public function load_options()
	{
		$this->pluginOptions = get_option(LOREMBLOGUM_DATA);
		/* internal defaults */
		if(!isset($this->pluginOptions['feed_id'])) $this->pluginOptions['feed_id']=0;
		if(!isset($this->pluginOptions['max_feed_id'])) $this->pluginOptions['max_feed_id']=0;

		if(!isset($this->pluginOptions['feeds'])) $this->pluginOptions['feeds']=[];
		if(!isset($this->pluginOptions['feed'])) $this->pluginOptions['feed']=[];

		if(!isset($this->pluginOptions['predefines'])) $this->pluginOptions['predefines']=[];
		if(!isset($this->pluginOptions['predefine'])) $this->pluginOptions['predefine']=null;

		/*setting defaults */	
		if(!isset($this->pluginOptions['default_category'])) $this->pluginOptions['default_category']='Uncategorized';
		if(!isset($this->pluginOptions['featured_image'])) $this->pluginOptions['featured_image']='Yes';
		
		if(!isset($this->pluginOptions['min_width'])) $this->pluginOptions['min_width']=120;
		if(!isset($this->pluginOptions['min_height'])) $this->pluginOptions['min_height']=120;
		if(!isset($this->pluginOptions['min_title'])) $this->pluginOptions['min_title']=8;
		if(!isset($this->pluginOptions['min_content'])) $this->pluginOptions['min_content']=76*12;

		if(!isset($this->pluginOptions['posts_with_images_only'])) $this->pluginOptions['posts_with_images_only']='Yes';

		if(!isset($this->pluginOptions['post_status'])) $this->pluginOptions['post_status']='publish';
		if(!isset($this->pluginOptions['post_type'])) $this->pluginOptions['post_type']='post';
		/*if(!isset($this->pluginOptions['post_parent'])) $this->pluginOptions['post_parent']='';*/
		if(!isset($this->pluginOptions['post_author'])) $this->pluginOptions['post_author']='';

		if(!isset($this->pluginOptions['maximum_remove_posts_per_call'])) $this->pluginOptions['maximum_remove_posts_per_call']=100;
		if(!isset($this->pluginOptions['maximum_imports_per_call'])) $this->pluginOptions['maximum_imports_per_call']=1;
		if(!isset($this->pluginOptions['maximum_rejects_per_call'])) $this->pluginOptions['maximum_rejects_per_call']=5;

		if(!isset($this->pluginOptions['caching_ttl'])) $this->pluginOptions['caching_ttl']=1;
		/*		if(!isset($this->pluginOptions['schedule'])) $this->pluginOptions['schedule']='hourly';*/

		foreach($this->pluginOptions['feeds'] as $feed)
		{
			if(isset($feed->lo))
				$feed->log=(array)$feed->log;
		}

		update_option(LOREMBLOGUM_DATA,$this->pluginOptions);
	}
	/* --------------------------------------------------------------------------- */

	/* */
	public function save_options()
	{
		update_option(LOREMBLOGUM_DATA,$this->pluginOptions);
		update_option(LOREMBLOGUM_FEEDS,$this->pluginOptions['feeds']);
		update_option(LOREMBLOGUM_PREDEFINES,$this->pluginOptions['predefines']);
	}
	/* --------------------------------------------------------------------------- */

	/* */
	public function save_feed()
	{
		if(isset($this->feed->key)&&isset($this->feed))
		{
			$this->pluginOptions['feeds'][$this->feed->key]=$this->feed;
		}
		update_option(LOREMBLOGUM_DATA,$this->pluginOptions);
		update_option(LOREMBLOGUM_FEEDS,$this->pluginOptions['feeds']);
	}

	/* --------------------------------------------------------------------------- */
	/* */
	public function save_predefine()
	{
		if(isset($this->feed->predefine->key)&&isset($this->feed->predefine))
		{
			$this->pluginOptions['predefines'][$this->feed->predefine->key]=$this->feed->predefine;
		}
		update_option(LOREMBLOGUM_DATA,$this->pluginOptions);
		update_option(LOREMBLOGUM_PREDEFINES,$this->pluginOptions['predefines']);
	}
	/* --------------------------------------------------------------------------- */

	/* */
	public function trace($str)
	{
		if(!is_string($str)) return;
		if(!isset($this->feed->log)) return;
		$this->feed->log['trace']=$str;
	}
	/* --------------------------------------------------------------------------- */


	/* curl get the raw content of a feed */
	private function getCurl($url,$cache_id=null)
	{	
		$caching_ttl=isset($this->pluginOptions['caching_ttl'])?$this->pluginOptions['caching_ttl']:0;
		$min_cache_bytes=1024;

		$this->trace("getCurl $url , $cache_id");

		/* is cached? */
		if($caching_ttl>0&&$cache_id!=null)
		{
			$this->raw=get_transient($cache_id);
			$this->trace("getCurl get_transient len raw=".strlen($this->raw));

			$this->feed->log['curl'][$cache_id]="CACHE: (".strlen($this->raw)." bytes) (min $min_cache_bytes) (ttl $caching_ttl) $url";

			if(strlen($this->raw)>$min_cache_bytes) return $this->raw;
		}

		/* */
		$ch = curl_init($url);
		curl_setopt ($ch, CURLOPT_URL, $url);

		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/43.0.2357.134 Safari/537.36");

		@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, 0);

		$cookie_file = "loremblogum.txt";
		curl_setopt($ch, CURLOPT_COOKIESESSION, true);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);

		try {
			$this->raw = curl_exec($ch);
		}
		catch(Exception $e)
		{
			$this->trace("getCurl Curl error: ".$e->getMessage()." ".curl_error($ch));
			return "";
		}

		$this->feed->log['curl'][$cache_id]="RAW: (".strlen($this->raw)." bytes) $url ==> ".curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

		if(curl_errno($ch))
		{
			$this->trace("getCurl ".$this->feed->url." error:" . curl_error($ch));
			return "";
		}
		else
		{
			$info = curl_getinfo($ch);
			if($info['http_code']!=200)
			{
				$this->trace("getCurl ".$this->feed->url." returned a ".$info['http_code']);
				return "";
			}
		}
		curl_close($ch);

		/* fall back */
		if(strlen($this->raw)==0)
		{
			$this->raw=file_get_contents($url);
			$this->feed->log['curl'][$cache_id]="RAW(2) : (".strlen($this->raw)." bytes) $url";
		}

		/* */
		if($caching_ttl>0&&$cache_id!=null&&strlen($this->raw)>$min_cache_bytes)
		{
			set_transient($cache_id,$this->raw,$caching_ttl * HOUR_IN_SECONDS);
			$this->trace("getCurl raw len=".strlen($this->raw));
		}

		return $this->raw;
	}

	/* --------------------------------------------------------------------------- */


	/* */

	public function fetch($feed_id)
	{
		$key=$this->get_feed_key($feed_id);
		if($key===false)
		{
			return -1;
		}
		$this->feed=(object)$this->pluginOptions['feeds'][$key];

		if(!isset($this->feed->collected_categories))
			$this->feed->collected_categories=[];

		if(!isset($this->feeds)||!is_array($this->feeds))
			$this->feeds=[];
		if(!isset($this->feed_items)||!is_array($this->feed_items))
			$this->feed_items=[];

		$feedobj=new \loremblogumFeeds();
		$this->feed=$feedobj->check_fields_exist_on_object($this->feed);

		$this->feed->predefine=[];

		$this->maximum_imports_per_call=intval($this->pluginOptions['maximum_imports_per_call']);
		$this->maximum_rejects_per_call=intval($this->pluginOptions['maximum_rejects_per_call']);
		$this->min_title=intval($this->pluginOptions['min_title']);
		$this->min_content=intval($this->pluginOptions['min_content']);

		$this->feed->log=[];
		$this->feed->log['trace']="";
		$this->feed->log['date']="";
		$this->feed->log['item_count']=0;
		$this->feed->log['image_count']=0;
		$this->feed->log['min_title']=$this->min_title;
		$this->feed->log['min_content']=$this->min_content;
		$this->feed->log['maximum_imports_per_call']=$this->maximum_imports_per_call;
		$this->feed->log['maximum_rejects_per_call']=$this->maximum_rejects_per_call;
		$this->feed->log['rejected_feed_item_count']=0;
		$this->feed->log['rejected_feed_items']=[];
		$this->feed->log['feed_items_page_title']=[];
		$this->feed->log['feed_items_page_raw_content']=[];
		$this->feed->log['feed_items_page_content']=[];
		$this->feed->log['feed_items']=[];
		$this->feed->log['feed_items_images']=[];
		$this->feed->log['feed_item_raw_count']=0;
		$this->feed->log['feed_item_count']=0;
		$this->feed->log['feed_items_url']=[];
		$this->feed->log['feed_items_title']=[];
		$this->feed->log['curl']=[];
		$this->feed->log['predefines']=[];

		$this->trace("Start");

		$this->filtersStringToArray();

		$this->pluginOptions['feed_id']=$this->feed->feed_id;

		$this->feed->image_count=0;
		$this->new_item_count=0;

		/* */
		$this->trace("Get RSS/ATOM");
		$r=$this->getRSS();
		if($r===false)
			$r=$this->getATOM();

		/* */
		$this->feed->log['date']=date("Y-m-d H:i",time());
		$this->feed->log['image_count']=$this->feed->image_count;
		$this->feed->log['item_count']=$this->new_item_count;
		$this->feed->log['feed_item_count']=count($this->feed_items);

		$this->save_feed();
		$this->save_options();

		return $this->new_item_count;
	}

	/* --------------------------------------------------------------------------- */

	/* Check if feed is RSS and if so return arrays with parsed Feeds and FeedItems data */
	private function getRSS()
	{
		$this->trace("getRSS");

		$cache_id="loremblogum-feed-".$this->feed->feed_id;
		$raw=$this->getCurl($this->feed->url,$cache_id);

		if(trim($raw)=='')
		{
			$this->feed->title="[Connection error -- feed can not be fetched.]";
			$this->trace($this->feed->title);
			return false;
		}

		libxml_use_internal_errors(true);
		try {
			$xml = new \SimpleXmlElement($raw, LIBXML_NOCDATA);
		}
		catch(Exception $e)
		{}

		/* */
		if (!isset($xml)||!isset($xml->channel)) {
			$errors = libxml_get_errors();
			libxml_clear_errors();
			$this->feed->title="[Connection error -- feed invalid.]";	
			$this->trace($this->feed->url." ==> ".var_export($errors,true)."");
			return false;
		}

		/* */
		$ns = array
		(
			'content' => 'http://purl.org/rss/1.0/modules/content/',
			'wfw' => 'http://wellformedweb.org/CommentAPI/',
			'dc' => 'http://purl.org/dc/elements/1.1/',
			'sy' => 'http://purl.org/rss/1.0/modules/syndication/',
			'slash' => 'http://purl.org/rss/1.0/modules/slash/'
			);

		$this->feed->type='RSS';
		$this->feed->title=(string)$xml->channel->title;
		if(isset($xml->channel->pubDate))
			$this->feed->published_at=date("Y-m-d h:m:s",strtotime((string)$xml->channel->pubDate));
		else if(isset($xml->channel->lastBuildDate))
			$this->feed->published_at=date("Y-m-d h:m:s",strtotime((string)$xml->channel->lastBuildDate));
		else
			$this->feed->published_at=null;

		$sy = $xml->channel->children($ns['sy']);

		$this->feed_items=[];
		$count = count($xml->channel->item);
		$this->feed->log['feed_item_raw_count']=$count;

		for($i=0; $i<$count; $i++)
		{
			$item=$xml->channel->item[$i];
			$this->feed->log['feed_items_title'][]=trim(wp_strip_all_tags((string)$item->title));
			$this->feed->log['feed_items_url'][]=trim(wp_strip_all_tags((string)$item->link));
		}

		$this->new_item_count=0;
		$this->rejects=0;
		$skip=false;
		for($i=0; $i<$count; $i++)
		{
			$this->trace("RSS i=".$i);

			$item=$xml->channel->item[$i];

			if($this->feed->published_at==null&&isset($item->pubDate))
				$this->feed->published_at=date("Y-m-d h:m:s",strtotime((string)$item->pubDate));

			$this->feed_item=new \loremblogumFeedItems();
			$this->feed_item->feed_id=$this->feed->feed_id;	
			$this->feed_item->url=trim((string)$item->link);
			$this->feed_item->title=trim((string)$item->title);
			$this->feed_item->published_at=date("Y-m-d h:m:s",strtotime((string)$item->pubDate));
			$this->feed_item->content="";

			$this->cleanFeedItemCategories($item->category);

			$this->trace("RSS i=".$i." computePredefine");

			$this->computePredefine();

			if($this->feed->predefine!=null)
			{
				if($this->postItem())
				{
					if($this->new_item_count>=$this->maximum_imports_per_call)
					{
						$skip=true;
					}
				}
				else
				{
					if($this->rejects>=$this->maximum_rejects_per_call)
					{
						$skip=true;
					}
				}
			}

			$this->feed_items[]=$this->feed_item;
			$this->save_options();
			if($skip) break;
		}

		return true;
	}

	/* --------------------------------------------------------------------------- */

	/* Check if feed is ATOM and if so return arrays with parsed Feeds and FeedItems data */
	private function getATOM()
	{
		$this->trace("getATOM");

		$cache_id="loremblogum-feed-".$this->feed->feed_id;
		$raw=$this->getCurl($this->feed->url,$cache_id);

		if(trim($raw)=='')
		{
			$this->feed->title="[Connection error -- feed can not be fetched.]";
			$this->feed->log['trace']=$this->feed->url." ==> ".$this->feed->title;
			return false;
		}

		libxml_use_internal_errors(true);
		try {
			$xml = new \SimpleXmlElement($raw, LIBXML_NOCDATA);
		}
		catch(Exception $e)
		{}

		/* */
		if (!isset($xml)||!isset($xml->entry)) {
			$errors = libxml_get_errors();
			libxml_clear_errors();
			$this->feed->title="[Connection error -- feed invalid.]";	
			$this->trace($this->feed->url." ==> ".var_export($errors,true)."");
			return false;
		}

		/* */
		$this->feed->type='ATOM';
		$this->feed->title=trim((string)$xml->title);
		$this->feed->published_at=(string)$xml->updated;
		$this->feed->author=(string)$xml->author;

		$this->feed_items=[];
		$links=[];
		$count = count($xml->entry);
		$this->feed->log['feed_item_raw_count']=$count;

		for($i=0; $i<$count; $i++)
		{
			$item=$xml->entry[$i];
			$this->feed->log['feed_items_title'][]=trim(wp_strip_all_tags((string)$item->title));
			$this->feed->log['feed_items_url'][]=trim(wp_strip_all_tags((string)$item->link['href']));
		}

		$this->new_item_count=0;
		$this->rejects=0;
		$skip=false;
		for($i=0; $i<$count; $i++)
		{
			$item=$xml->entry[$i];

			$this->feed_item=new \loremblogumFeedItems();
			$this->feed_item->feed_id=$this->feed->feed_id;
			$this->feed_item->url=trim((string)$item->link['href']);
			$this->feed_item->title=trim(wp_strip_all_tags((string)$item->title));
			$this->feed_item->published_at=date("Y-m-d h:m:s",strtotime((string)$item->updated));
			$this->feed_item->content="";

			$this->cleanFeedItemCategories($item->category);

			$this->computePredefine();
			if($this->feed->predefine!=null)
			{
				if($this->postItem())
				{
					if($this->new_item_count>=$this->maximum_imports_per_call)
					{
						$skip=true;
					}
				}
				else
				{
					if($this->rejects>=$this->maximum_rejects_per_call)
					{
						$skip=true;
					}
				}
			}

			$this->feed_items[]=$this->feed_item;

			if($skip) break;
		}

		return true;
	}

	/* --------------------------------------------------------------------------- */	
	
	/* Posts the actual article. Returns true if  posted as new, or false if determined to be a duplicate. */
	private function postItem()
	{
		$this->trace("postItem start");

		$this->filtersStringToArray();
		$this->doCollectedCategories();
		$this->getPostCategory();

		$user_id=$this->pluginOptions['post_author'];

		$this->feed_item->image_count=-1;

		/* Check if we already have this article by it's url. Skip if we do. */
		global $wpdb;
		$sql="select post_id from ".$wpdb->postmeta." WHERE meta_key='".LOREMBLOGUM_META_URL."' AND meta_value = \"%s\"";
		//echo "sql=$sql === ".$this->feed_item->url."<br>";
		$results = $wpdb->get_results(
			$wpdb->prepare($sql,$this->feed_item->url),
			ARRAY_A);
		if($results)
		{
			$post_id=$results[0]['post_id'];
			$reject=$this->feed_item->url." ".$this->feed_item->title." ==> Already have this article.";
			$this->trace($reject);
			
			$this->feed->log['rejected_feed_items'][$post_id]=$reject;
			$this->feed->log['rejected_feed_item_count']++;

			return false;
		}

		/* */
		$cache_id="loremblogum-article-".$this->feed->feed_id."-".md5($this->feed_item->url);
		$raw=$this->getCurl($this->feed_item->url,$cache_id);

		$this->feed->log['feed_items_page_raw_content'][$this->new_item_count."-".$cache_id]=strlen($raw)." (".$this->feed_item->article_type." ".$this->feed_item->article_element.") (".$this->feed_item->image_count.")";

		$this->trace("extract and clean");

		/* title */
		$this->extractTitle($raw);
		$this->feed->log['feed_items_page_title'][]=$this->feed_item->title." (".$this->feed_item->title_type." ".$this->feed_item->title_element.")";

		/* content */
		$this->extractContent($raw);
		$pre_content_10=substr($this->feed_item->content,0,10);
		if($pre_content_10!='No content')
		{
			$this->cleanContentDom();
			$this->stripContentDomByFilters();
			$this->cleanContentString();
		}

		/* */
		$this->trace("postItem good size");

		$cat_ids=$this->getMultipleCategories();

		$linkback='<div class="loremblogum-linkback">Via <a href="'.$this->feed_item->url.'">'.$this->feed->title.'</a></div>';
		$this->feed_item->content='<div class="loremblogum-article" data-feed_id="'.$this->feed->feed_id.'"><!-- loremblogum feed_id='.$this->feed->feed_id.'-->'.$this->feed_item->content.'</div>'.$linkback;

		$this->trace($this->feed->url." ==> wp_insert_post");

		/* disable revisions for a moment */
		remove_action('pre_post_update', 'wp_save_post_revision');

		/* post, but without image changes */
		$post_array = [
		'post_status'   => 'draft',
		'post_type'     => $this->pluginOptions['post_type'],
		'post_title'    => $this->feed_item->title,
		'post_content'  => $this->feed_item->content,
		'post_author'   => $user_id, 
		'post_date'   => date("Y-m-d H:i:s",strtotime($this->feed_item->published_at)),
		'post_category' => $cat_ids
		];
		$wp_error=true;
		$this->feed_item->post_id=wp_insert_post( $post_array ,$wp_error);
		$this->trace($this->feed->url." ==> wp_insert_post ".json_encode($wp_error));

		update_post_meta($this->feed_item->post_id, LOREMBLOGUM_META_URL, (string)$this->feed_item->url);
		update_post_meta($this->feed_item->post_id, LOREMBLOGUM_META_ID, $this->feed->feed_id);

		if($wp_error===true)
		{
			$this->trace($this->feed->url." ==> wp_update_post");

			$this->makeContentImagesLocal();

			$this->feed->log['feed_items_page_content'][$this->new_item_count."-".$cache_id]=strlen($this->feed_item->content)." (".$this->feed_item->article_type." ".$this->feed_item->article_element.") (".$this->feed_item->image_count." images)";

			/* */
			$trash=false;
			$reject="";
			if($pre_content_10=='No content')
			{
				$reject=$this->feed_item->url." ".strlen($this->feed_item->content)." ==> Content too short. (".$this->feed_item->image_count.")";
				$trash=true;
			}
			else if($this->feed_item->image_count<=0&&strlen($this->feed_item->title)<$this->pluginOptions['min_title'])
			{
				$reject=$this->feed_item->url." ".$this->feed_item->title." ==> Title too short. (".$this->feed_item->image_count.")";
				$trash=true;
			}	
			else if($this->feed_item->image_count<=0&&strlen($this->feed_item->content)<$this->pluginOptions['min_content'])
			{
				$reject=$this->feed_item->url." ".strlen($this->feed_item->content)." ==> Content too short and no images.";
				$trash=true;
			}
			/* if posting with images only, trash the post if it has no images. This should keep the postsmeta in the db so it is still flagged as fetched and we don't try to redownload it. */
			else if($this->pluginOptions['posts_with_images_only']=='Yes'
				&&$this->feed_item->image_count<=0)
			{
				$reject=$this->feed_item->url." ==> Has no image as required.";
				$trash=true;
			}

			/* */
			$post_array = [
			'ID'=>$this->feed_item->post_id,
			'post_status' => $this->pluginOptions['post_status'],
			'post_title'  => $this->feed_item->title,
			'post_content' => $this->feed_item->content
			];
			wp_update_post( $post_array);

			$this->feed->log['feed_items'][$this->feed_item->post_id]=$this->feed_item->title;
		}

		/* if we reject it, just trash it so the meta info stays in the database and we don't keep dup-trashing it over and over */
		if($trash)
		{
			$this->trace($reject);
			$this->feed->log['rejected_feed_items'][$this->feed_item->post_id]=$reject;
			$this->feed->log['rejected_feed_item_count']++;

			$this->rejects++;

			wp_trash_post($this->feed_item->post_id);
			$this->trace("trash reject temp  ==> $reject");
		}

		/* turn revisions back on */
		add_action('pre_post_update', 'wp_save_post_revision');

		$this->new_item_count++;
		return $trash===false;
	}

	/* --------------------------------------------------------------------------- */

	/* Copies the article image from whatever url to the local WP server and attaches it to the post */
	/* Also changes the url string in the article to the url to the local media */
	public function makeContentImagesLocal()
	{
		$this->feed_item->image_count=0;

		$user_id=$this->pluginOptions['post_author'];
		$post_id=$this->feed_item->post_id;
		$this->trace($this->feed->url." ==> makeContentImagesLocal");

		if(strlen($this->feed_item->content)<=2) return;

		$this->pre_loadHTML();

		$imgs = $this->dom->getElementsByTagName('img');

		if(get_class($imgs)!='DOMNodeList') return;

		$length = $imgs->length;
		$featured_image=false;
		for ($i = $length-1; $i >=0; $i--)
		{
			$img=$imgs->item($i);

			$found=false;
			$image=[
			'src'=>(string)$img->getAttribute('src'),
			'alt'=>(string)$img->getAttribute('alt'),
			'title'=>(string)$img->getAttribute('title')
			];

			$img->removeAttribute('width');
			$img->removeAttribute('height');

			if(strlen($image['src'])<0&&strlen($image['src'])>255)
				continue;

			if(substr($image['src'],0,2)=='//')
			{
				$image['src']="http:".$image['src'];
			}

			/* */
			$tmp = download_url( $image['src'] );

			$desc = $image['alt']==''?$image['title']:$image['alt'];

			$file_array = [];

			preg_match('/[^\?]+\.(jpg|jpe|jpeg|gif|png)/i', $image['src'], $matches);
			if(count($matches)==0)
			{
				$file_array['name'] = $image['src'];
			}
			else
			{
				$file_array['name'] = basename($matches[0]);
			}

			$file_array['tmp_name'] = $tmp;

			if ( is_wp_error( $tmp ) ) {
				@unlink($file_array['tmp_name']);
				$file_array['tmp_name'] = '';
			}
			else
			{
				$cat_ids=$this->getMultipleCategories();
				$post_array = [
				'post_author' => $user_id, 
				'post_date'   => date("Y-m-d H:i:s",strtotime($this->feed_item->published_at)),
				'post_category' => $cat_ids
				];

				$media_id = media_handle_sideload( $file_array, $post_id, $desc,$post_array );

				if ( is_wp_error($media_id) ) {
					/* image couldn' be found -- will be removed later */
					@unlink($file_array['tmp_name']);
					//$img->removeAttribute('src');
					$this->trace($image['src']." error: ".$media_id->get_error_messages( )[0]);
				}
				else
				{
					/* image is valid and local */
					$image['url'] = wp_get_attachment_url( $media_id );

					$min_width=$this->pluginOptions['min_width'];
					$min_height=$this->pluginOptions['min_height'];

					list($width, $height)=getimagesize($image['url']);
					$image['width']=$width;
					$image['height']=$height;

					/* ignore unless over a certain width/height */
					if($width>$min_width&&$height>$min_height)
					{
						/* replace old url with new url */
						$img->setAttribute('src',$image['url']);

						$class=$img->getAttribute('class');
						$class=explode(" ",trim($class));
						$class[]="loremblogum-responsive";
						$class=trim(implode(" ",$class));
						$img->setAttribute('class',$class);

						$this->feed_item->image_count++;
						$this->feed->image_count++;
						$hash=md5($image['src']);

						$this->feed->log['feed_items_images'][$hash]=(object)[
						'url'=>$this->feed_item->url,
						'published_at'=>$this->feed_item->published_at,
						'image_no'=>$this->feed_item->image_count,
						'image_url'=>$image['url'],
						'image_src'=>$image['src']
						];

						/* add a featured image when the first image is found */
						if($featured_image===false&&$this->pluginOptions['featured_image']=='Yes')
						{
							/* */
							set_post_thumbnail( $post_id, $media_id );
							$featured_image=true;

							/* */
							$img->parentNode->removeChild($img);
						}		

						/* */
						$found=true;
					}
				}
			}
			
			/* if we didn't get a local copy, remove reference */
			if($found===false)
			{
				/* replace old url with blank */
				$img->parentNode->removeChild($img);
			}
		}
		$this->post_saveHTML();
	}

	/* --------------------------------------------------------------------------- */

	/* get the title */
	private function extractTitle($content)
	{
		/* to UTF8 */
		if (mb_detect_encoding($content, 'UTF-8',true) === true)
		{
			$content=mb_convert_encoding($content, 'html-entities', 'UTF-8');
		}

		/* toEntityDecode */
		if (mb_detect_encoding($content, 'UTF-8',true) === true)
		{ 
			$content=html_entity_decode($content,ENT_QUOTES,"UTF-8");
		}

		$content=$this->convert_smart_quotes(trim($content));

		if(strlen($content)<=2) return;

		$this->dom = new \SmartDOMDocument();
		$this->dom->loadHTML($content);

		$this->feed_item->title_type="";
		$this->feed_item->title_element="";

		$title_ids=explode(",",$this->feed->predefine->title_id);
		$found=false;
		foreach($title_ids as $title_id)
		{
			if($found) break;
			/* */
			list($type,$id)=$this->get_filter_type_id($title_id);

			/* */
			$xpath = new \DOMXpath($this->dom);

			switch($type)
			{
				case 'class':
				$nodeList = $xpath->query("//*[contains(@class, '".$id."')]");
				if(get_class($nodeList)=='DOMNodeList')
				{
					foreach ($nodeList as $node) 
					{
						if(trim($node->nodeValue)!="")
						{
							$this->feed_item->title=$node->nodeValue;
							break;
						}
					}
					if(strlen($this->feed_item->title)<intval($this->min_title))
						$this->feed_item->content="No title: CSS Class $id title short.";
					else
						$found=true;
				}
				break;

				case 'id':
				$node=$this->dom->getElementById($id);
				if(get_class($node)=='DOMElement')
				{
					$this->feed_item->title=$node->nodeValue; 
					if(strlen($this->feed_item->title)<intval($this->min_title))
						$this->feed_item->content="No title: CSS Class $id title short.";
					else
						$found=true;
				}
				break;

				case 'tag':
				$nodeList = $this->dom->getElementsByTagName($id);
				if(get_class($nodeList)=='DOMNodeList')
				{
					foreach ($nodeList as $node)
					{
						if(trim($node->nodeValue)!="")
						{
							$this->feed_item->title=$node->nodeValue;
							break;
						}
					}
					if(strlen($this->feed_item->title)<intval($this->min_title))
						$this->feed_item->content="No title: CSS Class $id title short.";
					else
						$found=true;
				}
				break;
			}
			/* */
			if($found)
			{
				$this->feed_item->title_type=$type;
				$this->feed_item->title_element=$id;
			}

			/* clean the title */
			$this->feed_item->title=trim(wp_strip_all_tags($this->feed_item->title));
			if (mb_detect_encoding($this->feed_item->title, 'UTF-8',true) === true)
			{
				$this->feed_item->title=mb_convert_encoding($this->feed_item->title, 'html-entities', 'UTF-8');
			}
			if (mb_detect_encoding($this->feed_item->title, 'UTF-8',true) === true)
			{ 
				$this->feed_item->title=html_entity_decode($this->feed_item->content,ENT_QUOTES,"UTF-8");
			}
			$this->feed_item->title=$this->convert_smart_quotes($this->feed_item->title);

			$a=explode("\n",$this->feed_item->title);
			if(count($a)>1)
			{
				$this->feed_item->title=$a[0];
			}

		}
	}

	/* --------------------------------------------------------------------------- */

	/* get the conent */
	private function extractContent($raw)
	{
		$this->feed_item->content=$raw;

		/* to UTF8 */
		if (mb_detect_encoding($this->feed_item->content, 'UTF-8',true) === true)
		{
			$this->feed_item->content=mb_convert_encoding($this->feed_item->content, 'html-entities', 'UTF-8');
		}

		/* toEntityDecode */
		if (mb_detect_encoding($this->feed_item->content, 'UTF-8',true) === true)
		{ 
			$this->feed_item->content=html_entity_decode($this->feed_item->content,ENT_QUOTES,"UTF-8");
		}

		$this->feed_item->content=$this->convert_smart_quotes($this->feed_item->content);
		$this->feed_item->content=trim(preg_replace('/\s+/', ' ', $this->feed_item->content));

		$this->dom = new \SmartDOMDocument();
		$this->pre_loadHTML();

		/* */
		$found=false;
		$this->feed_item->article_type="";
		$this->feed_item->article_element="";
		$article_ids=explode(",",$this->feed->predefine->article_id);
		foreach($article_ids as $article_id)
		{
			list($type,$id)=$this->get_filter_type_id($article_id);

			/* */
			$xpath = new \DOMXpath($this->dom);

			$this->feed_item->content="No content.";
			switch($type)
			{
				case 'class':
				$nodeList = $xpath->query("//*[contains(@class, '$id')]");
				if(get_class($nodeList)=='DOMNodeList')
				{
					foreach ($nodeList as $node)
					{
						$this->post_saveHTML($node);
						break;
					}
					if(strlen($this->feed_item->content)<$this->min_content)
						$this->feed_item->content="No content: CSS Class $id content short.";
					else
						$found=true;
				}
				else
				{
					$this->feed_item->content="No content: CSS Class $id not found.";
				}
				break;

				case 'id':
				$node=$this->dom->getElementById($id);
				if(get_class($node)=='DOMElement')
				{
					$this->post_saveHTML($node);
					if(strlen($this->feed_item->content)<$this->min_content)
						$this->feed_item->content="No content: CSS Id $id content short.";
					else
						$found=true;
				}
				else
				{
					$this->feed_item->content="No content: CSS Id $id not found.";
				}
				break;

				case 'tag':
				$nodeList=$this->dom->getElementsByTagName($id);
				if(get_class($nodeList)=='DOMNodeList')
				{
					foreach ($nodeList as $node)
					{
						$this->post_saveHTML($node);
						break;
					}
					if(strlen($this->feed_item->content)<$this->min_content)
						$this->feed_item->content="No content: CSS Tag $id content short.";
					else
						$found=true;
				}
				else
				{
					$this->feed_item->content="No content: CSS Tag $id not found.";
				}
				break;

				default:
				$this->feed_item->content="No content: Article id ".$article_id." not understood (type $type id $id).";
			}

			/* */
			if($found)
			{
				$this->feed_item->article_type=$type;
				$this->feed_item->article_element=$id;
				return;
			}

		}
	}

	/* --------------------------------------------------------------------------- */

	/* */
	private function doCollectedCategories()
	{
		if(!isset($this->feed->collected_categories)||!is_array($this->feed->collected_categories))
			$this->feed->collected_categories=[];
		if(!isset($this->feed_item->categories_array)||!is_array($this->feed_item->categories_array))
			$this->feed_item->categories_array=[];

		$this->feed->collected_categories=array_merge(
			$this->feed->collected_categories,
			$this->feed_item->categories_array
			);
		$this->feed->collected_categories=array_unique($this->feed->collected_categories);
	}

	/* --------------------------------------------------------------------------- */

	/* get post category for post. if returns ->category='' means rejected. */
	private function getPostCategory()
	{
		$default_category=$this->pluginOptions['default_category'];

		$this->categoriesStringToArray();

		$categories=$this->feed_item->categories;

		$this->feed_item->category=$default_category;

		if(count($categories)==0&&count($this->feed->categories_array)==0)
		{
			$this->feed_item->category='';
			//
		}
		else if(count($categories)==0)
		{
			foreach($this->feed->categories_array as $k=>$v)
			{
				if($k=='')
				{
					$this->feed_item->category=$v;
					break;
				}				
			}
		}
		else if(count($this->feed->categories_array)==0)
		{
			//
		}
		/* */
		else
		{
			foreach($this->feed->categories_array as $k=>$v)
			{
				foreach($categories as $cat)
				{
					if($cat==$k||$k=='')
					{
						$this->feed_item->category=$v;
					}
					if($cat==$k)
					{
						break;
					}
				}
			}
		}
	}

	/* --------------------------------------------------------------------------- */

	/* */
	public function cleanCategoriesString($categories_string)
	{
		$a=explode(",",trim($categories_string));
		foreach($a as $k=>$v)
		{
			$a[$k]=trim(strtolower($v));
		}
		return implode(",",$a);
	}

	/* --------------------------------------------------------------------------- */

	/* */
	public function cleanFiltersString($filters_string)
	{
		$a=explode(",",trim($filters_string));
		foreach($a as $k=>$v)
		{
			$a[$k]=trim($v);
		}
		return implode(",",$a);
	}

	/* --------------------------------------------------------------------------- */

	/* */
	public function cleanFeedItemCategories($item_categories)
	{
		$categories=[];

		if(!is_array($item_categories))
		{
			foreach($item_categories as $s)
			{
				$categories[]=(str_replace("\"","",(string)$s));
			}

			foreach($categories as $k=>$v)
			{
				$categories[$k]=trim(strtolower($v));
			}
		}
		$this->feed_item->categories=array_values(array_unique($categories));			
	}

	/* --------------------------------------------------------------------------- */

	/* gets category ids for all categories; creates the category is it doesn't exist.
	categories are stored in an array as feed-category => wp-category. These are translated. */
	private function getMultipleCategories()
	{
		$cat_ids=[];

		foreach($this->feed->categories_array as $feedcat=>$wpcat)
		{
			$slug=sanitize_title($wpcat);
			$category=ucwords($wpcat);

			$cat=get_category_by_slug($slug);

			if($cat===false)
			{
				$category_array = array(
					'cat_name' => $category,
					'category_description' =>$category ,
					'category_nicename' => $slug,
					'category_parent' =>''
					);

				$wp_error=false;
				$id=wp_insert_category( $category_array, $wp_error );
				$cat=get_category_by_slug($slug);
			}

			$cat_ids[]=$cat->cat_ID;
		}

		return $cat_ids;
	}

	/* --------------------------------------------------------------------------- */

	/* */
	public function get_feed_key($feed_id)
	{
		foreach($this->pluginOptions['feeds'] as $key=>$feed)
		{
			$feed=(object)$feed;
			if(property_exists($feed,'feed_id') && $feed->feed_id==$feed_id)
			{
				$this->feed=$feed;
				$this->feed->key=$key;
				return $key;
			}	
		}
		return false;
	}

	/* --------------------------------------------------------------------------- */

	/* */
	public function get_feed_by_id($feed_id=null)
	{
		if($feed_id==null) return false;
		foreach($this->pluginOptions['feeds'] as $key=>$this->feed)
		{
			$this->feed=(object)$this->feed;
			if(is_object($this->feed) 
				&& property_exists($this->feed,'feed_id')
				&& $this->feed->feed_id==$feed_id)
			{
				$this->feed->key=$key;
				return $this->feed;
			}	
		}
		return false;
	}

	/* --------------------------------------------------------------------------- */

	/* */
	public function get_new_feed_id()
	{
		$max=0;
		foreach($this->pluginOptions['feeds'] as $feed)
		{
			if($feed->feed_id>$max) $max=$feed->feed_id;
		}
		if(isset($this->pluginOptions['max_feed_id']))
		{
			if($this->pluginOptions['max_feed_id']>$max) $max=$this->pluginOptions['max_feed_id'];
		}
		$max++;

		$this->pluginOptions['max_feed_id']=$max;
		return $this->pluginOptions['max_feed_id'];
	}

	/* --------------------------------------------------------------------------- */

	/* */
	public function get_feed_id()
	{
		if(!isset($this->pluginOptions['feed_id']))
			$this->pluginOptions['feed_id']=0;
		return $this->pluginOptions['feed_id'];
	}

	/* --------------------------------------------------------------------------- */

	/* */
	public function next_feed_id()
	{
		/* */
		$this->feed_id=$this->pluginOptions['feed_id'];

		/* get list of feed_ids */
		$feeds=[];
		foreach($this->pluginOptions['feeds'] as $key=>$feed)
		{
			$feed=(object)$feed;
			if(property_exists($feed,'feed_id'))
			{
				$feeds[]=$feed->feed_id;
			}
		}
		sort($feeds);

		/* get next feed_id in order */
		$found=false;
		foreach($feeds as $feed_id)
		{
			if($feed_id>$this->feed_id)
			{
				$this->trace("next ".$feed_id." > ".$this->feed_id." ==".implode(",",$feeds));
				$this->feed_id=$feed_id;
				$found=true;
				break;
			}
		}

		/* if we don't find next in order get the first */
		if($found===false)
		{
			foreach($feeds as $feed_id)
			{
				$this->feed_id=$feed_id;
				break;
			}
		}

		/* save */
		$this->pluginOptions['feed_id']=$this->feed_id;
		$this->save_options();

		return $this->feed_id;
	}

	/* --------------------------------------------------------------------------- */

	/* takes the feed->categories string and convert it to an array we store as feed->categories_array. categories_array is just a temporary variable. */
	public function categoriesStringToArray()
	{
		if(!isset($this->feed->categories))
			$this->feed->categories=[];

		$this->feed->categories_array=[];
		if(is_string($this->feed->categories))
		{
			$a=explode(",",$this->feed->categories);
			foreach($a as $i=>$kv)
			{
				$a=explode(":",$kv);
				if(count($a)>=2)
				{
					list($slug,$name)=$a;
					$slug=sanitize_title($slug);
					$this->feed->categories_array[$slug]=trim($name);
				}
			}
		}
	}

	/* --------------------------------------------------------------------------- */

	/* */
	public function categoriesArrayToString()
	{
		if(is_array($this->feed->categories))
		{
			$a=[];
			foreach($this->feed->categories as $k=>$v)
			{
				$a[]="$k:$v";
			}
			$this->feed->categories=implode(",",$a);
		}
	}

	/* --------------------------------------------------------------------------- */

	/* */
	public function filtersStringToArray()
	{
		if(!isset($this->feed->predefine))
			$this->feed->predefine=[];
		if(!isset($this->feed->predefine->filters))
		{
			$this->feed->predefine['filters']=[];
			$this->feed->predefine=(object)$this->feed->predefine;
		}

		$this->feed->predefine->filters_array=[];
		if(is_string($this->feed->predefine->filters))
		{
			$a=explode(",",$this->feed->predefine->filters);
			foreach($a as $k=>$v)
			{
				$this->feed->predefine->filters_array[$k]=trim($v);
			}
		}
	}

	/* --------------------------------------------------------------------------- */

	/* computePredefine */
	public function computePredefine()
	{
		$this->feed->predefine = null;

		$i=0;
		foreach($this->pluginOptions['predefines'] as $predefine)
		{
			$this->feed->log=(array)$this->feed->log;
			$this->feed->log['predefines'][$i]="predefine url_prefix: $predefine->url_prefix";

			if(isset($predefine->url_prefix))
			{
				$slen=strlen($predefine->url_prefix);
				$surl=substr($this->feed_item->url,0,$slen);

				$this->feed->log['predefines'][$i]="predefine url_prefix: $predefine->url_prefix == feed item url: $surl ($slen)";

				if(strtolower($predefine->url_prefix)==strtolower($surl))
				{
					$this->feed->predefine=$predefine;
					$this->feed->log['predefines'][$i].=" MATCH";
					break;
				}
			}
			$i++;
		}
		return $this->feed->predefine;
	}

	/* --------------------------------------------------------------------------- */

	/* */
	public function get_predefine_key($predefine_id=null)
	{
		if($predefine_id==null) return false;
		foreach($this->pluginOptions['predefines'] as $key=>$predefine)
		{		
			$predefine=(object)$predefine;
			if($predefine->predefine_id==$predefine_id)
			{
				return $key;
			}	
		}

		return false;
	}

	/* --------------------------------------------------------------------------- */

	/* */
	public function get_predefine_by_id($predefine_id=null)
	{
		if($predefine_id==null) return false;
		foreach($this->pluginOptions['predefines'] as $key=>$this->feed->predefine)
		{
			$this->feed->predefine=(object)$this->feed->predefine;
			if(is_object($this->feed->predefine)
				&& property_exists($this->feed->predefine,'predefine_id') 
				&& $this->feed->predefine->predefine_id==$predefine_id)
			{
				$this->feed->predefine->key=$key;
				return $this->feed->predefine;
			}	
		}
		return false;
	}

	/* --------------------------------------------------------------------------- */

	/* */
	public function get_new_predefine_id()
	{
		$max=0;
		foreach($this->pluginOptions['predefines'] as $predefine)
		{
			if($predefine->predefine_id>$max) $max=$predefine->predefine_id;
		}
		if(isset($this->pluginOptions['max_predefine_id']))
		{
			if($this->pluginOptions['max_predefine_id']>$max) $max=$this->pluginOptions['max_predefine_id'];
		}
		$max++;
		
		$this->pluginOptions['max_predefine_id']=$max;
		return $this->pluginOptions['max_predefine_id'];
	}

	/* --------------------------------------------------------------------------- */

	/* clean the content */
	public function cleanContentDom()
	{
		$this->pre_loadHTML();

		/* remove javascript */
		$tags = $this->dom->getElementsByTagName('script');
		$length = $tags->length;
		for ($i = $length-1; $i >=0; $i--)
		{
			if(is_object($tags->item($i)->parentNode)) {
				$tags->item($i)->parentNode->removeChild($tags->item($i));
			}
		}
		/* remove javascript */
		$tags = $this->dom->getElementsByTagName('noscript');
		$length = $tags->length;
		for ($i = $length-1; $i >=0; $i--)
		{
			if(is_object($tags->item($i)->parentNode)) {
				$tags->item($i)->parentNode->removeChild($tags->item($i));
			}
		}

		/* remove all style tags */
		while (($tags = $this->dom->getElementsByTagName("style")) 
			&& $tags->length>0) 
		{
			$tags->item(0)->parentNode->removeChild($tags->item(0));
		}

		/* remove all link tags */
		while (($tags = $this->dom->getElementsByTagName("link")) 
			&& $tags->length>0) 
		{
			$tags->item(0)->parentNode->removeChild($tags->item(0));
		}

		/* remove all meta tags */
		while (($tags = $this->dom->getElementsByTagName("meta")) 
			&& $tags->length>0) 
		{
			$tags->item(0)->parentNode->removeChild($tags->item(0));
		}

		/* remove comments */
		$xpath = new \DOMXPath($this->dom);
		foreach ($xpath->query('//comment()') as $comment) {
			$comment->parentNode->removeChild($comment);
		}

		/* remove attributes style */
		$xpath = new \DOMXPath($this->dom);
		$nodeList=$xpath->query('//*[@style]');
		foreach ($nodeList as $node)
		{
			$node->removeAttribute("style");
		}

		/* remove attributes onclick */
		$xpath = new \DOMXPath($this->dom);
		$nodeList=$xpath->query('//*[@onclick]');
		foreach ($nodeList as $node)
		{
			$node->removeAttribute("onclick");
		}

		$this->post_saveHTML();
	}

	/* --------------------------------------------------------------------------- */

	/* */
	public function cleanContentString()
	{
		$this->feed_item->content=$this->convert_smart_quotes($this->feed_item->content);
		$this->feed_item->content=trim($this->feed_item->content);
	}

	/* --------------------------------------------------------------------------- */

	/* convert various kinds of quotes into stock quotes */
	private function convert_smart_quotes($string) 
	{ 
		$quotes = array(
    "\xC2\xAB"     => '"', // « (U+00AB) in UTF-8
    "\xC2\xBB"     => '"', // » (U+00BB) in UTF-8
    "\xE2\x80\x98" => "'", // ‘ (U+2018) in UTF-8
    "\xE2\x80\x99" => "'", // ’ (U+2019) in UTF-8
    "\xE2\x80\x9A" => "'", // ‚ (U+201A) in UTF-8
    "\xE2\x80\x9B" => "'", // ‛ (U+201B) in UTF-8
    "\xE2\x80\x9C" => '"', // “ (U+201C) in UTF-8
    "\xE2\x80\x9D" => '"', // ” (U+201D) in UTF-8
    "\xE2\x80\x9E" => '"', // „ (U+201E) in UTF-8
    "\xE2\x80\x9F" => '"', // ‟ (U+201F) in UTF-8
    "\xE2\x80\xB9" => "'", // ‹ (U+2039) in UTF-8
    "\xE2\x80\xBA" => "'", // › (U+203A) in UTF-8
    chr(151)=>"--"
    );
		return strtr($string, $quotes);
	}

	/* --------------------------------------------------------------------------- */

	/* */
	private function toEntityEncode()
	{
		if (mb_detect_encoding($this->feed_item->content, 'UTF-8',true) === true)
		{ 
			$this->feed_item->content=htmlentities($this->feed_item->content,ENT_QUOTES,"UTF-8");
		}
	}

	/* --------------------------------------------------------------------------- */

	/* load html into dom */
	private function pre_loadHTML()
	{
		if(strlen($this->feed_item->content)<=1)
		{
			$this->dom = new \SmartDOMDocument();
		}
		else
		{
			$this->dom->loadHTML($this->feed_item->content);
		}
		$this->dom->substituteEntities = false;
	}

	/* --------------------------------------------------------------------------- */

	/* save current working dom as html */
	private function post_saveHTML($node=null)
	{
		if($node==null)
			$this->feed_item->content=$this->dom->saveHTML();
		else
			$this->feed_item->content=$this->dom->saveHTML($node);
	}


	/* --------------------------------------------------------------------------- */

	/* strip out specified html */
	private function stripContentDomByFilters()
	{
		if(count($this->feed->predefine->filters_array)==0) return;

		/* */
		foreach($this->feed->predefine->filters_array as $filter)
		{
			$this->pre_loadHTML();
			list($type,$id)=$this->get_filter_type_id($filter);

			$xpath = new \DOMXpath($this->dom);

			switch($type)
			{
				case 'class':
				$nodeList = $xpath->query("//*[contains(@class, '".$id."')]");
				if(get_class($nodeList)=='DOMNodeList')
				{
					foreach ($nodeList as $node) {
						$node->parentNode->removeChild($node);
					}
				}
				break;

				case 'id':
				$node=$this->dom->getElementById($id);
				if(get_class($node)=='DOMElement')
					$node->parentNode->removeChild($node);
				break;

				case 'tag':
				$nodeList=$this->dom->getElementsByTagName($id);
				if(get_class($nodeList)=='DOMNodeList')
				{
					foreach ($nodeList as $node) {
						$node->parentNode->removeChild($node);
					}
				}
				break;
			}
			$this->post_saveHTML();
		}
	}

	/* --------------------------------------------------------------------------- */

	/* extract type/id for css */
	private function get_filter_type_id($filter)
	{
		$c=substr($filter,0,1);
		if($c=='.')
		{
			$filter_type="class";
			$filter_id=substr($filter,1);
		}
		else if($c=='#')
		{
			$filter_type="id";
			$filter_id=substr($filter,1);
		}
		else
		{
			$filter_type="tag";
			$filter_id=$filter;
		}
		return [$filter_type,$filter_id];
	}

	/* --------------------------------------------------------------------------- */

	/* delete cache/tranisents */
	public function removeCache()
	{	
		/* purge transients */
		global $wpdb;
		$expired = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_loremblogum%';" );
		foreach( $expired as $transient ) {
			$key = str_replace('_transient_', '', $transient);
			delete_transient($key);
		}
	}

	/* --------------------------------------------------------------------------- */

	/* delete posts and thier meta data */
	public function removePosts()
	{
		/* purge posts */
		$args = [
		'post_type'        => $this->pluginOptions['post_type'],
		'posts_per_page'   => $this->pluginOptions['maximum_remove_posts_per_call'],
		'orderby'          => 'date',
		'order'           => 'ASC',
		'post_status'	=> array('draft','publish','trash','revision')
		];

		$posts = get_posts( $args );
		echo '<ul class="remove-posts">';
		foreach ( $posts as $post )
		{ 
			setup_postdata( $post ); 
			$url = get_post_meta( $post->ID, LOREMBLOGUM_META_URL, true );
			$feed_id = get_post_meta( $post->ID, LOREMBLOGUM_META_ID, true );
			if(strlen($url)>0||strlen($feed_id)>0)
			{
				echo "<li>(<b>".$post->ID."</b>) ".get_the_title( $post->ID )." - <b>Feed id:</b> $feed_id Url:$url <b>Post type:</b> ".get_post_type($post->ID)."";

				$images = get_attached_media( 'image', $post->ID );
				echo '<ul class="remove-posts-images">';
				foreach($images as $image)
				{
					wp_delete_attachment( $image->ID,true);
					echo "<li>(<b>".$image->ID."</b>) ".get_the_title( $image->ID )."</li>";
				}
				echo '</ul>';
				echo '</li>';

				wp_delete_post($post->ID,true);

				//delete_post_meta( $post->ID, LOREMBLOGUM_META_URL);
				//delete_post_meta( $post->ID, LOREMBLOGUM_META_ID);

				global $wpdb;
				$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE post_id=".$post->ID.";" );
			}
		}
		echo '</ul>';
		wp_reset_postdata();

		/* */
		$this->load_options();
		$this->pluginOptions['feed']['log']=[
		'feed_items'=>[]
		];

		/* */
		foreach($this->pluginOptions['feeds'] as $key=>$feed)
		{
			if(isset($feed->feed_id))
			{
				if(is_numeric($key))
				{
					$this->pluginOptions['feeds'][$key]->log=[
					'feed_items'=>[]
					];
				}
			}
		}

		/* */
		$this->save_options();
	}

	/* --------------------------------------------------------------------------- */

	/* gathers feeds/predefines as savable/emailable data */
	public function getBackupData()
	{	
		global $wpdb;

		$feeds=(array)array_values($this->pluginOptions['feeds']);
		foreach($feeds as $key=>$this->feed)
		{
			$feeds[$key]=(object)$this->feed;
		}

		$predefines=(array)array_values($this->pluginOptions['predefines']);

		$data=[];
		$data['feeds']=$feeds;
		$data['predefines']=$predefines;

		return $data;
	}

	/* --------------------------------------------------------------------------- */

	/* import json or array as feed/predefines data */
	public function importBackupData($data)
	{	
		global $wpdb;

		if(!is_array($data))
		{
			$data=json_decode($data);
		}

		if(isset($data->feeds))
		{
			$this->pluginOptions['feeds']=$data->feeds;
		}

		if(isset($data->predefines))
		{
			$this->pluginOptions['predefines']=$data->predefines;
		}

		$this->save_options();
	}

	/* --------------------------------------------------------------------------- */

}

?>