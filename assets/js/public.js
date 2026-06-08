(function() {
	var modal = document.getElementById('meal-modal');
	var body = document.getElementById('meal-modal-body');
	var title = document.getElementById('meal-modal-title');
	var closeBtn = document.getElementById('meal-modal-close');

	if (!modal || !body || !title || !closeBtn) return;

	function openModal(date, type, label) {
		title.textContent = label;
		body.innerHTML = '<div class="meal-modal-loader">Загрузка…</div>';
		modal.style.display = '';

		var xhr = new XMLHttpRequest();
		xhr.open('GET', mealMenu.ajaxUrl + '?action=meal_get_day_menu&date=' + encodeURIComponent(date) + '&type=' + encodeURIComponent(type) + '&nonce=' + encodeURIComponent(mealMenu.nonce));
		xhr.onload = function() {
			if (xhr.status !== 200) {
				body.innerHTML = '<p>Ошибка загрузки.</p>';
				return;
			}
			try {
				var data = JSON.parse(xhr.responseText);
				if (data.ok) {
					body.innerHTML = data.html;
				} else {
					body.innerHTML = data.html || '<p>Меню не найдено.</p>';
				}
			} catch(e) {
				body.innerHTML = '<p>Ошибка обработки ответа.</p>';
			}
		};
		xhr.onerror = function() {
			body.innerHTML = '<p>Ошибка сети.</p>';
		};
		xhr.send();
	}

	function closeModal() {
		modal.style.display = 'none';
	}

	document.addEventListener('click', function(e) {
		var trigger = e.target.closest('.meal-menu-trigger');
		if (trigger) {
			e.preventDefault();
			openModal(trigger.getAttribute('data-date'), trigger.getAttribute('data-type'), trigger.textContent);
		}
	});

	closeBtn.addEventListener('click', closeModal);

	modal.addEventListener('click', function(e) {
		if (e.target === modal) closeModal();
	});

	document.addEventListener('keydown', function(e) {
		if (e.key === 'Escape' && modal.style.display !== 'none') {
			closeModal();
		}
	});
})();
