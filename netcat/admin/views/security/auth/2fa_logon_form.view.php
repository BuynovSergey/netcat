<?php

if (!class_exists('nc_core')) {
    die;
}

/** @var nc_security_2fa_code $code */
/** @var int $length */

// количество оставшихся попыток будет показано, если их осталось 2 и меньше
$SHOW_ATTEMPTS_LEFT_BELOW = 3;

// время действия будет показано, если осталось 120 секунд и меньше
$SHOW_EXPIRATION_TIME_BELOW = 121;

?>

<form method="post" class="nc-2fa-form">
    <h2>
        <?php if (!$code->get('IsSent')): ?>
            <?= NETCAT_2FA_ERROR ?>
        <?php else: ?>
            <?= NETCAT_2FA_ENTER_CODE ?>
        <?php endif; ?>
    </h2>
    <div class="nc-2fa-hint"></div>
    <div class="nc-2fa-input-block">
        <input type="text" class="nc-2fa-input" data-maxlength="<?= $length ?>">
    </div>
    <div class="nc-2fa-refresh">
        <div class="nc-2fa-refresh-countdown nc--hide">
            <?= NETCAT_2FA_SEND_AGAIN ?><br>
            <?= NETCAT_2FA_REFRESH_IN ?>
            <span class="nc-2fa-refresh-in">&nbsp;&nbsp;</span>
        </div>
        <div class="nc-2fa-refresh-link nc--hide">
            <a href="#"><?= NETCAT_2FA_SEND_AGAIN ?></a>
        </div>
    </div>
    <div class="nc-2fa-attempts-hint"></div>
    <div class="nc-2fa-expiration">
        <div class="nc-2fa-expires-countdown nc--hide">
            <?=  NETCAT_2FA_CODE_VALID_FOR ?>
            <span class="nc-2fa-expires-in">&nbsp;&nbsp;&nbsp;</span>
        </div>
        <div class="nc-2fa-expired nc--hide">
            <?= NETCAT_2FA_CODE_NOT_VALID ?>
        </div>
    </div>

</form>

<script>
    (function () {
        if (window.frameElement) {
            $nc('body').hide();
            top.location.reload();
            return;
        }

        const SHOW_ATTEMPTS_LEFT_BELOW = <?= $SHOW_ATTEMPTS_LEFT_BELOW ?>;
        const SHOW_EXPIRATION_TIME_BELOW = <?= $SHOW_EXPIRATION_TIME_BELOW ?>;

        // Поле ввода
        const
            $input = $nc('.nc-2fa-input'),
            maxLength = $input.data('maxlength');

        let previousValue;

        $input
            .focus()
            .on('input', (e) => {
                let value = $input.val();
                value = value.replace(/\D+/g, '');
                if (value.length > maxLength) {
                    value = value.substring(0, maxLength);
                }
                $input.val(value);

                if (value !== previousValue) {
                    $input.removeClass('nc-2fa-input-error nc-2fa-input-success');
                    if (value.length == maxLength) {
                        checkCode(value);
                    }
                }

                previousValue = value;
            });

        // Отсчет времени до повтора
        const
            $refreshCountdown = $nc('.nc-2fa-refresh-countdown'),
            $refreshLink = $nc('.nc-2fa-refresh-link'),
            $refreshIn = $nc('.nc-2fa-refresh-in');

        let refreshCountdownSecondsLeft = 0,
            refreshCountdownInterval;

        function updateRefreshData() {
            if (refreshCountdownSecondsLeft <= 0) {
                clearInterval(refreshCountdownInterval);
                $refreshCountdown.hide();
                $refreshLink.toggle(refreshCountdownSecondsLeft >= 0); // countdown = -1 означает, что повторная отправка кода невозможна
            } else {
                $refreshIn.html(formatTime(refreshCountdownSecondsLeft))
                $refreshCountdown.show();
                $refreshLink.hide();
            }
        }

        // Отсчёт времени до повторной отправки
        function startRefreshCountdown() {
            updateRefreshData();
            if (refreshCountdownSecondsLeft > 0) {
                clearInterval(refreshCountdownInterval);
                refreshCountdownInterval = setInterval(
                    () => {
                        refreshCountdownSecondsLeft--;
                        updateRefreshData();
                    }, 1000);
            }
        }

        // Повторная отправка кода
        $refreshLink.find('a').click(() => {
            $input.addClass('nc--loading');
            $nc.post(
                    location.toString(),
                    { nc_security_2fa_refresh: 1 },
                    null,
                    'json'
                )
                .done((response) => {
                    update(response);
                    $input.focus();
                })
                .always(() => {
                    $input.removeClass('nc--loading');
                });
            return false;
        });

        // Время до истечения срока действия кода
        const
            $expiresCountdown = $nc('.nc-2fa-expires-countdown'),
            $expiresIn = $nc('.nc-2fa-expires-in'),
            $expired = $nc('.nc-2fa-expired');

        let expiresCountdownSecondsLeft = 0,
            expiresCountdownInterval;

        function updateExpirationData() {
            if (expiresCountdownSecondsLeft <= 0) {
                clearInterval(expiresCountdownInterval);
                $expiresCountdown.hide();
                $expired.show();
                $input.prop('disabled', true);
            } else {
                $expiresIn.html(formatTime(expiresCountdownSecondsLeft))
                $expiresCountdown.toggle(expiresCountdownSecondsLeft < SHOW_EXPIRATION_TIME_BELOW);
                $expired.hide();
                $input.prop('disabled', false);
            }
        }

        // Отсчёт времени до повторной отправки
        function startExpirationCountdown() {
            updateExpirationData();
            if (expiresCountdownSecondsLeft > 0) {
                clearInterval(expiresCountdownInterval);
                expiresCountdownInterval = setInterval(
                    () => {
                        expiresCountdownSecondsLeft--;
                        updateExpirationData();
                    }, 1000);
            }
        }

        // Форматирование времени как ММ:СС
        function formatTime(seconds) {
            const
                s = Math.ceil(seconds % 60),
                m = Math.ceil((seconds - s) / 60);
            return m.toString().padStart(2, '0') + ':' + s.toString().padStart(2, '0');
        }

        // Общая часть обновления по ответу сервера
        function update(info) {
            if ('refresh_in' in info) {
                refreshCountdownSecondsLeft = Number(info.refresh_in);
                startRefreshCountdown();
            }
            if ('hint' in info) {
                $nc('.nc-2fa-hint').html(info.hint);
            }
            if ('attempts_left' in info) {
                $nc('.nc-2fa-attempts-hint').html(info.attempts_hint).toggle(
                    info.attempts_left > 0 && info.attempts_left < SHOW_ATTEMPTS_LEFT_BELOW
                );
            }
            if (`is_sent` in info) {
                $nc('.nc-2fa-input-block').toggle(!!info.is_sent);
                $nc('.nc-2fa-expiration').toggle(!!info.is_sent);
            }
            if ('expires_in' in info) {
                expiresCountdownSecondsLeft = Number(info.expires_in);
                startExpirationCountdown();
            }
            if ('is_dynamic' in info) {
                $nc('.nc-2fa-refresh').toggle(!info.is_dynamic);
            }
        }

        // Проверка кода
        function checkCode(code) {
            $input.addClass('nc--loading');
            $nc.post(
                    location.toString(),
                    { nc_security_2fa_code: code },
                    null,
                    'json'
                )
                .done((response) => {
                    if (response.passed) {
                        $input.addClass('nc-2fa-input-success');
                        if (location.hash) {
                            location.reload();
                        } else {
                            location.assign(location.href); // reload with GET (to avoid form resubmission)
                        }
                    } else if (response.attempts_left == 0) {
                        location.reload(); // пользователь теперь заблокирован
                    } else {
                        $input.addClass('nc-2fa-input-error');
                        update(response);
                    }
                })
                .always(() => {
                    $input.removeClass('nc--loading');
                });
        }

        // Информация о текущем коде
        update(<?= nc_array_json($code->get_info()) ?>);

    })();
</script>