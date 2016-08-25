<!DOCTYPE html>
<html>
<head>
	<title>File Downloader</title>
	<link type="image/png" rel="icon" href="icon.png">
	<style>
	body {
		font-family: arial;
		margin: 0 auto;
		padding: 30px;
		width: 600px;
		color: #333333;
	}
	* {
		-webkit-box-sizing: border-box;
		   -moz-box-sizing: border-box;
				box-sizing: border-box;
	}
	header {
		background-color: #F0F0F0;
		padding: 30px;
		margin-bottom: 30px;
	}
		header h2 {
			margin: 0;
			line-height: 40px;
		}
		header a {
			text-decoration: none;
			display: inline-block;
			padding: 10px 15px;
			border-radius: 5px;
			background-color: #2ba142;
			color: #FFFFFF;
			font-size: 15px;
			line-height: 20px;
			cursor: pointer;
			float: right;
		}
		header a:hover {
			background-color: #177b2a;
		}

	input[type=text] {
		margin-bottom: 20px;
		margin-top: 10px;
		width: 100%;
		padding: 15px;
		border-radius: 5px;
		border: 1px solid #7ac9b7;
	}

	textarea {
		width: 100%;
		padding: 15px;
		margin-top: 10px;
		border: 1px solid #7ac9b7;
		border-radius: 5px;
		margin-bottom: 20px;
		resize: none;
	}

	input[type=text]:focus, textarea:focus {
		border-color: #4697e4;
	}

	input[type=submit] {
		margin-bottom: 20px;
		width: 100%;
		padding: 15px;
		border-radius: 5px;
		border-width: 0;
		background-color: #4180C5;
		color: #FFFFFF;
		font-size: 15px;
		cursor: pointer;
	}

	input[type=submit]:hover,
	input[type=submit]:active {
		background-color: #61A0E5;
	}
	</style>
</head>
<body>
	<?php
	$dir = 'downloads';
	if( !empty($_POST['fileurl']) ) :
		set_time_limit(0);
		$fileurl = $_POST['fileurl'];
		
		$filename = $_POST['filename'];
		if( empty($filename) ) {
			$filename = basename($fileurl);
		}
		
		if( $dir && $dir!='./' ) {
			if( !is_dir($dir) ) {
				mkdir($dir);
			}
			chdir($dir);
		}
		
		$fp = fopen( $filename, 'w+');
		
		if( is_resource($fp) ) :
			$ch = curl_init( $fileurl );
				  curl_setopt($ch, CURLOPT_TIMEOUT, 300);
				  curl_setopt($ch, CURLOPT_FILE, $fp);
				  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
				  curl_exec($ch);
			curl_close($ch);
			fclose($fp);
	?>
	<header>
		<h2>File successfully downloaded <a href="<?php echo $dir.$filename ?>">Get It Now</a></h2>
	</header>
	<?php
		endif;
	endif;
	?>
	<form method="post" action="">
		<label>File name:</label>
		<input type="text" name="filename" size="100">
		
		<label>File url:</label>
		<input type="text" name="fileurl" size="100" required>
		
		<input type="submit" value="Upload To Server">
	</form>
</body>
</html>