<?php namespace Ekko\Core {

	/**
	 * Ekko Course composite post class
	 * @author Brian Zoetewey <brian.zoetewey@ccci.org>
	 *
	 * @property array $lessons
	 * @property object $complete
	 * @property string $course_ID
	 * @property bool $show_metadata
	 * @property string $description
	 * @property string $copyright
	 * @property string $author_name
	 * @property string $author_email
	 * @property string $author_url
	 * @property array $resources
	 */
	final class CoursePost extends \GTO\Framework\Posts\Post {

		const LESSONS      = 'ekko-lessons';
		const COURSE_ID    = 'ekko-course-id';
		const AUTHOR_NAME  = 'ekko-author-name';
		const AUTHOR_EMAIL = 'ekko-author-email';
		const AUTHOR_URL   = 'ekko-author-url';
		const DESCRIPTION  = 'ekko-description';
		const COPYRIGHT    = 'ekko-copyright';
		const METADATA     = 'ekko-toggle-metadata';
		const RESOURCES    = 'ekko-resources';
		const COMPLETE     = 'ekko-complete';

		public function __get( $key ) {
			switch( $key ) {
				case 'lessons':
					$lessons = get_post_meta( $this->ID, self::LESSONS, true );
					if( !$lessons || $lessons == '' )
						$lessons = array();
					return $lessons;
				case 'complete':
					$complete = get_post_meta( $this->ID, self::COMPLETE, true );
					if( !$complete || $complete == '' )
						$complete = (object) array( 'message' => '', 'active' => false );
					return $complete;
				case 'course_ID':
					$course_id = get_post_meta( $this->ID, self::COURSE_ID, true );
					if( $course_id && is_numeric( $course_id ) )
						return "{$course_id}";
					return false;
				case 'description':
					return get_post_meta( $this->ID, self::DESCRIPTION, true );
				case 'copyright':
					return get_post_meta( $this->ID, self::COPYRIGHT, true );
				case 'author_name':
					$value = get_post_meta( $this->ID, self::AUTHOR_NAME, true );
					if( $value )
						return $value;
					$user = new \WP_User( $this->post_author );
					return $user->display_name;
				case 'author_email':
					$value = get_post_meta( $this->ID, self::AUTHOR_EMAIL, true );
					if( $value )
						return $value;
					$user = new \WP_User( $this->post_author );
					return $user->user_email;
				case 'author_url':
					$value = get_post_meta( $this->ID, self::AUTHOR_URL, true );
					if( $value )
						return $value;
					$user = new \WP_User( $this->post_author );
					return $user->user_url;
				case 'show_metadata':
					return !get_post_meta( $this->ID, self::METADATA, true );
				case 'resources':
					$resources = get_post_meta( $this->ID, self::RESOURCES, true );
					if( !$resources || !is_array( $resources ) )
						return array();
					return $resources;
				default:
					return parent::__get( $key );
			}
		}

		public function __set( $key, $value ) {
			switch( $key ) {
				case 'lessons':
					update_post_meta( $this->ID, self::LESSONS, $value );
					break;
				case 'complete':
					update_post_meta( $this->ID, self::COMPLETE, $value );
					break;
				case 'course_ID':
					update_post_meta( $this->ID, self::COURSE_ID, $value );
					break;
				case 'description':
					update_post_meta( $this->ID, self::DESCRIPTION, $value );
					break;
				case 'copyright':
					update_post_meta( $this->ID, self::COPYRIGHT, $value );
					break;
				case 'author_name':
					update_post_meta( $this->ID, self::AUTHOR_NAME, $value );
					break;
				case 'author_email':
					update_post_meta( $this->ID, self::AUTHOR_EMAIL, $value );
					break;
				case 'author_url':
					update_post_meta( $this->ID, self::AUTHOR_URL, $value );
					break;
				case 'show_metadata':
					update_post_meta( $this->ID, self::METADATA, $value );
					break;
				case 'resources':
					update_post_meta( $this->ID, self::RESOURCES, $value );
					break;
				default:
					parent::__set( $key, $value );
			}
		}

		private $resource_map;

		public function get_manifest() {
			$dom = new \DOMDocument( '1.0', 'UTF-8' );
			$dom->formatOutput = true;

			//Reset the internal resource map
			$this->resource_map = array();

			$course = $dom->appendChild( $dom->createElementNS( \Ekko\XMLNS_MANIFEST, 'ekko:course' ) );
			$course->setAttribute( 'schemaVersion', '1' );
			if( $this->course_ID )
				$course->setAttribute( 'id', $this->course_ID );

			$meta = $course->appendChild( $dom->createElementNS( \Ekko\XMLNS_MANIFEST, 'ekko:meta' ) );
			//Title
			$meta->appendChild( $dom->createElementNS( \Ekko\XMLNS_MANIFEST, 'ekko:title', $this->post_title ) );
			//Author
			$author = $meta->appendChild( $dom->createElementNS( \Ekko\XMLNS_MANIFEST, 'ekko:author' ) );
			$author->appendChild( $dom->createElementNS( \Ekko\XMLNS_MANIFEST, 'ekko:name', $this->author_name ) );
			$author->appendChild( $dom->createElementNS( \Ekko\XMLNS_MANIFEST, 'ekko:email', $this->author_email ) );
			$author->appendChild( $dom->createElementNS( \Ekko\XMLNS_MANIFEST, 'ekko:url', $this->author_url ) );
			//Banner
			$banner_id = (int) get_post_thumbnail_id( $this->ID );
			$banner = $meta->appendChild( $dom->createElementNS( \Ekko\XMLNS_MANIFEST, 'ekko:banner' ) );
			$banner_object = (object) array( 'type' => 'file', 'post_id' => $banner_id, 'banner' => true );
			$banner->setAttribute( 'resource', $this->get_resource_id( $banner_object ) );

			//Description
			$meta->appendChild( $dom->createElementNS( \Ekko\XMLNS_MANIFEST, 'ekko:description', $this->description ) );
			//Copyright
			$meta->appendChild( $dom->createElementNS( \Ekko\XMLNS_MANIFEST, 'ekko:copyright', $this->copyright ) );

			//Lessons
			$content = $course->appendChild( $dom->createElementNS( \Ekko\XMLNS_MANIFEST, 'ekko:content' ) );
			foreach( $this->lessons as $item ) {

				switch( $item->type ) {
					case "lesson":
						$lesson = $content->appendChild( $dom->createElementNS( \Ekko\XMLNS_MANIFEST, 'ekko:lesson' ) );
						$lesson->setAttribute( 'id', $item->id );
						$lesson->setAttribute( 'title', $item->title );

						foreach( $item->media->assets as $media_item ) {
							$media = $lesson->appendChild( $dom->createElementNS( \Ekko\XMLNS_MANIFEST, 'ekko:media' ) );
							$media->setAttribute( 'id', $media_item->id );
							$media->setAttribute( 'type', $media_item->type );
							$media->setAttribute( 'resource', $this->get_resource_id( $media_item->resource ) );
							if( $media_item->thumbnail )
								$media->setAttribute( 'thumbnail', $this->get_resource_id( $media_item->thumbnail ) );
						}

						$text_content = explode( '<div style="page-break-after:always"><span style="display:none">&nbsp;</span></div>', $item->text->content );
						$i=0;
						foreach( $text_content as $text_item ) {
							$text = $lesson->appendChild( $dom->createElementNS( \Ekko\XMLNS_MANIFEST, 'ekko:text' ) );
							$text->setAttribute( 'id', "{$item->text->id}-{$i}" );
							$text->appendChild( $dom->createCDATASection( $text_item ) );
							$i++;
						}
						break;
					case "quiz":
						$quiz = $content->appendChild( $dom->createElementNS( \Ekko\XMLNS_MANIFEST, 'ekko:quiz' ) );
						$quiz->setAttribute( 'id', $item->id );
						$quiz->setAttribute( 'title', $item->title );

						foreach( $item->questions as $question_item ) {
							$question = $quiz->appendChild( $dom->createElementNS( \Ekko\XMLNS_MANIFEST, 'ekko:question' ) );
							$question->setAttribute( 'id', $question_item->id );
							$question->setAttribute( 'type', $question_item->type );

							switch( $question_item->type ) {
								case "multiple":
									$text = $question->appendChild( $dom->createElementNS( \Ekko\XMLNS_MANIFEST, 'ekko:text' ) );
									$text->appendChild( $dom->createCDATASection( $question_item->question ) );

									$options = $question->appendChild( $dom->createElementNS( \Ekko\XMLNS_MANIFEST, 'ekko:options' ) );
									foreach( $question_item->options as $option_item ) {
										$option = $options->appendChild( $dom->createElementNS( \Ekko\XMLNS_MANIFEST, 'ekko:option', $option_item->text ) );
										$option->setAttribute( 'id', $option_item->id );
										if( $option_item->answer === true )
											$option->setAttribute( 'answer', 'answer' );
									}

									break;
							}
						}

						break;
				}
			}

			$course_complete = $this->complete;
			if( $course_complete->message && $course_complete->message != '' ) {
				$complete = $course->appendChild( $dom->createElementNS( \Ekko\XMLNS_MANIFEST, 'ekko:complete' ) );
				$message = $complete->appendChild( $dom->createElementNS( \Ekko\XMLNS_MANIFEST, 'ekko:message' ) );
				$message->appendChild( $dom->createCDATASection( $course_complete->message ) );
			}

			$resources_xml = $course->appendChild( $dom->createElementNS( \Ekko\XMLNS_MANIFEST, 'ekko:resources' ) );
			foreach( $this->resource_map as &$resource_item ) {

				$resource = $resources_xml->appendChild( $dom->createElementNS( \Ekko\XMLNS_MANIFEST, 'ekko:resource' ) );
				$resource->setAttribute( 'id',   $resource_item->id );
				$resource->setAttribute( 'type', $resource_item->type );
				switch( $resource_item->type ) {
					case 'file':
						$media_id = (int) $resource_item->post_id;
						if( $media_id <= 0 && $resource_item->banner == true ) {
							$media_file = \Ekko\PLUGIN_DIR . 'images/default-banner.png';

							$resource->setAttribute( 'file', basename( $resource_item->file = $media_file ) );
							$resource->setAttribute( 'size', $resource_item->size = filesize( $media_file ) );
							$resource->setAttribute( 'sha1', $resource_item->sha1 = sha1_file( $media_file ) );
							$resource->setAttribute( 'mimeType', $resource_item->mimeType = 'image/png' );
						}
						else {
							$media_post = get_post( $media_id );
							$media_file = get_attached_file( $media_id );
							$media_meta = wp_get_attachment_metadata( $media_id );
							if( is_array( $media_meta ) ) {
								if( is_array( $media_meta[ 'sizes' ] ) && array_key_exists( 'ekko-image', $media_meta[ 'sizes' ] ) ) {
									$media_file = dirname( $media_file ) . '/' . $media_meta[ 'sizes' ][ 'ekko-image' ][ 'file' ];
								}
							}

							$resource->setAttribute( 'file', basename( $resource_item->file = $media_file ) );
							$resource->setAttribute( 'size', $resource_item->size = filesize( $media_file ) );
							$resource->setAttribute( 'sha1', $resource_item->sha1 = sha1_file( $media_file ) );
							$resource->setAttribute( 'mimeType', $resource_item->mimeType = $media_post->post_mime_type );
						}
						break;
					case 'uri':
						$resource->setAttribute( 'uri', $resource_item->uri );
						if( $resource_item->provider )
							$resource->setAttribute( 'provider', $resource_item->provider );
						break;
				}
			}
			$this->resources = $this->resource_map;

			return $dom->saveXML();
		}

		/**
		 * Fetches a unique ID for a resource
		 * @param object $item
		 * @return string
		 */
		private function get_resource_id( $item ) {
			$item->key = ( $item->type == 'file' ) ? "{$item->post_id}" : $item->uri;
			foreach( $this->resource_map as $resource )
				if( $resource->key == $item->key )
					return $resource->id;

			$item->id = \GTO\Framework\Util\UUID::v4();
			$this->resource_map[] = $item;
			return $item->id;
		}
	}
}