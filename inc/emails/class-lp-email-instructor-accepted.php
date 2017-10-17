<?php

/**
 * Class LP_Email_Enrolled_Course
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit();

class LP_Email_Instructor_Accepted extends LP_Email {

	public function __construct() {

		$this->id          = 'instructor-accepted';
		$this->title       = __( 'Accepted', 'learnpress' );
		$this->description = __( 'Become an instructor email', 'learnpress' );

		$this->default_subject = __( '[{{site_title}}] Request to become an instructor', 'learnpress' );
		$this->default_heading = __( 'Become an instructor', 'learnpress' );

		add_action( 'learn-press/become-a-teacher-sent', array( $this, 'trigger' ) );

		parent::__construct();
	}

	/**
	 * Trigger email.
	 *
	 * @param string $email
	 *
	 * @return bool
	 */
	public function trigger( $email ) {
		if ( ! $this->enable ) {
			return false;
		}

		if ( ! $user = get_user_by( 'email', $email ) ) {
			return false;
		}

		LP_Emails::instance()->set_current( $this->id );

		$this->recipient = get_option( 'admin_email' );

		$this->get_object();
		$this->get_variable();

		$return = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

		return $return;
	}
}

return new LP_Email_Instructor_Accepted();