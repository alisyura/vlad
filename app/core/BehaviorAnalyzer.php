<?php

class BehaviorAnalyzer {
    public static function analyzeUserBehavior($behaviorData) {
        $riskScore = 0;
        
        // Проверка скорости мыши
        if (isset($behaviorData['mouse_speed'])) {
            if ($behaviorData['mouse_speed'] > 5000) { // Слишком быстро для человека
                $riskScore += 30;
            }
        }

        // Проверка паттернов кликов
        if (isset($behaviorData['click_pattern'])) {
            if (self::isRoboticClickPattern($behaviorData['click_pattern'])) {
                $riskScore += 25;
            }
        }

        // Проверка времени на странице
        if (isset($behaviorData['time_on_page'])) {
            if ($behaviorData['time_on_page'] < 2000) { // Меньше 2 секунд
                $riskScore += 20;
            }
        }

        return $riskScore;
    }

    private static function isRoboticClickPattern($clicks) {
        // Боты часто кликают точно в центр элементов
        $precisionThreshold = 2; // Пиксели
        foreach ($clicks as $click) {
            if ($click['precision'] < $precisionThreshold) {
                return true;
            }
        }
        return false;
    }
}