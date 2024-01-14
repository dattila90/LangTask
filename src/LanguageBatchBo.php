<?php

namespace Language;

/**
 * Business logic related to generating language files.
 */
class LanguageBatchBo
{
	/**
	 * Contains the applications which ones require translations.
	 *
	 * @var array
	 */
	protected static $applications = array();

	public function __construct(Config $config, ApiCall $apiCall)
	{
		$this->config = $config;
		$this->apiCall = $apiCall;
	}

	/**
	 * Starts the language file generation.
	 *
	 * @return void
	 */
	public function generateLanguageFiles()
	{
		// The applications where we need to translate.
		$applications = $this->config->get('system.translated_applications');

		echo "\nGenerating language files\n";

		foreach ($applications as $application => $langs) {
			$this->processApplication($application, $langs);
		}
	}

	private function processApplication($application, $langs)
	{
		echo "[APPLICATION: $application]\n";

		foreach ($langs as $lang) {
			echo "\t[LANGUAGE: $lang]";

			try {
				$this->processLanguage($application, $lang);
				echo " OK\n";
			} catch (\Exception $e) {
				throw new \Exception("Unable to generate language file for ($application/$lang): " . $e->getMessage());
			}
		}
	}

	private function processLanguage($application, $lang)
	{
		$langResponse = $this->apiCall->call(
			'system_api', 
			'language_api', 
			[
				'system' => 'LanguageFiles', 
				'action' => 'getLanguageFile'
			], 
			['language' => $lang]
		);

		$this->checkForApiErrorResult($langResponse);

		$destination = $this->getLanguageCachePath($application) . $lang . '.php';
		$this->createDirectoryIfNeeded($destination);

		$result = file_put_contents($destination, $langResponse['data']);

		if (!$result) {
			throw new \Exception('Unable to generate language file!');
		}
	}

	/**
	 * Gets the language file for the given language and stores it.
	 *
	 * @param string $application   The name of the application.
	 * @param string $lang      The identifier of the language.
	 *
	 * @throws CurlException   If there was an error during the download of the language file.
	 *
	 * @return bool   The success of the operation.
	 */
	protected function getLanguageFile($application, $lang)
	{
		$result = false;
		$langResponse = $this->apiCall->call(
			'system_api',
			'language_api',
			array(
				'system' => 'LanguageFiles',
				'action' => 'getLanguageFile'
			),
			array('language' => $lang)
		);

		try {
			$this->checkForApiErrorResult($langResponse);
		}
		catch (\Exception $e) {
			throw new \Exception('Error during getting language file: (' . $application . '/' . $lang . ')');
		}

		// If we got correct data we store it.
		$destination = $this->getLanguageCachePath($application) . $lang . '.php';
		// If there is no folder yet, we'll create it.
		var_dump($destination);
		if (!is_dir(dirname($destination))) {
			mkdir(dirname($destination), 0755, true);
		}

		$result = file_put_contents($destination, $langResponse['data']);

		return (bool)$result;
	}

	/**
	 * Gets the directory of the cached language files.
	 *
	 * @param string $application   The application.
	 *
	 * @return string   The directory of the cached language files.
	 */
	protected function getLanguageCachePath($application)
	{
		return $this->config->get('system.paths.root') . '/cache/' . $application. '/';
	}

	/**
	 * Gets the language files for the applet and puts them into the cache.
	 *
	 * @throws Exception   If there was an error.
	 *
	 * @return void
	 */
	public function generateAppletLanguageXmlFiles()
	{
		// List of the applets [directory => applet_id].
		$applets = array(
			'memberapplet' => 'JSM2_MemberApplet',
		);

		echo "\nGetting applet language XMLs..\n";

		foreach ($applets as $appletDirectory => $appletLanguageId) {
			echo " Getting > $appletLanguageId ($appletDirectory) language xmls..\n";
			$langs = $this->getAppletLanguages($appletLanguageId);
			if (empty($langs)) {
				throw new \Exception('There is no available languages for the ' . $appletLanguageId . ' applet.');
			}
			else {
				echo ' - Available languages: ' . implode(', ', $langs) . "\n";
			}
			$path = $this->config->get('system.paths.root') . '/cache/flash';
			foreach ($langs as $lang) {
				$xmlContent = $this->getAppletLanguageFile($appletLanguageId, $lang);
				$xmlFile    = $path . '/lang_' . $lang . '.xml';
				if (strlen($xmlContent) == file_put_contents($xmlFile, $xmlContent)) {
					echo " OK saving $xmlFile was successful.\n";
				}
				else {
					throw new \Exception('Unable to save applet: (' . $appletLanguageId . ') language: (' . $lang
						. ') xml (' . $xmlFile . ')!');
				}
			}
			echo " < $appletLanguageId ($appletDirectory) language xml cached.\n";
		}

		echo "\nApplet language XMLs generated.\n";
	}

	/**
	 * Gets the available languages for the given applet.
	 *
	 * @param string $applet   The applet identifier.
	 *
	 * @return array   The list of the available applet languages.
	 */
	protected function getAppletLanguages($applet)
	{
		$result = $this->apiCall->call(
			'system_api',
			'language_api',
			array(
				'system' => 'LanguageFiles',
				'action' => 'getAppletLanguages'
			),
			array('applet' => $applet)
		);

		try {
			$this->checkForApiErrorResult($result);
		}
		catch (\Exception $e) {
			throw new \Exception('Getting languages for applet (' . $applet . ') was unsuccessful ' . $e->getMessage());
		}

		return $result['data'];
	}


	/**
	 * Gets a language xml for an applet.
	 *
	 * @param string $applet      The identifier of the applet.
	 * @param string $lang    The language identifier.
	 *
	 * @return string|false   The content of the language file or false if weren't able to get it.
	 */
	protected function getAppletLanguageFile($applet, $lang)
	{
		$result = $this->apiCall->call(
			'system_api',
			'language_api',
			array(
				'system' => 'LanguageFiles',
				'action' => 'getAppletLanguageFile'
			),
			array(
				'applet' => $applet,
				'language' => $lang
			)
		);

		try {
			$this->checkForApiErrorResult($result);
		}
		catch (\Exception $e) {
			throw new \Exception('Getting language xml for applet: (' . $applet . ') on language: (' . $lang . ') was unsuccessful: '
				. $e->getMessage());
		}

		return $result['data'];
	}

	private function createDirectoryIfNeeded($path)
    {
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
    }

	/**
	 * Checks the api call result.
	 *
	 * @param mixed  $result   The api call result to check.
	 *
	 * @throws Exception   If the api call was not successful.
	 *
	 * @return void
	 */
	protected function checkForApiErrorResult($result)
	{
		// Error during the api call.
		if ($result === false || !isset($result['status'])) {
			throw new \Exception('Error during the api call');
		}
		// Wrong response.
		if ($result['status'] != 'OK') {
			throw new \Exception('Wrong response: '
				. (!empty($result['error_type']) ? 'Type(' . $result['error_type'] . ') ' : '')
				. (!empty($result['error_code']) ? 'Code(' . $result['error_code'] . ') ' : '')
				. ((string)$result['data']));
		}
		// Wrong content.
		if ($result['data'] === false) {
			throw new \Exception('Wrong content!');
		}
	}
}
