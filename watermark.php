<?php

    $edgePadding=15;                        // used when placing the watermark near an edge
    $quality=100;                           // used when generating the final image
    $default_watermark='img/watermark.png'; // your watermark here
	$DirPath = 'results';					// watermarked images destination
	$erroroutput = "";						//custom error for uploaded not jpg
	
    
    if(isset($_POST['process'])){
		$original=$_FILES['filejpg']['tmp_name'];
        // an image has been posted, let's get to the nitty-gritty
        if(isset($_FILES['filejpg']) && $_FILES['filejpg']['error']==0){
			
			$files = getimagesize($original);
			$mimetype = $files['mime'];
			
            // be sure that the other options we need have some kind of value
            $_POST['save_as']='jpeg';
            $_POST['verticalpos']='center';
            $_POST['horizontalpos']='center';
            $_POST['wotermarksayz']='1';
			
            if($mimetype != 'image/jpeg'){
				$erroroutput = "
					<div class='alert alert-danger'>
						<center>
							<strong><h4>Error!</strong> Only '<u>original jpg</u>' images is allowed to be watermarked.</h4>
						</center>
					</div>";
				goto skip;
			}
			
			if (!file_exists('results')) {
				mkdir('results', 0777, true); // Creates folder results if not exists
				touch("index.html");//creates index.html file
				rename("index.html", "results/index.html"); // move generate index.html file to results folder
				$filecreated = "results/index.html";
				$myfile = fopen($filecreated, "w") or die("Unable to open file!");//open index.html file with 'Open a file for write only' permission
				$txt = //text to written to index.html
				'<!DOCTYPE html>
				<html lang="en">
					<head>
						<!-- Simple HttpErrorPages | MIT X11 License | https://github.com/AndiDittrich/HttpErrorPages -->
						<meta charset="utf-8" />
						<meta http-equiv="X-UA-Compatible" content="IE=edge" />
						<meta name="viewport" content="width=device-width, initial-scale=1" />
						<link rel="icon" href="../img/favicon.ico" type="image/png">
						<title>Access Denied</title>
						<style>
							/*! Simple HttpErrorPages | MIT X11 License | https://github.com/AndiDittrich/HttpErrorPages */
							body,html{
								width:100%;
								height:100%;
								background-color:#21232a
							}
							body{
								margin:0;
								color:#fff;
								text-align:center;
								text-shadow:0 2px 4px rgba(0,0,0,.5);
								padding:0;min-height:100%;
								-webkit-box-shadow:inset 0 0 75pt rgba(0,0,0,.8);
								box-shadow:inset 0 0 75pt rgba(0,0,0,.8);
								display:table;
								font-family:"Open Sans",Arial,sans-serif
							}
							h1{
								font-family:inherit;
								font-weight:500;
								line-height:1.1;
								color:inherit;
								font-size:36px
							}
							h1 small{
								font-size:68%;
								font-weight:400;
								line-height:1;
								color:#777
							}
							.lead{
								color:silver;
								font-size:21px;
								line-height:1.4
							}
							.cover{
								display:table-cell;
								vertical-align:middle;
								padding:0 20px
							}
						</style>
					</head>
					<body>
						<div class="cover">
							<h1>Access Denied <small>Error 403</small></h1>
							<p class="lead">The requested resource requires an authentication.</p>
						</div>   
					</body>
				</html>';

				fwrite($myfile, $txt);//modify index.html
				fclose($myfile);//close file
			}
			
            // file upload success
            $size=getimagesize($original);
            if($size[2]==2){
                // it was a JPEG image, so we're OK so far
                
				date_default_timezone_set("Asia/Manila");//set default timezone to Manila, Philippines
				//generate new filename
                $target_name=date('YmdHis').'_'.preg_replace('`[^a-z0-9-_.]`i','',$_FILES['filejpg']['name']);
                $target=dirname(__FILE__).'/results/'.$target_name;
                $watermark=$default_watermark;
                $wmTarget=$watermark.'.tmp';

                $origInfo = getimagesize($original); 
                $origWidth = $origInfo[0]; 
                $origHeight = $origInfo[1]; 

                $waterMarkInfo = getimagesize($watermark);
                $waterMarkWidth = $waterMarkInfo[0];
                $waterMarkHeight = $waterMarkInfo[1];
        
                // watermark sizing info
                if($_POST['wotermarksayz']=='larger'){
                    $placementX=0;
                    $placementY=0;
                    $_POST['horizontalpos']='center';
                    $_POST['verticalpos']='center';
                	$waterMarkDestWidth=$waterMarkWidth;
                	$waterMarkDestHeight=$waterMarkHeight;
                    
                    // both of the watermark dimensions need to be 5% more than the original image...
                    // adjust width first.
                    if($waterMarkWidth > $origWidth*1.05 && $waterMarkHeight > $origHeight*1.05){
                    	// both are already larger than the original by at least 5%...
                    	// we need to make the watermark *smaller* for this one.
                    	
                    	// where is the largest difference?
                    	$wdiff=$waterMarkDestWidth - $origWidth;
                    	$hdiff=$waterMarkDestHeight - $origHeight;
                    	if($wdiff > $hdiff){
                    		// the width has the largest difference - get percentage
                    		$sizer=($wdiff/$waterMarkDestWidth)-0.05;
                    	}else{
                    		$sizer=($hdiff/$waterMarkDestHeight)-0.05;
                    	}
                    	$waterMarkDestWidth-=$waterMarkDestWidth * $sizer;
                    	$waterMarkDestHeight-=$waterMarkDestHeight * $sizer;
                    }else{
                    	// the watermark will need to be enlarged for this one
                    	
                    	// where is the largest difference?
                    	$wdiff=$origWidth - $waterMarkDestWidth;
                    	$hdiff=$origHeight - $waterMarkDestHeight;
                    	if($wdiff > $hdiff){
                    		// the width has the largest difference - get percentage
                    		$sizer=($wdiff/$waterMarkDestWidth)+0.05;
                    	}else{
                    		$sizer=($hdiff/$waterMarkDestHeight)+0.05;
                    	}
                    	$waterMarkDestWidth+=$waterMarkDestWidth * $sizer;
                    	$waterMarkDestHeight+=$waterMarkDestHeight * $sizer;
                    }
                }else{
	                $waterMarkDestWidth=round($origWidth * floatval($_POST['wotermarksayz']));
	                $waterMarkDestHeight=round($origHeight * floatval($_POST['wotermarksayz']));
	                if($_POST['wotermarksayz']==1){
	                    $waterMarkDestWidth-=2*$edgePadding;
	                    $waterMarkDestHeight-=2*$edgePadding;
	                }
                }

                // OK, we have what size we want the watermark to be, time to scale the watermark image
                resize_png_image($watermark,$waterMarkDestWidth,$waterMarkDestHeight,$wmTarget);
                
                // get the size info for this watermark.
                $wmInfo=getimagesize($wmTarget);
                $waterMarkDestWidth=$wmInfo[0];
                $waterMarkDestHeight=$wmInfo[1];

                $differenceX = $origWidth - $waterMarkDestWidth;
                $differenceY = $origHeight - $waterMarkDestHeight;

				$placementX =  round($differenceX / 2);
				$placementY =  round($differenceY / 2);
				
                $resultImage = imagecreatefromjpeg($original);
				
                imagealphablending($resultImage, TRUE);
        
                $finalWaterMarkImage = imagecreatefrompng($wmTarget);
                $finalWaterMarkWidth = imagesx($finalWaterMarkImage);
                $finalWaterMarkHeight = imagesy($finalWaterMarkImage);
        
                imagecopy($resultImage,
                          $finalWaterMarkImage,
                          $placementX,
                          $placementY,
                          0,
                          0,
                          $finalWaterMarkWidth,
                          $finalWaterMarkHeight
                );
                
                imagejpeg($resultImage,$target,$quality); 

                imagedestroy($resultImage);
                imagedestroy($finalWaterMarkImage);

                unlink($wmTarget);
            }
			skip:
        }
    }
//watermark resizing function
function resize_png_image($img,$newWidth,$newHeight,$target){
    $srcImage=imagecreatefrompng($img);
    if($srcImage==''){
        return FALSE;
    }
    $srcWidth=imagesx($srcImage);
    $srcHeight=imagesy($srcImage);
    $percentage=(double)$newWidth/$srcWidth;
    $destHeight=round($srcHeight*$percentage)+1;
    $destWidth=round($srcWidth*$percentage)+1;
    if($destHeight > $newHeight){
        // if the width produces a height bigger than we want, calculate based on height
        $percentage=(double)$newHeight/$srcHeight;
        $destHeight=round($srcHeight*$percentage)+1;
        $destWidth=round($srcWidth*$percentage)+1;
    }
    $destImage=imagecreatetruecolor($destWidth-1,$destHeight-1);
    if(!imagealphablending($destImage,FALSE)){
        return FALSE;
    }
    if(!imagesavealpha($destImage,TRUE)){
        return FALSE;
    }
    if(!imagecopyresampled($destImage,$srcImage,0,0,0,0,$destWidth,$destHeight,$srcWidth,$srcHeight)){
        return FALSE;
    }
    if(!imagepng($destImage,$target)){
        return FALSE;
    }
    imagedestroy($destImage);
    imagedestroy($srcImage);
    return TRUE;
}
?>