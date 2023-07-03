<?php

namespace Includes\Controllers\Api;

use Includes\baseClasses\KCBase;
use WP_Error;
use WP_REST_Response;
use WP_REST_Server;

class KCKiviController extends KCBase
{

	public $module = 'user';

	public $nameSpace;

	function __construct()
	{

		$this->nameSpace = KIVICARE_API_NAMESPACE;

		add_action('rest_api_init', function () {

			register_rest_route($this->nameSpace . '/api/v1/' . $this->module, '/forgot-password', array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [$this, 'forgot_password'],
				'permission_callback' => '__return_true',
			));

			register_rest_route($this->nameSpace . '/api/v1/' . $this->module, '/change-password', array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [$this, 'change_password'],
				'permission_callback' => '__return_true',
			));

			register_rest_route($this->nameSpace . '/api/v1/' . $this->module, '/get-dashboard', array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [$this, 'getDashboard'],
				'permission_callback' => '__return_true',
			));

			register_rest_route($this->nameSpace . '/api/v1/' . $this->module, '/profile-update', array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [$this, 'profileUpdate'],
				'permission_callback' => '__return_true',
			));

			register_rest_route($this->nameSpace . '/api/v1/' . $this->module, '/get-detail', array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [$this, 'getUserDetail'],
				'permission_callback' => '__return_true',
			));

			register_rest_route($this->nameSpace . '/api/v1/' . $this->module, '/get-configuration', array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [$this, 'getConfiguration'],
				'permission_callback' => '__return_true',
			));

			register_rest_route($this->nameSpace . '/api/v1/' . $this->module, '/change-telemed-type', array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [$this, 'changeTelemedType'],
				'permission_callback' => '__return_true',
			));
		});
	}

	public function forgot_password_old($request)
	{
		$parameters = $request->get_params();
		$email = $parameters['email'];

		$user = get_user_by('email', $email);
		$message = null;
		$status_code = null;

		if ($user) {

			$title = 'New Password';
			$password = kcaGenerateString();
			$message = '<label><b>Hello,</b></label>';
			$message .= '<p>You have recently requested to reset your password. Here is the new password for your App</p>';
			$message .= '<p><b>New Password </b> : ' . $password . '</p>';
			$message .= '<p>Thank You.</p>';

			$headers = "MIME-Version: 1.0" . "\r\n";
			$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
			$is_sent_wp_mail = wp_mail($email, $title, $message, $headers);

			if ($is_sent_wp_mail) {
				wp_set_password($password, $user->ID);
				$message = __('Password has been sent successfully to your email address.');
				$status_code = 200;
			} elseif (mail($email, $title, $message, $headers)) {
				wp_set_password($password, $user->ID);
				$message = __('Password has been sent successfully to your email address.');
				$status_code = 200;
			} else {
				$message = __('Email not sent');
				$status_code = 400;
			}
		} else {
			$message = __('User not found with this email address');
			$status_code = 400;
		}
		return comman_message_response($message, $status_code);
	}

	public function change_password($request)
	{

		$data = kcaValidationToken($request);

		if (!$data['status']) {
			return comman_custom_response($data, 401);
		}

		$parameters = $request->get_params();

		$userdata = get_user_by('ID', $data['user_id']);

		if ($userdata == null) {
			if ($userdata == null) {
				$message = __('User not found');
				return comman_message_response($message, 422);
			}
		}

		$status_code = 200;

		if (wp_check_password($parameters['old_password'], $userdata->data->user_pass)) {
			wp_set_password($parameters['new_password'], $userdata->ID);
			$message = __("Password has been changed successfully");
		} else {
			$status_code = 400;
			$message = __("Old password is invalid");
		}
		return comman_message_response($message, $status_code);
	}

	public function getDashboard($request)
	{
		global $wpdb;
		$data = kcaValidationToken($request);

		if (!$data['status']) {
			return comman_custom_response($data, 401);
		}

		$parameters = $request->get_params();

		$users_table = $wpdb->prefix . 'users';
		$role = $data['role'];
		$userid = $data['user_id'];

		$isKiviCareProOnName = $this->isKiviCareProOnName();
		$args = [
			'date'	=> date('Y-m-d'),
			'limit'	=> (isset($parameters['limit']) && $parameters['limit'] != '') ? $parameters['limit'] : 10,
			'page' 	=> (isset($parameters['page']) && $parameters['page'] != '') ? $parameters['page'] : 1,
			'isKiviCareProOnName' => $isKiviCareProOnName
		];

		$dashboard = [];
		$total_appointment_data = kcaAppointmentList(['status' => 'all', 'isKiviCareProOnName' => $isKiviCareProOnName], $data);
		$dashboard['total_appointment'] = $total_appointment_data['total'];
		if ($role == 'doctor') {
			$patient_record = collect(get_users([
				'role' => $this->getPatientRole(),
				'meta_query' =>
				array(
					array(
						'relation' => 'AND',
						array(
							'key' => 'patient_added_by',
							'value' => $userid,
							'compare' => "=",
							'type' => 'numeric'
						),
					),
				),
			]));
			$dashboard['total_patient'] = count($patient_record);

			$args["status"] = null;
			$service_table	= $wpdb->prefix . 'kc_' . 'services';
			$mapping_table  = $wpdb->prefix . 'kc_' . 'service_doctor_mapping';

			if ($this->teleMedAddOnName()) {
				// $zoom_config_data = get_user_meta($userid, 'zoom_config_data', true);
				// $zoom_config_data = json_decode($zoom_config_data);

				$zoom_config_data = (object) kcaGetZoomConfig($userid);
				if (isset($zoom_config_data) && $zoom_config_data->enableTeleMed) {
					$condition = " AND 0 = 0 ";
				} else {
					$condition = " AND {$service_table}.type != 'system_service' ";
				}
			} else {
				$condition = " AND {$service_table}.type != 'system_service' ";
			}
			$query = "SELECT {$service_table}.id,{$service_table}.`type`,{$service_table}.`name`, {$mapping_table}.`status`,
						{$mapping_table}.`service_id`,{$mapping_table}.`doctor_id`,
						{$mapping_table}.`charges` , {$mapping_table}.id as `mapping_table_id`
				FROM {$mapping_table} 
				LEFT JOIN {$service_table} 
					ON {$mapping_table}.`service_id` = {$service_table}.id 
				WHERE {$mapping_table}.doctor_id = {$userid}  {$condition} ";

			$total_record = $wpdb->get_results($query, OBJECT);
			$dashboard['total_service'] = count($total_record);
			$appointment_data = kcaAppointmentList($args, $data);

			/*
			$total_appointment_data = kcaAppointmentList( ['status' => 'all'] , $data );
			$dashboard['total_appointment'] = $total_appointment_data['total'];
			*/
			$dashboard['upcoming_appointment_total'] = $appointment_data['total'];
			$dashboard['upcoming_appointment'] = $appointment_data['data'];
			$dashboard['weekly_appointment'] = $this->getWeeklyAppointment();
		}

		if ($role == 'patient') {
			$args["status"] = null;

			$appointment_data = kcaAppointmentList($args, $data);
			/*
			$dashboard['total_appointment'] = $appointment_data['total'];
			*/
			$dashboard['upcoming_appointment_total'] = $appointment_data['total'];
			$dashboard['upcoming_appointment'] = $appointment_data['data'];

			$authorization = $request->get_header('Authorization');
			$doctor_list = wp_remote_get(get_home_url() . "/wp-json/kivicare/api/v1/doctor/get-list?limit=4&clinic_id", array(
				'headers' => array(
					'Authorization' => $authorization,
				)
			));

			$dashboard['doctor'] = json_decode($doctor_list['body'])->data;

			$service_list = wp_remote_get(get_home_url() . "/wp-json/kivicare/api/v1/service/get-list", array(
				'headers' => array(
					'Authorization' => $authorization,
				)
			));

			$service_data = collect(json_decode($service_list['body'])->data);
			$service_count = $service_data->count();
			$dashboard['service'] = $service_count > 0 ? collect($service_data)->take('8') : [];

			$news_list = wp_remote_get(get_home_url() . "/wp-json/kivicare/api/v1/news/get-news-list", array());

			$dashboard['news'] = json_decode($news_list['body'])->data;
		}

		if ($role == 'receptionist') {
			$args["status"] = null;
			$appointment_data = kcaAppointmentList($args, $data);
			/*
			$dashboard['total_appointment'] = $appointment_data['total'];
			*/
			$dashboard['upcoming_appointment_total'] = $appointment_data['total'];
			$dashboard['upcoming_appointment'] = $appointment_data['data'];
		}

		return $dashboard;
	}

	public  function getWeeklyAppointment()
	{

		global $wpdb;

		$appointments_table = $wpdb->prefix . 'kc_' . 'appointments';

		$sunday = strtotime("last monday");
		$sunday = date('w', $sunday) === date('w') ? $sunday + 7 * 86400 : $sunday;
		$monday = strtotime(date("Y-m-d", $sunday) . " +6 days");

		$week_start = date("Y-m-d", $sunday);
		$week_end = date("Y-m-d", $monday);

		$appointments = "SELECT * FROM {$appointments_table} WHERE appointment_start_date BETWEEN '{$week_start}' AND '{$week_end}' ";

		$results = $wpdb->get_results($appointments, OBJECT);

		$data = [];
		if (count($results) > 0) {
			$appointment_data = collect($results)->groupBy('appointment_start_date');

			$group_date_appointment = $appointment_data->map(function ($item) {
				return collect($item)->count();
			});

			$datediff = strtotime($week_end) - strtotime($week_start);
			$datediff = floor($datediff / (60 * 60 * 24));

			$group_date_appointment = $group_date_appointment->toArray();

			for ($i = 0; $i < $datediff + 1; $i++) {

				$date = date("Y-m-d", strtotime($week_start . ' + ' . $i . 'day'));
				$count_appointment_date = array_key_exists($date, $group_date_appointment) ? $group_date_appointment[$date] : 0;
				$data[] = [
					"x" => date("l", strtotime($week_start . ' + ' . $i . 'day')),
					"y" => ($count_appointment_date !== null) ? $count_appointment_date : 0
				];
			}
		}

		return $data;
	}

	public function profileUpdate($request)
	{

		$data = kcaValidationToken($request);


		if (!$data['status']) {
			return comman_custom_response($data, 401);
		}

		$reqArr = $request->get_params();

		$user_detail =  get_user_by('ID', $reqArr['ID']);
		$userid  = $user_detail->ID;
		$role = getUserRole($userid);
		$validation = kcaValidateRequest([
			'first_name' 	=> 'required',
			'last_name' 	=> 'required',
			'user_email' 	=> 'email|required',
			'mobile_number' => 'required',
			'dob'           => 'required',
			'gender'        => 'required',
		], $reqArr);

		if (count($validation)) {
			return comman_message_response($validation[0], 400);
		}

		$res = wp_insert_user($reqArr);


		if (isset($res->errors)) {
			return comman_message_response(kcaGetErrorMessage($res), 400);
		}
		$temp = [
			'mobile_number' => $reqArr['mobile_number'],
			'gender'        => $reqArr['gender'],
			'dob'           => $reqArr['dob'],
			'address'       => $reqArr['address'],
			'city'          => $reqArr['city'],
			// 'state'         => $reqArr['state'],
			'country'       => $reqArr['country'],
			'postal_code'   => $reqArr['postal_code'],

		];
		$profile_data  = (array) json_decode(get_user_meta($userid, 'basic_data', true));
		if ($role == 'patient') {
			$temp['blood_group'] = $reqArr['blood_group'];
		}
		if (isset($_FILES['profile_image']) && $_FILES['profile_image'] != null) {
			$profile_image_id = media_handle_upload('profile_image', 0);
			update_user_meta($userid, $role . '_profile_image', $profile_image_id);
		}
		if ($role == 'doctor') {

			$temp['qualifications'] = json_decode($reqArr['qualifications'], true);
			$temp['specialties'] =  json_decode($reqArr['specialties'], true);
			$temp['no_of_experience'] = $reqArr['no_of_experience'];
			$temp['price'] = $reqArr['price'];
			$temp['price_type'] = $reqArr['price_type'];
			if (isset($reqArr['price_type']) && $reqArr['price_type'] == "range") {
				$temp['price'] = $reqArr['minPrice'] . '-' . $reqArr['maxPrice'];
			}


			if ($this->teleMedAddOnName()) {
				apply_filters('kct_save_zoom_configuration', [
					'user_id' => $userid,
					'enableTeleMed' => $reqArr['enableTeleMed'],
					'api_key' => $reqArr['api_key'] ?? '',
					'api_secret' => $reqArr['api_secret'] ?? ''
				]);
			}

			// Functinalty To Store Signature no Doc Role.
			if (isset($request['signature_img'])) {
				update_user_meta(get_current_user_id(), 'doctor_signature', $request['signature_img']);
			}
		}

		wp_update_user([
			'ID' => $userid,
			'first_name' => $reqArr['first_name'],
			'last_name' => $reqArr['last_name'],
			'display_name' => $reqArr['first_name'] . " " . $reqArr['last_name']
		]);

		update_user_meta($userid, 'basic_data', json_encode($temp));
		$basic_data  = get_user_meta($userid, 'basic_data', true);
		$users = get_userdata($userid);

		$profile = (array) json_decode($basic_data);
		if ($role == 'patient') {
			$profile['custom_fields'] = kcaGetCustomFields('patient_module', $userid);
		} else {
			$profile['custom_fields'] = kcaGetCustomFields('doctor_module', $userid);
		}
		$profile['profile_image'] = kcaGetProfileImage($role, $userid);
		$user_data = [
			'ID'			=> $users->ID,
			'first_name' 	=> $users->first_name,
			'last_name' 	=> $users->last_name,
			'user_email' 	=> $users->user_email,
			'user_login' 	=> $users->user_login,
			'display_name'  => $users->display_name,
		];

		if ($this->teleMedAddOnName()) {
			$config_data = kcaGetZoomConfig($userid);
			$user_data = array_merge($user_data, $config_data);
		}

		$response['data'] = (object) array_merge($user_data, $profile);
		$response['message'] = __('Profile has been updated succesfully', 'kivicare-api');

		return comman_custom_response($response);
	}

	public function getUserDetail($request)
	{
		global $wpdb;
		$data = kcaValidationToken($request);

		if (!$data['status']) {
			return comman_custom_response($data, 401);
		}

		$parameters = $request->get_params();

		$rules = [
			'ID'	=> 'required'
		];

		$validation = kcaValidateRequest($rules, $parameters);

		if (count($validation)) {
			return comman_message_response($validation[0], 400);
		}
		$id = (isset($parameters['ID']) && $parameters['ID'] != '') ? $parameters['ID'] : null;

		$users = get_userdata($id);
		$basic_data  = get_user_meta($id, 'basic_data', true);
		$profile = (array) json_decode($basic_data);

		$role = getUserRole($id);
		$profile['profile_image'] = kcaGetProfileImage($role, $id);
		$user_data = [
			'first_name' 	=> $users->first_name,
			'last_name' 	=> $users->last_name,
			'user_email' 	=> $users->user_email,
			'user_login' 	=> $users->user_login,
			'role'			=> $role
		];
		if ($role == 'doctor') {
			$user_data['custom_fields'] =  kcaGetCustomFields('doctor_module', $id);

			if ($this->teleMedAddOnName()) {
				$config_data = kcaGetZoomConfig($id);
				$user_data = array_merge($user_data, $config_data);
			}
			if ($signature_img = get_user_meta($id, 'doctor_signature', true)) {
				$user_data['signature_img'] = $signature_img;
			}
			if ($results =  $wpdb->get_results("SELECT * FROM {$wpdb->prefix}kc_patient_review WHERE `doctor_id` LIKE {$id}  "))
				$user_data['review'] = array_map(function ($item) {
					$item->id = (int)$item->id;
					$item->review = (int)$item->review;
					$item->patient_id = (int)$item->patient_id;
					$item->patient_name =  get_user_by('id', $item->patient_id)->display_name;
					$item->patient_img = wp_get_attachment_url(get_user_meta($item->patient_id, 'patient_profile_image', true));

					$item->doctor_id = (int)$item->doctor_id;
					$item->doctor_name =  get_user_by('id', $item->doctor_id)->display_name;
					$item->doctor_img =  wp_get_attachment_url(get_user_meta($item->doctor_id, 'doctor_profile_image', true));
					return $item;
				}, $results);
		}
		$response = (object) array_merge($user_data, $profile);
		return comman_custom_response($response);
	}

	public function getConfiguration($request)
	{
		$data = kcaValidationToken($request);

		if (!$data['status']) {
			return comman_custom_response($data, 401);
		}

		$data['role']		= getUserRole($data['user_id']);
		$data['isTeleMedActive'] = $this->teleMedAddOnName();
		$data['isKiviCareProOnName'] = $this->isKiviCareProOnName();
		$data['isKiviCareGooglemeetActive'] = $this->isKiviCareGooglemeetActive();
		$data['telemed_type'] = get_user_meta($data['user_id'], 'telemed_type', true);

		if ($this->teleMedAddOnName()) {
			$config_data = apply_filters('kct_get_zoom_configuration', [
				'user_id' => $data['user_id'],
			]);

			if (!empty($config_data['data']->enableTeleMed) && $config_data['data']->enableTeleMed == 'false') {
				$config_data['data']->enableTeleMed = false;
			}

			if ((!empty($config_data['data']->enableTeleMed)) && $data['telemed_type'] != 'googlemeet') {
				$data['telemed_type'] = 'zoom';
			}
		}

		$data['is_uploadfile_appointment'] = get_option(KIVI_CARE_PREFIX . 'multifile_appointment', 'off');
		$restrict_appointment = [
			'pre' =>  "0",
			'post' => "365",
		];

		$data['restrict_appointment'] = get_option(KIVI_CARE_PREFIX . 'restrict_appointment', $restrict_appointment);
		if ($data['isKiviCareProOnName']) {
			$get_googlecal_config = get_option(KIVI_CARE_PREFIX . 'google_cal_setting', true);
			$data['is_enable_google_cal'] = $get_googlecal_config['enableCal'] == 1 ? 'on' : 'off';

			$get_patient_cal_config = get_option(KIVI_CARE_PREFIX . 'patient_cal_setting', true);
			$data['is_patient_enable'] = $get_patient_cal_config == 1 ? 'on' : 'off';
			$data['google_client_id'] = trim($get_googlecal_config['client_id']);

			if ($data['role'] == 'doctor' || $data['role'] == 'receptionist') {
				$doctor_enable = get_user_meta($data['user_id'], KIVI_CARE_PREFIX . 'google_cal_connect', true);

				$data['is_enable_doctor_gcal'] = ($doctor_enable == 'off' || empty($doctor_enable)) ? 'off' : 'on';
			}
			$data['global_date_format'] = function_exists("kcGetDateFormat") ? kcGetDateFormat() : 'D-MM-YYYY';
		}
		return comman_custom_response($data);
	}

	public function changeTelemedType($request)
	{

		$data = kcaValidationToken($request);

		if (!$data['status']) {
			return comman_custom_response($data, 401);
		}

		$parameters = $request->get_params();

		if (!empty($parameters['user_id'])) {
			update_user_meta($parameters['user_id'], 'telemed_type', $parameters['telemed_type']);
			$status_code = 200;
			$message = __("Telemed status changed.", "kivicare-api");
		} else {
			$status_code = 400;
			$message = __("Telemed status not changed.", "kivicare-api");
		}

		return comman_message_response($message, $status_code);
	}

	public function forgot_password($request)
	{
		$parameters = $request->get_params();
		$email = $parameters['email'];

		$user = get_user_by('email', $email);
		$message = null;
		$status_code = null;

		if ($user) {
			$user_login = $user->user_login;
			$user_email = $user->user_email;
			$key        = get_password_reset_key($user);

			if (is_wp_error($key)) {
				return $key;
			}

			// Localize password reset message content for user.
			$locale = get_user_locale($user);

			$switched_locale = switch_to_locale($locale);

			if (is_multisite()) {
				$site_name = get_network()->site_name;
			} else {
				/*
				* The blogname option is escaped with esc_html on the way into the database
				* in sanitize_option. We want to reverse this for the plain text arena of emails.
				*/
				$site_name = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
			}

			$message = "<p>" . __('Someone has requested a password reset for the following account:', 'kivicare-api') . "</p>";
			/* translators: %s: Site name. */
			$message .= "<p>" . sprintf(__('Site Name: %s', 'kivicare-api'), $site_name) . "</p>";
			/* translators: %s: User login. */
			$message .= "<p>" . sprintf(__('Username: %s', 'kivicare-api'), $user_login) . "</p>";
			$message .= "<p>" . __('If this was a mistake, ignore this email and nothing will happen.', 'kivicare-api') . "</p>";
			$message .= "<p>" . __('To reset your password, visit the following address:', 'kivicare-api') . "</p>";
			$message .= "<p>" . network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login') . '&wp_lang=' . $locale . "</p>";

			if (!is_user_logged_in()) {
				$requester_ip = $_SERVER['REMOTE_ADDR'];
				if ($requester_ip) {
					$message .= sprintf(
						/* translators: %s: IP address of password reset requester. */
						"<p>" . __('This password reset request originated from the IP address %s.'),
						$requester_ip
					) . "</p>";
				}
			}

			/* translators: Password reset notification email subject. %s: Site title. */
			$title = sprintf(__('[%s] Password Reset'), $site_name);

			$headers = "MIME-Version: 1.0" . "\r\n";
			$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

			$is_sent_wp_mail = wp_mail($email, $title, $message, $headers);

			if ($is_sent_wp_mail) {
				$message = __('Password reset link has been sent successfully to your email address.', 'kivicare-api');
				$status_code = 200;
			} elseif (mail($email, $title, $message, $headers)) {
				$message = __('Password reset link has been sent successfully to your email address.', 'kivicare-api');
				$status_code = 200;
			} else {
				$message = __('Email not sent', 'kivicare-api');
				$status_code = 400;
			}
		} else {
			$message = __('User not found with this email address', 'kivicare-api');
			$status_code = 400;
		}
		return comman_message_response($message, $status_code);
	}
}
