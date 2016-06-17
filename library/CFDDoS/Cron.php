<?php


class CFDDoS_Cron
{

	public static function cron() {
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

		if ((($info['page_views'] > $options->cfddos_limit_views && $options->cfddos_limit_views > 0) || (sys_getloadavg()[0] > $options->cfddos_limit_load && $options->cfddos_limit_load)) && !$info['enabled']) {
			$model = XenForo_Model::create('CFDDoS_Model_CloudFlare');
			$model->enableProtection();
			$info['stop_time'] = time() + $options->cfddos_delay;
			$info['enabled'] = true;
		}

		if ($info['enabled']) {
			if ($info['stop_time'] < time()) {
				if (sys_getloadavg()[0] > $options->cfddos_limit_load) {
					$info['stop_time'] = time() + $options->cfddos_delay;
				} else {
					$model = XenForo_Model::create('CFDDoS_Model_CloudFlare');
					$model->disableProtection();
					$info['enabled'] = false;
				}
			}
		}

		$info['page_views'] = 0;

		XenForo_Application::setSimpleCacheData('cfddos_info', $info);
	}

}