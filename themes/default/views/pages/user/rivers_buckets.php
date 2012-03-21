<div class="container list select data" id="river-bucket-list">
	<h2 class="null"><?php echo "No $active to display"; ?></h2>
	<article class="item cf" id="item-list-header">
		<div class="list-header">
			<div class="cell name"><?php echo $name_header; ?></div>
			<div class="cell activity"><?php echo __("30 Day Droplet Flow"); ?></div>
			<div class="cell count"><?php echo __("Drop Count"); ?></div>
			<div class="cell subscribers"><?php echo __("Subscribers"); ?></div>
			<div class="cell actions"></div>
		</div>
	</article>
</div>

<script type="text/template" id="profile-row-item-template">
<div class="row">
	<div class="cell name">
		<h3>
			<% var extraClass = item_public == 1 ? "" : "private"; %>
			<span class="icon <%= extraClass %>"></span>
			<% if (is_other_account) { %>
				<a href="<%= item_owner_url %>"><%= account_path %></a> /&nbsp;
			<% } %>

			<a href="<%= item_url %>" class="title"><%= item_name %></a>
		</h3>
	</div>
	<div class="cell activity"><span class="activity-chart"></span></div>
	<div class="cell count">
		<span class="count"><%= drop_count %></span>
	</div>
	<div class="cell subscribers">
		<span class="count"><%= subscriber_count %></span>
	</div>
	<div class="cell actions">
		<section class="actions">
		    <?php if ( ! $anonymous): ?>
		    	<% if ( ! is_owner ) { %>
				    <% class_name = ""; %>			
				    <div class="button">
				    	<p class="button-change">
							<a class="subscribe" onclick=""><span class="icon"></span>
							<% if (subscribed) { %>
								<span class="label"><?php echo __('Unsubscribe'); ?></span></a></p>
							<% } else { %>
								<span class="label"><?php echo __('Subscribe'); ?></span></a></p>
							<% } %>
				    	<div class="clear"></div>
				    </div>
				<% } else { %>
					<div class="button delete-item">
						<p class="button-change">
							<a class="delete">
								<span class="icon"></span>
								<span class="nodisplay"><?php echo __('Delete '.ucfirst($active)); ?></span>
							</a>
						</p>
						<div class="clear"></div>
						<div class="dropdown container">
							<p><?php echo __('Are you sure you want to delete this '.$active.'?'); ?></p>
							<ul>
								<li class="confirm">
									<a><?php echo __('Yep.'); ?></a>
								</li>
								<li class="cancel"><a><?php echo __('No, nevermind.'); ?></a></li>
							</ul>
						</div>
					</div>
				<% } %>
			<?php endif; ?>
		</section>
	</div>
</div>
</script>

<script type="text/javascript">
$(function() {
	var RiverBucketItem = Backbone.Model.extend({
		
		toggleSubscribe: function() {
			this.save({
				subscribed: this.get("subscribed") ? 0 : 1,
				subscriber_count: this.get("subscribed") ? parseInt(this.get("subscriber_count")) - 1 : parseInt(this.get("subscriber_count")) + 1,
			});
		}
	});
	
	var RiverBucketItemList = Backbone.Collection.extend({
		model: RiverBucketItem,
	});
	
	var RiverBucketItemView = Backbone.View.extend({
		
		tagName: "article",
		
		className: "item cf",
		
		template: _.template($("#profile-row-item-template").html()),
		
		events: {
			"click section.actions .button-change a.subscribe": "toggleSubscription",
			"click section.actions .delete-item .confirm": "delete"
		},
		
		initialize: function () {
			this.model.on('change', this.render, this);
			this.model.on('destroy', this.removeView, this);
		},
		
		render: function() {
			this.$el.html(this.template(this.model.toJSON()));
			return this;	
		},
		
		toggleSubscription: function() {
			this.model.toggleSubscribe();
		},
		
		delete: function() {
			this.model.destroy({wait: true});
		},
		
		removeView: function() {
			this.$el.fadeOut("slow");
		}
	});
		
		
	var ProfileView = Backbone.View.extend({
		
		el: "#river-bucket-list",
		
		events: {
			"click section.actions .follow-user a.subscribe": "toggleFollow"
		},
		
		initialize: function() {
			this.items = new RiverBucketItemList;
			this.items.on('add',	 this.addItem, this);
			this.items.on('reset', this.addItems, this);
			
		},
		
		addItem: function (item) {
			var view = new RiverBucketItemView({model: item});
			this.$el.append(view.render().el);

			// Show the activity
			if (typeof item.get("activity_data") != "undefined") {
				activityData = item.get("activity_data");
				view.$("span.activity-chart").sparkline(activityData, 
					{type: 'bar', barColor: '#888', barWidth: 5});
			}
		},
		
		addItems: function() {
			if (this.items.length) {
				this.$("h2.null").hide();
				this.items.each(this.addItem, this);
			} else {
				this.$("article#item-list-header").hide();
			}
		},
         
		toggleFollow: function() {
			userItem.toggleSubscribe();
		}

	});

	// Bootstrap
	var profile = new ProfileView;
	profile.items.url = "<?php echo $fetch_url ?>";
	profile.items.reset(<?php echo $list_items ?>);
});
</script>