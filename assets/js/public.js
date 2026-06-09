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

	var calBody = document.getElementById('meal-calendar-body');
	if (!calBody) return;

	function loadCalendar(type, year, month, pushState) {
		calBody.innerHTML = '<div style="text-align:center;padding:2rem;opacity:.5">Загрузка…</div>';

		var xhr = new XMLHttpRequest();
		xhr.open('GET', mealMenu.ajaxUrl + '?action=meal_get_calendar&meal_type=' + encodeURIComponent(type) + '&meal_y=' + encodeURIComponent(year) + '&meal_m=' + encodeURIComponent(month) + '&nonce=' + encodeURIComponent(mealMenu.nonce));
		xhr.onload = function() {
			if (xhr.status !== 200) {
				calBody.innerHTML = '<p>Ошибка загрузки.</p>';
				return;
			}
			try {
				var data = JSON.parse(xhr.responseText);
				if (data.ok) {
					calBody.innerHTML = data.html;
					if (pushState) {
						var url = new URL(window.location);
						url.searchParams.set('meal_type', type);
						url.searchParams.set('meal_y', year);
						url.searchParams.set('meal_m', month);
						history.pushState({ meal_type: type, meal_y: year, meal_m: month }, '', url);
					}
				} else {
					calBody.innerHTML = '<p>Ошибка загрузки.</p>';
				}
			} catch(e) {
				calBody.innerHTML = '<p>Ошибка обработки ответа.</p>';
			}
		};
		xhr.onerror = function() {
			calBody.innerHTML = '<p>Ошибка сети.</p>';
		};
		xhr.send();
	}

	document.addEventListener('click', function(e) {
		var tab = e.target.closest('.meal-tab');
		if (tab && tab.getAttribute('data-type')) {
			e.preventDefault();
			var type = tab.getAttribute('data-type');
			var year = tab.getAttribute('data-year');
			var month = tab.getAttribute('data-month');
			loadCalendar(type, year, month, true);
		}
	});

	document.addEventListener('click', function(e) {
		var link = e.target.closest('.meal-nav-month a');
		if (link && link.getAttribute('data-year')) {
			e.preventDefault();
			var year = link.getAttribute('data-year');
			var month = link.getAttribute('data-month');
			var activeTab = document.querySelector('.meal-tab--active');
			var type = activeTab ? activeTab.getAttribute('data-type') : '';
			loadCalendar(type, year, month, true);
		}
	});

	window.addEventListener('popstate', function(e) {
		if (e.state && e.state.meal_type) {
			loadCalendar(e.state.meal_type, e.state.meal_y, e.state.meal_m, false);
		} else {
			location.reload();
		}
	});

	var currentTab = document.querySelector('.meal-tab--active');
	if (currentTab) {
		var st = window.history.state;
		if (!st) {
			var url = new URL(window.location);
			history.replaceState({
				meal_type: url.searchParams.get('meal_type') || (currentTab.getAttribute('data-type') || ''),
				meal_y: url.searchParams.get('meal_y') || '',
				meal_m: url.searchParams.get('meal_m') || ''
			}, '', url);
		}
	}
})();