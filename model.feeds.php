<?php
/*

@author: Lewis A. Sellers <lasellers@gmail.com>
@date: 6/2015
*/
class LoremBlogumFeeds 
{
	protected $primaryKey = 'feed_id';

	protected $fillable = array(
		'type',
		'feed_id',
		'title',
		'url',
		'categories',
		'categories_array',
		'published_at',
		'collected_categories',
		'image_count',
		'predefine',
		'key',
		'log'
		);

	function __construct() {
		$this->check_fields_exist();
	}

	/* */
	public function check_fields_exist() {
		if(!isset($this->collected_categories))
			$this->collected_categories=[];
		foreach($this->fillable as $name)
		{
			if(!isset($this->$name))
				$this->$name="";
		}
	}

	/* */
	public function check_fields_exist_on_object($feed) {
		if(!isset($feed->collected_categories))
			$feed->collected_categories=[];
		if(!isset($feed->categories_array))
			$feed->categories_array=[];
		if(!isset($feed->log))
		{
			$feed->log=[];
		}
		$feed->log=(array)$feed->log;

		if(!isset($feed->log['feed_items']))
		{
			$feed->log['feed_items']=[];
		}
		foreach($this->fillable as $name)
		{
			if(!isset($feed->$name))
				$feed->$name="";
		}
		return $feed;
	}

	/* */
	public function save()
	{
	
	}
}

?>