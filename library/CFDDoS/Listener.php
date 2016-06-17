<?php


class CFDDoS_Listener
{

	public static function init_dependencies() {
		$options = XenForo_Application::get('options');
		$info = XenForo_Application::getSimpleCacheData('cfddos_info');

		if ($options->cfddos_api_email == '' || $options->cfddos_api_key == '' || $options->cfddos_zone == '')
			return;

		if (!$info) {
			$info = array(
				'stop_time' => 0,
				'enabled' => false,
                'page_views' => 0
			);
		}

        $info['page_views']++;

		XenForo_Application::setSimpleCacheData('cfddos_info', $info);
	}

}