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
 * @author Aybat Duyshokov <duyshokov@gmail.com>
 * @package ow_system_plugins.base.components
 * @since 1.0
 */
abstract class BASE_CMP_Users extends OW_Component
{
    protected $showOnline = true, $list = array();

    public function getContextMenu( $userId )
    {
        return null;
    }

    abstract public function getFields( $userIdList );

    public function __construct( $list, $itemCount, $usersOnPage, $showOnline = true )
    {
        parent::__construct();

        $this->setTemplate(OW::getPluginManager()->getPlugin('base')->getCmpViewDir() . 'users.html');

        $this->list = $list;
        $this->showOnline = $showOnline;

        $page = (!empty($_GET['page']) && intval($_GET['page']) > 0 ) ? $_GET['page'] : 1;

        $this->addComponent('paging', new BASE_CMP_Paging($page, ceil($itemCount / $usersOnPage), 5));
    }

    protected function process( $list, $showOnline )
    {
        $service = BOL_UserService::getInstance();

        $idList = array();
        $userList = array();

        foreach ( $list as $dto )
        {
            $userList[] = array('dto' => $dto);
            $idList[] = $dto->getId();
        }

        $avatars = array();
        $usernameList = array();
        $displayNameList = array();
        $onlineInfo = array();
        $questionList = array();

        if ( !empty($idList) )
        {
            $avatars = BOL_AvatarService::getInstance()->getDataForUserAvatars($idList);

            foreach ( $avatars as $userId => $avatarData )
            {
                $displayNameList[$userId] = isset($avatarData['title']) ? $avatarData['title'] : '';
            }
            $usernameList = $service->getUserNamesForList($idList);

            if ( $showOnline )
            {
                $onlineInfo = $service->findOnlineStatusForUserList($idList);
            }
        }

        $showPresenceList = array();

        $ownerIdList = array();

        foreach ( $onlineInfo as $userId => $isOnline )
        {
            $ownerIdList[$userId] = $userId;
        }

        $eventParams = array(
                'action' => 'base_view_my_presence_on_site',
                'ownerIdList' => $ownerIdList,
                'viewerId' => OW::getUser()->getId()
            );

        $permissions = OW::getEventManager()->getInstance()->call('privacy_check_permission_for_user_list', $eventParams);

        foreach ( $onlineInfo as $userId => $isOnline )
        {
            // Check privacy permissions
            if ( isset($permissions[$userId]['blocked']) && $permissions[$userId]['blocked'] == true )
            {
                $showPresenceList[$userId] = false;
                continue;
            }

            $showPresenceList[$userId] = true;
        }

        $contextMenuList = array();
        foreach ( $idList as $uid )
        {
            $contextMenu = $this->getContextMenu($uid);
            if ( $contextMenu )
            {
                $contextMenuList[$uid] = $contextMenu->render();
            }
            else
            {
                $contextMenuList[$uid] = null;
            }
        }

        $fields = array();

        $this->assign('contextMenuList', $contextMenuList);

        $this->assign('fields', $this->getFields($idList));
        $this->assign('questionList', $questionList);
        $this->assign('usernameList', $usernameList);
        $this->assign('avatars', $avatars);
        $this->assign('displayNameList', $displayNameList);
        $this->assign('onlineInfo', $onlineInfo);
        $this->assign('showPresenceList', $showPresenceList);
        $this->assign('list', $userList);
    }

    public function onBeforeRender()
    {
        parent::onBeforeRender();

        $this->process($this->list, $this->showOnline);
    }
}