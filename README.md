# netcat
Тестовое задание Лаборатория Интернет.
Создание обратной связи и API работа с пользователями
путь /api/

Продемонстрированы разные способы работы: при помощи mysql запросов; при помощи api necat

# Создание пользователя
{
    "user": "senseyr@mail.ru",
    "password": "123",
    "type": "create_user",
    "name": "имя"
}

# Изменение пользователя
{
    "user": "senseyr@mail.ru",
    "password": "123",
    "type": "change_user",
    "name": "Имя"
}

# Авторизация пользователя
{
    "user": "senseyr@mail.ru",
    "password": "123",
    "type": "authorized_user"
}

# Получить информацию о пользователе
{
    "user": "senseyr@mail.ru",
    "password": "123",
    "type": "get_user",
    "id": "3"
}

# Удаление пользователя
{
    "user": "senseyr@mail.ru",
    "password": "123",
    "type": "delete_user",
    "id": "3"
}
