<?php

// app/framework/Pipeline.php (Конвейер)

class Pipeline
{
    protected array $middleware = [];
    protected Closure $destination; // Финальное действие (запуск контроллера)

    public function setMiddleware(array $middleware): self
    {
        // Здесь должны быть имена классов, реализующих MiddlewareInterface
        $this->middleware = $middleware;
        return $this;
    }

    public function then(Closure $destination): self
    {
        // Устанавливает финальную точку - вызов контроллера/роута
        $this->destination = $destination;
        return $this;
    }

    /**
     * Пропускает запрос через конвейер.
     * @param Request $request
     * @return Response
     */
    public function send(Request $request): Response
    {
        // 1. Создаем "пузырь" (начальная точка), который будет вызывать следующий элемент
        $pipeline = array_reduce(
            array_reverse($this->middleware), // Перебираем Middleware в обратном порядке
            $this->createSlice(),             // Используем метод для создания Closure для каждого Middleware
            $this->prepareDestination()       // Начинаем с финального действия (контроллера)
        );

        // 2. Запускаем конвейер
        return $pipeline($request);
    }
    
    // Вспомогательный метод: готовит Closure для финального действия
    protected function prepareDestination(): Closure
    {
        return function (Request $request) {
            return ($this->destination)($request);
        };
    }
    
    // Вспомогательный метод: создает Closure для каждого Middleware
    protected function createSlice(): Closure
    {
        return function (Closure $stack, string $pipe) {
            return function (Request $request) use ($stack, $pipe) {
                // Создаем экземпляр Middleware и вызываем его метод handle()
                $middlewareInstance = new $pipe(); // В реальном DI-приложении здесь будет $container->get($pipe)
                
                // Передаем запрос и $stack (Closure для следующего элемента)
                return $middlewareInstance->handle($request, $stack);
            };
        };
    }
}