<?php
class CFDDoS_Model_CloudFlare
{

    public function enableProtection() {
        $options = XenForo_Application::get('options');
        $zoneId = $this->_getZoneId();

        $curl_opt = array(
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                "X-Auth-Email: {$options->cfddos_api_email}",
                "X-Auth-Key: {$options->cfddos_api_key}"
            )
        );

        $ch = curl_init("https://api.cloudflare.com/client/v4/zones/{$zoneId}/settings/security_level");
        curl_setopt_array($ch, $curl_opt);
        $response = curl_exec($ch);
        $response = $this->_checkForErrors($response);

        if($response['result']['value'] == 'under_attack') {
            return;
        }

        XenForo_Application::setSimpleCacheData('cfddos_security_level', $response['result']['value']);

        $curl_opt[CURLOPT_CUSTOMREQUEST] = 'PATCH';
        $curl_opt[CURLOPT_POSTFIELDS] = json_encode(array('value' => 'under_attack'));

        $ch = curl_init("https://api.cloudflare.com/client/v4/zones/{$zoneId}/settings/security_level");
        curl_setopt_array($ch, $curl_opt);
        $response = curl_exec($ch);
        $this->_checkForErrors($response);
    }

    public function disableProtection() {
        $options = XenForo_Application::get('options');
        $zoneId = $this->_getZoneId();

        $curl_opt = array(
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                "X-Auth-Email: {$options->cfddos_api_email}",
                "X-Auth-Key: {$options->cfddos_api_key}"
            )
        );

        $ch = curl_init("https://api.cloudflare.com/client/v4/zones/{$zoneId}/settings/security_level");
        curl_setopt_array($ch, $curl_opt);
        $response = curl_exec($ch);
        $response = $this->_checkForErrors($response);

        if($response['result']['value'] != 'under_attack')
            return;

        $security_level = XenForo_Application::getSimpleCacheData('cfddos_security_level');
        $security_level = !$security_level ? 'medium' : $security_level;

        $curl_opt[CURLOPT_CUSTOMREQUEST] = 'PATCH';
        $curl_opt[CURLOPT_POSTFIELDS] = json_encode(array('value' => $security_level));

        $ch = curl_init("https://api.cloudflare.com/client/v4/zones/{$zoneId}/settings/security_level");
        curl_setopt_array($ch, $curl_opt);
        $response = curl_exec($ch);
        $this->_checkForErrors($response);
    }

    private function _getZoneId() {
        $options = XenForo_Application::get('options');

        $zone = XenForo_Application::getSimpleCacheData('cfddos_zone');

        if(!$zone || $zone != $options->cfddos_zone || !XenForo_Application::getSimpleCacheData('cfddos_zone_id')) {
            $curl_opt = array(
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    "X-Auth-Email: {$options->cfddos_api_email}",
                    "X-Auth-Key: {$options->cfddos_api_key}"
                )
            );

            $ch = curl_init('https://api.cloudflare.com/client/v4/zones/');
            curl_setopt_array($ch, $curl_opt);
            $response = curl_exec($ch);
            $response = $this->_checkForErrors($response);

            $zoneId = '';

            foreach($response['result'] as $zoneArr) {
                if(strtolower($zoneArr['name']) == strtolower($options->cfddos_zone))
                    $zoneId = $zoneArr['id'];
            }

            if($zoneId == '')
                throw new XenForo_Exception("Zone {$options->cfddos_zone} not found!");

            XenForo_Application::setSimpleCacheData('cfddos_zone', $options->cfddos_zone);
            XenForo_Application::setSimpleCacheData('cfddos_zone_id', $zoneId);
        }

        return XenForo_Application::getSimpleCacheData('cfddos_zone_id');
    }

    private function _checkForErrors($response) {
        $response = json_decode($response, true);
        if($response['success'] == false) {
            $errors = '';
            foreach($response['errors'] as $error)
                $errors .= "({$error['code']}) {$error['message']}, ";
            $errors = trim($errors, ', ');
            throw new XenForo_Exception("CloudFlare Error(s): {$errors}");
        }

        return $response;
    }

}