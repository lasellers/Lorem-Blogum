<?php
/*

@author: Lewis A. Sellers <lasellers@gmail.com>
@date: 6/2015
*/
function loremblogum_settings_page()
{
	global $wpdb;
	?>
	<div class="wrap container" id="loremblogum_settings">
		<h2><span class="dashicons dashicons-groups"></span> Lorem Blogum Settings<small></small></h2>
		<div id="loading"></div>
		<div id="status"></div>
		<p>
			Lorem Blogum news feed import plugin. 
		</p>
		<?php
		$lorem=new loremblogum();
		$pluginOptions = $lorem->pluginOptions;

		/* */
		if( isset( $_POST['featured_image'] ) )
		{
			$pluginOptions['featured_image']= htmlentities( $_POST['featured_image'], ENT_QUOTES);
			update_option(LOREMBLOGUM_DATA, $pluginOptions);
		}	
		if( isset( $_POST['min_width'] ) )
		{
			$pluginOptions['min_width']= htmlentities( $_POST['min_width'], ENT_QUOTES);
			update_option(LOREMBLOGUM_DATA, $pluginOptions);
		}	
		if( isset( $_POST['min_height'] ) )
		{
			$pluginOptions['min_height']= htmlentities( $_POST['min_height'], ENT_QUOTES);
			update_option(LOREMBLOGUM_DATA, $pluginOptions);
		}
		if( isset( $_POST['min_title'] ) )
		{
			$pluginOptions['min_title']= htmlentities( $_POST['min_title'], ENT_QUOTES);
			update_option(LOREMBLOGUM_DATA, $pluginOptions);
		}
		if( isset( $_POST['min_content'] ) )
		{
			$pluginOptions['min_content']= htmlentities( $_POST['min_content'], ENT_QUOTES);
			update_option(LOREMBLOGUM_DATA, $pluginOptions);
		}
		if( isset( $_POST['default_category'] ) )
		{
			$pluginOptions['default_category']= htmlentities( $_POST['default_category'], ENT_QUOTES);
			update_option(LOREMBLOGUM_DATA, $pluginOptions);
		}
		if( isset( $_POST['posts_with_images_only'] ) )
		{
			$pluginOptions['posts_with_images_only']= htmlentities( $_POST['posts_with_images_only'], ENT_QUOTES);
			update_option(LOREMBLOGUM_DATA, $pluginOptions);
		}
		if( isset( $_POST['post_status'] ) )
		{
			$pluginOptions['post_status']= htmlentities( $_POST['post_status'], ENT_QUOTES);
			update_option(LOREMBLOGUM_DATA, $pluginOptions);
		}
		if( isset( $_POST['post_type'] ) )
		{
			$pluginOptions['post_type']= htmlentities( $_POST['post_type'], ENT_QUOTES);
			update_option(LOREMBLOGUM_DATA, $pluginOptions);
		}
		if( isset( $_POST['post_author'] ) )
		{
			$pluginOptions['post_author']= htmlentities( $_POST['post_author'], ENT_QUOTES);
			update_option(LOREMBLOGUM_DATA, $pluginOptions);
		}
		
		/* */
		if( isset( $_POST['maximum_remove_posts_per_call'] ) )
		{
			$pluginOptions['maximum_remove_posts_per_call']= htmlentities( $_POST['maximum_remove_posts_per_call'], ENT_QUOTES);
			if(!($pluginOptions['maximum_remove_posts_per_call']>0&&$pluginOptions['maximum_remove_posts_per_call']<10000))
				$pluginOptions['maximum_remove_posts_per_call']=100;
			update_option(LOREMBLOGUM_DATA, $pluginOptions);
		}

		/* */
		if( isset( $_POST['maximum_imports_per_call'] ) )
		{
			$pluginOptions['maximum_imports_per_call']= htmlentities( $_POST['maximum_imports_per_call'], ENT_QUOTES);
			if(!($pluginOptions['maximum_imports_per_call']>0&&$pluginOptions['maximum_imports_per_call']<1000))
				$pluginOptions['maximum_imports_per_call']=10;
			update_option(LOREMBLOGUM_DATA, $pluginOptions);
		}

		/* */
		if( isset( $_POST['maximum_rejects_per_call'] ) )
		{
			$pluginOptions['maximum_rejects_per_call']= htmlentities( $_POST['maximum_rejects_per_call'], ENT_QUOTES);
			if(!($pluginOptions['maximum_rejects_per_call']>0&&$pluginOptions['maximum_rejects_per_call']<10))
				$pluginOptions['maximum_rejects_per_call']=2;
			update_option(LOREMBLOGUM_DATA, $pluginOptions);
		}

		/* */
		if( isset( $_POST['caching_ttl'] ) )
		{
			$pluginOptions['caching_ttl']= htmlentities( $_POST['caching_ttl'], ENT_QUOTES);
			if(!($pluginOptions['caching_ttl']>=0&&$pluginOptions['caching_ttl']<48))
				$pluginOptions['caching_ttl']=0;
			update_option(LOREMBLOGUM_DATA, $pluginOptions);
		}

		/* */
		if( isset( $_POST['post_type'] ) )
		{
			$pluginOptions['post_type']= htmlentities( $_POST['post_type'], ENT_QUOTES);
			update_option(LOREMBLOGUM_DATA, $pluginOptions);
		}
		?>

		<div id="loremblogum-tabs">
			<ul>
				<li><a href="#loremblogum-tab-1">Feeds</a></li>
				<li><a href="#loremblogum-tab-2">Pre-defines</a></li>
				<li><a href="#loremblogum-tab-3">Settings</a></li>
				<li><a href="#loremblogum-tab-4">Posts</a></li>
				<li><a href="#loremblogum-tab-5">Backup</a></li>
				<li><a href="#loremblogum-tab-6">About</a></li>
			</ul>


			<div id="loremblogum-tab-1" style="display: none">

				<h3 class="title">Feeds</h3>
				<p>These are all the news feeds.</p>

				<form method="post" action="admin.php?page=loremblogum-settings&hash=tabs-1#tabs-1">

					<table class="edit-table table table-striped">
						<thead id="feeds-fields">
						</thead>
						<tbody id="feeds">
						</tbody>
						<tfoot id="feeds-fields2">
						</tfoot>
					</table>

				</form>

				<p>Note: <b>Url</b> the url to the feed. Must be valid RSS or ATOM.</p>
				<p>Note: <b>Categories</b>. If blank, then uses the default category from the settings tab. Otherwise, this is a csv list of category translation pairs. That is, any of the "collected categories" items -- which categories articles in the feed are tagged with -- can be remapped to use a category on your own site. For example to make any article wth the category tag of "apple" show up with the "Geek" category we would use a colon seperated string such as <em>apple:geek</em>. More complex examples might <em>apple:geek,android:geek,cats:Humor,world:News,bbc:News</em>. If the first element of a pair is blank that is considered the default category for that feed, ie, <em>:news</em>.</p>
			</div>


			<div id="loremblogum-tab-2" style="display: none">
				<h3 class="title">Pre-defines</h3>
				<p>These pre-defines specify what css elements to filter out of any article page retrieved and where title and article content can be found.</p>

				<form method="post" action="admin.php?page=loremblogum-settings&hash=tabs-2#tabs-2">

					<table class="edit-table table table-striped">
						<thead id="predefines-fields">
						</thead>
						<tbody id="predefines">
						</tbody>
						<tfoot id="predefines-fields2">
						</tfoot>
					</table>

				</form>

				<p>Note: <b>Url Prefix</b> is the url to the articles as listed in the RSS feed. It may be different than the feed url. Sometimes you may have to follow the about tab instructions to directly force an specific feed to be pulled to see the debug info containing this url info.</p>
				<p>Note: <b>Strip CSS Element</b> is CSV list of css elements that you wish stripped from the article. Typically these are things like the social sharing icons, and links to other parts of the feed's site.</p>
				<p>Note: <b>Title element</b> is the name of CSS element that holds the title text -- usually h1. Can be a csv list.</p>
				<p>Note: <b>Article element</b> is the name of best CSS element containing the article text. Sometimes there is no good choice for this and you have to use a judicious amount of strip elements to clean up the content. Can be a csv list.</p>
			</div>



			<div id="loremblogum-tab-3" style="display: none">
				<p></p>	

				<h3 class="title">Settings</h3>
				<form method="post" action="admin.php?page=loremblogum-settings&hash=tabs-3#tabs-3">
					<p>Change various settings for this plugin.</p>
					<table class="form-table">

						<tr valign="top">
							<th width="25%"></th>
							<td width="25%"></td>
							<td width="50%"></td>
						</tr>

						<tr valign="top">
							<th scope="row">Min image width:</th>
							<td><input size=6 id="min_width" class="form-controlx" type="number" name="min_width" value="<?php echo $pluginOptions['min_width']; ?>"
							</td>
							<td><p>Images smaller than the minimum are ignored.</p></td>
						</tr>
						<tr valign="top">
							<th scope="row">Min image height:</th>
							<td><input size=6 id="min_height" class="form-controlx" type="number" name="min_height" value="<?php echo $pluginOptions['min_height']; ?>"
							</td>
							<td></td>
						</tr>

						<tr valign="top">
							<th scope="row">Min title length:</th>
							<td><input size=6 id="min_title" class="form-controlx" type="number" name="min_title" value="<?php echo $pluginOptions['min_title']; ?>"
							</td>
							<td><p>Articles with titles shorter are ignored.</p></td>
						</tr>
						<tr valign="top">
							<th scope="row">Min content length:</th>
							<td><input size=6 id="min_content" class="form-controlx" type="number" name="min_content" value="<?php echo $pluginOptions['min_content']; ?>"
							</td>
							<td><p>Articles with content shorter are ignored.</p></td>
						</tr>


						<tr valign="top">
							<th scope="row">Maximum Imports:</th>
							<td><input size=3 id="maximum_imports_per_call" class="form-controlx" type="number" name="maximum_imports_per_call" value="<?php echo $pluginOptions['maximum_imports_per_call']; ?>" />&nbsp;per
							</td>
							<td><p>Per scheduled call,. Suggested: 1</p></td>
						</tr>

						<tr valign="top">
							<th scope="row">Maximum Rejects:</th>
							<td><input size=3 id="maximum_rejects_per_call" class="form-controlx" type="number" name="maximum_rejects_per_call" value="<?php echo $pluginOptions['maximum_rejects_per_call']; ?>" />&nbsp;per
							</td>
							<td><p>Per scheduled call. Suggested: 5</p></td>
						</tr>

						<tr valign="top">
							<th scope="row">Maximum Deletes:</th>
							<td><input size=3 id="maximum_remove_posts_per_call" class="form-controlx" type="number" name="maximum_remove_posts_per_call" value="<?php echo $pluginOptions['maximum_remove_posts_per_call']; ?>" />&nbsp;per 
							</td>
							<td><p>Per scheduled call. Suggested: 100</p></td>
						</tr>

						<tr valign="top">
							<th scope="row">Caching:</th>
							<td><input size=6 id="caching_ttl" class="form-controlx" type="number" name="caching_ttl" value="<?php echo $pluginOptions['caching_ttl']; ?>" /> hours
							</td>
							<td><p>Default: 1hr</p><p>The number of hours the internal RSS & page performance cache persists.</p></td>
						</tr>

						<?php
						$args = array(
							'type'                     => 'post',
							'child_of'                 => 0,
							'parent'                   => '',
							'orderby'                  => 'name',
							'order'                    => 'ASC',
							'hide_empty'=>false
							); 
						$categories = get_categories( $args );
						?>
						<tr valign="top">
							<th scope="row">Default Category:</th>
							<td>
								<select id="default_category" name="default_category">
									<?php
									foreach ( $categories as $category ) {
										echo '<option value="'.$category->name.'"';
										if($pluginOptions['default_category']==$category->name)
											echo ' selected';
										echo '>' . esc_html( $category->name ) . '</option>';
									}?>
								</select>
							</td>
							<td>
								<p>The category articles are posted under if there is not a match in the feeds category.
								</p>
							</td>
						</tr>

						<?php
						$posts_with_images_only = ['No'=>'No','Yes'=>'Yes']; 
						?>
						<tr valign="top">
							<th scope="row">Posts with Images Only:</th>
							<td>
								<select id="posts_with_images_only" name="posts_with_images_only">
									<?php
									foreach ( $posts_with_images_only as $key=>$name ) {
										echo '<option value="'.$key.'"';
										if($pluginOptions['posts_with_images_only']==$key)
											echo ' selected';
										echo '>' . esc_html( $name ) . '</option>';
									}?>
								</select>
							</td>
							<td>
								<p>
									If yes, then articles with no valid images will be ignored.
								</p>
							</td>
						</tr>

						<?php
						$featured_image = ['No'=>'No','Yes'=>'Yes']; 
						?>
						<tr valign="top">
							<th scope="row">Featured image:</th>
							<td>
								<select id="featured_image" name="featured_image">
									<?php
									foreach ( $featured_image as $key=>$name ) {
										echo '<option value="'.$key.'"';
										if($pluginOptions['featured_image']==$key)
											echo ' selected';
										echo '>' . esc_html( $name ) . '</option>';
									}?>
								</select>
							</td>
							<td>
								<p>
									If yes, marks first image as the featured image.
								</p>
							</td>
						</tr>

						<?php
						$post_statuses = ['publish'=>'Publish','draft'=>'Draft']; 
						?>
						<tr valign="top">
							<th scope="row">Default Post Status:</th>
							<td>
								<select id="post_status" name="post_status">
									<?php
									foreach ( $post_statuses as $post_status=>$name ) {
										echo '<option value="'.$post_status.'"';

										if($pluginOptions['post_status']==$post_status)
											echo ' selected';
										echo '>' . esc_html( $name ) . '</option>';
									}?>
								</select>
							</td>
							<td>
								<p>The status articles are posted under: Publish means that are published immediately.</p>
							</td>
						</tr>

						<?php
						$args = [
						'public'   => true,
						'_builtin' => true
						];
						$post_types = get_post_types( $args, 'objects' ,'or'); 
						?>
						<tr valign="top">
							<th scope="row">Post Type:</th>
							<td>
								<select id="post_type" name="post_type">
									<?php
									foreach ( $post_types as $post_type ) {
										echo '<option value="'.$post_type->name.'"';
										if($pluginOptions['post_type']==$post_type->name)
											echo ' selected';
										echo '>' . esc_html( $post_type->label ) . '</option>';
									}?>
								</select>
							</td>
							<td><p>Generally you want "post"/"Posts" as the post type. You may optionally use loremblogum -- however your theme must be setup to handle that <em>custom</em> post type in order for the articles to apear.</p></td>
						</tr>

						<tr valign="top">
							<th scope="row">Post Author:</th>
							<td>
								<?php
								$users = get_users( 'orderby=nicename' );
								?>
								<select id="post_author" name="post_author">
									<?php
									foreach ( $users as $user ) {
										echo '<option value="'.$user->ID.'"';

										if($pluginOptions['post_author']==$user->ID)
											echo ' selected';
										echo '>' . esc_html( $user->first_name ) .' '. esc_html( $user->last_name ) . '</option>';
									}?>
								</select>
							</td>
							<td>
								<p>
									The user account all imported articles are posted under.
								</p>
							</td>
						</tr>

						<tr valign="top">
							<td colspan=2>
								<p class="submit">
									<input type="submit" class="button-primary btn btn-primary" value="<?php _e( 'Save Changes', LOREMBLOGUM_ID ); ?>" />
								</p>
							</td>
						</tr>
					</table>

				</form>

			</div>



			<div id="loremblogum-tab-4" style="display: none">
				<h3 class="title">Posts</h3>

				<p>
					Delete posts or purge their temporary caches.
				</p>
				<?php
				if( isset( $_POST['remove_posts'] )) 
				{
					echo '<div class="close-container"><div class="close">Close</div>';
					$lorem->removePosts();
					echo '</div>';
				}
				global $wpdb;
				$count=$wpdb->get_var("SELECT COUNT(post_id) AS count FROM $wpdb->postmeta WHERE meta_key = 'loremblogum_url'");			
				?>
				<form method="post" action="admin.php?page=loremblogum-settings&hash=tabs-4#tabs-4">
					<p>In some cases you may wish to delete the imported posts. The button below deletes them in blocks of 
						<?php echo $pluginOptions['maximum_remove_posts_per_call'] ?>.</p>
						<table class="form-table">
							<tr valign="top">
								<td colspan=2>
									<p class="submit">
										<input type="submit" name="remove_posts" class="button-primary btn btn-primary" value="<?php _e( 'Remove Posts', LOREMBLOGUM_ID ); ?>" /> (<b><?php echo $count; ?></b> of type <em><?php echo $pluginOptions['post_type']; ?></em>)
									</p>
								</td>
							</tr>
						</table>
					</form>

					<?php
					if( isset( $_POST['remove_cache'] )) 
					{
						$lorem->removeCache();
					}
					global $wpdb;
					$count=$wpdb->get_var("SELECT COUNT(option_id) AS count FROM $wpdb->options WHERE option_name LIKE  '_transient_loremblogum%'");			
					?>
					<form method="post" action="admin.php?page=loremblogum-settings&hash=tabs-4#tabs-4">
						<p>If your WordPress is not setup to automatically clear transients you can do so here.</p>
						<table class="form-table">
							<tr valign="top">
								<td colspan=2>
									<p class="submit">
										<input type="submit" name="remove_cache" class="button-primary btn btn-primary" value="<?php _e( 'Purge Cache', LOREMBLOGUM_ID ); ?>" /> <b>(<?php echo $count; ?></b> cached items)
									</p>
								</td>
							</tr>
						</table>
					</form>

				</div>



				<div id="loremblogum-tab-5" style="display: none">
					<h3 class="title">Backup</h3>

					<p>
						Send backup of feeds/predefine data or restore it.
					</p>
					<?php
					if( isset( $_POST['email_backup'] )) 
					{
						$data=$lorem->getBackupData();

						$to_user=get_userdata( $_POST['to_post_author'] );
						$user=get_userdata( $_POST['post_author'] );

						$temp = tmpfile();
						fwrite($temp, json_encode($data));

						$attachments = [stream_get_meta_data($temp)['uri']];
						$headers = [
						'From: '.$user->first_name.' '.$user->last_name.' <'.$user->user_email.'>' . "\r\n",
						""
						];

						$status=wp_mail( 
							$to_user->user_email,
							'loremblogum feeds backup', 
							'This message contains loremblogum feeds backup data.', 
							$headers,
							$attachments
							);
						echo '<div>';
						if($status==1)
						{
							echo '<p>Email sent.</p>';
						}
						else
						{
							echo '<p>There was a problem sending the email.</p>';
						}
						echo '</div>';

						fclose($temp); 

					}
					if( isset( $_POST['restore_backup'] )) 
					{
						foreach($_FILES AS $file)
						{
							if(
								isset($file['tmp_name'])
								&&strlen($file['tmp_name'])>0
								&&file_exists($file['tmp_name'])
								)
							{
								$data=file_get_contents($file['tmp_name']);
								$lorem->importBackupData($data);
							}
						}
					}
					?>
					<form method="post" action="admin.php?page=loremblogum-settings&hash=tabs-7#tabs-7">
						<table class="form-table">
							<tr valign="top">
								<td colspan=2>
									<p class="submit">
										<?php
										$users = get_users( 'orderby=nicename' );
										?>
										Email from 
										<select id="post_author" name="post_author">
											<?php
											foreach ( $users as $user ) {
												echo '<option value="'.$user->ID.'"';
												if($pluginOptions['post_author']==$user->ID)
													echo ' selected';
												echo '>' . esc_html( $user->first_name ) .' '. esc_html( $user->last_name ) .' ('. esc_html( $user->user_email ) . ')</option>';
											}?>
										</select>

										Email to 
										<select id="to_post_author" name="to_post_author">
											<?php
											$user_id=get_current_user_id();
											foreach ( $users as $user ) {
												echo '<option value="'.$user->ID.'"';
												if($user_id==$user->ID)
													echo ' selected';
												echo '>' . esc_html( $user->first_name ) .' '. esc_html( $user->last_name ) .' ('. esc_html( $user->user_email ) . ')</option>';
											}?>
										</select>

										<input type="submit" name="email_backup" class="button-primary btn btn-primary" value="<?php _e( 'Email Backup', LOREMBLOGUM_ID ); ?>" />
									</p>
								</td>
							</tr>
						</table>
					</form>

					<form method="post" enctype="multipart/form-data" action="admin.php?page=loremblogum-settings&hash=tabs-7#tabs-7">
						<table class="form-table">
							<tr valign="top">
								<td colspan=2>
									<p>
										Please specify a backup file to use:<br>
										<input type="file" name="datafile" size="40">
									</p>
									<p class="submit">
										<input type="submit" name="restore_backup" class="button-primary btn btn-primary" value="<?php _e( 'Restore from Backup', LOREMBLOGUM_ID ); ?>" />
									</p>
								</td>
							</tr>
						</table>
					</form>

				</div>



				<div id="loremblogum-tab-6" style="display: none">
					<h3 class="title">About</h3>

					<p>
						This plugin aggregates feed posts into the normal WP post workflow.
						All raw fetched feeds and pages are cached into WordPress temporary storage (varients) for improved performance. See Settings. 
					</p>
					<p>Titles and content are isolated by identifying containing css elements as specified by the header and article feed fields. Typically the header element is "h1" and the article element "article" but this varies from site to site.
						Uniqueness is determined by url and title.
					</p>
					<p>
						Categories specify mapping from the RSS "category" to the WordPress category as a color-seperated tuple -- ie, "cartoon:Humor" says all RSS posts that have a source "cartoon" category should be posted in the "Humor" category. Multiple categories are seperated by commas. All case is ignored (internally are categories are stored as lowercase).
					</p>
					<p>Imported articles have thier images processed so that small images (typically site icons) are filtered out. All other mages are copied into the media upload and attachented to the post. Videos are left linking to the original source (Youtube, etc). All imported images have width and height attributes removed and the class "responsive" added to thier css.
					</p>
					<p>Filters: CSV lists of css elements that should be striped from the content, such as ".shares".</p>

					<hr>
					<p>The main Cron Job is located at:</p>
					<tt><?php echo get_site_url() ?>/index.php?__api=loremblogum</tt><br>
					<p>
						...and can be setup via crontab as:<br>
						<tt>wget -q -O - <?php echo get_site_url() ?>/index.php?__api=loremblogum</tt><br>
					</p>

					<hr>
					<p>The url to directly force an specific feed to be pulled once is:<br>
						<tt><?php echo get_site_url() ?>/wp-admin/admin-ajax.php?action=fetch_feed&feed_id={feed_id}</tt><br>
						where {feed_id} is the integer id number of the feed starting at 1, as listed in the feed settings screen.
					</p>
					<p>
						This also provides some useful debugging information for when you are setting up predefines.
					</p>

					<hr>
					<p>The url to directly force an specific feed to be pulled once is:<br>
						<tt><?php echo get_site_url() ?>/wp-admin/admin-ajax.php?action=fetch_feed&feed_id={feed_id}</tt><br>
						where {feed_id} is the integer id number of the feed starting at 1, as listed in the feed settings screen.
					</p>
					


					<hr>
					<p>The url to fetch a copy of the backup data for reference is:<br>
						<tt><?php echo get_site_url() ?>/wp-admin/admin-ajax.php?action=get_backup_data</tt><br>
					</p>

					<hr>
					<p>The following css is suggested for images.</p>
					<tt>
						img.loremblogum-responsive {<br>
						max-width: 100%;<br>
						max-height: 100%;<br>
					}<br>
				</tt><br>

			</div>

			<hr>

			<div class="text-center"><small>
				v <?php echo LOREMBLOGUM_VERSION; ?>. Developed by <em>Lewis A. Sellers</em> 2015 August. Email at &lt;<a href="mailto:lasellers@gmail.com">lasellers@gmail.com</a>&gt; for questions or support. Or find me @ <a href="https://www.linkedin.com/in/lewisasellers">linkedin lewisasellers</a>.
			</small></div>
		</div>

		<?php
	}


	/* */
	add_action('admin_menu', function()
	{
		$settings=add_menu_page( 'Lorem Blogum Settings', 'loremblogum', 'manage_options', 'loremblogum-settings', 'loremblogum_settings_page','dashicons-groups' );
	});

?>