<?php

class TaxonomyRegistry
{
    private const TAXONOMIES = [
        TagsTaxonomy::class,
        CategoriesTaxonomy::class,
    ];
    
    public static function getAllIds(): array
    {
        return array_map(fn($class) => $class::getId(), self::TAXONOMIES);
    }
    
    public static function isValid(string $taxonomy): bool
    {
        return in_array($taxonomy, self::getAllIds(), true);
    }
    
    public static function getRegexPattern(): string
    {
        return implode('|', array_map('preg_quote', self::getAllIds()));
    }
    
    /**
     * Название таблицы в БД, где находится таксономия
     */
    public static function getTableName(string $taxonomy): ?string
    {
        foreach (self::TAXONOMIES as $class) {
            if ($class::getId() === $taxonomy) {
                return $class::getTableName();
            }
        }
        return null;
    }

    /**
     * Название таблицы в БД, связи таксономии и постов
     */
    public static function getLinkTableName(string $taxonomy): ?string
    {
        foreach (self::TAXONOMIES as $class) {
            if ($class::getId() === $taxonomy) {
                return $class::getLinkTableName();
            }
        }
        return null;
    }

    /**
     * Название поля таксономии в таблице связи с постом в БД
     */
    public static function getIdFieldName(string $taxonomy): ?string
    {
        foreach (self::TAXONOMIES as $class) {
            if ($class::getId() === $taxonomy) {
                return $class::getIdFieldName();
            }
        }
        return null;
    }
    
    public static function getClass(string $taxonomy): ?string
    {
        foreach (self::TAXONOMIES as $class) {
            if ($class::getId() === $taxonomy) {
                return $class;
            }
        }
        return null;
    }
}