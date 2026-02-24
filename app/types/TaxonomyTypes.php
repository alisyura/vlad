<?php

class TaxonomyTypes
{
    public const TAXONOMY_TAGS = 'tags';
    public const TAXONOMY_CATEGORIES = 'categories';

    private const TAXONOMIES = [
        self::TAXONOMY_TAGS,
        self::TAXONOMY_CATEGORIES
    ];

    // Для валидации
    public static function isValid(string $taxonomy): bool
    {
        return in_array($taxonomy, self::TAXONOMIES, true);
    }

    // Для использования в регулярных выражениях
    public static function getRegexPattern(): string
    {
        return implode('|', array_map('preg_quote', self::TAXONOMIES));
    }
}