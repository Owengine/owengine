<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.oxwall.org/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Oxwall software.
 * The Initial Developer of the Original Code is Oxwall Foundation (http://www.oxwall.org/foundation).
 * All portions of the code written by Oxwall Foundation are Copyright (c) 2011. All Rights Reserved.

 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2011 Oxwall Foundation. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Oxwall community software
 * Attribution URL: http://www.oxwall.org/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */

/**
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_system_plugins.base.mobile.components
 * @since 1.6.0
 */
class BASE_MCMP_ConsoleInvitationsSection extends OW_MobileComponent
{
    /**
     * @var BOL_InvitationService
     */
    private $service;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->service = BOL_InvitationService::getInstance();

        $allInvitationCount = $this->service->findInvitationCount(OW::getUser()->getId());
        if ( !$allInvitationCount )
        {
            $this->setVisible(false);
        }
    }

    public function onBeforeRender()
    {
        parent::onBeforeRender();

        $limit = MBOL_ConsoleService::SECTION_ITEMS_LIMIT;
        $this->addComponent('itemsCmp', new BASE_MCMP_ConsoleInvitations($limit));
        $this->assign('loadMore', $this->service->findInvitationCount(OW::getUser()->getId()) > $limit);

        $params = array(
            'cmdUrl' => OW::getRouter()->urlFor('BASE_MCTRL_Invitations', 'command')
        );

        $script = 'var invitationsConsole = new OWM_InvitationsConsole(' . json_encode($params) . ');';

        OW::getDocument()->addOnloadScript($script);
    }
}