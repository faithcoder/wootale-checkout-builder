(function(){
var dragged = null;
var draggedStep = null;
var dragIntent = null;
var paletteField = null;
var activeCard = null;
var form = document.getElementById('checkoutly-builder-form');
var steps = document.getElementById('checkoutly-steps');
var input = document.getElementById('checkoutly-workflow-input');
var builderView = document.getElementById('checkoutly-builder-view');
var globalSettings = document.getElementById('checkoutly-global-settings');
var multiStepEnabled = document.getElementById('checkoutly-multistep-enabled');
var multiStepModal = document.getElementById('checkoutly-multistep-modal');
var openMultiStepSettings = document.getElementById('checkoutly-open-multistep-settings');
var multiStepControls = document.getElementById('checkoutly-multistep-controls');
var canvasCount = document.querySelector('.checkoutly-canvas-head h2 span');
var stepCount = document.getElementById('checkoutly-step-count');
var indicatorSelect = document.getElementById('checkoutly-indicator-select');
var connector = document.getElementById('checkoutly-connector');
var connectorThickness = document.getElementById('checkoutly-connector-thickness');
var connectorGap = document.getElementById('checkoutly-connector-gap');
var activeColor = document.getElementById('checkoutly-active-color');
var completedColor = document.getElementById('checkoutly-completed-color');
var inactiveColor = document.getElementById('checkoutly-inactive-color');
var previousText = document.getElementById('checkoutly-previous-text');
var nextText = document.getElementById('checkoutly-next-text');
var continueText = document.getElementById('checkoutly-continue-text');
var previousButtonColor = document.getElementById('checkoutly-previous-button-color');
var previousButtonBg = document.getElementById('checkoutly-previous-button-bg');
var nextButtonColor = document.getElementById('checkoutly-next-button-color');
var nextButtonBg = document.getElementById('checkoutly-next-button-bg');
var continueButtonColor = document.getElementById('checkoutly-continue-button-color');
var continueButtonBg = document.getElementById('checkoutly-continue-button-bg');
var allowCompleted = document.getElementById('checkoutly-allow-completed');
var scrollOnChange = document.getElementById('checkoutly-scroll-on-change');
var validateBeforeNext = document.getElementById('checkoutly-validate-before-next');
var rememberStep = document.getElementById('checkoutly-remember-step');
var modal = document.getElementById('checkoutly-field-modal');
var modalTitle = document.getElementById('checkoutly-field-modal-title');
var modalFieldType = document.getElementById('checkoutly-modal-field-type');
var modalStep = document.getElementById('checkoutly-modal-step');
var modalLabel = document.getElementById('checkoutly-modal-label');
var modalKey = document.getElementById('checkoutly-modal-key');
var modalDefault = document.getElementById('checkoutly-modal-default');
var modalPlaceholder = document.getElementById('checkoutly-modal-placeholder');
var modalOptions = document.getElementById('checkoutly-modal-options');
var modalOptionEditor = document.querySelector('.checkoutly-option-editor');
var modalValidation = document.getElementById('checkoutly-modal-validation');
var modalWidth = document.getElementById('checkoutly-modal-width');
var modalRequired = document.getElementById('checkoutly-modal-required');
var modalEnabled = document.getElementById('checkoutly-modal-enabled');
var modalDisplayOrder = document.getElementById('checkoutly-modal-display-order');
var modalDisplayEmails = document.getElementById('checkoutly-modal-display-emails');
var modalDisplayThankYou = document.getElementById('checkoutly-modal-display-thank-you');
var modalLockNote = document.getElementById('checkoutly-native-lock-note');
var saveButton = document.querySelector('.checkoutly-actions .button-primary');
var bulkActions = document.querySelector('.checkoutly-bulk-actions');
var stepSettingsSelect = document.getElementById('checkoutly-step-settings-select');
var stepTitleColor = document.getElementById('checkoutly-step-title-color');
var stepBackgroundColor = document.getElementById('checkoutly-step-background-color');
var stepBorderStyle = document.getElementById('checkoutly-step-border-style');
var stepBorderWidth = document.getElementById('checkoutly-step-border-width');
var stepBorderRadius = document.getElementById('checkoutly-step-border-radius');
var stepBorderColor = document.getElementById('checkoutly-step-border-color');
var stepPadding = document.getElementById('checkoutly-step-padding');
var stepMargin = document.getElementById('checkoutly-step-margin');

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
	card.classList.remove('checkoutly-width-1', 'checkoutly-width-2', 'checkoutly-width-3');
	card.classList.add('checkoutly-width-' + width);
	card.querySelector('strong').textContent = field.label || field.key || 'Field';
}
function selectedFieldCards(){
	return Array.prototype.slice.call(document.querySelectorAll('.checkoutly-field-select:checked')).map(function(input){
		return input.closest('.checkoutly-field-card');
	}).filter(Boolean);
}
function refreshBulkActions(){
	var hasSelection = selectedFieldCards().length > 0;

	if (!bulkActions) {
		return;
	}

	Array.prototype.forEach.call(bulkActions.querySelectorAll('button'), function(button){
		button.disabled = !hasSelection;
	});
}
function defaultStepStyle(color){
	return {
		titleColor: '#111827',
		backgroundColor: '#ffffff',
		borderStyle: 'solid',
		borderWidth: 2,
		borderRadius: 10,
		borderColor: color || '#2563eb',
		padding: 14,
		margin: 14
	};
}
function parseStepStyle(step){
	var color = step ? (getComputedStyle(step).getPropertyValue('--step-color').trim() || '#2563eb') : '#2563eb';

	try {
		return Object.assign(defaultStepStyle(color), JSON.parse(step.dataset.stepStyle || '{}'));
	} catch(e) {
		return defaultStepStyle(color);
	}
}
function applyStepStyle(step, style){
	if (!step) {
		return;
	}

	style = Object.assign(defaultStepStyle(getComputedStyle(step).getPropertyValue('--step-color').trim()), style || {});
	step.dataset.stepStyle = JSON.stringify(style);
	step.style.setProperty('--checkoutly-step-title-color', style.titleColor);
	step.style.setProperty('--checkoutly-step-bg', style.backgroundColor);
	step.style.setProperty('--checkoutly-step-border-style', style.borderStyle);
	step.style.setProperty('--checkoutly-step-border-width', Number(style.borderWidth) + 'px');
	step.style.setProperty('--checkoutly-step-radius', Number(style.borderRadius) + 'px');
	step.style.setProperty('--checkoutly-step-border-color', style.borderColor);
	step.style.setProperty('--checkoutly-step-padding', Number(style.padding) + 'px');
	step.style.setProperty('--checkoutly-step-margin', Number(style.margin) + 'px');
}
function selectedSettingsStep(){
	var index = stepSettingsSelect ? Number(stepSettingsSelect.value) || 0 : 0;
	return steps.querySelectorAll('.checkoutly-step')[index] || steps.querySelector('.checkoutly-step');
}
function loadStepSettingsControls(){
	var step = selectedSettingsStep();
	var style = step ? parseStepStyle(step) : defaultStepStyle('#2563eb');

	if (!stepSettingsSelect) {
		return;
	}

	stepTitleColor.value = style.titleColor;
	stepBackgroundColor.value = style.backgroundColor;
	stepBorderStyle.value = style.borderStyle;
	stepBorderWidth.value = String(style.borderWidth);
	stepBorderRadius.value = String(style.borderRadius);
	stepBorderColor.value = style.borderColor;
	stepPadding.value = String(style.padding);
	stepMargin.value = String(style.margin);
}
function updateStepSettingsOptions(){
	var selected = stepSettingsSelect ? stepSettingsSelect.value : '0';

	if (!stepSettingsSelect) {
		return;
	}

	stepSettingsSelect.innerHTML = '';
	steps.querySelectorAll('.checkoutly-step').forEach(function(step, index){
		var option = document.createElement('option');
		var title = step.querySelector('.checkoutly-step-title').value || ('Step ' + (index + 1));
		option.value = String(index);
		option.textContent = title;
		stepSettingsSelect.appendChild(option);
	});
	stepSettingsSelect.value = Number(selected) < steps.querySelectorAll('.checkoutly-step').length ? selected : '0';
	loadStepSettingsControls();
}
function updateModalStepOptions(selectedStep){
	if (!modalStep) {
		return;
	}

	modalStep.innerHTML = '';
	steps.querySelectorAll('.checkoutly-step').forEach(function(step, index){
		var option = document.createElement('option');
		var title = step.querySelector('.checkoutly-step-title').value || ('Step ' + (index + 1));
		option.value = String(index);
		option.textContent = title;
		modalStep.appendChild(option);
	});
	modalStep.value = String(selectedStep || 0);
}
function updateSelectedStepStyle(){
	var step = selectedSettingsStep();

	if (!step) {
		return;
	}

	applyStepStyle(step, {
		titleColor: stepTitleColor.value || '#111827',
		backgroundColor: stepBackgroundColor.value || '#ffffff',
		borderStyle: stepBorderStyle.value || 'solid',
		borderWidth: Number(stepBorderWidth.value) || 0,
		borderRadius: Number(stepBorderRadius.value) || 0,
		borderColor: stepBorderColor.value || '#2563eb',
		padding: Number(stepPadding.value) || 0,
		margin: Number(stepMargin.value) || 0
	});
	serialize();
}
function optionValuesFromText(value){
	return String(value || '').split(/\r?\n/).map(function(option){
		return option.trim();
	}).filter(Boolean);
}
function fieldTypeSupportsOptions(type){
	return ['select', 'radio', 'checkbox'].indexOf(type) >= 0;
}
function refreshOptionEditor(){
	if (!modalOptionEditor || !modalFieldType) {
		return;
	}

	modalOptionEditor.hidden = !fieldTypeSupportsOptions(modalFieldType.value);
}
function optionValuesFromEditor(){
	return Array.prototype.slice.call(modalOptions.querySelectorAll('.checkoutly-option-value')).map(function(input){
		return input.value.trim();
	}).filter(Boolean);
}
function addOptionRow(value){
	var row = document.createElement('div');
	var input = document.createElement('input');
	var edit = document.createElement('button');
	var remove = document.createElement('button');

	row.className = 'checkoutly-option-row';
	input.type = 'text';
	input.className = 'checkoutly-option-value';
	input.value = value || '';
	input.placeholder = 'Option label';
	edit.type = 'button';
	edit.className = 'checkoutly-icon-button';
	edit.dataset.editOption = '1';
	edit.title = 'Edit option';
	edit.textContent = '✎';
	remove.type = 'button';
	remove.className = 'checkoutly-icon-button checkoutly-danger';
	remove.dataset.removeOption = '1';
	remove.title = 'Remove option';
	remove.textContent = '×';
	row.append(input, edit, remove);
	modalOptions.appendChild(row);
}
function renderOptionRows(value){
	modalOptions.innerHTML = '';
	optionValuesFromText(value).forEach(addOptionRow);
}
function removeSelectedStep(){
	var step = selectedSettingsStep();
	var allSteps = steps.querySelectorAll('.checkoutly-step');
	var selectedIndex = stepSettingsSelect ? Number(stepSettingsSelect.value) || 0 : 0;

	if (!step || allSteps.length <= 1) {
		return;
	}

	step.remove();
	renumberSteps();
	if (stepSettingsSelect) {
		stepSettingsSelect.value = String(Math.max(0, Math.min(selectedIndex, allSteps.length - 2)));
	}
	updateStepSettingsOptions();
	serialize();
}
function fieldCard(field){
	var card = document.createElement('div');
	field.width = fieldWidth(field);
	card.className = 'checkoutly-field-card checkoutly-width-' + field.width;
	card.draggable = true;
	card.innerHTML = '<span class="checkoutly-field-handle" title="Drag field" aria-hidden="true"></span><input type="checkbox" class="checkoutly-field-select" aria-label="Select field for bulk actions" /><strong></strong><span class="checkoutly-field-actions"><button type="button" class="checkoutly-field-settings-button" data-open-field-settings title="Field settings">⚙</button><button type="button" class="checkoutly-icon-button" data-duplicate-field title="Duplicate field">⧉</button><button type="button" class="checkoutly-icon-button checkoutly-danger" data-remove-field title="Remove field">×</button></span>';
	card.querySelector('[data-duplicate-field]').disabled = field.type !== 'custom';
	setField(card, field);
	return card;
}
function customField(type, label) {
	var key = 'checkoutly_' + type + '_' + Date.now();
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
	Array.prototype.forEach.call(document.querySelectorAll('.checkoutly-line-setting'), function(control){
		control.hidden = navigation !== 'line';
	});
}
function refreshMultiStepControls(){
	if (openMultiStepSettings && multiStepEnabled) {
		openMultiStepSettings.hidden = !multiStepEnabled.checked;
	}
}
function refreshRangeOutputs(){
	var thicknessOutput = document.getElementById('checkoutly-connector-thickness-output');
	var gapOutput = document.getElementById('checkoutly-connector-gap-output');

	if (thicknessOutput && connectorThickness) {
		thicknessOutput.textContent = connectorThickness.value + 'px';
	}
	if (gapOutput && connectorGap) {
		gapOutput.textContent = connectorGap.value + 'px';
	}
}
function renumberSteps(){
	steps.querySelectorAll('.checkoutly-step').forEach(function(step, index){
		step.dataset.stepIndex = String(index);
		var badge = step.querySelector('.checkoutly-badge');
		if (badge) {
			badge.textContent = String(index + 1);
		}
	});
	if (stepCount) {
		stepCount.value = String(steps.querySelectorAll('.checkoutly-step').length);
	}
	if (canvasCount) {
		canvasCount.textContent = steps.querySelectorAll('.checkoutly-step').length + ' / 3 Free';
	}
}
function stepCard(index){
	var colors = ['#2563eb', '#16a34a', '#7c3aed'];
	var step = document.createElement('section');
	step.className = 'checkoutly-step';
	step.draggable = true;
	step.dataset.stepIndex = String(index);
	step.style.setProperty('--step-color', colors[index] || '#2563eb');
	step.innerHTML = '<div class="checkoutly-step-head"><span class="checkoutly-drag" title="Drag step" aria-hidden="true"></span><span class="checkoutly-badge">' + (index + 1) + '</span><div><input class="checkoutly-step-title" value="Step ' + (index + 1) + '" /><input class="checkoutly-step-description" value="" /></div><button type="button" class="checkoutly-collapse" aria-expanded="true" title="Collapse step"><span aria-hidden="true"></span></button></div><div class="checkoutly-field-list" data-step-fields></div>';
	applyStepStyle(step, defaultStepStyle(colors[index] || '#2563eb'));
	return step;
}
function setStepCount(nextCount){
	var current = steps.querySelectorAll('.checkoutly-step').length;
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
	updateStepSettingsOptions();
	serialize();
}
function fieldDropTarget(list, y){
	var cards = Array.prototype.slice.call(list.querySelectorAll('.checkoutly-field-card:not(.is-dragging)'));
	return cards.reduce(function(closest, child){
		var box = child.getBoundingClientRect();
		var offset = y - box.top - box.height / 2;
		if (offset < 0 && offset > closest.offset) {
			return { offset: offset, element: child };
		}
		return closest;
	}, { offset: Number.NEGATIVE_INFINITY, element: null }).element;
}
function stepDropTarget(y){
	var cards = Array.prototype.slice.call(steps.querySelectorAll('.checkoutly-step:not(.is-step-dragging)'));
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
	steps.querySelectorAll('.checkoutly-step').forEach(function(step, index){
		var fields = [];
		step.querySelectorAll('.checkoutly-field-card').forEach(function(card){ fields.push(parseField(card)); });
		workflow.steps.push({
			id: 'checkoutly_step_' + (index + 1),
			title: step.querySelector('.checkoutly-step-title').value || ('Step ' + (index + 1)),
			description: step.querySelector('.checkoutly-step-description').value || '',
			color: getComputedStyle(step).getPropertyValue('--step-color').trim() || '#2563eb',
			style: parseStepStyle(step),
			fields: fields
		});
	});
	input.value = JSON.stringify(workflow);
	renumberSteps();
}
function showSaveNotice(type, message) {
	var existing = document.querySelector('.checkoutly-save-notice');
	var topbar = document.querySelector('.checkoutly-topbar');
	var notice = existing || document.createElement('div');
	var paragraph = document.createElement('p');

	notice.className = 'notice checkoutly-save-notice ' + (type === 'success' ? 'notice-success' : 'notice-error');
	paragraph.textContent = message;
	notice.replaceChildren(paragraph);

if (!existing && topbar) {
		topbar.insertAdjacentElement('afterend', notice);
	}
}

document.addEventListener('pointerdown', function(event){
	var stepHandle = event.target.closest('.checkoutly-drag');
	var fieldHandle = event.target.closest('.checkoutly-field-handle');

	dragIntent = null;

	if (stepHandle) {
		dragIntent = {
			step: stepHandle.closest('.checkoutly-step')
		};
	}

	if (fieldHandle) {
		dragIntent = {
			field: fieldHandle.closest('.checkoutly-field-card')
		};
	}
});
document.addEventListener('pointerup', function(){
	if (!dragged && !draggedStep) {
		dragIntent = null;
	}
});
document.addEventListener('dragstart', function(event){
	var card = event.target.closest('.checkoutly-field-card');
	var step = event.target.closest('.checkoutly-step');
	var component = event.target.closest('[data-add-field]');
	var wooField = event.target.closest('[data-woo-field]');
	var wooComponent = event.target.closest('[data-woo-component]');

	if (step && dragIntent && dragIntent.step === step) {
		draggedStep = step;
		step.classList.add('is-step-dragging');
		event.dataTransfer.effectAllowed = 'move';
		event.dataTransfer.setData('text/plain', 'checkoutly-step');
		return;
	}

	if (step && !card && (!dragIntent || dragIntent.step !== step)) {
		event.preventDefault();
		return;
	}

	if (component) {
		paletteField = customField(component.dataset.addField, component.textContent.trim());
		event.dataTransfer.effectAllowed = 'copy';
		event.dataTransfer.setData('text/plain', 'checkoutly-palette-field');
		return;
	}

	if (wooField) {
		paletteField = nativeField(wooField.dataset.section, wooField.dataset.wooField, wooField.textContent.trim(), wooField.dataset.required === '1');
		event.dataTransfer.effectAllowed = 'copy';
		event.dataTransfer.setData('text/plain', 'checkoutly-woo-field');
		return;
	}

	if (wooComponent) {
		paletteField = componentField(wooComponent.dataset.wooComponent, wooComponent.textContent.trim());
		event.dataTransfer.effectAllowed = 'copy';
		event.dataTransfer.setData('text/plain', 'checkoutly-woo-component');
		return;
	}

	if (card) {
		if (!dragIntent || dragIntent.field !== card) {
			event.preventDefault();
			return;
		}
		dragged = card;
		card.classList.add('is-dragging');
		event.dataTransfer.effectAllowed = 'move';
		event.dataTransfer.setData('text/plain', 'checkoutly-field');
	}
});
document.addEventListener('dragend', function(){
	if (dragged) {
		dragged.classList.remove('is-dragging');
	}
	if (draggedStep) {
		draggedStep.classList.remove('is-step-dragging');
	}
	dragged = null;
	draggedStep = null;
	dragIntent = null;
	paletteField = null;
});
document.addEventListener('dragover', function(event){
	var list = event.target.closest('[data-step-fields]');
	var stepContainer = event.target.closest('#checkoutly-steps');

	if (draggedStep && stepContainer) {
		event.preventDefault();
		var beforeStep = stepDropTarget(event.clientY);
		if (beforeStep !== draggedStep) {
			steps.insertBefore(draggedStep, beforeStep);
			renumberSteps();
		}
		return;
	}

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
	var stepContainer = event.target.closest('#checkoutly-steps');

	if (draggedStep && stepContainer) {
		event.preventDefault();
		draggedStep.classList.remove('is-step-dragging');
		draggedStep = null;
		renumberSteps();
		updateStepSettingsOptions();
		serialize();
		return;
	}

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
	var card = event.target.closest('.checkoutly-field-card');
	var segmentButton = event.target.closest('[data-setting-segment] button[data-value]');
	var topTab = event.target.closest('[data-checkoutly-tab]');

	if (topTab) {
		event.preventDefault();
		Array.prototype.forEach.call(document.querySelectorAll('[data-checkoutly-tab]'), function(tab){
			tab.classList.toggle('is-active', tab === topTab);
		});
		if (builderView) {
			builderView.hidden = topTab.dataset.checkoutlyTab !== 'builder';
		}
		if (globalSettings) {
			globalSettings.hidden = topTab.dataset.checkoutlyTab !== 'settings';
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
		setStepCount(steps.querySelectorAll('.checkoutly-step').length - 1);
	}
	if (event.target.matches('[data-step-count-increase], #checkoutly-add-step')) {
		setStepCount(steps.querySelectorAll('.checkoutly-step').length + 1);
	}
	if (event.target.matches('.checkoutly-collapse')) {
		var currentStep = event.target.closest('.checkoutly-step');
		var collapsed = currentStep.classList.toggle('is-collapsed');
		event.target.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
		event.target.title = collapsed ? 'Expand step' : 'Collapse step';
	}
	if (event.target.matches('.checkoutly-delete-step')) {
		removeSelectedStep();
	}
	if (event.target.matches('[data-open-field-settings]') && card) {
		var field = parseField(card);
		var display = displayOptions(field);
		var currentStep = card.closest('.checkoutly-step');
		var currentStepIndex = Array.prototype.indexOf.call(steps.querySelectorAll('.checkoutly-step'), currentStep);
		activeCard = card;
		modalTitle.textContent = 'Edit: ' + (field.label || field.key || 'Field');
		modalFieldType.value = field.fieldType || 'text';
		modalFieldType.disabled = field.type !== 'custom';
		updateModalStepOptions(currentStepIndex >= 0 ? currentStepIndex : 0);
		modalLabel.value = field.label || '';
		modalKey.value = field.key || '';
		modalKey.disabled = field.type !== 'custom';
		modalDefault.value = field.default || '';
		modalPlaceholder.value = field.placeholder || '';
		renderOptionRows(field.options || '');
		refreshOptionEditor();
		modalValidation.value = (field.validation || [])[0] || '';
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
	if (event.target.matches('#checkoutly-open-multistep-settings')) {
		multiStepModal.hidden = false;
	}
	if (event.target.matches('[data-close-multistep-settings]')) {
		multiStepModal.hidden = true;
	}
	if (event.target.matches('[data-add-option]')) {
		if (!fieldTypeSupportsOptions(modalFieldType.value)) {
			return;
		}
		addOptionRow('');
		var rows = modalOptions.querySelectorAll('.checkoutly-option-value');
		if (rows.length) {
			rows[rows.length - 1].focus();
		}
	}
	if (event.target.matches('[data-edit-option]')) {
		var optionInput = event.target.closest('.checkoutly-option-row').querySelector('.checkoutly-option-value');
		if (optionInput) {
			optionInput.focus();
			optionInput.select();
		}
	}
	if (event.target.matches('[data-remove-option]')) {
		event.target.closest('.checkoutly-option-row').remove();
	}
	if (event.target.matches('#checkoutly-apply-field-settings') && activeCard) {
		var next = parseField(activeCard);
		next.label = modalLabel.value.trim() || next.label || next.key;
		if (next.type === 'custom') {
			next.fieldType = modalFieldType.value || 'text';
			next.key = modalKey.value.trim().toLowerCase().replace(/[^a-z0-9_]/g, '') || next.key;
		}
		next.default = modalDefault.value.trim();
		next.placeholder = modalPlaceholder.value.trim();
		next.options = fieldTypeSupportsOptions(next.fieldType || modalFieldType.value) ? optionValuesFromEditor().join('\n') : '';
		next.validation = modalValidation.value ? [modalValidation.value] : [];
		next.required = modalRequired.checked;
		next.enabled = modalEnabled.checked;
		next.width = Number(modalWidth.value) || 2;
		next.display = {
			orderDetails: modalDisplayOrder.checked,
			emails: modalDisplayEmails.checked,
			thankYou: modalDisplayThankYou.checked
		};
		setField(activeCard, next);
		if (modalStep) {
			var targetStep = steps.querySelectorAll('.checkoutly-step')[Number(modalStep.value) || 0];
			var targetList = targetStep ? targetStep.querySelector('[data-step-fields]') : null;
			if (targetList && activeCard.parentNode !== targetList) {
				targetList.appendChild(activeCard);
			}
		}
		serialize();
		modal.hidden = true;
		activeCard = null;
	}
	if (event.target.matches('[data-duplicate-field]') && card && !event.target.disabled) {
		var field = parseField(card);
		if (field.type === 'custom') {
			var copy = JSON.parse(JSON.stringify(field));
			copy.key = 'checkoutly_' + copy.fieldType + '_' + Date.now();
			copy.id = copy.key;
			copy.label = (copy.label || 'Field') + ' copy';
			card.parentNode.insertBefore(fieldCard(copy), card.nextSibling);
			serialize();
		}
	}
	if (event.target.matches('[data-remove-field]') && card && !event.target.disabled) {
		card.remove();
		refreshBulkActions();
		serialize();
	}
	if (event.target.matches('[data-bulk-remove]')) {
		selectedFieldCards().forEach(function(selectedCard){
			selectedCard.remove();
		});
		refreshBulkActions();
		serialize();
	}
	if (event.target.matches('[data-bulk-show], [data-bulk-hide]')) {
		var enabled = event.target.matches('[data-bulk-show]');
		selectedFieldCards().forEach(function(selectedCard){
			var field = parseField(selectedCard);
			field.enabled = enabled;
			setField(selectedCard, field);
		});
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
document.addEventListener('change', function(event){
	if (event.target.matches('.checkoutly-field-select')) {
		refreshBulkActions();
	}
});
if (stepSettingsSelect) {
	stepSettingsSelect.addEventListener('change', loadStepSettingsControls);
}
if (modalFieldType) {
	modalFieldType.addEventListener('change', refreshOptionEditor);
}
[stepTitleColor, stepBackgroundColor, stepBorderStyle, stepBorderWidth, stepBorderRadius, stepBorderColor, stepPadding, stepMargin].forEach(function(control){
	if (control) {
		control.addEventListener('input', updateSelectedStepStyle);
		control.addEventListener('change', updateSelectedStepStyle);
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
		formData.set('action', 'checkoutly_save_builder');
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
			showSaveNotice('success', payload.data && payload.data.message ? payload.data.message : 'Checkoutly checkout builder saved.');
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
document.addEventListener('input', function(event){
	if (event.target.matches('.checkoutly-step-title')) {
		updateStepSettingsOptions();
		if (!modal.hidden) {
			updateModalStepOptions(modalStep ? Number(modalStep.value) || 0 : 0);
		}
	}
	serialize();
});
refreshMultiStepControls();
refreshNavigationControls();
refreshRangeOutputs();
refreshBulkActions();
updateStepSettingsOptions();
serialize();

var componentSearch = document.querySelector('.checkoutly-components .checkoutly-search');
var componentPanel = document.querySelector('.checkoutly-components');

if (componentSearch && componentPanel) {
	componentSearch.addEventListener('input', function() {
		var term = this.value.trim().toLowerCase();
		var groups = componentPanel.querySelectorAll('h4.checkoutly-component-group-title');
		var grids = componentPanel.querySelectorAll('.checkoutly-component-grid');
		var details = componentPanel.querySelectorAll('details.checkoutly-component-accordion');
		var advancedHeading = componentPanel.querySelector('h3');
		var advancedGrid = componentPanel.querySelector('.checkoutly-component-grid.is-disabled');

		if (!term) {
			componentPanel.querySelectorAll('.checkoutly-component').forEach(function(btn) {
				btn.style.display = '';
			});
			groups.forEach(function(g) { g.style.display = ''; });
			grids.forEach(function(g) { g.style.display = ''; });
			if (advancedHeading) { advancedHeading.style.display = ''; }
			if (advancedGrid) { advancedGrid.style.display = ''; }
			return;
		}

		componentPanel.querySelectorAll('.checkoutly-component').forEach(function(btn) {
			var text = btn.textContent.trim().toLowerCase();
			btn.style.display = text.indexOf(term) < 0 ? 'none' : '';
		});

		if (advancedHeading && advancedGrid) {
			var hasVisibleAdvanced = Array.prototype.some.call(advancedGrid.querySelectorAll('.checkoutly-component'), function(btn) {
				return btn.style.display !== 'none';
			});
			advancedHeading.style.display = hasVisibleAdvanced ? '' : 'none';
			advancedGrid.style.display = hasVisibleAdvanced ? '' : 'none';
		}

		var visibleGrids = new Set();
		componentPanel.querySelectorAll('.checkoutly-component').forEach(function(btn) {
			if (btn.style.display !== 'none') {
				var grid = btn.closest('.checkoutly-component-grid');
				if (grid) { visibleGrids.add(grid); }
			}
		});
		grids.forEach(function(g) {
			if (!visibleGrids.has(g) && g !== advancedGrid) {
				g.style.display = 'none';
			} else if (g !== advancedGrid) {
				g.style.display = '';
			}
		});

		groups.forEach(function(title) {
			var next = title.nextElementSibling;
			var hasVisible = false;
			if (next && next.classList && next.classList.contains('checkoutly-component-grid')) {
				hasVisible = next.style.display !== 'none';
			}
			title.style.display = hasVisible ? '' : 'none';
		});

		details.forEach(function(detail) {
			var hasVisible = detail.querySelector('.checkoutly-component-grid:not([style*="display: none"]) .checkoutly-component:not([style*="display: none"])') ||
				detail.querySelector('.checkoutly-component[style=""], .checkoutly-component:not([style])');
			detail.style.display = hasVisible ? '' : 'none';
		});
	});
}

})();
