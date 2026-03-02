<?php

abstract class TaxonomyBaseType
{
    /**
     * Название таблицы таксономии
     */
    abstract public static function getId(): string;

    /**
     * Название таблицы в БД, где находится таксономия
     */
    abstract public static function getTableName(): string;

    /**
     * Название таблицы в БД, связи таксономии и постов
     */
    abstract public static function getLinkTableName(): string;

    /**
     * Название поля таксономии в таблице связи с постом в БД
     */
    abstract public static function getIdFieldName(): string;
    
    // Общие методы для всех таксономий
    public static function isValid(string $taxonomy): bool
    {
        return $taxonomy === static::getId();
    }
    
    public static function getRegexPattern(): string
    {
        return preg_quote(static::getId());
    }
    
    // Если нужны дополнительные общие методы
    public static function getLabel(): string
    {
        return ucfirst(static::getId());
    }
}