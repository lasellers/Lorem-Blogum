<?php
/*

@author: Lewis A. Sellers <lasellers@gmail.com>
@date: 6/2015
*/

/* */
function loremblogum_getorpost($parm,$default='')
{
	if(isset($_POST[$parm])) return trim($_POST[$parm]);
	if(isset($_GET[$parm])) return trim($_GET[$parm]);
	return $default;
}

/* */
function loremblogum_save_feed_callback() {
	global $wpdb;

	$lorem=new oremblogum();

	$feed_id = loremblogum_getorpost('feed_id','');
	$title = loremblogum_getorpost('title','');
	$url = loremblogum_getorpost('url','');
	$categories = loremblogum_getorpost('categories','');

	$key=$lorem->get_feed_key($feed_id);
	if(!($key===false))
	{
		$lorem->pluginOptions['feeds'][$key]->feed_id=$feed_id;
		$lorem->pluginOptions['feeds'][$key]->title=$title;
		$lorem->pluginOptions['feeds'][$key]->url=$url;
		$lorem->pluginOptions['feeds'][$key]->categories=$lorem->cleanCategoriesString($categories);
		$lorem->pluginOptions['feeds']=array_values($lorem->pluginOptions['feeds']);
		$lorem->save_options();
	}

	$cache_id="feed-".$feed_id;
	delete_transient($cache_id);

	$data=[];
	$data['success']='true';
	$data['feed']=$lorem->pluginOptions['feeds'][$key];
	echo json_encode($data);
	wp_die();
}
add_action( 'wp_ajax_save_feed', 'loremblogum_save_feed_callback' );

/* */
function loremblogum_getCharacterOffsetOfDifference($str1, $str2, $encoding = 'UTF-8') {
	return mb_strlen(
		mb_strcut(
			$str1,
			0, strspn($str1 ^ $str2, "\0"),
			$encoding
			),
		$encoding
		);
}

/* 
 http://192.168.1.28/wp-admin/admin-ajax.php?action=get_feeds 
*/
 function loremblogum_get_feeds_callback() {
 	$lorem=new loremblogum();

 	if(!isset($lorem->pluginOptions['feeds'])) 
 		$lorem->pluginOptions['feeds']=[];
 	if(!isset($lorem->pluginOptions['predefines'])) 
 		$lorem->pluginOptions['predefines']=[];

 	$feeds=(array)array_values($lorem->pluginOptions['feeds']);
 	foreach($feeds as $key=>$lorem->feed)
 	{
 		$feeds[$key]=(object)$lorem->feed;
 	}

 	/* compute the common base url for reference */
 	foreach($feeds as $i=>$feed)
 	{
 		if(!is_array($feed->log))
 		$feed->log=(array)$feed->log;

 		$urls=$feed->log['feed_items_url'];
 		$base_url="";
 		if(count($urls)>=2)
 		{
 			$len=loremblogum_getCharacterOffsetOfDifference($urls[0],$urls[1]);
 			$base_url=mb_strcut($urls[0],0,$len);
 		}
 		$feed->log['base_url']=$base_url;
 		$feeds[$i]=$feed;
 	}

 	/* return data structure */
 	$data=[];
 	$data['success']='true';
 	$data['feeds']=$feeds;
 	echo json_encode($data);
 	wp_die();
 }
 add_action( 'wp_ajax_get_feeds', 'loremblogum_get_feeds_callback' );


 /* */
 function loremblogum_add_feed_callback() {
 	$lorem=new loremblogum();

 	$feed_id=$lorem->get_new_feed_id();

 	$pluginOptions = get_option(LOREMBLOGUM_DATA);
 	$feeds=$lorem->pluginOptions['feeds'];

 	$feed=(object)[
 	'feed_id'=>$feed_id,
 	'title'=>'New',
 	'url'=>'',
 	'categories'=>'',
 	'published_at'=>'',
 	'collected_categories'=>'',
 	'key'=>'',
 	'log'=>[],
 	'categories_array'=>[],
 	'image_count'=>0
 	];
 	$feeds[]=$feed;

 	$lorem->pluginOptions['feeds']=$feeds;
 	$lorem->save_feed();

 	$cache_id="feed-".$feed_id;
 	delete_transient($cache_id);

 	$data=[];
 	$data['success']='true';
 	$data['feed']=$feed;
 	echo json_encode($data);
 	wp_die();
 }
 add_action( 'wp_ajax_add_feed', 'loremblogum_add_feed_callback' );


 /* */
 function loremblogum_delete_feed_callback() {
 	$lorem=new loremblogum();

 	$feed_id = loremblogum_getorpost('feed_id','');
 	foreach($lorem->pluginOptions['feeds'] as $key=>$feed)
 	{
 		$feed=(object)$feed;
 		if(is_object($feed) && property_exists($feed,'feed_id'))
 		{
 			if($feed->feed_id==$feed_id)
 			{
 				unset($lorem->pluginOptions['feeds'][$key]);
 				break;
 			}
 		}
 	}
 	$lorem->save_feed();

 	$cache_id="feed-".$feed_id;
 	delete_transient($cache_id);

 	$data=[];
 	$data['success']='true';
 	$data['feed']=$feed;
 	$data['predefine']=$lorem->predefine;
 	echo json_encode($data);
 	wp_die();
 }
 add_action( 'wp_ajax_delete_feed', 'loremblogum_delete_feed_callback' );


/*

Pulls new RSS from one feed at a time. If feed_id not specified then it uses an
internal copy of the feed_id var that it will incremeant and loop through the feeds
list with given successive calls.

*/
function loremblogum_fetch_feed_callback() {
	$lorem=new loremblogum();

	$feed_id = loremblogum_getorpost('feed_id','');
	if(!is_numeric($feed_id))
	{
		if(!isset($lorem->pluginOptions['feed_id'])) $lorem->pluginOptions['feed_id']=0;
		$feed_id=$lorem->pluginOptions['feed_id'];
	}

	$key=$lorem->get_feed_key($feed_id);
	if($key===false)
	{
		echo json_encode(['error'=>'feed_id '.$feed_id.' ('.$key.') does not exist.']);
		wp_die(); 
	}

	$count=$lorem->fetch($feed_id);
	$lorem->save_feed();

	$lorem->pluginOptions['feed_id']=$feed_id;
	$lorem->save_options();

	$data=[];
	if($count==0)
		$data['error']='No items';
	else
		$data['success']='true';
	$data['count']=$count;
	$data['feed']=$lorem->feed;
	echo json_encode($data);
	wp_die();
}
add_action( 'wp_ajax_fetch_feed', 'loremblogum_fetch_feed_callback' );



/* */
function loremblogum_save_predefine_callback() {
	global $wpdb;

	$lorem=new loremblogum();

	$predefine_id = loremblogum_getorpost('predefine_id','');
	$title = loremblogum_getorpost('title','');
	$url_prefix = loremblogum_getorpost('url_prefix','');
	$filters = loremblogum_getorpost('filters','');
	$title_id = loremblogum_getorpost('title_id','');
	$article_id = loremblogum_getorpost('article_id','');

	$key=$lorem->get_predefine_key($predefine_id);
	if(!($key===false))
	{
		$lorem->pluginOptions['predefines'][$key]->predefine_id=$predefine_id;
		$lorem->pluginOptions['predefines'][$key]->title=$title;
		$lorem->pluginOptions['predefines'][$key]->url_prefix=$url_prefix;
		$lorem->pluginOptions['predefines'][$key]->filters=$lorem->cleanFiltersString($filters);
		$lorem->pluginOptions['predefines'][$key]->title_id=$title_id;
		$lorem->pluginOptions['predefines'][$key]->article_id=$article_id;

		$lorem->pluginOptions['predefines']=array_values($lorem->pluginOptions['predefines']);
		$lorem->save_options(); 
	}

	$cache_id="predefine-".$predefine_id;
	delete_transient($cache_id);

	$data=[];
	$data['success']='true';
	$data['key']=$key;
	$data['predefine']=$lorem->pluginOptions['predefines'][$key];	
	echo json_encode($data);
	wp_die();
}
add_action( 'wp_ajax_save_predefine', 'loremblogum_save_predefine_callback' );

/* 
 http://192.168.1.28/wp-admin/admin-ajax.php?action=get_predefines 
*/
 function loremblogum_get_predefines_callback() {
 	$lorem=new loremblogum();

 	if(!isset($lorem->pluginOptions['predefines'])) {
 		$lorem->pluginOptions['predefines']=[];
 		$lorem->save_predefine();
 	}

 	$data=[];
 	$data['success']='true';
 	$data['predefines']=(array)array_values($lorem->pluginOptions['predefines']);
 	echo json_encode($data);
 	wp_die();
 }
 add_action( 'wp_ajax_get_predefines', 'loremblogum_get_predefines_callback' );


 /* */
 function loremblogum_add_predefine_callback() {
 	$lorem=new loremblogum();

 	$predefine_id=$lorem->get_new_predefine_id();

 	$predefines=$lorem->pluginOptions['predefines'];

 	$predefine=(object)[
 	'predefine_id'=>$predefine_id,
 	'title'=>'New',
 	'url_prefix'=>'http://yourfeed.com',
 	'filters'=>'h1,header,footer,.shares',
 	'title_id'=>'h1',
 	'article_id'=>'article',
 	'published_at'=>'',
 	'filters_array'=>[],
 	];
 	$predefines[]=$predefine;

 	$lorem->pluginOptions['predefines']=$predefines;
 	$lorem->save_predefine();

 	$cache_id="predefine-".$predefine_id;
 	delete_transient($cache_id);

 	$data=[];
 	$data['success']='true';
 	$data['predefine']=(array)$predefine;
 	echo json_encode($data);
 	wp_die();
 }
 add_action( 'wp_ajax_add_predefine', 'loremblogum_add_predefine_callback' );


 /* */
 function loremblogum_delete_predefine_callback() {
 	$lorem=new loremblogum();

 	$predefine_id = loremblogum_getorpost('predefine_id','');
 	foreach($lorem->pluginOptions['predefines'] as $key=>$predefine)
 	{
 		$predefine=(object)$predefine;
 		if(is_object($predefine) && property_exists($predefine,'predefine_id'))
 		{
 			if($predefine->predefine_id==$predefine_id)
 			{
 				unset($lorem->pluginOptions['predefines'][$key]);
 				break;
 			}
 		}
 	}
 	$lorem->save_predefine();

 	$cache_id="predefine-".$predefine_id;
 	delete_transient($cache_id);

 	$data=[];
 	$data['success']='true';
 	$data['predefine']=(array)$predefine;
 	echo json_encode($data);
 	wp_die();
 }
 add_action( 'wp_ajax_delete_predefine', 'loremblogum_delete_predefine_callback' );



 /* */
 function loremblogum_get_backup_data_callback() {
 	$lorem=new loremblogum();
 	echo json_encode($lorem->getBackupData());
 	wp_die();
 }
 add_action( 'wp_ajax_get_backup_data', 'loremblogum_get_backup_data_callback' );


 /* */
 function loremblogum_import_backup_data_callback() {
 	$lorem=new loremblogum();
 	$backup = loremblogum_getorpost('backup','');
 	$lorem->importBackupData($backup);
 	$data=['success'=>'Backup restored'];
 	echo json_encode($data);
 	wp_die();
 }
 add_action( 'wp_ajax_import_backup_data', 'loremblogum_import_backup_data_callback' );

 ?>