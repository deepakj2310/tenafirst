<?php

namespace ProApp\filters;

use App\baseClasses\KCBase;
use App\models\KCMedicalHistory;
use App\models\KCPatientEncounter;
use App\models\KCPrescriptionEnconterTemplateModel;
use App\models\KCEncounterTemplateModel;
use App\models\KCEncounterTemplateMappingModel;
use App\models\KCDoctorClinicMappingNewModel;
use TenQuality\WP\Database\QueryBuilder;

class KCProPatientEncounterTemplate extends KCBase
{
    public $db;
    private $medical_history_table_name;
    private $prescription_table_name;
    private $patient_encounters_template_table_name;
    private $template_table_name;

    private $prepare_val;

    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
        $this->prepare_val = '';
        $this->medical_history_table_name = $wpdb->prefix . "kc_medical_history";
        $this->prescription_table_name = $this->db->prefix . "kc_prescription";
        $this->patient_encounters_template_table_name = $this->db->prefix . "kc_patient_encounters_template_mapping";
        $this->template_table_name = $wpdb->prefix . 'kc_patient_encounters_template';


        add_action('kcpro_get_encounter_templates', [$this, 'get_encounter_templates']);
        add_action('kcpro_delete_encounter_temp', [$this, 'delete_encounter_temp']);
        add_action('kcpro_add_encounter_temp', [$this, 'add_encounter_temp']);
        add_action('kcpro_insert_template_to_encounter', [$this, 'insert_template_to_encounter']);


        add_action('kcpro_patient_encounter_template_details', [$this, 'patient_encounter_template_details']);
        add_action('kcpro_medical_history_list_from_template', [$this, 'medical_history_list_from_template']);
        add_action('kcpro_save_encounter_template_medical_history', [$this, 'save_encounter_template_medical_history']);
        add_action('kcpro_delete_encounter_template_medical_history', [$this, 'delete_encounter_template_medical_history']);


        add_action('kcpro_get_encounter_template_prescription_list', [$this, 'get_encounter_template_prescription_list']);
        add_action('kcpro_delete_encounter_template_prescription', [$this, 'delete_encounter_template_prescription']);
        add_action('kcpro_save_encounter_template_prescription', [$this, 'save_encounter_template_prescription']);

        add_action("kcpro_encounter_template_has_permission", [$this, "has_permission"]);
    }

    public function has_permission($args)
    {

        $cap_function_wise =  array(
            "get_encounter_templates" => KIVI_CARE_PRO_PREFIX . "encounters_template_list",
            "medical_history_list_from_template" => KIVI_CARE_PRO_PREFIX . "encounters_template_list",
            "insert_template_to_encounter" => KIVI_CARE_PRO_PREFIX . "encounters_template_list",
            "add_encounter_temp" => KIVI_CARE_PRO_PREFIX . 'encounters_template_add',
            "patient_encounter_template_details" => KIVI_CARE_PRO_PREFIX . "encounters_template_view",
            "save_encounter_template_medical_history" =>  KIVI_CARE_PRO_PREFIX . "encounters_template_edit",
            "delete_encounter_template_medical_history" => KIVI_CARE_PRO_PREFIX . 'encounters_template_edit',
            "delete_encounter_temp" => KIVI_CARE_PRO_PREFIX . 'encounters_template_delete',
            "get_encounter_template_prescription_list" => KIVI_CARE_PRO_PREFIX . "encounters_template_edit",
            "save_encounter_template_prescription" => KIVI_CARE_PRO_PREFIX . "encounters_template_edit",
            "delete_encounter_template_prescription" => KIVI_CARE_PRO_PREFIX . "encounters_template_edit",
        );

        if (!isset($cap_function_wise[$args['fn']])) {
            echo json_encode([
                'status'      => false,
                //                'status_code' => 403,
                'message'     => esc_html__('You don\'t have permission to access', 'kc-lang'),
                'data'        => []
            ]);
            wp_die();
        }
        if (!current_user_can($cap_function_wise[$args['fn']])) {
            echo json_encode([
                'status'      => false,
                //                'status_code' => 403,
                'message'     => esc_html__('You don\'t have permission to access', 'kc-lang'),
                'data'        => []
            ]);
            wp_die();
        }
    }

    public function get_encounter_templates($request)
    {
        do_action("kcpro_encounter_template_has_permission", ['fn' => __FUNCTION__]);
        $condition = "";
        if (in_array($this->getLoginUserRole(), [$this->getReceptionistRole(), $this->getClinicAdminRole()])) {

            $clinic_id = $this->getLoginUserRole() == $this->getReceptionistRole() ? kcGetClinicIdOfReceptionist() :  kcGetClinicIdOfClinicAdmin();
            $ids = KCDoctorClinicMappingNewModel::builder()->select("*")->where([
                "clinic_id" => $clinic_id
            ])->get(OBJECT, function ($row) {
                return (int)$row->doctor_id;
            });
            $ids[] = get_current_user_id();

            $condition = "added_by IN (" . implode(",", $ids) . ")";
        } else if ($this->getLoginUserRole() == $this->getDoctorRole()) {
            $condition = "added_by IN (" .  get_current_user_id() . ")";
        }

        $patient_encounter = (new KCPatientEncounter)->get_var(['id' => $request['encounter_id']], 'template_id');

        $result = KCEncounterTemplateMappingModel::builder()->select("*")->having($condition)->get();

        if (count($result) > 0) {
            wp_send_json_success(array('list' => $result, 'default' => $patient_encounter ?? ""));
        }
        wp_send_json_success([]);
    }
    public function add_encounter_temp($request_data)
    {
        do_action("kcpro_encounter_template_has_permission", ['fn' => __FUNCTION__]);
        $model = KCEncounterTemplateMappingModel::insert(["encounters_template_name" => $request_data['template_name'], "added_by" => $request_data['added_by']]);
        if ($model->id != false) {
            wp_send_json_success(["id" => $model->id, 'message' => __("Encounter Template Added Successfully", 'kc-lang')]);
        }
        wp_send_json_error();
    }
    public function delete_encounter_temp($request_data)
    {
        do_action("kcpro_encounter_template_has_permission", ['fn' => __FUNCTION__]);
        if (KCEncounterTemplateMappingModel::find($request_data['id'])->delete()) {
            echo json_encode(['status' => true, 'message' => __("Prescription has been deleted successfully", '')]);
            die;
        }
        echo json_encode(['status' => false, 'message' => __("Not data removed", '')]);
        die;
    }
    public function insert_template_to_encounter($request_data)
    {

        do_action("kcpro_encounter_template_has_permission", ['fn' => __FUNCTION__]);
        global $wpdb;
        $medical_history = $wpdb->prefix . 'kc_medical_history';


        (new KCPatientEncounter())->update([
            "template_id" => $request_data['encounterTemplateID']
        ], ["id" => (int)$request_data['encounter_id']]);

        // $encounter_detail

        $builder = QueryBuilder::create();
        $builder
            ->from("kc_medical_history")
            ->where(
                [
                    "encounter_id" => $request_data['encounter_id'],
                    "is_from_template" => 1
                ]
            )
            ->delete();
        $builder
            ->from("kc_prescription")
            ->where(
                [
                    "encounter_id" => $request_data['encounter_id'],
                    "is_from_template" => 1
                ]
            )
            ->delete();


        $result = KCEncounterTemplateModel::builder()->select("*")->where(['encounters_template_id' => $request_data['encounterTemplateID']])->get();
        $medical_histories = [];

        $current_user_id = get_current_user_id();
        foreach ($result as $key => $val) {
            $medical_histories[] = [
                'encounter_id' => $request_data['encounter_id'],
                'patient_id' => $request_data['patientID'],
                'title' => '"' . $val->clinical_detail_val . '"',
                'type' => '"' . $val->clinical_detail_type . '"',
                'added_by' => $current_user_id,
                'created_at' =>  '"' . date("Y-m-d H:i:s") . '"',
                "is_from_template" => 1
            ];
        }



        $placeholders = array_fill(0, count($medical_histories[0]), '%s');
        $placeholders = '(' . implode(', ', $placeholders) . ')';

        $values = array();
        foreach ($medical_histories as $row) {
            $values[] = array_values($row);
        }

        $values = array_map(function ($row) use ($placeholders) {
            return vsprintf($placeholders, $row);
        }, $values);

        $sql = "INSERT INTO {$medical_history} (`encounter_id`, `patient_id`, `title`,`type` ,`added_by` ,`created_at`,`is_from_template` ) VALUES " . implode(', ', $values);


        $wpdb->query($sql);

        $prescription_table = $wpdb->prefix . 'kc_prescription';
        $prescriptions_from_enconter_template = KCPrescriptionEnconterTemplateModel::builder()->select("*")->where(['encounters_template_id' => $request_data['encounterTemplateID']])->get();
        $prescriptions = [];
        foreach ($prescriptions_from_enconter_template as $prescription) {
            $prescriptions[] = [
                'encounter_id' => $request_data['encounter_id'],
                'patient_id' => $request_data['patientID'],
                'name' => '"' . $prescription->name . '"',
                'frequency' => '"' . $prescription->frequency . '"',
                'duration' => $prescription->duration,
                'instruction' =>  '"' . $prescription->instruction . '"',
                'added_by' => $current_user_id,
                'created_at' =>  '"' . date("Y-m-d H:i:s") . '"',
                "is_from_template" => 1
            ];
        }


        $placeholders = array_fill(0, count($prescriptions[0]), '%s');
        $placeholders = '(' . implode(', ', $placeholders) . ')';

        $values = array();
        foreach ($prescriptions as $row) {
            $values[] = array_values($row);
        }

        $values = array_map(function ($row) use ($placeholders) {
            return vsprintf($placeholders, $row);
        }, $values);

        $sql = "INSERT INTO {$prescription_table} (`encounter_id`, `patient_id`, `name`,`frequency` ,`duration` ,`instruction`,`added_by`,`created_at`,`is_from_template` ) VALUES " . implode(', ', $values);

        $wpdb->query($sql);

        wp_send_json_success();
    }
    public function medical_history_list_from_template($request_data)
    {
        do_action("kcpro_encounter_template_has_permission", ['fn' => __FUNCTION__]);
        $data = [];
        $result = KCEncounterTemplateModel::builder()
            ->select("clinical_detail_type as type ,clinical_detail_val as title , id, encounters_template_id,added_by")
            ->where(
                [
                    "encounters_template_id" => $request_data['encounter_id']
                ]
            )->get();

        foreach ($result as $key => $value) {
            $data[$value->type][] = $value;
        }

        echo json_encode([
            "status" => true,
            "message" => __("Medical history saved successfully"),
            "data" => $data
        ]);
        die;
    }
    public function patient_encounter_template_details($request_data)
    {
        do_action("kcpro_encounter_template_has_permission", ['fn' => __FUNCTION__]);
        $data = [];
        $result = KCEncounterTemplateModel::builder()
            ->select("clinical_detail_type as type ,clinical_detail_val as title , id, encounters_template_id,added_by")
            ->where(
                [
                    "encounters_template_id" => $request_data['encounter_id']
                ]
            )->get();

        foreach ($result as $key => $value) {
            $data[$value->type][] = $value;
        }

        echo json_encode([
            "status" => true,
            "message" => __("Medical history saved successfully"),
            "data" => $data
        ]);
        die;
    }
    public function save_encounter_template_medical_history($request_data)
    {
        do_action("kcpro_encounter_template_has_permission", ['fn' => __FUNCTION__]);
        $result = KCEncounterTemplateModel::insert([
            "encounters_template_id" => $request_data['encounter_id'],
            "clinical_detail_type" => $request_data['type'],
            "clinical_detail_val" => $request_data['title'],
        ]);

        echo json_encode([
            "status" => true,
            "message" => __("Medical history saved successfully"),
            "data" => array_pop($result->builder()->select("*")->where(['id' => $result->id])->get(OBJECT, function ($row) {
                $row->title = $row->clinical_detail_val;
                $row->type = $row->clinical_detail_type;
                unset($row->clinical_detail_val);
                unset($row->clinical_detail_type);

                return $row;
            }))
        ]);
        die;
    }
    public function delete_encounter_template_medical_history($request)
    {
        do_action("kcpro_encounter_template_has_permission", ['fn' => __FUNCTION__]);
        $status = false;
        $massage = '';
        $encounter_template_model = KCEncounterTemplateModel::find($request['id']);

        if (!is_null($encounter_template_model)) {
            if ($encounter_template_model->delete() > 0) {
                $status = true;
                $massage = __("Medical history deleted successfully", '');
            } else {
                $status = false;
                $massage = __("SomeThing Went Wrong", '');
            }
        } else {
            $massage = __("Medical history Not Found ", '');
        }


        echo json_encode([
            "status" => $status,
            "message" => $massage
        ]);
        die;
    }
    public function get_encounter_template_prescription_list($request)
    {
        do_action("kcpro_encounter_template_has_permission", ['fn' => __FUNCTION__]);
        $prescriptions_name_dropdown_options = collect($this->db->get_results("SELECT `label` as id, label FROM {$this->db->prefix}kc_static_data WHERE status = 1 AND `type`='prescription_medicine'"))->unique('id')->toArray();

        echo json_encode([
            'status'  => true,
            'message' =>  esc_html__('Medical history', 'kc-lang'),
            'data'    => KCPrescriptionEnconterTemplateModel::builder()->select("*")->where([
                "encounters_template_id" => $request["encounter_id"]
            ])->get(OBJECT, function ($row) {
                $row->name = [
                    "label" => $row->name,
                    "id" => $row->name,
                ];
                return $row;
            }),
            'prescriptionNames'  => $prescriptions_name_dropdown_options
        ]);
        die;
    }
    public function save_encounter_template_prescription($request)
    {
        do_action("kcpro_encounter_template_has_permission", ['fn' => __FUNCTION__]);
        $val = [
            'encounters_template_id' => (int)$request['encounter_id'],
            'name'         => $request['name']['id'],
            'frequency'    => $request['frequency'],
            'duration'     => (int)$request['duration'],
            'instruction'  => $request['instruction'],
            'added_by'     => get_current_user_id()
        ];

        if (isset($request['id'])) {
            $PrescriptionModelObject = KCPrescriptionEnconterTemplateModel::find($request['id']);
            if ($PrescriptionModelObject->update($val) > 0) {
                $massage = __("Prescription has been updated successfully", '');
            } else {
                $massage = __("Same Value Already Present", '');
            }
            $PrescriptionModelObject =  KCPrescriptionEnconterTemplateModel::find($request['id'])->update($val);
        } else {
            $PrescriptionModelObject =  KCPrescriptionEnconterTemplateModel::insert($val);
            $massage = __("Prescription has been added successfully", '');
        }

        if ($PrescriptionModelObject->id != false || isset($request['id'])) {
            echo json_encode([
                'status'  => true,
                'message' => $massage,
                'data'    => array_pop(KCPrescriptionEnconterTemplateModel::builder()->select("*")->where(['id' => $PrescriptionModelObject->id ?? $request['id']])->get(OBJECT, function ($row) {
                    $row->name = [
                        "label" => $row->name,
                        "id" => $row->name,
                    ];
                    return $row;
                }))
            ]);
            die;
        }
    }
    public function delete_encounter_template_prescription($request)
    {
        do_action("kcpro_encounter_template_has_permission", ['fn' => __FUNCTION__]);
        if (KCPrescriptionEnconterTemplateModel::find($request['id'])->delete()) {
            echo json_encode(['status' => true, 'message' => __("Prescription has been deleted successfully", '')]);
            die;
        }
        echo json_encode(['status' => false, 'message' => __("Not data removed", '')]);
        die;
    }
}
