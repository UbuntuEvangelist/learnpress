<?php

/**
 * Class LP_Lesson
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit();

class LP_Lesson extends LP_Course_Item {
	/**
	 * @var mixed|string|void
	 */
	public $content = '';

	/**
	 *
	 * @var string
	 */
	public $lesson_type = null;

	/**
	 * @var string
	 */
	protected $_item_type = 'lp_lesson';

	/**
	 * LP_Lesson constructor.
	 *
	 * @param int          $lesson
	 * @param array|string $args
	 */
	public function __construct( $lesson, $args = '' ) {
		parent::__construct( $lesson, $args );
		static $count = 0;

	}

	/**
	 * __get function.
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function __get( $key ) {
		if ( isset( $this->{$key} ) ) {
			return $this->{$key};
		}
		$value = null;
		switch ( $key ) {
			case 'ID':
				$value = $this->get_id();
				break;
			/*case 'title':
				$value = $this->post->post_title;
				break;
			case 'content':
				$value = $this->get_content();
				break;*/
			default:
				$value = get_post_meta( $this->get_id(), '_lp_' . $key, true );
				if ( ! empty( $value ) ) {
					$this->$key = $value;
				}
		}

		return $value;
	}

	public function get_title() {
		return get_the_title( $this->get_id() );
	}

	public function get_content() {
		if ( ! did_action( 'learn_press_get_content_' . $this->get_id() ) ) {
			global $post, $wp_query;
			$post = get_post( $this->get_id() );
			//$posts = apply_filters( 'the_posts', array( $post ), $wp_query );
			$posts = apply_filters_ref_array( 'the_posts', array( array( $post ), &$wp_query ) );

			if ( $posts ) {
				$post = $posts[0];
			}
			setup_postdata( $post );
			ob_start();
			the_content();
			$this->content = ob_get_clean();
			wp_reset_postdata();
			do_action( 'learn_press_get_content_' . $this->get_id() );
		}

		return $this->content;
	}

	public function is( $tag ) {
		$args = func_get_args();
		unset( $args[0] );
		$method   = 'is_' . preg_replace( '!-!', '_', $tag );
		$callback = array( $this, $method );
		if ( is_callable( $callback ) ) {
			return call_user_func_array( $callback, $args );
		} else {
			throw new Exception( sprintf( __( 'The function %s doesn\'t exists', 'learnpress' ), $tag ) );
		}
	}

	public function is_previewable() {
		return apply_filters( 'learn_press_lesson_preview', ( $this->preview == 1 || $this->preview === 'yes' ), $this );
	}

	public function get_settings( $user_id, $course_id ) {
		$item_statuses = LP_Cache::get_item_statuses( false, array() );

		return array(
			'userId'   => $user_id,
			'courseId' => $course_id,
			'id'       => $this->get_id(),
			'status'   => ! empty( $item_statuses[ $this->get_id() ] ) ? $item_statuses[ $this->get_id() ] : '',
			'type'     => LP_LESSON_CPT
		);
	}

	/**
	 * @param       mixed
	 * @param array $args
	 *
	 * @return bool
	 */
	public static function get_lesson( $the_lesson = false, $args = array() ) {
		$the_lesson = self::get_lesson_object( $the_lesson );
		if ( ! $the_lesson ) {
			return false;
		}

		static $lessons = array();

		if ( empty( $lessons[ $the_lesson->ID ] ) || ( ! empty( $args['force'] ) && $args['force'] ) ) {
			$class_name = self::get_lesson_class( $the_lesson, $args );
			if ( ! class_exists( $class_name ) ) {
				$class_name = 'LP_Lesson';
			}
			$lessons[ $the_lesson->ID ] = new $class_name( $the_lesson, $args );
		}

		return $lessons[ $the_lesson->ID ];
	}

	/**
	 * Get the lesson class name
	 *
	 * @param  WP_Post $the_lesson
	 * @param  array   $args (default: array())
	 *
	 * @return string
	 */
	private static function get_lesson_class( $the_lesson, $args = array() ) {
		$lesson_id = absint( $the_lesson->ID );
		$post_type = $the_lesson->post_type;

		if ( LP_LESSON_CPT === $post_type ) {
			if ( isset( $args['lesson_type'] ) ) {
				$lesson_type = $args['lesson_type'];
			} else {
				/*$terms          = get_the_terms( $course_id, 'course_type' );
				$course_type    = ! empty( $terms ) ? sanitize_title( current( $terms )->name ) : 'simple';
				*/
				$lesson_type = '';
			}
		} else {
			$lesson_type = false;
		}

		$class_name = self::get_class_name_from_lesson_type( $lesson_type );

		// Filter class name so that the class can be overridden if extended.
		return apply_filters( 'learn_press_lesson_class', $class_name, $lesson_type, $post_type, $lesson_id );
	}

	/**
	 * Get the lesson object
	 *
	 * @param  mixed $the_lesson
	 *
	 * @uses   WP_Post
	 * @return WP_Post|bool false on failure
	 */
	private static function get_lesson_object( $the_lesson ) {
		if ( false === $the_lesson ) {
			$the_lesson = $GLOBALS['post'];
		} elseif ( is_numeric( $the_lesson ) ) {
			$the_lesson = get_post( $the_lesson );
		} elseif ( $the_lesson instanceof LP_Lesson ) {
			$the_lesson = get_post( $the_lesson->id );
		} elseif ( ! empty( $the_lesson->ID ) ) {
			$the_lesson = get_post( $the_lesson->id );
		} elseif ( ! ( $the_lesson instanceof WP_Post ) ) {
			$the_lesson = false;
		}

		return apply_filters( 'learn_press_lesson_object', $the_lesson );
	}

	/**
	 * @param  string $lesson_type
	 *
	 * @return string|false
	 */
	private static function get_class_name_from_lesson_type( $lesson_type ) {
		return $lesson_type ? 'LP_Course_' . implode( '_', array_map( 'ucfirst', explode( '-', $lesson_type ) ) ) : false;
	}
}