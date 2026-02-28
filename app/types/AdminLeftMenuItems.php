<?php

class AdminLeftMenuItems
{
    public const MENU_DASHBOARD = 'dashboard';
    public const MENU_POSTS = 'posts';
    public const MENU_PAGES = 'pages';
    public const MENU_TAGS = 'tags';
    public const MENU_USERS = 'users';
    public const MENU_MEDIATEKA = 'mediateka';
    public const MENU_SETTINGS = 'settings';
    public const MENU_CATEGORIES = 'categories';

    private const MENU_ITEMS = [
        self::MENU_DASHBOARD,
        self::MENU_POSTS,
        self::MENU_PAGES,
        self::MENU_TAGS,
        self::MENU_USERS,
        self::MENU_MEDIATEKA,
        self::MENU_SETTINGS,
        self::MENU_CATEGORIES,
    ];

    // Для валидации
    public static function isValid(string $taxonomy): bool
    {
        return in_array($taxonomy, self::MENU_ITEMS, true);
    }

    // Для использования в регулярных выражениях
    public static function getRegexPattern(): string
    {
        return implode('|', array_map('preg_quote', self::MENU_ITEMS));
    }
}