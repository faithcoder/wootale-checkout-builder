(function () {
function ready(callback) {
	if (document.readyState !== 'loading') {
		callback();
		return;
	}
	document.addEventListener('DOMContentLoaded', callback);
}

function fieldElement(field) {
	if (field.type === 'component') {
		if (field.key === 'order_review') {
			var orderHeading = document.getElementById('order_review_heading');
			if (orderHeading) {
				orderHeading.style.display = 'none';
			}
			return document.querySelector('#order_review');
		}
		if (field.key === 'payment_methods') {
			return document.querySelector('#payment .wc_payment_methods');
		}
		if (field.key === 'terms') {
			return document.querySelector('#payment .woocommerce-terms-and-conditions-wrapper');
		}
		if (field.key === 'place_order') {
			return document.querySelector('#payment .place-order');
		}
		return null;
	}

	return document.getElementById(field.key + '_field');
}

function cleanupEmptyNativeSections() {
	Array.prototype.forEach.call(document.querySelectorAll('.woocommerce-additional-fields'), function(section) {
		if (!section.querySelector('.form-row, p.form-row, #order_comments_field')) {
			section.style.display = 'none';
		}
	});
	var payment = document.getElementById('payment');
	if (payment && !payment.querySelector('.wc_payment_methods, .woocommerce-terms-and-conditions-wrapper, .place-order')) {
		payment.style.display = 'none';
	}
}

function mountCheckoutSteps() {
	var workflow = window.wtcbClassicWorkflow || {};
	var form = document.querySelector('form.checkout');

	if (!form || !workflow.steps || form.dataset.wtcbClassicMounted === 'true') {
		return;
	}

	var host = document.createElement('div');
	var nav = document.createElement('ol');
	var panels = document.createElement('div');
	var storageKey = 'wtcb_active_step';
	var rememberedStep = workflow.rememberStep && window.localStorage ? Number(window.localStorage.getItem(storageKey) || 0) : 0;
	var state = { active: Math.max(0, rememberedStep), completed: [] };
	var visibleSteps = (workflow.steps || []).filter(function (step) {
		return (step.fields || []).some(function (field) { return field.enabled !== false; });
	});
	var isMultiStep = workflow.multiStepEnabled !== false && visibleSteps.length > 1;
	var indicator = workflow.indicator || 'number';
	var navigation = workflow.navigation || 'line';
	navigation = navigation === 'tabs' ? 'line' : navigation;
	var iconMap = { '1': '1', user: '♙', flag: '⚑', star: '☆', check: '✓' };

	host.className = 'wtcb-classic-checkout wtcb-orientation-' + (workflow.orientation || 'horizontal') + ' wtcb-indicator-' + indicator + ' wtcb-connector-' + (workflow.connector || 'solid') + ' wtcb-navigation-' + navigation + (isMultiStep ? '' : ' is-single-step');
	host.style.setProperty('--wtcb-active-color', workflow.activeColor || '#2563eb');
	host.style.setProperty('--wtcb-completed-color', workflow.completedColor || '#16a34a');
	host.style.setProperty('--wtcb-inactive-color', workflow.inactiveColor || '#6b7280');
	host.style.setProperty('--wtcb-connector-thickness', (workflow.connectorThickness || 2) + 'px');
	host.style.setProperty('--wtcb-connector-gap', (workflow.connectorGap || 24) + 'px');
	host.style.setProperty('--wtcb-previous-button-color', workflow.previousButtonColor || '#1f2937');
	host.style.setProperty('--wtcb-previous-button-bg', workflow.previousButtonBackground || '#f5f6f7');
	host.style.setProperty('--wtcb-next-button-color', workflow.nextButtonColor || '#ffffff');
	host.style.setProperty('--wtcb-next-button-bg', workflow.nextButtonBackground || '#2563eb');
	host.style.setProperty('--wtcb-continue-button-color', workflow.continueButtonColor || '#ffffff');
	host.style.setProperty('--wtcb-continue-button-bg', workflow.continueButtonBackground || '#16a34a');
	nav.className = 'wtcb-classic-steps';
	panels.className = 'wtcb-classic-panels';
	if (isMultiStep) {
		host.append(nav);
	}
	host.append(panels);
	form.prepend(host);

	visibleSteps.forEach(function (step, index) {
		var item = document.createElement('li');
		var button = document.createElement('button');
		var panel = document.createElement('section');
		var heading = document.createElement('h3');
		var description = document.createElement('p');

		if (isMultiStep) {
			var marker = document.createElement('span');
			var label = document.createElement('span');

			button.type = 'button';
			marker.className = 'wtcb-step-marker';
			label.className = 'wtcb-step-label';
			label.textContent = step.title || ('Step ' + (index + 1));
			button.append(marker, label);
			button.dataset.wtcbStep = String(index);
			item.append(button);
			nav.append(item);
		}

		panel.className = 'wtcb-classic-panel';
		panel.dataset.wtcbStep = String(index);
		panel.style.setProperty('--wtcb-step-color', step.color || '#2563eb');
		if (isMultiStep) {
			heading.textContent = step.title || ('Step ' + (index + 1));
			description.textContent = step.description || '';
			panel.append(heading, description);
		}

		(step.fields || []).forEach(function (field) {
			if (field.enabled === false) {
				return;
			}

			var element = fieldElement(field);

			if (!element || element.dataset.wtcbMounted === 'true') {
				return;
			}

			element.classList.remove('form-row-first', 'form-row-last', 'form-row-wide', 'wtcb-width-1', 'wtcb-width-2', 'wtcb-width-3');
			element.classList.add('wtcb-classic-field', 'wtcb-width-' + (field.width || 2));
			element.dataset.wtcbMounted = 'true';
			panel.append(element);
		});

		panels.append(panel);
	});

	state.active = Math.max(0, Math.min(state.active, panels.children.length - 1));

	var actions = document.createElement('div');
	var prev = document.createElement('button');
	var next = document.createElement('button');

	if (isMultiStep) {
		actions.className = 'wtcb-classic-actions';
		prev.type = 'button';
		next.type = 'button';
		prev.className = 'wtcb-prev-button';
		next.className = 'wtcb-next-button';
		prev.textContent = workflow.previousText || 'Previous';
		next.textContent = workflow.continueText || workflow.nextText || 'Continue';
		actions.append(prev, next);
		host.append(actions);
	}

	function render() {
		if (!isMultiStep) {
			Array.prototype.forEach.call(panels.children, function (panel) {
				panel.hidden = false;
			});
			return;
		}
		Array.prototype.forEach.call(nav.querySelectorAll('button'), function (button, index) {
			var marker = button.querySelector('.wtcb-step-marker');
			var item = button.closest('li');
			var completed = state.completed.indexOf(index) >= 0;

			button.setAttribute('aria-current', index === state.active ? 'step' : 'false');
			button.disabled = navigation === 'buttons' && index > state.active && !completed;
			if (item) {
				item.classList.toggle('is-active', index === state.active);
				item.classList.toggle('is-completed', completed && index !== state.active);
			}
			if (marker) {
				if (indicator === 'icon') {
					marker.textContent = completed && index !== state.active ? '✓' : (iconMap[workflow.stepIcon || '1'] || '1');
				} else {
					marker.textContent = String(index + 1);
				}
			}
		});
		Array.prototype.forEach.call(panels.children, function (panel, index) {
			panel.hidden = index !== state.active;
		});
		prev.hidden = state.active === 0;
		next.hidden = state.active >= panels.children.length - 1;
		next.textContent = state.active >= panels.children.length - 2 ? (workflow.continueText || 'Continue') : (workflow.nextText || 'Next');
		next.classList.toggle('is-continue', state.active >= panels.children.length - 2);
	}

	function currentPanelIsValid() {
		var panel = panels.children[state.active];
		var invalid = panel ? panel.querySelector(':invalid') : null;

		if (!workflow.validateBeforeNext || !invalid) {
			return true;
		}

		if (typeof invalid.reportValidity === 'function') {
			invalid.reportValidity();
		} else {
			invalid.focus();
		}

		return false;
	}

	function go(nextIndex) {
		if (nextIndex > state.active && !currentPanelIsValid()) {
			return;
		}
		if (nextIndex > state.active && state.completed.indexOf(state.active) < 0) {
			state.completed.push(state.active);
		}
		state.active = Math.max(0, Math.min(nextIndex, panels.children.length - 1));
		if (workflow.rememberStep && window.localStorage) {
			window.localStorage.setItem(storageKey, String(state.active));
		}
		render();
		if (workflow.scrollOnStepChange) {
			host.scrollIntoView({ block: 'start', behavior: 'smooth' });
		}
	}

	nav.addEventListener('click', function (event) {
		var button = event.target.closest('button[data-wtcb-step]');
		if (button) {
			var nextIndex = Number(button.dataset.wtcbStep);
			if (nextIndex <= state.active || workflow.allowCompletedStepNavigation !== false && state.completed.indexOf(nextIndex) >= 0) {
				go(nextIndex);
			}
		}
	});
	prev.addEventListener('click', function () { go(state.active - 1); });
	next.addEventListener('click', function () { go(state.active + 1); });

	form.dataset.wtcbClassicMounted = 'true';
	cleanupEmptyNativeSections();
	render();
}

ready(mountCheckoutSteps);
document.body.addEventListener('updated_checkout', mountCheckoutSteps);
})();
