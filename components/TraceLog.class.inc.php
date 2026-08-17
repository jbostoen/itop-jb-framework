<?php

/**
 * @copyright   Copyright (c) 2019-2021 Jeffrey Bostoen
 * @license     https://www.gnu.org/licenses/gpl-3.0.en.html
 * @version     2.6.210908
 *
 * Definition of TraceLog
 */

namespace jb_itop_extensions\components;

/**
 * Class TraceLog
 *
 * @deprecated Extensions should implement their own `Helper::Trace()` delegating to a `Logger` (extends `\LogAPI`),
 * matching the pattern already used in other jbostoen extensions (e.g. itop-jb-tags). See GitHub issue #12.
 */
abstract class TraceLog {

	/**
	 * Prints a $sMessage in the CRON output.
	 *
	 * @deprecated See GitHub issue #12. Use an extension-specific `Helper::Trace()` delegating to `\LogAPI` instead.
	 *
	 * @param \String $sMessage Message to put in the trace log (CRON output)
	 * @param \String $sType Type of message. Possible values: 'info' (default), 'error'
	 * @param \String $sWantedTraceLevel Wanted trace level. Possible values: 'none', 'info' (default), 'error'
	 */
	public static function Trace($sMessage, $sType = 'info', $sWantedTraceLevel = 'info') {

		@trigger_error('TraceLog::Trace() is deprecated. Extensions should implement their own Helper::Trace() delegating to a Logger (extends \LogAPI) instead. See GitHub issue #12.', E_USER_DEPRECATED);

		switch($sWantedTraceLevel) {

			case 'info':
				if(in_array($sType, ['info', 'error'])) {
					echo $sMessage. PHP_EOL;
				}
				break;

			case 'error':
				if($sType === 'error') {
					echo $sMessage. PHP_EOL;
				}
				break;

			case 'none':
				break;

			default:
				echo 'Unexpected trace level: '.$sWantedTraceLevel. PHP_EOL;

		}
	}

}
