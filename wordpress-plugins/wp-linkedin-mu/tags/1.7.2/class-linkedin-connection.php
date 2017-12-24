<?php


class WPLinkedInMUConnection extends WPLinkedInConnection {

	public function __construct($user_id) {
		parent::__construct();
		$this->user_id = $user_id;
	}

	protected function _set_cache($key, $value) {
		return update_user_meta($this->user_id, $key, $value);
	}

	protected function _get_cache($key) {
		return get_user_meta($this->user_id, $key, true);
	}

	protected function _delete_cache($key) {
		global $wpdb;
		$options = $wpdb->get_col($wpdb->prepare("SELECT meta_key FROM {$wpdb->usermeta} WHERE user_id = %s AND meta_key LIKE %s", $this->user_id, $key));

		if (empty($options)) {
			return false;
		} else {
			foreach ($options as $option) {
				delete_user_meta($this->user_id, $option);
			}

			return true;
		}
	}

	public function process_authorization($code, $state, $redirect_uri=false) {
		if (get_option('wp-linkedin-mu_connect_with_linkedin')) {
			if (isset($_REQUEST['error'])) {
				$error = $_REQUEST['error'];
				$error_description = $_REQUEST['error_description'];
				$this->redirect($redirect_uri, 'error', "$error_description ($error)");
			} elseif ($this->check_state_token($state)) {
				$retcode = $this->retrieve_access_token($code, $redirect_uri);

				if (!is_wp_error($retcode)) {
					$this->access_token = $retcode->access_token;
					$fields = array('id', 'email-address');
					$fields = apply_filters('linkedin_connect_fields', $fields);
					$fields = array_unique($fields);
					$profile = $this->get_profile(implode(',', $fields));

					if (!$profile) {
						$this->redirect($redirect_uri, 'error', __('Cannot fetch profile', 'wp-linkedin-mu'));
						return;
					}

					$ret = $this->get_or_create_user($profile);
					$user_id = $ret[0];
					$user_is_new = $ret[1];
					
					if ($user_id) {
						$this->user_id = $user_id;

						if (!is_user_logged_in()) {
							wp_set_auth_cookie($this->user_id);
							$user = get_user_by('id', $this->user_id);
							do_action('wp_login', $user->user_login, $user);
							wp_set_current_user($this->user_id);
						}

						$this->set_cache('oauthtoken', $retcode->access_token, $retcode->expires_in);
						do_action('linkedin_user_connected', $profile, $user_id, $user_is_new);
						$redirect_uri = apply_filters('linkedin_user_redirect_uri', $redirect_uri, $profile, $user_id, $user_is_new);
						$this->redirect($redirect_uri, 'success', __('Profile successfully updated', 'wp-linkedin-mu'));
					} else {
						$this->redirect($redirect_uri, 'error', __('Cannot find or create user', 'wp-linkedin-mu'));
					}
				} else {
					$this->redirect($redirect_uri, 'error', $retcode->get_error_message());
				}
			} else {
				$this->redirect($redirect_uri, 'error', __('Invalid state', 'wp-linkedin'));
			}
		} else {
			parent::process_authorization($code, $state, $redirect_uri);
		}
	}

	/**
	 * @param Object $profile
	 * @return a tuple containing the user ID and a boolean indicating whether 
	 * 			this user has just been created or not
	 */
	private function get_or_create_user($profile) {
		if (is_user_logged_in()) {
			$user_id = get_current_user_id();
			update_user_meta($user_id, 'wp-linkedin-mu_profile_id', $profile->id);
		    return array($user_id, false);
		}

		$user_by_id = get_users(array('meta_key' => 'wp-linkedin-mu_profile_id',
						'meta_value' => $profile->id) );

		if (count($user_by_id) > 0) {
		    return array($user_by_id[0]->ID, false);
		}

		$email = $profile->emailAddress;

		if (email_exists($email)) {
		    $user = get_user_by('email', $email);
		    update_user_meta($user->ID, 'wp-linkedin-mu_profile_id', $profile->id);
		    return array($user->ID, false);
		}

		$user_info = array(
				'user_login'	=> apply_filters('linkedin_user_login', $email, $profile),
				'user_email'	=> $email,
				'user_pass'		=> apply_filters('linkedin_user_pass', wp_generate_password(15), $profile),
				'role' 			=> get_option('wp-linkedin-mu_default_user_role', 'subscriber'));

	    $user_id = wp_insert_user($user_info);
	    update_user_meta($user_id, 'wp-linkedin-mu_profile_id', $profile->id);
	    return array($user_id, true);
	}

	public function send_invalid_token_email() {
		$send_mail = get_user_meta($this->user_id, 'wp-linkedin_sendmail_on_token_expiry');

		if ($send_mail && !$this->get_cache('invalid_token_mail_sent')) {
			$user_info = get_userdata($this->user_id);
			$blog_name = get_option('blogname');
			$user_email = $user_info->user_email;
			$header = array("From: $blog_name <$user_email>");
			$subject = "[$blog_name] " . __('Invalid or expired LinkedIn access token', 'wp-linkedin-mu');

			$message = __('The access token for the WP LinkedIn plugin is either invalid or has expired, please click on the following link to renew it.', 'wp-linkedin-mu');
			$message .= "\n\n" . $this->get_authorization_url();
			$message .= "\n\n" . __('This link will only be valid for a limited period of time.', 'wp-linkedin-mu');
			$message .= "\n" . __('-Thank you.', 'wp-linkedin-mu');

			$sent = wp_mail($user_email, $subject, $message, $header);
			$this->update_user_meta('wp-linkedin_invalid_token_mail_sent', $sent);
		}
	}

}
