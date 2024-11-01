<?php
/*
Plugin Name: Simple Post
Plugin URI: http://wordpress.org/extend/plugins/simple-post/
Description: Display a simpler Write Post screen. <a href="options-general.php?page=simple-post.php">Configuration</a>.
Version: 1.1
Author: Nick Momrik
Author URI: http://nickmomrik.com/

*/ 

add_action('admin_menu', 'simple_post_add_pages');
register_activation_hook(__FILE__, 'set_simple_post_options');

function simple_post_add_pages() {
	add_submenu_page('post.php', 'Write Simple Post', 'Simple Post', 'edit_posts', __FILE__, 'simple_post_page');
	add_options_page('Simple Post Options', 'Simple Post', 8, __FILE__, 'simple_post_options_page');
}

function simple_post_page() {
	$simple_post_cat = get_option('simple_post_cat');
	$simple_post_ping = get_option('simple_post_ping');
	$simple_post_comment = get_option('simple_post_comment');
	$simple_post_tags = get_option('simple_post_tags');
	$simple_post_edtool = get_option('simple_post_edtool');//dothis
	$simple_post_title = get_option('simple_post_title');
	$simple_post_datetime = get_option('simple_post_datetime');
	$simple_post_first = get_option('simple_post_first');

	if ( isset($_GET['posted']) && $_GET['posted'] ) : ?>
	<div id="message" class="updated fade"><p><strong><?php _e('Post saved.'); ?></strong> <a href="<?php echo get_permalink( $_GET['posted'] ); ?>"><?php _e('View post &raquo;'); ?></a></p></div>
	<?php
	endif;
	?>

	<form name="post" action="post.php" method="post" id="post">
	<?php if ( (isset($mode) && 'bookmarklet' == $mode) || isset($_GET['popupurl']) ): ?>
	<input type="hidden" name="mode" value="bookmarklet" />
	<?php endif; ?>
	
	<div class="wrap">
	<?php
		$form_action = 'post';
		$temp_ID = -1 * time();
		$form_extra = "<input type='hidden' id='post_ID' name='temp_ID' value='$temp_ID' />";
		wp_nonce_field('add-post');
	
	if ( !empty( $_REQUEST['text'] ) ) {
		$text = wp_specialchars( stripslashes( urldecode( $_REQUEST['text'] ) ) );
		$text = funky_javascript_fix( $text);
	}	
	if ( !empty( $_REQUEST['popuptitle'] ) ) {
		$post->post_title = wp_specialchars( stripslashes( $_REQUEST['popuptitle'] ));
		$post->post_title = funky_javascript_fix( $post->post_title );
		$popupurl = clean_url($_REQUEST['popupurl']);
		$post->post_content = '<a href="'.$popupurl.'">'.$post->post_title.'</a>'."\n$text";
	}
	else {
		if (!empty($text)) $post->post_content = $text;
		
		if (!empty($simple_post_title) && !empty($simple_post_datetime)) $space = ' ';
	
		if ($simple_post_first == 'text') $post->post_title = $simple_post_title . $space . gmdate($simple_post_datetime, current_time('timestamp'));
		elseif ($simple_post_first == 'datetime') $post->post_title = gmdate($simple_post_datetime, current_time('timestamp')) . $space . $simple_post_title;
	}

	$form_pingback = '<input type="hidden" name="post_pingback" value="' . (int) get_option('default_pingback_flag') . '" id="post_pingback" />';
	$form_prevstatus = '<input type="hidden" name="prev_status" value="' . attribute_escape( $post->post_status ) . '" />';
	$form_trackback = '<input type="text" name="trackback_url" style="width: 415px" id="trackback" tabindex="7" value="'. attribute_escape(str_replace("\n", ' ', $post->to_ping)) .'" />';

	if (empty($post->post_status)) $post->post_status = 'draft';
	?>

	<input type="hidden" name="user_ID" value="<?php echo (int) $user_ID ?>" />
	<input type="hidden" id="hiddenaction" name="action" value="<?php echo $form_action ?>" />
	<input type="hidden" id="originalaction" name="originalaction" value="<?php echo $form_action ?>" />
	<input type="hidden" name="post_author" value="<?php echo attribute_escape( $post->post_author ); ?>" />
	<input type="hidden" id="post_type" name="post_type" value="post" />
	<?php echo $form_extra ?>
	<input id="cat-<?php echo $simple_post_cat; ?>" type="hidden" name="post_category[]" value="<?php echo $simple_post_cat; ?>" checked="checked" />
	<input id="comment_status" type="hidden" name="comment_status" value="<?php
		if($simple_post_comment == 'open' || ($simple_post_comment == 'blog' && get_option('default_comment_status') == 'open')) echo 'open" checked="checked" ';
		else echo 'closed"';
	?> />
	<input id="ping_status" type="hidden" name="ping_status" value="<?php
		if($simple_post_ping == 'open' || ($simple_post_ping == 'blog' && get_option('default_ping_status') == 'open')) echo 'open" checked="checked" ';
		else echo 'closed"';
	?>/>
	<fieldset id="titlediv">
		<legend><?php _e('Title') ?></legend>
		<div><input type="text" name="post_title" size="30" tabindex="1" value="<?php echo attribute_escape($post->post_title); ?>" id="title" /></div>
	</fieldset>
	
	<fieldset id="<?php echo user_can_richedit() ? 'postdivrich' : 'postdiv'; ?>">
		<legend><?php _e('Post') ?></legend>
		<?php if ($simple_post_edtool == 'yes') the_editor($post->post_content);
		elseif ($simple_post_edtool == 'no') { ?>
		<div><textarea rows='10' cols='40' name='content' tabindex='2' id='content'><?php echo attribute_escape( $post->post_content ); ?></textarea></div>
		<?php } ?>
	</fieldset>
	
	<?php echo $form_pingback ?>
	<?php echo $form_prevstatus ?>

	<?php if($simple_post_tags == 'yes') { ?>
	<fieldset id="tagdiv">
		<legend><?php _e('Tags (separate multiple tags with commas: cats, pet food, dogs)'); ?></legend>
		<div><input type="text" name="tags_input" class="tags-input" id="tags-input" size="30" tabindex="3" value="<?php echo get_tags_to_edit( $post_ID ); ?>" /></div>
	</fieldset>
	<?php } ?>
	
	<p class="submit">
	<?php if ( !in_array( $post->post_status, array('publish', 'future') ) || 0 == $post_ID ) { ?>
		<?php if ( current_user_can('publish_posts') ) : ?>
			<input name="publish" type="submit" id="publish" tabindex="4" accesskey="p" value="<?php _e('Publish') ?>" />
		<?php else : ?>
			<input name="publish" type="submit" id="publish" tabindex="4" accesskey="p" value="<?php _e('Submit for Review') ?>" />
		<?php endif; ?>
	<?php } ?>
	<input name="referredby" type="hidden" id="referredby" value="<?php
	if ( !empty($_REQUEST['popupurl']) )
		echo clean_url(stripslashes($_REQUEST['popupurl']));
	else if ( url_to_postid(wp_get_referer()) == $post_ID )
		echo 'redo';
	else
		echo clean_url(stripslashes(wp_get_referer()));
	?>" />
	<br /><br /><a href="options-general.php?page=simple-post.php">Configure &raquo;</a></p>
	
	</div>
	
	</form>
<?php 	
}

function set_simple_post_options() {
	add_option('simple_post_cat', get_option('default_category'));
	add_option('simple_post_ping','blog');
	add_option('simple_post_comment', 'blog');
	add_option('simple_post_tags', 'yes');
	add_option('simple_post_edtool', 'no');
	add_option('simple_post_title', 'Simple Post: ');
	add_option('simple_post_datetime', 'm/d/Y g:i:sa');
	add_option('simple_post_first', 'text');
	
}

function update_simple_post_options() {
	$updated = false;
	
	if($_REQUEST['simple_post_cat']) {
		update_option('simple_post_cat', $_REQUEST['simple_post_cat']);
		$updated = true;
	}
	if($_REQUEST['simple_post_ping']) {
		update_option('simple_post_ping', $_REQUEST['simple_post_ping']);
		$updated = true;
	}
	if($_REQUEST['simple_post_comment']) {
		update_option('simple_post_comment', $_REQUEST['simple_post_comment']);
		$updated = true;
	}
	if($_REQUEST['simple_post_tags']) {
		update_option('simple_post_tags', $_REQUEST['simple_post_tags']);
		$updated = true;
	}
	if($_REQUEST['simple_post_edtool']) {
		update_option('simple_post_edtool', $_REQUEST['simple_post_edtool']);
		$updated = true;
	}
	if($_REQUEST['simple_post_title']) {
		update_option('simple_post_title', $_REQUEST['simple_post_title']);
		$updated = true;
	}
	if($_REQUEST['simple_post_datetime']) {
		update_option('simple_post_datetime', $_REQUEST['simple_post_datetime']);
		$updated = true;
	}
	if($_REQUEST['simple_post_title']) {
		update_option('simple_post_first', $_REQUEST['simple_post_title']);
		$updated = true;
	}
	
	if($updated) { ?>
		<div id="message" class="updated fade">
			<p>Options saved.</p>
		</div><?php	
	} else { ?>
		<div id="message" class="failed fade">
			<p>Failed to update options.</p>
		</div><?php
	}
}

function simple_post_options_page() {
?>
	<div class="wrap">
	<h2>Simple Post Options</h2>
<?php
	if($_REQUEST['submit']) update_simple_post_options();

	$simple_post_cat = get_option('simple_post_cat');
	$simple_post_ping = get_option('simple_post_ping');
	$simple_post_comment = get_option('simple_post_comment');
	$simple_post_tags = get_option('simple_post_tags');
	$simple_post_edtool = get_option('simple_post_edtool');
	$simple_post_title = get_option('simple_post_title');
	$simple_post_datetime = get_option('simple_post_datetime');
	$simple_post_first = get_option('simple_post_first');
?>
	<form method="post" action="options.php">
	<?php wp_nonce_field('update-options') ?>
	
	<fieldset class="options">
	<legend><?php _e('Post Defaults') ?></legend>
	<table width="100%" cellspacing="2" cellpadding="5" class="optiontable editform">
	<tr valign="top">
	<th scope="row"><?php _e('Category:') ?></th>
	<td><select name="simple_post_cat" id="simple_post_cat">
	<?php
	$categories = get_categories('get=all');
	foreach ($categories as $category) :
	$category = sanitize_category($category);
	if ($category->term_id == $simple_post_cat) $selected = " selected='selected'";
	else $selected = '';
	echo "\n\t<option value='$category->term_id' $selected>$category->name</option>";
	endforeach;
	?>
	</select></td></tr>
	<tr valign="top">
	<th scope="row">Ping Status</th>
	<td><select name="simple_post_ping" id="simple_post_ping">
	<option value="open"<?php if('open' == $simple_post_ping) echo ' selected="selected"'; ?>>Open</option>
	<option value="closed"<?php if('closed' == $simple_post_ping) echo ' selected="selected"'; ?>>Closed</option>
	<option value="blog"<?php if('blog' == $simple_post_ping) echo ' selected="selected"'; ?>>Use Blog Setting</option>
	</select></td></tr>
	<tr valign="top">
	<th scope="row">Comment Status</th>
	<td><select name="simple_post_comment" id="simple_post_comment">
	<option value="open"<?php if('open' == $simple_post_comment) echo ' selected="selected"'; ?>>Open</option>
	<option value="closed"<?php if('closed' == $simple_post_comment) echo ' selected="selected"'; ?>>Closed</option>
	<option value="blog"<?php if('blog' == $simple_post_comment) echo ' selected="selected"'; ?>>Use Blog Setting</option>
	</select></td></tr>
	</table></fieldset>

	<fieldset class="options">
	<legend><?php _e('Post Title') ?></legend>
	<table width="100%" cellspacing="2" cellpadding="5" class="optiontable editform">
	<tr valign="top">
	<th scope="row">Text</th>
	<td><input name="simple_post_title" type="text" id="simple_post_title" value="<?php echo $simple_post_title; ?>" size="15" />
	</td></tr>
	<th scope="row">Date/Time Format</th>
	<td><input name="simple_post_datetime" type="text" id="simple_post_datetime" value="<?php echo $simple_post_datetime; ?>" size="15" /> <a href="http://www.php.net/date" title="PHP Date/Time Format">PHP Date/Time Format</a><br />Leave blank for no date/time display.
	</td></tr>
	<tr valign="top">
	<th scope="row">Display First</th>
	<td><select name="simple_post_first" id="simple_post_first">
	<option value="text"<?php if('text' == $simple_post_first) echo ' selected="selected"'; ?>>Text</option>
	<option value="datetime"<?php if('datetime' == $simple_post_first) echo ' selected="selected"'; ?>>Date/Time</option>
	</select>
	<br /><?php _e('Output:') ?> <strong><?php 
		$datetime = gmdate($simple_post_datetime, current_time('timestamp'));
		if('text' == $simple_post_first) echo $simple_post_title . ' ' . $datetime;
		else echo $datetime . ' ' . $simple_post_title;
	?></strong></td></tr>
	</table></table></fieldset>
	
	<fieldset class="options">
	<legend><?php _e('Display') ?></legend>
	<table width="100%" cellspacing="2" cellpadding="5" class="optiontable editform">
	<tr valign="top">
	<th scope="row">Tags</th>
	<td><select name="simple_post_tags" id="simple_post_tags">
	<option value="yes"<?php if('yes' == $simple_post_tags) echo ' selected="selected"'; ?>>Yes</option>
	<option value="no"<?php if('no' == $simple_post_tags) echo ' selected="selected"'; ?>>No</option>
	</select></td></tr>
	<tr valign="top">
	<th scope="row">Editor Toolbar</th>
	<td><select name="simple_post_edtool" id="simple_post_edtool">
	<option value="yes"<?php if('yes' == $simple_post_edtool) echo ' selected="selected"'; ?>>Yes</option>
	<option value="no"<?php if('no' == $simple_post_edtool) echo ' selected="selected"'; ?>>No</option>
	</select></td></tr>
	</table></fieldset>

	<p class="submit">
	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="page_options" value="simple_post_cat,simple_post_ping,simple_post_comment,simple_post_tags,simple_post_edtool,simple_post_title,simple_post_datetime,simple_post_first" />
	<input type="submit" name="Submit" value="<?php _e('Update Options &raquo;') ?>" />
	</p>
	</form>
	</div>

	<div class="wrap">
	<h2>Simple Post Even Simpler</h2>
<?php require (ABSPATH . WPINC . '/vars.php'); 
	if ( $is_NS4 || $is_gecko || $is_winIE ) { ?>
	<h3>Bookmarks</h3>
	<p>Right click on the link and choose &#0147;Bookmark This Link...&#0148; or &#0147;Add to Favorites...&#0148; to create a posting shortcut.</p>
	<p>Direct Link: <a href="<?php echo get_option('siteurl') ?>/wp-admin/post-new.php?page=simple-post.php" title="Simple Post"><?php echo get_option('blogname'); ?> Simple Post</a>
	<?php if ($is_NS4 || $is_gecko) { ?>
	<br /><br />Bookmarklet: <a href="javascript:if(navigator.userAgent.indexOf('Safari') >= 0){Q=getSelection();}else{Q=document.selection?document.selection.createRange().text:document.getSelection();}location.href='<?php echo get_option('siteurl') ?>/wp-admin/post-new.php?page=simple-post.php&text='+encodeURIComponent(Q)+'&amp;popupurl='+encodeURIComponent(location.href)+'&amp;popuptitle='+encodeURIComponent(document.title);"><?php printf(__('Press It - %s Simple Post'), get_bloginfo('name', 'display')); ?></a>
	<?php } else if ($is_winIE) { ?>
	<br /><br />Bookmarklet: <a href="javascript:Q='';if(top.frames.length==0)Q=document.selection.createRange().text;location.href='<?php echo get_option('siteurl') ?>/wp-admin/post-new.php?page=simple-post.php&text='+encodeURIComponent(Q)+'&amp;popupurl='+encodeURIComponent(location.href)+'&amp;popuptitle='+encodeURIComponent(document.title);"><?php printf(__('Press it - %s Simple Post'), get_bloginfo('name', 'display')); ?></a>
	<?php } else if ($is_opera) { ?>
	<br /><br />Bookmarklet: <a href="javascript:location.href='<?php echo get_option('siteurl'); ?>/wp-admin/post-new.php?page=simple-post.php&popupurl='+escape(location.href)+'&popuptitle='+escape(document.title);"><?php printf(__('Press it - %s Simple Post'), get_option('blogname')); ?></a>
	<?php } ?>
		</p><br />
	<h3>Quicksilver AppleScript</h3>
	<ul>
		<li><a href="applescript://com.apple.scripteditor?action=new&script=using%20terms%20from%20application%20%22Quicksilver%22%0D%09on%20process%20text%20simple_post%0D%09%09set%20simple_post%20to%20urlencode(simple_post)%20of%20me%0D%09%09set%20simple_post_URL%20to%20%22<?php echo get_option('siteurl'); ?>%2Fwp-admin%2Fpost-new.php%3Fpage%3Dsimple-post.php%26text%3D%22%20%26%20simple_post%0D%09%09%0D%09%09tell%20application%20%22Safari%22%0D%09%09%09activate%0D%09%09%09set%20the%20URL%20of%20document%201%20to%20simple_post_URL%0D%09%09end%20tell%0D%09end%20process%20text%0Dend%20using%20terms%20from%0D%0Don%20urlencode(theText)%0D%09set%20theTextEnc%20to%20%22%22%0D%09repeat%20with%20eachChar%20in%20characters%20of%20theText%0D%09%09set%20useChar%20to%20eachChar%0D%09%09set%20eachCharNum%20to%20ASCII%20number%20of%20eachChar%0D%09%09if%20eachCharNum%20%3D%2032%20then%0D%09%09%09set%20useChar%20to%20%22+%22%0D%09%09else%20if%20(eachCharNum%20is%20not%2042)%20and%20(eachCharNum%20is%20not%2095)%20and%20(eachCharNum%20%3C%2045%20or%20eachCharNum%20%3E%2046)%20and%20(eachCharNum%20%3C%2048%20or%20eachCharNum%20%3E%2057)%20and%20(eachCharNum%20%3C%2065%20or%20eachCharNum%20%3E%2090)%20and%20(eachCharNum%20%3C%2097%20or%20eachCharNum%20%3E%20122)%20then%0D%09%09%09set%20firstDig%20to%20round%20(eachCharNum%20%2F%2016)%20rounding%20down%0D%09%09%09set%20secondDig%20to%20eachCharNum%20mod%2016%0D%09%09%09if%20firstDig%20%3E%209%20then%0D%09%09%09%09set%20aNum%20to%20firstDig%20+%2055%0D%09%09%09%09set%20firstDig%20to%20ASCII%20character%20aNum%0D%09%09%09end%20if%0D%09%09%09if%20secondDig%20%3E%209%20then%0D%09%09%09%09set%20aNum%20to%20secondDig%20+%2055%0D%09%09%09%09set%20secondDig%20to%20ASCII%20character%20aNum%0D%09%09%09end%20if%0D%09%09%09set%20numHex%20to%20(%22%25%22%20%26%20(firstDig%20as%20string)%20%26%20(secondDig%20as%20string))%20as%20string%0D%09%09%09set%20useChar%20to%20numHex%0D%09%09end%20if%0D%09%09set%20theTextEnc%20to%20theTextEnc%20%26%20useChar%20as%20string%0D%09end%20repeat%0D%09return%20theTextEnc%0Dend%20urlencode">Safari Version</a></li>
		<li><a href="applescript://com.apple.scripteditor?action=new&script=using%20terms%20from%20application%20%22Quicksilver%22%0D%09on%20process%20text%20simple_post%0D%09%09set%20simple_post%20to%20urlencode(simple_post)%20of%20me%0D%09%09set%20simple_post_URL%20to%20%22<?php echo get_option('siteurl'); ?>%2Fwp-admin%2Fpost-new.php%3Fpage%3Dsimple-post.php%26text%3D%22%20%26%20simple_post%0D%09%09%0D%09%09tell%20application%20%22Firefox%22%0D%09%09%09activate%0D%09%09%09Get%20URL%20simple_post_URL%0D%09%09end%20tell%0D%09end%20process%20text%0Dend%20using%20terms%20from%0D%0Don%20urlencode(theText)%0D%09set%20theTextEnc%20to%20%22%22%0D%09repeat%20with%20eachChar%20in%20characters%20of%20theText%0D%09%09set%20useChar%20to%20eachChar%0D%09%09set%20eachCharNum%20to%20ASCII%20number%20of%20eachChar%0D%09%09if%20eachCharNum%20%3D%2032%20then%0D%09%09%09set%20useChar%20to%20%22+%22%0D%09%09else%20if%20(eachCharNum%20is%20not%2042)%20and%20(eachCharNum%20is%20not%2095)%20and%20(eachCharNum%20%3C%2045%20or%20eachCharNum%20%3E%2046)%20and%20(eachCharNum%20%3C%2048%20or%20eachCharNum%20%3E%2057)%20and%20(eachCharNum%20%3C%2065%20or%20eachCharNum%20%3E%2090)%20and%20(eachCharNum%20%3C%2097%20or%20eachCharNum%20%3E%20122)%20then%0D%09%09%09set%20firstDig%20to%20round%20(eachCharNum%20%2F%2016)%20rounding%20down%0D%09%09%09set%20secondDig%20to%20eachCharNum%20mod%2016%0D%09%09%09if%20firstDig%20%3E%209%20then%0D%09%09%09%09set%20aNum%20to%20firstDig%20+%2055%0D%09%09%09%09set%20firstDig%20to%20ASCII%20character%20aNum%0D%09%09%09end%20if%0D%09%09%09if%20secondDig%20%3E%209%20then%0D%09%09%09%09set%20aNum%20to%20secondDig%20+%2055%0D%09%09%09%09set%20secondDig%20to%20ASCII%20character%20aNum%0D%09%09%09end%20if%0D%09%09%09set%20numHex%20to%20(%22%25%22%20%26%20(firstDig%20as%20string)%20%26%20(secondDig%20as%20string))%20as%20string%0D%09%09%09set%20useChar%20to%20numHex%0D%09%09end%20if%0D%09%09set%20theTextEnc%20to%20theTextEnc%20%26%20useChar%20as%20string%0D%09end%20repeat%0D%09return%20theTextEnc%0Dend%20urlencode">Firefox Version</a></li>
	</ul>

	<ol>
		<li>Open the AppleScript in a new Script Editor window by clicking the appropriate version above.</li>
		<li>Save the script to a location scanned by your Quicksilver catalog.</li>
		<li>Rescan the Quicksilver catalog.</li>
		<li>Launch Quicksilver.</li>
		<li>Type a period to enter text mode</li>
		<li>Type the text for your Simple Post.</li>
		<li>Hit tab and start typing the name of the script you saved in step 1.</li>
		<li>Once your script is selected, hit the Return key and your browser will launch to the Simple Post screen with the post content already filled in.</li>
	</ol>
<?php } ?>
	</div>
<?php	
}
?>
