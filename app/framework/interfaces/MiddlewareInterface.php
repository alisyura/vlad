<?php

// app/interfaces/MiddlewareInterface.php
interface MiddlewareInterface {
    function handle(?array $params = null): bool;

    /**
     * Обрабатывает входящий запрос и/или исходящий ответ. (на будущее)
     *
     * @param Request $request Входящий запрос.
     * @param Closure $next Функция, которая вызывает следующий Middleware в цепочке.
     * @return Response|null Возвращает объект Response или null для продолжения.
     */
    // public function handle(Request $request, Closure $next): Response;
}