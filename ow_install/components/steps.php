<?php

class INSTALL_CMP_Steps extends INSTALL_Component
{
    private $steps = array();

    /**
     * Constructor
     *
     * @param array $optionalPlugins
     */
    public function __construct(array $optionalPlugins = array())
    {
        parent::__construct();
        
        $this->add('site', 'Site');
        $this->add('db', 'Database');
        $this->add('install', 'Install');

        // allow to admin select additional plugins
        if ( $optionalPlugins )
        {
            $this->add('plugins', 'Plugins');
        }
    }
    
    public function add($key, $label, $active = false)
    {
        $this->steps[$key] = array( 
            'label' => $label,
            'active' => $active
        );
    }
    
    public function activate($key)
    {
        foreach ( $this->steps as & $step )
        {
            $step['active'] = false;
        }
        
        $this->steps[$key]['active'] = true;
    }
    
    public function onBeforeRender()
    {
        parent::onBeforeRender();
        
        $this->assign('steps', $this->steps);
    }
}