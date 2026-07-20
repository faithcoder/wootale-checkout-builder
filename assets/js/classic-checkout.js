(function () {
function ready(callback) {
	if (document.readyState !== 'loading') {
		callback();
		return;
	}
	document.addEventListener('DOMContentLoaded', callback);
}

function fieldElements(field) {
	if (field.type === 'component') {
		if (field.key === 'order_payment') {
			var heading = document.getElementById('order_review_heading');
			var review = document.querySelector('#order_review');
			var payment = document.querySelector('#payment');
			var elements = [];

			if (heading) {
				heading.style.display = 'none';
			}
			if (review) {
				elements.push(review);
			}
			if (payment && (!review || !review.contains(payment))) {
				elements.push(payment);
			}

			return elements;
		}
		if (field.key === 'order_review') {
			var orderHeading = document.getElementById('order_review_heading');
			if (orderHeading) {
				orderHeading.style.display = 'none';
			}
			return [document.querySelector('#order_review')];
		}
		if (field.key === 'payment_methods') {
			return [document.querySelector('#payment .wc_payment_methods')];
		}
		if (field.key === 'terms') {
			return [document.querySelector('#payment .woocommerce-terms-and-conditions-wrapper')];
		}
		if (field.key === 'place_order') {
			return [document.querySelector('#payment .place-order')];
		}
		return [];
	}

	return [document.getElementById(field.key + '_field')];
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

function defaultStepStyle(color) {
	return {
		titleColor: '#111827',
		backgroundColor: '#ffffff',
		borderStyle: 'solid',
		borderWidth: 0,
		borderRadius: 0,
		borderColor: color || '#2563eb',
		padding: 0,
		margin: 14
	};
}

function applyStepPanelStyle(panel, step) {
	var style = Object.assign(defaultStepStyle(step.color || '#2563eb'), step.style || {});

	panel.style.setProperty('--checkoutly-step-title-color', style.titleColor);
	panel.style.setProperty('--checkoutly-step-bg', style.backgroundColor);
	panel.style.setProperty('--checkoutly-step-border-style', style.borderStyle);
	panel.style.setProperty('--checkoutly-step-border-width', Number(style.borderWidth) + 'px');
	panel.style.setProperty('--checkoutly-step-radius', Number(style.borderRadius) + 'px');
	panel.style.setProperty('--checkoutly-step-border-color', style.borderColor);
	panel.style.setProperty('--checkoutly-step-padding', Number(style.padding) + 'px');
	panel.style.setProperty('--checkoutly-step-margin', Number(style.margin) + 'px');
}

function mountCheckoutSteps() {
	var workflow = window.checkoutlyClassicWorkflow || {};
	var form = document.querySelector('form.checkout');

	if (!form || !workflow.steps || form.dataset.checkoutlyClassicMounted === 'true') {
		return;
	}

	var host = document.createElement('div');
	var nav = document.createElement('ol');
	var panels = document.createElement('div');
	var storageKey = 'checkoutly_active_step';
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

	host.className = 'checkoutly-classic-checkout checkoutly-orientation-' + (workflow.orientation || 'horizontal') + ' checkoutly-indicator-' + indicator + ' checkoutly-connector-' + (workflow.connector || 'solid') + ' checkoutly-navigation-' + navigation + (isMultiStep ? '' : ' is-single-step');
	host.style.setProperty('--checkoutly-active-color', workflow.activeColor || '#2563eb');
	host.style.setProperty('--checkoutly-completed-color', workflow.completedColor || '#16a34a');
	host.style.setProperty('--checkoutly-inactive-color', workflow.inactiveColor || '#6b7280');
	host.style.setProperty('--checkoutly-connector-thickness', (workflow.connectorThickness || 2) + 'px');
	host.style.setProperty('--checkoutly-connector-gap', (workflow.connectorGap || 24) + 'px');
	host.style.setProperty('--checkoutly-previous-button-color', workflow.previousButtonColor || '#1f2937');
	host.style.setProperty('--checkoutly-previous-button-bg', workflow.previousButtonBackground || '#f5f6f7');
	host.style.setProperty('--checkoutly-next-button-color', workflow.nextButtonColor || '#ffffff');
	host.style.setProperty('--checkoutly-next-button-bg', workflow.nextButtonBackground || '#2563eb');
	host.style.setProperty('--checkoutly-continue-button-color', workflow.continueButtonColor || '#ffffff');
	host.style.setProperty('--checkoutly-continue-button-bg', workflow.continueButtonBackground || '#16a34a');
	nav.className = 'checkoutly-classic-steps';
	panels.className = 'checkoutly-classic-panels';
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
			marker.className = 'checkoutly-step-marker';
			label.className = 'checkoutly-step-label';
			label.textContent = step.title || ('Step ' + (index + 1));
			button.append(marker, label);
			button.dataset.checkoutlyStep = String(index);
			item.append(button);
			nav.append(item);
		}

		panel.className = 'checkoutly-classic-panel';
		panel.dataset.checkoutlyStep = String(index);
		panel.style.setProperty('--checkoutly-step-color', step.color || '#2563eb');
		applyStepPanelStyle(panel, step);
		if (isMultiStep) {
			heading.textContent = step.title || ('Step ' + (index + 1));
			description.textContent = step.description || '';
			panel.append(heading, description);
		}

		(step.fields || []).forEach(function (field) {
			if (field.enabled === false) {
				return;
			}

			var elements = fieldElements(field);

			if (!elements.length) {
				return;
			}

			elements.forEach(function (element) {
				if (!element || element.dataset.checkoutlyMounted === 'true') {
					return;
				}

				element.classList.remove('form-row-first', 'form-row-last', 'form-row-wide', 'checkoutly-width-1', 'checkoutly-width-2', 'checkoutly-width-3');
				element.classList.add('checkoutly-classic-field', 'checkoutly-width-' + (field.width || 2));
				element.dataset.checkoutlyMounted = 'true';
				panel.append(element);
			});
		});

		panels.append(panel);
	});

	state.active = Math.max(0, Math.min(state.active, panels.children.length - 1));

	var actions = document.createElement('div');
	var prev = document.createElement('button');
	var next = document.createElement('button');

	if (isMultiStep) {
		actions.className = 'checkoutly-classic-actions';
		prev.type = 'button';
		next.type = 'button';
		prev.className = 'checkoutly-prev-button';
		next.className = 'checkoutly-next-button';
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
			var marker = button.querySelector('.checkoutly-step-marker');
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
		var button = event.target.closest('button[data-checkoutly-step]');
		if (button) {
			var nextIndex = Number(button.dataset.checkoutlyStep);
			if (nextIndex <= state.active || workflow.allowCompletedStepNavigation !== false && state.completed.indexOf(nextIndex) >= 0) {
				go(nextIndex);
			}
		}
	});
	prev.addEventListener('click', function () { go(state.active - 1); });
	next.addEventListener('click', function () { go(state.active + 1); });

	form.dataset.checkoutlyClassicMounted = 'true';
	cleanupEmptyNativeSections();
	render();
}

ready(mountCheckoutSteps);
document.body.addEventListener('updated_checkout', mountCheckoutSteps);
})();
