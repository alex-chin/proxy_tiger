# Tiger Proxy

Простой сервис-прокси для работы с Tiger SMS API: получение виртуального номера, чтение СМС, отмена номера и проверка статуса. Реализован на Laravel в виде тонкого HTTP-прокси со валидацией входных данных, обработкой ошибок и логированием запросов/ответов.

## Возможности
- Получение номера: `GET /api/getNumber?country=se&service=ds`
- Получение СМС: `GET /api/getSms?activation=ACTIVATION_ID`
- Отмена номера: `GET /api/cancelNumber?activation=ACTIVATION_ID`
- Проверка статуса: `GET /api/getStatus?activation=ACTIVATION_ID`

## Быстрый старт
1. Скопируйте `.env.example` в `.env` и укажите переменные:
   - `TIGER_SMS_API_URL`
   - `TIGER_SMS_TOKEN`
   - (опционально) `TIGER_SMS_DEFAULT_COUNTRY`, `TIGER_SMS_DEFAULT_SERVICE`
2. Установите зависимости и запустите сервер Laravel.
3. Используйте коллекции в `tests/tiger-proxy.http` для ручной проверки запросов.

## Конфигурация
Файл `config/tiger_test.php`:
- `api_url`, `token` — адрес и токен внешнего Tiger API (из `.env`).
- `default_country`, `default_service` — значения по умолчанию.
- `allowed_countries`, `allowed_services` — whitelists для валидации входа.

## Архитектура и решения
- Слой контроллера: `App\Http\Controllers\SmsController`
  - Валидирует входные параметры через правила и словари из `config/tiger_test.php`.
  - Унифицированный ответ JSON, обработка `ValidationException` и доменного `SmsServiceException`.
- Сервисный слой: `App\Services\TigerSmsService`
  - Инкапсулирует запросы к внешнему Tiger SMS API (через `Http::get`).
  - Таймаут 30с, заголовки JSON, проброс параметров действия (`action`, `country`, `service`, `activation`).
  - Логирование успешных и ошибочных вызовов через `Log` с метаданными и временем.
- Исключения: `App\Exceptions\SmsServiceException`
  - Единая обертка ошибок интеграции, чтобы не протекали детали HTTP-клиента в контроллер.
- Маршрутизация: `routes/api.php`
  - Эндпоинты сгруппированы под middleware `cors`.
- CORS: `App\Http\Middleware\CorsMiddleware`
  - Устанавливает заголовки `Access-Control-Allow-*` для простых кросс-доменных запросов.

### Принятые решения
- Тонкий контроллер, толстый сервис»: вся интеграционная логика в сервисе, контроллер только валидирует и маршрутизирует.
- Whitelist-валидация кодов страны/сервиса: защита от некорректных значений и ранняя ошибка 422.
- Централизованное логирование запрос/ответ/статус: упрощает диагностику при сбоях у внешнего провайдера.
- Кастомное исключение: единообразная обработка ошибок интеграции в одном месте.

## Примеры запросов
См. `tests/tiger-proxy.http` и `tests/tiger.http`.

## Безопасность и ограничения
- Не хранит чувствительные данные; токен передается из окружения.
- Разрешает CORS везде (`*`): проверьте требования безопасности перед продом.

## Развитие
- Добавить retry при временных ошибках внешнего API.
- Вынести словари валидации в конфиг/фичетогглы с автоподгрузкой.
- Поддержка POST для операций, где требуется конфиденциальность параметров.

## Паттерн «Стратегия» для действий с SMS
Контроллер делегирует выполнение доменных действий стратегиям, реализующим общий интерфейс.

- **Интерфейс**: `App\Services\SmsActions\SmsActionInterface::execute(array $data)`
- **Реализации**:
  - `GetNumberAction` — получение номера
  - `GetSmsAction` — получение СМС по `activation`
  - `CancelNumberAction` — отмена номера
  - `GetStatusAction` — проверка статуса

Контроллер использует стратегии так:
- `SmsController::respondWithJson(Request $request, array $rules, SmsActionInterface $action)` — валидирует вход и вызывает `$action->execute($data)`.

### Как добавить новое действие
1. Создайте класс в `app/Services/SmsActions/`, реализующий `SmsActionInterface`.
2. Внедрите `TigerSmsService` через конструктор и реализуйте `execute(array $data)`.
3. В `SmsController` создайте новый публичный метод-эндпоинт, передающий соответствующую стратегию в `respondWithJson` и добавьте правила валидации.

Пример (сокращенно):
```php
namespace App\Services\SmsActions;
use App\Services\TigerSmsService;

class GetBalanceAction implements SmsActionInterface
{
    public function __construct(private TigerSmsService $smsService) {}
    public function execute(array $data)
    {
        return $this->smsService->getBalance();
    }
}
```
