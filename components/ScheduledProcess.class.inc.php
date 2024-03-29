<?php

/**
 * @copyright   Copyright (c) 2019-2021 Jeffrey Bostoen
 * @license     https://www.gnu.org/licenses/gpl-3.0.en.html
 * @version     2.6.210908
 *
 * Definition of ScheduledProcess
 */

namespace jb_itop_extensions\components;

// generic
use \DateTime;

// iTop internals
use \CoreUnexpectedValue;
use \MetaModel;
use \utils;

/**
 * Class ScheduledProcess
 */
class ScheduledProcess {
	
	/**
	 * @var \String MODULE_CODE Identifier of the extension (used in iTop configuration settings). Should be redefined in subclasses.
	 */
	const MODULE_CODE = 'jb-scheduled-process---should-be-overwritten';

	const KEY_MODULE_SETTING_ENABLED = 'enabled';
	const KEY_MODULE_SETTING_DEBUG = 'debug';
	const KEY_MODULE_SETTING_WEEKDAYS = 'week_days';
	const KEY_MODULE_SETTING_TIME = 'time';

	const DEFAULT_MODULE_SETTING_ENABLED = true;
	const DEFAULT_MODULE_SETTING_DEBUG = false;
	const DEFAULT_MODULE_SETTING_WEEKDAYS = 'monday, tuesday, wednesday, thursday, friday, saturday, sunday';
	const DEFAULT_MODULE_SETTING_TIME = '03:00';
	
	/**
	 * Constructor.
	 */
	function __construct() {
	}

	/**
	 * Gives the exact time at which the process must be run next time
	 *
	 * @return \DateTime
	 * @throws CoreUnexpectedValue
	 */
	public function GetNextOccurrence() {
		
		$bEnabled = MetaModel::GetConfig()->GetModuleSetting(static::MODULE_CODE, 'enabled', true);
		
		if($bEnabled == false) {
			$oRet = new DateTime('3000-01-01');
		}
		else {
			
			$sRunTime = MetaModel::GetConfig()->GetModuleSetting(static::MODULE_CODE, 'time', static::DEFAULT_MODULE_SETTING_TIME);
			if(!preg_match('/^([01]?\d|2[0-3]):([0-5]?\d)(?::([0-5]?\d))?$/', $sRunTime, $aMatches)) {
				throw new CoreUnexpectedValue(static::MODULE_CODE.": wrong format for setting 'time' (found '$sRunTime')");
			}
			$iHours = (int)$aMatches[1];
			$iMinutes = (int)$aMatches[2];
//			$iSeconds = (array_key_exists(3, $aMatches)) ? $aMatches[3] : 0;
			$iSeconds = 59; // workaround : if below then will run multiple times till the minute is over
			// eg if set to 12:30:12 cron will launch the task at 12:30:12, and on each loop iteration until 12:31:00

			// 1st - Interpret the list of days as ordered numbers (monday = 1)
			//
			$aDays = $this->InterpretWeekDays();

			// 2nd - Find the next active week day
			//
			$oNow = new DateTime();
			$iNextPos = false;
			for ($iDay = $oNow->format('N'); $iDay <= 7; $iDay++) {
				$iNextPos = array_search($iDay, $aDays);
				if ($iNextPos !== false) {
					if (($iDay > $oNow->format('N')) || ($oNow->format('H:i') < $sRunTime)) {
						break;
					}
					$iNextPos = false; // necessary on sundays
				}
			}

			// 3rd - Compute the result
			//
			if($iNextPos === false) {
				// Jump to the first day within the next week
				$iFirstDayOfWeek = $aDays[0];
				$iDayMove = $oNow->format('N') - $iFirstDayOfWeek;
				$oRet = clone $oNow;
				$oRet->modify('-'.$iDayMove.' days');
				$oRet->modify('+1 weeks');
			}
			else {
				$iNextDayOfWeek = $aDays[$iNextPos];
				$iMove = $iNextDayOfWeek - $oNow->format('N');
				$oRet = clone $oNow;
				$oRet->modify('+'.$iMove.' days');
			}
			$oRet->setTime($iHours, $iMinutes, $iSeconds);
			
		}

		return $oRet;
		
	}

	/**
	 * @inheritdoc
	 *
	 * @throws \CoreException
	 * @throws \CoreUnexpectedValue
	 * @throws \MissingQueryArgument
	 * @throws \MySQLException
	 * @throws \MySQLHasGoneAwayException
	 * @throws \OQLException
	 */
	public function Process($iTimeLimit) {
		
		// Child class should do something
		
	}

	/**
	 * Interpret current setting for the week days
	 *
	 * Note: This comes from itop-backup scheduled task.
	 *
	 * @returns array of int (monday = 1)
	 * @throws CoreUnexpectedValue
	 */
	public function InterpretWeekDays() {
		
		static $aWEEKDAYTON = [
			'monday' => 1,
			'tuesday' => 2,
			'wednesday' => 3,
			'thursday' => 4,
			'friday' => 5,
			'saturday' => 6,
			'sunday' => 7,
		];
		
		$aDays = [];
		$sWeekDays = MetaModel::GetConfig()->GetModuleSetting(static::MODULE_CODE, 'week_days', 'monday, tuesday, wednesday, thursday, friday, saturday, sunday');

		if($sWeekDays != '') {
			$aWeekDaysRaw = explode(',', $sWeekDays);
			foreach ($aWeekDaysRaw as $sWeekDay) {
				$sWeekDay = strtolower(trim($sWeekDay));
				if (array_key_exists($sWeekDay, $aWEEKDAYTON)) {
					$aDays[] = $aWEEKDAYTON[$sWeekDay];
				}
				else {
					throw new CoreUnexpectedValue(static::MODULE_CODE.": wrong format for setting 'week_days' (found '$sWeekDay')");
				}
			}
		}
		if(count($aDays) == 0) {
			throw new CoreUnexpectedValue(static::MODULE_CODE.": missing setting 'week_days'");
		}
		$aDays = array_unique($aDays);
		sort($aDays);

		return $aDays;
		
	}

	/**
	 * Prints a $sMessage in the CRON output.
	 *
	 * @param \String $sMessage Message to put in the trace log (CRON output)
	 * @param \String $sType Type of message. Possible values: info, error
	 *
	 */
	public function Trace($sMessage, $sType = 'info') {
		
		TraceLog::Trace($sMessage, $sType, utils::GetCurrentModuleSetting('debug_level', 'info'));
		
	}
	
}
