<?php

/**
 * Created by PhpStorm.
 * User: radu
 * Date: 2/22/16
 * Time: 10:08 AM
 */
class JSON_API_Forms_Controller
{
    public function get_form()
    {
        if (!$_GET['fid'] || !is_numeric($_GET['fid'])) {
            return null;
        }

        $form = GFAPI::get_form($_GET['fid']);

        $this->processForm($form);

        return $form;
    }

    private function processForm(&$form)
    {
//        print_r($form);

        function replace_bool($item)
        {
            if (is_bool($item)) {
                // booleans get transformed to empty arrays otherwise in the output JSON
                return $item ? 1 : 0;
            }
            return $item;
        }

        foreach ($form as $k => $prop) {

            if ($k == 'fields') {
                foreach ($prop as $fk => $field) {
                    foreach ($field as $fieldName => $fieldVal) {
                        $form[$k][$fk][$fieldName] = replace_bool($fieldVal);
                    }
                }
            }

            if($k == 'notifications'){
                $form[$k] = null;
            }

            $form[$k] = replace_bool($prop);

//            echo $k . ' => ';
//            var_dump($prop);
//            echo "\n\r";
//            echo "</br>";
        }
    }

    public function get_form_by_name()
    {
        if (!$_GET['fname'] || empty($_GET['fname'])) {
            return null;
        }

        $fList = GFAPI::get_forms();
        $theForm = null;

//        print_r($fList);
        foreach ($fList as $form) {
            if (mb_strtolower($form['title']) == mb_strtolower($_GET['fname'])) {
                $theForm = $form;
                break;
            }
        }

        $this->processForm($theForm);

        return $theForm;
    }

    public function submit_form()
    {

        $json = file_get_contents('php://input');
        $postData = json_decode($json);

        $input_values = array();

        foreach ($postData->fields as $k => $v) {
            if(is_object($v)){
                $input_values['input_' . $k] = $v->value;
            }else if(is_array($v)){
                $input_values['input_' . $k] = $v['value'];
            }else{
                $input_values['input_' . $k] = $v;
            }
        }

        $form = GFAPI::submit_form($postData->id, $input_values);

        $this->processForm($form);

        return $form;
    }

    public function submit_download_attachments_form()
    {
        $json = file_get_contents('php://input');
        $postData = json_decode($json);

        $input_values = array();

        foreach ($postData->fields as $k => $v) {
            $input_values['input_' . $k] = $v;
        }

        $form = GFAPI::submit_form($postData->id, $input_values);
        $this->processForm($form);

        return $form;
    }

    private function isJson($string)
    {
        if ($string[0] == '{' || $string[0] == '[') {
            $jsonStr = json_decode($string);
            return (json_last_error() == JSON_ERROR_NONE);
        } else {
            return false;
        }
    }


}