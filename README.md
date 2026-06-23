# Invoice Management — Full-stack Test Assignment

Мінімальний, але реалістичний модуль для роботи з інвойсами.  
Стек: **Nuxt 4 + Vue 3.5 + TailwindCSS 4** / **Laravel 13 + PostgreSQL** / **Docker**.

---

## Запуск проєкту

**Вимоги:** Docker, Docker Compose.

> Порти **3000** і **8000** мають бути вільні. Якщо вони зайняті іншим екземпляром — зупиніть його: `docker compose down`

### Крок 1 — підняти контейнери

```bash
git clone https://github.com/Maxim-Glushko/money-money-money
cd money-money-money

docker compose up --build
# або, якщо немає прав на docker socket:
sudo docker compose up --build
```

`composer install` і `npm install` виконуються **автоматично** — вручну нічого встановлювати не потрібно.

Перший запуск займає 2–4 хвилини (збирається образ PHP, завантажуються npm-пакети).

### Крок 2 — міграції (тільки при першому запуску)

Коли в логах з'явиться `ready for start up` від nginx — виконайте:

```bash
docker compose exec backend php artisan migrate --seed
# або:
sudo docker compose exec backend php artisan migrate --seed
```

Seed створює 50 тестових інвойсів (всі три статуси, 4 сторінки пагінації).

### Крок 3 — відкрити в браузері

```
http://localhost:3000
```

Автоматично перенаправить на список інвойсів.

| Сервіс      | URL                       |
|-------------|---------------------------|
| Frontend    | http://localhost:3000     |
| Backend API | http://localhost:8000/api |

---

## Як я структурував frontend і backend?

### Backend (Laravel 13)

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/InvoiceController.php   # тонкий контролер
│   │   └── Requests/
│   │       ├── StoreInvoiceRequest.php         # валідація + перевірка сум
│   │       └── UpdateInvoiceRequest.php        # те саме + перевірка статусу
│   ├── Models/Invoice.php                      # UUID, fillable, decimal/date casts
│   └── Services/InvoiceService.php             # бізнес-логіка (list, create, update)
├── database/
│   ├── migrations/...create_invoices_table.php
│   └── seeders/InvoiceSeeder.php
└── routes/api.php                              # apiResource → 4 endpoints
```

Принцип: **контролер делегує, сервіс містить логіку**.  
Валідація повністю в FormRequest — контролер не знає про правила.

### Frontend (Nuxt 4)

```
frontend/app/
├── types/invoice.ts              # TypeScript-інтерфейси
├── composables/useInvoiceApi.ts  # API-шар ($fetch)
├── components/
│   └── InvoiceStatusBadge.vue   # ізольований UI-компонент
└── pages/invoices/
    ├── index.vue                 # список
    └── [id].vue                 # деталі + форма
```

Принцип: **pages = оркестратор, логіка — у composables і компонентах**.  
API-шар (`useInvoiceApi`) відокремлений від UI — якщо зміниться базовий URL або спосіб авторизації, міняємо лише composable.

---

## Які компроміси я зробив і чому?

### Клієнтський рендеринг замість SSR

Всі запити до API йдуть через `onMounted` / клієнтський `$fetch`, а не через SSR `useFetch`.

**Причина:** в Docker frontend-контейнер не може достукатись до `http://localhost:8000` на сервері (SSR) — це зовнішня адреса хоста, недоступна з середини контейнера. Для продакшену це вирішується через окремий internal URL (env `NUXT_PRIVATE_API_BASE`).

### `gross_amount` перераховується тільки на фронті

Backend приймає `gross_amount` від клієнта і перевіряє його формулою.  
Альтернатива — ігнорувати поле і завжди розраховувати на бекенді. Вибрав перший варіант, щоб зберегти ТЗ і показати клієнт-сайд логіку.

### Pagination на бекенді, проста на фронті

Laravel повертає стандартну `LengthAwarePaginator`. На фронті — кнопки «назад/вперед» без номерів сторінок. Достатньо для тесту, в реальному продукті зробив би numbered pagination.

### Немає оптимістичних оновлень

Після збереження форми відповідь сервера одразу замінює стан — немає `optimistic update`. Це просто і надійно; для великих форм варто було б додати.

---

## Що б я покращив у production-версії?

**Backend:**
- Окремий API Resource (`InvoiceResource`) для контролю формату відповіді
- Rate limiting на API endpoints
- Soft deletes для аудиту
- Events/Listeners при зміні статусу (наприклад, надсилання email)
- Індекси на `status`, `due_date`, `created_at` для швидких фільтрів

**Frontend:**
- SSR-режим з internal API URL (server-side `$fetch` через docker network)
- `useInfiniteScroll` або нормальна numbered pagination
- Глобальний error boundary + toast-сповіщення
- Skeleton з реальними розмірами під контент (а не прямокутники)
- Збереження стану сторінки у query string (`?page=2`)

**Infrastructure:**
- Health check для backend у `docker compose` перед запуском міграцій
- Multi-stage Docker build для production (не `node:20-slim` з `npm run dev`)
- Nginx як reverse proxy перед Nuxt (зараз Nuxt dev-сервер відкритий напряму)
- CI: автоматичний запуск `php artisan test` та `nuxt build`

---

## Які UX edge cases я врахував?

| Edge case | Рішення |
|---|---|
| API недоступний | Error state з кнопкою «Спробувати знову» на обох сторінках |
| Порожній список | Рядок «Інвойси відсутні» замість пустої таблиці |
| Завантаження даних | Skeleton animation замість пустого екрану |
| Інвойс не pending | Форма відображається, але всі поля `disabled`; пояснювальний текст |
| Помилки валідації з сервера (422) | Парсяться і відображаються під формою |
| Некоректне `gross_amount` | Блокується і на фронті (computed readonly), і на бекенді (FormRequest) |
| `due_date` раніше `issue_date` | `min` атрибут на `<input type="date">` + валідація на бекенді |
| Збереження форми | Кнопка `disabled` під час запиту (`isSubmitting`), індикатор «Збереження...» |
| Успішне збереження | Зелене повідомлення; дані інвойсу оновлюються з відповіді сервера |
| Числові поля | `z.coerce.number()` — коректна коерція рядків з `<input>` у число |
