<?php
/*
Plugin Name: PicasaWeb Show
Plugin URI: http://henku.info
Description: Allow add picasaweb photos into your blog post.
Version: 1.2
Author: sagasw
Author URI: http://henku.info
Update Server: http://henku.info
Min WP Version: 2.0
*/

		if ( !function_exists('fetch_rss') )
		{	
		    // adam's hack to prevent fatals on "fetch_rss"
			if ( file_exists(ABSPATH . WPINC . '/rss.php') )
				require_once(ABSPATH . WPINC . '/rss.php');
			else
				require_once(ABSPATH . WPINC . '/rss-functions.php');
		} // end hack


        // photo_size can be 144, 160, 288, 576, 720, 800.
        // the feed of album should like 
        // http://picasaweb.google.com/data/feed/base/user/sagasw/albumid/4993413609788145681?kind=photo
        // the user name is "sagasw", the album id is "4993413609788145681".
        
        function DisplayAlbum(
			$username, 
            $albumid = '', 
            $photo_number = 5, 
            $photo_size = 160, 
            $start_photoindex = 0, 
            $is_random = 0, 
            $css_start = '',
			$css_end = ''
			)
        {
            $p=array();
			$p['url']="";
			$p['is_random']= $is_random;
            if ($photo_number <= 0)
                $photo_number = 0;
                
			$p['photo_number']= $photo_number;
            
			$p['photo_size']= $photo_size;
			$p['username']= $username;
			$p['albumid']= $albumid;
			$p['linkToAlbum']= 0;
            
			$category='album';
			$p['url'] = "http://picasaweb.google.com/data/feed/api/user/" . $p['username'] ."/";

			if(isset($p['albumid']) && $p['albumid'] != ''){
				$p['url'] = 'http://picasaweb.google.com/data/feed/base/user/' . $p['username'] . '/albumid/';
				$category='photo';
				$p['url'] .= $p['albumid'];
			}
            
            $list = '';
            
            if($images = loadWpImages($p['url']))
            {
    			if ($p['is_random'])
    			{
    				// We want a random selection, so lets shuffle it
    				shuffle($images);
    			}
    			if ($p['photo_number'] > 0)
    			{
    				// Slice off the number of items that we want:
    				$images = array_slice($images, $start_photoindex, $p['photo_number']);
    			}
                   
                $more_flag = 0;
                $has_more = 0;

                if ($photo_size == 800)
				{
					foreach ($images as $image)
					{
					    $bChange = false;
						
						$imgUrl = $image['album_thumbnail_url'];

						$imgLastPos = strrpos($image['album_thumbnail_url'], "/");
						$img800_left = substr($image['album_thumbnail_url'], 0, $imgLastPos);
						$img800_right = substr($image['album_thumbnail_url'], $imgLastPos +1);

						$img800 = $img800_left . "/s800/" . $img800_right;

                        $imgUrl = $img800;


						$imgLink= $image['image_url'];
						
						if($p['linkToAlbum']){
							$imgLink= $image['album_url'];
						}

						$imgUrl = $imgUrl;

                        $list .= $css_start. '<a href="'.$imgLink.'" target="_blank" ><img src="' . $imgUrl .'" alt="'.$image['title'].'" /></a>' . $css_end;
                       

                        $more_flag++;
                        if ($more_flag > 3 && $has_more == 0)
                        {
                            $list .= '<!--more-->';
                            $has_more = 1;
                        }
    				}
				}
                else
                {
                    foreach ($images as $image) {
                        $imgUrl = $image['album_thumbnail_url'].'?imgmax='. $photo_size;

                        if($photo_size == 160){
                            $imgUrl .= '&crop=1';
                        }
                        
                        $imgLink= $image['image_url'];
                        
                        if($p['linkToAlbum']){
							$imgLink= $image['album_url'];
						}

                        $list .= $css_start. '<a href="'.$imgLink.'" target="_blank" "><img src="' . $imgUrl .'" alt="'.$image['title'].'" /></a>' . $css_end;
                        
                        $more_flag++;
                        if ($photo_size >160 && $more_flag > 3 && $has_more == 0)
                        {
                            $list .= '<!--more-->';
                            $has_more = 1;
                        }
                    }
                }
            }
            
            // print $list;
			return $list;
        }

function loadWpImages($url)
   {
      $feed = @fetch_rss($url);
      $feedItems = $feed->items;
      $count = 0;
	 if ( is_array( $feedItems ) )
     { // prevent fatals when feed fails to load (adam's hack)
      foreach ($feedItems as $key=>$image)
      {
         $images[$count]['id'] = $image['id'];
         $images[$count]['image_url'] = $image['link'];
		 if ( '' == $image['content']['src'] ) {	// robustness (adam's hack)
	         	preg_match('/href="http:\/\/[^"]+".*src="(http:\/\/[^"]+)"/', $image['summary'], $thumbMatches);
			$images[$count]['album_thumbnail_url'] = $thumbMatches[1];   
		 }else{
			$images[$count]['album_thumbnail_url'] = $image['content']['src'];
		 }

		 $images[$count]['album_thumbnail_url'] = str_replace ( "s288/", "", $images[$count]['album_thumbnail_url']);
 		$images[$count]['album_thumbnail_url'] = str_replace ( "s144/", "", $images[$count]['album_thumbnail_url']);
 		$images[$count]['album_thumbnail_url'] = str_replace ( "s72/", "", $images[$count]['album_thumbnail_url']);

         $count++;
      }
	 } // close "if" (adam's hack)
     return $images;
  }


// get extended entry info (<!--more-->)
function get_picasawebshow_parameters($post) {
	//Match the new style more links
	if ( preg_match('/<--PicasaWebShow(.*?)?-->/', $post, $matches) ) {
		list($begin, $content1) = explode($matches[0], $post, 2);
	} else {
		$params = '';
	}

	if ( preg_match('/<--PicasaWebShow(.*?)?-->/', $content1, $matches2) ) {
		list($params, $end) = explode($matches2[0], $content1, 2);
	} else {
		$params = '';
	}

	return array('begin' => $begin, 'end' => $end, 'params' => $params);
}

function picasawebshow_content($text){
	global $post, $wpdb, $page, $pages;

	$post_id = $post->ID;

	$original = $text;

	$savedParam = get_post_meta($post_id, "PicasaWebShowOriginal", true);

	$must_set = false;
	if (preg_match('/<--PicasaWebShow(.*?)?-->/', $text, $matches))
	{
		$must_set = true;
		if ($savedParam != "")
		{
			delete_post_meta($post_id, "PicasaWebShowOriginal");
			$savedParam = "";
		}
	}

	if ($savedParam != "" && $text != "")
	{
		if (preg_match('/<--PicasaWebShow(.*?)?-->/', $text, $matches) == 0)
		{
			// according to random, we direct return text.
			if (rand(1,100) > 5)
			{
				return $original;
			}
		}

		$text = $savedParam;
	}
	else if ($text != "")
	{
		if (preg_match('/<--PicasaWebShow(.*?)?-->/', $text, $matches) == 0)
		{
			return $original;
		}
	}

		// replace the <--PicasaWebShowBegin-->Param1,Param2,Param3,Param4...<--PicasaWebShowEnd-->
		$picasapost = get_picasawebshow_parameters($text);
		if ($picasapost['params'] == '')
			return $original;

		$params = $picasapost['params'];
		$array_params = explode (',', trim($params));
		if (count($array_params) < 8 )
	    {
			array_push($array_params, "", "", "", "", "");
		}
			 
		$display_string = DisplayAlbum(
				trim($array_params[0]), 
				trim($array_params[1]),	
				intval(trim($array_params[2])),
				intval(trim($array_params[3])), 
				intval(trim($array_params[4])),
				intval(trim($array_params[5])),
				trim($array_params[6]),
				trim($array_params[7])
				);
			
        $text = $picasapost['begin'] . $display_string . $picasapost['end'];

		$text_process = $wpdb->escape($text);
		$text_process2 = wp_filter_post_kses($text_process);
		$wpdb->query(
					"UPDATE IGNORE $wpdb->posts SET
					post_content = '$text_process2'
					WHERE ID = $post_id");

		if ( preg_match('/<!--more(.*?)?-->/', $text_process2, $matches) ) 
		{
			$text = get_the_content("<h4>-- Please click here to see full content 阅读全文，更多精彩 --</h4>", 0, '');
		}

		if ($savedParam == "")
		{
			add_post_meta($post_id, "PicasaWebShowOriginal", $original, true);
		}

		return $text;
}

function picasawebshow_action($post_id)
{
	// get post content, 
	// do picasawebshow_content($content);
}

function picasawebpostshow_publish($post_ID) 
{
	if ($post_ID<=0) return $post_ID;
	
	$mypost = wp_get_single_post($post_ID, ARRAY_A);
    
	// fully text.
	$message = $mypost['post_content'];

        picasawebshow_content($message);
	
	return $post_ID;
}

add_filter('the_content', 'picasawebshow_content',0);

?>