<?php
/*
Plugin Name: MU Featured Image
Plugin URI:  https://wordpress.org/plugins/multisite-featured-image/
Description: Changing the featured image box on multisite installation. It can be used to set any image you want by Media Uploader, Url or other. 
Version:     1.4.0
Author:      Igor Benić
Author URI:  http://www.twitter.com/igorbenic
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages
Text Domain: ibenic_mufimg 
*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

if ( !is_multisite() ) return; 

define('IBENIC_MUFIMG_PARENT_BLOG_ID', get_site_option( "ibenic_mufimg_default_blog" ));


add_filter( 'admin_post_thumbnail_html', 'ibenic_mufimg_html', 1, 2);

/**
 * HTML for the custom feature image
 * @param  string       $content    Content in the featured image metabox
 * @param  number       $postID     ID of the post we are on
 * @return string               Content for the featured image
 */
function ibenic_mufimg_html($content, $postID){
        
        $currentBlogID = get_current_blog_id();

        if( $currentBlogID != IBENIC_MUFIMG_PARENT_BLOG_ID){

            $imgURL = get_post_meta( $postID, '_ibenic_mufimg_src', true );
             
             
                $content = '<div class="custom-img-container">';

                   if ( $imgURL ) :  

                    $content .= '<img src="'.$imgURL.'" alt="" style="max-width:100%;" />';
                   endif;  

                $content .= '</div>' ;

                // Your add & remove image links 
                $content .= '<p class="hide-if-no-js">';

                    $content .= '<a class="upload-custom-img '. (( $imgURL  ) ? 'hidden':'').'"'; 

                       $content .= ' href="#">';

                        $content .= __('Set custom image', 'ibenic_mufimg');

                    $content .= ' </a>';

                     $content .= '<a class="delete-custom-img '. ( ( ! $imgURL  ) ? 'hidden':''  ).'" ';

                       $content .= 'href="#">';

                         $content .= __('Remove this image', 'ibenic_mufimg');

                     $content .= '</a>';

                 $content .= '</p>';

                //<!-- A hidden input to set and post the chosen image URL-->
                $content .= '<input class="custom-img-id" name="ibenic_mufimg_custom-img-src" type="hidden" value="'.esc_url( $imgURL ).'" />';

             

        }

        return $content;
        
}

/**
 * Enqueue the script
 * @param  string $hook Name of the hook / file we are on 
 */
function ibenic_mufimg_scripts($hook) {
    

    wp_enqueue_script( 'ibenic_mufimg_js', plugin_dir_url( __FILE__ ) . 'js/ibenic_mufimg.js' );

}
add_action( 'admin_enqueue_scripts', 'ibenic_mufimg_scripts' );

/**
 * Saving the thumbnail when used on other multisites
 * @param  number $post_id ID of the post 
 */
function ibenic_mufimg_save_thumbnail( $post_id ) {

    /**
     * Move away if the $_POST does not contain this data
     * @since  1.3.4  Fixed bug when posting remotely using XML-RPC
     */
     if( ! isset( $_POST["ibenic_mufimg_custom-img-src"] ) ) {
        return;
     }

     $currentBlogID = get_current_blog_id();
     $switchedBlog = false;
     $switchCount = 0; // Counter of how many times the blog has switched

     if( $currentBlogID == IBENIC_MUFIMG_PARENT_BLOG_ID){

        return;

     }

     $imgURL = $_POST["ibenic_mufimg_custom-img-src"];
     $imgID = ibenic_mfuimg_get_attachment_id_from_url( $imgURL );
     /**
      * If the imgID was not found, we need to look on other blog sites.
      */
     if(!$imgID){
        global $wpdb;
        $blogList =  $wpdb->get_results( $wpdb->prepare("SELECT blog_id, domain, path FROM $wpdb->blogs WHERE site_id = %d AND public = '1' AND archived = '0' AND spam = '0' AND deleted = '0' ORDER BY registered DESC", $wpdb->siteid), ARRAY_A );
      
        foreach ($blogList as $blog) {
            if($blog["blog_id"] == $currentBlogID){

                continue;
            }

            switch_to_blog( $blog["blog_id"] );

            $switchCount++; //Increment for each switch

            $switchedBlog = true;

            $imgID = ibenic_mfuimg_get_attachment_id_from_url( $imgURL );

            if($imgID != false){
                break;
            }
            
        }
     }

     //$imageSizes = get_intermediate_image_sizes();

     $images = array();
     //If ImageID was found on on of the sites then use it save sizes
     //If not, we will save only the SRC and save an empty array
     if($imgID != false){
         $imageMetaData = wp_get_attachment_metadata($imgID);
         $imageFull = wp_get_attachment_image_src( $imgID, "full" );
         $images["full"] = array(
            "url" => $imageFull[0],
            "width" => $imageMetaData["width"],
            "height" => $imageMetaData["height"]
         );
         foreach ($imageMetaData["sizes"] as $size => $sizeInfo) {

            $image = wp_get_attachment_image_src( $imgID, $size );

            $images[$size] = array(
                "url" => $image[0],
                "width" => $sizeInfo["width"],
                "height" => $sizeInfo["height"]
            );
            
         }
     }

     //Change to the current blog
     if($switchedBlog){
        for ($i = 0; $i < $switchCount; $i++) { 
            restore_current_blog(); //Restore for each switch
        }
     }
     //Save our data
     update_post_meta( $post_id, '_ibenic_mufimg_image', $images );
     update_post_meta( $post_id, '_ibenic_mufimg_src', $imgURL );

     //Fake the thumbnail_id so the has_post_thumbnail works as intended on other sites
     $thumbnail_id = get_post_meta( $post_id, '_thumbnail_id', true );
     if( ! $thumbnail_id ) { 
        update_post_meta( $post_id, '_ibenic_mufimg_has_fake_thumb', '1' );
        update_post_meta( $post_id, '_thumbnail_id', '1' );
     }
     
}

add_action( 'save_post', 'ibenic_mufimg_save_thumbnail' );

/**
 * Change the post thumbnail Source
 * @param  string       $html               
 * @param  number       $post_id            
 * @param  number       $post_thumbnail_id  
 * @param  string/array $size               
 * @param  array        $attr              
 * @return string                    
 */
function ibenic_mufimg_post_thumbnail_html($html, $post_id, $post_thumbnail_id, $size, $attr) {

    $currentBlogID = get_current_blog_id();
    
    // Return the current HTML if we are on the parent blog
    if( $currentBlogID == IBENIC_MUFIMG_PARENT_BLOG_ID){

        return $html;

    }

     
     if(!is_array($attr)){
                
        $attr = array();

     }

     if(!isset($attr["class"])){

            $attr["class"] =  "wp-post-image attachment-".$size;

     }  

      
    //Get the data
    $images = get_post_meta( $post_id, '_ibenic_mufimg_image', true );

    
    //Define the variable
    $imgURL = "";
   

    /**
     * Check if is an array (New on Save)
     * If not then it could be an OLD save or even Image from URL
     * @since  1.1
     */
    if($images && is_array($images) && count($images) > 0){
        $image = "";
        if( isset( $images[ $size ] ) ){
            $image = $images[$size];
        }else {
            $image = $images["full"];
        }
        //Settings the URL
        $imgURL = $image["url"];
        //Setting the attributes
        $attr["width"] = $image["width"];
        $attr["height"] = $image["height"];

    } else {
        //Backward compatibility if saved as one URL
        
        $imgURL = get_post_meta( $post_id, '_ibenic_mufimg_src', true );

    }

    if( $imgURL == '' ) {
        return $html;
    }
    
    $imgTag = "<img ";

    $imgTag .= " src='".esc_url( $imgURL )."' ";


     if(is_array($attr) && count($attr) > 0){

        foreach ($attr as $attribute => $value) {
            
            $imgTag .= " ".$attribute."='".$value."' ";
        }
     }

    $imgTag .= " />";
    
    return $imgTag;

}

 

add_filter( "post_thumbnail_html", "ibenic_mufimg_post_thumbnail_html", 99, 5 );

add_action('network_admin_menu', 'ibenic_mufimg_settings');

/**
 * Adding the Network Setting Page
 * @return [type] [description]
 */
function ibenic_mufimg_settings(){

    add_submenu_page( 'settings.php', 'Multisite Featured Image', 'MU Featured Image', 'manage_options', 'ibenic_mufimg', 'ibenic_mufimg_settings_page' );

}


/**
 * Network Settings Admin page
 * @return [type] [description]
 */
function ibenic_mufimg_settings_page(){
    global $wpdb;
    $blogList =  $wpdb->get_results( $wpdb->prepare("SELECT blog_id, domain, path FROM $wpdb->blogs WHERE site_id = %d AND public = '1' AND archived = '0' AND spam = '0' AND deleted = '0' ORDER BY registered DESC", $wpdb->siteid), ARRAY_A );

    if(isset($_POST["ibenic_mfuimg_submit"])){

        if(isset($_POST["ibenic_mufimg_delete_featured"]) && $_POST["ibenic_mufimg_delete_featured"]=="1"){

             update_site_option( "ibenic_mufimg_delete_featured", 1 );

        }else{
             update_site_option( "ibenic_mufimg_delete_featured", 0 );
        }

        if(isset($_POST["ibenic_mufimg_default_blog"])){

                update_site_option( "ibenic_mufimg_default_blog", $_POST["ibenic_mufimg_default_blog"]);

        }

    }
     
    echo "<h1>".__('Multisite Featured Image','ibenic_mufimg')."</h1>";
    ?>
    <form method="POST" action="settings.php?page=ibenic_mufimg">

         <p>

            <strong><?php _e('Delete all references on other sites', 'ibenic_mufimg'); ?></strong>

            <input type="checkbox" name="ibenic_mufimg_delete_featured" value="1" <?php checked( 1, get_site_option( "ibenic_mufimg_delete_featured", 1 ), true ); ?> />
         
         </p>
    
         <p>

            <strong><?php _e('Default Blog', 'ibenic_mufimg'); ?></strong>
            
            <select name="ibenic_mufimg_default_blog">
                <?php foreach ($blogList as $blog) {
                    $blogname = get_blog_option( $blog['blog_id'], 'blogname' );
                    echo "<option ".selected( $blog["blog_id"], get_site_option( "ibenic_mufimg_default_blog", 1 ), false )." value='".$blog["blog_id"]."'>".$blogname."</option>";
                }
                ?>
            
            </select>
        </p>

        <p>

            <button type="submit" name="ibenic_mfuimg_submit" class="button button-primary"><?php _e("Save Changes",'wordpress'); ?></button>
        
        </p>
    </form>
    <?php

}

function ibenic_mfuimg_get_attachment_id_from_url( $attachment_url = '' ) {
 
    global $wpdb;
    $attachment_id = false;
 
    // If there is no url, return.
    if ( '' == $attachment_url )
        return;
 
    // Get the upload directory paths
    $upload_dir_paths = wp_upload_dir();
 
    // Make sure the upload path base directory exists in the attachment URL, to verify that we're working with a media library image
    if ( false !== strpos( $attachment_url, $upload_dir_paths['baseurl'] ) ) {
 
        // If this is the URL of an auto-generated thumbnail, get the URL of the original image
        $attachment_url = preg_replace( '/-\d+x\d+(?=\.(jpg|jpeg|png|gif)$)/i', '', $attachment_url );
 
        // Remove the upload path base directory from the attachment URL
        $attachment_url = str_replace( $upload_dir_paths['baseurl'] . '/', '', $attachment_url );
 
        // Finally, run a custom database query to get the attachment ID from the modified attachment URL
        $attachment_id = $wpdb->get_var( $wpdb->prepare( "SELECT wposts.ID FROM $wpdb->posts wposts, $wpdb->postmeta wpostmeta WHERE wposts.ID = wpostmeta.post_id AND wpostmeta.meta_key = '_wp_attached_file' AND wpostmeta.meta_value = '%s' AND wposts.post_type = 'attachment'", $attachment_url ) );
 
    }
 
    return $attachment_id;
}

/**
 * Filter the post metadata when getting the thumbnail id
 * @param  mixed $null          Check value for the post meta
 * @param  int $post_id         Post ID
 * @param  string $meta_key     Meta Key we want to retrieve
 * @param  bool $single         Return a single value
 * @return mixed           
 */
function mufimg_filter_post_thumbnail_id( $null, $post_id, $meta_key, $single ) {
    
    if ( '_thumbnail_id' !== $meta_key ) {
        return $null;
    }

    $currentBlogID = get_current_blog_id();
    
    // Return the $null if we are on the parent blog
    if( $currentBlogID == IBENIC_MUFIMG_PARENT_BLOG_ID){

        return $null;

    }

    // Check if we have a value of it
    $mufimg_src = get_post_meta( $post_id, '_ibenic_mufimg_src', true );

    if( ! $mufimg_src ) {
        return $null;
    }

    $attachment_id = 'mufimg_' . $post_id;

    return $attachment_id;
}

add_filter( 'get_post_metadata', 'mufimg_filter_post_thumbnail_id', 10, 4 );

/**
 * [mufimg_filter_post_thumbnail_src description]
 * @param  array $image                
 * @param  int $attachment_id 
 * @param  string $size         
 * @param  string $icon          
 * @return array                
 */
function mufimg_filter_post_thumbnail_src( $image, $attachment_id, $size, $icon ) {
    
    if ( false === strpos( $attachment_id, 'mufimg_' ) ) {
        return $image;
    }

    $post_id = (int) str_replace('mufimg_', '', $attachment_id );

    //Get the data
    $images = get_post_meta( $post_id, '_ibenic_mufimg_image', true );

    /**
     * Check if is an array (New on Save)
     * If not then it could be an OLD save or even Image from URL
     * @since  1.4.0
     */
    if($images && is_array( $images ) && count( $images ) > 0){
        $the_image = "";
        if( isset( $images[ $size ] ) ){
            $the_image = $images[$size];
        }else {
            $the_image = $images["full"];
        }
        //Settings the URL
        $image[0] = $the_image["url"];
        //Setting the attributes
        $image[1] = $the_image["width"];
        $image[2] = $the_image["height"];

    } else {
        //Backward compatibility if saved as one URL
        
        $image[0] = get_post_meta( $post_id, '_ibenic_mufimg_src', true );

    }

    // Set the image URL for the passed $attachment_id.
    // $image[0] = ...

    return $image;
}
add_filter( 'wp_get_attachment_image_src', 'mufimg_filter_post_thumbnail_src', 10, 4 );

/**
 * Add site options for this plugin if there are none
 *  
 */
function ibenic_mufimg_activate() {

     if(null == get_site_option( "ibenic_mufimg_delete_featured" ) || "" == get_site_option( "ibenic_mufimg_delete_featured" )){
        add_site_option( "ibenic_mufimg_delete_featured", 1 );
     }

     if(null == get_site_option( "ibenic_mufimg_default_blog" ) || "" == get_site_option( "ibenic_mufimg_default_blog" )){
        add_site_option( "ibenic_mufimg_default_blog", 1 );
     }

}
register_activation_hook( __FILE__, 'ibenic_mufimg_activate' );


/**
 * Check to delete all settings made by this plugin
 *  
 */
function ibenic_mufimg_deactivate(){

    if(get_site_option( "ibenic_mufimg_delete_featured" ) == 1 || get_site_option( "ibenic_mufimg_delete_featured" ) == "1" ){

        global $wpdb, $blog_id;
        $dbquery = 'SELECT blog_id FROM '.$wpdb->blogs;
        $ids = $wpdb->get_col( $dbquery );
        foreach ( $ids as $id ) {
            switch_to_blog( $id );

            $postmeta = 'SELECT post_id FROM '.$wpdb->postmeta.' WHERE meta_key="_ibenic_mufimg_src"';
            $postIDs = $wpdb->get_col( $postmeta );

            delete_post_meta_by_key( '_ibenic_mufimg_src' ); 
            delete_post_meta_by_key( '_ibenic_mufimg_image' ); 

            foreach ($postIDs as $postID) {
                //DELETE fake thumbs
                $has_thumb = get_post_meta( $postID, '_ibenic_mufimg_has_fake_thumb', true );
                if( "1" == $has_thumb ) {
                    delete_post_meta( $postID, '_thumbnail_id' );
                    delete_post_meta( $postID, '_ibenic_mufimg_has_fake_thumb' );
                }

            }
        }

        delete_site_option( "ibenic_mufimg_default_blog" );
        delete_site_option( "ibenic_mufimg_delete_featured" );

    }



}
register_deactivation_hook( __FILE__, 'ibenic_mufimg_deactivate' );