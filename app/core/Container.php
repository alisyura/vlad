<?php

class Container
{
    protected array $bindings = [];
    protected array $instances = [];

    /**
     * Зарегистрировать "связь" между интерфейсом и его реализацией.
     * Или зарегистрировать класс с замыканием для его создания.
     *
     * @param string $abstract
     * @param mixed $concrete
     * @return void
     */
    public function bind(string $abstract, $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
    }

    /**
     * Зарегистрировать класс как синглтон (единственный экземпляр на запрос).
     *
     * @param string $abstract
     * @param mixed $concrete
     * @return void
     */
    public function singleton(string $abstract, $concrete = null): void
    {
        $this->bind($abstract, $concrete);
        // Используем null, чтобы отличить от обычного биндинга
        $this->instances[$abstract] = null;
    }

    /**
     * Разрешить (создать) экземпляр класса.
     *
     * @param string $abstract
     * @return mixed
     * @throws ReflectionException
     */
    public function make(string $abstract): mixed
    {
        // 1. Проверяем, существует ли уже синглтон.
        // Это первая и самая важная проверка.
        if (array_key_exists($abstract, $this->instances) && $this->instances[$abstract] !== null) {
            return $this->instances[$abstract];
        }

        // 2. Ищем зарегистрированный "бинд".
        if (isset($this->bindings[$abstract])) {
            $concrete = $this->bindings[$abstract];
            if ($concrete instanceof Closure) {
                // Если это замыкание, вызываем его для создания объекта.
                $instance = $concrete($this);
            } else {
                // Если это имя класса, используем его для рефлексии.
                $abstract = $concrete;
            }
        }
        
        // 3. Используем Reflection для создания нового экземпляра.
        if (!isset($instance)) {
            $reflector = new ReflectionClass($abstract);
            
            // Если у класса нет конструктора или он не имеет зависимостей, просто создаем его.
            if (!$reflector->isInstantiable() || ($constructor = $reflector->getConstructor()) === null) {
                $instance = new $abstract;
            } else {
                // Получаем зависимости из конструктора.
                $dependencies = $this->getDependencies($constructor->getParameters());
                // Создаём экземпляр класса, передавая зависимости.
                $instance = $reflector->newInstanceArgs($dependencies);
            }
        }

        // 4. Сохраняем экземпляр, если он был зарегистрирован как синглтон.
        if (array_key_exists($abstract, $this->instances)) {
            $this->instances[$abstract] = $instance;
        }

        return $instance;
    }

    /**
     * Рекурсивно получаем все зависимости класса.
     *
     * @param array $parameters
     * @return array
     * @throws ReflectionException
     */
    protected function getDependencies(array $parameters): array
    {
        $dependencies = [];
        foreach ($parameters as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $dependencies[] = $this->make($type->getName());
            } else {
                // Если зависимость не является классом, используем значение по умолчанию, если оно есть.
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    // Если нет значения по умолчанию, выбрасываем исключение.
                    throw new Exception("Невозможно разрешить зависимость '{$parameter->getName()}'");
                }
            }
        }
        return $dependencies;
    }
}