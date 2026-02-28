<?php

// app/services/RobotsList.php

class RobotsList
{
    private const ROBOTS_LIST = [
            'noindex, follow', 
            'index, follow', 
            'noindex, nofollow', 
            'index, nofollow'
    ];

    public function getRobotsList(bool $noIndexFirst = true): array
    {
        $robotslist = self::ROBOTS_LIST;
        if (!$noIndexFirst)
        {
            [$robotslist[0], $robotslist[1]] = [$robotslist[1], $robotslist[0]];
        }

        return $robotslist;
    }

    public function isValid(string $robotsValue): bool
    {
        return in_array($robotsValue, self::ROBOTS_LIST, true);
    }
}