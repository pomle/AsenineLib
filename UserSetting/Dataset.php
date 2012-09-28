<?
namespace Asenine\UserSetting;

class Dataset
{
	public static function getAvailable()
	{
		$userSettings = array();
		include ASENINE_DIR_CONFIG . 'UserSettings.inc.php';
		return $userSettings;
	}
}