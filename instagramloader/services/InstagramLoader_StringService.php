<?php

namespace Craft;

class InstagramLoader_StringService extends BaseApplicationComponent
{
	public function truncate($string, $length = 80)
	{
		if (strlen($string) < $length)
		{
			return $string;
		}

		return substr($string, 0, $length - 3) . '...';
	}
}

?>