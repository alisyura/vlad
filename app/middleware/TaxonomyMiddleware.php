<?php
// app/middleware/TaxonomyMiddleware.php

class TaxonomyMiddleware implements MiddlewareInterface
{
    /**
     * @var Request Объект, содержащий данные текущего HTTP-запроса.
     */
    private Request $request;

    /**
     * Конструктор TaxonomyMiddleware.
     *
     * @param Request $request Объект запроса, внедряемый через конструктор.
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Обрабатывает входящий HTTP-запрос.
     *
     * Проверяет корректность taxonomy из URL.
     *
     * @param array|null $param Параметры из роута (должны содержать 'taxonomy')
     * @return bool Возвращает `true`, если taxonomy валидна.
     * В противном случае выбрасывает исключение.
     */
    public function handle(?array $param = null): bool
    {
        // Получаем URI и извлекаем taxonomy
        $uri = $this->request->getUri();
        $parts = explode('/', trim($uri, '/'));
        
        // taxonomy находится после /eryfbh/ и перед /api/create
        $taxonomy = $parts[2] ?? null;
        
        if (!$taxonomy || !TaxonomyTypes::isValid($taxonomy)) {
            throw new HttpException(
                'Неизвестная таксономия',
                404,
                null,
                HttpException::JSON_RESPONSE
            );
        }
        
        // Просто возвращаем true, если все ок
        // Значение taxonomy нам здесь не нужно сохранять
        return true;
    }
}