<?php

namespace Includes\Controllers\Api;

use Includes\baseClasses\KCBase;
use WP_Error;
use WP_REST_Response;
use WP_REST_Server;

class KCPrescriptionController extends KCBase {

    public $module = 'prescription';

    public $nameSpace;

    function __construct() {

		$this->nameSpace = KIVICARE_API_NAMESPACE;

		add_action( 'rest_api_init', function () {

			register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, '/save', array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [ $this, 'savePrescription' ],
				'permission_callback' => '__return_true',
            ));

            register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, '/delete', array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [ $this, 'deletePrescription' ],
				'permission_callback' => '__return_true',
            ));

            register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, '/list', array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [ $this, 'getList' ],
				'permission_callback' => '__return_true',
			));

            register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, '/prescription-mail', array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [ $this, 'prescriptionMail' ],
				'permission_callback' => '__return_true',
			));
		});
    }

    public function prescriptionMail($request)
    {
        global $wpdb;

        $data = kcaValidationToken($request);

		if (!$data['status']) {
			return comman_custom_response($data,401);
        }

        $userid = $data['user_id'];

        $parameters = $request->get_params();

        $encounter_id = isset($parameters['encounter_id']) && $parameters['encounter_id'] != null ? $parameters['encounter_id'] : '';

        $precription_table = $wpdb->prefix. 'kc_' . 'prescription';
        $encounter_table = $wpdb->prefix.'kc_'.'patient_encounters';
        $status = false;
        $message = 'Failed To Send Mail';
        $default_email_template = [
            [
                'post_name' => KIVI_CARE_PREFIX.'book_prescription',
                'post_content' => '<p> Welcome to KiviCare ,</p><p> You Have Medicine Prescription on </p><p> Clinic : {{clinic_name}}</p><p>Doctor : {{doctor_name}}</p><p>Prescription :{{prescription}} </p><p> Thank you. </p>',
                'post_title' => 'Patient Prescription Reminder',
                'post_type' => KIVI_CARE_PREFIX.'mail_tmp',
                'post_status' => 'publish',
            ],
        ];

        kcAddMailSmsPosts($default_email_template);
        if($encounter_id != '')
        {
            $results = $wpdb->get_results("SELECT pre.* ,enc.*
                                            FROM {$precription_table} AS pre 
                                            JOIN {$encounter_table} AS enc ON enc.id = pre.encounter_id
                                            WHERE pre.encounter_id={$encounter_id}");
            // print_r($results);die;
            if($results != null){
                $doctor_id = collect($results)->pluck('doctor_id')->unique('doctor_id')->toArray();
                $patient_id = collect($results)->pluck('patient_id')->unique('patient_id')->toArray();
                $clinic_id = collect($results)->pluck('clinic_id')->unique('clinic_id')->toArray();
                $doctor_data = isset($doctor_id[0]) ? get_user_by('ID',$doctor_id[0]) : '';
                $patient_data = isset($patient_id[0]) ? get_user_by('ID',$patient_id[0]) : '';
                $clinic_data = isset($clinic_id[0]) ? kcClinicDetail($clinic_id[0]) : '';
                ob_start();
            ?>
            <table style="border: 1px solid black; width:100%" >
                <tr>
                    <th style="border: 1px solid black;"><?php echo esc_html__('NAME','kc-lang'); ?></th>
                    <th style="border: 1px solid black;"><?php echo esc_html__('FREQUENCY','kc-lang'); ?></th>
                    <th style="border: 1px solid black;"><?php echo esc_html__('DAYS','kc-lang'); ?></th>
                </tr>
                <?php foreach ($results as $temp) { ?>
                    <tr>
                        <td style="border: 1px solid black;"><?php echo !empty($temp->name) ?$temp->name : '' ; ?></td>
                        <td style="border: 1px solid black;"><?php echo !empty($temp->frequency) ?$temp->frequency : ''; ?></td>
                        <td style="border: 1px solid black;"><?php echo !empty($temp->duration) ?$temp->duration : ''; ?></td>
                    </tr>
                <?php } ?>
            </table>
            <?php
                $data = ob_get_clean();
                $email_data = [
                    'user_email' => isset($patient_data->user_email) ? $patient_data->user_email:'',
                    'doctor_name' => isset($doctor_data->display_name) ? $doctor_data->display_name:'',
                    'clinic_name' => isset($clinic_data->name) ? $clinic_data->name:'',
                    'prescription' => $data,
                    'email_template_type' => 'book_prescription'
                ];
                $status = kcSendEmail($email_data);
                $message = 'Prescription send to successfully';
            }
        }
        return comman_message_response($message);
    }
    public function getList( $request ) {

        global $wpdb;

        $data = kcaValidationToken($request);

		if (!$data['status']) {
			return comman_custom_response($data,401);
        }

        $userid = $data['user_id'];

        $parameters = $request->get_params();

        $limit  = (isset($parameters['limit']) && $parameters['limit'] != '' ) ? $parameters['limit'] : 10;
        $page = (isset($parameters['page']) && $parameters['page'] != '' ) ? $parameters['page'] : 1;
        $offset = ( $page - 1 ) *  $limit;

        // $validation = kcaValidateRequest([
		// 	'encounter_id' 	=> 'required'
        // ], $parameters);

		// if (count($validation)) {
		// 	return comman_message_response($validation[0] , 400);
		// }

        $prescription_table = $wpdb->prefix. 'kc_' . 'prescription';

        $query = "SELECT * FROM $prescription_table";
        
        if(isset($parameters['encounter_id']) && $parameters['encounter_id'] != null){
            $query = $query. " WHERE encounter_id = {$parameters['encounter_id']} ";
        }

        $prescription_count = $wpdb->get_results($query,OBJECT);
        $query = $query. " ORDER BY id ASC LIMIT {$limit} OFFSET {$offset} ";

        $prescription_list = $wpdb->get_results($query,OBJECT);

		// if(count($prescription_list) <= 0) {
		// 	$response = 'Record not found';
        //     return comman_message_response ( $response,400);	
		// } 
        $response['total'] = count($prescription_count);
		$response['data']  = $prescription_list;

        return comman_custom_response($response);

    }

    public function savePrescription( $request ) {

        global $wpdb;

        $data = kcaValidationToken($request);

		if (!$data['status']) {
			return comman_custom_response($data,401);
        }
        
        $userid = $data['user_id'];
        
        $parameters = $request->get_params();
        
        $validation = kcaValidateRequest([
			'name' 		        => 'required',
			'frequency' 		=> 'required',
			'duration' 	        => 'required',
		], $parameters);

		if (count($validation)) {
			return comman_message_response($validation[0] , 400);
        }
        
        $prescription_table = $wpdb->prefix. 'kc_' . 'prescription';
        
        $patient_encounters_table = $wpdb->prefix. 'kc_' . 'patient_encounters';

        $patient_encounters = "SELECT * FROM {$patient_encounters_table} WHERE id = ".$parameters['encounter_id']. ""; 
 
        $patient_encounter = $wpdb->get_row($patient_encounters,OBJECT);
        $patient_id        = $patient_encounter->patient_id;
        
        $temp = array(
            'encounter_id'      => $parameters['encounter_id'],
            'patient_id'        => $patient_id,
			'name' 		        => $parameters['name'],
			'frequency' 		=> $parameters['frequency'],
			'duration' 	        => $parameters['duration'],
            'instruction'	    => $parameters['instruction']
        );
     
        $id	= (isset($parameters['id']) && $parameters['id'] != '' ) ? $parameters['id'] : null;

        if($id == null) {

            $temp['created_at'] = current_time( 'Y-m-d H:i:s' );
            $temp['added_by']   = $userid;

            $wpdb->insert( $prescription_table,$temp);
            $id = $wpdb->insert_id;
            $message = 'Prescription has been added successfully';
        } else {

            $wpdb->update($prescription_table,$temp,array( 'id' => $id ));

            $message = 'Prescription has been updated successfully';

        }
        $prescription_data = $wpdb->get_row("SELECT * FROM {$prescription_table} WHERE id = {$id}", OBJECT );
        return comman_custom_response($prescription_data);
    }

    public function deletePrescription( $request ) {

        global $wpdb;

        $data = kcaValidationToken($request);

		if (!$data['status']) {
			return comman_custom_response($data,401);
        }
        
        $parameters = $request->get_params();

        $prescription_table = $wpdb->prefix. 'kc_' . 'prescription';

        $results = $wpdb->delete( $prescription_table , array ('id' => $parameters['id']) );

		if ( $results ) {
			$message = 'Prescription has been deleted successfully';
		} else {
			$message = 'Prescription delete failed';
		}

		return comman_message_response( $message );

    }
    
}