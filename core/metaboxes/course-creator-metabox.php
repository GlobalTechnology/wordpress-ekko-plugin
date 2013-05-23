<?php namespace Ekko\Core\Metaboxes {

	final class CourseCreatorMetabox extends \GTO\Framework\Posts\PostMetabox {

		const EKKO_LESSONS = 'ekko-lessons';

		final protected function __construct() {
			$this->id = 'ekkocousecreatordiv';
			$this->location = self::LOC_STATIC_AFTER_EDITOR;
			$this->ajax = array(
				'ekko-thumbnail' => array( &$this, 'get_ekko_thumbnail' ),
			);
		}

		/**
		 * (non-PHPdoc)
		 * @see \GTO\Framework\Posts\PostMetabox::display()
		 *
		 * @param \Ekko\Core\CoursePost $course
		 * @param array|null $metabox
		 */
		final public function display( $course, $metabox ) {
			$lessons = json_encode( $course->lessons );
			?>
			<input type="hidden" name="<?php echo self::EKKO_LESSONS; ?>" value="<?php echo _wp_specialchars( $lessons, ENT_QUOTES, 'UTF8', true ); ?>" />
			<div class="ekko-bootstrap container-fluid" ng-app="EkkoApp">
				<div ng-controller="CourseController">

					<div class="row-fluid course-nav">
						<div class="span12">
							<h1 class="pull-left">Course Content</h1>
							<div class="pull-right ekko-content-buttons">
								<a class="btn btn-ekko" href ng-click="addLesson()"><i class="icon-plus icon-white"></i> Lesson</a>
								<a class="btn btn-pimp" href ng-click="addQuiz()"><i class="icon-plus icon-white"></i> Quiz</a>
							</div>
						</div>
					</div>

					<div class="course-items" ui-sortable="{handle:'.item-drag-handle', helper:'clone'}" ng-model="lessons">
						<div ng-repeat="item in lessons" ng-include="'ekko-' + item.type"></div>
					</div>

					<!-- Angular Templates -->
					<scrip<?php ?>t type="text/ng-template" id="ekko-lesson">
						<div class="ekko-item ekko-lesson ekko-item-blue">
							<div class="navbar">
								<div class="navbar-inner item-drag-handle">
									<div class="container">

										<div class="pull-left section-toggle" title="click to toggle" ng-click="item.active = !item.active">
											<span class="icon-chevron-down"></span>
										</div>

										<div class="brand">Lesson</div>

										<div class="navbar-form pull-left">
											<div class="input-append">
												<input type="text" placeholder="Title" class="span4" ng-model="item.title" />
												<span class="add-on">{{40 - item.title.length}}</span>
											</div>
										</div>

										<div class="navbar-form pull-right">
											<div class="btn-group">
												<a class="btn btn-ekko dropdown-toggle" data-toggle="dropdown" href><i class="icon-cog icon-white"></i> <span class="caret"></span></a>
												<ul class="dropdown-menu">
													<li><a tabindex="-1" href ng-click="removeLesson($index)"><i class="icon-remove"></i> Remove Lesson</a></li>
												</ul>
											</div>
										</div>

										<div class="pull-right info-well">
											<span class="badge" data-toggle="tooltip" data-title="Media Count">
												<i class="icon-film icon-white"></i> {{item.media.assets.length}}/5
											</span>
											<span class="badge" data-toggle="tooltip" data-title="Text Pages">
												<i class="icon-font icon-white"></i> {{item.text.content.split('page-break-after:always').length}}
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

										<div class="pull-left section-toggle" title="click to toggle" ng-click="item.active = !item.active">
											<span class="icon-chevron-down"></span>
										</div>

										<div class="brand">Quiz</div>

										<div class="navbar-form pull-right">
											<div class="btn-group">
												<a class="btn btn-pimp dropdown-toggle" data-toggle="dropdown" href><i class="icon-cog icon-white"></i> <span class="caret"></span></a>
												<ul class="dropdown-menu">
													<li><a tabindex="-1" href ng-click="item.questions.push({type:'multiple',active:true,question:'',options:[{answer:true,text:''},{answer:false,text:''}]})"><i class="icon-plus"></i> Multiple Choice</a></li>
													<li class="divider"></li>
													<li><a tabindex="-1" href ng-click="removeLesson($index)"><i class="icon-remove"></i> Remove</a></li>
												</ul>
											</div>
										</div>

										<div class="pull-right info-well">
											<span class="badge" data-toggle="tooltip" data-title="Questions">
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
									<div class="pull-left section-toggle" title="click to toggle" ng-click="item.media.active = !item.media.active">
										<span class="icon-chevron-down"></span>
									</div>
									<div class="brand">Media</div>

									<div class="navbar-form pull-right">
										<div class="btn-group">
											<a class="btn btn-success" href ng-click="addMediaAsset()" ng-class="{disabled:item.media.assets.length>4}"><i class="icon-film icon-white"></i> Add Media</a>
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
									<i class="icon-picture"></i> Image
								</span>
								<span ng-switch-when="video">
									<i class="icon-film"></i> Video
								</span>
								<span ng-switch-when="audio">
									<i class="icon-headphones"></i> Audio
								</span>
							</p>
							<img ng-src="{{thumbnail_url}}{{media.thumbnail_id}}" style="width:150px; height:84px;" />
							<div class="row-fluid">
								<a ui-if="!(media.type=='image')" href class="btn btn-mini btn-info pull-left" ng-click="addMediaThumbnail()">thumbnail</a>
								<a href class="btn btn-mini btn-danger pull-right" ng-click="removeMedia()">remove</a>
							</div>
						</div>
					</script>

					<scrip<?php ?>t type="text/ng-template" id="ekko-asset-text">
						<div class="navbar ekko-item-yellow">
							<div class="navbar-inner">
								<div class="container">
									<div class="pull-left section-toggle" title="click to toggle" ng-click="item.text.active = !item.text.active">
										<span class="icon-chevron-down"></span>
									</div>
									<div class="brand">Text</div>
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
									<div class="pull-left section-toggle" title="click to toggle" ng-click="question.active = !question.active">
										<span class="icon-chevron-down"></span>
									</div>
									<div class="brand">Multiple Choice</div>

									<div class="navbar-form pull-left">
										<div class="input-append">
											<input type="text" placeholder="Question?" class="span6" ng-model="question.question" />
											<span class="add-on">{{140 - question.question.length}}</span>
										</div>
									</div>

									<div class="navbar-form pull-right">
										<div class="btn-group">
											<a class="btn btn-pink" ng-click="question.options.push({answer:false,text:''})"><i class="icon-plus icon-white"></i> Option</a>
										</div>
										<div class="btn-group">
											<a class="btn btn-pink dropdown-toggle" data-toggle="dropdown" href><i class="icon-cog icon-white"></i> <span class="caret"></span></a>
											<ul class="dropdown-menu">
												<li><a tabindex="-1" href ng-click=""><i class="icon-remove"></i> remove</a></li>
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
												<div class="brand">Option</div>
												<div class="navbar-form pull-left form-inline">
													<div ui-switch ng-model="option.answer"></div>
													<div class="input-append">
														<input type="text" placeholder="Choice" class="span4" ng-model="option.text" />
														<span class="add-on">{{40 - option.text.length}}</span>
													</div>
												</div>
												<div class="navbar-form pull-right">
													<div class="btn-group">
														<a class="btn btn-orange dropdown-toggle" data-toggle="dropdown" href><i class="icon-cog icon-white"></i> <span class="caret"></span></a>
														<ul class="dropdown-menu">
															<li><a tabindex="-1" href ng-click="$parent.question.options.splice($index,1)"><i class="icon-remove"></i> remove</a></li>
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

				</div>
			</div>
			<?php
		}

		/**
		 * (non-PHPdoc)
		 * @see \GTO\Framework\Posts\PostMetabox::save()
		 * @param \Ekko\Core\CoursePost $course
		 */
		final public function save( $course ) {
			if( array_key_exists( self::EKKO_LESSONS, $_POST ) )
				$course->lessons = json_decode( stripslashes( $_POST[ self::EKKO_LESSONS ] ) );
		}

		final public function get_ekko_thumbnail() {
			$id = array_key_exists( 'id', $_REQUEST ) ? (int) stripslashes( $_REQUEST[ 'id'] ) : null;
			if( $id ) {
				$image = image_get_intermediate_size( $id, 'ekko-thumbnail' );
				$src = wp_get_attachment_image_src( $id, 'ekko-thumbnail', true );
				wp_redirect( $src[0] );
				exit();
			}
			wp_die(-1);
		}

	}
}