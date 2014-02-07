<?php
/*
  Name: Wordpress Video Gallery
  Plugin URI: http://www.apptha.com/category/extension/Wordpress/Video-Gallery
  Description: Ajax Video Upload.
  Version: 2.6
  Author: Apptha
  Author URI: http://www.apptha.com
  License: GPL2
 */
## look up for the path
require_once '../../hdflv-config.php';
## get the path url from querystring
$file_name    = $error = '';
$errorcode    = 12;
$errormsg[0]  = '<b>Upload Success:</b> File Uploaded Successfully';
$errormsg[1]  = '<b>Upload Cancelled:</b> Cancelled by user';
$errormsg[2]  = '<b>Upload Failed:</b> Invalid File type specified';
$errormsg[3]  = '<b>Upload Failed:</b> Your File Exceeds Server Limit size';
$errormsg[4]  = '<b>Upload Failed:</b> Unknown Error Occured';
$errormsg[5]  = '<b>Upload Failed:</b> The uploaded file exceeds the upload_max_filesize directive in php.ini';
$errormsg[6]  = '<b>Upload Failed:</b> The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
$errormsg[7]  = '<b>Upload Failed:</b> The uploaded file was only partially uploaded';
$errormsg[8]  = '<b>Upload Failed:</b> No file was uploaded';
$errormsg[9]  = '<b>Upload Failed:</b> Missing a temporary folder';
$errormsg[10] = '<b>Upload Failed:</b> Failed to write file to disk';
$errormsg[11] = '<b>Upload Failed:</b> File upload stopped by extension';
$errormsg[12] = '<b>Upload Failed:</b> Unknown upload error.';
$errormsg[13] = '<b>Upload Failed:</b> Please check post_max_size in php.ini settings';

if ( isset( $_GET['error'] ) ) {
	$error = $_GET['error'];
}

if ( isset( $_GET['processing'] ) ) {
	$pro = $_GET['processing'];
}

if ( isset( $_POST['mode'] ) ) {

	$exttype = $_POST['mode'];
	if ( $exttype == 'video' ) {
		$allowedExtensions = array( 'flv', 'FLV', 'mp4', 'MP4', 'm4v', 'M4V', 'M4A', 'm4a', 'MOV', 'mov', 'mp4v', 'Mp4v', 'F4V', 'f4v', 'mp3', 'MP3' );
	} else if ( $exttype == 'image' ) {
		$allowedExtensions = array( 'jpg', 'JPG', 'jpeg', 'JPEG', 'png', 'PNG' );
	} else if ( $exttype == 'srt' ) {
		$allowedExtensions = array( 'srt', 'SRT' );
	}
}

## check if upload cancelled
if ( ! iserror(  ) ) {
	## check if stopped by post_max_size
	if ( ( $pro == 1 ) && ( empty( $_FILES['myfile'] ) ) ) {
		$errorcode = 13;
	} else {
		$file = $_FILES['myfile'];
		if ( no_file_upload_error( $file ) ) {

			if ( is_allowed_extension( $file ) ) {
				## check file size
				if ( ! filesizeexceeds( $file ) ) {
					doupload( $file );
				}
			}
		}
	}
}

function iserror(  ) {
	global $error;
	global $errorcode;
	if ( $error == 'cancel' ) {
		$errorcode = 1;
		return true;
	} else {
		return false;
	}
}

function no_file_upload_error( $file ) {
	global $errorcode;
	$error_code = $file['error'];
	switch ( $error_code ) {
		case 1:
			$errorcode = 5;
			return false;
		case 2:
			$errorcode = 6;
			return false;
		case 3:
			$errorcode = 7;
			return false;
		case 4:
			$errorcode = 8;
			return false;
		case 6:
			$errorcode = 9;
			return false;
		case 7:
			$errorcode = 10;
			return false;
		case 8:
			$errorcode = 11;
			return false;
		case 0:
			return true;
		default:
			$errorcode = 12;
			return false;
	}
}

function is_allowed_extension( $file ) {
	global $allowedExtensions;
	global $errorcode;
	$filename = $file['name'];
	$output   = in_array( end( explode( '.', $filename ) ), $allowedExtensions );
	if ( ! $output ) {
		$errorcode = 2;
		return false;
	} else {
		return true;
	}
}

function filesizeexceeds( $file ) {
	global $errorcode;
	$POST_MAX_SIZE = ini_get( 'post_max_size' );
	$post_max_size = substr( $POST_MAX_SIZE, -1 );
	$post_max_size_value = ( $post_max_size == 'M' ? 1048576 : ( $post_max_size == 'K' ? 1024 : ( $post_max_size == 'G' ? 1073741824 : 1 ) ) );
	if ( $_SERVER['CONTENT_LENGTH'] > $post_max_size_value * ( int ) $POST_MAX_SIZE && $POST_MAX_SIZE ) {
		return true;
		$errorcode = 3;
	} else {
		return false;
	}
}

function doupload( $file ) {

	global $options1, $uploadpath, $file, $errorcode, $file_name, $wpdb;
	$options1			= get_option( 'HDFLVSettings' );
	$uploadPath = $wpdb->get_col( 'SELECT uploads FROM ' . $wpdb->prefix . 'hdflvvideoshare_settings' );
	$uPath		= $uploadPath[0];
	if ( $uPath != '' ) {
		$dir = ABSPATH . trim( $uPath ) . '/';
		$url = trailingslashit( get_option( 'siteurl' ) ) . trim( $uPath ) . '/';
		if ( ! wp_mkdir_p( $dir ) ) {
			$message = sprintf( __( 'Unable to create directory %s. Is its parent directory writable by the server?', 'hdflv' ), $dir );
			$uploads['error'] = $message;
			return $uploads;
		}
		$uploads    = array( 'path' => $dir, 'url' => $url, 'error' => false );
		$uploadpath = $uploads['path'];
	} else {
		$uploadpath = ABSPATH;
	}

	$filesave = 'select MAX( vid ) from ' . $wpdb->prefix . 'hdflvvideoshare';
	$fsquery  = mysql_query( $filesave );
	$row      = mysql_fetch_array( $fsquery, MYSQL_NUM );

	$destination_path = $uploadpath;

	$row1      = $row[0] + 1;
	$file_name = $row1 . '_' . $_FILES['myfile']['name'];

	$target_path = $destination_path . '' . $file_name;
	if ( @move_uploaded_file( $file['tmp_name'], $target_path ) ) {
		$errorcode = 0;
	} else {
		$errorcode = 4;
	}
	sleep( 1 );
}
?>
<script language='javascript' type='text/javascript'>

	window.top.window.updateQueue( <?php echo balanceTags( $errorcode );
?>, '<?php echo balanceTags( $errormsg[$errorcode] ); ?>', '<?php echo balanceTags( $file_name ); ?>' );

</script>