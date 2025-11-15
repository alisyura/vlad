<?php

// app/framework/response/XmlResponse.php

class XmlResponse extends Response
{
    /**
     * @var string Корневой элемент для XML-документа.
     */
    protected const XML_ROOT_NODE = 'response';

    /**
     * Конструктор XmlResponse.
     * @param array|string $data Данные для кодирования в XML.
     * @param int $statusCode HTTP-код статуса.
     * @param array $headers Дополнительные заголовки.
     */
    public function __construct(array|string $data, int $statusCode = 200, array $headers = [])
    {
        // 1. Конвертируем массив данных в XML-строку
        $xmlContent = '';//$this->arrayToXml($data); 

        if (is_array($data)) {
            $xmlContent = $this->arrayToXml($data); 
        } elseif (is_string($data)) {
            $xmlContent = $data;
        } else {
             // Обработка ошибки
        }
        
        // 2. Вызываем родительский конструктор с XML-строкой
        parent::__construct($xmlContent, $statusCode, $headers);
    }

    /**
     * Переопределяет заголовки по умолчанию, устанавливая Content-Type для XML.
     */
    protected function getDefaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ];
    }
    
    /**
     * Рекурсивно конвертирует PHP-массив в объект SimpleXMLElement.
     * @param array $data
     * @param SimpleXMLElement|null $xml_data
     * @return string
     */
    protected function arrayToXml(array $data, ?SimpleXMLElement $xml_data = null): string
    {
        // Инициализация SimpleXMLElement, если не передан
        if ($xml_data === null) {
            $xml_data = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><' . self::XML_ROOT_NODE . '/>');
        }

        foreach ($data as $key => $value) {
            // Если ключ числовой (как в случае с обычными массивами, а не ассоциативными)
            if (is_numeric($key)) {
                $key = 'item'; // Используем общий элемент для списка
            }

            if (is_array($value)) {
                // Рекурсивный вызов для вложенных массивов
                $subnode = $xml_data->addChild($key);
                $this->arrayToXml($value, $subnode);
            } else {
                // Добавление элемента с его значением. 
                // Оборачиваем в CDATA, если есть спецсимволы, но здесь делаем простое добавление.
                $xml_data->addChild($key, htmlspecialchars((string)$value));
            }
        }
        
        return $xml_data->asXML();
    }
}