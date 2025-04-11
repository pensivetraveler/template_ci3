const customValidatorsPreset = {
	inflector: function(name) {
		let res = '';
		if(this.rules.hasOwnProperty(name) && this.rules[name].hasOwnProperty('validatorName')){
			res = this.rules[name].validatorName;
		}
		return res;
	},
	extractor : function(regex, rule) {
		const regexp = new RegExp(regex);
		return rule.match(regexp);
	},
	rules : {
		exact_length : {
			regex : '^exact_length\\[(\\d+)\\]$',
			options : function(form, item, matches) {
				return {
					min: parseInt(matches[1]),
					max: parseInt(matches[1]),
				};
			},
			validatorName : 'stringLength',
		},
		min_length : {
			regex : '^min_length\\[(\\d+)\\]$',
			options : function(form, item, matches) {
				return {
					min: parseInt(matches[1])
				}
			},
			validatorName : 'stringLength',
		},
		max_length : {
			regex : '^max_length\\[(\\d+)\\]$',
			options : function(form, item, matches) {
				return {
					max: parseInt(matches[1]),
				};
			},
			validatorName : 'stringLength',
		},
		min : {
			regex : '^min\\[(\\d+)\\]$',
			options : function(form, item, matches) {
				return {
					min: parseInt(matches[1]),
				};
			},
			validatorName : 'options',
		},
		max : {
			regex : '^max\\[(\\d+)\\]$',
			options : function(form, item, matches) {
				return {
					max: parseInt(matches[1]),
				};
			},
			validatorName : 'options',
		},
		matches : {
			regex : '^matches\\[(.*?)\\]$',
			options : function(form, item, matches) {
				return {
					compare: function() {
						return form.querySelector(`[name="${matches[1]}"]`).value;
					},
				};
			},
			validatorName : 'identical',
		},
		required_if_value : {
			regex : '^required_if_value\\[(.*?)\\]$',
			options : function(form, item, matches) {
				return {
					field: matches[1].split('|')[0],
					value: matches[1].split('|')[1],
				};
			},
			validatorName : 'requiredIfValue',
		},
		required_if_empty_data : {
			regex : '^required_if_empty_data\\[(.*?)\\]$',
			options : function(form, item, matches) {
				return {
					listId: `${form.querySelector(item.selector).id}-list`,
				};
			},
			validatorName : 'requiredIfEmpty',
		},
		required_if_empty_file : {
			regex : '^required_if_empty_file\\[(.*?)\\]$',
			options : function(form, item, matches) {
				return {
					listId: `${form.querySelector(item.selector).id}-list`,
				};
			},
			validatorName : 'requiredIfEmpty',
		},
		required_if_article_file_empty : {
			regex : '^required_if_article_file_empty$',
			options : function(form, item, matches) {
				return {
					listId: `${form.querySelector(item.selector).id}-list`,
				};
			},
			validatorName : 'requiredIfEmpty',
		},
		required_mod : {
			regex : '^required_mod\\[(\\w+)\\]$',
			options : function(form, item, matches) {
				return {
					mod: matches[1],
				};
			},
			validatorName : 'requiredMod',
		},
		is_numeric : {
			regex : '',
			options : function(form, item, matches) {
				return {};
			},
			validatorName : 'isNumeric',
		},
		is_unique : {
			regex : '^is_unique\\[(.*?)\\]$',
			options : function(form, item, matches) {
				const name = `${form.querySelector(item.selector).name}_unique`;
				const hiddenId = form.querySelector(`[name="${name}"]`) ? form.querySelector(`[name="${name}"]`).id : null;
				return {
					hiddenId: hiddenId,
				};
			},
			validatorName : 'isUnique',
		},
		max_files : {
			regex : '^max_files\\[(.*?)\\]$',
			options : function(form, item, matches) {
				const max = matches[1].split('|')[0];
				return {
					listId: `${form.querySelector(item.selector).id}-list`,
					max : matches[1].split('|')[0],
				};
			},
			validatorName : 'checkFileCounts',
		},
	},
	validators : {
		baseValidator : function () {
			return {
				validate: function(input) {
					let valid = false;
					const node = input.element;
					const form = node.closest('form');

					return {
						node: node,
						form: form,
						valid: valid,
					};
				},
				message : 'hi',
			}
		},
		requiredIfValue : function() {
			return {
				validate: function(input) {
					let valid = false;
					const node = input.element;
					const form = node.closest('form');

					const field = input.options.field;
					const value = input.options.value;

					valid = true;
					if(form.querySelector(`[name=${field}]`)) {
						if(form.querySelector(`[name=${field}]`).value === value) valid = isEmpty(node.value);
					}

					return {
						node: node,
						form: form,
						valid: valid,
					};
				},
				message : 'hi',
			}
		},
		requiredMod : function() {
			return {
				validate: function(input) {
					let valid = false;
					const node = input.element;
					const form = node.closest('form');
					const mode = form['_mode'].value;
					const required = input.options.mod.split('|').includes(mode);

					if(required) {
						valid = node.value !== null && node.value !== '' && node.value.trim() !== '';
					}else{
						valid = true;
					}

					return {
						node: node,
						form: form,
						valid : valid,
						mode: mode,
						required: required,
					};
				}
			}
		},
		requiredIfEmpty : function() {
			return {
				validate: function(input) {
					let valid = false;
					const node = input.element;
					const form = node.closest('form');
					const list = document.getElementById(input.options.listId);

					if(list.children.length > 0) {
						valid = true;
					}else{
						valid = node.value !== null && node.value !== '' && node.value.trim() !== '';
					}

					return {
						node: node,
						form: form,
						valid : valid,
						list: list,
					};
				}
			}
		},
		isNumeric : function() {
			return {
				validate: function(input) {
					let valid = false;
					const node = input.element;
					const form = node.closest('form');

					// 값이 문자열일 경우, 숫자로 변환할 수 있는지 확인
					if (typeof input.element.value === 'string') valid = /^-?\d*\.?\d+$/.test(input.element.value);
					// 값이 숫자일 경우, 문자열로 변환 후 검사
					if (typeof input.element.value === 'number') valid = /^-?\d*\.?\d+$/.test(input.element.value.toString());

					return {
						node: node,
						form: form,
						valid: valid,
					};
				}
			}
		},
		isUnique : function () {
			return {
				validate: function(input) {
					let valid = false;
					const node = input.element;
					const form = node.closest('form');

					let message = '';
					if(form['_event'].value !== 'submit') {
						valid = true;
					}else{
						const mode = form['_mode'].value;
						if(input.options.hiddenId) valid = form.querySelector(`#${input.options.hiddenId}`).value === '1';
						if(!valid && mode === 'edit' && input.getAttribute('data-original-value') === input.value) valid = true;
						if(!valid) message = '중복 검사를 실행해주세요.';
					}

					return {
						node: node,
						form: form,
						valid: valid,
						hidden: form.querySelector(`#${input.options.hiddenId}`),
						...(message && {message : message}),
					};
				}
			}
		},
		checkFileCounts : function() {
			return {
				validate: function(input) {
					let valid = false;
					const node = input.element;
					const form = node.closest('form');
					const list = document.getElementById(input.options.listId);
					const max = parseInt(input.options.max);

					valid = list.children.length < max;

					return {
						node: node,
						form: form,
						valid: valid,
						list: list,
						max: max,
					};
				}
			}
		}
	},
};
