/*

startup on page load ... render and populate as needed

@author: Lewis A. Sellers <lasellers@gmail.com>
@date: 6/2015
*/



jQuery( document ).ready(function( $ ) {

	if($( "#loremblogum_settings" ).length>0) 
	{
		var hash = window.location.hash;
		if(hash=="")
		{
			var hash=loremblogum_getParameterByName('hash');
			if(hash==null||hash=="") hash="#tabs-1"; else hash="#"+hash;
		}
		switch_tab($,hash);

		$("#tabs ul a").on("click",function(e) {
			e.preventDefault();
			var href = $(this).attr("href");
			switch_tab($,href);
		});

		if ( $( "#feeds" ).length ) {
			loremblogum_getFeeds($,function(feeds) {
			});
			loremblogum_getPredefines($,function(predefines) {
			});
		}

		else if ( $( "#predefines" ).length ) {
			loremblogum_getFeeds($,function(feeds) {
			});
			loremblogum_getPredefines($,function(predefines) {
			});
		}

		$(".close").on("click",function(e) {
			e.preventDefault();
			$(".close-container").hide();
		});

	}

});

/* handle clicking on settings tabs, switch to different views */
function switch_tab($,hash)
{
	if($( "#loremblogum_settings" ).length==0) return;

	var i=hash.indexOf("#");
	if(!(i==-1))
	{
		hash = hash.substr(i+1);
	}

	console.log("switch_tab "+hash);

	window.history.pushState('hash', hash, '?page=loremblogum-settings&hash='+hash+'#'+hash+'');

	var LOREMBLOGUM_TAB_COUNT=8;
	for(var i=1;i<=LOREMBLOGUM_TAB_COUNT;i++)
	{
		$("#tabs-"+i).hide();
	}
	$("#"+hash).show();
	if(hash=="tabs-1") loremblogum_renderFeeds($);
	if(hash=="tabs-2") renderPredefines($);
}

/* handles the rotating "loading" graphic that shows up when we're waiting on data... */
var loremblogum_ajax_effects={

	start_loading: function($,callback) 
	{
		$("#feeds").html("<tr><td colspan=8>Refreshing...</td></tr>");
		$("#loading").html('<i class="fa fa-refresh fa-spin fa-4x"></i><br> Loading feeds...');
		$("#loading").show();
		if(typeof callback === 'function')
			callback();
	},

	done_loading: function($,callback) 
	{
		$("#loading").hide();
		if(typeof callback === 'function')
			callback();
	},

}

/* get list of all feeds */
var loremblogum_getFeeds = function($,callback)
{
	var data = {
		'action': 'get_feeds'
	};

	console.log("loremblogum_getFeeds");

	loremblogum_ajax_effects.start_loading($);

	$.post(ajaxurl, data, function(response) {
		var obj=JSON.parse(response);
		var feeds=obj.feeds;

		localStorage.setItem("feeds",JSON.stringify(feeds));

		loremblogum_ajax_effects.done_loading($);

		loremblogum_renderFeeds($);
		if(typeof callback === 'function')
			callback(feeds);
	});
}	

/* actually fetch a feed while we wait... */
var loremblogum_refreshFeed = function($,feed_id,callback)
{
	console.log("loremblogum_refreshFeed "+feed_id);

	var data = {
		'action': 'fetch_feed',
		'feed_id': feed_id
	};

	loremblogum_ajax_effects.start_loading($);

	$.post(ajaxurl, data, function(response) {
		var obj=JSON.parse(response);
		loremblogum_ajax_effects.done_loading($);

		loremblogum_getFeeds($,function() {
			if(typeof callback === 'function')
				callback(feeds);
		});
	});

}

/*  render the feed data for the settings panel */

var loremblogum_renderFeeds = function($,callback)
{
	console.log("loremblogum_renderFeeds ");

	var output='';
	output+='<tr>';
	output+='<th><a class="add_feed"><span class="glyphicon glyphicon-refresh"></span></a></th>';
	output+='<th><a class="add_feed"><span class="glyphicon glyphicon-plus"></span></a></th>';
	output+='<th><a href="?page=loremblogum-settings&sort=id">id</a></th>';
	output+='<th><a href="?page=loremblogum-settings&sort=url">Url</a></th>';
	output+='<th><a href="?page=loremblogum-settings&sort=title">Title</a></th>';
	output+='<th><a href="?page=loremblogum-settings&sort=categories">Categories</a></th>';
	output+='</tr>';
	$("#feeds-fields").html(output);
	$("#feeds-fields2").html(output);

	var feeds=JSON.parse(localStorage.getItem('feeds'));
	var predefines=JSON.parse(localStorage.getItem('predefines'));

	if(typeof(feeds)!=undefined&&feeds.length>0)
	{
		var output="";
		for(var i in feeds)
		{
			var feed=feeds[i];
			var feed_id=feed.feed_id;

			console.log("feed["+i+"]");
			console.log(feed);

			output+='<tr id="feed-'+feed_id+'" class="feed-'+feed_id+'">';

			output+='<td><a data-feed_id="'+feed_id+'" class="refresh_feed"><span class="glyphicon glyphicon-refresh"></span></a></td>';
			output+='<td><a data-feed_id="'+feed_id+'" class="feed_edit" data-title="'+feed.title+'" data-url="'+feed.url+'" data-categories="'+feed.categories+'" data-predefine_id="'+feed.predefine_id+'"><span class="glyphicon glyphicon-pencil"></span></a></td>';
			output+='<td><small>'+feed.feed_id+'</small></td>';

			output+='<td class="clip"><a href="'+feed.url+'">'+feed.url+'</a></td>';
			output+='<td class="clip">'+feed.title+'</td>';

			if(Array.isArray(feed.categories))
				output+='<td class="clip">'+feed.categories.join()+'</td>';
			else
				output+='<td class="clip">'+feed.categories+'</td>';

			output+='<td><a class="delete_feed" data-feed_id="'+feed.feed_id+'"><span class="glyphicon glyphicon-trash"></span></a></td>';

			output+='</tr>';

			output+='<tr class="feed-'+feed_id+'">';

			output+='<td class="clip" colspan=9>';

			if(typeof(feed.predefine)!='undefined')
			{
				var predefine=feed.predefine;
				console.log("feed.predefine=");
				console.log(predefine);
				output+=' <b>Predefine:</b> ';
				if(predefine!=null&&predefine.hasOwnProperty('title'))
				{
					output+=predefine.title;
				}
				else
				{
					output+="[No match]";
				}
			}

			if(feed.log!=undefined)
			{
				if(feed.log.base_url!=undefined)
					output+=' <b>Base url:</b> <em>'+feed.log.base_url+'</em>';

				if(feed.log.feed_item_count!=undefined)
					output+=' <b>Feed Items:</b> '+feed.log.feed_item_count;

				if(feed.log.image_count!=undefined)
					output+=' <b>Feed images:</b> '+feed.log.image_count;

				if(feed.log.date!=undefined)
					output+=' <b>Last log:</b> '+feed.log.date;
			}


			if(Array.isArray(feed.collected_categories)&&feed.collected_categories.length>0)
				output+='<br><b>Collected categories:</b> '+feed.collected_categories.join()+'<br>';

			output+='</td>';

			output+='</tr>';
		}

		$("#feeds").html(output);
	}
	else
	{
		$("#feeds").html("<tr><td><p>There is no data. ("+feeds.length+")</td></tr>");
	}

	loremblogum_enable_renderFeedsUI($);

	if(typeof callback === 'function')
		callback();
}

/* */
var loremblogum_saveFeed = function($,feed_id, callback)
{
	console.log("loremblogum_saveFeed "+feed_id);

	var title=$("#edit_title").val();
	var url=$("#edit_url").val();
	var categories=$("#edit_categories").val();
	
	var data = {
		'action': 'save_feed',
		'feed_id': feed_id,
		'title': title,
		'url': url,
		'categories': categories
	};

	$.post(ajaxurl, data, function(response) {
		var obj=JSON.parse(response);
		
		if(typeof callback === 'function')
			callback();
	});
}

var loremblogum_deleteFeed = function($,feed_id,callback)
{
	var data = {
		'action': 'delete_feed',
		'feed_id': feed_id,
	};

	console.log("loremblogum_deleteFeed "+feed_id);

	$.post(ajaxurl, data, function(response) {
		var obj=JSON.parse(response);

		$("#feed-"+feed_id).hide("slow");
		$(".feed-"+feed_id).hide("slow");
		if(typeof callback === 'function')
			callback();
	});
}

/* */
var loremblogum_addFeed = function($,callback)
{
	var data = {
		'action': 'add_feed'
	};

	console.log("loremblogum_addFeed ");

	$.post(ajaxurl, data, function(response) {
		var obj=JSON.parse(response);

		if(typeof callback === 'function')
			callback();
	});
}


/* */
var loremblogum_enable_renderFeedsUI = function($)
{
	console.log("loremblogum_enable_renderFeedsUI ");

	/* */
	$(".delete_feed").on("click",function(e) {
		e.preventDefault();
		var feed_id=$(this).data("feed_id");
		loremblogum_deleteFeed($,feed_id, function() {
			loremblogum_getFeeds($);
		});
	});

	/* */
	$(".add_feed").on("click",function(e) {
		e.preventDefault();
		loremblogum_addFeed($, function() {
			loremblogum_getFeeds($);
		});
	});	

	/* */
	$(".refresh_feed").on("click",function(e) {
		e.preventDefault();
		var feed_id=$(this).data("feed_id");
		loremblogum_refreshFeed($, feed_id,function() {
			loremblogum_getFeeds($);
		});
	});	

	/* */
	$(".feed_edit").on("click",function(e) {
		e.preventDefault();
		var feed_id=$(this).data('feed_id');
		var title=$(this).data('title');
		var url=$(this).data('url');
		var categories=$(this).data('categories');
		var predefine_id=$(this).data('predefine_id');

		feeds=JSON.parse(localStorage.getItem('feeds'));

		loremblogum_renderFeeds($,function() {
			$("#feed-"+feed_id+" td:nth-child(3)").html('<a data-feed_id="'+feed_id+'" class="refresh_feed"><span class="glyphicon glyphicon-refresh"></span></a>');

			$("#feed-"+feed_id+" td:nth-child(4)").html('<input class="edit_feed_field" name="edit_url" id="edit_url" data-feed_id="'+feed_id+'" placeholder="Ex: http://site.com/rss" value="'+url+'">');

			$("#feed-"+feed_id+" td:nth-child(5)").html('<input class="edit_feed_field" name="edit_title" id="edit_title" data-feed_id="'+feed_id+'" placeholder="Ex: My feed" value="'+title+'">');

			$("#feed-"+feed_id+" td:nth-child(6)").html('<input class="edit_feed_field" name="edit_categories" id="edit_categories" data-feed_id="'+feed_id+'" placeholder="Ex: apple:Tech,android:Tech,cats:Humor" value="'+categories+'">');

			/* */
			$('.edit_feed_field').bind("enterKey",function(e){
				e.preventDefault();

				var feed_id=$(this).data('feed_id');

				var url=$("#edit_url").val();
				if(url.length>3)
				{
					loremblogum_saveFeed($, feed_id, function() {
						loremblogum_getFeeds($);
					});
				}
			});
			$('.edit_feed_field').keyup(function(e){
				e.preventDefault();
				if(e.keyCode == 13)
				{
					$(this).trigger("enterKey");
				}
			});

		});
});
}


/* */
var loremblogum_getPredefines = function($,callback)
{
	var data = {
		'action': 'get_predefines'
	};

	console.log("loremblogum_getPredefines ");

	loremblogum_ajax_effects.start_loading($);

	$.post(ajaxurl, data, function(response) {
		var obj=JSON.parse(response);
		var predefines=obj.predefines;

		localStorage.setItem("predefines",JSON.stringify(predefines));

		loremblogum_ajax_effects.done_loading($);

		renderPredefines($);
		if(typeof callback === 'function')
			callback(predefines);
	});
}	


/* */
var renderPredefines = function($,callback)
{
	console.log("renderPredefines ");

	var output='';
	output+='<tr>';
	output+='<th><a class="add_predefine"><span class="glyphicon glyphicon-refresh"></span></a></th>';
	output+='<th><a class="add_predefine"><span class="glyphicon glyphicon-plus"></span></a></th>';
	output+='<th><a href="?sort=id">id</a></th>';
	output+='<th><a href="?sort=title">Title</a></th>';
	output+='<th><a href="?sort=url_prefix">Url Prefix</a></th>';
	output+='<th><a href="?sort=filters">Strip CSS element</a></th>';
	output+='<th><a href="?sort=title_id">Title<br>element</a></th>';
	output+='<th><a href="?sort=article_id">Article<br>element</a></th>';
	output+='</tr>';
	$("#predefines-fields").html(output);
	$("#predefines-fields2").html(output);

	var predefines=JSON.parse(localStorage.getItem('predefines'));

	if(typeof(predefines)!=undefined&&predefines.length>0)
	{
		var output="";
		for(var i in predefines)
		{
			var predefine=predefines[i];
			var predefine_id=predefine.predefine_id;
			var filters_array=predefine.filters.split(",");
			console.log(" ** predefine["+i+"]");
			console.log(predefine);

			output+='<tr id="predefine-'+predefine_id+'" class="predefine-'+predefine_id+'">';
			output+='<td><a data-predefine_id="'+predefine_id+'" class="refresh_predefine"><span class="glyphicon glyphicon-refresh"></span></a></td>';

			output+='<td><a data-predefine_id="'+predefine_id+'" class="predefine_edit" data-title="'+predefine.title+'" data-url_prefix="'+predefine.url_prefix+'" data-filters="'+predefine.filters+'" data-title_id="'+predefine.title_id+'" data-article_id="'+predefine.article_id+'"><span class="glyphicon glyphicon-pencil"></span></a></td>';

			output+='<td><small>'+predefine.predefine_id+'</small></td>';

			output+='<td class="clip">'+predefine.title+'</td>';
			output+='<td class="clip">'+predefine.url_prefix+'</td>';

			console.log(filters_array);

			if(Array.isArray(filters_array))
				output+='<td class="clip">'+filters_array.join(", ")+'</td>';
			else
				output+='<td class="clip">[None]</td>';

			output+='<td class="clip">'+predefine.title_id+'</td>';
			output+='<td class="clip">'+predefine.article_id+'</td>';

			output+='<td><a class="delete_predefine" data-predefine_id="'+predefine.predefine_id+'"><span class="glyphicon glyphicon-trash"></span></a></td>';
			output+='</tr>';
		}

		$("#predefines").html(output);
	}
	else
	{
		$("#predefines").html("<tr><td><p>There is no data. ("+predefines.length+")</td></tr>");
	}

	loremblogum_enableRenderPredefinesUI($);

	if(typeof callback === 'function')
		callback();
}

/* */
var loremblogum_savePredefine = function($,predefine_id, callback)
{
	console.log("loremblogum_savePredefine "+predefine_id);

	var title=$("#edit_predefine_title").val();
	var url_prefix=$("#edit_predefine_url_prefix").val();
	var filters=$("#edit_predefine_filters").val();
	var title_id=$("#edit_predefine_title_id").val();
	var article_id=$("#edit_predefine_article_id").val();

	var data = {
		'action': 'save_predefine',
		'predefine_id': predefine_id,
		'title': title,
		'url_prefix': url_prefix,
		'filters': filters,
		'title_id': title_id,
		'article_id': article_id,
	};

	$.post(ajaxurl, data, function(response) {
		var obj=JSON.parse(response);
		
		if(typeof callback === 'function')
			callback();
	});

}

/* */
var loremblogum_deletePredefine = function($,predefine_id,callback)
{

	var data = {
		'action': 'delete_predefine',
		'predefine_id': predefine_id,
	};

	console.log("loremblogum_deletePredefine "+predefine_id);

	$.post(ajaxurl, data, function(response) {
		var obj=JSON.parse(response);

		$("#predefine-"+predefine_id).hide("slow");
		$(".predefine-"+predefine_id).hide("slow");
		if(typeof callback === 'function')
			callback();
	});

}

/* */
var loremblogum_addPredefine = function($,callback)
{
	var data = {
		'action': 'add_predefine'
	};

	console.log("loremblogum_addPredefine ");

	$.post(ajaxurl, data, function(response) {
		var obj=JSON.parse(response);
		if(typeof callback === 'function')
			callback();
	});

}


/* */
var loremblogum_enableRenderPredefinesUI = function($)
{
	console.log("loremblogum_enableRenderPredefinesUI ");

	/* */
	$(".delete_predefine").on("click",function(e) {
		e.preventDefault();
		var predefine_id=$(this).data("predefine_id");
		loremblogum_deletePredefine($,predefine_id, function() {
			loremblogum_getPredefines($,function() {
				loremblogum_ajax_effects.done_loading($);
				//renderPredefines($);
			});
		});
	});

	/* */
	$(".add_predefine").on("click",function(e) {
		e.preventDefault();
		loremblogum_addPredefine($, function() {
			loremblogum_getPredefines($,function() {
				loremblogum_ajax_effects.done_loading($);
				//renderPredefines($);
			});
		});
	});	

	/* */
	$(".predefine_edit").on("click",function(e) {
		e.preventDefault();
		var predefine_id=$(this).data('predefine_id');
		var url_prefix=$(this).data('url_prefix');
		var title=$(this).data('title');
		var filters=$(this).data('filters');
		var title_id=$(this).data('title_id');
		var article_id=$(this).data('article_id');

		var s=filters.split(",");
		for(var i in s)
		{
			s[i]=s[i].trim();
		}
		filters=s.join(", ");

		predefines=JSON.parse(localStorage.getItem('predefines'));

		renderPredefines($,function() {
			$("#predefine-"+predefine_id+" td:nth-child(3)").html('<a data-predefine_id="'+predefine_id+'" class="refresh_predefine"><span class="glyphicon glyphicon-refresh"></span></a>');

			$("#predefine-"+predefine_id+" td:nth-child(4)").html('<input class="edit_predefine_field" name="edit_predefine_title" id="edit_predefine_title" data-predefine_id="'+predefine_id+'" placeholder="Ex: bbc3" value="'+title+'">');

			$("#predefine-"+predefine_id+" td:nth-child(5)").html('<input class="edit_predefine_field" name="edit_predefine_url_prefix" id="edit_predefine_url_prefix" data-predefine_id="'+predefine_id+'" placeholder="Ex: http://www.bbc.co.uk" value="'+url_prefix+'">');

			$("#predefine-"+predefine_id+" td:nth-child(6)").html('<textarea class="edit_predefine_field" name="edit_predefine_filters" id="edit_predefine_filters" data-predefine_id="'+predefine_id+'" placeholder="Ex: .shares,header,footer,#icons" rows="5">'+filters+'</textarea>');

			$("#predefine-"+predefine_id+" td:nth-child(7)").html('<input class="edit_predefine_field" name="edit_predefine_title_id" id="edit_predefine_title_id" data-predefine_id="'+predefine_id+'" placeholder="Ex: h1" value="'+title_id+'">');

			$("#predefine-"+predefine_id+" td:nth-child(8)").html('<input class="edit_predefine_field" name="edit_predefine_article_id" id="edit_predefine_article_id" data-predefine_id="'+predefine_id+'" placeholder="Ex: article" value="'+article_id+'">');

			/* */
			$('.edit_predefine_field').bind("enterKey",function(e){
				e.preventDefault();

				var predefine_id=$(this).data('predefine_id');
				loremblogum_savePredefine($, predefine_id, function() {
					loremblogum_getPredefines($);
				});
			});
			$('.edit_predefine_field').keyup(function(e){
				e.preventDefault();
				if(e.keyCode == 13)
				{
					$(this).trigger("enterKey");
				}
			});

		});
});

}

/* */
function loremblogum_getParameterByName(name) {
	name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
	var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
	results = regex.exec(location.search);
	return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
}
