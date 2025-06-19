-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Июн 19 2025 г., 20:20
-- Версия сервера: 8.0.30
-- Версия PHP: 8.1.9

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `vlad`
--

-- --------------------------------------------------------

--
-- Структура таблицы `categories`
--

CREATE TABLE `categories` (
  `id` int NOT NULL,
  `url` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `categories`
--

INSERT INTO `categories` (`id`, `url`, `name`, `created_at`, `updated_at`) VALUES
(3, 'anekdoty', 'Анекдоты', '2025-04-22 16:55:47', '2025-04-24 12:10:59'),
(4, 'veselaya-rifma', 'Веселая рифма', '2025-04-22 16:57:05', '2025-04-24 12:11:27'),
(5, 'citatnik', 'Цитатник', '2025-05-16 18:57:47', '2025-05-16 18:57:47'),
(6, 'istorii', 'Истории', '2025-05-16 18:58:24', '2025-05-16 19:01:05'),
(7, 'kartinki', 'Картинки', '2025-05-16 19:01:38', '2025-05-16 19:01:38'),
(8, 'video', 'Видео', '2025-05-16 19:01:38', '2025-05-16 19:01:38'),
(9, 'tegi', 'Тэги', '2025-05-16 19:02:23', '2025-05-16 19:02:23'),
(10, 'luchshee', 'Лучшее', '2025-05-16 19:02:23', '2025-05-16 19:02:23');

-- --------------------------------------------------------

--
-- Структура таблицы `comments`
--

CREATE TABLE `comments` (
  `id` int NOT NULL,
  `post_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `visitor_id` int DEFAULT NULL,
  `content` text NOT NULL,
  `status` enum('deleted','pending','published') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `media`
--

CREATE TABLE `media` (
  `id` int NOT NULL COMMENT 'Уникальный идентификатор файла',
  `post_id` int DEFAULT NULL COMMENT 'ID поста, к которому прикреплен файл (если есть)',
  `user_id` int DEFAULT NULL COMMENT 'ID пользователя, который загрузил файл',
  `file_name` varchar(255) NOT NULL COMMENT 'Имя файла (например, image.jpg)',
  `file_path` varchar(255) NOT NULL COMMENT 'Путь к файлу на сервере (например, /uploads/2025/04/image.jpg)',
  `file_type` enum('image','video','audio','document','other') NOT NULL COMMENT 'Тип файла',
  `mime_type` varchar(100) NOT NULL COMMENT 'MIME-тип файла (например, image/jpeg)',
  `file_size` int NOT NULL COMMENT 'Размер файла в байтах',
  `thumbnail_path` varchar(255) DEFAULT NULL COMMENT 'Путь к миниатюре (если применимо)',
  `alt_text` varchar(255) DEFAULT NULL COMMENT 'Альтернативный текст для изображений (SEO)',
  `description` text COMMENT 'Описание файла',
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата и время загрузки',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Дата и время обновления'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `media`
--

INSERT INTO `media` (`id`, `post_id`, `user_id`, `file_name`, `file_path`, `file_type`, `mime_type`, `file_size`, `thumbnail_path`, `alt_text`, `description`, `uploaded_at`, `updated_at`) VALUES
(3, 3, 2, '08_oblozhka.jpg', '/assets/uploads/2025/06/08_oblozhka.jpg', 'image', '', 19840, NULL, NULL, NULL, '2025-06-08 13:39:57', '2025-06-08 16:05:01'),
(4, 230, 2, 'aVUOus_1Tok.jpg', '/assets/uploads2025/06/15_avuous1tok_9.jpg', 'image', 'image/jpeg', 384086, NULL, NULL, NULL, '2025-06-15 17:03:11', '2025-06-15 17:03:11'),
(5, 231, 2, '15_avuous1tok_10.jpg', '/assets/uploads2025/06/15_avuous1tok_10.jpg', 'image', 'image/jpeg', 384086, NULL, NULL, NULL, '2025-06-15 17:10:00', '2025-06-15 17:10:00'),
(6, 232, 2, '15_avuous1tok.jpg', '/assets/uploads2025/06/15_avuous1tok.jpg', 'image', 'image/jpeg', 384086, NULL, NULL, NULL, '2025-06-15 17:30:40', '2025-06-15 17:30:40'),
(7, 233, 2, '15_avuous1tok_1.jpg', '/assets/uploads2025/06/15_avuous1tok_1.jpg', 'image', 'image/jpeg', 384086, NULL, NULL, NULL, '2025-06-15 17:31:43', '2025-06-15 17:31:43');

-- --------------------------------------------------------

--
-- Структура таблицы `posts`
--

CREATE TABLE `posts` (
  `id` int NOT NULL,
  `url` varchar(128) NOT NULL,
  `user_id` int NOT NULL,
  `title` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `keywords` varchar(255) DEFAULT NULL,
  `description` varchar(160) DEFAULT NULL,
  `robots` enum('index','follow','noindex','nofollow','noindex, follow','index, follow','noindex, nofollow','index, nofollow') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'index',
  `status` enum('draft','pending','published','deleted') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'draft',
  `article_type` enum('post','page') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'post',
  `likes_count` int UNSIGNED NOT NULL DEFAULT '0',
  `dislikes_count` int UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `posts`
--

INSERT INTO `posts` (`id`, `url`, `user_id`, `title`, `content`, `created_at`, `updated_at`, `keywords`, `description`, `robots`, `status`, `article_type`, `likes_count`, `dislikes_count`) VALUES
(3, 'taksuyu', 2, 'Про таксиста', 'Taксую. Ранее утро. Пассажир — женщина. Спокойно катим в пробке. Мимо нас в междурядье с грохотом проносится мотоциклист.\n— Ух, отчаянный, — провожаю его добрым словом.\n— Вы не гоняете, anekdotov.net, случаем, на байке? — спрашиваю женщину.\n— Я — нет, — улыбнулась она.\n— А вот племянник недавно признался, что мечтает о мотоцикле. И никакие доводы на него не действуют. Ни мои, ни матери. Хочу и всё! И понимаем, что запретить никак не получится. Купит втайне и гонять будет. Ну, тогда я ему и предложила месяц у меня поработать, и тогда пусть покупает. Если денег не хватит — добавлю. Работа чисто физическая. Принеси/унеси. Как раз для молодого парня. После второй смены он сказал, что передумал покупать мотоцикл.\n— А где вы работаете?\n— В реанимации.', '2025-04-22 16:54:59', '2025-04-24 13:44:14', NULL, NULL, 'index', 'published', 'post', 0, 0),
(4, 'sotrudnitsa-otdela-prodazh', 2, 'Про сотрудницу', 'Сотрудница отдела продаж, специалист по сервису и их начальник идут обедать и находят старую масляную лампу. Они трут лампу, и Джин появляется в облаке дыма. Джин говорит:\n— Обычно я выполняю три желания, поэтому каждый из Вас может загадать по одному.\n— Чур, я первая! , — говорит сотрудница отдела продаж. Я хочу быть сейчас на Багамах, мчаться без забот на скутере по волнам.\nПуфф! И она растворяется в воздухе. anekdotov.net,\n— Я следующий! , — говорит спец по сервису. Я хочу на Гавайи, расслабляться на пляже с личной массажисткой и бесконечным запасом Пина-Колады.\nПуфф! Исчезает.\n— OK, твоя очередь! , — говорит Джин менеджеру.\nТогда менеджер говорит:\n— Я хочу, чтобы эти двое были в офисе после обеда.', '2025-04-01 16:57:23', '2025-06-19 15:48:18', NULL, NULL, 'index', 'published', 'post', 1, 2),
(5, 'pro-fax', 2, 'Про факс', '\"В России одновременно сосуществуют поколения людей, anekdotov.net, которые в 20 лет ещё не знали, что такое \"отправить FAX\" и которые в 20 лет уже не знают, что такое \"отправить FAX\"\"\r\nИ вспомнилось...\r\nВ конце 90-х на \"ЛМЗ\" (Ленинградский Металлический завод, я там работал) и \"Электросилу\" наехали новые \"собственники\" с новыми порядками ведения бизнеса: коммерческая тайна и т. п. А я тогда как раз какой-то проект делал, как обычно: ЛМЗ-шная турбина и Электросиловский генератор. И мне от Электросилы технические данные нужны.\r\nЗвоню в их КБ, задаю вопросы. Мне в ответ: все данные готовы, но передать не можем — новый собственник запретил напрямую письма писать, телексы и факсы отправлять (е-мэйла у нас тогда ещё не было). Только через Службу Безопасности!\r\nА это ещё неделя на согласование!\r\nЯ устно информацию принять, конечно, могу, но мне именно документ нужен.\r\n— Что, совсем всё запретили?\r\n— Всё!\r\n— И телеграммы?\r\n— И телеграммы!\r\n— И факсы?\r\n— И факсы!\r\n— А телефонограммы?\r\n— О!\r\nЧерез 15 минут из факса вылезает лист с крупным заголовком: ТЕЛЕФОНОГРАММА!', '2025-04-24 13:42:02', '2025-06-08 11:56:38', NULL, NULL, 'index', 'published', 'post', 2, 1),
(184, 'o-proekte', 2, 'О проекте', 'Добро пожаловать на страницу, которая сейчас примерно такая же содержательная, как объявление в газете \"Пропала собака...\". Но не переживайте — мы уже работаем над тем, чтобы рассказать вам о себе всё. Или почти всё. Ну, хотя бы то, что можно опубликовать без риска быть избитым родственниками.\r\n\r\nНаш портал — это место, где собираются любители доброго слова, острой шутки, умелой сатиры и тех, кто просто хочет отвлечься от серых будней и чьих-то политических заявлений. Мы любим анекдоты, истории из жизни, весёлые стишочки, мемы, которые ещё не устарели, и загадки, ответы на которые знают только авторы (и то — не всегда).\r\n\r\nО проекте? Что ж… Это история про то, как несколько человек с чувством юмора (или без него — проверить сложно) решили собраться вместе и создать что-нибудь такое, что заставит вас хотя бы на пару минут забыть про кредиты, пробки и вечный вопрос: «Почему носки пропадают после стирки?»\r\n\r\nМы верим, что смех — лучшее лекарство. Особенно если таблетки кончились, а до зарплаты ещё две недели.\r\n\r\nНа этой странице вы найдёте историю создания портала, цели, ради которых он был запущен, планы на будущее, информацию о команде (да, она существует!), а также, возможно, кое-какие прикольные факты, которые обычно прячут под матрасом.\r\n\r\nТерпение, друг мой, скоро здесь будет не только текст, но и дух нашего проекта — весёлый, немного дерзкий, но всегда доброжелательный (ну… почти всегда).\r\n\r\nА пока — не скучайте, читайте анекдоты, делитесь ими с друзьями и сохраняйте хорошее настроение. Ведь именно ради этого мы всё и затеяли!', '2025-06-08 12:22:28', '2025-06-08 12:22:28', 'юмор, приколы, смех', 'Описание страницы о проекте', 'index', 'published', 'page', 0, 0),
(185, 'kontakty', 2, 'Контакты', 'Вы хотели связаться с нами? Это замечательно! Мы тоже давно мечтали о связи — не только электронной, но и духовной. Однако пока наша команда контактов занята важным делом: проверяет, работает ли кнопка \"отправить\" на самом деле, а также спорит, сколько должно быть адресов, чтобы считаться «множеством».\r\n\r\nПока они решают эти глобальные вопросы, мы предлагаем вам немного подождать. Скоро здесь появятся все возможные способы добраться до нас — от электронной почты до телепатии (последнее пока в стадии тестирования).\r\n\r\nЕсли у вас срочное дело, можете попробовать поймать одного из редакторов на просторах сайта — обычно они шатаются где-то между рубриками «Анекдоты» и «Истории». Или просто напишите нам через форму обратной связи (когда она заработает), или как-нибудь иначе — мы всегда рады общению, особенно если оно с юмором!\r\n\r\nТем временем предлагаем вам не скучать, а читать что-нибудь смешное. А потом рассказать об этом друзьям. Или врагам. Всё равно полезно.\r\n\r\nСпасибо, что вы с нами. Почти буквально.', '2025-06-08 12:22:28', '2025-06-08 12:22:28', 'контакты', 'Описание страницы контакты', 'index', 'published', 'page', 0, 0),
(186, 'policy', 2, 'Пользовательское соглашение', 'Добро пожаловать на страницу, которую почти никто не читает, но все соглашаются. Да-да, вот вы уже нажали «Принять», даже не дочитав до конца. Мы знаем.\r\n\r\nНо если вы всё-таки решили ознакомиться с текстом поближе — будьте осторожны. Здесь может быть (и скорее всего будет) множество скучных правил, условий использования, упоминаний о законах и всяких важных вещах, которые мы обязаны написать, чтобы нас не забанили или не потребовали компенсацию за то, что кто-то обиделся на анекдот про бородатого дядю Ваню.\r\n\r\nСейчас эта страница находится в состоянии \"мы хотели, но ещё не успели\". Наш юрист (или человек, который так назвался после просмотра сериала про адвокатов) работает над тем, чтобы превратить обычный набор буков в официальный документ. С кучей пунктов, подпунктов и парой шуток, спрятанных между строк.\r\n\r\nЧто вас ожидает:\r\n\r\nПравила использования нашего портала (никому не вредить, ничего не ломать, не кормить админку после полуночи).\r\nИнформация о ваших правах и обязанностях (например, что вы сами решаете, что читать, но несёте ответственность за последствия — особенно если начнёте повторять анекдоты на семейном ужине).\r\nУсловия согласия на обработку данных (мы не продадим их третьим лицам... разве что друзьям, и то — только если они попросят очень вежливо).\r\nКак только всё будет готово, вы узнаете, можно ли нам вообще публиковать то, что мы публикуем, и имеете ли вы право это читать.\r\n\r\nА пока просто считайте, что вы уже всё одобрили, и продолжайте радоваться жизни, нашим шуткам и случайным мемам, которые иногда грустят в уголке.\r\n\r\nСпасибо, что вы с нами — по обоюдному согласию сторон.', '2025-06-08 12:22:28', '2025-06-08 12:22:28', 'пользовательское соглашение', 'Описание страницы пользовательское соглашение', 'index', 'published', 'page', 0, 0),
(187, 'sitemap', 2, 'Карта сайта', 'Вы попали сюда, значит, либо вы — опытный искатель приключений (и кнопок), либо просто потерялись среди нашего безграничного океана юмора. Не волнуйтесь, мы тоже иногда не можем найти, куда запрятали главную страницу.\r\n\r\nПока что эта карта больше похожа на меню в кафе, где половина блюд уже закончилась, а официант уверяет, что «всё есть, просто не всё сразу». Но обещаем — скоро здесь будет настоящий путеводитель по нашему порталу: удобный, понятный и такой же доброжелательный, как наш админ, когда он не в плохом настроении после чтения комментариев.\r\n\r\nЧто вы сможете найти:\r\n\r\nРазделы с анекдотами (свежими, как утренний хлеб, и не менее питательными).\r\nИстории из жизни (потому что реальность порой смешнее вымысла).\r\nВесёлые стихи (рифма есть — совесть не просим).\r\nАвторские колонки и сатира (остро, но не до крови).\r\nВозможно даже кое-что полезное — но это строго на ваш страх и риск.\r\nА пока предлагаем ориентироваться по принципу: «Кликнул — не пожалел», или как говорится у нас в редакции — «Вперёд, туда, где ещё никто не успел заблудиться!»\r\n\r\nЕсли вдруг найдёте что-то интересное по пути — не держите в себе, делитесь с друзьями. А если потеряетесь — не переживайте, выход всегда там, где вход.', '2025-06-08 12:22:28', '2025-06-08 12:40:49', 'карта сайта', 'Описание страницы карта сайта', 'noindex, follow', 'published', 'page', 0, 0),
(188, 'istoriya-1', 2, 'История 1', 'Представьте, что вы едете на машине. На спидометре стрелка показывает 60 км/ч — это ваша мгновенная скорость в конкретный момент времени. Именно так работает производная!\r\nОна отвечает на вопрос: «Как быстро меняется одна величина (например, путь) относительно другой (например, времени)?»\r\n\r\nПример из жизни:\r\n\r\nВы замечаете, что за 1 час температура на улице поднялась с +5°C до +10°C.\r\nСредняя скорость изменения: 10−51=5110−5​=5°C в час.\r\nНо если температура росла неравномерно (сначала быстро, потом медленно), производная покажет её мгновенное изменение в конкретную минуту.\r\nКак понять производную через графики\r\nДопустим, вы рисуете график, где по оси Х — время, а по оси Y — расстояние, которое вы прошли.\r\n\r\nСредняя скорость — это наклон прямой между двумя точками (например, за весь день).\r\nПроизводная (мгновенная скорость) — это наклон касательной к кривой в конкретной точке.\r\nПроще говоря, чем круче график в определённый момент, тем больше производная (и тем быстрее что-то меняется).\r\n\r\nПримеры из жизни, где встречается производная\r\n1. Экономика: прибыль компании\r\nДопустим, компания продаёт кофе.\r\n\r\nПроизводная покажет, как быстро растёт прибыль при увеличении продаж на 1 чашку.\r\nЕсли график прибыли резко идёт вверх — производная большая (бизнес процветает).\r\nЕсли график падает — производная отрицательная (убытки).\r\n2. Медицина: действие лекарства\r\nДоктор смотрит, как быстро снижается температура у пациента после приёма таблетки.\r\nПроизводная здесь — скорость выздоровления (например, на сколько градусов в час падает жар).\r\n3. Спорт: подготовка марафонца\r\nТренер анализирует, как увеличивается скорость бега спортсмена от недели к неделе.\r\nПроизводная покажет, в какой момент прогресс замедлился и нужно сменить тренировки.\r\n4. Строительство: наполнение бассейна\r\nЕсли вы открываете кран, производная — это скорость наполнения (литры в минуту).\r\nЕсли кран засорился и вода течёт медленнее, производная уменьшается.\r\nЗачем это нужно?\r\nПроизводная помогает:\r\n\r\nРассчитать оптимальную скорость поезда, чтобы он не опоздал.\r\nПредсказать, когда закончится бензин в баке.\r\nПонять, как быстро тают льды в Арктике.\r\nСоздавать реалистичную анимацию в играх (например, падение мяча под уклон).\r\nКак посчитать производную? (Минимум формул)\r\nДля тех, кто хочет чуть больше математики:\r\n\r\nВозьмите функцию, например, y=x² (путь зависит от времени).\r\nПроизводная этой функции — y′=2x.\r\nЭто значит, что скорость изменения y в любой момент x равна 2x.\r\nНапример:\r\n\r\nВ момент времени x=3 скорость будет 2×3=6.\r\nЧем больше x, тем быстрее растёт y.\r\nКрасным проведена касательная к параболе в точке А.\r\nНо не пугайтесь: в жизни производные часто считают компьютеры. Ваша задача — понять, что они означают.\r\n\r\nПроизводная — это как «математическая интуиция», она помогает чувствовать, как мир меняется вокруг нас:\r\n\r\nКогда вы ждёте автобус и решаете, бежать или идти шагом,\r\nКогда видите, как быстро темнеет зимой,\r\nИли когда пытаетесь успеть на скидку в магазине.\r\nПроизводная превращает абстрактные числа в истории о движении, росте и времени. Попробуйте замечать эти изменения — и математика станет чуть ближе к реальности!\r\n\r\nА вы задумывались, как быстро меняется что-то в вашей жизни? Делитесь примерами в комментариях.', '2025-04-22 16:54:59', '2025-04-24 13:44:14', 'История 1', 'описание истории 1', 'index, follow', 'published', 'post', 0, 0),
(189, 'post-ot-13062025', 2, 'Пост от 13.06.2025', '', '2025-06-13 17:26:05', '2025-06-13 17:26:05', NULL, NULL, 'index', 'pending', 'post', 0, 0),
(196, 'predlozhennyy-material-2025-06-13-202846', 2, 'Пост от 13.06.2025', '', '2025-06-13 17:28:46', '2025-06-13 17:28:46', NULL, NULL, 'index', 'pending', 'post', 0, 0),
(197, 'predlozhennyy-material-2025-06-13-202910', 2, 'Пост от 13.06.2025', 'sdfgsdfgdfsdfgsdfgdfgdfg', '2025-06-13 17:29:10', '2025-06-13 17:29:10', NULL, NULL, 'index', 'pending', 'post', 0, 0),
(198, 'predlozhennyy-material-2025-06-13-203658', 2, 'Пост от 13.06.2025', 'asdfasdfadsf', '2025-06-13 17:36:58', '2025-06-13 17:36:58', NULL, NULL, 'index', 'pending', 'post', 0, 0),
(214, 'predlozhennyy-material-2025-06-13-204531', 2, 'Пост от 13.06.2025', '', '2025-06-13 17:45:31', '2025-06-13 17:45:31', NULL, NULL, 'index', 'pending', 'post', 0, 0),
(215, 'predlozhennyy-material-2025-06-13-204608', 2, 'Пост от 13.06.2025', 'вапывапрывапвпвапвапв', '2025-06-13 17:46:08', '2025-06-13 17:46:08', NULL, NULL, 'index', 'pending', 'post', 0, 0),
(216, 'predlozhennyy-material-2025-06-15-190627', 2, 'Пост от 15.06.2025', 'df ghd ghdfgf', '2025-06-15 16:06:27', '2025-06-15 16:06:27', NULL, NULL, 'index', 'pending', 'post', 0, 0),
(217, 'predlozhennyy-material-2025-06-15-191225', 2, 'Пост от 15.06.2025', 'gsdfgsdgsdfggfs', '2025-06-15 16:12:25', '2025-06-15 16:12:25', NULL, NULL, 'index', 'pending', 'post', 0, 0),
(218, 'predlozhennyy-material-2025-06-15-193232', 2, 'Пост от 15.06.2025', 'ячсмчясмвыавыафыва', '2025-06-15 16:32:32', '2025-06-15 16:32:32', NULL, NULL, 'index', 'pending', 'post', 0, 0),
(219, 'predlozhennyy-material-2025-06-15-193249', 2, 'Пост от 15.06.2025', 'ыва ыва вапыва п', '2025-06-15 16:32:49', '2025-06-15 16:32:49', NULL, NULL, 'index', 'pending', 'post', 0, 0),
(220, 'predlozhennyy-material-2025-06-15-193614', 2, 'Пост от 15.06.2025', 'we g ывап sdfgsdfg', '2025-06-15 16:36:14', '2025-06-15 16:36:14', NULL, NULL, 'index', 'pending', 'post', 0, 0),
(221, 'predlozhennyy-material-2025-06-15-193836', 2, 'Пост от 15.06.2025', 'sвапывапваып', '2025-06-15 16:38:36', '2025-06-15 16:38:36', NULL, NULL, 'index', 'pending', 'post', 0, 0),
(222, 'predlozhennyy-material-2025-06-15-193851', 2, 'Пост от 15.06.2025', 'вы ыва sdfs', '2025-06-15 16:38:51', '2025-06-15 16:38:51', NULL, NULL, 'index', 'pending', 'post', 0, 0),
(227, 'predlozhennyy-material-2025-06-15-200020', 2, 'Пост от 15.06.2025', 'sdfsdfsdfsdfs', '2025-06-15 17:00:20', '2025-06-15 17:00:20', NULL, NULL, 'index', 'pending', 'post', 0, 0),
(228, 'predlozhennyy-material-2025-06-15-200129', 2, 'Пост от 15.06.2025', 'sdfsgsdfgasdfsad', '2025-06-15 17:01:29', '2025-06-15 17:01:29', NULL, NULL, 'index', 'pending', 'post', 0, 0),
(230, 'predlozhennyy-material-2025-06-15-200311', 2, 'Пост от 15.06.2025', 'вапвап вап вап', '2025-06-15 17:03:11', '2025-06-15 17:03:11', NULL, NULL, 'index', 'pending', 'post', 0, 0),
(231, 'predlozhennyy-material-2025-06-15-201000', 2, 'Пост от 15.06.2025', 'фывафываыфваыфва', '2025-06-15 17:10:00', '2025-06-15 17:10:00', NULL, NULL, 'index', 'pending', 'post', 0, 0),
(232, 'predlozhennyy-material-2025-06-15-203040', 2, 'Пост от 15.06.2025', 'asdfasdfasdfaf', '2025-06-15 17:30:40', '2025-06-15 17:30:40', NULL, NULL, 'index', 'pending', 'post', 0, 0),
(233, 'predlozhennyy-material-2025-06-15-203143', 2, 'Пост от 15.06.2025', 'sdfasdfsdf', '2025-06-15 17:31:43', '2025-06-15 17:31:43', NULL, NULL, 'index', 'pending', 'post', 0, 0);

-- --------------------------------------------------------

--
-- Структура таблицы `post_category`
--

CREATE TABLE `post_category` (
  `post_id` int NOT NULL,
  `category_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `post_category`
--

INSERT INTO `post_category` (`post_id`, `category_id`) VALUES
(3, 3),
(5, 3),
(4, 4),
(188, 6);

-- --------------------------------------------------------

--
-- Структура таблицы `post_tag`
--

CREATE TABLE `post_tag` (
  `post_id` int NOT NULL,
  `tag_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `post_tag`
--

INSERT INTO `post_tag` (`post_id`, `tag_id`) VALUES
(3, 7),
(3, 8),
(186, 9),
(186, 10);

-- --------------------------------------------------------

--
-- Структура таблицы `post_votes`
--

CREATE TABLE `post_votes` (
  `id` int NOT NULL,
  `post_id` int NOT NULL,
  `visitor_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `vote_type` enum('like','dislike') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `post_votes`
--

INSERT INTO `post_votes` (`id`, `post_id`, `visitor_id`, `created_at`, `updated_at`, `vote_type`) VALUES
(3, 5, 1, '2025-04-24 14:42:42', '2025-04-24 14:42:42', 'like'),
(4, 5, 2, '2025-04-24 14:44:51', '2025-04-24 14:44:51', 'like'),
(5, 5, 4, '2025-04-24 14:45:32', '2025-04-24 14:45:32', 'dislike'),
(6, 4, 2, '2025-04-24 14:45:32', '2025-04-24 14:45:32', 'dislike'),
(8, 4, 1, '2025-06-08 15:17:19', '2025-06-08 15:17:19', 'dislike');

--
-- Триггеры `post_votes`
--
DELIMITER $$
CREATE TRIGGER `after_post_vote_insert` AFTER INSERT ON `post_votes` FOR EACH ROW BEGIN
    IF NEW.vote_type = 'like' THEN
        UPDATE posts SET likes_count = likes_count + 1 WHERE id = NEW.post_id;
    ELSEIF NEW.vote_type = 'dislike' THEN
        UPDATE posts SET dislikes_count = dislikes_count + 1 WHERE id = NEW.post_id;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_post_vote_delete` BEFORE DELETE ON `post_votes` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Удаление голоса запрещено';
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_post_vote_update` BEFORE UPDATE ON `post_votes` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Изменение голоса запрещено';
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `roles`
--

CREATE TABLE `roles` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`) VALUES
(1, 'Administrator', 'Администратор'),
(2, 'Moderator', 'Модератор'),
(3, 'Redaktor', 'Редактор');

-- --------------------------------------------------------

--
-- Структура таблицы `tags`
--

CREATE TABLE `tags` (
  `id` int NOT NULL,
  `url` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `tags`
--

INSERT INTO `tags` (`id`, `url`, `name`, `created_at`, `updated_at`) VALUES
(7, 'anekdot-dnya', 'Анекдот дня', '2025-04-22 16:56:25', '2025-04-24 12:14:08'),
(8, 'smeshnoe', 'Смешное', '2025-06-08 15:13:17', '2025-06-08 15:13:17'),
(9, 'page', 'Страница', '2025-06-09 12:36:16', '2025-06-09 12:36:16'),
(10, 'policy', 'Соглашение', '2025-06-09 12:36:16', '2025-06-09 12:36:16');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `visible_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `name`, `visible_name`, `email`, `password`, `role_id`, `created_at`, `updated_at`) VALUES
(2, 'Администратор', 'Админ', '', '', 1, '2025-04-01 12:08:57', '2025-04-01 12:08:57');

-- --------------------------------------------------------

--
-- Структура таблицы `visitors`
--

CREATE TABLE `visitors` (
  `id` int NOT NULL,
  `uuid` char(36) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `visitors`
--

INSERT INTO `visitors` (`id`, `uuid`) VALUES
(1, '222'),
(2, '333'),
(4, '555'),
(7, 'zsasd');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_category_url` (`url`),
  ADD KEY `idx_categories_name` (`name`);

--
-- Индексы таблицы `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `comments_ibfk_3` (`visitor_id`);

--
-- Индексы таблицы `media`
--
ALTER TABLE `media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_media_user_id` (`user_id`),
  ADD KEY `idx_post_image` (`post_id`,`file_type`,`id`);

--
-- Индексы таблицы `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_url` (`url`),
  ADD KEY `posts_ibfk_1` (`user_id`),
  ADD KEY `idx_status_article_updated` (`status`,`article_type`,`updated_at`);

--
-- Индексы таблицы `post_category`
--
ALTER TABLE `post_category`
  ADD PRIMARY KEY (`post_id`,`category_id`),
  ADD KEY `fk_post_category_category_id` (`category_id`);

--
-- Индексы таблицы `post_tag`
--
ALTER TABLE `post_tag`
  ADD PRIMARY KEY (`post_id`,`tag_id`),
  ADD KEY `fk_post_tag_tag_id` (`tag_id`);

--
-- Индексы таблицы `post_votes`
--
ALTER TABLE `post_votes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_post_visitor` (`post_id`,`visitor_id`),
  ADD KEY `idx_post_vote` (`post_id`,`vote_type`),
  ADD KEY `post_votes_ibfk_2` (`visitor_id`);

--
-- Индексы таблицы `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Индексы таблицы `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `unique_category_url` (`url`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`);

--
-- Индексы таблицы `visitors`
--
ALTER TABLE `visitors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid_unique` (`uuid`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT для таблицы `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `media`
--
ALTER TABLE `media`
  MODIFY `id` int NOT NULL AUTO_INCREMENT COMMENT 'Уникальный идентификатор файла', AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT для таблицы `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=234;

--
-- AUTO_INCREMENT для таблицы `post_votes`
--
ALTER TABLE `post_votes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT для таблицы `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `tags`
--
ALTER TABLE `tags`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `visitors`
--
ALTER TABLE `visitors`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `comments_ibfk_3` FOREIGN KEY (`visitor_id`) REFERENCES `visitors` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `media`
--
ALTER TABLE `media`
  ADD CONSTRAINT `media_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `media_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT;

--
-- Ограничения внешнего ключа таблицы `post_category`
--
ALTER TABLE `post_category`
  ADD CONSTRAINT `fk_post_category_category_id` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `post_category_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `post_tag`
--
ALTER TABLE `post_tag`
  ADD CONSTRAINT `fk_post_tag_tag_id` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `post_tag_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `post_votes`
--
ALTER TABLE `post_votes`
  ADD CONSTRAINT `post_votes_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_votes_ibfk_2` FOREIGN KEY (`visitor_id`) REFERENCES `visitors` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
