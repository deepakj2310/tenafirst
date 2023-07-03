<?php

namespace TeleMedApp\filters;

use App\baseClasses\KCBase;
use TeleMedApp\baseClasses\KCTZoomApi;

class KCTDoctorConfigFilters extends KCBase {

	public function __construct() {
		// Filter to save zoom configuration for user...
		add_filter('kct_save_zoom_configuration', [$this, 'saveZoomConfiguration']);

		add_filter('kct_get_zoom_configuration', [$this, 'getZoomConfiguration']);
	}

	public function getZoomConfiguration( $filterData ) {

		$user_meta = get_user_meta( (int)$filterData['user_id'], 'zoom_config_data', true );

		if ( $user_meta ) {
			$user_meta = json_decode( $user_meta );
		} else {
			$user_meta = [];
		}

		return [
			'status'  => true,
			'message' => esc_html__( 'Configuration saved', 'kiviCare-telemed-addon' ),
			'data'    => $user_meta
		];
	}

	public function saveZoomConfiguration($filterData) {
		$res = json_decode((new KCTZoomApi( $filterData['api_key'], $filterData['api_secret']))->listUsers());
		if( isset($res->users[0]->id)){
			$zoom_id = $res->users[0]->id;
		}else{
			$zoom_id = $filterData['zoom_id'];
		}

        $enable = $filterData['enableTeleMed'];
        $status = true;
        $message = esc_html__('Configuration saved', 'kiviCare-telemed-addon');

		if (isset($res->code) && $res->code === 124) {
            $enable = 'false';
            $status = false;
            $message = esc_html__('Invalid access token', 'kiviCare-telemed-addon');
		}

        $temp = [
            'enableTeleMed' => $enable,
            'api_key' => isset($filterData['api_key']) && $filterData['api_key'] !== null ? $filterData['api_key'] : "",
            'api_secret' => isset($filterData['api_secret']) && $filterData['api_secret'] !== null ? $filterData['api_secret'] : "",
            'zoom_id' => $zoom_id,
        ];

        update_user_meta((int)$filterData['user_id'], 'zoom_config_data', json_encode($temp));
        if( $enable === 'true'){
            update_user_meta((int)$filterData['user_id'],'telemed_type','zoom');
        }

		return [
			'status' => $status,
            'message' => $message
		];
	}
}