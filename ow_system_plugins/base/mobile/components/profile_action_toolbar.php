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
 * @package ow_system_plugins.base.mobile.components
 * @since 1.6.0
 */
class BASE_MCMP_ProfileActionToolbar extends BASE_MCMP_ButtonList
{
    const EVENT_NAME = "base.mobile.add_profile_action_toolbar";
    const EVENT_GROUP_MENU_CLASS = "base.mobile.profile_action_toolbar_group_menu_class";

    protected $userId;

    /**
     * Constructor.
     *
     * @param integer $userId
     */
    public function __construct( $userId )
    {
        parent::__construct(array());
        
        $this->userId = (int) $userId;
        
        $event = new BASE_CLASS_EventCollector(self::EVENT_NAME, array("userId" => $this->userId));

        OW::getEventManager()->trigger($event);

        $addedData = $event->getData();
        
        if ( empty($addedData) )
        {
            $this->setVisible(false);

            return;
        }
        
        $this->items = $addedData;
    }

    public function onBeforeRender()
    {
        parent::onBeforeRender();

        $this->initList();

        $template = OW::getPluginManager()->getPlugin("base")->getMobileCmpViewDir() . "profile_action_toolbar.html";
        $this->setTemplate($template);
    }

    protected function initList()
    {
        $itemGroups = array();
        $buttons = array();

        foreach ( $this->items as $item  )
        {
            if ( isset($item["group"]) )
            {
                if ( empty($itemGroups[$item["group"]]) )
                {
                    $itemGroups[$item["group"]] = array(
                        "key" => $item["group"],
                        "label" => isset($item["groupLabel"]) ? $item["groupLabel"] : null,
                        "context" => array()
                    );
                }

                $itemGroups[$item["group"]]["items"][] = $item;
            }
            else
            {
                $buttons[] = $this->prepareItem($item, "owm_btn_list_item");
            }
        }

        $tplGroups = array();
        foreach ( $itemGroups as $group )
        {
            $event = new OW_Event(self::EVENT_GROUP_MENU_CLASS, array("key" => $group["key"], "userId" => $this->userId), array(
                "owm_btn_list_item_wrap",
                "owm_view_more",
                "owm_float_right"
            ));

            OW::getEventManager()->trigger($event);
            $contextAction = new BASE_MCMP_ContextAction($group["items"], $group["label"]);
            $tplGroups[] = [
                "classes" => implode(" ", $event->getData()),
                "view" => $contextAction->render()
            ];
        }

        $this->assign("groups", $tplGroups);
        $this->assign("buttons", $this->getSortedItems($buttons));
    }
}