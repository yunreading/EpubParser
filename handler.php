<?php include_once("includes/connection.php"); ?>
<?php
	// The following function is an error handler which is used
	// to output an HTML error page if the file upload fails
	function error($error, $seconds = 3)
	{    
		echo $error;    
	} // end error handler
	
    $max_file_size = $_POST['MAX_FILE_SIZE'];
    $filename = 'file';
    $basedir = dirname ( realpath ( __FILE__ )) ;
	//$project_id
    $dir_to_upload = $basedir."/ePub/";
    if (!file_exists($dir_to_upload)) {
        $result = mkdir($dir_to_upload);
    }
    $uploadsDirectory = $dir_to_upload;
	
    // extract file type
    $file_name = $_FILES[$filename]['name'];
    $file_type = "";
    $dot_pos = strripos($file_name, ".");
    if ($dot_pos) {
        $file_type = substr($file_name, $dot_pos);
    }
	
	$newFilePath = $uploadsDirectory.$file_name;

	// Now let's deal with the upload

	// possible PHP upload errors
    $errors = array(1 => 'php.ini max file size exceeded',
                2 => 'html form max file size exceeded',
                3 => 'file upload was only partial',
                4 => 'no file was attached');

	// check the upload form was actually submitted else print the form
    isset($_POST['submit'])
    or error('the upload form is needed');

	// check for PHP's built-in uploading errors
    ($_FILES[$filename]['error'] == 0)
    or error($errors[$_FILES[$filename]['error']]);

	// check that the file we are working on really was the subject of an HTTP upload
    is_uploaded_file($_FILES[$filename]['tmp_name'])
    or error('not an HTTP upload');

	// check that the file size is under limit
    ($_FILES[$filename]['size']<=$max_file_size)
    or error('file limit exceeded');

	// now let's move the file to its final location and allocate the new filename to it
    move_uploaded_file($_FILES[$filename]['tmp_name'], $newFilePath)
    or error('receiving directory insuffiecient permission');

	$bookFileName=$newFilePath;
	// If you got this far, everything has worked and the file has been successfully saved.
	// Do appropriate actions
	
	// Process the uploaded file with the path in: $_FILES[$filename]['tmp_name']

// Setup variables
$basedir = dirname(realpath(__FILE__));
$basedir = $basedir."/epubs";
if(!file_exists($basedir)) mkdir($basedir);
$OPF = "";
$STATE = "";
$BOOK = array();
$BOOK['CHAPTERCOUNT']=0;
$bookID=0;

function createFile($base,$fileName){
	$tmp = explode("/",$fileName);
    $result = "";
    if(count($tmp)>1){
        for($i=1; $i<count($tmp); $i++){
            $result.="/".$tmp[$i];
            if($i!=count($tmp)-1 && !file_exists($base.$result)) mkdir($base.$result);
        }
    } else {
        $result = "/".$fileName;
    }
	if($result!=""){
		touch($base.$result);
	}
	return $result;
}
function containerStartElement($parser,$name,$attr){
	global $OPF,$BOOK;
	if(strcasecmp($name,"ROOTFILE")==0){
		$OPF = $attr["FULL-PATH"];
        if(strpos($OPF,"/")===FALSE) $BOOK['OPF'] = $OPF;
        else $BOOK['OPF'] = substr($OPF,strpos($OPF,"/")+1);
	}
}
function contentMetadataStart($parser,$name,$attr){
	global $STATE,$BOOK;
	//echo $name."<br />";
	$lowerName = strtolower($name);
	if($lowerName=="dc:title"){
		$STATE = "TITLE";
	} else if($lowerName=="dc:creator"){
		if(strtolower($attr['OPF:ROLE'])=="aut"){
			$STATE = "AUTHOR";
		}
	} else if($lowerName=="dc:identifier"){
		$STATE = "IDENTIFIER";
	} else if($lowerName=="dc:date"){
		$STATE = "DATE";
	} else if($lowerName=="dc:publisher"){
		$STATE = "PUBLISHER";
	} else if($lowerName=="dc:rights"){
		$STATE = "RIGHTS";
	} else if($lowerName=="dc:subject"){
		$STATE = "SUBJECT";
	} else if($lowerName=="dc:language"){
		$STATE = "LANGUAGE";
	}else {
	
	}
}

/**
 * Function to read the manifest and spine of OPF file
 * The metadata is assumed to be read by contentMetadataStart
 */
function contentStart($parser,$name,$attr){
	global $BOOK,$connection,$bookID;
	$lowerName = strtolower($name);
	if($lowerName=="item"){
		$itemID = $attr['ID'];
		$href = $attr['HREF'];
        if((strtolower($itemID)=="cover")||(strtolower($itemID)=="cover-image")){
            $BOOK['COVER'] = $href;
        } else {
    		$BOOK['ITEM'][$itemID]['HREF'] = $href;
        }
	} else if($lowerName=="itemref") {
		$itemID = $attr['IDREF'];
		if(!isset($attr['LINEAR']) || strcasecmp($attr['LINEAR'],"yes")==0){
			$BOOK['CHAPTER'][$BOOK['CHAPTERCOUNT']] = $BOOK['ITEM'][$itemID]['HREF'];
			$query = "INSERT INTO Chapter (b_id,c_id,title,filename) VALUES ({$bookID},".count($BOOK['CHAPTER']).",'{$itemID}','{$BOOK['CHAPTER'][$BOOK['CHAPTERCOUNT']]}')";
			$result = mysql_query($query,$connection);
			if(!$result){
				die("Query: {$query}<br />Error inserting chapter: ".mysql_error());
			}
			$BOOK['CHAPTERCOUNT']++;
		}
	} else {
	
	}
}

/**
 * Function to read the data of tags inside OPF file
 * Including metadata, manifest, and spine
 */
function contentData($parser,$data){
	global $STATE,$BOOK,$connection;
	if($STATE=="TITLE"){
		$BOOK['TITLE'] = trim($data);
	} else if($STATE=="AUTHOR"){
		$BOOK['AUTHOR'] = trim($data);
	} else if($STATE=="IDENTIFIER"){
	  // Get the UUID with format ********-****-****-****-************ where * means any alphanumeric characters
	  preg_match('/[A-Za-z0-9]{8}(-[A-Za-z0-9]{4}){3}-[A-Za-z0-9]{12}/',$data,$matches);
	  if(count($matches)==0){
	      // Get the ISBN number with format 13 digits
	      preg_match('/[0-9]{13}/',$data,$matches);
	      if(count($matches)==0){
		  // No identification, use the encoded title as the identification
		  $BOOK['IDENTIFIER'] = "BS64:".base64_encode($BOOK['TITLE']);
	      } else {
		  $BOOK['IDENTIFIER'] = "ISBN:".$matches[0];
	      }
	  } else {
	      $BOOK['IDENTIFIER'] = "UUID:".$matches[0];
	  }
	} else if($STATE=="DATE"){
		$BOOK['DATE'] = trim($data);
	} else if($STATE=="PUBLISHER"){
		$BOOK['PUBLISHER'] = trim($data);
	} else if($STATE=="LANGUAGE"){
		$BOOK['LANGUAGE'] = trim($data);
		if (($BOOK['LANGUAGE']=='zh-CN')||($BOOK['LANGUAGE']=='简体中文'))
		$BOOK['LANGUAGE']='zh';
	}else if($STATE=="RIGHTS"){
		$BOOK['RIGHTS'] = trim($data);
	} else if($STATE=="SUBJECT"){
		$tagName = trim($data);
		$BOOK['TAGS'][] = $tagName;
		$tagName = mysql_prep($tagName);
		$query = "SELECT * FROM Tag WHERE t_name='{$tagName}'";
		$result = mysql_query($query,$connection);
		if(!$result){
			die("Query: {$query}<br />Error checking tag name: ".mysql_error());
		}
		if(mysql_num_rows($result)==0){
			$query = "INSERT INTO Tag(t_name) VALUES ('{$tagName}')";
			$result = mysql_query($query,$connection);
			if(!$result){
				die("Query: {$query}<br />Error inserting tag name: ".mysql_error());
			}
		}
	} else {
	
	}
}

function contentEnd($parser,$name){
    global $STATE,$BOOK;
    if($STATE=="IDENTIFIER" && (!isset($BOOK['IDENTIFIER']) || strlen($BOOK['IDENTIFIER'])==0)){
        if(isset($BOOK['TITLE'])){
            $BOOK['IDENTIFIER'] = "BS64:".base64_encode($BOOK['TITLE']);
        } else {
            $BOOK['IDENTIFIER'] = "BS64:";
        }
    }
    $STATE = "";
}

function isImage($path){
    if(preg_match("/(jpg|png|gif)$/",$path)){
        return true;
    } else return false;
}

// Function that does nothing
function nop(){}
?>
<html>
    <head>
        <title>ePub-Reader</title>
        <meta http-equiv="content-type" content="text/html; charset=utf-8">
        <!--<script type="text/javascript" src="js/jquery-1.6.3.min.js"></script>
        <script type="text/javascript" src="js/index.js"></script>-->
    </head>
    <body>
    <?php
	////////////////////////////////////////////////////////////////////////
	// Open and extract the epub file                                     //
	// Read the content file to determine whether the book already exists //
	// If it doesn't exist, add it to the database                        //
	////////////////////////////////////////////////////////////////////////
    //$bookFileName = "ePub/poe-purloined-letter.epub";
	//$bookFileName = "ePub/carroll-alice-in-wonderland-illustrations.epub";
	//$bookFileName = "ePub/cooper-deerslayer.epub";
	//$bookFileName = "ePub/ming.epub"; // Doesn't work for Chinese as for now
	
	$zip = zip_open($bookFileName);
	while($BOOK['TITLE']==""){ // The OPF file might be extracted first before the container.xml
		while($zip_entry = zip_read($zip)){
			////////////////////////////////
			// Process the container file //
			////////////////////////////////
			$zip_entry_name = zip_entry_name($zip_entry);
			if($zip_entry_name=="META-INF/container.xml"){
				if(!zip_entry_open($zip,$zip_entry)) continue;
				$content = "";
				while($zip_content = zip_entry_read($zip_entry)){
					$content.=$zip_content;
				}
				zip_entry_close($zip_entry);
				$parser = xml_parser_create();
				xml_set_element_handler($parser,containerStartElement,nop);
				if(!xml_parse($parser,$content)){ 
					die("Error on line " . xml_get_current_line_number($parser)); 
				}
				xml_parser_free($parser);
			} else if($zip_entry_name==$OPF){
				if(!zip_entry_open($zip,$zip_entry)) continue;
				$content = "";
				while($zip_content = zip_entry_read($zip_entry)){
					$content.=$zip_content;
				}
				zip_entry_close($zip_entry);
				$parser = xml_parser_create();
				xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "utf-8");
				xml_set_element_handler($parser,contentMetadataStart,contentEnd);
				xml_set_character_data_handler($parser,contentData);
				if(!xml_parse($parser,$content)){ 
					die("Error on line " . xml_get_current_line_number($parser)); 
				}
				xml_parser_free($parser);
			}
		}
		if($OPF==""){
			// Invalid epub file
			die("Invalid epub file, please check your file again<br />");
		}
	}
	zip_close($zip);
	$query = "SELECT title FROM Book WHERE UUID='".mysql_prep($BOOK['IDENTIFIER'])."'";
	$result = mysql_query($query,$connection);
	if(!$result){
		die("Error checking book existance: ".mysql_error());
	}
	echo "The title of the book is: ".$BOOK['TITLE'].". Written by: ".$BOOK['AUTHOR']."<br />";
	echo "Book identification number: ".$BOOK['IDENTIFIER']."<br />";
	if(mysql_num_rows($result)==0){
		echo "Inserting the book into the database<br />";
		$query = "INSERT INTO Book(UUID,title,author,publisher,publish_date,content_path,rights,language,create_date) VALUES 
				 ('".mysql_prep($BOOK['IDENTIFIER'])."','".mysql_prep($BOOK['TITLE'])."','".mysql_prep($BOOK['AUTHOR'])."','".mysql_prep($BOOK['PUBLISHER'])."','".mysql_prep($BOOK['DATE'])."','".mysql_prep($BOOK['OPF'])."','".mysql_prep($BOOK['RIGHTS'])."','".mysql_prep($BOOK['LANGUAGE'])."','".date("Y-m-d H:i:s",time())."')";
		$result = mysql_query($query,$connection);
		if(!$result){
			die("Error inserting new book: ".mysql_error());
		}
		$bookID = mysql_insert_id();
		// Inserting book tags
        if(count($BOOK['TAGS'])>0){
            $query = "INSERT INTO Booktag (b_id,t_id) (SELECT '{$bookID}',t_id FROM Tag WHERE ";
            for($i=0; $i<count($BOOK['TAGS']); $i++){
                if($i>0) $query.=" OR";
                $query.=" t_name='".mysql_prep($BOOK['TAGS'][$i])."'";
            }
            $query.=")";
            $result = mysql_query($query,$connection);
            if(!$result){
                die("Query: {$query}<br />Error applying tags: ".mysql_error());
            }
        }

        $upload_dir = $basedir."/".$bookID;
        if(!file_exists($upload_dir)) mkdir($upload_dir);
		$zip = zip_open($bookFileName);
		while($zip_entry = zip_read($zip)){
			/////////////////////////////////
			// Printing file name and size //
			/////////////////////////////////
			echo ($zip_entry_name=zip_entry_name($zip_entry)).", Size: ";
			echo zip_entry_filesize($zip_entry)."<br />";
			
			//////////////////////////////////////////////////
			// Open the file, put the content in a variable //
			//////////////////////////////////////////////////
			if(!zip_entry_open($zip,$zip_entry)) continue;
			$content = "";
			while($zip_content = zip_entry_read($zip_entry)){
				$content.=$zip_content;
			}
			zip_entry_close($zip_entry);
			
			//////////////////////////////////////
			// Create the file or directory     //
			// Discards the top level directory //
			//////////////////////////////////////
			$fname = createFile($upload_dir,$zip_entry_name);

			// If it's not empty and not a directory, put the contents
			if($fname!="" && $fname[strlen($fname)-1]!="/"){
				file_put_contents($upload_dir.$fname,$content);
			}

			////////////////////////////////
			// Read the chapter directive //
			////////////////////////////////
			if($zip_entry_name==$OPF){
				$parser = xml_parser_create();
				xml_set_element_handler($parser,contentStart,nop);
				if(!xml_parse($parser,$content)){ 
					die("Error on line " . xml_get_current_line_number($parser)); 
				}
				xml_parser_free($parser);
				
				// Update the total chapters
				$query = "UPDATE Book SET totalChapter=".count($BOOK['CHAPTER'])." WHERE UUID='{$BOOK['IDENTIFIER']}'";
				$result = mysql_query($query,$connection);
				if(!$result){
					die("Error updating total chapters: ".mysql_error());
				}
			}
			echo "<hr /><br />";
		}
		zip_close($zip);

        if(isset($BOOK['COVER']) && $BOOK['COVER']!="" && isImage($BOOK['COVER'])){
            require_once("SimpleImage.php");
            $img = new SimpleImage();
            $img->load($upload_dir."/".$BOOK['COVER']);
            $img->resizeToHeight(200);
            $img->save($upload_dir."/".$BOOK['COVER']);
            $res = mysql_query("UPDATE Book SET cover_path='".mysql_prep($BOOK['COVER'])."' WHERE UUID='".$BOOK['IDENTIFIER']."'");
            if(!$res){
                die("Error adding cover: ".mysql_error());
            }
        }

    } else {
		echo "The book was already inside the database<br />";
	}
?>
<br />
</body>
</html>
