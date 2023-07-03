<?php


namespace Includes\model;

use Includes\baseClasses\KCAppModel;

class KCReviewModel extends KCAppModel
{

    public function __construct()
    {
        parent::__construct('patient_review');
    }

    /**
     * It gets the reviews for a doctor, and returns an array of objects with the review, the patient's
     * name, the patient's image, the doctor's name, and the doctor's image
     * 
     * @param data an array of data that you want to pass to the model.
     * 
     * @return The review_get function is returning an array of objects.
     */
    public function review_get($data)
    {
        $output = $this->get_by(
            array('doctor_id' => $data['doctor_id']),
            '=',
            false,
            array(
                'orderBy' => array('by' => 'created_at', 'order' => 'DESC'),
                'limit' => $data['limit'], 'offset' => $data['offset']
            )
        );
        if ($output == false)
            return false;

        return array_map(function ($item) {
            $patient_img =  wp_get_attachment_url(get_user_meta($item->patient_id, 'patient_profile_image', true));
            $doctor_img =  wp_get_attachment_url(get_user_meta($item->doctor_id, 'doctor_profile_image', true));

            $item->id = (int)$item->id;
            $item->review = (int)$item->review;
            $item->patient_id = (int)$item->patient_id;
            $item->patient_name =  get_user_by('id', $item->patient_id)->display_name;
            $item->patient_img = $patient_img == false ? null : $patient_img;

            $item->doctor_id = (int)$item->doctor_id;
            $item->doctor_name =  get_user_by('id', $item->doctor_id)->display_name;
            $item->doctor_img =  $doctor_img == false ? null : $doctor_img;
            return $item;
        }, $output);
    }

    public function get_review_object($docterID = -1, $patientID = -1)
    {
        global $wpdb;
        $result = $wpdb->get_row("SELECT * FROM `wp_kc_patient_review` WHERE `doctor_id` LIKE {$docterID} AND `patient_id` LIKE $patientID", \OBJECT);

        if (is_null($result)) {
            return null;
        }

        $patient_img =  wp_get_attachment_url(get_user_meta($result->patient_id, 'patient_profile_image', true));
        $doctor_img =  wp_get_attachment_url(get_user_meta($result->doctor_id, 'doctor_profile_image', true));

        $result->id = (int)$result->id;
        $result->review = (int)$result->review;
        $result->patient_name =  get_user_by('id', $result->patient_id)->display_name;
        $result->patient_img = $patient_img == false ? null : $patient_img;
        $result->patient_id = (int)$result->patient_id;

        $result->doctor_id = (int)$result->doctor_id;
        $result->doctor_name =  get_user_by('id', $result->doctor_id)->display_name;
        $result->doctor_img =  $doctor_img == false ? null : $doctor_img;

        return $result;
    }

    /**
     * It returns the average rating for a given doctor
     * 
     * @param docterID The ID of the doctor you want to get the average rating for.
     */
    public function get_docter_avg_rating($docterID = 0)
    {
        return (float)sprintf("%.2f", $this->get_var(array('doctor_id' => $docterID), "AVG(review) as avg_rating"));
    }
}
