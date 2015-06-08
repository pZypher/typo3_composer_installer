<?php
namespace Bytepark\CMS;
use Composer\Script\Event;
use Symfony\Component\Process\Process;

class Typo3Installer
{
	/**
	 * @param Event $event
	 */
	public static function cleanup(Event $event) {
		Typo3Installer::preCleanup();
	}

	/**
	 * @param Event $event
	 */
	public static function install(Event $event) {
		Typo3Installer::preCleanup();
		Typo3Installer::copyTyp3conf();
		Typo3Installer::typo3PathReplacement();
		Typo3Installer::postCleanup();
		Typo3Installer::createSymlinks();
	}

	/**
	 * @return void
	 */
	public static function preCleanup(){
		$script = array(
			'rm -rf typo3',
			'rm -f index.php',
			'rm -rf t3lib'
		);
		$p = new Process(implode(chr(10),$script));
		$p->run();
	}

	/**
	 * @return void
	 */
	public static function copyTyp3conf(){
		$script = array(
			'mv typo3conf typo3_src/typo3conf'
		);
		$p = new Process(implode(chr(10),$script));
		if(is_dir('typo3conf')){
			$p->run();
		}
	}

	/**
	 * @return void
	 */
	public static function typo3PathReplacement(){
		$t3lib = is_dir('typo3_src/t3lib')?'./t3lib ':'';
		$script = array(
			'cd typo3_src',
			'find ./index.php '.$t3lib.'./typo3 ./_.htaccess -type f -print0 | xargs -0 -n 1 sed -i -e "/typo3.org/b;/typo3lang/b;/typo3v4/b;/typo3_6/b;/typo3_src-/b;/typo3temp/b;/typo3conf/b;/typo3logo/b;/typo3cms/b;/typo3pageModule/b;/typo3\.log/b;/typo3-the-cms/b;/typo3-logo/b;/typo3\.png/b;/<typo3/b;/TYPO3_mainDir != [\']typo3/b;/$this->scriptID = [\']typo3/b; s/typo3\//t3admin\//g; s/\/typo3/\/t3admin/g"',
			'find ./_.htaccess -type f -print0 | xargs -0 -n 1 sed -i -e "s/(typo3/(t3admin/g"',
			'touch .t3admin_replaced',
			'cd ..'
		);
		$p = new Process(implode(chr(10),$script));
		$p->setTimeout(3600);
		if(!is_file('typo3_src/.t3admin_replaced')){
			$p->run();
		}
	}

	/**
	 * @return void
	 */
	public static function postCleanup(){
		$script = array(
			'rm web/index.php',
			'rm web/t3admin',
			'rm web/t3lib',
			'rm -rf web/typo3temp',
			'mkdir web/typo3temp',
			'rm -f web/_.htaccess'
		);
		if(!is_file('web/_.htaccess')){
			$script[] = 'cp typo3_src/_.htaccess web/_.htaccess';
		}
		$p = new Process(implode(chr(10),$script));
		$p->run();
	}

	/**
	 * @return void
	 */
	public static function createSymlinks(){
		$script = array(
			'ln -s "../typo3_src/index.php" "web/index.php"',
			'ln -s ../typo3_src/typo3 web/t3admin',
			'ln -s ../typo3_src/t3lib web/t3lib',
			'rm -rf web/typo3temp',
			'mkdir web/typo3temp',
			'rm -f web/_.htaccess',
			'rm -rf web/typo3temp/*'
		);
		$extPath = 'typo3_src/typo3conf/ext';
		$results = scandir($extPath);
		foreach ($results as $result) {
			if ($result === '.' or $result === '..') continue;
			if (is_dir($extPath . '/' . $result)) {
				$script[] = 'ln -s ../../../typo3_src/typo3conf/ext/' . $result . ' web/typo3conf/ext/' . $result;
			}
		}
		$p = new Process(implode(chr(10),$script));
		$p->run();
	}
}