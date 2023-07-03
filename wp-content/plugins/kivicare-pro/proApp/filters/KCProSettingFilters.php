<?php 

namespace ProApp\filters ;
use App\baseClasses\KCBase;

class KCProSettingFilters  extends KCBase {
    public function __construct() {
        add_filter('kcpro_get_encounter_setting', [$this, 'getEncounterModule']);
        add_filter('kcpro_save_prescription_setting', [$this, 'savePrescriptionModule']);
        add_filter('kcpro_get_prescription_list', [$this, 'getPrescriptionModule']);
        add_filter('kcpro_get_list', [$this, 'getList']);
        add_filter('kcpro_save_pro_option_value', [$this, 'saveProOptionValue'], 10, 2);
        add_filter('kcpro_save_wordpress_logo', [$this, 'saveWordpresLogo']);
        add_filter('kcpro_get_wordpress_logo', [$this, 'getWordpresLogo']);
    }
    public function getList(){
        $prefix = KIVI_CARE_PRO_PREFIX;
        $encounter_modules = get_option($prefix . 'enocunter_modules');
		if($encounter_modules !== '') {
            $data = json_decode($encounter_modules);
			$status = true ;
		} else {
			$data = [] ;
			$status = false ;
        }
        return [
            'status' => $status,
            'data'    => $data
        ];
    }
    public function getEncounterModule($settings){
        if(isset($settings)){
            $prefix = KIVI_CARE_PRO_PREFIX;
            update_option( $prefix. 'enocunter_modules', json_encode($settings['encounter_module']));
            return [
                'status' => true,
                'message' => esc_html__('Setting update successfully', 'kcp-lang')

            ];
        }else{
            return [
                'status' => false,
                'message' => esc_html__('Failed to update  setting', 'kiviCare-clinic-&-patient-management-system-pro')
            ];
        }
    }
    public function savePrescriptionModule($settings){
        if(isset($settings)){
            $prefix = KIVI_CARE_PRO_PREFIX;
            update_option( $prefix. 'prescription_module', json_encode($settings['prescription_module']));
            return [
                'status' => true,
                'message' => esc_html__('Setting update successfully', 'kiviCare-clinic-&-patient-management-system-pro')

            ];
        }else{
            return [
                'status' => false,
                'message' => esc_html__('Failed to update  setting', 'kiviCare-clinic-&-patient-management-system-pro')
            ];
        }
    }
    public function getPrescriptionModule($settings){
        $prefix = KIVI_CARE_PRO_PREFIX;
        $prescription_modules = get_option($prefix . 'prescription_module');
		if($prescription_modules !== '') {
            $data = json_decode($prescription_modules);
			$status = true ;
		} else {
			$data = [] ;
			$status = false ;
        }
        return [
            'status' => $status,
            'data'    => $data
        ];
    }

    public function saveProOptionValue($request_data,$setting_name){
        if(isset($request_data['text'])){
            update_option(KIVI_CARE_PRO_PREFIX.$setting_name,$request_data['text']);
        }else if(isset($request_data['status'])){
            update_option(KIVI_CARE_PRO_PREFIX.$setting_name,$request_data['status']);
        }
    }

    public function saveWordpresLogo($requestData){

        if(isset($requestData['status'])){
            $requestData['status'] = $requestData['status'] == '1' ? "on"  : "off";
            update_option( KIVI_CARE_PRO_PREFIX . 'wordpress_logo_status',$requestData['status'] );
            return ['status' => true,'data' => $requestData['status'] == 'on'];
        }
        try{
            if(isset($requestData['wordpress_logo'])){
                update_option( KIVI_CARE_PRO_PREFIX . 'wordpress_logo',$requestData['wordpress_logo'] );
                $url = wp_get_attachment_url($requestData['wordpress_logo']);
                return [
                    'data'=> $url,
                    'status' => true,
                    'message' => esc_html__('Wordpress logo updated', 'kc-lang')
                ];

            }
        }catch (Exception $e) {
            return [
                'status' => false,
                'message' => esc_html__('Failed to update Wordpress logo', 'kc-lang')
            ];
        }
    }

    public function getWordpresLogo(){

        return [
            'status' => true,
            'data' =>[
                'status' => kcWordpressLogostatusAndImage('status') ? 1 : 0,
                'logo' => kcWordpressLogostatusAndImage('image')
            ]
        ];
    }
}