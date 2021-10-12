<?php
/*
 *      TPVV Redsýs for RSForm! Pro
 *      @package TPVV Redsýs for RSForm! Pro
 *      @subpackage Payplans
 *      @author José António Cidre Bardelás
 *      @copyright Copyright (C) 2015 José António Cidre Bardelás and Joomla Empresa. All rights reserved
 *      @license GNU/GPL v3 or later
 *      
 *      Contact us at info@joomlaempresa.com (http://www.joomlaempresa.es)
 *      
 *      This file is part of TPVV Redsýs for RSForm! Pro.
 *      
 *          TPVV Redsýs for RSForm! Pro is free software: you can redistribute it and/or modify
 *          it under the terms of the GNU General Public License as published by
 *          the Free Software Foundation, either version 3 of the License, or
 *          (at your option) any later version.
 *      
 *          TPVV Redsýs for RSForm! Pro is distributed in the hope that it will be useful,
 *          but WITHOUT ANY WARRANTY; without even the implied warranty of
 *          MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *          GNU General Public License for more details.
 *      
 *          You should have received a copy of the GNU General Public License
 *          along with TPVV Redsýs for RSForm! Pro.  If not, see <http://www.gnu.org/licenses/>.
 */
defined('_JEXEC') or die('Acesso a '.basename(__FILE__).' restrito.');

class plgSystemRSFPRedsysInstallerScript
{
	public function preflight($type, $parent) {
		if ($type != 'uninstall') {
			$app = JFactory::getApplication();
			
			if (!file_exists(JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/rsform.php')) {
				$app->enqueueMessage('Please install the RSForm! Pro component before continuing.', 'error');
				return false;
			}
			
			if (!file_exists(JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/version.php')) {
				$app->enqueueMessage('Please upgrade RSForm! Pro to at least R45 before continuing!', 'error');
				return false;
			}
			
			if (!file_exists(JPATH_PLUGINS.'/system/rsfppayment/rsfppayment.php')) {
				$app->enqueueMessage('Please install the RSForm! Pro Payment Plugin first!', 'error');
				return false;
			}
			
			$jversion = new JVersion();
			if (!$jversion->isCompatible('2.5.5')) {
				$app->enqueueMessage('Please upgrade to at least Joomla! 2.5.5 before continuing!', 'error');
				return false;
			}
		}
		
		return true;
	}
	
	public function postflight($type, $parent) {
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->update($db->qn('#__extensions'))
			->set($db->qn('enabled').' = '.$db->q('1'))
			->where($db->qn('element').' = '.$db->q('rsfpredsys'))
			->where($db->qn('folder').' = '.$db->q('system'));
		$db->setQuery($query);
		$db->execute();
	}
}