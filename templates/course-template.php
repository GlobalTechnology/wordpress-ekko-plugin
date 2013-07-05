<!-- Angular Templates -->
<scrip<?php ?>t type="text/ng-template" id="ekko-lesson">
	<div class="ekko-item ekko-lesson ekko-item-blue">
		<div class="navbar">
			<div class="navbar-inner item-drag-handle">
				<div class="container">

					<div class="pull-left section-toggle" title="<?php esc_attr_e( 'click to toggle', \Ekko\TEXT_DOMAIN ); ?>" ng-click="item.active = !item.active">
						<span ng-class="{'icon-chevron-right':!item.active, 'icon-chevron-down':item.active}"></span>
					</div>

					<div class="brand"><?php esc_html_e( 'Lesson', \Ekko\TEXT_DOMAIN ); ?></div>

					<div class="navbar-form pull-left">
						<div class="input-append">
							<input type="text" placeholder="<?php echo esc_attr_x( 'Title', 'lesson title placeholder', \Ekko\TEXT_DOMAIN ); ?>" class="span4" ng-model="item.title" />
							<span class="add-on">{{40 - item.title.length}}</span>
						</div>
					</div>

					<div class="navbar-form pull-right">
						<div class="btn-group">
							<a class="btn btn-ekko dropdown-toggle" data-toggle="dropdown" href><i class="icon-cog icon-white"></i> <span class="caret"></span></a>
							<ul class="dropdown-menu">
								<li><a tabindex="-1" href ng-click="removeContentItem( $index )"><i class="icon-remove"></i> <?php esc_html_e( 'Remove Lesson', \Ekko\TEXT_DOMAIN ); ?></a></li>
							</ul>
						</div>
					</div>

					<div class="pull-right info-well">
						<span class="badge">
							<i class="icon-film icon-white"></i> {{item.media.assets.length}}/5
						</span>
						<span class="badge">
							<i class="icon-font icon-white"></i> {{item.text.content.split('page-break-after:').length}}
						</span>
					</div>
				</div>
			</div>
			<div collapse="!item.active" ng-class="{in:item.active}">
				<div class="well ekko-item-assets">
					<div ng-include="'ekko-asset-media'" class="ekko-asset-media"></div>
					<div ng-include="'ekko-asset-text'" class="ekko-asset-text"></div>
				</div>
			</div>
		</div>
	</div>
</script>

<scrip<?php ?>t type="text/ng-template" id="ekko-quiz">
	<div class="ekko-item ekko-quiz ekko-item-purple">
		<div class="navbar">
			<div class="navbar-inner item-drag-handle">
				<div class="container">

					<div class="pull-left section-toggle" title="<?php esc_attr_e( 'click to toggle', \Ekko\TEXT_DOMAIN ); ?>" ng-click="item.active = !item.active">
						<span ng-class="{'icon-chevron-right':!item.active, 'icon-chevron-down':item.active}"></span>
					</div>

					<div class="brand"><?php esc_html_e( 'Quiz', \Ekko\TEXT_DOMAIN ); ?></div>

					<div class="navbar-form pull-left">
						<div class="input-append">
							<input type="text" placeholder="<?php echo esc_attr_x( 'Title', 'quiz title placeholder', \Ekko\TEXT_DOMAIN ); ?>" class="span4" ng-model="item.title" />
							<span class="add-on">{{40 - item.title.length}}</span>
						</div>
					</div>

					<div class="navbar-form pull-right">
						<div class="btn-group">
							<a class="btn btn-pimp dropdown-toggle" data-toggle="dropdown" href><i class="icon-cog icon-white"></i> <span class="caret"></span></a>
							<ul class="dropdown-menu">
								<li><a tabindex="-1" href ng-click="item.questions.push( $ekko.question_multiple() )"><i class="icon-plus"></i> <?php esc_html_e( 'Multiple Choice', \Ekko\TEXT_DOMAIN ); ?></a></li>
								<li class="divider"></li>
								<li><a tabindex="-1" href ng-click="removeContentItem( $index )"><i class="icon-remove"></i> <?php esc_html_e( 'Remove', \Ekko\TEXT_DOMAIN ); ?></a></li>
							</ul>
						</div>
					</div>

					<div class="pull-right info-well">
						<span class="badge">
							<i class="icon-question-sign icon-white"></i> {{item.questions.length}}
						</span>
					</div>
				</div>
			</div>
			<div collapse="!item.active" ng-class="{in:item.active}">
				<div class="well ekko-item-assets">
					<div ng-repeat="question in item.questions" ng-include="'ekko-question-' + question.type"></div>
				</div>
			</div>
		</div>
	</div>
</script>

<scrip<?php ?>t type="text/ng-template" id="ekko-asset-media">
	<div class="navbar ekko-item-green" ng-controller="MediaAssetsController">
		<div class="navbar-inner">
			<div class="container">
				<div class="pull-left section-toggle" title="<?php esc_attr_e( 'click to toggle', \Ekko\TEXT_DOMAIN ); ?>" ng-click="item.media.active = !item.media.active">
					<span ng-class="{'icon-chevron-right':!item.media.active, 'icon-chevron-down':item.media.active}"></span>
				</div>
				<div class="brand"><?php esc_html_e( 'Media', \Ekko\TEXT_DOMAIN ); ?></div>

				<div class="navbar-form pull-right">
					<div class="btn-group">
						<a class="btn btn-success" href ng-click="addMedia()"><i class="icon-film icon-white"></i> <?php esc_html_e( 'Add Media', \Ekko\TEXT_DOMAIN ); ?></a>
					</div>
				</div>

			</div>
		</div>
		<div collapse="!item.media.active" ng-class="{in:item.media.active}">
			<div class="well">
				<ul class="thumbnails" ng-model="item.media.assets" ui-sortable>
					<li class="span2" ng-repeat="media in item.media.assets" ng-include="'ekko-asset-media-item'" ng-controller="MediaAssetItemController"></li>
				</ul>
			</div>
		</div>
	</div>
</script>

<scrip<?php ?>t type="text/ng-template" id="ekko-asset-media-item">
	<div class="thumbnail">
		<p class="text-center" ng-switch on="media.type">
			<span ng-switch-when="image">
				<i class="icon-picture"></i> <?php esc_html_e( 'Image', \Ekko\TEXT_DOMAIN ); ?>
			</span>
			<span ng-switch-when="video">
				<i class="icon-film"></i> <?php esc_html_e( 'Video', \Ekko\TEXT_DOMAIN ); ?>
			</span>
			<span ng-switch-when="audio">
				<i class="icon-headphones"></i> <?php esc_html_e( 'Audio', \Ekko\TEXT_DOMAIN ); ?>
			</span>
		</p>
		<img ng-src="{{thumbnail_url}}" style="width:150px; height:84px;" />
		<div class="row-fluid">
			<a ui-if="!(media.type=='image')" href class="btn btn-mini btn-info pull-left" ng-click="addMediaThumbnail()"><?php esc_html_e( 'thumbnail', \Ekko\TEXT_DOMAIN ); ?></a>
			<a href class="btn btn-mini btn-danger pull-right" ng-click="$parent.item.media.assets.splice( $index, 1 )"><?php esc_html_e( 'remove', \Ekko\TEXT_DOMAIN ); ?></a>
		</div>
	</div>
</script>

<scrip<?php ?>t type="text/ng-template" id="ekko-asset-text">
	<div class="navbar ekko-item-yellow">
		<div class="navbar-inner">
			<div class="container">
				<div class="pull-left section-toggle" title="<?php esc_attr_e( 'click to toggle', \Ekko\TEXT_DOMAIN ); ?>" ng-click="item.text.active = !item.text.active">
					<span ng-class="{'icon-chevron-right':!item.text.active, 'icon-chevron-down':item.text.active}"></span>
				</div>
				<div class="brand"><?php esc_html_e( 'Text', \Ekko\TEXT_DOMAIN ); ?></div>
			</div>
		</div>
		<div collapse="!item.text.active" ng-class="{in:item.text.active}">
			<div class="well">
				<textarea ck-editor ng-model="item.text.content"></textarea>
			</div>
		</div>
	</div>
</script>

<scrip<?php ?>t type="text/ng-template" id="ekko-question-multiple">
	<div class="navbar ekko-item-pink ekko-question-multiple">
		<div class="navbar-inner">
			<div class="container">
				<div class="pull-left section-toggle" title="<?php esc_attr_e( 'click to toggle', \Ekko\TEXT_DOMAIN ); ?>" ng-click="question.active = !question.active">
					<span ng-class="{'icon-chevron-right':!question.active, 'icon-chevron-down':question.active}"></span>
				</div>
				<div class="brand"><?php esc_html_e( 'Multiple Choice', \Ekko\TEXT_DOMAIN ); ?></div>

				<div class="navbar-form pull-left">
					<div class="input-append">
						<input type="text" placeholder="<?php echo esc_attr_x( 'Question?', 'question placeholder', \Ekko\TEXT_DOMAIN ); ?>" class="span6" ng-model="question.question" />
						<span class="add-on">{{140 - question.question.length}}</span>
					</div>
				</div>

				<div class="navbar-form pull-right">
					<div class="btn-group">
						<a class="btn btn-pink" ng-click="question.options.push( $ekko.question_multiple_option() )"><i class="icon-plus icon-white"></i> <?php echo esc_html_x( 'Option', 'add option button', \Ekko\TEXT_DOMAIN ); ?></a>
					</div>
					<div class="btn-group">
						<a class="btn btn-pink dropdown-toggle" data-toggle="dropdown" href><i class="icon-cog icon-white"></i> <span class="caret"></span></a>
						<ul class="dropdown-menu">
							<li><a tabindex="-1" href ng-click=""><i class="icon-remove"></i> <?php esc_html_e( 'remove', \Ekko\TEXT_DOMAIN ); ?></a></li>
						</ul>
					</div>
				</div>

			</div>
		</div>
		<div collapse="!question.active" ng-class="{in:question.active}">
			<div class="well">
				<div class="navbar ekko-item-orange ekko-rounded-item" ng-repeat="option in question.options">
					<div class="navbar-inner">
						<div class="container">
							<div class="brand"><?php esc_html_e( 'Option', \Ekko\TEXT_DOMAIN ); ?></div>
							<div class="navbar-form pull-left form-inline">
								<div ui-switch ng-model="option.answer"></div>
								<div class="input-append">
									<input type="text" placeholder="<?php echo esc_attr_x( 'Choice', 'multiple question choice placeholder', \Ekko\TEXT_DOMAIN ); ?>" class="span4" ng-model="option.text" />
									<span class="add-on">{{40 - option.text.length}}</span>
								</div>
							</div>
							<div class="navbar-form pull-right">
								<div class="btn-group">
									<a class="btn btn-orange dropdown-toggle" data-toggle="dropdown" href><i class="icon-cog icon-white"></i> <span class="caret"></span></a>
									<ul class="dropdown-menu">
										<li><a tabindex="-1" href ng-click="$parent.question.options.splice( $index, 1 )"><i class="icon-remove"></i> <?php esc_html_e( 'remove', \Ekko\TEXT_DOMAIN ); ?></a></li>
									</ul>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</script>
