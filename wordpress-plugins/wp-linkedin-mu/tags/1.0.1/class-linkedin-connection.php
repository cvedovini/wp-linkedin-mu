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

	public function get_token_process_url() {
		return site_url('/wp-admin/profile.php');
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
