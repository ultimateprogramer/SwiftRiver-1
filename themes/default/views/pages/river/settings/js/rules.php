<script type="text/javascript">
$(function() {

	// Model for the rule actions
	var RuleAction  = Backbone.Model.extend({
		defaults: {
			'removeFromRiver': false,
			'markAsRead': false,
		},

		validate: function(attrs, options) {
			if (!attrs.markAsRead && !attrs.removeFromRiver && (attrs.addToBucket == undefined)) {
				return "<?php echo __("The rule action has not been set"); ?>";
			}
		}
	});
	
	var RuleActionList = Backbone.Collection.extend({model: RuleAction});

	// Model for the rule condition
	var RuleCondition = Backbone.Model.extend({
		defaults: {
			'field': null,
			'operator': null,
			'value': null
		},

		validate: function(attrs, options) {
			if (!attrs.value || $.trim(attrs.value.length) == 0) {
				return "<?php echo __("The keywords/value for the condition has not been specified"); ?>";
			}
			
			if (!attrs.field || !attrs.operator) {
				return "<?php echo __("The field and/or operator for the condition have not been specified"); ?>";
			}
		}
	});
	
	var RuleConditionList = Backbone.Collection.extend({model: RuleCondition});
	
	// Model for rules
	var Rule = Backbone.Model.extend({
		defaults: {
			'conditions': [],
			'actions': []
		},

		validate: function(attrs, options) {
			if (!attrs.name || attrs.name.length == 0) {
				return '<?php echo __("The name of the rule must be specified"); ?>';
			}
			
			if (!attrs.conditions || attrs.conditions.length == 0) {
				return '<?php echo __("No conditions have been specified this rule"); ?>';
			}
			
			if (!attrs.actions || attrs.actions.length == 0) {
				return '<?php echo __("No actions have been specified for this rule"); ?>';
			}
			
			if (attrs.all_conditions == undefined || attrs.all_conditions == null) {
				return '<?php echo __("The action trigger has not been specified"); ?>';
			}
		}
	});

	var RulesList = Backbone.Collection.extend({
		model: Rule,
		url: '<?php echo $action_url; ?>'
	});

	// Global rules list
	var rulesList = new RulesList();

	// View for an individual rule condition item
	var RuleConditionItemView = Backbone.View.extend({
		tagName: "li",

		template: _.template($("#rule-condition-item-template").html()),

		events: {
			"click a.modal-transition": "edit",
			"click a.modal-transition > span.remove": "delete",
		},

		render: function() {
			this.$el.html(this.template(this.model.toJSON()));
			return this;
		},

		edit: function() {
			this.options.dialog.editRuleItem(this.model);
		},

		delete: function() {
			this.options.dialog.conditionsList.remove(this.model);
			this.$el.fadeOut('fast');
			return false;
		}
	});
	
	// View for an individual rule action item
	var RuleActionItemView = Backbone.View.extend({
		tagName: "li",

		template: _.template($("#rule-action-item-template").html()),

		events: {
			"click a.modal-transition": "edit",
			"click a.modal-transition > span.remove": "delete",
		},

		initialize: function() {
			this.model.on("change:addToBucket change:markAsRead change:removeFromRiver", this.updateLabel, this);
			this.labels = [];
			this.createLabel();
		},

		render: function() {
			this.$el.html(this.template(this.model.toJSON()));
			return this;
		},

		edit: function() {
			this.options.dialog.editRuleItem(this.model);
		},

		delete: function() {
			this.options.dialog.conditionsList.remove(this.model);
			this.$el.fadeOut('fast');
			return false;
		},
		
		// Create the label for displaying the rule
		createLabel: function() {
			if (this.model.get('addToBucket')) {
				// Find the bucket
				var bucketId = this.model.get('addToBucket')
				var bucket = _.find(Assets.bucketList.own(), function(bucket) {
					return (bucket.get('id') == bucketId);
					
				});
				this.labels.push('<?php echo __("Add to \""); ?>' + bucket.get('name') + "\" bucket");
			}
			
			if (this.model.get('markAsRead')) {
				this.labels.push('<?php echo __("Mark as read"); ?>');
			}
			
			if (this.model.get('removeFromRiver')) {
				this.labels.push('<?php echo __("Remove from river"); ?>');
			}
			
			if (this.labels.length > 0) {
				var label = this.labels.length == 1 ? this.labels[0] : this.labels.join(" & ");
				this.model.set('label', label);
			}
			
		},
		
		updateLabel: function() {
			this.labels = [];
			this.createLabel();
			this.render();
		},
		
	});
	
	// View for a single bucket
	var BucketItemView = Backbone.View.extend({
		tagName: "li",
		
		className: "static cf",
		
		events: {
			"click span.select": "selectBucket",
		},
		
		template: _.template($("#bucket-item-template").html()),
		
		render: function() {
			this.$el.html(this.template(this.model.toJSON()));
			return this;
		},
		
		setSelected: function() {
			this.$el.addClass("selected");
		},
		
		selectBucket: function() {
			var hasClass = this.$el.hasClass('selected');
			this.options.actionView.removeSelectedBuckets();
			if (!hasClass) {
				this.setSelected();
				this.options.actionView.setTargetBucket(this.model);
			}
		}
	});
	
	// View for the rule conditions
	var EditRuleConditionView = Backbone.View.extend({
		tagName: "article",
		
		className: "modal modal-view modal-segment",

		template: _.template($("#edit-rule-condition-modal-template").html()),

		events: {
			"click .modal-toolbar a.button-submit": "save",
		},

		initialize: function(options) {
			this.isNew = !this.model.get('field') && !this.model.get('operator') && !this.model.get('value');
			this.model.on("invalid", function(model, error) {
				showFailureMessage(error);
			});
			
		},

		render: function() {
			this.$el.html(this.template(this.model.toJSON()));
			this.$("#condition_field").val(this.model.get('field')).attr('selected', true);
			this.$("#condition_operator").val(this.model.get('operator')).attr('selected', true);
			return this;
		},

		save: function(e) {
			var conditionData = {
				field: this.$("#condition_field").val(),
				operator: this.$("#condition_operator").val(),
				value: this.$("#condition_value").val()
			};

			this.model.set(conditionData, {validate: true});
			if (this.model.validationError) {
				return false;
			}
			if (this.isNew) {
				this.trigger("add", this.model);
			}
			return false;
		}

	});
	
	// View for the rule actions
	var EditRuleActionView = Backbone.View.extend({
		tagName: "article",

		className: "modal modal-view modal-segment",

		template: _.template($("#edit-rule-action-modal-template").html()),
		
		initialize: function(options) {
			this.isNew = this.model.get('addToBucket') == undefined && this.model.get('markAsRead') == false && this.model.get('markAsRead') == false;
			this.model.on("invalid", function(model, error) {
				showFailureMessage(error);
			});
		},

		events: {
			"click .modal-toolbar a.button-submit": "save"
		},
		
		addBucket: function(bucket) {
			var view = new BucketItemView({model: bucket, actionView: this});
			if (this.model.get('addToBucket') && this.model.get('addToBucket') == bucket.get('id')) {
				view.setSelected();
			}
			this.$(".buckets-list .view-table").append(view.render().el);
		},

		render: function() {
			this.$el.html(this.template(this.model.toJSON()));
			_.each(this.options.bucketsList.own(), this.addBucket, this);
			return this;
		},

		save: function() {
			var actionData = {};
			if (this.$("#mark_as_read").is(":checked")) {
				actionData.markAsRead = true;
			}
			if (this.$("#remove_from_river").is(":checked")) {
				actionData.removeFromRiver = true;
			}

			this.model.set(actionData, {validate: true});
			if (this.model.validationError) {
				return false;
			}

			if (this.isNew) {
				this.trigger("add", this.model);
			}
			return false;
		},
		
		removeSelectedBuckets: function() {
			this.$(".buckets-list ul.view-table li").removeClass('selected');
			this.model.unset('addToBucket');
		},
		
		setTargetBucket: function(bucket) {
			this.model.set('addToBucket', bucket.get('id'));
		}
	});

	// Modal for creating/editing rules
	var CreateRuleModal = Backbone.View.extend({
		tagName: "article",
		
		className: "modal modal-view",

		template: _.template($("#create-rule-modal-template").html()),
		
		events: {
			"click #rule-actions li.add > a.modal-transition": "createRuleAction",
			"click #rule-conditions li.add > a.modal-transition": "createRuleCondition",
			"click #rule-conditions-match li a": "toggleConditionMatch",
			"click .modal-toolbar a.modal-close": "save"
		},
		
		initialize: function(options) {
			this.conditionsList = new RuleConditionList();
			this.actionsList = new RuleActionList();

			this.conditionsList.on('reset', this.addConditions, this);
			this.conditionsList.on('add', this.addCondition, this);
			this.actionsList.on('reset', this.addActions, this);
			this.actionsList.on('add', this.addAction, this);
			
			// Show failure message on validation error
			this.model.on("invalid", function(model, error) {
				showFailureMessage(error);
			});
		},
		
		createRuleAction: function() {
			this.editRuleItem(new RuleAction());
		},
		
		createRuleCondition: function() {
			this.editRuleItem(new RuleCondition());
		},
		
		addConditions: function() {
			this.conditionsList.each(this.addCondition, this);
		},

		addCondition: function(condition) {
			var view = new RuleConditionItemView({model: condition, dialog: this});
			this.$("#rule-conditions").prepend(view.render().el);
		},
		
		addActions: function() {
			this.actionsList.each(this.addAction, this);
		},

		addAction: function(action) {
			var view = new RuleActionItemView({model: action, dialog: this});
			this.$("#rule-actions").prepend(view.render().el);
		},
		
		editRuleItem: function(item) {
			var optionData = {model: item, rule: this.model};
			var view = null;

			if (item instanceof RuleCondition) {
				optionData.conditionsList = this.conditionsList;
				view = new EditRuleConditionView(optionData);
				view.on("add", this.ruleConditionAdded, this);
			} else if (item instanceof RuleAction) {
				optionData.actionsList = this.actionsList;
				optionData.bucketsList = Assets.bucketList;
				view = new EditRuleActionView(optionData);
				view.on("add", this.ruleActionAdded, this);
			}

			if (view != null) {
				modalShow(view.render().$el);
			}
		},
		
		ruleConditionAdded: function(condition) {
			this.conditionsList.add(condition);
			modalBack();
		},

		ruleActionAdded: function(action) {
			this.actionsList.add(action);
			modalBack();
		},

		render: function() {
			var ruleName = this.model.get('name');
			this.$el.html(this.template({name: ruleName}));

			this.conditionsList.reset(this.model.get('conditions'));
			this.actionsList.reset(this.model.get('actions'));
			
			if (this.model.get('id')) {
				if (this.model.get('all_conditions')) {
					this.$("#match-all").attr("checked", true);
				} else {
					this.$("#match-any").attr("checked", true);
				}
			}
			
			return this;
		},

		save: function(e) {
			var data = {
				name: $.trim(this.$("#rule_name").val()),
				conditions: this.conditionsList.models,
				actions: this.actionsList.models,
				all_conditions: this.$("input[name=all_conditions]:checked").val()
			}

			var isNew = !this.model.get('id');
			// Save options
			var saveOptions = {
				wait: true,
				success: function(model, response){
					var message = isNew
						? '<?php echo __("Rule successfully created"); ?>'
						: '<?php echo __("Rule successfully updated"); ?>';

					showSuccessMessage(message, {flash: true});
					setTimeout(function() { modalHide(); }, 2200);
				},
				error: function(model, response) {
					showFailureMessage(response.responseText);
				}
			};
			
			if (!this.model.get('id')) {
				rulesList.create(data, saveOptions);
			} else {
				this.model.save(data, saveOptions);
			}

			return false;
		},

		toggleConditionMatch: function(e) {
			var hash = $(e.currentTarget).prop("hash");
			this.$(hash).attr("checked", true);
			
			return false;
		}
	});
	
	var RuleItemView = Backbone.View.extend({
		tagName: "li",

		template: _.template($("#rules-item-template").html()),

		events: {
			"click span.remove": "deleteRule",
			"click a.modal-trigger": "displayRule"
		},
		
		initialize: function() {
			if (this.model.get("id")) {
				this.model.on("change:name", this.renderRuleLabel, this);
			}
		},

		deleteRule: function() {
			new ConfirmationWindow('<?php echo __("Are you sure you want to delete the \""); ?>' + this.model.get('name') + "\" rule", 
				this.confirmDelete, this).show();
				
			return false;
		},
		
		confirmDelete: function() {
			var view = this;
			this.model.destroy({
				wait: true,
				success: function(){
					view.$el.fadeOut('fast');
					view.$el.remove();
					showSuccessMessage('<?php echo __("The rule has been deleted!"); ?>', {flash: true});
			}})
			return false;
		},
		
		displayRule: function() {
			modalShow(new CreateRuleModal({model: this.model}).render().el);
			return false;
		},

		render: function() {
			this.$el.html(this.template(this.model.toJSON()));
			return this;
		},
		
		renderRuleLabel: function() {
			this.$el.html(this.template(this.model.toJSON()));
		}
	});
	
	var RulesView = Backbone.View.extend({
		el: "#rules-list",
		
		events: {
			"click li.add a.modal-trigger": "showCreateRule",
		},
		
		initialize: function(options) {
			options.rulesList.on("add", this.addRule, this);
			options.rulesList.on("reset", this.addRules, this);
		},
		
		addRule: function(rule) {
			var view = new RuleItemView({model: rule});
			this.$el.prepend(view.render().el);
		},
		
		addRules: function() {
			rulesList.each(this.addRule, this);
		},
		
		showCreateRule: function() {
			var rule = new Rule();
			rule.urlRoot = '<?php echo $action_url; ?>';
			modalShow(new CreateRuleModal({model: rule}).render().el);
			return false;
		}

	});
	
	new RulesView({rulesList: rulesList});
	
	rulesList.reset(<?php echo $rules; ?>);
});
</script>