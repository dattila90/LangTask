<?php

namespace Language;

class ApiCall
{
	const GET_LANGUAGE_FILE_RESULT = "<?php
		return array (
			'favorites' => 'Favoriten',
			'help' => 'Hilfe',
			'login' => 'Anmelden',
			'sign up' => 'Registrieren'
		);";

	const GET_APPLET_LANGUAGE_FILE_RESULT = '<?xml version="1.0" encoding="UTF-8"?>
		<data>
			<button_send value="Küldés"/>
			<button_ok value="OK"/>
			<button_close value="Bezárás"/>
			<button_back value="Vissza"/>
			<cancel value="Törlés"/>
		</data>';

	public static function call($target, $mode, $getParameters, $postParameters)
	{
		if (!isset($getParameters['action']))
		{
			return;
		}

		switch ($getParameters['action'])
		{
			case 'getLanguageFile':
				return self::formatAsResult(self::GET_LANGUAGE_FILE_RESULT);
				break;

			case 'getAppletLanguages':
				return self::formatAsResult(['en']);
				break;

			case 'getAppletLanguageFile':
				return self::formatAsResult(self::GET_APPLET_LANGUAGE_FILE_RESULT);
				break;
		}
	}

	private static function formatAsResult($data)
	{
		return [
			'status' => 'OK',
			'data'   => $data,
		];
	}
}