<?php

namespace Includes\Controllers\Api;

use Includes\baseClasses\KCAppModel;
use Includes\baseClasses\KCBase;
use Includes\model\KCReviewModel;
use WP_Error;
use WP_REST_Response;
use WP_REST_Server;


class KCReviewController extends KCBase
{

    public $module = 'review';
    public $tableName;
    public $nameSpace;

    private $user;
    private $requestParam;

    public function __construct()
    {
        global $wpdb;
        $this->nameSpace = KIVICARE_API_NAMESPACE;

        $this->tableName = $wpdb->prefix . "kc_patient_review";

        add_action('rest_api_init', function () {
            register_rest_route($this->nameSpace . '/api/v1/' . $this->module, '/add', array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'review_add'],
                'permission_callback' => [$this, 'hasPermission'],
                'args'                => array(
                    'rating'      => array(
                        'description' => __('Review Rating For Of the Patients', 'kivicare-api'),
                        'required'    => true,
                        'type'        => 'int',
                        'sanitize_callback' => 'absint',
                    ),
                    'review'      => array(
                        'description' => __('Review Message For Of the Patients', 'kivicare-api'),
                        'required'    => false,
                        'type'        => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'doctor_id'   => array(
                        'required'    => true,
                        'type'        => 'int',
                        'sanitize_callback' => 'absint',
                    ),

                    'updated_at' => array(
                        'default' => current_time('Y-m-d H:i:s'),
                    ),
                ),
            ));
            register_rest_route($this->nameSpace . '/api/v1/' . $this->module, '/edit', array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'review_edit'],
                'permission_callback' => [$this, 'hasPermission'],
                'args'                => array(
                    'review_id'   => array(
                        'required'    => true,
                        'type'        => 'int',
                        'sanitize_callback' => 'absint',
                    ),
                    'rating'      => array(
                        'description' => __('Review Rating For Of The Docter', 'kivicare-api'),
                        'required'    => true,
                        'type'        => 'int',
                        'sanitize_callback' => 'absint',
                        'default' => 1,
                    ),
                    'review'      => array(
                        'description' => __('Review Message For Of The Docter', 'kivicare-api'),
                        'required'    => false,
                        'type'        => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                        'default' => "",
                    ),
                    'updated_at' => array(
                        'default' => current_time('Y-m-d H:i:s'),
                    ),
                ),
            ));
            register_rest_route($this->nameSpace . '/api/v1/' . $this->module, '/delete', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => [$this, 'review_delete'],
                'permission_callback' => [$this, 'hasPermission'],
                'args'                => array(
                    'review_id'   => array(
                        'required'    => true,
                        'type'        => 'int',
                        'sanitize_callback' => 'absint',
                    ),
                ),
            ));
            register_rest_route($this->nameSpace . '/api/v1/' . $this->module, '/get', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'review_get'],
                'permission_callback' => [$this, 'hasPermission'],
                'args'                => array(
                    'limit'   => array(
                        'default' => 8,
                        'required'    => false,
                        'type'        => 'int',
                        'sanitize_callback' => 'absint',
                    ),
                    'page' => array(
                        'default' => 1,
                        'required'    => false,
                        'type'        => 'int',
                        'sanitize_callback' => 'absint',
                    ),
                    'doctor_id'   => array(
                        'required'    => true,
                        'type'        => 'int',
                        'sanitize_callback' => 'absint',
                    ),
                ),
            ));
        });
    }
    public function review_add($request)
    {

        $temp = [
            'review' => $this->requestParam['rating'],
            'review_description' => $this->requestParam['review'] ?? "",
            'doctor_id' => $this->requestParam['doctor_id'],
            'updated_at' => $this->requestParam['updated_at'],
            'patient_id' => $this->user['id']
        ];

        $condition = [
            'patient_id' => $this->user['id'],
            'doctor_id' => $temp['doctor_id'],
        ];
        $result =  (new KCReviewModel())->insertOnCondition($temp, $condition);

        if ($result == 0) {
            return comman_message_response(__("You Can Only Add One Review For That Particular Docter", 'kivicare-api'), 200);
        }

        if ($result !== false)
            return comman_message_response(__("Thank You For Your Review", 'kivicare-api'), 200);
        else
            return comman_message_response(__("Something Went Wrong", 'kivicare-api'), 200);
    }

    public function review_edit($request)
    {
        global $wpdb;
        $temp = [
            'review' => $this->requestParam['rating'],
            'review_description' => $this->requestParam['review'],
            'updated_at' => $this->requestParam['updated_at'],
        ];
        $result  = $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}kc_patient_review` WHERE `id` LIKE  {$this->requestParam['review_id']} AND  `patient_id` LIKE {$this->user['id']}");

        if (empty($result)) {
            return comman_message_response(__("Unable To Find Review", 'kivicare-api'), 200);
        }

        $result = $wpdb->update($wpdb->prefix . 'kc_patient_review', $temp, array('id' => $this->requestParam['review_id']));

        if ($result !== false)
            return comman_message_response(__("Your Review Successfully Updated.", 'kivicare-api'), 200);
        else
            return comman_message_response(__("Something Went Wrong", 'kivicare-api'), 200);
    }

    public function review_delete($request)
    {
        global $wpdb;

        $temp = [
            'id' => $this->requestParam['review_id'] ?? -1
        ];


        $result  = $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}kc_patient_review` WHERE `id` LIKE  {$temp['id']} AND  `patient_id` LIKE {$this->user['id']}");

        if (empty($result)) {
            return comman_message_response(__("No Review Found For Delete It", 'kivicare-api'), 200);
        }

        $result = $wpdb->delete($wpdb->prefix . 'kc_patient_review', $temp);

        if ($result !== false)
            return comman_message_response(__("Your Review Has Been Deleted.", 'kivicare-api'), 200);
        else
            return comman_message_response(__("Something Went Wrong", 'kivicare-api'), 200);
    }


    public function review_get($request)
    {

        $temp = [
            'limit' => $this->requestParam['limit'],
            'offset' => ($this->requestParam['limit'] * ($this->requestParam['page'] - 1)),
            'doctor_id' => $this->requestParam['doctor_id'],
        ];

        $result = (new KCReviewModel())->review_get($temp);

        if (empty($result)) {
            return comman_message_response(__("No Review Found", 'kivicare-api'), 200);
        }

        return comman_custom_response(array("data" => $result), 200);
    }


    /**
     * If the current user doesn't have the permission to do the action, return an error
     * 
     * @param request The request object.
     * 
     * @return return new WP_Error('rest_forbidden', __('Sorry, you are not allowed to make requests.',
     * 'kivicare-api'), array('status' => rest_authorization_required_code()));
     *     return true;
     */
    public function hasPermission($request)
    {
        /**
         * $request->get_attributes()['callback'][1]
         * it can get text of callback function
         */
        if (!current_user_can(KIVI_CARE_PREFIX . 'patient_' . $request->get_attributes()['callback'][1])) {
            return new WP_Error('rest_forbidden', __('Sorry, you are not allowed to make requests.', 'kivicare-api'), array('status' => rest_authorization_required_code()));
        }
        $this->user  = ['id' => get_current_user_id(), "role" => getUserRole(get_current_user_id())];
        $this->requestParam = $request->get_params();
        return true;
    }
}
