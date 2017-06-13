=== Multisite Featured Image ===
Contributors: ibenic 
Tags: featured-image, post-thumbnail
Requires at least: 4.0.
Tested up to: 4.3.1
Stable tag: 1.4.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Multisite Featured Image changes the box of the featured image so that you can use the classic Media Uploader also with Network Shared Media.

== Description ==

Multisite Featured Image changes the box of the featured image for all sites. It can be used also in a single instance of WordPress and not a MU installation.

This plugin changes the box in the way that the default pop-up is replaced with the classic Media Uploader where you can choose images. *Images From URL do work*.

For a greater Multisite experience you can combine this plugin with the [Network Shared Media plugin](https://wordpress.org/plugins/network-shared-media/) and have a complete Media Sharing site.

Read about it on this [article](http://siderrz.com/wordpress-multisite-shared-featured-image/).

= Features =

- Using WordPress Media Uploader to set Featured Image
- You can set an image from URL
- Images from URL will be downloaded into the WordPress Media and it can be used for anything else inside WordPress
- Downloaded images are saved in every registered image size 
- You can set the Main site on which you will download the images in a MU installation

 
== Frequently Asked Questions ==

= Will this plugin work with any theme? =

No, this plugin will work only on those themes which are using the function the_post_thumbnail() or any variation of that function.

= Do I have to use a custom function to enable post thumbnails? =

No, this plugin is hooked on the core function the_post_thumbnail().

= Can I set the image from URL? =

Yes, from version 1.2, you can set images from URL.

= Can I use custom image sizes or choose between the default sizes? =

This plugin is using the WordPress Media Uploader and all the registered image sizes are saved in the database. When there is a request for the post thumbnail, the size that is requested inside the theme will be used.

= Can I use any registered image size with this plugin or the featured image will stay in the selected image? =

From version 1.1, featured image retrieves the required size from the function the_post_thumbnail. This plugin delivers the right size of the image, so the answer is: Yes, you can use any registered image size.


== Installation ==

This section describes how to install the plugin and get it working.

 

1. Upload the folder 'mu-featured-image' to the plugins folder
2. Activate the plugin through the 'Plugins' menu in WordPress Network Dashboard
3. Go to the Network dashboard and look at Settings > MU Featured Image to change the settings if needed

 

== Screenshots ==

1. Settings Page in the Network Settings
2. The Main Site Featured Image without any change
3. The Featured Image box on other sites
4. Thickbox when selecting image
5. Selecting the Size of the image to be used
6. Selected Image
7. Image shown as thumbnail on a site which is not the main site

== Changelog ==

= 1.4.0 =
* Enabling other thumbnails

= 1.3.4 =
* Fixed bug when posting remotely using XML-RPC

= 1.3.2 =
* WordPress Media Uploader is back.

= 1.3.1 =
* Disabled the WordPress Media Upload modal and return to thick box since Network Shared Media uses thickbox
* Images are not saved anymore to save space.

= 1.3 =
* Enabled the WordPress Media Upload modal instead of the thick box
* Images from URL are downloaded to WordPress Media and saved in every registered image size

= 1.2 =
* Enabled setting the media from URL

= 1.1 =
* Added all image sizes. The function the_post_thumbnail now renders the image in the requested size

= 1.0 =
* Initial release