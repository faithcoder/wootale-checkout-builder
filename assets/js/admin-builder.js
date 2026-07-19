(function(){
var dragged = null;
var paletteField = null;
var activeCard = null;
var form = document.getElementById('wtcb-builder-form');
var steps = document.getElementById('wtcb-steps');
var input = document.getElementById('wtcb-workflow-input');
var builderView = document.getElementById('wtcb-builder-view');
var globalSettings = document.getElementById('wtcb-global-settings');
var multiStepEnabled = document.getElementById('wtcb-multistep-enabled');
var multiStepModal = document.getElementById('wtcb-multistep-modal');
var openMultiStepSettings = document.getElementById('wtcb-open-multistep-settings');
var multiStepControls = document.getElementById('wtcb-multistep-controls');
var canvasCount = document.querySelector('.wtcb-canvas-head h2 span');
var stepCount = document.getElementById('wtcb-step-count');
var indicatorSelect = document.getElementById('wtcb-indicator-select');
var connector = document.getElementById('wtcb-connector');
var connectorThickness = document.getElementById('wtcb-connector-thickness');
var connectorGap = document.getElementById('wtcb-connector-gap');
var activeColor = document.getElementById('wtcb-active-color');
var completedColor = document.getElementById('wtcb-completed-color');
var inactiveColor = document.getElementById('wtcb-inactive-color');
var previousText = document.getElementById('wtcb-previous-text');
var nextText = document.getElementById('wtcb-next-text');
var continueText = document.getElementById('wtcb-continue-text');
var previousButtonColor = document.getElementById('wtcb-previous-button-color');
var previousButtonBg = document.getElementById('wtcb-previous-button-bg');
var nextButtonColor = document.getElementById('wtcb-next-button-color');
var nextButtonBg = document.getElementById('wtcb-next-button-bg');
var continueButtonColor = document.getElementById('wtcb-continue-button-color');
var continueButtonBg = document.getElementById('wtcb-continue-button-bg');
var allowCompleted = document.getElementById('wtcb-allow-completed');
var scrollOnChange = document.getElementById('wtcb-scroll-on-change');
var validateBeforeNext = document.getElementById('wtcb-validate-before-next');
var rememberStep = document.getElementById('wtcb-remember-step');
var modal = document.getElementById('wtcb-field-modal');
var modalTitle = document.getElementById('wtcb-field-modal-title');
var modalFieldType = document.getElementById('wtcb-modal-field-type');
var modalSection = document.getElementById('wtcb-modal-section');
var modalLabel = document.getElementById('wtcb-modal-label');
var modalKey = document.getElementById('wtcb-modal-key');
var modalDefault = document.getElementById('wtcb-modal-default');
var modalPlaceholder = document.getElementById('wtcb-modal-placeholder');
var modalOptions = document.getElementById('wtcb-modal-options');
var modalValidation = document.getElementById('wtcb-modal-validation');
var modalWidth = document.getElementById('wtcb-modal-width');
var modalRequired = document.getElementById('wtcb-modal-required');
var modalEnabled = document.getElementById('wtcb-modal-enabled');
var modalDisplayOrder = document.getElementById('wtcb-modal-display-order');
var modalDisplayEmails = document.getElementById('wtcb-modal-display-emails');
var modalDisplayThankYou = document.getElementById('wtcb-modal-display-thank-you');
var modalLockNote = document.getElementById('wtcb-native-lock-note');
var saveButton = document.querySelector('.wtcb-actions .button-primary');

function parseField(card){ try { return JSON.parse(card.dataset.field || '{}'); } catch(e){ return {}; } }
function fieldWidth(field){ return [1,2].indexOf(Number(field.width)) >= 0 ? Number(field.width) : 2; }
function displayOptions(field){
	var display = field.display || {};
	return {
		orderDetails: display.orderDetails !== false,
		emails: display.emails !== false,
		thankYou: display.thankYou !== false
	};
}
function setField(card, field){
	var width = fieldWidth(field);
	field.display = displayOptions(field);
	card.dataset.field = JSON.stringify(field);
	card.classList.toggle('is-disabled', field.enabled === false);
	card.classList.remove('wtcb-width-1', 'wtcb-width-2', 'wtcb-width-3');
	card.classList.add('wtcb-width-' + width);
	card.querySelector('strong').textContent = field.label || field.key || 'Field';
}
function fieldCard(field){
	var card = document.createElement('div');
	field.width = fieldWidth(field);
	card.className = 'wtcb-field-card wtcb-width-' + field.width;
	card.draggable = true;
	card.innerHTML = '<span class="wtcb-field-handle" title="Drag field">▦</span><button type="button" class="wtcb-field-settings-button" data-open-field-settings title="Field settings">⚙</button><strong></strong><span class="wtcb-field-actions"><button type="button" class="wtcb-icon-button" data-duplicate-field title="Duplicate field">⧉</button><button type="button" class="wtcb-icon-button wtcb-danger" data-remove-field title="Remove field">×</button></span>';
	card.querySelector('[data-duplicate-field]').disabled = field.type !== 'custom';
	setField(card, field);
	return card;
}
function customField(type, label) {
	var key = 'wtcb_' + type + '_' + Date.now();
	return { id:key, key:key, section:'order', type:'custom', fieldType:type, label:label, required:false, enabled:true, width:2, default:'', placeholder:'', options:'', validation:[], display:{ orderDetails:true, emails:true, thankYou:true } };
}
function nativeField(section, key, label, required) {
	return { id:key, key:key, section:section, type:'native', fieldType:'text', label:label, required:required, enabled:true, width:2, default:'', placeholder:'', options:'', validation:[], display:{ orderDetails:true, emails:true, thankYou:true } };
}
function componentField(key, label) {
	return { id:'component_' + key, key:key, section:'component', type:'component', fieldType:'component', label:label, required:false, enabled:true, width:2, default:'', placeholder:'', options:'', validation:[], display:{ orderDetails:true, emails:true, thankYou:true } };
}
function settingValue(name, fallback) {
	var active = document.querySelector('[data-setting-segment="' + name + '"] .is-active');
	return active ? active.dataset.value : fallback;
}
function refreshNavigationControls(){
	var navigation = settingValue('navigation', 'line');
	Array.prototype.forEach.call(document.querySelectorAll('.wtcb-line-setting'), function(control){
		control.hidden = navigation !== 'line';
	});
}
function refreshMultiStepControls(){
	if (openMultiStepSettings && multiStepEnabled) {
		openMultiStepSettings.hidden = !multiStepEnabled.checked;
	}
}
function refreshRangeOutputs(){
	var thicknessOutput = document.getElementById('wtcb-connector-thickness-output');
	var gapOutput = document.getElementById('wtcb-connector-gap-output');

	if (thicknessOutput && connectorThickness) {
		thicknessOutput.textContent = connectorThickness.value + 'px';
	}
	if (gapOutput && connectorGap) {
		gapOutput.textContent = connectorGap.value + 'px';
	}
}
function renumberSteps(){
	steps.querySelectorAll('.wtcb-step').forEach(function(step, index){
		step.dataset.stepIndex = String(index);
		var badge = step.querySelector('.wtcb-badge');
		if (badge) {
			badge.textContent = String(index + 1);
		}
	});
	if (stepCount) {
		stepCount.value = String(steps.querySelectorAll('.wtcb-step').length);
	}
	if (canvasCount) {
		canvasCount.textContent = steps.querySelectorAll('.wtcb-step').length + ' / 3 Free';
	}
}
function stepCard(index){
	var colors = ['#2563eb', '#16a34a', '#7c3aed'];
	var step = document.createElement('section');
	step.className = 'wtcb-step';
	step.dataset.stepIndex = String(index);
	step.style.setProperty('--step-color', colors[index] || '#2563eb');
	step.innerHTML = '<div class="wtcb-step-head"><span class="wtcb-drag">::</span><span class="wtcb-badge">' + (index + 1) + '</span><div><input class="wtcb-step-title" value="Step ' + (index + 1) + '" /><input class="wtcb-step-description" value="" /></div><button type="button" class="wtcb-collapse">⌃</button></div><div class="wtcb-field-list" data-step-fields></div>';
	return step;
}
function setStepCount(nextCount){
	var current = steps.querySelectorAll('.wtcb-step').length;
	nextCount = Math.max(1, Math.min(3, Number(nextCount) || current));

	while (current < nextCount) {
		steps.appendChild(stepCard(current));
		current++;
	}

	while (current > nextCount) {
		steps.lastElementChild.remove();
		current--;
	}

	renumberSteps();
	serialize();
}
function fieldDropTarget(list, y){
	var cards = Array.prototype.slice.call(list.querySelectorAll('.wtcb-field-card:not(.is-dragging)'));
	return cards.reduce(function(closest, child){
		var box = child.getBoundingClientRect();
		var offset = y - box.top - box.height / 2;
		if (offset < 0 && offset > closest.offset) {
			return { offset: offset, element: child };
		}
		return closest;
	}, { offset: Number.NEGATIVE_INFINITY, element: null }).element;
}
function serialize(){
	var workflow = {
		version: 1,
		multiStepEnabled: multiStepEnabled ? multiStepEnabled.checked : true,
		orientation: settingValue('orientation', 'horizontal'),
		indicator: indicatorSelect ? indicatorSelect.value : 'number',
		stepIcon: settingValue('stepIcon', '1'),
		connector: connector ? connector.value : 'solid',
		navigation: settingValue('navigation', 'line'),
		activeColor: activeColor ? activeColor.value : '#2563eb',
		completedColor: completedColor ? completedColor.value : '#16a34a',
		inactiveColor: inactiveColor ? inactiveColor.value : '#6b7280',
		connectorThickness: connectorThickness ? Number(connectorThickness.value) || 2 : 2,
		connectorGap: connectorGap ? Number(connectorGap.value) || 24 : 24,
		previousText: previousText ? previousText.value.trim() || 'Previous' : 'Previous',
		nextText: nextText ? nextText.value.trim() || 'Next' : 'Next',
		continueText: continueText ? continueText.value.trim() || 'Continue' : 'Continue',
		previousButtonColor: previousButtonColor ? previousButtonColor.value : '#1f2937',
		previousButtonBackground: previousButtonBg ? previousButtonBg.value : '#f5f6f7',
		nextButtonColor: nextButtonColor ? nextButtonColor.value : '#ffffff',
		nextButtonBackground: nextButtonBg ? nextButtonBg.value : '#2563eb',
		continueButtonColor: continueButtonColor ? continueButtonColor.value : '#ffffff',
		continueButtonBackground: continueButtonBg ? continueButtonBg.value : '#16a34a',
		allowCompletedStepNavigation: allowCompleted ? allowCompleted.checked : true,
		scrollOnStepChange: scrollOnChange ? scrollOnChange.checked : true,
		validateBeforeNext: validateBeforeNext ? validateBeforeNext.checked : true,
		rememberStep: rememberStep ? rememberStep.checked : false,
		steps: []
	};
	steps.querySelectorAll('.wtcb-step').forEach(function(step, index){
		var fields = [];
		step.querySelectorAll('.wtcb-field-card').forEach(function(card){ fields.push(parseField(card)); });
		workflow.steps.push({
			id: 'wtcb_step_' + (index + 1),
			title: step.querySelector('.wtcb-step-title').value || ('Step ' + (index + 1)),
			description: step.querySelector('.wtcb-step-description').value || '',
			color: getComputedStyle(step).getPropertyValue('--step-color').trim() || '#2563eb',
			fields: fields
		});
	});
	input.value = JSON.stringify(workflow);
	renumberSteps();
}
function showSaveNotice(type, message) {
	var existing = document.querySelector('.wtcb-save-notice');
	var topbar = document.querySelector('.wtcb-topbar');
	var notice = existing || document.createElement('div');
	var paragraph = document.createElement('p');

	notice.className = 'notice wtcb-save-notice ' + (type === 'success' ? 'notice-success' : 'notice-error');
	paragraph.textContent = message;
	notice.replaceChildren(paragraph);

	if (!existing && topbar) {
		topbar.insertAdjacentElement('afterend', notice);
	}
}

document.addEventListener('dragstart', function(event){
	var card = event.target.closest('.wtcb-field-card');
	var component = event.target.closest('[data-add-field]');
	var wooField = event.target.closest('[data-woo-field]');
	var wooComponent = event.target.closest('[data-woo-component]');

	if (component) {
		paletteField = customField(component.dataset.addField, component.textContent.trim());
		event.dataTransfer.effectAllowed = 'copy';
		return;
	}

	if (wooField) {
		paletteField = nativeField(wooField.dataset.section, wooField.dataset.wooField, wooField.textContent.trim(), wooField.dataset.required === '1');
		event.dataTransfer.effectAllowed = 'copy';
		return;
	}

	if (wooComponent) {
		paletteField = componentField(wooComponent.dataset.wooComponent, wooComponent.textContent.trim());
		event.dataTransfer.effectAllowed = 'copy';
		return;
	}

	if (card) {
		dragged = card;
		card.classList.add('is-dragging');
		event.dataTransfer.effectAllowed = 'move';
	}
});
document.addEventListener('dragend', function(){
	if (dragged) {
		dragged.classList.remove('is-dragging');
	}
	dragged = null;
	paletteField = null;
});
document.addEventListener('dragover', function(event){
	var list = event.target.closest('[data-step-fields]');
	if (list) {
		event.preventDefault();
		var before = fieldDropTarget(list, event.clientY);
		if (dragged && before !== dragged) {
			list.insertBefore(dragged, before);
		}
	}
});
document.addEventListener('drop', function(event){
	var list = event.target.closest('[data-step-fields]');
	if (!list) {
		return;
	}

	if (paletteField) {
		event.preventDefault();
		var before = fieldDropTarget(list, event.clientY);
		list.insertBefore(fieldCard(paletteField), before);
		paletteField = null;
		serialize();
		return;
	}

	if (dragged) {
		event.preventDefault();
		dragged.classList.remove('is-dragging');
		dragged = null;
		serialize();
	}
});
document.addEventListener('click', function(event){
	var card = event.target.closest('.wtcb-field-card');
	var segmentButton = event.target.closest('[data-setting-segment] button[data-value]');
	var topTab = event.target.closest('[data-wtcb-tab]');

	if (topTab) {
		event.preventDefault();
		Array.prototype.forEach.call(document.querySelectorAll('[data-wtcb-tab]'), function(tab){
			tab.classList.toggle('is-active', tab === topTab);
		});
		if (builderView) {
			builderView.hidden = topTab.dataset.wtcbTab !== 'builder';
		}
		if (globalSettings) {
			globalSettings.hidden = topTab.dataset.wtcbTab !== 'settings';
		}
	}
	if (segmentButton) {
		Array.prototype.forEach.call(segmentButton.parentNode.children, function(button){
			button.classList.toggle('is-active', button === segmentButton);
		});
		refreshNavigationControls();
		serialize();
	}
	if (event.target.matches('[data-step-count-decrease]')) {
		setStepCount(steps.querySelectorAll('.wtcb-step').length - 1);
	}
	if (event.target.matches('[data-step-count-increase], #wtcb-add-step')) {
		setStepCount(steps.querySelectorAll('.wtcb-step').length + 1);
	}
	if (event.target.matches('.wtcb-delete-step')) {
		setStepCount(steps.querySelectorAll('.wtcb-step').length - 1);
	}
	if (event.target.matches('[data-open-field-settings]') && card) {
		var field = parseField(card);
		var display = displayOptions(field);
		activeCard = card;
		modalTitle.textContent = 'Edit: ' + (field.label || field.key || 'Field');
		modalFieldType.value = field.fieldType || 'text';
		modalFieldType.disabled = field.type !== 'custom';
		modalSection.value = field.section || 'order';
		modalSection.disabled = field.type !== 'custom';
		modalLabel.value = field.label || '';
		modalKey.value = field.key || '';
		modalKey.disabled = field.type !== 'custom';
		modalDefault.value = field.default || '';
		modalPlaceholder.value = field.placeholder || '';
		modalOptions.value = field.options || '';
		Array.prototype.forEach.call(modalValidation.options, function(option){
			option.selected = (field.validation || []).indexOf(option.value) >= 0;
		});
		modalWidth.value = String(fieldWidth(field));
		modalRequired.checked = !!field.required;
		modalEnabled.checked = field.enabled !== false;
		modalDisplayOrder.checked = display.orderDetails;
		modalDisplayEmails.checked = display.emails;
		modalDisplayThankYou.checked = display.thankYou;
		modalLockNote.hidden = field.type === 'custom';
		modal.hidden = false;
	}
	if (event.target.matches('[data-close-field-settings]')) {
		modal.hidden = true;
		activeCard = null;
	}
	if (event.target.matches('#wtcb-open-multistep-settings')) {
		multiStepModal.hidden = false;
	}
	if (event.target.matches('[data-close-multistep-settings]')) {
		multiStepModal.hidden = true;
	}
	if (event.target.matches('#wtcb-apply-field-settings') && activeCard) {
		var next = parseField(activeCard);
		next.label = modalLabel.value.trim() || next.label || next.key;
		if (next.type === 'custom') {
			next.fieldType = modalFieldType.value || 'text';
			next.section = modalSection.value || 'order';
			next.key = modalKey.value.trim().toLowerCase().replace(/[^a-z0-9_]/g, '') || next.key;
		}
		next.default = modalDefault.value.trim();
		next.placeholder = modalPlaceholder.value.trim();
		next.options = modalOptions.value.trim();
		next.validation = Array.prototype.filter.call(modalValidation.options, function(option){ return option.selected; }).map(function(option){ return option.value; });
		next.required = modalRequired.checked;
		next.enabled = modalEnabled.checked;
		next.width = Number(modalWidth.value) || 2;
		next.display = {
			orderDetails: modalDisplayOrder.checked,
			emails: modalDisplayEmails.checked,
			thankYou: modalDisplayThankYou.checked
		};
		setField(activeCard, next);
		serialize();
		modal.hidden = true;
		activeCard = null;
	}
	if (event.target.matches('[data-duplicate-field]') && card && !event.target.disabled) {
		var field = parseField(card);
		if (field.type === 'custom') {
			var copy = JSON.parse(JSON.stringify(field));
			copy.key = 'wtcb_' + copy.fieldType + '_' + Date.now();
			copy.id = copy.key;
			copy.label = (copy.label || 'Field') + ' copy';
			card.parentNode.insertBefore(fieldCard(copy), card.nextSibling);
			serialize();
		}
	}
	if (event.target.matches('[data-remove-field]') && card && !event.target.disabled) {
		card.remove();
		serialize();
	}
	if (event.target.matches('[data-add-field]')) {
		var type = event.target.dataset.addField;
		var list = steps.querySelector('[data-step-fields]');
		list.appendChild(fieldCard(customField(type, event.target.textContent.trim())));
		serialize();
	}
	if (event.target.matches('[data-woo-field]')) {
		var wooList = steps.querySelector('[data-step-fields]');
		wooList.appendChild(fieldCard(nativeField(event.target.dataset.section, event.target.dataset.wooField, event.target.textContent.trim(), event.target.dataset.required === '1')));
		serialize();
	}
	if (event.target.matches('[data-woo-component]')) {
		var componentKey = event.target.dataset.wooComponent;
		var componentList = steps.querySelector('[data-step-fields]');
		componentList.appendChild(fieldCard(componentField(componentKey, event.target.textContent.trim())));
		serialize();
	}
});
if (stepCount) {
	stepCount.addEventListener('change', function(){ setStepCount(stepCount.value); });
}
if (multiStepEnabled) {
	multiStepEnabled.addEventListener('change', function(){
		refreshMultiStepControls();
		if (multiStepEnabled.checked && multiStepModal) {
			multiStepModal.hidden = false;
		}
		serialize();
	});
}
if (connector) {
	connector.addEventListener('change', serialize);
}
if (activeColor) {
	activeColor.addEventListener('input', serialize);
}
if (completedColor) {
	completedColor.addEventListener('input', serialize);
}
if (multiStepControls) {
	multiStepControls.addEventListener('change', function(){
		refreshNavigationControls();
		refreshRangeOutputs();
		serialize();
	});
}
[connectorThickness, connectorGap].forEach(function(range){
	if (range) {
		range.addEventListener('input', function(){
			refreshRangeOutputs();
			serialize();
		});
	}
});
if (form) {
	form.addEventListener('submit', function(event){
		var formData;
		var originalText;

		event.preventDefault();
		serialize();

		formData = new FormData(form);
		formData.set('action', 'wtcb_save_builder');
		originalText = saveButton ? saveButton.textContent : '';

		if (saveButton) {
			saveButton.disabled = true;
			saveButton.textContent = 'Saving...';
		}

		fetch(window.ajaxurl || '/wp-admin/admin-ajax.php', {
			body: formData,
			credentials: 'same-origin',
			method: 'POST'
		}).then(function(response){
			return response.json();
		}).then(function(payload){
			if (!payload || !payload.success) {
				throw new Error(payload && payload.data && payload.data.message ? payload.data.message : 'Unable to save checkout builder.');
			}
			showSaveNotice('success', payload.data && payload.data.message ? payload.data.message : 'WooTale checkout builder saved.');
		}).catch(function(error){
			showSaveNotice('error', error.message || 'Unable to save checkout builder.');
		}).finally(function(){
			if (saveButton) {
				saveButton.disabled = false;
				saveButton.textContent = originalText || 'Save Changes';
			}
		});
	});
}
modal.addEventListener('click', function(event){
	if (event.target === modal) {
		modal.hidden = true;
		activeCard = null;
	}
});
multiStepModal.addEventListener('click', function(event){
	if (event.target === multiStepModal) {
		multiStepModal.hidden = true;
	}
});
document.addEventListener('input', serialize);
refreshMultiStepControls();
refreshNavigationControls();
refreshRangeOutputs();
serialize();
})();
