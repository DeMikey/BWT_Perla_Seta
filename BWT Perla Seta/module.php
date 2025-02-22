<?php

declare(strict_types=1);
	class BWTPerlaSeta extends IPSModule
	{
		public function Create()
		{
			//Never delete this line!
			parent::Create();

			$this->RegisterPropertyString("IPAddress", '0.0.0.0');
			$this->RegisterPropertyString("Username", 'user');
			$this->RegisterPropertyString("Password", '');
			$this->RegisterPropertyBoolean("HTTPUpdateTimer", false);
			$this->RegisterPropertyBoolean("DailyData", false);
			$this->RegisterPropertyBoolean("MonthlyData", false);
			$this->RegisterPropertyBoolean("YearlyData", false);
			$this->RegisterPropertyBoolean("DebugLog", false);

			// Timer
			$this->RegisterTimer("BWTPerla_UpdateTimer", 0, 'BWTPerla_Update($_IPS[\'TARGET\']);');
		}

		public function Destroy()
		{
			//Never delete this line!
			parent::Destroy();
		}

		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();

			// Generate Profiles & Variables
	        $this->registerProfiles();
    	    $this->registerVariables();

        	// Set Timer
        	if ($this->ReadPropertyBoolean("HTTPUpdateTimer")) {
            	$this->SetTimerInterval("BWTPerla_UpdateTimer", 60000);
        	} else {
            	$this->SetTimerInterval("BWTPerla_UpdateTimer", 0);
        	}
		}

		#================================================================================================
		public function Update() {
		#================================================================================================
		
			$semaphore = 'BWTPerla'.$this->InstanceID.'_Update';
			$this->log('Update - Try to enter Semaphore');
			if (IPS_SemaphoreEnter($semaphore, 0) == false) {
				$this->log('Update - Semaphore not avaible');
				return false;
			};
			$this->log('Update - Semaphore entered');
	
			// HTTP status request
			$data = $this->SendHTTPCommand('GetCurrentData');
			$this->log('Empfangene Daten:');
			if ($data == false) {
				IPS_SemaphoreLeave($semaphore);
				$this->log('Update - Keine Daten empfangen');
				$this->log('Update - Semaphore leaved');
				return false;
			} else {
				// set values to variables
				$this->log(print_r($data, true));
				$this->log(print_r(array_keys($data),true));
		
				//--- Identification
				if ($data['ActiveErrorIDs'] === "") {
					$this->SetValue("ActiveErrorIDs", 0);
				} else {
					$this->SetValue("ActiveErrorIDs", $data['ActiveErrorIDs']);
				}
				$this->SetValue("BlendedWaterSinceSetup_l", $data['BlendedWaterSinceSetup_l']);
	
				$this->SetValue("CapacityColumn1_ml_dH", $data['CapacityColumn1_ml_dH']);
				$this->SetValue("CapacityColumn2_ml_dH", $data['CapacityColumn2_ml_dH']);
				$this->SetValue("CurrentFlowrate_l_h", $data['CurrentFlowrate_l_h']);
				$this->SetValue("DosingSinceSetup_ml", $data['DosingSinceSetup_ml']);
				$this->SetValue("FirmwareVersion", $data['FirmwareVersion']);
				$this->SetValue("HardnessIN_CaCO3", $data['HardnessIN_CaCO3']);
				$this->SetValue("HardnessIN_dH", $data['HardnessIN_dH']);
				$this->SetValue("HardnessIN_fH", $data['HardnessIN_fH']);
				$this->SetValue("HardnessIN_mmol_l", $data['HardnessIN_mmol_l']);
				$this->SetValue("HardnessOUT_CaCO3", $data['HardnessOUT_CaCO3']);
				$this->SetValue("HardnessOUT_dH", $data['HardnessOUT_dH']);
				$this->SetValue("HardnessOUT_fH", $data['HardnessOUT_fH']);
				$this->SetValue("HardnessOUT_mmol_l", $data['HardnessOUT_mmol_l']);
				$this->SetValue("HolidayModeStartTime", $data['HolidayModeStartTime']);
				$this->SetValue("LastRegenerationColumn1", strtotime($data['LastRegenerationColumn1']));
				$this->SetValue("LastRegenerationColumn2", strtotime($data['LastRegenerationColumn2']));
				$this->SetValue("LastServiceCustomer", strtotime($data['LastServiceCustomer']));
				$this->SetValue("LastServiceTechnican", strtotime($data['LastServiceTechnican']));
				$this->SetValue("OutOfService", $data['OutOfService']);
				$this->SetValue("RegenerationCounterColumn1", $data['RegenerationCounterColumn1']);
				$this->SetValue("RegenerationCounterColumn2", $data['RegenerationCounterColumn2']);
				$this->SetValue("RegenerationCountSinceSetup", $data['RegenerationCountSinceSetup']);
				$this->SetValue("RegenerativLevel", $data['RegenerativLevel']);
				$this->SetValue("RegenerativRemainingDays", $data['RegenerativRemainingDays']);
				$this->SetValue("RegenerativSinceSetup_g", $data['RegenerativSinceSetup_g']);
				$this->SetValue("ShowError", $data['ShowError']);
				$this->SetValue("WaterTreatedCurrentDay_l", $data['WaterTreatedCurrentDay_l']);
				$this->SetValue("WaterTreatedCurrentMonth_l", $data['WaterTreatedCurrentMonth_l']);
				$this->SetValue("WaterTreatedCurrentYear_l", $data['WaterTreatedCurrentYear_l']);
			}
			// Get Daily Data
			if ($this->ReadPropertyBoolean("DailyData")) {
				$data = $this->SendHTTPCommand('GetDailyData');
				if ($data == false) {
					IPS_SemaphoreLeave($semaphore);
					$this->log('Update - Keine Tages Ststistik Daten empfangen');
					$this->log('Update - Semaphore leaved');
					return false;
				} else {
					$DailyParent = $this->GetIDForIdent('WaterTreatedCurrentDay_l');
					$this->log('Update - Tages Kategorie Id: ' . $DailyParent);
					for ($i = 0; $i <= 23; $i++) {
						if ($i < 10) {
							$Hour = "0" . $i;
						} else {
							$Hour = $i;
						}
						$this->log("Daily key: " . $Hour .  "00_" . $Hour . "29_l");
						if ($VarId = @IPS_GetObjectIDByIdent($Hour . "00" . $Hour . "29", $DailyParent)) {
							SetValue($VarId, $data[$Hour .  "00_" . $Hour . "29_l"]);
						}
						if ($VarId = @IPS_GetObjectIDByIdent($Hour . "30" . $Hour . "59", $DailyParent)) {
							SetValue($VarId, $data[$Hour .  "30_" . $Hour . "59_l"]);
						}
					}
				}
			}
			if ($this->ReadPropertyBoolean("MonthlyData")) {
				// Get Health Data
				$data = $this->SendHTTPCommand('GetMonthlyData');
				if ($data == false) {
					IPS_SemaphoreLeave($semaphore);
					$this->log('Update - Keine Monats Ststistik Daten empfangen');
					$this->log('Update - Semaphore leaved');
					return false;
				} else {
					$MontlyDataCategory = $this->GetIDForIdent('WaterTreatedCurrentMonth_l');
					$this->log('Update - Monats Kategorie Id: ' . $MontlyDataCategory);
					for ($i = 1; $i <= 31; $i++) {
						if ($i < 10) {
							$Day = "0" . $i;
						} else {
							$Day = $i;
						}
						$this->log("Montly key: " . "Day" . $Day . "_l");
						if ($VarId = @IPS_GetObjectIDByIdent("Day" . $Day, $MontlyDataCategory)) {
							SetValue($VarId, $data["Day" . $Day . "_l"]);
						}
					}
				}
			}
			if ($this->ReadPropertyBoolean("YearlyData")) {
				// Get Health Data
				$data = $this->SendHTTPCommand('GetYearlyData');
				if ($data == false) {
					IPS_SemaphoreLeave($semaphore);
					$this->log('Update - Keine Jahres Ststistik Daten empfangen');
					$this->log('Update - Semaphore leaved');
					return false;
				} else {
					$YearlyDataCategory = $this->GetIDForIdent('WaterTreatedCurrentYear_l');
					$this->log('Update - Jahres Kategorie Id: ' . $YearlyDataCategory);
					for ($i = 1; $i <= 12; $i++) {
						if ($i < 10) {
							$Year = "0" . $i;
						} else {
							$Year = $i;
						}
						$this->log("Montly key: " . "Month" . $Year . "_l");
						if ($VarId = @IPS_GetObjectIDByIdent("Month" . $Year, $YearlyDataCategory)) {
							SetValue($VarId, $data["Month" . $Year . "_l"]);
						}
					}
				}
			}
			IPS_SemaphoreLeave($semaphore);
			$this->log('Update - Semaphore leaved');
		}
	

		#================================================================================================
    	protected function SendHTTPCommand($command) {
        #================================================================================================
        	$IPAddress = trim($this->ReadPropertyString("IPAddress"));
        	$Username = trim($this->ReadPropertyString("Username"));
        	$Password = trim($this->ReadPropertyString("Password"));
        	$this->log('SendHTTPCommand - Begin');
        	// check if IP is ocnfigured and valid
        	if ($IPAddress == "0.0.0.0") {
            	$this->SetStatus(200); // no configuration done
            	return false;
        	} elseif (filter_var($IPAddress, FILTER_VALIDATE_IP) == false) {
            	$this->SetStatus(201); // no valid IP configured
            	return false;
        	}
        	$this->log('Http Request send');
        	switch ($command) {
            	case 'GetDailyData':
                	$URL = 'http://' . $IPAddress . ':8080/api/GetDailyData';
                	break;
				case 'GetMonthlyData':
					$URL = 'http://' . $IPAddress . ':8080/api/GetMonthlyData';
					break;
				case 'GetYearlyData':
					$URL = 'http://' . $IPAddress . ':8080/api/GetYearlyData';
					break;
				default:
                	$URL = 'http://' . $IPAddress . ':8080/api//GetCurrentData';
                	break;
        	}
			try {
				$ch = curl_init();
				curl_setopt_array($ch, [
					CURLOPT_URL => $URL,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
					CURLOPT_USERPWD => $Username . ':' . $Password,
					CURLOPT_TIMEOUT => 30,
					CURLOPT_RETURNTRANSFER => 1,
					CURLOPT_HEADER => 1
				]);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				$response = curl_exec($ch);
				curl_close($ch);
				$this->log('Http Request finished');
			} catch (Exception $e) {
				curl_close($ch);
				$this->log('Http Request on error');
				$this->SetStatus(203); // no valid IP configured
				return false;
			};
			if (curl_error($ch)) {
				$this->log((curl_error($ch)));
				$this->SetStatus(202); // No Device at IP
				$this->log('SendHTTPCommand - End');
				return false;
			}
			$this->log("Response: " . $response);
			$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$header = substr($response, 0, $header_size);
			$body = substr($response, $header_size);
			$this->log("Header: " . $header);
			$this->log("Body: " . $body);
			if (curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200) {
				$this->log("BWT Perla found");
				$this->SetStatus(102); // BWT Perl found
			} else {
				$this->log("Other Device found");
				$this->SetStatus(202); // No Device at IP
			}
        	$this->log('SendHTTPCommand - End');
			return json_decode(utf8_encode($body), true, 1000, JSON_INVALID_UTF8_IGNORE);
    	}

    	#================================================================================================
		protected function log( string $text ) {
    	#================================================================================================
		if ( $this->ReadPropertyBoolean("DebugLog") ) {
            	$this->SendDebug( "BWT Perla", $text, 0 );
        	};
    	}

		#================================================================================================
		protected function RegisterDailyStatisticVariables(int $Parent){
		#================================================================================================
			for ($i = 0; $i <= 23; $i++) {
				if ($i < 10) {
					$Hour = "0" . $i;
				} else {
					$Hour = $i;
				}
				if (!@IPS_GetObjectIDByIdent($Hour . "00" . $Hour . "29", $Parent)) {
					IPS_SetParent($this->RegisterVariableInteger($Hour . "00" . $Hour . "29", $Hour . ":00-" . $Hour . ":29", "BWTPerla_Liter", 100 . $i), $Parent); 
				}
				if (!@IPS_GetObjectIDByIdent($Hour . "30" . $Hour . "59", $Parent)) {
					IPS_SetParent($this->RegisterVariableInteger($Hour . "30" . $Hour . "59", $Hour . ":30-" . $Hour . "-59", "BWTPerla_Liter", 100 . $i), $Parent); 
				}
			}
		}

		#===============================================================================================
		protected function UnregisterDailyStatisticVariables(int $Parent) {
		#===============================================================================================
			for ($i = 0; $i <= 23; $i++) {
				if ($i < 10) {
					$Hour = "0" . $i;
				} else {
					$Hour = $i;
				}
				if ($VarId = @IPS_GetObjectIDByIdent($Hour . "00" . $Hour . "29", $Parent)) {
					if ($Parent == $this->InstanceID) {
						$this->UnregisterVariable($Hour . "00" . $Hour . "29");
					} else {
						IPS_DeleteVariable($VarId);
					}
				}
				if ($VarId = @IPS_GetObjectIDByIdent($Hour . "30" . $Hour . "59", $Parent)) {
					if ($Parent == $this->InstanceID) {
						$this->UnregisterVariable($Hour . "30" . $Hour . "59");
					} else {
						IPS_DeleteVariable($VarId);
					}
				}
			}
		}

		#================================================================================================
		protected function RegisterMontlyStatisticVariables(int $Parent) {
		#================================================================================================
			for ($i = 1; $i <= 31; $i++) {
				if ($i < 10) {
					$Day = "0" . $i;
				} else {
					$Day = $i;
				}
				if (!@IPS_GetObjectIDByIdent("Day" . $Day, $Parent)) {
					IPS_SetParent($this->RegisterVariableInteger("Day" . $Day, $this->TRanslate("Day") . " " . $Day, "BWTPerla_Liter", 200 . $i), $Parent); 
				}
			}
		}

		#===============================================================================================
		protected function UnregisterMontlyStatisticVariables(int $Parent) {
		#===============================================================================================
			for ($i = 1; $i <= 31; $i++) {
				if ($i < 10) {
					$Day = "0" . $i;
				} else {
					$Day = $i;
				}
				if ($VarId = @IPS_GetObjectIDByIdent("Day" . $Day, $Parent)) {
					if ($Parent == $this->InstanceID) {
						$this->UnregisterVariable("Day" . $Day);
					} else {
						IPS_DeleteVariable($VarId);
					}
				}
			} 
		}

		#===============================================================================================
		protected function RegisterYearlyStatisticVariables(int $Parent) {
		#===============================================================================================
			for ($i = 1; $i <= 12; $i++) {
				if ($i < 10) {
					$Year = "0" . $i;
				} else {
					$Year = $i;
				}
				if (!@IPS_GetObjectIDByIdent("Month" . $Year, $Parent)) {
					IPS_SetParent($this->RegisterVariableInteger("Month" . $Year, $this->Translate("Month") . " " . $Year, "BWTPerla_Liter", 300 . $i), $Parent); 
				}
			}
		}

		#===============================================================================================
		protected function UnregisterYearlyStatisticVariables(int $Parent) {
		#===============================================================================================
			for ($i = 1; $i <= 12; $i++) {
				if ($i < 10) {
					$Year = "0" . $i;
				} else {
					$Year = $i;
				}
				if ($VarId = @IPS_GetObjectIDByIdent("Month" . $Year, $Parent)) {
					if ($Parent == $this->InstanceID) {
						$this->UnregisterVariable("Month" . $Year);
					} else {
						IPS_DeleteVariable ($VarId);
					} 
				}
			} 
		}

    	#================================================================================================
		protected function registerVariables() {
    	#================================================================================================

			//--- Basic Data ---------------------------------------------------------
			$this->RegisterVariableInteger("ActiveErrorIDs", $this->Translate("ActiveErrorIDs"), "BWTPerla_ErrorCode", 10);
			$this->RegisterVariableString("FirmwareVersion", $this->Translate("FirmwareVersion"), "", 11);			
			$this->RegisterVariableInteger("HolidayModeStartTime", $this->Translate("HolidayModeStartTime"), "", 12 );
			$this->RegisterVariableBoolean("OutOfService", $this->Translate("OutOfService"), "BWTPerla_Switch", 13 );
			$this->RegisterVariableBoolean("ShowError", $this->Translate("ShowError"), "BWTPerla_Switch", 14 );
#			$this->RegisterVariableString("LastServiceCustomer", $this->Translate("LastServiceCustomer"), "", 15);
			$this->RegisterVariableInteger("LastServiceCustomer", $this->Translate("LastServiceCustomer"), "~UnixTimestamp", 15);
			$this->RegisterVariableInteger("LastServiceTechnican", $this->Translate("LastServiceTechnican"), "~UnixTimestamp", 16);
			$this->RegisterVariableInteger("BlendedWaterSinceSetup_l", $this->Translate("BlendedWaterSinceSetup_l"), "BWTPerla_Liter", 17 );
			$this->RegisterVariableInteger("DosingSinceSetup_ml", $this->Translate("DosingSinceSetup_ml"), "BWTPerla_Milliliter", 18 );
			$this->RegisterVariableInteger("RegenerationCountSinceSetup", $this->Translate("RegenerationCountSinceSetup"), "", 19 );
			$this->RegisterVariableInteger("RegenerativLevel", $this->Translate("RegenerativLevel"), "BWTPerla_Prozent", 20);
			$this->RegisterVariableInteger("RegenerativRemainingDays", $this->Translate("RegenerativRemainingDays"), "BWTPerla_Tage", 21);
			$this->RegisterVariableInteger("RegenerativSinceSetup_g", $this->Translate("RegenerativSinceSetup_g"), "BWTPerla_Gramm", 22);
			$this->RegisterVariableInteger("CurrentFlowrate_l_h", $this->Translate("CurrentFlowrate_l_h"), "BWTPerla_Liter_pro_Stunde", 23 );
			$this->RegisterVariableInteger("CapacityColumn1_ml_dH", $this->Translate("CapacityColumn1_ml_dH"), "BWTPerla_Milliliter_Deutsche_Haertungsgrad", 24);
			$this->RegisterVariableInteger("CapacityColumn2_ml_dH", $this->Translate("CapacityColumn2_ml_dH"), "BWTPerla_Milliliter_Deutsche_Haertungsgrad", 25 );
			$this->RegisterVariableInteger("HardnessIN_CaCO3", $this->Translate("HardnessIN_CaCO3"), "BWTPerla_ppm_CaCO3", 26 );
			$this->RegisterVariableInteger("HardnessOUT_CaCO3", $this->Translate("HardnessOUT_CaCO3"), "BWTPerla_ppm_CaCO3", 27 );
			$this->RegisterVariableInteger("HardnessIN_dH", $this->Translate("HardnessIN_dH"), "BWTPerla_Deutsche_Haertungsgrad", 28 );
			$this->RegisterVariableInteger("HardnessOUT_dH", $this->Translate("HardnessOUT_dH"), "BWTPerla_Deutsche_Haertungsgrad", 29 );
			$this->RegisterVariableInteger("HardnessIN_fH", $this->Translate("HardnessIN_fH"), "BWTPerla_Franzoesische_Haertungsgrad", 30 );
			$this->RegisterVariableInteger("HardnessOUT_fH", $this->Translate("HardnessOUT_fH"), "BWTPerla_Franzoesische_Haertungsgrad", 31 );
			$this->RegisterVariableInteger("HardnessIN_mmol_l", $this->Translate("HardnessIN_mmol_l"), "BWTPerla_MilliMol_pro_Liter", 32 );
			$this->RegisterVariableInteger("HardnessOUT_mmol_l", $this->Translate("HardnessOUT_mmol_l"), "BWTPerla_MilliMol_pro_Liter", 33 );
			$this->RegisterVariableInteger("LastRegenerationColumn1", $this->Translate("LastRegenerationColumn1"), "~UnixTimestamp", 34);
			$this->RegisterVariableInteger("LastRegenerationColumn2", $this->Translate("LastRegenerationColumn2"), "~UnixTimestamp", 35);
			$this->RegisterVariableInteger("RegenerationCounterColumn1", $this->Translate("RegenerationCounterColumn1"), "", 36);
			$this->RegisterVariableInteger("RegenerationCounterColumn2", $this->Translate("RegenerationCounterColumn2"), "", 37);
			$this->RegisterVariableInteger("WaterTreatedCurrentDay_l", $this->Translate("WaterTreatedCurrentDay_l"), "BWTPerla_Liter", 100);
			$this->RegisterVariableInteger("WaterTreatedCurrentMonth_l", $this->Translate("WaterTreatedCurrentMonth_l"), "BWTPerla_Liter", 200);
			$this->RegisterVariableInteger("WaterTreatedCurrentYear_l", $this->Translate("WaterTreatedCurrentYear_l"), "BWTPerla_Liter", 300);

			//---- Variable für Archiv konfigurieren
			$archiveID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
			if (!AC_GetLoggingStatus ($archiveID, $this->GetIDForIdent("WaterTreatedCurrentDay_l"))) {
				AC_SetLoggingStatus($archiveID, $this->GetIDForIdent("WaterTreatedCurrentDay_l"), true);
				AC_SetAggregationType($archiveID, $this->GetIDForIdent("WaterTreatedCurrentDay_l"), 1);
			}

			//---- Statistik
			// Daliy Statistik 
			if ($this->ReadPropertyBoolean("DailyData")) {
				// Erstellen der Daily Variabeln
				if ($DailyParent = $this->GetIDForIdent("WaterTreatedCurrentDay_l")) {
					// Variablen werden erstellt
					$this->RegisterDailyStatisticVariables($DailyParent);
				}
			} else {
				// Löschen der Daily Variabeln
				if ($DailyParent = $this->GetIDForIdent("WaterTreatedCurrentDay_l")) {
					$this->UnregisterDailyStatisticVariables($DailyParent);
				}
			}
			// Monthly Statistik
			if ($this->ReadPropertyBoolean("MonthlyData")) {
				if ($MonthlyParent = $this->GetIDForIdent('WaterTreatedCurrentMonth_l')) {
					// Variabeln werden erstellt
					$this->RegisterMontlyStatisticVariables($MonthlyParent);
				}
			} else {
				// Löschen der Montly Variabeln
				if ($MonthlyParent = $this->GetIDForIdent('WaterTreatedCurrentMonth_l')) {
					// Variabeln werden gelöscht
					$this->UnregisterMontlyStatisticVariables($MonthlyParent);
				}
			}
			// Yearly Statistick
			if ($this->ReadPropertyBoolean("YearlyData")) {
				if ($YearlyParent = $this->GetIDForIdent('WaterTreatedCurrentYear_l')) {
					// Variabeln erstellen
					$this->RegisterYearlyStatisticVariables($YearlyParent);
				}
			} else {
				// Löschen der Yearly Variabeln
				if ($YearlyParent = $this->GetIDForIdent('WaterTreatedCurrentYear_l')) {
					// Löschen der Variabeln
					$this->UnregisterYearlyStatisticVariables($YearlyParent);
				}
			}
		}

    	#================================================================================================
		protected function registerProfiles() {
    	#================================================================================================
			// Generate Variable Profiles
			if (!IPS_VariableProfileExists('BWTPerla_ErrorCode')) {
				IPS_CreateVariableProfile('BWTPerla_ErrorCode', 1);
				IPS_SetVariableProfileIcon('BWTPerla_ErrorCode', '');
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 0, $this->Translate("Error Code 0"), "", 0x00FF00);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 1, $this->Translate("Error Code 1"), "", 0xFF0000);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 2, $this->Translate("Error Code 2"), "", 0xFF0000);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 3, $this->Translate("Error Code 3"), "", 0xFF0000);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 5, $this->Translate("Warning Code 5"), "", 0xFFFF00);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 8, $this->Translate("Error Code 8"), "", 0xFF0000);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 9, $this->Translate("Error Code 9"), "", 0xFF0000);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 10, $this->Translate("Error Code 10"), "", 0xFF0000);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 12, $this->Translate("Error Code 12"), "", 0xFF0000);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 13, $this->Translate("Error Code 13"), "", 0xFF0000);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 14, $this->Translate("Error Code 14"), "", 0xFF0000);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 15, $this->Translate("Warning Code 15"), "", 0xFFFF00);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 16, $this->Translate("Warning Code 16"), "", 0xFFFF00);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 21, $this->Translate("Error Code 21"), "", 0xFF0000);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 22, $this->Translate("Error Code 22"), "", 0xFF0000);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 25, $this->Translate("Warning Code 25"), "", 0xFFFF00);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 26, $this->Translate("Error Code 26"), "", 0xFF0000);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 27, $this->Translate("Error Code 27"), "", 0xFF0000);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 32, $this->Translate("Warning Code 32"), "", 0xFFFF00);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 33, $this->Translate("Warning Code 33"), "", 0xFFFF00);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 34, $this->Translate("Warning Code 34"), "", 0xFFFF00);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 35, $this->Translate("Warning Code 35"), "", 0xFFFF00);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 36, $this->Translate("Warning Code 36"), "", 0xFFFF00);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 43, $this->Translate("Error Code 43"), "", 0xFF0000);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 44, $this->Translate("Error Code 44"), "", 0xFF0000);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 45, $this->Translate("Error Code 45"), "", 0xFF0000);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 46, $this->Translate("Error Code 46"), "", 0xFF0000);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 54, $this->Translate("Warning Code 54"), "", 0xFFFF00);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 55, $this->Translate("Warning Code 55"), "", 0xFFFF00);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 56, $this->Translate("Error Code 56"), "", 0xFF0000);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 57, $this->Translate("Error Code 57"), "", 0xFF0000);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 58, $this->Translate("Error Code 58"), "", 0xFF0000);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 59, $this->Translate("Error Code 59"), "", 0xFF0000);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 61, $this->Translate("Warning Code 61"), "", 0xFFFF00);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 62, $this->Translate("Warning Code 62"), "", 0xFFFF00);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 63, $this->Translate("Warning Code 63"), "", 0xFFFF00);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 64, $this->Translate("Warning Code 64"), "", 0xFFFF00);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 65, $this->Translate("Error Code 65"), "", 0xFF0000);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 66, $this->Translate("Warning Code 66"), "", 0xFFFF00);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 67, $this->Translate("Warning Code 67"), "", 0xFFFF00);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 68, $this->Translate("Error Code 68"), "", 0xFF0000);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 74, $this->Translate("Warning Code 74"), "", 0xFFFF00);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 75, $this->Translate("Warning Code 75"), "", 0xFFFF00);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 88, $this->Translate("Warning Code 88"), "", 0xFFFF00);
			}
		
			if (!IPS_VariableProfileExists('BWTPerla_Switch')) {
				IPS_CreateVariableProfile('BWTPerla_Switch', 0);
				IPS_SetVariableProfileIcon('BWTPerla_Switch', '');
				IPS_SetVariableProfileAssociation("BWTPerla_Switch", 0, $this->Translate("Off"), "", 0x00FF00);
				IPS_SetVariableProfileAssociation("BWTPerla_Switch", 1, $this->Translate("On"), "", 0xFF0000);
			}

			if (!IPS_VariableProfileExists('BWTPerla_Liter')) {
				IPS_CreateVariableProfile('BWTPerla_Liter', 1);
				IPS_SetVariableProfileText('BWTPerla_Liter', '', ' l');
			}

			if (!IPS_VariableProfileExists('BWTPerla_Milliliter_Deutsche_Haertungsgrad')) {
				IPS_CreateVariableProfile('BWTPerla_Milliliter_Deutsche_Haertungsgrad', 1);
				IPS_SetVariableProfileText('BWTPerla_Milliliter_Deutsche_Haertungsgrad', '', ' ml*°dH');
			}

			if (!IPS_VariableProfileExists('BWTPerla_Liter_pro_Stunde')) {
				IPS_CreateVariableProfile('BWTPerla_Liter_pro_Stunde', 1);
				IPS_SetVariableProfileText('BWTPerla_Liter_pro_Stunde', '', ' l/h');
			}

			if (!IPS_VariableProfileExists('BWTPerla_Milliliter')) {
				IPS_CreateVariableProfile('BWTPerla_Milliliter', 1);
				IPS_SetVariableProfileText('BWTPerla_Milliliter', '', ' ml');
			}
	
			if (!IPS_VariableProfileExists('BWTPerla_ppm_CaCO3')) {
				IPS_CreateVariableProfile('BWTPerla_ppm_CaCO3', 1);
				IPS_SetVariableProfileText('BWTPerla_ppm_CaCO3', '', ' ppm CaCO3');
			}

			if (!IPS_VariableProfileExists('BWTPerla_Deutsche_Haertungsgrad')) {
				IPS_CreateVariableProfile('BWTPerla_Deutsche_Haertungsgrad', 1);
				IPS_SetVariableProfileText('BWTPerla_Deutsche_Haertungsgrad', '', ' °dH');
			}

			if (!IPS_VariableProfileExists('BWTPerla_Franzoesische_Haertungsgrad')) {
				IPS_CreateVariableProfile('BWTPerla_Franzoesische_Haertungsgrad', 1);
				IPS_SetVariableProfileText('BWTPerla_Franzoesische_Haertungsgrad', '', ' °fH');
			}

			if (!IPS_VariableProfileExists('BWTPerla_MilliMol_pro_Liter')) {
				IPS_CreateVariableProfile('BWTPerla_MilliMol_pro_Liter', 1);
				IPS_SetVariableProfileText('BWTPerla_MilliMol_pro_Liter', '', ' mmol/l');
			}

			if (!IPS_VariableProfileExists('BWTPerla_Tage')) {
				IPS_CreateVariableProfile('BWTPerla_Tage', 1);
				IPS_SetVariableProfileText('BWTPerla_Tage', '', ' Tage');
			}

			if (!IPS_VariableProfileExists('BWTPerla_Prozent')) {
				IPS_CreateVariableProfile('BWTPerla_Prozent', 1);
				IPS_SetVariableProfileText('BWTPerla_Prozent', '', ' %');
			}

			if (!IPS_VariableProfileExists('BWTPerla_Gramm')) {
				IPS_CreateVariableProfile('BWTPerla_Gramm', 1);
				IPS_SetVariableProfileText('BWTPerla_Gramm', '', ' g');
			}
		}		

	}