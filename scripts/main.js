let isInitMap = false;

document.getElementById('openModal').onclick = openModal;

function openModal() {
    event.preventDefault();

    document.getElementById('modal').style.display = 'flex';
    document.querySelector('body').style.overflowY = 'hidden';

    if (!isInitMap)
        initMap();
}

document.getElementById('close').onclick = closeModal;

function closeModal() {
    document.getElementById('modal').style.display = 'none';
    document.querySelector('body').style.overflowY = 'auto';
}

let map, placemarks = [];

function initMap() {
    isInitMap = true;

    // Показать лоадер
    document.getElementById('loader').style.display = 'flex';
    document.querySelector('.loader_text').textContent = 'Загрузка ПВЗ ...';

    map = new ymaps.Map("map", {
        center: [56.3002, 38.1359], // Начальные координаты
        zoom: 12
    });

    // Загрузка точек ПВЗ
    fetch('/includes/ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({ request_type: 'get_pvz' })
    })
        .then(response => response.json())
        .then(data => {
            for (const key in data) {
                let pvz = data[key];

                if (!pvz.location || !pvz.location.latitude || !pvz.location.longitude) {
                    console.warn('Отсутствуют координаты у ПВЗ:', pvz);
                    continue;
                }

                const placemark = new ymaps.Placemark(
                    [pvz.location.latitude, pvz.location.longitude],
                    {
                        balloonContent: `<strong>${pvz.name}</strong><br>
                            ${pvz.location.address}<br>
                            Время работы: ${pvz.work_time}<br>
                            <div id="price_${pvz.location.city_code}" style="height: 50px;">
                                <span>Загрузка цен...</span>
                            </div>
                            <button id="selectPVZ_${pvz.location.city_code}" 
                                class="selectPVZ" 
                                onclick="selectPVZ('${pvz.code}', '${pvz.name}', '${pvz.location.address}', this.getAttribute('data-price'))">
                                Выбрать
                            </button>
                        `
                    },
                    {
                        preset: 'islands#greenIcon'
                    }
                );


                // При открытии балуна сразу загружаем стоимость доставки
                placemark.events.add('balloonopen', function () {
                    calculateCost(pvz.location.city_code);
                });

                map.geoObjects.add(placemark);
                placemarks.push(placemark);
            }
        })
        .catch(error => {
            console.error('Ошибка при загрузке ПВЗ:', error);
        })
        .finally(() => {
            // Скрыть лоадер после загрузки
            document.getElementById('loader').style.display = 'none';
        });
}

function calculateCost(pvzCode) {
    const toLocation = pvzCode;

    fetch('/includes/ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
            request_type: 'calculate_delivery',
            toLocation
        })
    })
        .then(response => response.json())
        .then(data => {
            let priceContainer = document.getElementById(`price_${pvzCode}`);

            if (data.error) {
                priceContainer.innerHTML = `<span style="color: red;">Ошибка загрузки</span>`;
            } else {
                const { total_sum, period_min, period_max } = data;
                priceContainer.innerHTML = `<strong>Стоимость:</strong> ${total_sum} ₽<br>
                    <strong>Срок:</strong> ${period_min} - ${period_max} дней
                `;

                // Добавляем атрибуты к кнопке выбора ПВЗ
                let selectButton = document.querySelector(`#selectPVZ_${pvzCode}`);
                if (selectButton) {
                    selectButton.setAttribute('data-price', total_sum);
                }
            }
        })
        .catch(() => {
            let priceContainer = document.getElementById(`price_${pvzCode}`);
            priceContainer.innerHTML = `<span style="color: red;">Ошибка загрузки цен</span>`;
        });
}


function selectPVZ(code, name, address, price) {
    closeModal();

    document.getElementById('deliveryPoint').value = code;
    document.getElementById('pvzName').value = name;
    document.getElementById('pvzAddress').value = address;
    document.getElementById('pvzPrice').value = price; // Записываем цену, если есть

    document.getElementById('pvzNameBlock').style.display = 'block';
    document.getElementById('pvzAddressBlock').style.display = 'block';

    updateBuyButtonState();
}

function updateBuyButtonState() {
    const tshirtSize = document.querySelector('textarea[name="notes"]').value.trim();
    const pvzName = document.getElementById('pvzName').value.trim();
    const buyButton = document.getElementById('buyButton');
    const sizeButtons = document.querySelectorAll('.size-button');

    // Подсветка размеров, если размер не выбран
    if (!tshirtSize) {
        // Добавляем класс для подсветки
        sizeButtons.forEach(button => {
            button.classList.add('highlight');
        });
    } else {
        // Убираем подсветку
        sizeButtons.forEach(button => {
            button.classList.remove('highlight');
        });
    }

    // Кнопка всегда активна, проверки делаем при нажатии
    buyButton.disabled = false;
    buyButton.style.opacity = '1';
    buyButton.style.cursor = 'pointer';
}

function validateForm() {
    // Получаем значения полей
    const recipientName = document.getElementById('recipientName').value.trim();
    const recipientPhone = document.getElementById('recipientPhone').value.trim();
    const recipientEmail = document.getElementById('recipientEmail').value.trim();
    const deliveryPoint = document.getElementById('deliveryPoint').value.trim();
    const pvzName = document.getElementById("pvzName").value.trim();
    const tshirtSize = document.querySelector('textarea[name="notes"]').value.trim();

    // Скрываем все ошибки перед проверкой
    hideAllErrors();

    let isValid = true;

    // Проверка размера
    if (!tshirtSize) {
        document.getElementById('orderSizeAlert').style.display = 'block';
        // Подсветка размеров
        const sizeButtons = document.querySelectorAll('.size-button');
        sizeButtons.forEach(button => {
            button.classList.add('highlight');
        });
        isValid = false;
    }

    // Проверка ФИО
    if (!recipientName) {
        document.getElementById('nameError').style.display = 'block';
        isValid = false;
    }

    // Проверка телефона
    if (!recipientPhone) {
        document.getElementById('phoneError').style.display = 'block';
        isValid = false;
    }

    // Проверка email
    if (!recipientEmail) {
        document.getElementById('emailError').style.display = 'block';
        isValid = false;
    }

    // Проверка ПВЗ
    if (!pvzName || !deliveryPoint) {
        document.getElementById('pvzError').style.display = 'block';
        isValid = false;
    }

    return isValid;
}

function hideAllErrors() {
    // Скрываем все сообщения об ошибках
    document.getElementById('orderSizeAlert').style.display = 'none';
    document.getElementById('nameError').style.display = 'none';
    document.getElementById('phoneError').style.display = 'none';
    document.getElementById('emailError').style.display = 'none';
    document.getElementById('pvzError').style.display = 'none';
    document.getElementById('validationErrors').style.display = 'none';
}

function submitOrder() {
    // Проверяем форму перед отправкой
    if (!validateForm()) {
        // Если есть ошибки, показываем общее сообщение
        const validationErrors = document.getElementById('validationErrors');
        validationErrors.style.display = 'block';
        validationErrors.textContent = '⚠️ ПОЖАЛУЙСТА, ЗАПОЛНИТЕ ВСЕ ОБЯЗАТЕЛЬНЫЕ ПОЛЯ ⚠️';
        return false;
    }

    // Получаем значения полей для отправки
    const recipientName = document.getElementById('recipientName').value.trim();
    const recipientPhone = document.getElementById('recipientPhone').value.trim();
    const recipientEmail = document.getElementById('recipientEmail').value.trim();
    const deliveryPoint = document.getElementById('deliveryPoint').value.trim();
    const deliveryPrice = document.getElementById('pvzPrice').value.trim();
    const pvzName = document.getElementById("pvzName").value.trim();
    const pvzAddress = document.getElementById("pvzAddress").value.trim();
    const tshirtSize = document.querySelector('textarea[name="notes"]').value.trim();

    document.getElementById('loader').style.display = 'flex';
    document.querySelector('.loader_text').textContent = 'Создание заказа ...';

    // Подготовка данных для отправки
    const requestData = new URLSearchParams({
        request_type: 'create_order',
        recipientName,
        recipientPhone,
        email: recipientEmail,
        deliveryPoint,
        deliveryPrice,
        size: tshirtSize,
        pvzName,
        pvzAddress
    });

    // Отправка данных на сервер
    fetch('/includes/ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: requestData
    })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert(`❌ Ошибка: ${data.error}`)
            } else {
                if (data['requests'][0]['state'] == 'SUCCESSFUL' || data['requests'][0]['state'] == 'ACCEPTED') {
                    document.querySelector('#sdekOrderForm').submit();
                } else {
                    alert(`Заказ обработался c ошибкой - не создался !`)
                }
            }
        })
        .catch(error => {
            console.error('Ошибка при создании заказа:', error);
            alert(`Ошибка при создании заказа: ${error}`)
        })
        .finally(() => {
            document.getElementById('loader').style.display = 'none';
        });
}

document.addEventListener('DOMContentLoaded', function () {
    const sizeButtons = document.querySelectorAll('.size-button');
    sizeButtons.forEach(button => {
        button.classList.remove('active');
    });

    document.querySelector('textarea[name="notes"]').value = '';

    updateBuyButtonState();

    // Добавляем обработчик события на каждую кнопку размера
    sizeButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Убираем класс active с других кнопок
            sizeButtons.forEach(b => b.classList.remove('active'));
            // Добавляем класс active к нажатой кнопке
            button.classList.add('active');
            document.querySelector('textarea[name="notes"]').value = button.textContent;

            // Скрываем предупреждение, если оно было показано
            const orderSizeAlert = document.getElementById('orderSizeAlert');
            if (orderSizeAlert) {
                orderSizeAlert.style.display = 'none';
            }

            // Обновляем состояние кнопки покупки
            updateBuyButtonState();
        });
    });

    // Добавляем обработчик события для кнопки "В СОБСТВЕННОСТЬ"
    const buyButton = document.getElementById('buyButton');
    if (buyButton) {
        buyButton.addEventListener('click', function () {
            // Вызываем функцию отправки заказа
            submitOrder();
        });
    }
});
