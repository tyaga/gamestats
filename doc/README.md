запросы

подпись с sig, сохраняется timestamp

регистрация
(uid)

сохранение данных юзера:

(uid, param[WINS]=12)
(uid, param[LEVEL]=4)

сохранение fight_stats

(uid, param[bot_id]=42, param[bot_level]=2)

онлайн считаем
событие логина (uid)
на любое событие ставим last_active

отчеты:

распределение (group by param * n, date, count(id))
регистрации (group by date, count(id))
отчет по боям
отчет по онлайну собирается в менеджере за последние 5 минут, сохраняется

(game_id, uid, sig, param[NAME]=VALUE, param[NAME1]=VALUE1)

