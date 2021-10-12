INSERT IGNORE INTO `#__rsform_config` (`SettingName`, `SettingValue`) VALUES
('redsys.pos_payment_type', 'A'),
('redsys.pos_mode', 'sim'),
('redsys.prod_pos_url', 'https://sis.redsys.es/sis/realizarPago'),
('redsys.sim_pos_url', 'https://sis-t.redsys.es:25443/sis/realizarPago'),
('redsys.Ds_Merchant_Currency', '978'),
('redsys.Ds_Merchant_ProductDescription', ''),
('redsys.Ds_Merchant_MerchantCode', ''),
('redsys.Ds_Merchant_MerchantName', ''),
('redsys.Ds_Merchant_ConsumerLanguage', '0'),
('redsys.Ds_Merchant_Terminal', '1'),
('redsys.Ds_Merchant_TransactionType', '0'),
('redsys.signature_type', 'extended'),
('redsys.mail_admin', '1'),
('redsys.debug', '0'),
('redsys.debug_email', ''),
('redsys.key', '');

INSERT IGNORE INTO `#__rsform_component_types` (`ComponentTypeId`, `ComponentTypeName`) VALUES (1500, 'redsys');

DELETE FROM #__rsform_component_type_fields WHERE ComponentTypeId = 1500;
INSERT IGNORE INTO `#__rsform_component_type_fields` (`ComponentTypeId`, `FieldName`, `FieldType`, `FieldValues`, `Ordering`) VALUES
(1500, 'NAME', 'textbox', '', 0),
(1500, 'LABEL', 'textbox', '', 1),
(1500, 'COMPONENTTYPE', 'hidden', '1500', 2),
(1500, 'LAYOUTHIDDEN', 'hiddenparam', 'YES', 7);