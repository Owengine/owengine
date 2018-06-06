<?php

/******** OWENGINE EXTENDED FUNCTIONALITY FOR COMPATIBILITY WITH OXWALL.COM/ORG******
*************************************************************************************
************ DO NOT REMOVE THIS FILE OR SYSTEM WILL NOT WORK ************************
*************************************************************************************
*************************************************************************************/

class BOL_StorageService extends BOL_StorageServiceOxwall
{
    const UPDATE_SERVER_OWENGINE = "https://www.owengine.com/";
	
    public $themeService;
    public $pluginService;
    public static $classInstance;
	
	private $invalidItems;
	private $validItems;

    private $unknownItems = array();
    
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }
	
	
	public function __construct()
    {
		$this->pluginService = BOL_PluginService::getInstance();
        $this->themeService = BOL_ThemeService::getInstance();
		
		parent::__construct();
        

	}

    public function checkUpdates()
    {
  $requestArray = array("platform" => array(self::URI_VAR_BUILD => OW::getConfig()->getValue("base", "soft_build"), "engine" => "owengine", "site-url" => OW::getRouter()->getBaseUrl()), "items" => array());

        $plugins = $this->pluginService->findRegularPlugins();

        /* @var $plugin BOL_Plugin */
        foreach ( $plugins as $plugin )
        {
            $requestArray["items"][] = array(
                self::URI_VAR_KEY => $plugin->getKey(),
                self::URI_VAR_DEV_KEY => $plugin->getDeveloperKey(),
                self::URI_VAR_BUILD => $plugin->getBuild(),
                self::URI_VAR_LICENSE_KEY => $plugin->getLicenseKey(),
                self::URI_VAR_ITEM_TYPE => self::URI_VAR_ITEM_TYPE_VAL_PLUGIN
            );
        }
        //check all manual updates before reading builds in DB
        $this->themeService->checkManualUpdates();
        $themes = $this->themeService->findAllThemes();

        /* @var $dto BOL_Theme */
        foreach ( $themes as $dto )
        {
            $requestArray["items"][] = array(
                self::URI_VAR_KEY => $dto->getKey(),
                self::URI_VAR_DEV_KEY => $dto->getDeveloperKey(),
                self::URI_VAR_BUILD => $dto->getBuild(),
                self::URI_VAR_LICENSE_KEY => $dto->getLicenseKey(),
                self::URI_VAR_ITEM_TYPE => self::URI_VAR_ITEM_TYPE_VAL_THEME
            );
        }

        $data = $this->triggerEventBeforeRequest();
        $data["info"] = json_encode($requestArray);


        $params = new UTIL_HttpClientParams();
        $params->addParams($data);
        $response = UTIL_HttpClient::post($this->getStorageUrlOwEngine(self::URI_CHECK_ITEMS_FOR_UPDATE), $params);

        if ( !$response || $response->getStatusCode() != UTIL_HttpClient::HTTP_STATUS_OK )
        {
			
            OW::getLogger()->addEntry(__CLASS__ . "::" . __METHOD__ . "#" . __LINE__ . " storage owengine request status is not OK",
                "core.update");
			
			return false;
        }

        $resultArray = array();

        if ( $response->getBody() )
        {
			$resultArray = json_decode($response->getBody(), true);
        }


        if ( empty($resultArray) || !is_array($resultArray) )
        {
            OW::getLogger()->addEntry(__CLASS__ . "::" . __METHOD__ . "#" . __LINE__ . " remote request returned empty result",
                "core.update");

			return false;
        }
			
		OW::getLogger()->addEntry(__CLASS__ . "::" . __METHOD__ . "#" . __LINE__ . " storage owengine request status is OK",
                "core.update");
       
				
		$validItems = array(); 
        if ( !empty($resultArray["validLicense"]) )
		{
			$validItems = $resultArray["validLicense"];
		}
        if ( !empty($resultArray["update"]) )
        {
            if ( !empty($resultArray["update"]["platform"]) && (bool) $resultArray["update"]["platform"] )
            {
                OW::getConfig()->saveConfig("base", "update_soft", 1);
            }

            if ( !empty($resultArray["update"]["items"]) )
            {
                $this->updateItemsUpdateStatus($resultArray["update"]["items"]);
            }
        }

        $items = !empty($resultArray["invalidLicense"]) ? $resultArray["invalidLicense"] : array();
        $itemsvalid = !empty($resultArray["validLicense"]) ? $resultArray["validLicense"] : array();
		
		$this->invalidItems = $items;
		$this->validItems = $itemsvalid;
		
        $this->updateItemsLicenseStatus( $items ); 
		
        $oxrequestArray = array("items" => array());
        $oxrequestArray["items"] = $this->remainingItems($requestArray["items"], $items, $itemsvalid); 
        
		return $this->checkUpdatesOxwall( $oxrequestArray ); //OXWALL COMPATIBILITY ADDED

        return true;

    }
    private function remainingItems($requestedArray, $invalid, $valid)
    {

        $requestArray = array();        
        $checkedItems = array_merge($invalid, $valid);
        
    	foreach ( $requestedArray as $item )
		{
            $thischecked = false;
            foreach($checkedItems as $checked)
            {
                if ($item[self::URI_VAR_KEY] == $checked[self::URI_VAR_KEY] 
                && $item[self::URI_VAR_DEV_KEY] == $checked[self::URI_VAR_DEV_KEY]
                && $item[self::URI_VAR_ITEM_TYPE] == $checked[self::URI_VAR_ITEM_TYPE]
                )
                {
                    $thischecked = true;
                }
            }
            if (!$thischecked)
            {
                $requestArray[] = $item;
            }
        }

        return $requestArray;
    }
    public function checkUpdatesOxwall( $requestArray )
    {
      
        $data = $this->triggerEventBeforeRequest();
        $data["info"] = json_encode($requestArray);

        $params = new UTIL_HttpClientParams();
        $params->addParams($data);
        $response = UTIL_HttpClient::post($this->getStorageUrl(self::URI_CHECK_ITEMS_FOR_UPDATE), $params);

        if ( !$response || $response->getStatusCode() != UTIL_HttpClient::HTTP_STATUS_OK )
        {
			
            OW::getLogger()->addEntry(__CLASS__ . "::" . __METHOD__ . "#" . __LINE__ . " storage request status is not OK",
                "core.update");
			
			return false;
        }

		
		OW::getLogger()->addEntry(__CLASS__ . "::" . __METHOD__ . "#" . __LINE__ . " storage OXWALL request status is OK",
                "core.update");
		OW::getLogger()->addEntry("request: {$resultArray}",
                "core.update");

				$resultArray = array();

        if ( $response->getBody() )
        {
				$resultArray = json_decode($response->getBody(), true);
        }

        if ( empty($resultArray) || !is_array($resultArray) )
        {
            OW::getLogger()->addEntry(__CLASS__ . "::" . __METHOD__ . "#" . __LINE__ . " remote request returned empty result",
                "core.update");

            return false;
        }

        if ( !empty($resultArray["update"]) )
        {
            if ( !empty($resultArray["update"]["platform"]) && (bool) $resultArray["update"]["platform"] )
            {
               // OW::getConfig()->saveConfig("base", "update_soft", 1);
            }

            if ( !empty($resultArray["update"]["items"]) )
            {
                $this->updateItemsUpdateStatus($resultArray["update"]["items"]);
            }
        }

        $items = !empty($resultArray["invalidLicense"]) ? $resultArray["invalidLicense"] : array();
        $items = array_merge($this->invalidItems, $items);

        $this->updateItemsLicenseStatus($items);
        
        return true;
    }	
	
    /**
     * Returns information from remote storage for store item.
     *
     * @param string $key
     * @param string $devKey
     * @param int $currentBuild
     * @return array
     */
    public function getItemInfoForUpdate( $key, $devKey, $currentBuild = 0 )
    {
        $params = array(
            self::URI_VAR_KEY => trim($key),
            self::URI_VAR_DEV_KEY => trim($devKey),
            self::URI_VAR_BUILD => (int) $currentBuild
        );

        $data = array_merge($params, $this->triggerEventBeforeRequest($params));
       
		$resultArray = $this->requestGetResultAsJson($this->getStorageUrlOwEngine(self::URI_GET_ITEM_INFO), $data);
		
		if ( !empty($resultArray) || is_array($resultArray) )
        {
            if (isset($resultArray["unknown"]))
            {
                $this->addUnknownItems($params);
                return parent::getItemInfoForUpdate( $key, $devKey, $currentBuild ); //OXWALL FALLBACK
                
            }else{
			    return $resultArray;
            }
		}
    }

    /**
     * Returns information from remote storage for platform.
     *
     * @return array
     */
    public function getPlatformInfoForUpdate()
    {
        $data = $this->triggerEventBeforeRequest();
        return $this->requestGetResultAsJson($this->getStorageUrlOwEngine(self::URI_GET_PLATFORM_INFO), $data);
    }

    /**
     * Downloads platform update archive and puts it to the provided path.
     *
     * @return string
     * @throws LogicException
     */
    public function downloadPlatform()
    {
        $params = array(
            "platform-version" => OW::getConfig()->getValue("base", "soft_version"),
            "platform-build" => OW::getConfig()->getValue("base", "soft_build"),
            "site-url" => OW::getRouter()->getBaseUrl()
        );

        $data = array_merge($params, $this->triggerEventBeforeRequest($params));
		
        $paramsObj = new UTIL_HttpClientParams();
        $paramsObj->addParams($data);
        $response = UTIL_HttpClient::get($this->getStorageUrlOwEngine(self::URI_DOWNLOAD_PLATFORM_ARCHIVE), $paramsObj);

        if ( !$response || $response->getStatusCode() != UTIL_HttpClient::HTTP_STATUS_OK || !$response->getBody() )
        {
            throw new LogicException("Can't download file. Server returned empty file.");
        }

        $fileName = UTIL_String::getRandomStringWithPrefix("platform_archive_", 8, UTIL_String::RND_STR_NUMERIC) . ".zip";
        $archivePath = OW_DIR_PLUGINFILES . DS . $fileName;
        file_put_contents($archivePath, $response->getBody());

        return $archivePath;
    }

    /**
     * Downloads item archive and returns it's local path.
     *
     * @param string $key
     * @param string $devKey
     * @param string $licenseKey
     * @return string
     * @throws LogicException
     */
    public function downloadItem( $key, $devKey, $licenseKey = null )
    {
        $params = array(
            self::URI_VAR_KEY => trim($key),
            self::URI_VAR_DEV_KEY => trim($devKey),
            self::URI_VAR_LICENSE_KEY => $licenseKey != null ? trim($licenseKey) : null,
            "site-url" => OW::getRouter()->getBaseUrl()
        );

        $data = array_merge($params, $this->triggerEventBeforeRequest($params));

        $paramsObj = new UTIL_HttpClientParams();
        $paramsObj->addParams($data);

        $url = $this->getDownloadUrl($params);

        $response = UTIL_HttpClient::get($url, $paramsObj);

        if ( !$response || $response->getStatusCode() != UTIL_HttpClient::HTTP_STATUS_OK || !$response->getBody() )
        {
            throw new LogicException("Can't download file. Server returned empty file.");
        }
		
		$fileName = UTIL_String::getRandomStringWithPrefix("plugin_archive_", 8, UTIL_String::RND_STR_NUMERIC) . ".zip";
		$archivePath = OW_DIR_PLUGINFILES . DS . $fileName;
		file_put_contents($archivePath, $response->getBody());

		return $archivePath;
    }

    /**
     * Checks if license key is valid for store item.
     *
     * @param string $key
     * @param string $developerKey
     * @param string $licenseKey
     * @return bool
     */
	 
    public function checkLicenseKey( $key, $devKey, $licenseKey )
    {	
		
        if ( empty($key) || empty($devKey) || empty($licenseKey) )
        {
            return null;
        }

        $params = array(
            self::URI_VAR_KEY => trim($key),
            self::URI_VAR_DEV_KEY => trim($devKey),
            self::URI_VAR_LICENSE_KEY => trim($licenseKey)
        );

        $data = array_merge($params, $this->triggerEventBeforeRequest($params));
		
        $result = $this->requestGetResultAsJson($this->getStorageUrlOwEngine(self::URI_CHECK_LECENSE_KEY), $data);
		      
        if ( $result === null )
        {
            return null;
        }
		
        
		if ($result === true){
			return true;
		}
        
        if ($result == "unknown"){
            $this->addUnknownItems($params);
            return parent::checkLicenseKey( $key, $devKey, $licenseKey ); //OXWALL FALLBACK
        }
        return $result === true ? true : false;
		
    }
    private function addUnknownItems($params)
    {        
        $this->unknownItems[] = $params;
    }
	private function getDownloadUrl($params)
    {
        if ($this->isUnknownItem($params))
        {
            return     $this->getStorageUrl(self::URI_DOWNLOAD_ITEM); //OXWALL FALLBACK
        }
        return     $this->getStorageUrlOwEngine(self::URI_DOWNLOAD_ITEM);
    }
    
    //Return true if item was unknown by Owengine.com
    private function isUnknownItem($params)
    {
            foreach( $this->unknownItems as $unknown )
            {
                if ($item[self::URI_VAR_KEY] == $unknown[self::URI_VAR_KEY] 
                && $item[self::URI_VAR_DEV_KEY] == $unknown[self::URI_VAR_DEV_KEY]
                && $item[self::URI_VAR_LICENSE_KEY] == $unknown[self::URI_VAR_LICENSE_KEY]
                )
                {
                    return true;
                }
            }
        return false;
    }



    public function getStorageUrlOwEngine( $uri )
    {		

	   return self::UPDATE_SERVER_OWENGINE . UTIL_String::removeFirstAndLastSlashes($uri) . "/";	   
    }


}
