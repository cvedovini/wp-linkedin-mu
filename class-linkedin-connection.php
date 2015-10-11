<?php


class WPLinkedInMUConnection extends WPLinkedInConnection {

	public function __construct($user_id) {
		parent::__construct();
		$this->user_id = $user_id;
	}

	public function set_cache($key, $value, $expires=0) {
		return update_user_option($this->user_id, $key, $value);
	}

	public function get_cache($key, $default=false) {
		$value = get_user_option($key, $this->user_id);
		return ($value !== false) ? $value : $default;
	}

	public function delete_cache($key) {
		return delete_user_option($this->user_id, $key);
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
					$profile = $this->get_profile(implode(',', $fields));

					if (!$profile) {
						$this->redirect($redirect_uri, 'error', __('Cannot fetch profile', 'wp-linkedin-mu'));
						return;
					}

					wp_logout();
					$this->user_id = $this->get_or_create_user($profile);
					if ($this->user_id) {
						wp_set_auth_cookie($this->user_id);
						wp_set_current_user($this->user_id);
						$this->set_cache('wp-linkedin_oauthtoken', $retcode->access_token, $retcode->expires_in);
						do_action('linkedin_user_connected', $profile);
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

	private function get_or_create_user($profile) {
		$user_by_id = get_users(array('meta_key' => 'wp-linkedin-mu_profile_id',
						'meta_value' => $profile->id) );

		if (count($user_by_id) == 1) {
		    return $user_by_id[0]->ID;
		} else {
			$email = $profile->emailAddress;

			if (email_exists($email)) {
			    $user = get_user_by('email', $email);
			    return $user->ID;
			} elseif (is_email($email)) {
				$user_info = array(
						'user_login'	=> $email,
						'user_email'	=> $email,
						'user_pass'		=> wp_generate_password(15),
						'role' 			=> get_option('wp-linkedin-mu_default_user_role', 'subscriber'));

			    $user_id = wp_insert_user($user_info);
			    update_user_meta($user_id, 'wp-linkedin-mu_profile_id', $profile->id);
			    return $user_id;
			}
		}

		return false;
	}

	public function send_invalid_token_email() {
		$send_mail = get_user_option('wp-linkedin_sendmail_on_token_expiry');

		if ($send_mail && !$this->get_cache('wp-linkedin_invalid_token_mail_sent')) {
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
			$this->set_cache('wp-linkedin_invalid_token_mail_sent', $sent);
		}
	}

}
