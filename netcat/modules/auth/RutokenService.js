const RutokenService = function (plugin) {
    this.$plugin = plugin;
    this.selectedDeviceId = null;
    this.$errors = [];
    this.$tokenDialog = null;
    this.$tokenSelect = null;
    this.$tokenSubmitButton = null;
    this.$tokenPinCode = null;
    this.$tokenAlert = null;

    this.fillErrorsMeta();
    this.renderDialog();
}

RutokenService.prototype = {
    renderDialog(target = "body") {
        if (this.$tokenDialog) {
            return;
        }

        jQuery(target).append(`<div id="token-dialog" style="display:none;">
                                    <div class="token-content">
                                        <div class="nc-alert nc--red"></div>
                                        <select id="token-select" class="nc-select"></select>
                                        <input type="password" id="token-pin" value="" placeholder="Введите PIN-код"/>
                                        <div class="nc-btn nc--blue">Ввод</div>  
                                    </div>
                                </div>`);

        this.$tokenDialog = jQuery("#token-dialog");
        this.$tokenSelect = jQuery("#token-select");
        this.$tokenPinCode = jQuery("#token-pin");
        this.$tokenSubmitButton = jQuery("#token-dialog .nc-btn");
        this.$tokenAlert = jQuery("#token-dialog .nc-alert");
        const self = this;

        jQuery(this.$tokenDialog).on("click", function (e) {
            if (e.target === this) {
                self.hidePinDialog();
            }
        });

        jQuery(this.$tokenPinCode).on("input", function () {
            this.value = this.value.replace(/[^0-9\.]/g, "");
        })

        const style = document.createElement("style");
        style.innerHTML = `#token-dialog {
                                  display: flex;
                                  justify-content: center;
                                  align-items: center;
                                  position: fixed;
                                  top: 0;
                                  right: 0;
                                  bottom: 0;
                                  left: 0;
                                  background: rgba(0, 0, 0, 0.5);
                                  z-index: 999999;
                                }
                                
                                .token-content {
                                    min-width: 220px;
                                    display: flex;
                                    justify-content: center;
                                    flex-direction: column;
                                    gap: 10px;
                                    height: auto;
                                    min-height: 150px;
                                    background: #fff;
                                    padding: 30px 20px;
                                    border-radius: 10px;
                                    box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
                                }
                                
                                .token-content select {
                                    width: 100%;
                                }
                                
                                .nc-alert,
                                #token-keypairs {
                                    display:none;
                                }`

        document.head.appendChild(style);
    },

    async loginThroughDialog() {
        if (this.selectedDeviceId !== null) {
            return Promise.resolve();
        }

        const devices = await this.getDevices();
        devices.forEach((token, i) => this.$tokenSelect.append(`<option value="${i}">${token}</option>`));

        if (devices.length === 1) {
            jQuery(this.$tokenSelect).find("option").eq(0).attr("selected", true);
        }

        this.$tokenSelect.show();
        this.$tokenDialog.fadeIn(150);

        return new Promise((resolve, reject) => {
            this.$tokenSubmitButton.on("click", async () => {
                const id = this.$tokenSelect.val();
                const pin = this.$tokenPinCode.val();

                if (typeof id !== "undefined" && pin) {
                    try {
                        this.$tokenAlert.hide();

                        await this.login(id, pin);

                        this.hidePinDialog();
                        this.selectedDeviceId = id;

                        return resolve(id);
                    } catch (e) {
                        this.$tokenAlert.text(this.$getError(e));
                        this.$tokenAlert.show();
                    }
                }
            });
        })
    },

    hidePinDialog() {
        this.$tokenDialog.fadeOut(150);
        this.$tokenSelect.empty();
        this.$tokenPinCode.val("");
        this.$tokenAlert.hide();
        this.$tokenSubmitButton.unbind("click");
    },

    fillErrorsMeta() {
        if (this.$errors.length) {
            return;
        }

        const errorCodes = this.$plugin.errorCodes;

        this.$errors[errorCodes.UNKNOWN_ERROR] = "Неизвестная ошибка";
        this.$errors[errorCodes.BAD_PARAMS] = "Неправильные параметры";
        this.$errors[errorCodes.NOT_ENOUGH_MEMORY] = "Недостаточно памяти";
        this.$errors[errorCodes.DEVICE_NOT_FOUND] = "Устройство не найдено";
        this.$errors[errorCodes.DEVICE_ERROR] = "Ошибка устройства";
        this.$errors[errorCodes.TOKEN_INVALID] = "Ошибка чтения/записи устройства. Возможно, устройство было извлечено. Попробуйте выполнить enumerate";
        this.$errors[errorCodes.CERTIFICATE_CATEGORY_BAD] = "Недопустимый тип сертификата";
        this.$errors[errorCodes.CERTIFICATE_EXISTS] = "Сертификат уже существует на устройстве";
        this.$errors[errorCodes.CERTIFICATE_NOT_FOUND] = "Сертификат не найден";
        this.$errors[errorCodes.CERTIFICATE_HASH_NOT_UNIQUE] = "Хэш сертификата не уникален";
        this.$errors[errorCodes.CA_CERTIFICATES_NOT_FOUND] = "Корневые сертификаты не найдены";
        this.$errors[errorCodes.CERTIFICATE_VERIFICATION_ERROR] = "Ошибка проверки сертификата";
        this.$errors[errorCodes.PKCS11_LOAD_FAILED] = "Не удалось загрузить PKCS#11 библиотеку";
        this.$errors[errorCodes.PIN_LENGTH_INVALID] = "Некорректная длина PIN-кода";
        this.$errors[errorCodes.PIN_INCORRECT] = "Некорректный PIN-код";
        this.$errors[errorCodes.PIN_LOCKED] = "PIN-код заблокирован";
        this.$errors[errorCodes.PIN_CHANGED] = "PIN-код был изменен";
        this.$errors[errorCodes.SESSION_INVALID] = "Состояние токена изменилось";
        this.$errors[errorCodes.USER_NOT_LOGGED_IN] = "Выполните вход на устройство";
        this.$errors[errorCodes.ALREADY_LOGGED_IN] = "Вход на устройство уже был выполнен";
        this.$errors[errorCodes.ATTRIBUTE_READ_ONLY] = "Свойство не может быть изменено";
        this.$errors[errorCodes.KEY_NOT_FOUND] = "Соответствующая сертификату ключевая пара не найдена";
        this.$errors[errorCodes.KEY_ID_NOT_UNIQUE] = "Идентификатор ключевой пары не уникален";
        this.$errors[errorCodes.CEK_NOT_AUTHENTIC] = "Выбран неправильный ключ";
        this.$errors[errorCodes.KEY_LABEL_NOT_UNIQUE] = "Метка ключевой пары не уникальна";
        this.$errors[errorCodes.WRONG_KEY_TYPE] = "Неправильный тип ключа";
        this.$errors[errorCodes.LICENCE_READ_ONLY] = "Лицензия доступна только для чтения";
        this.$errors[errorCodes.DATA_INVALID] = "Неверные данные";
        this.$errors[errorCodes.UNSUPPORTED_BY_TOKEN] = "Операция не поддерживается токеном";
        this.$errors[errorCodes.KEY_FUNCTION_NOT_PERMITTED] = "Операция запрещена для данного типа ключа";
        this.$errors[errorCodes.BASE64_DECODE_FAILED] = "Ошибка декодирования даных из BASE64";
        this.$errors[errorCodes.PEM_ERROR] = "Ошибка разбора PEM";
        this.$errors[errorCodes.ASN1_ERROR] = "Ошибка декодирования ASN1 структуры";
        this.$errors[errorCodes.FUNCTION_REJECTED] = "Операция отклонена пользователем";
        this.$errors[errorCodes.FUNCTION_FAILED] = "Невозможно выполнить операцию";
        this.$errors[errorCodes.MECHANISM_INVALID] = "Указан неправильный механизм";
        this.$errors[errorCodes.ATTRIBUTE_VALUE_INVALID] = "Передан неверный атрибут";
        this.$errors[errorCodes.X509_UNABLE_TO_GET_ISSUER_CERT] = "Невозможно получить сертификат подписанта";
        this.$errors[errorCodes.X509_UNABLE_TO_GET_CRL] = "Невозможно получить CRL";
        this.$errors[errorCodes.X509_UNABLE_TO_DECRYPT_CERT_SIGNATURE] = "Невозможно расшифровать подпись сертификата";
        this.$errors[errorCodes.X509_UNABLE_TO_DECRYPT_CRL_SIGNATURE] = "Невозможно расшифровать подпись CRL";
        this.$errors[errorCodes.X509_UNABLE_TO_DECODE_ISSUER_PUBLIC_KEY] = "Невозможно раскодировать открытый ключ эмитента";
        this.$errors[errorCodes.X509_CERT_SIGNATURE_FAILURE] = "Неверная подпись сертификата";
        this.$errors[errorCodes.X509_CRL_SIGNATURE_FAILURE] = "Неверная подпись CRL";
        this.$errors[errorCodes.X509_CERT_NOT_YET_VALID] = "Срок действия сертификата еще не начался";
        this.$errors[errorCodes.X509_CRL_NOT_YET_VALID] = "Срок действия CRL еще не начался";
        this.$errors[errorCodes.X509_CERT_HAS_EXPIRED] = "Срок действия сертификата истек";
        this.$errors[errorCodes.X509_CRL_HAS_EXPIRED] = "Срок действия CRL истек";
        this.$errors[errorCodes.X509_ERROR_IN_CERT_NOT_BEFORE_FIELD] = "Некорректные данные в поле \"notBefore\" у сертификата";
        this.$errors[errorCodes.X509_ERROR_IN_CERT_NOT_AFTER_FIELD] = "Некорректные данные в поле \"notAfter\" у сертификата";
        this.$errors[errorCodes.X509_ERROR_IN_CRL_LAST_UPDATE_FIELD] = "Некорректные данные в поле \"lastUpdate\" у CRL";
        this.$errors[errorCodes.X509_ERROR_IN_CRL_NEXT_UPDATE_FIELD] = "Некорректные данные в поле \"nextUpdate\" у CRL";
        this.$errors[errorCodes.X509_OUT_OF_MEM] = "Нехватает памяти";
        this.$errors[errorCodes.X509_DEPTH_ZERO_SELF_SIGNED_CERT] = "Недоверенный самоподписанный сертификат";
        this.$errors[errorCodes.X509_SELF_SIGNED_CERT_IN_CHAIN] = "В цепочке обнаружен недоверенный самоподписанный сертификат";
        this.$errors[errorCodes.X509_UNABLE_TO_GET_ISSUER_CERT_LOCALLY] = "Невозможно получить локальный сертификат подписанта";
        this.$errors[errorCodes.X509_UNABLE_TO_VERIFY_LEAF_SIGNATURE] = "Невозможно проверить первый сертификат";
        this.$errors[errorCodes.X509_CERT_CHAIN_TOO_LONG] = "Слишком длинная цепочка сертификатов";
        this.$errors[errorCodes.X509_CERT_REVOKED] = "Сертификат отозван";
        this.$errors[errorCodes.X509_INVALID_CA] = "Неверный корневой сертификат";
        this.$errors[errorCodes.X509_INVALID_NON_CA] = "Неверный некорневой сертфикат, помеченный как корневой";
        this.$errors[errorCodes.X509_PATH_LENGTH_EXCEEDED] = "Превышена длина пути";
        this.$errors[errorCodes.X509_PROXY_PATH_LENGTH_EXCEEDED] = "Превышина длина пути прокси";
        this.$errors[errorCodes.X509_PROXY_CERTIFICATES_NOT_ALLOWED] = "Проксирующие сертификаты недопустимы";
        this.$errors[errorCodes.X509_INVALID_PURPOSE] = "Неподдерживаемое назначение сертификата";
        this.$errors[errorCodes.X509_CERT_UNTRUSTED] = "Недоверенный сертификат";
        this.$errors[errorCodes.X509_CERT_REJECTED] = "Сертифкат отклонен";
        this.$errors[errorCodes.X509_APPLICATION_VERIFICATION] = "Ошибка проверки приложения";
        this.$errors[errorCodes.X509_SUBJECT_ISSUER_MISMATCH] = "Несовпадения субьекта и эмитента";
        this.$errors[errorCodes.X509_AKID_SKID_MISMATCH] = "Несовпадение идентификатора ключа у субьекта и доверенного центра";
        this.$errors[errorCodes.X509_AKID_ISSUER_SERIAL_MISMATCH] = "Несовпадение серийного номера субьекта и доверенного центра";
        this.$errors[errorCodes.X509_KEYUSAGE_NO_CERTSIGN] = "Ключ не может быть использован для подписи сертификатов";
        this.$errors[errorCodes.X509_UNABLE_TO_GET_CRL_ISSUER] = "Невозможно получить CRL подписанта";
        this.$errors[errorCodes.X509_UNHANDLED_CRITICAL_EXTENSION] = "Неподдерживаемое расширение";
        this.$errors[errorCodes.X509_KEYUSAGE_NO_CRL_SIGN] = "Ключ не может быть использован для подписи CRL";
        this.$errors[errorCodes.X509_KEYUSAGE_NO_DIGITAL_SIGNATURE] = "Ключ не может быть использован для цифровой подписи";
        this.$errors[errorCodes.X509_UNHANDLED_CRITICAL_CRL_EXTENSION] = "Неподдерживаемое расширение CRL";
        this.$errors[errorCodes.X509_INVALID_EXTENSION] = "Неверное или некорректное расширение сертификата";
        this.$errors[errorCodes.X509_INVALID_POLICY_EXTENSION] = "Неверное или некорректное расширение политик сертификата";
        this.$errors[errorCodes.X509_NO_EXPLICIT_POLICY] = "Явные политики отсутствуют";
        this.$errors[errorCodes.X509_DIFFERENT_CRL_SCOPE] = "Другая область CRL";
        this.$errors[errorCodes.X509_UNSUPPORTED_EXTENSION_FEATURE] = "Неподдерживаемое расширение возможностей";
        this.$errors[errorCodes.X509_UNNESTED_RESOURCE] = "RFC 3779 неправильное наследование ресурсов";
        this.$errors[errorCodes.X509_PERMITTED_VIOLATION] = "Неправильная структура сертифката";
        this.$errors[errorCodes.X509_EXCLUDED_VIOLATION] = "Неправильная структура сертфиката";
        this.$errors[errorCodes.X509_SUBTREE_MINMAX] = "Неправильная структура сертифката";
        this.$errors[errorCodes.X509_UNSUPPORTED_CONSTRAINT_TYPE] = "Неправильная структура сертфиката";
        this.$errors[errorCodes.X509_UNSUPPORTED_CONSTRAINT_SYNTAX] = "Неправильная структура сертифката";
        this.$errors[errorCodes.X509_UNSUPPORTED_NAME_SYNTAX] = "Неправильная структура сертфиката";
        this.$errors[errorCodes.X509_CRL_PATH_VALIDATION_ERROR] = "Неправильный путь CRL";
        this.$errors[errorCodes.CMS_CERTIFICATE_ALREADY_PRESENT] = "Сертификат уже используется";
        this.$errors[errorCodes.CANT_HARDWARE_VERIFY_CMS] = "Проверка множественной подписи с вычислением хеша на устройстве не поддерживается";
    },

    async getContainers() {
        await this.loginThroughDialog();

        try {
            const keys = await this.$plugin.enumerateKeys(this.selectedDeviceId, "");

            if (!keys.length) {
                this.selectedDeviceId = null;
                return Promise.reject("Отсутствуют ключевые пары для выбранного устройства");
            }

            return Promise.resolve(keys.map(key => this.$getContainerNameFromHex(key)));
        } catch (e) {
            return Promise.reject(this.$getError(e));
        }
    },

    async getDevices() {
        try {
            const devices = await this.$plugin.enumerateDevices();

            if (!devices.length) {
                return Promise.reject(this.$errors[this.$plugin.errorCodes.DEVICE_NOT_FOUND]);
            }

            return Promise.all(devices.map(device => this.$plugin.getDeviceInfo(device, this.$plugin.TOKEN_INFO_READER)));
        } catch (e) {
            return Promise.reject(this.$getError(e));
        }
    },

    async login(deviceId, pin) {
        const isLogged = await this.isDeviceLoggedIn(deviceId);

        if (isLogged) {
            return Promise.resolve(true);
        }

        try {
            await this.$plugin.login(deviceId, pin);
            return Promise.resolve(true);
        } catch (e) {
            return Promise.reject(this.$getError(e));
        }
    },

    async logout(deviceId) {
        try {
            await this.$plugin.logout(deviceId);
            return Promise.resolve(true);
        } catch (ignored) {}
    },

    async isDeviceLoggedIn(deviceId) {
        try {
            return await this.$plugin.getDeviceInfo(deviceId, this.$plugin.TOKEN_INFO_IS_LOGGED_IN);
        } catch (e) {
            return Promise.reject(this.$getError(e));
        }
    },

    async sign(keyName, message) {
        await this.loginThroughDialog();

        // Плагин принимает хэш на подпись в другом порядке байт, необходимо перевернуть
        const messageReversed = message.match(/.{2}/g).reverse().join(":");
        const keyId = this.$getContainerHexFromName(keyName);

        try {
            const rawSign = await this.$plugin.rawSign(this.selectedDeviceId, keyId, messageReversed, {});
            await this.logout(this.selectedDeviceId);

            //Результирующую подпись нужно привести к формату Рутокенвэб.
            //Результирующая подпись выходит в формате be(leR & leS). Сначала необходимо получить leR & leS, затем beR & beS.
            const sign = rawSign.split(":").reverse();
            const R = sign.splice(0, sign.length / 2).reverse();
            const S = sign.reverse();

            return Promise.resolve(R.join("") + S.join(""));
        } catch (e) {
            return Promise.reject(this.$getError(e));
        }
    },

    async generateKeyPair(containerName) {
        await this.loginThroughDialog();

        try {
            const keyOptions = {
                id: this.$getContainerHexFromName(containerName), paramset: "A",
                publicKeyAlgorithm: this.$plugin.PUBLIC_KEY_ALGORITHM_GOST3410_2001,
                signatureSize: 512
            }
            const keyId = await this.$plugin.generateKeyPair(this.selectedDeviceId, undefined, "", keyOptions);
            const publicKey = await this.$plugin.getPublicKeyValue(this.selectedDeviceId, keyId, {});
            await this.logout(this.selectedDeviceId);

            const res = publicKey.split(":").reverse();
            const A = res.splice(0, res.length / 2);
            const B = res;

            return Promise.resolve((B.join("") + A.join("")).toUpperCase());
        } catch (e) {
            this.selectedDeviceId = null;
            return Promise.reject(this.$getError(e));
        }
    },

    async deleteContainer(containerId) {
        await this.loginThroughDialog();

        try {
            await this.$plugin.deleteKeyPair(this.selectedDeviceId, this.$getContainerHexFromName(containerId));
            await this.logout(this.selectedDeviceId);

            return Promise.resolve(true);
        } catch (e) {
            return Promise.reject(this.$getError(e));
        }
    },

    $getError(reason) {
        return this.$errors[reason.message || reason] || reason;
    },

    $getContainerNameFromHex(str) {
        return (":" + str).split(":").reduce(function (str, i) {
            return str + String.fromCharCode("0x" + i);
        });
    },

    $getContainerHexFromName(str) {
        return str.split("").map(letter => letter.charCodeAt(0).toString(16)).join(":");
    },
}
