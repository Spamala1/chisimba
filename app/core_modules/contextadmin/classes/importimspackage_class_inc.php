<?php
# security check - must be included in all scripts
if (!$GLOBALS['kewl_entry_point_run']) {
    die("You cannot view this page directly");
} 
# end security check
/**
 * The class importIMSPackage that manages 
 * the import of IMS specification content into Chisimba
 * @package importimspackage
 * @category context
 * @copyright 2007, University of the Western Cape & AVOIR Project
 * @license GNU GPL
 * @version
 * @author Jarrett Jordaan
 * The process for import IMS specification content is:
 * 
 * 
 */

class importIMSPackage extends dbTable
{
	/**
	 * @var object $objConfig
	*/
	public $objConfig;
	/**
	 * @var object $objIEUtils
	*/
	public $objIEUtils;
	/**
	 * @var object $objLanguage
	*/
	public $objLanguage;
	/**
	 * @var object $objDBContext
	*/
	public $objDBContext;
	/**
	 * @var object $objContextContent
	*/
	public $objContextContent;
	/**
	 * @var object $objDebug - debugging flag to display information
	*/
	public $objDebug;
	/**
	 * @var object $objError - error flag to return errors
	*/
	public $objError;
	/**
	 * @var object $fileMod - file modification flag to modify file types
	*/
	public $fileMod;
	/**
	 * @var object $pageIds - holds the page id's corresponding to a file name
	*/
	public $pageIds;
	/**
	 * The constructor
	*/
	function init()
	{
		#Load Filemanager class
		$this->objIndex =& $this->getObject('indexfileprocessor', 'filemanager');
		$this->objUpload =& $this->getObject('upload', 'filemanager');
		#Load System classes
		$this->objConfig = & $this->newObject('altconfig','config');
        	$this->objLanguage = & $this->newObject('language', 'language');
        	$this->objDBContext = & $this->newObject('dbcontext', 'context');
        	$this->objUser =& $this->getObject('user', 'security');
		#Load Inner classes
		$this->objIEUtils = & $this->newObject('importexportutils','contextadmin');
		#Load Chapter Classes
		$this->objChapters =& $this->getObject('db_contextcontent_chapters','contextcontent');
		$this->objContextChapters =& $this->getObject('db_contextcontent_contextchapter','contextcontent');
		#Load context classes
        	$this->objContentPages =& $this->getObject('db_contextcontent_pages','contextcontent');
	        $this->objContentOrder =& $this->getObject('db_contextcontent_order','contextcontent');
        	$this->objContentTitles =& $this->getObject('db_contextcontent_titles','contextcontent');
	        $this->objContentInvolvement =& $this->getObject('db_contextcontent_involvement','contextcontent');
		$this->pageIds = array();
		#Initialize Flags
		$this->fileMod = FALSE;
		$this->objDebug = FALSE;
		#Un-comment to view debug information
		#$this->objDebug = TRUE;
		//$this->objError = FALSE;
		#Un-comment to catch errors
		$this->objError = TRUE;
	}
	
	public $courseId;
	/**
	 * Controls the process for import IMS specification content
	 * Calls all necessary functions an does error checking
	 * 
	 * @param $_FILES global - uploaded file
	 *
	 * @return TRUE - Successful execution
	 * 
	*/
	function importIMScontent($FILES)
	{
		$this->fileMod = FALSE;
		$this->objIEUtils->fileModOff();
		$this->courseId = '';
		if(!isset($FILES) || $FILES['upload']['type'] != 'application/zip')
		{
			if($this->objError)
				return  "zipFileError";
		}
		#Retrieve temp folder
		$folder = $this->unzipIMSFile($FILES);
		if(!isset($folder) || $folder == 'unzipError')
		{
			if($this->objError)
				return  "unzipError";
		}
		#Retrieve file names
		$fileNames = $this->objIEUtils->list_dir_files($folder,0);
		if(!isset($fileNames))
		{
			if($this->objError)
				return  "fileReadError";
		}
		#Retrieve file locations
		$filesLocation = $this->locateAllFiles($folder);
		if(!isset($filesLocation))
		{
			if($this->objError)
				return  "fileReadError";
		}
		#Locate imsmanifest.xml file
		$imsFileLocation = $this->locateIMSfile($filesLocation, "/imsmanifest/");
		if(!isset($imsFileLocation))
		{
			if($this->objError)
				return  "imsReadError";
		}
		#Read imsmanifest.xml file
		#Create simplexml object to access xml file
		$simpleXmlObj = $this->loadSimpleXML($imsFileLocation);
		if(!isset($simpleXmlObj) || $simpleXmlObj == 'simpleXmlError')
		{
			if($this->objError)
				return  "simpleXmlError";
		}
		#Create domdocument object to access xml file	
		$domDocumentObj = $this->loadDOMDocument($imsFileLocation);
		if(!isset($domDocumentObj) || $domDocumentObj == 'domReadError')
		{
			if($this->objError)
				return  "domReadError";
		}
		#Create xpath object to access xml file
		$xpathObj = $this->loadXPath($domDocumentObj);
		if(!isset($xpathObj))
		{
			if($this->objError)
				return  "xpathSetError";
		}
		#Extract course data
		$courseData = $this->extractCourseData($simpleXmlObj, $domDocumentObj, $xpathObj);
		if(!isset($courseData) || $courseData == 'courseReadError')
		{
			if($this->objError)
				return  "courseReadError";
		}
		#Initialize all locations
		$init = $this->initLocations($courseData['contextcode'], $courseData['title']);
		if(!isset($init))
		{
			if($this->objError)
				return  "initializeError";
		}
		#Create course
		$courseCreated = $this->objIEUtils->createCourseInChisimba($courseData);
		$this->courseId = $courseCreated;
		if(!isset($courseCreated) || $courseCreated == 'courseWriteError')
		{
			if($this->objError)
				return  "courseWriteError";
		}
		#Write Resources
		$writeData = $this->writeResources($simpleXmlObj, $folder, $courseData);
		if(!isset($writeData))
		{
			if($this->objError)
				return  "writeResourcesError";
		}
		#Get organizations
		$structure = $this->getStructure($simpleXmlObj);
		if(!isset($structure))
		{
			if($this->objError)
				return  "noStructureError";
		}
		#Load html data into Chisimba
		$loadData = $this->loadToChisimba($writeData, $structure);
		if(!isset($loadData))
		{
			if($this->objError)
				return  "loadDataError";
		}
		#Load image data into Chisimba
		$uploadImagesToChisimba = $this->uploadImagesToChisimba($folder);
		if(!isset($uploadImagesToChisimba))
		{
			if($this->objError)
				return  "uploadError";
		}
		#Rebuild html images and url links
		$rebuildHtml = $this->rebuildHtml($loadData,$fileNames);
		if(!isset($rebuildHtml))
		{
			if($this->objError)
				return  "rebuildHtmlError";
		}
		#!!!Under going testing
		//$this->objIEUtils->eduCommonsData($imsFileLocation);

		return TRUE;
	}

	public $contentBasePath;
	public $courseContentBasePath;
	public $contextCode;
	public $courseTitle;
	public $courseContentPath;
	public $imagesLocation;
	public $docsLocation;
	public $filesLocation;
	/**
	 * 
	 * 
	 * 
	*/
	function initLocations($contextcode, $courseTitle)
	{
		#Static Chisimba file locations
		#opt/lampp/htdocs/chisimba_framework/app/usrfiles/
		$this->contentBasePath = $this->objConfig->getcontentBasePath();
		$this->courseContentBasePath = $this->contentBasePath."content/";
		$this->contextCode = strtolower(str_replace(' ','_',$contextcode));
		$this->courseTitle = strtolower(str_replace(' ','_',$courseTitle));
		$this->courseContentPath = $this->courseContentBasePath.$this->contextCode;
		$this->imagesLocation = $this->courseContentPath."/images";
		$this->docsLocation = $this->courseContentPath."/staticcontent";
		$this->filesLocation = $this->courseContentPath."/documents";
		$locations = array('contentBasePath' => $this->contentBasePath,
					'courseContentBasePath' => $this->courseContentBasePath,
					'contextCode' => $this->contextCode,
					'courseContentPath' => $this->courseContentPath,
					'imagesLocation' => $this->imagesLocation,
					'docsLocation' => $this->docsLocation
					);
		#Enter context
		$enterContext = $this->objDBContext->joinContext($this->contextCode);
		if($this->objDebug)
		{
			echo $this->contentBasePath."<br />";
			echo $this->courseContentBasePath."<br />";
			echo $this->contextCode."<br />";
			echo $this->courseContentPath."<br />";
			echo $this->imagesLocation."<br />";
			echo $this->docsLocation."<br />";
		}

		return $locations;
	}

	/**
	 * @var object $folder - relative path of temporary folder
	*/
	public $folder;
	/**
	 * Function to unzip an uploaded zip file
	 * 
	 * @param $_FILES global - Location of uploaded zip-file
	 *
	 * @return string $folder - Temp extraction folder location
	 * 
	*/
	function unzipIMSFile($FILES)
	{
		if(!is_uploaded_file($FILES['upload']['tmp_name']))
			return "unzipError";
		else if ($FILES['upload']['error'] != UPLOAD_ERR_OK)
			return "unzipError";
		else
		{
			$type = $FILES['upload']['type'];
			$name = $FILES['upload']['name'];
			$name = preg_replace('/^(.*)\.php$/i', '\\1.phps', $name);
			for ($i=0;$i<strlen($name);$i++) 
			{
				if ($name{$i} == ' ') 
					$name{$i} = '_';
			}
			$extension = "";
			$len = strlen($name);
			$i = $len-1;
			while($i >= 0 && $name[$i]!='.')
			{
				$extension = $name[$i].$extension;
				$i--;
			}
			$j = 0;
			$newname = "";
			while($len > $j && $name[$j]!='.')
			{
				$newname .= $name[$j];
				$j++;
			}
			$name = $newname;
			if ($extension == 'zip')
			{
				#!!!Need to find a way to test if directory is created
				$tempfile=$FILES['upload']['tmp_name'];
				$tempdir=substr($tempfile,0,strrpos($tempfile,'/'));
				$tempdir .= '/'.$name.'_'.$this->objIEUtils->generateUniqueId();
				$this->folder=$tempdir;
				$objWZip=&$this->getObject('wzip','utilities');
				$objWZip->unzip($tempfile,$this->folder);
			}
		}

	return $this->folder;
	}

	/**
	 * @var object $fileLocations - relative paths to all files
	*/
	public $fileLocations;
	/**
	 * Function to retrieve all file Locations within a specified folder
	 *
	 * @param $folder - Location of folder to scan
	 *
	 * @return array $fileLocations - Locations of all files within folder
	 * 
	*/
	function locateAllFiles($folder)
	{
		$this->fileLocations = $this->objIEUtils->list_dir_files($folder,1);
	
		return $this->fileLocations;
	}

	/**
	 * @var object $imsmanifestLocation - relative paths to IMS manifest file
	*/
	public $imsmanifestLocation;
	/**
	 * Scans a specified array of strings for a specified string
	 *
	 * @param array $files - list of file locations
	 * @param string $regex - filename to scan in regular expression form
	 *
	 * @return $imsmanifestLocation
	 *
	*/
	public function locateIMSFile($files, $regex)
	{
		foreach($files as $aFile)
		{
			if(preg_match($regex, $aFile))
			{
				$this->imsmanifestLocation = $aFile;
			}
		}

		return $this->imsmanifestLocation;
	}

	/**
	 * @var object $simpleXmlObj - simplexml object to access xml file
	*/
	public $simpleXmlObj;
	/**
	 * Takes the imsmanifest.xml file as input
	 *
	 * @param string $imsFileLocation
	 *
	 * @return simpleXml $simpleXmlObj
	 *
	*/
	function loadSimpleXml($imsFileLocation)
	{
		#Load imsmanifest.xml file
		if(file_exists($imsFileLocation)) 
		{
    			$this->simpleXmlObj = simplexml_load_file($imsFileLocation);
		}
		else 
		{
    			return  "simpleXmlError";
		}
		
		return $this->simpleXmlObj;
	}

	/**
	 * @var object $domDocumentObj - dom document object to access xml file
	*/
	public $domDocumentObj;
	/**
	 * Takes the imsmanifest.xml file as input
	 *
	 * @param string $imsFileLocation
	 *
	 * @return DOMDocument $domDocumentObj
	 *
	*/
	function loadDOMDocument($imsFileLocation)
	{
		$this->domDocumentObj = new DOMDocument();
		#Create domdocument object to access xml file
		if($this->domDocumentObj->load($imsFileLocation))
		{
		}
		else 
		{
    			return  "domReadError";
		}

		return $this->domDocumentObj;
	}

	/**
	 * @var object $xpathObj - xpath object to access xml file
	*/
	public $xpathObj;
	/**
	 * Takes a DOM document as input
	 *
	 * @param DOMDocument $domDocumentObj
	 *
	 * @return DOMXPath $domDocumentObj
	 *
	*/
	function loadXPath($domDocumentObj)
	{
		#Create xpath object to access xml file
		$this->xpathObj = new DOMXPath($domDocumentObj);

		return $this->xpathObj;
	}

	/**
	 * @var object $newCourse - all course data needed to create course
	*/
	public $newCourse;
	/**
	 * Extract the course information from imsmanifest.xml
	 *
	 * @return TRUE - Successful execution
	 *
	*/
	function extractCourseData($xml, $doc, $xpath)
	{
		#Set eduCommons namespaces
		$xpath->registerNamespace("educommons", "http://albatross.ed.usu.edu/xsd/educommons_v1");
		#Set imsmd namespaces
		$xpath->registerNamespace("imsmd", "http://www.imsglobal.org/xsd/imsmd_v1p2");
		#Create course
		#Establish which resource is a course
		foreach($xml->resources->resource as $resource)
		{
			#Retrieve file type
			$objectType = $resource->metadata->eduCommons->objectType;
			#Cast to string
			$objectType = (string)$objectType;
			#Remove whitespaces for comparison
			$objectType = trim($objectType);
			#Check file type
			#Course
			if(strcmp($objectType,"Course")==0)
			{
#course id (contextcode)
				$courseId = $resource->metadata->eduCommons->courseId;
				$courseId = (string)$courseId;
				$courseId = trim($courseId);
				if(!(strlen($courseId) > 1))
					return 'courseReadError';
				$this->newCourse['contextcode'] = $courseId.'_'.$this->objIEUtils->generateUniqueId();
#course title (title)
				$courseTitle = $resource->metadata->lom->general->title->langstring;
				$courseTitle = (string)$courseTitle;
				$courseTitle = trim($courseTitle);
				$this->newCourse['title'] = $courseTitle;
				if(!(strlen($courseTitle) > 1))
					return 'courseReadError';
#course title (menu text)
				$this->newCourse['menutext'] = $courseTitle;
#course title (userId)
				$this->newCourse['userid'] = "1";
#course identifier (not in use)
				$courseIdentifier = $resource['identifier'];
				$courseIdentifier = (string)$courseIdentifier;
				$courseIdentifier = trim($courseIdentifier);
				$this->newCourse['courseIdentifier'] = $courseIdentifier;
				if(!(strlen($courseIdentifier) > 1))
					return 'courseReadError';
#course description (about)
				$courseDescription = $resource->metadata->lom->general->description->langstring;
				$courseDescription = (string)$courseDescription;
				$courseDescription = trim($courseDescription);
				$this->newCourse['about'] = $courseDescription;
				if(!(strlen($courseDescription) > 1))
					$this->newCourse['about'] = "Description Not Available";
#course status (status)
				$courseStatus = "Public";
				$this->newCourse['isactive'] = $courseStatus;
#course access (access)
				$courseAccess = "UnPublished";
				$this->newCourse['isclosed'] = $courseAccess;
			}
		}

		return $this->newCourse;
	}

	/**
	 * @var object $resourceFileNames - the filenames of resources, including type
	*/
	public $resourceFileNames;
	/**
	 * @var object $alldata - all data needed to add file to database
	*/
	public $alldata;
	/**
	 * Move the resources to appropriate locations on the file system
	 * and to the proper databases
	 *
	 * @param $xml
	 * @param $folder
	 * @param $newCourse
	 *
	 * @return TRUE - Successful execution
	 *
	*/
	function writeResources($xml, $folder, $newCourse)
	{
		#Pre-initialize variables
		$resourceFileLocations = array();
		#First add course to Chisimba database
		foreach($xml->resources->resource as $resource)
		{
			#Retrieve file type
			$objectType = $resource->metadata->eduCommons->objectType;
			#Cast to string
			$objectType = (string)$objectType;
			#Remove whitespaces for comparison
			$objectType = trim($objectType);
			#Check file type
			#Course
			if(strcmp($objectType,"Course")==0)
			{
				$filename = $resource->metadata->lom->metametadata->catalogentry->entry->langstring;
				$filename = (string)$filename;
				$filename = trim($filename);
				#Retrieve relative file location
				$file = $resource->file['href'];
				$file = (string)$file;
				$file = trim($file);
				$fileLocation = $folder."/".$file;
				#Chech if file exists on local system
				if(!file_exists($fileLocation))
				{
					$folders = $this->objIEUtils->list_dir($folder, '0', '0');
					foreach($folders as $aFolder)
					{
						$testerLocation = $folder."/".$aFolder."/".$file;

						if(file_exists($testerLocation))
						{
							$fileLocation = $testerLocation;
						}
					}
				}
				#Retrieve contents of file
				$fileContents = file_get_contents($fileLocation);
				#Save all data
				$this->allData['0'] = array('resource' => $resource,
							'fileContents' => $fileContents,
							'contextCode' => $this->contextCode,
							'file' => $file,
							'objectType' => $objectType,
							'filename' => $filename);
				#Check debug flag
				if($this->objDebug)
				{
					echo "Course"."<br />";
					echo $filename."<br />";
					echo $file."<br />";
					echo $fileLocation."<br />";
				}
			}
		}
		#Add all other resources to Chisimba database
		foreach($xml->resources->resource as $resource)
		{
			static $i = 1;
			#Retrieve file type
			$objectType = $resource->metadata->eduCommons->objectType;
			#Cast to string
			$objectType = (string)$objectType;
			#Remove whitespaces for comparison
			$objectType = trim($objectType);
			#Check file type
			#Document
			if(strcmp($objectType,"Document")==0)
			{
				$filename = $resource->metadata->lom->metametadata->catalogentry->entry->langstring;
				$filename = (string)$filename;
				$filename = trim($filename);
				#Retrieve relative file location
				$file = $resource->file['href'];
				$file = (string)$file;
				$file = trim($file);
				$fileLocation = $folder."/".$file;
				#Chech if file exists on local system
				if(!file_exists($fileLocation))
				{
					$folders = $this->objIEUtils->list_dir($folder, '0', '0');
					foreach($folders as $aFolder)
					{
						$testerLocation = $folder."/".$aFolder."/".$file;

						if(file_exists($testerLocation))
						{
							$fileLocation = $testerLocation;
						}
					}
				}
				#Write file contents to documents folder
				#Retrieve contents of file
				$fileContents = file_get_contents($fileLocation);
				#Check filename
				if(!preg_match("/.html|.htm/",$filename))
				{
					#Correct filename
					$filename = $filename.".html";
					$this->fileMod = TRUE;
					$this->objIEUtils->fileModOn();
				}
				#New location for Documents
				$newLocation = $this->docsLocation."/".$filename;
				#Store resource locations
				$resourceFileLocations[$i] = $newLocation;
				#Store filesname
				$this->resourceFileNames[$i] = $filename;
				#Open images directory
				$fp = fopen($newLocation,'w');
				#Write the file to images directory
				if((fwrite($fp, $fileContents) === FALSE))
				{
					return  "writeResourcesError";
				}
				#Close the directory
				fclose($fp);
				#Save all data
				$this->allData[$i] = array('resource' => $resource,
							'fileContents' => $fileContents, 
							'contextCode' => $this->contextCode, 
							'file' => $file,
							'objectType' => $objectType,
							'filename' => $filename);
				#Check debug flag
				if($this->objDebug)
				{
					echo "Document"."<br />";
					echo $filename."<br />";
					echo $file."<br />";
					echo $fileLocation."<br />";
				}
			}
			#File
			if(strcmp($objectType,"File")==0)
			{
				$filename = $resource->metadata->lom->metametadata->catalogentry->entry->langstring;
				$filename = (string)$filename;
				$filename = trim($filename);
				#Retrieve relative file location
				$file = $resource->file['href'];
				$file = (string)$file;
				$file = trim($file);
				$fileLocation = $folder."/".$file;
				#Chech if file exists on local system
				if(!file_exists($fileLocation))
				{
					$folders = $this->objIEUtils->list_dir($folder, '0', '0');
					foreach($folders as $aFolder)
					{
						$testerLocation = $folder."/".$aFolder."/".$file;

						if(file_exists($testerLocation))
						{
							$fileLocation = $testerLocation;
						}
					}
				}
				#Write file contents to documents folder
				#Retrieve contents of file
				$fileContents = file_get_contents($fileLocation);
				#New location for Files
				$newLocation = $this->docsLocation."/".$filename;
				#Store resource locations
				$resourceFileLocations[$i] = $newLocation;
				#Store filesname
				$this->resourceFileNames[$i] = $filename;
				#Open images directory
				$fp = fopen($newLocation,'w');
				#Write the file to images directory
				if((fwrite($fp, $fileContents) === FALSE))
				{
					return  "writeResourcesError";
				}
				#Close the directory
				fclose($fp);
				#Save all data
				$this->allData[$i] = array('resource' => $resource,
							'fileContents' => $fileContents,
							'contextCode' => $this->contextCode,
							'file' => $file,
							'objectType' => $objectType,
							'filename' => $filename);
				#Check debug flag
				if($this->objDebug)
				{
					echo "Course"."<br />";
					echo $filename."<br />";
					echo $file."<br />";
					echo $fileLocation."<br />";
				}
			}
			#Image
			if(strcmp($objectType,"Image")==0)
			{
				#Retrieve filename
				$filename = $resource->metadata->lom->metametadata->catalogentry->entry->langstring;
				$filename = (string)$filename;
				$filename = trim($filename);
				#Retrieve relative file location
				$file = $resource->file['href'];
				$file = (string)$file;
				$file = trim($file);
				#Retrieve absolute file location
				$fileLocation = $folder."/".$file;
				#Chech if file exists on local system
				if(!file_exists($fileLocation))
				{
					$folders = $this->objIEUtils->list_dir($folder, '0', '0');
					foreach($folders as $aFolder)
					{
						$testerLocation = $folder."/".$aFolder."/".$file;

						if(file_exists($testerLocation))
						{
							$fileLocation = $testerLocation;
						}
					}
				}
				#Write file contents to images folder
				#Retrieve contents of file
				$fileContents = file_get_contents($fileLocation);
				#Check filename
				if(!preg_match("/.jpg|.gif|.png/",$filename))
				{
					#Correct filename
					$filename = $filename.".jpg";
					$this->fileMod = TRUE;
					$this->objIEUtils->fileModOn();
				}
				#New location for Images
				$newLocation = $this->imagesLocation."/".$filename;
				#Store resource locations
				$resourceFileLocations[$i] = $newLocation;
				#Store filesname
				$this->resourceFileNames[$i] = $filename;
				#Open images directory
				$fp = fopen($newLocation,'w');
				#Write the file to images directory
				if((fwrite($fp, $fileContents) === FALSE))
				{
					return  "writeResourcesError";
				}
				#Close the directory
				fclose($fp);
				#Save all data
				$this->allData[$i] = array('resource' => $resource,
							'fileContents' => $fileContents,
							'contextCode' => $this->contextCode,
							'file' => $file,
							'objectType' => $objectType,
							'filename' => $filename);
				#Check debug flag
				if($this->objDebug)
				{
					echo "Image"."<br />";
					echo $filename."<br />";
					echo $file."<br />";
					echo $fileLocation."<br />";
				}
			}
		$i++;
		}
		if($this->objDebug)
		{
			var_dump($resourceFileLocations);
			var_dump($this->resourceFileNames);
		}

		return $this->allData;
	}

	public $imageIds;
	/**
	 * Writes all images used by course to new database (Chisimba)
	 * Makes query to tbl_files
	 * 
	 * @param string $folder - selected course
	 * @return array $indexFolder - list of id fields belonging to images
	*/
	function uploadImagesToChisimba($folder = '')
	{
		#Initialize Inner variables
		parent::init('tbl_files');
		#Add Images to database
		$indexFolder = $this->objIndex->indexFolder($this->imagesLocation, $this->objUser->userId());
		#Match image Id's to image names
		foreach($indexFolder as $pageId)
		{
			$filter = "WHERE id = '$pageId'";
			$result = $this->getAll($filter);
			$aFile = $result['0']['filename'];
			$this->imageIds[$aFile] = $pageId;
		}

		return $this->imageIds;
	}

	/**
	 * Function to return the order in which pages should be added
	 * 
	 * @param simpleXML object 
	 *
	 * @return array $titles 
	 * 
	*/
	function getStructure($xml)
	{
		$titles = array();
		$i = 0;
		foreach($xml->organizations->organization->item as $item)
		{
			$aTitle = (string)$item->title;
			$aTitle = trim($aTitle);
			$titles[$i] = $aTitle;
			$i++;
		}

		return $titles;
	}

	public $resourceIds;
	public $chapterIds;
	/**
	 * Control loading resources into Chisimba
	 * and file manipulation functions
	 *
	 * @param array $writeData - all data needed
	 * @param array $structure - the order in which pages should be added
	 *
	 * @return array $menutitles - all menutitles of pages
	 *
	*/
	function loadToChisimba($writeData, $structure)
	{
		#Pre-initialize variables
		static $i = 0;
		static $j = 0;
		static $k = 0;
		$menutitles = array();
		$orderedData = array();
		$numItems = count($structure);
		$numVisibleItems = 0;
		#Change Structure of data
		#Add all pages
		foreach($writeData as $resource)
		{
			#Unpack data
			$xmlResource = $resource['resource'];
			$objectType = $resource['objectType'];
			$objectType = (string)$objectType;
			$objectType = trim($objectType);
			$resourceId = (string)$xmlResource['identifier'];
			$resourceId = trim($resourceId);
			#Check file type
			if(strcmp($objectType,"Image")!=0)
			{
				#Retrieve title
				$title = $xmlResource->metadata->lom->general->title->langstring;
				$title = (string)$title;
				$title = trim($title);
				$index = array_search($title, $structure);
				if($index === FALSE)
				{
					$orderedData[$j+$numItems] = $resource;
					$j++;
				}	
				else
				{
					$orderedData[$index] = $resource;
					$this->chapterIds[$k] = $resourceId;
					$k++;
					$numVisibleItems++;
					if(strcmp($objectType,"Course")==0)
					{
						$indexOfCourse = $index;
					}
				}
			}
		}
		#Fix indexing
		for($i=0;$i<count($orderedData);$i++)
		{
			$indexedData[$i] = $orderedData[$i];
		}
		$start = 0;
		$before = $indexOfCourse;
		$after = count($orderedData);
		$fromStart = array_slice($indexedData, $start, $before);
		$toEnd = array_slice($indexedData, $before+1, $after);
		$course = array_slice($indexedData, $before, $before);
		$orderedData = array_merge($course, $fromStart, $toEnd);
		#Retrieve resource Id's
		foreach($orderedData as $resource)
		{
			#Unpack data
			$xmlResource = $resource['resource'];
			$fileContents = $resource['fileContents'];
			$resourceId = (string)$xmlResource['identifier'];
			$resourceId = trim($resourceId);
			foreach($this->resourceFileNames as $aFile)
			{
				if($this->fileMod)
					$aFile = preg_replace("/.html|.htm|.jpg|.gif|.png/","",$aFile);
				$regex = '/(href=".*'.$aFile.'.*?")/i';
				preg_match_all($regex, $fileContents, $matches, PREG_SET_ORDER);
				if($matches)
				{
					$index = array_search($resourceId, $this->chapterIds);
					if($index === FALSE)
					{
					}
					else
					{
					}
				}
			}

			$this->resourceIds[$i] = $resourceId;
			$i++;
		}
		$this->addChapters();
		#Add ordered data
		for($i=0;$i<count($orderedData);$i++)
		{
			#Unpack data
			$xmlResource = $orderedData[$i]['resource'];
			$fileContents = $orderedData[$i]['fileContents'];
			$contextCode = $orderedData[$i]['contextCode'];
			$file = $orderedData[$i]['file'];
			$objectType = $orderedData[$i]['objectType'];
			#Cast to string
			$objectType = (string)$objectType;
			#Remove whitespaces for comparison
			$objectType = trim($objectType);
			#Write Course to Chisimba database
			if($i > $numVisibleItems)
				$menutitle = $this->passPage($xmlResource, $fileContents, $contextCode,$i,'N');
			else
				$menutitle = $this->passPage($xmlResource, $fileContents, $contextCode,$i,'Y');
			$menutitle = (string)$menutitle;
			$menutitles[$i] = $menutitle;
		}

		return $menutitles;
	}

	public $chapterId;
	function addChapters()
	{
		#Add Chapters
		#Course
		$title = $this->contextCode;
		$intro = $this->newCourse['about'];
		$visibility = 'Y';
		$this->chapterId = $this->objChapters->addChapter('', $title, $intro);
		$result = $this->objContextChapters->addChapterToContext($this->chapterId, $title, $visibility);
		#Add additional Chapters
		
	}

	/**
	 * Retrieve page details and pass data
	 * database insertion functions
	 *
	 * @param simpleXml $resource - simple xml object
	 * @param string $fileContents - html page
	 * @param string $contextCode - course contextcode
	 *
	 * @return boolean 
	 *
	*/
	function passPage($resource, $fileContents, $contextCode, $bookmark='', $isBookmark='')
	{
		#Check if menutitle exists
		$resId = (string)$resource['identifier'];
		$resId = trim($resId);
		foreach($this->simpleXmlObj->organizations->organization->item as $item)
		{
			$orgId = (string)$item['identifierref'];
			$orgId = trim($orgId);
			if(strcmp($resId, $orgId)==0)
			{
				$aTitle = (string)$item->title;
				$aTitle = trim($aTitle);
				$menutitle = $aTitle;
			}
		}
		#Retrieve page data
		$titleid = $resource->metadata->lom->general->title->langstring;
		if(!(strlen($menutitle) > 0))
			$menutitle = $resource->metadata->lom->general->description->langstring;
		$content = $fileContents;
		$language = $resource->metadata->lom->general->language;
		$headerscript = "";
		$filename = $resource->metadata->lom->metametadata->catalogentry->entry->langstring;
		#Load to $values
		if(!strlen($menutitle) > 0)
		{
			$menutitle = $titleid;
		}
		$values = array('titleid' => (string)$titleid,
				'menutitle' => (string)$menutitle,
				'content' => (string)$content,
				'language' => (string)$language,
				'headerscript' => (string)$headerscript,
				'filename' => trim((string)$filename),
				'bookmark' => $bookmark,
				'isbookmark' => $isBookmark);
		#Insert into database
		$writePage = $this->writePage($values, $contextCode);

		return $menutitle;
	}

	/**
	 * Write content to Chisimba database
	 *
	 * @param array $values - page details
	 * @param string $contextCode - course contextcode
	 *
	 * @return boolean 
	 *
	*/
	function writePage($values, $contextCode)
	{
		#duplication error needs to be fixed by Tohir
		parent::init('tbl_contextcontent_pages');
		$menutitle = $values['menutitle'];
		$filter = "WHERE menutitle = '$menutitle'";
		$result = $this->getAll($filter);
		#un-comment to force no duplication
		#if(!count($result) > 1)
		#{
			#No idea!!!
			$tree = $this->objContentOrder->getTree($contextCode, 'dropdown', $parent);
			#Add page
        		$titleId = $this->objContentTitles->addTitle('', 
								$values['menutitle'],
								$values['content'],
								$values['language'],
								$values['headerscript']);
        		$this->pageIds[$values['filename']] = $this->objContentOrder->addPageToContext($titleId, $parent, $contextCode, $this->chapterId, $values['bookmark'], $values['isbookmark']);
		#}

		return TRUE;
	}

	/**
	 * Control function to manipulate html pages and resources
	 * and re-insert into database
	 * 
	 * @param array $menutitles - each menu title of html page
	 * @param array $fileNames - the file name of resource
	 *
	 * @return TRUE - on successful execution
	 *
	*/
	function rebuildHtml($menutitles, $fileNames)
	{
		#switch tables
		parent::init('tbl_contextcontent_pages');
		#Retrieve resources
		#Manipulate images
		static $i = 0;
		static $j = 0;
		foreach($menutitles as $menutitle)
		{
			$filter = "WHERE menutitle = '$menutitle'";
			$result = $this->getAll($filter);
			if(count($result) > 0)
			{
				#Retrieve page contents
				$fileContents = $result['0']['pagecontent'];
				$id = $result['0']['id'];
				#Rewrite images source in html
				$page = $this->objIEUtils->changeImageSRC($fileContents, $this->contextCode, $this->resourceFileNames, $this->imageIds);
				#Reinsert into database with updated images
				if(strlen($page) > 1 )
				{
					$update = $this->update('id', $id, array('pagecontent' => $page));
					if($i==0)
					{
						#Modify about in tbl_context
						parent::init('tbl_context');
						$this->update('id', $this->courseId, array('about' => $page));
						#switch tables
						parent::init('tbl_contextcontent_pages');
						$i++;
					}
				}
			}
		}
		#Manipulate links 
		foreach($menutitles as $menutitle)
		{
			$filter = "WHERE menutitle = '$menutitle'";
			$result = $this->getAll($filter);
			if(count($result) > 0)
			{
				#Retrieve page contents
				$fileContents = $result['0']['pagecontent'];
				$id = $result['0']['id'];
				#Rewrite links source in html
				$page = $this->objIEUtils->changeLinkUrl($fileContents, $this->contextCode, $this->resourceFileNames, $this->pageIds);
				#Reinsert into database with updated links
				if(strlen($page) > 1 )
				{
					$update = $this->update('id', $id, array('pagecontent' => $page));
					if($j==0)
					{
						#Modify about in tbl_context
						parent::init('tbl_context');
						$this->update('id', $this->courseId, array('about' => $page));
						#switch tables
						parent::init('tbl_contextcontent_pages');
						$j++;
					}
				}
			}
		}

		return TRUE;
	}

  	/**
	 * Sets debugging on
	 *
    	 * @param NULL
	 *
	*/
	function debugOn()
	{
		$this->objDebug = TRUE;
	}

	/**
	 * Sets debugging off
	 *
    	 * @param NULL
	 *
	*/
	function debugOff()
	{
		$this->objDebug = FALSE;
	}
}
?>