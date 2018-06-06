<?php

class INSTALL
{
    /**
     * 
     * @return INSTALL_Storage
     */
    public static function getStorage()
    {
        return INSTALL_Storage::getInstance();    
    }
    
    /**
     * 
     * @return INSTALL_FeedBack
     */
    public static function getFeedback()
    {
        return INSTALL_FeedBack::getInstance();    
    }

    /**
     * 
     * @return INSTALL_CMP_Steps
     */
    public static function getStepIndicator()
    {
        static $stepIndicator;
        
        if ( empty($stepIndicator) )
        {
            $stepIndicator = new INSTALL_CMP_Steps( self::getPredefinedPluginList(true) );
        }

        return $stepIndicator;    
    }

    /**
     * 
     * @return INSTALL_ViewRenderer
     */
    public static function getViewRenderer()
    {
        return INSTALL_ViewRenderer::getInstance();
    }

    /**
     * Get predefined plugin list
     *
     * @param boolean $onlyOptional
     * @return array
     */
    public static function getPredefinedPluginList($onlyOptional = false)
    {
        $fileContent = file_get_contents(INSTALL_DIR_FILES . 'plugins.txt');
        $pluginForInstall = explode("\n", $fileContent);
        $resultPluginList = array();

        foreach ( $pluginForInstall as $pluginLine )
        {
            $plInfo = explode(':', $pluginLine);
            $isAutoInstall = ( !empty($plInfo[1]) && trim($plInfo[1]) == 'auto' );

            if ( $onlyOptional && $isAutoInstall )
            {
                continue;
            }

            $resultPluginList[] = array(
                'plugin' => $plInfo[0],
                'auto' =>  $isAutoInstall
            );
        }

        return $resultPluginList;
    }
}
