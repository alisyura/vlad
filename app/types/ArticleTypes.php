<?php

class ArticleTypes
{
    /**
     * Тип контента пост
     */
    public const ARTICLE_POST = 'post';
    /**
     * Тип контента страница
     */
    public const ARTICLE_PAGE = 'page';

    private const ARTICLES = [
        self::ARTICLE_POST,
        self::ARTICLE_PAGE
    ];

    // Для валидации
    public static function isValid(string $taxonomy): bool
    {
        return in_array($taxonomy, self::ARTICLES, true);
    }

    // Для использования в регулярных выражениях
    public static function getRegexPattern(): string
    {
        return implode('|', array_map('preg_quote', self::ARTICLES));
    }
}