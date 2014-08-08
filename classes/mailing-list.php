<?php
/**
 * This model manages all functionality associated with the subscriber
 * mailing list.
 *
 * Required models: Database, Encryption
 *
 * Future:
 * - Integrate social media links with site-wide social media link settings
 * - Count subscribers and unsubscribers
 * - Add support for more social media links
 * - Add form support that uses JS (require jQuery?) (and if JS is not enabled?)
 * - Add send email functionality on subscribe and unsubscribe
 * - Setup cron job to manage monthly/weekly newsletter distribution of latest posts
 * - Add subscribe, unsubscribe, and message default HTML email templates
 * - Add MailChimp & other 3rd party integrations
 * - Add stats (number of subscribers, unsubscribers, etc)
 * - Add ability to send out email to members of the subscription list
 * - Shortcode and function capability to pull a subscribe form into any view
 * - Extract into Wordpress plugin format
 * - Create ability to add custom HTML email templates
 * - Add additional layers of security
 *
 * @author Colton James Wiscombe <colton@hazardmediagroup.com>
 * @copyright 2014 Hazard Media Group LLC
 * @version 1.2
 */

class Mailing_List {

	public $settings = array(
		// key => array( default_value, field_type, label, options )
		'sender' => array( '', 'text', 'Send Mail From', NULL, 'no-reply@example.com' ),
		'facebook' => array( '', 'url', 'Facebook Link', NULL, 'http://facebook.com' ),
		'twitter' => array( '', 'url', 'Twitter Link', NULL, 'http://twitter.com' ),
		'pinterest' => array( '', 'url', 'Pinterest Link', NULL, 'http://pinterest.com' ),
		'instagram' => array( '', 'url', 'Instagram Link', NULL, 'http://instagram.com' ),
		'linkedin' => array( '', 'url', 'LinkedIn Link', NULL, 'http://linkedin.com' ),
		'google' => array( '', 'url', 'Google Link', NULL, 'http://plus.google.com' )
	);

	public static $table = array(
		'name' => 'mailing_list',
		'prefix' => 'ml',
		'version' => '1.0',
		'structure' => array(
			'email' => array( 'VARCHAR(255)', true ),
			'status' => array( 'VARCHAR(255)', false, 'active' ),
			'timestamp' => array( 'TIMESTAMP' )
		)
	);

	public function __construct( $args ) {

		$this->settings = Functions::merge_array( $args, $this->settings );

		$this->setup_mailing_list();
		$this->setup_admin_menus();
		$this->wp_hooks();

	}

	protected function setup_mailing_list() {

		Encryption::generate_key( 'mailing_list_key' );
		Database::install_table( static::$table );

	}

	protected function setup_admin_menus() {

		$mailing_list = array(
			'type' => 'menu_page',
			'title' => 'Mailing List',
			'menu_title' => 'Mailing List',
			'icon' => 'dashicons-email-alt',
			'view' => VIEWS_DIR . 'admin/mailing-list.php',
			'table' => static::$table
		);

		$mailing_list_settings = array(
			'type' => 'submenu_page',
			'title' => 'Mailing List Settings',
			'menu_title' => 'Settings',
			'parent' => 'mailing_list',
			'defaults' => $this->settings
		);

		new Admin_Menu( $mailing_list );
		new Admin_Menu( $mailing_list_settings );

	}

	protected function wp_hooks() {

		// Update the mailing list
		add_action( 'admin_init', array( &$this, 'update_mailing_list' ) );

	}

	public function update_mailing_list() {

		// Retrieve action to be taken
		if( !empty( $_GET['action1'] ) ) {
			$action = $_GET['action1'];
		} elseif( !empty( $_GET['action2'] ) ) {
			$action = $_GET['action2'];
		} else {
			$action = '';
		}

		// Execute action
		if( !empty( $action ) ) {
			$selected = ( !empty( $_GET['ckd'] ) ) ? $_GET['ckd'] : array();
			foreach( $selected as $row_id ) {
				switch( $action ) {

					case 'active' :
						Database::update_row( static::$table, 'id', $row_id, array( 'status' => 'active' ) );
						break;

					case 'trash' :
						Database::update_row( static::$table, 'id', $row_id, array( 'status' => 'trash' ) );
						break;

					case 'delete' :
						Database::delete_row( static::$table, 'id', $row_id );
						break;

				}
			}
		}

	}

	public function get_mailing_list( $status = 'all' ) {

		$data = array();
		$list = Database::get_results( static::$table, array( 'id', 'email', 'status', 'timestamp' ) );
		$count = count( $list );

		// Filter and return retrieved data
		switch( $status ) {

			case 'all' :
				return $data = $list;
				break;

			default :
				$data = $list;
				foreach( $list as $row ) {
					if( $row['status'] !== $status ) {
						unset( $data[$count] );
					}
					$count--;
				}
				return $data;
				break;

		}

	}

	public static function run_api_action( $action, $email ) {

		$resp = array();

		switch( $action ) {

			case 'save' :
				$resp = static::save_email( $email );
				break;

			case 'unsubscribe' :
				$resp = static::delete_email( $email );
				break;

			default :
				$resp['status'] = 'error';
				$resp['desc'] = 'invalid-action';
				$resp['message'] = 'Defined API action cannot be performed';
				break;

		}

		return $resp;

	}

	protected static function save_email( $email ) {

		$resp = array();

		// Scrub out invalid email addresses
		if( preg_match( '/^[A-Za-z0-9._%\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,4}$/', $email ) ) :

			// Save email to mailing list
			$status = static::save_to_database( strtolower( $email ) );

			switch( $status ) {

				case "success" :
					$resp['status'] = 'success';
					$resp['desc'] = 'submitted';
					$resp['message'] = 'The submitted email address has successfully been added to the mailing list.';
					break;

				case "duplicate" :
					$resp['status'] = 'error';
					$resp['desc'] = 'duplicate';
					$resp['message'] = 'The submitted email address is already on the mailing list.';
					break;

				case "error" :
					$resp['status'] = 'error';
					$resp['desc'] = 'database-connection-error';
					$resp['message'] = 'An error occured connecting to the database.  Try again later.';
					break;

			}

		else :
			$resp['status'] = 'error';
			$resp['desc'] = 'invalid-format';
			$resp['message'] = 'The submitted email address does not match the required format.';
		endif;

		return $resp;

	}

	protected static function save_to_database( $email ) {

		$data = array(
			'email' => $email,
			'timestamp' => date( 'Y-m-d H:i:s', time() )
		);
		$match = false;

		// Check for duplicates
		$list = Database::get_results( static::$table, array( 'email' ) );
		foreach( $list as $item ) {
			if( $item['email'] === $data['email'] ) {
				$match = true;
			}
		}

		// Take appropriate action
		if( $match ) :

			return $status = 'duplicate';

		else :

			Database::insert_row( static::$table, $data );

			$sender = ( get_option( 'mailing_list_settings_sender' ) && get_option( 'mailing_list_settings_sender' ) !== '' ) ? get_option( 'mailing_list_settings_sender' ) : get_bloginfo( 'admin_email' );
			$subscribe_email = array(
				'sender' => $sender,
				'reply_to' => $sender,
				'recipient' => $email,
				'subject' => 'Thanks for Subscribing!',
				'template' => VIEWS_DIR . 'email/subscribe.php'
			);

			new Email( $subscribe_email );
			return $status = 'success';

		endif;

	}

	protected static function delete_email( $email ) {

		$resp = array();

		// Scrub out invalid email addresses
		if( preg_match( '/^[A-Za-z0-9._%\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,4}$/', $email ) ) :

			// Remove email from mailing list
			$status = static::remove_from_database( strtolower( $email ) );

			switch( $status ) {

				case "success" :
					$resp['status'] = 'success';
					$resp['desc'] = 'removed';
					$resp['message'] = 'The submitted email address has successfully been removed from the mailing list.';
					break;

				case "not-found" :
					$resp['status'] = 'error';
					$resp['desc'] = 'not-found';
					$resp['message'] = 'The submitted email address is not on the mailing list.';
					break;

				case "error" :
					$resp['status'] = 'error';
					$resp['desc'] = 'database-connection-error';
					$resp['message'] = 'An error occured connecting to the database.  Try again later.';
					break;

			}

		else :
			$resp['status'] = 'error';
			$resp['desc'] = 'invalid-format';
			$resp['message'] = 'The submitted email address does not match the required format.';
		endif;

		return $resp;

	}

	protected static function remove_from_database( $email ) {

		$data = Database::get_row( static::$table, 'email', $email, true );

		if( !empty( $data['email'] ) ) :

			Database::delete_row( static::$table, 'email', $email, true );

			$sender = ( get_option( 'mailing_list_settings_sender' ) && get_option( 'mailing_list_settings_sender' ) !== '' ) ? get_option( 'mailing_list_settings_sender' ) : get_bloginfo( 'admin_email' );
			$unsubscribe_email = array(
				'sender' => $sender,
				'reply_to' => $sender,
				'recipient' => $email,
				'subject' => 'Unsubscribe Confirmation',
				'template' => VIEWS_DIR . 'email/unsubscribe.php'
			);

			new Email( $unsubscribe_email );
			return $status = 'success';

		else :

			return $status = 'not-found';

		endif;

	}

	public static function get_form( $template = '' ) {

		ob_start();
		include ( !empty( $template ) ) ? $template : VIEWS_DIR . 'mailing-list-form.php';
		$html = ob_get_contents();
		ob_end_clean();
		return $html;

	}

	public static function form( $template = '' ) {

		echo static::get_form( $template );

	}

}