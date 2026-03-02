<?php

class TagsTaxonomy extends TaxonomyBaseType
{
    /**
     * Название таблицы таксономии
     */
    public static function getId(): string
    {
        return 'tags';
    }
    
    /**
     * Название таблицы в БД, где находится таксономия
     */
    public static function getTableName(): string
    {
        return 'tags';
    }

    /**
     * Название таблицы в БД, связи таксономии и постов
     */
    public static function getLinkTableName(): string
    {
        return 'post_tag';
    }

    /**
     * Название поля таксономии в таблице связи с постом в БД
     */
    public static function getIdFieldName(): string
    {
        return 'tag_id';
    }
}

