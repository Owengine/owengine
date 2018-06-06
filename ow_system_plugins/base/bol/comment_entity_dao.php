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
 * Data Access Object for `base_comment_entity` table.  
 * 
 * @author Sardar Madumarov <madumarov@gmail.com>
 * @package ow_system_plugins.base.bol
 * @since 1.0
 */
class BOL_CommentEntityDao extends OW_BaseDao
{
    const ENTITY_TYPE = 'entityType';
    const ENTITY_ID = 'entityId';
    const PLUGIN_KEY = 'pluginKey';
    const ACTIVE = 'active';

    /**
     * Singleton instance.
     *
     * @var BOL_CommentEntityDao
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return BOL_CommentEntityDao
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    /**
     * Constructor.
     */
    protected function __construct()
    {
        parent::__construct();
    }

    /**
     * @see OW_BaseDao::getDtoClassName()
     *
     */
    public function getDtoClassName()
    {
        return 'BOL_CommentEntity';
    }

    /**
     * @see OW_BaseDao::getTableName()
     *
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'base_comment_entity';
    }

    /**
     * 
     * @param string $entityType
     * @param integer $entityId
     * @return BOL_CommentEntity
     */
    public function findByEntityTypeAndEntityId( $entityType, $entityId )
    {
        $example = new OW_Example();
        $example->andFieldEqual(self::ENTITY_TYPE, $entityType);
        $example->andFieldEqual(self::ENTITY_ID, $entityId);

        return $this->findObjectByExample($example);
    }

    public function findCommentedEntityCount( $entityType )
    {
        $queryParts = BOL_ContentService::getInstance()->getQueryFilter(array(
            BASE_CLASS_QueryBuilderEvent::TABLE_CONTENT => 'base_comment_entity'
        ), array(
            BASE_CLASS_QueryBuilderEvent::FIELD_CONTENT_ID => 'id'
        ), array(
            BASE_CLASS_QueryBuilderEvent::OPTION_METHOD => __METHOD__,
            BASE_CLASS_QueryBuilderEvent::OPTION_TYPE => $entityType
        ));

        $query = "SELECT COUNT( DISTINCT `base_comment_entity`.`id`) FROM `" . $this->getTableName() . "` AS `base_comment_entity`
            " . $queryParts['join'] . "
            WHERE `base_comment_entity`.`" . self::ENTITY_TYPE . "` = :entityType AND " . $queryParts['where'] . " " ;

        return (int) $this->dbo->queryForColumn($query, array('entityType' => trim($entityType)));
    }

    public function deleteByEntityType( $entityType )
    {
        $example = new OW_Example();
        $example->andFieldEqual(self::ENTITY_TYPE, trim($entityType));

        $this->deleteByExample($example);
    }

    public function deleteByPluginKey( $pluginKey )
    {
        $example = new OW_Example();
        $example->andFieldEqual(self::PLUGIN_KEY, trim($pluginKey));

        $this->deleteByExample($example);
    }

    
}
