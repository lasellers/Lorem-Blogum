<?php
/*

@author: Lewis A. Sellers <lasellers@gmail.com>
@date: 6/2015
*/
class LoremBlogumPredefines 
{
	protected $primaryKey = 'predefine_id';

	protected $fillable = array(
		'type',
		'predefine_id',
		'url_prefix',
		'title',
		'filters',
		'filters_array',
		'title_id',
		'article_id',
		);

	function __construct() {
		$this->check_fields_exist();
	}

	/* */
	public function check_fields_exist() {
		foreach($this->fillable as $name)
		{
			if(!isset($this->$name)) 
				$this->$name="";
		}
	}

	/* */
	public function check_fields_exist_on_object($predefine) {
		if(!isset($predefine->filters_array))
			$predefine->filters_array=[];
		foreach($this->fillable as $name)
		{
			if(!isset($predefine->$name)) 
				$predefine->$name="";
		}
		return $predefine;
	}

	/* */
	public function save()
	{

	}
}

?>