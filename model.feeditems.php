<?php
/*

@author: Lewis A. Sellers <lasellers@gmail.com>
@date: 6/2015
*/
class LoremBlogumFeedItems
{
	protected $primaryKey = 'item_id';

	protected $fillable = array(
		'item_id',
		'feed_id',
		'categories',
		'title',
		'published_at',
		'post_id',
		'category',
		'image_count',
		'content',
		'title_type',
		'title_element',
		'article_type',
		'article_element'
		);

	function __construct() {
		$this->check_fields_exist();
		$this->title="Title not found";
		$this->content="Content not found";
	}

	public function check_fields_exist() {
		foreach($this->fillable as $name)
		{
			if(!isset($this->$name)||strlen($this->$name)==0)
			$this->$name="";
		}
	}

	public function save()
	{

	}

}

?>