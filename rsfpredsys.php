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
defined('_JEXEC') or die('Acesso a ' . basename(__FILE__) . ' restrito.');

class plgSystemRSFPRedsys extends JPlugin
{
	protected $componentId = 1500;
	protected $componentValue = 'redsys';

	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
		$this->newComponents = array(1500);
	}

	public function rsfp_bk_onAfterShowComponents()
	{
		$lang = JFactory::getLanguage();
		$lang->load('plg_system_rsfpredsys', JPATH_ADMINISTRATOR);

		$mainframe = JFactory::getApplication();
		$db        = JFactory::getDBO();
		$formId    = JRequest::getInt('formId');

		$link = "displayTemplate('" . $this->componentId . "')";
		if ($components = RSFormProHelper::componentExists($formId, $this->componentId))
			$link = "displayTemplate('" . $this->componentId . "', '" . $components[0] . "')";
		?>
        <li><a href="javascript: void(0);" onclick="<?php echo $link; ?>;return false;"
               id="rsfpc<?php echo $this->componentId; ?>"><span
                        class="rsficon"><?php echo '<img src="' . JURI::root() . 'plugins/system/rsfpredsys/images/redsys.png" />'; ?></span><span
                        id="redsys" class="inner-text"><?php echo JText::_('RSFP_REDSYS_COMPONENT'); ?></span></a></li>
		<?php
	}

	public function rsfp_getPayment(&$items, $formId)
	{
		if ($components = RSFormProHelper::componentExists($formId, $this->componentId))
		{
			$data = RSFormProHelper::getComponentProperties($components[0]);

			$item        = new stdClass();
			$item->value = $this->componentValue;
			$item->text  = $data['LABEL'];

			// add to array
			$items[] = $item;
		}
	}

	public function rsfp_doPayment($payValue, $formId, $SubmissionId, $price, $products, $code)
	{
		// execute only for our plugin
		if ($payValue != $this->componentValue) return;

		if ($price > 0)
		{
			list($replace, $with) = RSFormProHelper::getReplacements($SubmissionId);

			$amount  = number_format($price, 2, '.', '') * 100;
			$orderId = $SubmissionId;

			$long = strlen($orderId);
			if ($long < 11)
			{
				for ($i = $long; $i < 11; $i++)
				{
					$orderId = "0" . $orderId;
				}
				$orderId = "0" . $orderId;
			}
            elseif ($long > 12)
			{
				$orderId = substr($orderId, -12);
			}

			$language   = '';
			$local      = JFactory::getLanguage();
			$localFull  = $local->getLocale();
			$localShort = explode('.', $localFull[0]);
			$jooLang    = $localShort[0];

			if (trim(RSFormProHelper::getConfig('redsys.Ds_Merchant_ConsumerLanguage', 0)) == 'AUTO')
			{
				switch (substr($jooLang, 0, 3))
				{
					case 'es_':
						$language = '001';
						break;
					case 'en_':
						$language = '002';
						break;
					case 'ca_':
						$language = '003';
						break;
					case 'fr_':
						$language = '004';
						break;
					case 'de_':
						$language = '005';
						break;
					case 'nl_':
						$language = '006';
						break;
					case 'it_':
						$language = '007';
						break;
					case 'sv_':
						$language = '008';
						break;
					case 'pt_':
						$language = '009';
						break;
					case 'pl_':
						$language = '011';
						break;
					case 'gl_':
						$language = '012';
						break;
					case 'eu_':
						$language = '013';
						break;
					default :
						$language = '0';
				}
			}
			else
				$language = RSFormProHelper::getConfig('redsys.Ds_Merchant_ConsumerLanguage');
			if (!$language)
				$language = 0;

			$merchantCode     = RSFormProHelper::getConfig('redsys.Ds_Merchant_MerchantCode');
			$merchantCurrency = RSFormProHelper::getConfig('redsys.Ds_Merchant_Currency');
			$transactionType  = RSFormProHelper::getConfig('redsys.Ds_Merchant_TransactionType');
			$languages        = JLanguageHelper::getLanguages('lang_code');
			$notificationURL  = JURI::root() . 'index.php?option=com_rsform&formId=' . $formId . '&task=plugin&plugin_task=redsys.notify&t=n&code=' . $code . (JLanguageMultilang::isEnabled() ? '&lang=' . $languages[$local->getTag()]->sef : '');

			$args = array(
				'Ds_Merchant_Amount'             => $amount,
				'Ds_Merchant_Currency'           => $merchantCurrency,
				'Ds_Merchant_Order'              => $orderId,
				'Ds_Merchant_ProductDescription' => (RSFormProHelper::getConfig('redsys.Ds_Merchant_ProductDescription') ? RSFormProHelper::getConfig('redsys.Ds_Merchant_ProductDescription') . ' ' : '') . '(' . implode(', ', $products) . ' - ' . $formId . ')',
				'Ds_Merchant_MerchantCode'       => $merchantCode,
				'Ds_Merchant_MerchantURL'        => urlencode($notificationURL),
				'Ds_Merchant_UrlOK'              => urlencode(JURI::root() . 'index.php?option=com_rsform&formId=' . $formId . '&task=plugin&plugin_task=redsys.notify&t=ok&code=' . $code . (JFactory::getApplication()->input->getInt('Itemid') != '' ? '&Itemid=' . JFactory::getApplication()->input->getInt('Itemid') : '')),
				'Ds_Merchant_UrlKO'              => urlencode(JURI::root() . 'index.php?option=com_rsform&formId=' . $formId . '&task=plugin&plugin_task=redsys.notify&t=ko&code=' . $code . (JFactory::getApplication()->input->getInt('Itemid') != '' ? '&Itemid=' . JFactory::getApplication()->input->getInt('Itemid') : '')),
				'Ds_Merchant_ConsumerLanguage'   => $language,
				'Ds_Merchant_Terminal'           => RSFormProHelper::getConfig('redsys.Ds_Merchant_Terminal'),
				'Ds_Merchant_TransactionType'    => $transactionType,
				'Ds_Merchant_PayMethods'         => RSFormProHelper::getConfig('redsysy.Ds_Merchant_PayMethods') == 'A' ? ' ' : RSFormProHelper::getConfig('redsysy.Ds_Merchant_PayMethods')
			);

			if (RSFormProHelper::getConfig('redsys.Ds_Merchant_MerchantName'))
			{
				$args['Ds_Merchant_MerchantName'] = RSFormProHelper::getConfig('redsys.Ds_Merchant_MerchantName');
			}

			// Get a new instance of the Redsys object. This is used so that we can programatically change values sent to Redsys through the "Scripts" areas.
			$redsys = RSFormProRedsys::getInstance();

			// If any options have already been set, use this to override the ones used here
			$redsys->args = array_merge($args, $redsys->args);

			JFactory::getApplication()->redirect('index.php?option=com_rsform&formId=' . $formId . '&task=plugin&plugin_task=redsys.notify&redirect=1&' . http_build_query($redsys->args, '', '&'));
		}
	}

	public function rsfp_bk_onAfterCreateComponentPreview($args = array())
	{
		if ($args['ComponentTypeName'] == 'redsys')
		{
			$args['out'] = '<td>&nbsp;</td>';
			$args['out'] .= '<td><img src="' . JURI::root(true) . '/plugins/system/rsfpredsys/images/redsys.png" /> ' . $args['data']['LABEL'] . '</td>';
		}
	}

	public function rsfp_bk_onAfterShowConfigurationTabs($tabs)
	{
		$lang = JFactory::getLanguage();
		$lang->load('plg_system_rsfpredsys', JPATH_ADMINISTRATOR);

		$tabs->addTitle(JText::_('RSFP_REDSYS_LABEL'), 'form-redsys');
		$tabs->addContent($this->redsysConfigurationScreen());
	}

	public function redsysConfigurationScreen()
	{
		ob_start();

		$paymentTypeOptions = array(
			'A' => JText::_('RSFP_REDSYS_ALL'),
			'C' => JText::_('RSFP_REDSYS_CREDIT_CARD'),
			'O' => JText::_('RSFP_REDSYS_IUPAY'),
			'T' => JText::_('RSFP_REDSYS_CREDIT_CARD_AND_IUPAY')
		);
		$posModeOptions     = array(
			'sim'  => JText::_('RSFP_REDSYS_SIM'),
			'prod' => JText::_('RSFP_REDSYS_PROD')
		);
		$languageOptions    = array(
			'0'    => JText::_('RSFP_REDSYS_UNDEFINED'),
			'AUTO' => JText::_('RSFP_REDSYS_AUTOMATIC'),
			'001'  => JText::_('RSFP_REDSYS_SPANISH'),
			'002'  => JText::_('RSFP_REDSYS_ENGLISH'),
			'003'  => JText::_('RSFP_REDSYS_CATALAN'),
			'004'  => JText::_('RSFP_REDSYS_FRENCH'),
			'005'  => JText::_('RSFP_REDSYS_GERMAN'),
			'006'  => JText::_('RSFP_REDSYS_DUTCH'),
			'007'  => JText::_('RSFP_REDSYS_ITALIAN'),
			'008'  => JText::_('RSFP_REDSYS_SWEDISH'),
			'009'  => JText::_('RSFP_REDSYS_PORTUGUESE'),
			'010'  => JText::_('RSFP_REDSYS_VALENCIAN'),
			'011'  => JText::_('RSFP_REDSYS_POLISH'),
			'012'  => JText::_('RSFP_REDSYS_GALIZAN'),
			'013'  => JText::_('RSFP_REDSYS_BASQUE')
		);
		?>
        <div id="page-redsys" class="com-rsform-css-fix">
            <table class="admintable">
                <tr>
                    <td width="400" align="right" colspan="2"
                        style="width: 400px; text-align: center; padding: 1em;"><?php echo JText::_('PLG_SYSTEM_RSFP_REDSYS_DESC'); ?></td>
                </tr>
                <tr>
                    <td width="200" style="width: 200px;" align="right" class="key"><label
                                for="redsys.pos_payment_type"><?php echo JText::_('RSFP_REDSYS_POS_PAYMENT_TYPE'); ?></label>
                    </td>
                    <td><?php echo JHTML::_('select.genericlist', $paymentTypeOptions, 'rsformConfig[redsys.pos_payment_type]', '', 'value', 'text', RSFormProHelper::htmlEscape(RSFormProHelper::getConfig('redsys.pos_payment_type'))); ?></td>
                </tr>
                <tr>
                    <td width="200" style="width: 200px;" align="right" class="key"><label
                                for="redsys.pos_mode"><?php echo JText::_('RSFP_REDSYS_POS_MODE'); ?></label></td>
                    <td><?php echo JHTML::_('select.genericlist', $posModeOptions, 'rsformConfig[redsys.pos_mode]', '', 'value', 'text', RSFormProHelper::htmlEscape(RSFormProHelper::getConfig('redsys.pos_mode'))); ?></td>
                </tr>
                <tr>
                    <td width="200" style="width: 200px;" align="right" class="key"><label
                                for="redsys.sim_pos_url"><?php echo JText::_('RSFP_REDSYS_SIM_URL'); ?></label></td>
                    <td><input type="text" name="rsformConfig[redsys.sim_pos_url]"
                               value="<?php echo RSFormProHelper::htmlEscape(RSFormProHelper::getConfig('redsys.sim_pos_url')); ?>"
                               size="200" maxlength="96" style="width: 350px;"></td>
                </tr>
                <tr>
                    <td width="200" style="width: 200px;" align="right" class="key"><label
                                for="redsys.prod_pos_url"><?php echo JText::_('RSFP_REDSYS_PROD_URL'); ?></label></td>
                    <td><input type="text" name="rsformConfig[redsys.prod_pos_url]"
                               value="<?php echo RSFormProHelper::htmlEscape(RSFormProHelper::getConfig('redsys.prod_pos_url')); ?>"
                               size="200" maxlength="96" style="width: 350px;"></td>
                </tr>
                <tr>
                    <td width="200" style="width: 200px;" align="right" class="key"><label
                                for="redsys.Ds_Merchant_MerchantName"><?php echo JText::_('RSFP_REDSYS_STORE_NAME'); ?></label>
                    </td>
                    <td><input type="text" name="rsformConfig[redsys.Ds_Merchant_MerchantName]"
                               value="<?php echo RSFormProHelper::htmlEscape(RSFormProHelper::getConfig('redsys.Ds_Merchant_MerchantName')); ?>"
                               size="100"></td>
                </tr>
                <tr>
                    <td width="200" style="width: 200px;" align="right" class="key"><label
                                for="redsys.Ds_Merchant_ProductDescription"><?php echo JText::_('RSFP_REDSYS_SALE_DESCRIPTION'); ?></label>
                    </td>
                    <td><input type="text" name="rsformConfig[redsys.Ds_Merchant_ProductDescription]"
                               value="<?php echo RSFormProHelper::htmlEscape(RSFormProHelper::getConfig('redsys.Ds_Merchant_ProductDescription')); ?>"
                               size="100"></td>
                </tr>
                <tr>
                    <td width="200" style="width: 200px;" align="right" class="key"><label
                                for="redsys.Ds_Merchant_MerchantCode"><?php echo JText::_('RSFP_REDSYS_COMMERCE_NUMBER'); ?></label>
                    </td>
                    <td><input type="text" name="rsformConfig[redsys.Ds_Merchant_MerchantCode]"
                               value="<?php echo RSFormProHelper::htmlEscape(RSFormProHelper::getConfig('redsys.Ds_Merchant_MerchantCode')); ?>"
                               size="100"></td>
                </tr>
                <tr>
                    <td width="200" style="width: 200px; padding-bottom: 1em;" align="right" class="key"><label
                                for="redsys.key"><?php echo JText::_('RSFP_REDSYS_CHANGE_KEY'); ?></label></td>
                    <td style="padding-bottom: 1em;">
						<?php
						$component = JComponentHelper::getComponent('com_jetpvvcommon', true);
						if ((!file_exists(JPATH_ADMINISTRATOR . '/components/com_jetpvvcommon/version.php')) || !$component->enabled)
						{
							echo '<span class="label label-important">' . JText::_('RSFP_REDSYS_CRYPT_WARNING') . '</span>';
						}
						else
						{
							require_once JPATH_ADMINISTRATOR . '/components/com_jetpvvcommon/version.php';
							if (version_compare(JETPVVCOMMON_VERSION, '3.0.0', 'lt'))
							{
								echo '<span class="label label-important">' . JText::_('RSFP_REDSYS_CRYPT_WARNING') . '</span>';
							}
							else
							{
								// Load the modal behavior script.
								JHtml::_('behavior.modal', 'a.modal');

								// Setup variables for display.
								$html        = array();
								$jeTPVVToken = version_compare(JVERSION, '3.0.0', 'ge') ? JSession::getFormToken() : JUtility::getToken();
								$link        = 'index.php?option=com_jetpvvcommon&amp;layout=modal&amp;tmpl=component&amp;key=rsfp_redsys_key&amp;cid=1&amp;' . $jeTPVVToken . '=1';

								// The user select button.
								$html[] = '<div class="button2-left">';
								$html[] = '  <div class="blank">';
								$html[] = '	<a class="modal btn btn-primary" title="' . JText::_('RSFP_REDSYS_CLICK_CHANGE_KEY_DET') . '"  href="' . $link . '" rel="{handler: \'iframe\', size: {x: 800, y: 450}}">' . JText::_('RSFP_REDSYS_CLICK_CHANGE_KEY') . '</a>';
								$html[] = '  </div>';
								$html[] = '</div>';

								echo implode("\n", $html);
							}
						}
						?>
                    </td>
                </tr>
                <tr>
                    <td width="200" style="width: 200px;" align="right" class="key"><label
                                for="redsys.Ds_Merchant_Terminal"><?php echo JText::_('RSFP_REDSYS_TERMINAL_NUMBER'); ?></label>
                    </td>
                    <td><input type="text" name="rsformConfig[redsys.Ds_Merchant_Terminal]"
                               value="<?php echo RSFormProHelper::htmlEscape(RSFormProHelper::getConfig('redsys.Ds_Merchant_Terminal')); ?>"
                               size="100"></td>
                </tr>
                <tr>
                    <td width="200" style="width: 200px;" align="right" class="key"><label
                                for="redsys.Ds_Merchant_Currency"><?php echo JText::_('RSFP_REDSYS_CURRENCY'); ?></label>
                    </td>
                    <td><input type="text" name="rsformConfig[redsys.Ds_Merchant_Currency]"
                               value="<?php echo RSFormProHelper::htmlEscape(RSFormProHelper::getConfig('redsys.Ds_Merchant_Currency')); ?>"
                               size="100"></td>
                </tr>
                <tr>
                    <td width="200" style="width: 200px;" align="right" class="key"><label
                                for="redsys.Ds_Merchant_TransactionType"><?php echo JText::_('RSFP_REDSYS_TRANSACTION_TYPE'); ?></label>
                    </td>
                    <td><input type="text" name="rsformConfig[redsys.Ds_Merchant_TransactionType]"
                               value="<?php echo RSFormProHelper::htmlEscape(RSFormProHelper::getConfig('redsys.Ds_Merchant_TransactionType')); ?>"
                               size="100"></td>
                </tr>
                <tr>
                    <td width="200" style="width: 200px;" align="right" class="key"><label
                                for="redsys.Ds_Merchant_ConsumerLanguage"><?php echo JText::_('RSFP_REDSYS_LANGUAGE'); ?></label>
                    </td>
                    <td><?php echo JHTML::_('select.genericlist', $languageOptions, 'rsformConfig[redsys.Ds_Merchant_ConsumerLanguage]', '', 'value', 'text', RSFormProHelper::htmlEscape(RSFormProHelper::getConfig('redsys.Ds_Merchant_ConsumerLanguage'))); ?></td>
                </tr>
                <tr>
                    <td width="200" style="width: 200px;" align="right" class="key"><label
                                for="redsys.mail_admin"><?php echo JText::_('RSFP_REDSYS_MAIL_ADMIN'); ?></label></td>
                    <td><?php echo JHTML::_('select.booleanlist', 'rsformConfig[redsys.mail_admin]', '', RSFormProHelper::htmlEscape(RSFormProHelper::getConfig('redsys.mail_admin'))); ?></td>
                </tr>
                <tr>
                    <td width="200" style="width: 200px;" align="right" class="key"><label
                                for="redsys.debug"><?php echo JText::_('RSFP_REDSYS_DEBUG'); ?></label></td>
                    <td><?php echo JHTML::_('select.booleanlist', 'rsformConfig[redsys.debug]', '', RSFormProHelper::htmlEscape(RSFormProHelper::getConfig('redsys.debug'))); ?></td>
                </tr>
                <tr>
                    <td width="200" style="width: 200px;" align="right" class="key"><label
                                for="redsys.debug_email"><?php echo JText::_('RSFP_REDSYS_DEBUG_EMAIL'); ?></label></td>
                    <td><input type="text" name="rsformConfig[redsys.debug_email]"
                               value="<?php echo RSFormProHelper::htmlEscape(RSFormProHelper::getConfig('redsys.debug_email')); ?>"
                               size="100"></td>
                </tr>
            </table>
        </div>
		<?php

		$contents = ob_get_contents();
		ob_end_clean();

		return $contents;
	}

	public function rsfp_f_onSwitchTasks()
	{
		$lang = JFactory::getLanguage();
		$lang->load('plg_system_rsfpredsys', JPATH_ADMINISTRATOR);
		$jInput = JFactory::getApplication()->input;

		// Notification receipt or response page from Redsys
		if ($jInput->get('plugin_task') == 'redsys.notify')
		{
			if ($jInput->get('redirect', 0))
			{
				$redsysArgs = version_compare(JVERSION, '3.0.0', 'lt') ? JRequest::get('get') : $jInput->getArray();

				$redsysOrderParams = array();

				foreach ($redsysArgs as $name => $value)
				{
					if (substr($name, 0, 3) == 'Ds_')
					{
						if ($name == 'Ds_Merchant_MerchantURL' || $name == 'Ds_Merchant_UrlOK' || $name == 'Ds_Merchant_UrlKO')
						{
							$redsysOrderParams[$name] = urldecode($value);
						}
						else
						{
							$redsysOrderParams[$name] = $value;
						}
					}
				}

				require_once JPATH_ADMINISTRATOR . '/components/com_jetpvvcommon/helpers/jetpvvcommon.php';
				require_once JPATH_ADMINISTRATOR . '/components/com_jetpvvcommon/helpers/redsys.php';
				$signatureVersion = "HMAC_SHA256_V1";
				$signature        = JETPVvCommonHelperRedsys::createSendSignature('rsfp_redsys_key', 1, $redsysOrderParams);

				$redsysUrl = RSFormProHelper::getConfig('redsys.pos_mode') == 'sim' ? RSFormProHelper::getConfig('redsys.sim_pos_url') : RSFormProHelper::getConfig('redsys.prod_pos_url');

				$args = array(
					'Ds_SignatureVersion'   => $signatureVersion,
					'Ds_MerchantParameters' => base64_encode(json_encode($redsysOrderParams)),
					'Ds_Signature'          => $signature,
				);

				if (RSFormProHelper::getConfig('redsys.debug'))
					$this->sendNotifyMail('[Debug] Data sent to POS', RSFormProHelper::getConfig('redsys.debug_email'), print_r($args, true));
				?>
                <h1><?php echo JText::_('RSFP_REDSYS_REDIRECTING') ?></h1>
                <form action="<?php echo $redsysUrl ?>" method="post" name="paymentForm" id="paymentForm">
					<?php
					foreach ($args as $name => $value)
					{
						if (substr($name, 0, 3) == 'Ds_')
						{
							if ($name == 'Ds_Merchant_MerchantURL' || $name == 'Ds_Merchant_UrlOK' || $name == 'Ds_Merchant_UrlKO')
								echo '<input type="hidden" name="' . $name . '" value="' . htmlspecialchars(urldecode($value)) . '" />';
							else
								echo '<input type="hidden" name="' . $name . '" value="' . htmlspecialchars($value) . '" />';
						}
					}
					?>
                </form>
                <script type="text/javascript">
                    document.paymentForm.submit();
                </script>
				<?php
			}
			else
			{
				$type = $jInput->get('t', '', 'ALNUM');
				if ($type == 'n')
				{
					// Notification
					if (RSFormProHelper::getConfig('redsys.debug'))
					{
						$this->sendNotifyMail('[Debug] Notification data received from POS', RSFormProHelper::getConfig('redsys.debug_email'));
					}

					$code   = $jInput->get('code', '', 'ALNUM');
					$formId = $jInput->getInt('formId');
					if (!$code || !$formId)
					{
						return false;
					}

					$redsysOrderParamsB64   = $jInput->getString('Ds_MerchantParameters');
					$redsysOrderParamsJSon  = base64_decode(strtr($redsysOrderParamsB64, '-_', '+/'));
					$redsysOrderParamsArray = json_decode($redsysOrderParamsJSon, true);

					$Ds_Amount            = $redsysOrderParamsArray['Ds_Amount'];
					$Ds_Order             = $redsysOrderParamsArray['Ds_Order'];
					$Ds_MerchantCode      = $redsysOrderParamsArray['Ds_MerchantCode'];
					$Ds_Currency          = $redsysOrderParamsArray['Ds_Currency'];
					$Ds_Response          = $redsysOrderParamsArray['Ds_Response'];
					$Ds_Signature         = $jInput->getString('Ds_Signature');
					$Ds_AuthorisationCode = $redsysOrderParamsArray['Ds_AuthorisationCode'];
					$db                   = JFactory::getDBO();

					$db->setQuery("SELECT SubmissionId FROM #__rsform_submissions AS s WHERE s.FormId='" . $formId . "' AND MD5(CONCAT(s.SubmissionId,s.DateSubmitted)) = '" . $db->escape($code) . "'");
					if ($SubmissionId = $db->loadResult())
					{
						//$db->setQuery("SELECT DISTINCT PropertyValue FROM #__rsform_component_types AS ct INNER JOIN #__rsform_components AS c ON ct.ComponentTypeId=c.ComponentTypeId INNER JOIN #__rsform_properties AS p ON c.ComponentId=p.ComponentId WHERE c.FormId='".$formId."' AND ct.ComponentTypeName='total'");
						//$totalField = $db->loadResult();
						$totalField = 'rsfp_Total';

						$db->setQuery("SELECT sv.FieldName, sv.FieldValue FROM #__rsform_submission_values AS sv WHERE sv.FormId='" . $formId . "' AND sv.SubmissionId='" . $SubmissionId . "'");
						$subObject = $db->loadObjectList('FieldName');

						require_once JPATH_ADMINISTRATOR . '/components/com_jetpvvcommon/helpers/jetpvvcommon.php';
						require_once JPATH_ADMINISTRATOR . '/components/com_jetpvvcommon/helpers/redsys.php';
						$signatureVersionLocal = "HMAC_SHA256_V1";
						$signatureCalc         = JETPVvCommonHelperRedsys::createNotifySignature('rsfp_redsys_key', 1, $redsysOrderParamsB64);

						if ($signatureCalc != $Ds_Signature)
							return false;

						if ((string) $Ds_Amount != (string) (number_format($subObject[$totalField]->FieldValue, 2, '.', '') * 100))
						{
							return false;
						}

						if (($Ds_Response >= 0) && ($Ds_Response <= 99) && isset($Ds_AuthorisationCode))
						{
							$status = 1;
							$db->setQuery("SELECT DateSubmitted FROM #__rsform_submissions AS s WHERE s.FormId='" . $formId . "' AND s.SubmissionId='" . $SubmissionId . "'");
							$date = RSFormProHelper::getDate($db->loadResult());

							if (RSFormProHelper::getConfig('redsys.mail_admin'))
							{
								$db->setQuery("SELECT * FROM #__rsform_forms WHERE FormId='".$formId."'");
								$form = $db->loadObject();

								$mailAdminBody = JText::sprintf('RSFP_REDSYS_MAIL_ADMIN_BODY', $SubmissionId, $date, number_format($Ds_Amount / 100, 2, ',', ''), $Ds_AuthorisationCode);
								$this->sendNotifyMail(JText::sprintf('RSFP_REDSYS_MAIL_ADMIN_SUBJECT', $SubmissionId), $form->AdminEmailTo, $mailAdminBody);
							}
						}
						else
						{
							$status = -1;
						}

						$db->setQuery("UPDATE #__rsform_submission_values AS sv SET sv.FieldValue='" . $status . "' WHERE sv.FieldName='_STATUS' AND sv.FormId='" . $formId . "' AND sv.SubmissionId = '" . $SubmissionId . "'");
						$db->execute();

						$mainframe = JFactory::getApplication();
						$mainframe->triggerEvent('rsfp_afterConfirmPayment', array($SubmissionId));
					}

					jexit('ok');
				}
				else
				{
					// Response page
					if (RSFormProHelper::getConfig('redsys.debug'))
						$this->sendNotifyMail('[Debug] Response data received from POS', RSFormProHelper::getConfig('redsys.debug_email'));
					$formId = $jInput->getInt('formId');
					$code   = $jInput->get('code', '', 'ALNUM');
					$db     = JFactory::getDBO();
					$db->setQuery("SELECT SubmissionId FROM #__rsform_submissions AS s WHERE s.FormId='" . $formId . "' AND MD5(CONCAT(s.SubmissionId,s.DateSubmitted)) = '" . $db->escape($code) . "'");
					if ($SubmissionId = $db->loadResult())
					{
						$layout = $type == 'ok' ? JText::_('RSFP_REDSYS_RESPONSE_OK') : JText::_('RSFP_REDSYS_RESPONSE_KO');
						echo $layout;

						return true;
					}
					else
					{
						echo JText::_('RSFP_REDSYS_SUBMISSION_NOT_FOUND');

						return false;
					}

				}
			}
		}
	}

	function sendNotifyMail($subject, $to = null, $body = null)
	{
		if (!$body)
			$body = '$_POST:' . print_r($_POST, true) . '$_GET:' . print_r($_GET, true) . '$_SERVER:' . print_r($_SERVER, true);
		$config = JFactory::getConfig();
		$from   = version_compare(JVERSION, '3.0.0', 'ge') ? $config->get('mailfrom') : $config->getValue('config.mailfrom');
		if (!$to)
			$to = $from;
		$mail = JFactory::getMailer();
		$mail->addRecipient(trim($to));
		$mail->IsHTML(false);
		$mail->setSender(trim($from));
		$mail->setSubject($subject);
		$mail->setBody($body);

		return $mail->Send();
	}

}

class RSFormProRedsys
{
	public $args = array();
	public $url;

	public static function getInstance()
	{
		static $inst;
		if (!$inst)
		{
			$inst = new RSFormProRedsys;
		}

		return $inst;
	}
}