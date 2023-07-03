<?php

namespace ProApp\filters;

use App\models\KCAppointment;
use App\models\KCPatientEncounter;
use App\models\KCPatientReport;
use App\baseClasses\KCBase;
use WP_User;

class KCProPatientReport extends KCBase
{

    public $db;
    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;

        add_filter('kcpro_upload_patient_report', [$this, 'uploadPatientReport']);
        add_filter('kcpro_get_patient_report', [$this, 'getPatientReport']);
        add_filter('kcpro_view_patient_report', [$this, 'viewPatientReport']);
        add_filter('kcpro_delete_patient_report', [$this, 'deletePatientReport']);
        add_action('kcpro_edit_patient_report', [$this, 'editPatientReport']);
    }


    public function uploadPatientReport($formdata)
    {
        $patient_report_table = new KCPatientReport;
        $formdata['upload_data']['name'] = esc_sql(trim($formdata['upload_data']['name']));
        $same_file_name_exists = $this->db->get_var("SELECT name FROM {$this->db->prefix}kc_patient_medical_report WHERE name='{$formdata['upload_data']['name']}'");
        if (!empty($same_file_name_exists)) {
            return [
                'status' => false,
                'data' => '',
                'message' => esc_html__('Same medical file name already exists,please enter different file name', 'kiviCare-clinic-&-patient-management-system-pro'),
            ];
        }

        $data = array(
            'name' => $formdata['upload_data']['name'],
            'patient_id' => $formdata['upload_data']['patient_id'],
            'date' => $formdata['upload_data']['date'],
            'upload_report' => $formdata['upload_data']['upload_report'],

        );
        $result = $patient_report_table->insert($data);
        if($result){
            return [
                'status' => true,
                'data'=> $formdata['upload_data']['patient_id'],
                'message' => esc_html__('Report Added Successfully', 'kiviCare-clinic-&-patient-management-system-pro'),
            ];
        }else{
            return [
                'status' => false,
                'data'=> $formdata['upload_data']['patient_id'],
                'message' => esc_html__('Report Not Added', 'kiviCare-clinic-&-patient-management-system-pro'),
            ];
        }
        wp_die();
    }
    public function getPatientReport($data)
    {

        if (isset($data)) {
            $data['pid'] = (int)$data['pid'];
            if (!empty($data['report_id'])) {
                $reports = collect((new KCPatientReport())->get_by(['id' => (int)$data['report_id']]));
                return [
                    'data' => $reports,
                    'status' => true,
                ];
            }
            $current_user_role = $this->getLoginUserRole();
            $validate = true;
            if ($current_user_role == $this->getClinicAdminRole()) {
                $clinic_id = kcGetClinicIdOfClinicAdmin();
                $get_patient_clinic_id = $this->db->get_var("SELECT clinic_id FROM {$this->db->prefix}kc_patient_clinic_mappings WHERE patient_id={$data['pid']}");
                if ($clinic_id !== (int)$get_patient_clinic_id) {
                    $validate = false;
                }
            } elseif ($current_user_role == $this->getReceptionistRole()) {
                $clinic_id = kcGetClinicIdOfReceptionist();
                $get_patient_clinic_id = $this->db->get_var("SELECT clinic_id FROM {$this->db->prefix}kc_patient_clinic_mappings WHERE patient_id={$data['pid']}");
                if ($clinic_id !== (int)$get_patient_clinic_id) {
                    $validate = false;
                }
            } elseif ($current_user_role == $this->getDoctorRole()) {
                $all_user = kcDoctorPatientList();
                if (!in_array($data['pid'], $all_user)) {
                    $validate = false;
                }
            } elseif ($current_user_role == $this->getPatientRole()) {
                if (get_current_user_id() !== $data['pid']) {
                    $validate = false;
                }
            }
            if (!$validate) {
                return [
                    'status' => false,
                    'status_code' => 403,
                    'message' => esc_html__('You don\'t have a permission to access', 'kc-lang'),
                    'data' => []
                ];
            }
            $reports = collect((new KCPatientReport())->get_by(['patient_id' => (int)$data['pid']]))->map(function ($v) {
                $v->date = !empty($v->date) ? date_format(date_create($v->date), "Y-m-d") :  $v->date;
                return $v;
            });
            return [
                'data' => $reports,
                'status' => true,
            ];
        } else {
            return [
                'data' => [],
                'status' => false,
            ];
        }
    }

    public function viewPatientReport($view)
    {
        $reports = collect((new KCPatientReport())->get_by(['patient_id' => (int)$view['pid'], 'id' => (int)$view['docid']]));
        $url = wp_get_attachment_url($reports[0]->upload_report);
        return [
            'data' => $url,
            'status' => true,
        ];
    }

    public function deletePatientReport($data)
    {
        $report = new KCPatientReport();
        if (isset($data['report_id'])) {
            $report->delete(['id' => (int)$data['report_id']]);
            return [
                'status' => true,
                'message' => esc_html__('Report Deleted Successfully', 'kiviCare-clinic-&-patient-management-system-pro')
            ];
        } else {
            return [
                'status' => false,
                'message' => esc_html__('Report not Deleted Successfully', 'kiviCare-clinic-&-patient-management-system-pro')
            ];
        }
    }
    public function editPatientReport($req_data)
    {
        $patient_report_table = new KCPatientReport();

        $result =  $patient_report_table->update(array('name' => sanitize_text_field($req_data['name']),  'date' => sanitize_text_field($req_data['date'])), array('id' => $req_data['id']));

        if ($result !== false)
            wp_send_json_success(array(
                'message' => esc_html__('Report Update Successfully', 'kiviCare-clinic-&-patient-management-system-pro')
            ));


        wp_send_json_success([
            'message' => esc_html__('SomeThing Wents Wrongs', 'kiviCare-clinic-&-patient-management-system-pro'),
        ]);
    }
    public function kcCheckUserHasPermission()
    {
        global $current_user;
        $current_user_role =  $current_user->roles;

        if ($current_user_role == $this->getClinicAdminRole()) {
            $clinic_id = kcGetClinicIdOfClinicAdmin();
            $get_patient_clinic_id = $this->db->get_var("SELECT clinic_id FROM {$this->db->prefix}kc_patient_clinic_mappings WHERE patient_id={$data['pid']}");
            if ($clinic_id !== (int)$get_patient_clinic_id) {
                $validate = false;
            }
        } elseif ($current_user_role == $this->getReceptionistRole()) {
            $clinic_id = kcGetClinicIdOfReceptionist();
            $get_patient_clinic_id = $this->db->get_var("SELECT clinic_id FROM {$this->db->prefix}kc_patient_clinic_mappings WHERE patient_id={$data['pid']}");
            if ($clinic_id !== (int)$get_patient_clinic_id) {
                $validate = false;
            }
        } elseif ($current_user_role == $this->getDoctorRole()) {
            $all_user = kcDoctorPatientList();
            if (!in_array($data['pid'], $all_user)) {
                $validate = false;
            }
        } elseif ($current_user_role == $this->getPatientRole()) {
            if (get_current_user_id() !== $data['pid']) {
                $validate = false;
            }
        }
    }
}
