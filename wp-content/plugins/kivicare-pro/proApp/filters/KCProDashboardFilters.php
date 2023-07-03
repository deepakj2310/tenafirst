<?php 

namespace ProApp\filters ;
use App\baseClasses\KCBase;
use App\models\KCAppointment;
use App\models\KCBill;
use App\models\KCClinic;
use App\models\KCDoctorClinicMapping;
use App\models\KCPatientClinicMapping;
use App\models\KCPatientEncounter;
use App\models\KCReceptionistClinicMapping;
class KCProDashboardFilters  extends KCBase {
    public $db;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        add_filter('kcpro_get_doctor_dashboard_detail', [$this, 'getDashboardDetail']);
    }
    public function getDashboardDetail($details){
        global $wpdb;
        $this->db = $wpdb;
        if($this->getDoctorRole() === $this->getLoginUserRole()){

            $todayAppointments = $appointments = [];
            if(kcCheckPermission('dashboard_total_today_appointment') || kcCheckPermission('dashboard_total_appointment')){
                $appointments = collect(( new KCAppointment() )->get_by(['doctor_id' => (int)$details['user_id']]));
                if(kcCheckPermission('dashboard_total_today_appointment')){
                    $today = date("Y-m-d");
                    $todayAppointments= $appointments->where('appointment_start_date', $today);
                }
                if(!kcCheckPermission('dashboard_total_appointment')){
                    $appointments = [];
                }
            }

            $service_count = 0;
            if(kcCheckPermission('dashboard_total_service')){
                $service_table = $this->db->prefix . 'kc_service_doctor_mapping';
                if(kcDoctorTelemedServiceEnable($details['user_id'])){
                    $service = "SELECT  count(*) FROM {$service_table} WHERE `doctor_id` = ".(int)$details['user_id'];
                }else{
                    $service_name_table = $this->db->prefix . 'kc_services';
                    $service = "SELECT  count(*) FROM {$service_table} join {$service_name_table} on {$service_name_table}.id= {$service_table}.service_id  WHERE {$service_table}.doctor_id = ".(int)$details['user_id']. " AND {$service_table}.telemed_service != 'yes'";
                }
                $service_count = $this->db->get_var( $service);
            }

            $patient_count = 0;
            if(kcCheckPermission('dashboard_total_patient')){
                $patient_count  = count(kcDoctorPatientList());
            }


            $data = [
                'patient_count' => $patient_count,
                'appointment_count' => count($appointments),
                'today_count'=>count($todayAppointments),
                'service' => $service_count,
            ];
            return [
                'data'=> $data,
                'status' => true,
                'message' => esc_html__('doctor dashboard', 'kiviCare-clinic-&-patient-management-system-pro')
            ];
        }else if('administrator' ===$this->getLoginUserRole()){

            $patients = [];
            if(kcCheckPermission('dashboard_total_patient')){
                $patients = get_users([
                    'role' => $this->getPatientRole(),
                    'fields' => ['ID']
                ]);
            }

            $doctors = [];
            if(kcCheckPermission('dashboard_total_doctor')){
                $doctors = get_users([
                    'role' => $this->getDoctorRole(),
                    'fields' => ['ID']
                ]);
            }


            $appointment = 0;
            if(kcCheckPermission('dashboard_total_appointment')){
                $appointment = collect((new KCAppointment())->get_all())->count();
            }

            $bills = 0;
            if(kcCheckPermission('dashboard_total_revenue')){
                $config = kcGetModules();
                $modules = collect($config->module_config)->where('name','billing')->where('status', 1)->count();
                if($modules > 0){
                    if(!empty(get_option(KIVI_CARE_PREFIX.'reset_revenue'))){
                        $reset_revenue_date = get_option(KIVI_CARE_PREFIX.'reset_revenue');
                        $bills = $this->db->get_var("SELECT SUM(actual_amount) FROM {$this->db->prefix}kc_patient_encounters INNER JOIN {$this->db->prefix}kc_bills ON {$this->db->prefix}kc_patient_encounters.id = {$this->db->prefix}kc_bills.encounter_id WHERE {$this->db->prefix}kc_bills.payment_status = 'paid' AND {$this->db->prefix}kc_bills.created_at > '{$reset_revenue_date}'");
                    }else{
                        $bills = $this->db->get_var("SELECT SUM(actual_amount) FROM {$this->db->prefix}kc_patient_encounters INNER JOIN {$this->db->prefix}kc_bills ON {$this->db->prefix}kc_patient_encounters.id = {$this->db->prefix}kc_bills.encounter_id WHERE {$this->db->prefix}kc_bills.payment_status = 'paid'");
                    }
                }
            }

            $revenue = !empty($bills) ? (int)$bills : 0;
            $data = [
                'patient_count' => count($patients),
                'doctor_count'  => count($doctors),
                'appointment_count' => $appointment,
                'revenue'   => (!empty($details['clinic_prefix']) ? $details['clinic_prefix'] : '').$revenue.(!empty($details['clinic_postfix']) ? $details['clinic_postfix'] : ''),
            ];
            return [
                'data'=> $data,
                'status' => true,
            ];
        }else if($this->getLoginUserRole() === $this->getClinicAdminRole()){

            return [
                'data'=> $this->clinicReceptionistData(kcGetClinicIdOfClinicAdmin(),$details),
                'status' => true,
            ];
           
        }else if($this->getLoginUserRole() === $this->getReceptionistRole()){

            return [
                'data'=> $this->clinicReceptionistData(kcGetClinicIdOfReceptionist(),$details),
                'status' => true,
            ];
        }else{
            wp_die();
        }
    }

    public function clinicReceptionistData($clinic_id,$details){

        global $wpdb;
        $clinic_id = (int)$clinic_id;

        $doctors = [];
        if(kcCheckPermission('dashboard_total_doctor')){
            $doctors =  collect(( new KCDoctorClinicMapping() )->get_by(['clinic_id' => (int)$clinic_id ]))->pluck('doctor_id')->toArray();
            if(!empty($doctors)){
                $doctors = get_users(['include' =>  $doctors ,'role' => $this->getDoctorRole(),'fields'=>['ID']]);
            }
        }

        $patients = [];
        if(kcCheckPermission('dashboard_total_patient')){
            $patients =  collect(( new KCPatientClinicMapping() )->get_by(['clinic_id' => (int)$clinic_id ]))->pluck('patient_id')->toArray();
            if(!empty($patients)){
                $patients = get_users(['include' =>  $patients ,'role' => $this->getPatientRole(),'fields'=>['ID']]);
            }
        }

        $appointments = 0;
        if(kcCheckPermission('dashboard_total_appointment')){
            $appointments = (new KCAppointment())->get_var(['clinic_id' => (int)$clinic_id ],'count(*)');
        }

        $revenue = 0;
        if(kcCheckPermission('dashboard_total_revenue')){
            $config = kcGetModules();
            $modules = collect($config->module_config)->where('name','billing')->where('status', 1)->count();
            if($modules > 0){
                if(!empty(get_option(KIVI_CARE_PREFIX.'reset_revenue'))){
                    $reset_revenue_date = get_option(KIVI_CARE_PREFIX.'reset_revenue');
                    $bills = $this->db->get_var("SELECT SUM(actual_amount) FROM {$this->db->prefix}kc_patient_encounters INNER JOIN {$this->db->prefix}kc_bills ON {$this->db->prefix}kc_patient_encounters.id = {$this->db->prefix}kc_bills.encounter_id WHERE {$this->db->prefix}kc_bills.payment_status = 'paid' AND {$this->db->prefix}kc_patient_encounters.clinic_id = {$clinic_id} AND {$this->db->prefix}kc_bills.created_at > '{$reset_revenue_date}'");
                }else{
                    $bills = $this->db->get_var("SELECT SUM(actual_amount) FROM {$this->db->prefix}kc_patient_encounters INNER JOIN {$this->db->prefix}kc_bills ON {$this->db->prefix}kc_patient_encounters.id = {$this->db->prefix}kc_bills.encounter_id WHERE {$this->db->prefix}kc_bills.payment_status = 'paid' AND {$this->db->prefix}kc_patient_encounters.clinic_id = {$clinic_id} ");
                }

                $revenue = !empty($bills) ? (int)$bills : 0;
            }
        }

        return  [
            'doctor_count'=>count($doctors),
            'patient_count' => count($patients),
            'appointment_count' => $appointments,
            'revenue'   => (!empty($details['clinic_prefix']) ? $details['clinic_prefix'] : '').$revenue.(!empty($details['clinic_postfix']) ? $details['clinic_postfix'] : ''),
        ];
    }
}