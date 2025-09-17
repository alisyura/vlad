<?php

class Container
{
    protected array $bindings = [];

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
     * Разрешить (создать) экземпляр класса.
     *
     * @param string $abstract
     * @return mixed
     * @throws ReflectionException
     */
    public function make(string $abstract): mixed
    {
        // Проверяем, есть ли зарегистрированный "бинд"
        if (isset($this->bindings[$abstract])) {
            $concrete = $this->bindings[$abstract];
            if ($concrete instanceof Closure) {
                return $concrete($this);
            }
            $abstract = $concrete;
        }

        // Используем Reflection для анализа класса и его зависимостей
        $reflector = new ReflectionClass($abstract);

        // Если класс не имеет конструктора или он не имеет зависимостей, просто создаём его
        if (!$reflector->isInstantiable() || ($constructor = $reflector->getConstructor()) === null) {
            return new $abstract;
        }

        // Получаем зависимости из конструктора
        $dependencies = $this->getDependencies($constructor->getParameters());

        // Создаём новый экземпляр класса, передавая зависимости
        return $reflector->newInstanceArgs($dependencies);
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
                // Если зависимость не является классом (например, строка или число),
                // и у нее есть значение по умолчанию, используем его.
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    // Если нет значения по умолчанию, выбрасываем исключение
                    throw new Exception("Невозможно разрешить зависимость '{$parameter->getName()}'");
                }
            }
        }
        return $dependencies;
    }
}
