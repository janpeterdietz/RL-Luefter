<?php

declare(strict_types=1);
	class RL_Luefter extends IPSModule
	{
		public function Create()
		{
			//Never delete this line!   
			parent::Create();

			$this->ConnectParent('{B62FAC0C-B4EE-9669-4FA3-334D4BD50E3D}');
			

			if (!IPS_VariableProfileExists('RLV.Powermode')) 
			{
				IPS_CreateVariableProfile('RLV.Powermode', VARIABLETYPE_INTEGER);
				IPS_SetVariableProfileText('RLV.Powermode', '', '');
				IPS_SetVariableProfileValues ('RLV.Powermode', 1, 255, 0);
				IPS_SetVariableProfileAssociation('RLV.Powermode', 0x01, $this->Translate("Level 1"),"" , -1);
				IPS_SetVariableProfileAssociation('RLV.Powermode', 0x02, $this->Translate("Level 2"),"" , -1);
				IPS_SetVariableProfileAssociation('RLV.Powermode', 0x03, $this->Translate("Level 3"),"" , -1);
				IPS_SetVariableProfileAssociation('RLV.Powermode', 0xFF, $this->Translate("Manuel"),"" , -1);
			}

			if (!IPS_VariableProfileExists('RLV.Operatingmode')) 
			{
				IPS_CreateVariableProfile('RLV.Operatingmode', VARIABLETYPE_INTEGER);
				IPS_SetVariableProfileText('RLV.Operatingmode', '', '');
				IPS_SetVariableProfileValues ('RLV.Operatingmode', 0, 2, 0);
				IPS_SetVariableProfileAssociation('RLV.Operatingmode', 0x00, $this->Translate("Exhaust air"),"" , -1);
				IPS_SetVariableProfileAssociation('RLV.Operatingmode', 0x01, $this->Translate("Heat recovery"),"" , -1);
				IPS_SetVariableProfileAssociation('RLV.Operatingmode', 0x02, $this->Translate("supply air"),"" , -1);
			}

			if (!IPS_VariableProfileExists('RLV.AlertLevel')) 
			{
				IPS_CreateVariableProfile('RLV.AlertLevel', VARIABLETYPE_INTEGER);
				IPS_SetVariableProfileText('RLV.AlertLevel', '', '');
				IPS_SetVariableProfileValues ('RLV.AlertLevel', 0, 2, 0);
				IPS_SetVariableProfileAssociation('RLV.AlertLevel', 0x00, $this->Translate("OK"),'' , 0x7FFF00);
				IPS_SetVariableProfileAssociation('RLV.AlertLevel', 0x01, $this->Translate("Warning"),'' , 0xFFFF00);
				IPS_SetVariableProfileAssociation('RLV.AlertLevel', 0x02, $this->Translate("Alarm"),'' , 0xFF0000);
			}

			if (!IPS_VariableProfileExists('RLV.mV')) 
			{
				IPS_CreateVariableProfile('RLV.mV', VARIABLETYPE_INTEGER);
				IPS_SetVariableProfileText('RLV.mV', '', ' mV');
			}


			
			$this->RegisterPropertyBoolean('Active', false);
			$this->RegisterPropertyString('Vent_ident', false);
			$this->RegisterPropertyInteger("UpdateInterval", 60);
			$this->RegisterPropertyString("IPAddress", "192.168.178.1");
			$this->RegisterPropertyString("Vent_Type", "RL Lüfter");

		
			$this->RegisterVariableBoolean ("State", $this->Translate("State"),  "~Switch", 10) ;
			$this->RegisterVariableInteger('Powermode', $this->Translate('Powermode'), 'RLV.Powermode', 20);
			$this->RegisterVariableInteger('Speed', $this->Translate('Speed'), '~Intensity.100', 30);
			$this->RegisterVariableInteger('Operatingmode', $this->Translate('Operatingmode'), 'RLV.Operatingmode', 40);
			
			$this->RegisterVariableInteger ("Humidity", $this->Translate("Humidity"), "~Humidity" , 50) ;
			$this->RegisterVariableBoolean ("Filtercleaning", $this->Translate("Filter cleaning"), "~Alert" , 60) ;
			$this->RegisterVariableString ("time_to_filter_cleaning", $this->Translate("Time until filter cleaning"), "" , 70) ;
			$this->RegisterVariableInteger ("Systemwarning", $this->Translate("Systemwarning"), "RLV.AlertLevel" , 80) ;
			$this->RegisterVariableInteger ("RTC_Batterie_Voltage", $this->Translate("RTC Batterie Voltage"), 'RLV.mV' , 90) ;
			

			$this->EnableAction('State');
			$this->EnableAction('Powermode');
			$this->EnableAction('Speed');
			$this->EnableAction('Operatingmode');

			$this->RegisterTimer("UpdateData", ($this->ReadPropertyInteger("UpdateInterval"))*1000, 'RL_RequestStatus(' . $this->InstanceID . ');');

			$this->RegisterAttributeString("IP_Adress", ""); 

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

			
			if ($this->ReadPropertyBoolean('Active')) 
			{
				$this->RequestStatus();
		
				$this->SetTimerInterval('UpdateData', $this->ReadPropertyInteger('UpdateInterval') * 1000);
                $this->SetStatus(102);
            } else 
			{
                $this->SetTimerInterval('UpdateData', 0);
                $this->SetStatus(104);
            }



			$IPAddress = $this->ReadPropertyString("IPAddress");
			$this->SetSummary($IPAddress);
			
			$Vent_ident = $this->ReadPropertyString("Vent_ident");
			
		
			$filter = '.*"VentID":.*';
			$filter .= '.*' . '"' . $Vent_ident. '"'. '.*';
			
			$this->SetReceiveDataFilter($filter);
			
		}

		public function ReceiveData($JSONString)
		{
			$data = json_decode($JSONString, true);

			$data = json_decode($data['Buffer'], true);
			$key = key($data);
		
			if (array_key_exists('State', $data[$key]))
			{
				$this->SetValue('State', $data[$key]['State']);
			}
			if (array_key_exists('Powermode', $data[$key]))
			{
				$this->SetValue('Powermode', $powermode = $data[$key]['Powermode']);
				if ($powermode == 255)
				{
					IPS_SetHidden($this->GetIDForIdent('Speed'), false);
				}
				else
				{
					IPS_SetHidden($this->GetIDForIdent('Speed'), true);
				}
			}
			if (array_key_exists('Speed', $data[$key]))
			{
				$this->SetValue('Speed', $data[$key]['Speed']);
			}
			if (array_key_exists('RTC_Batterie_Voltage', $data[$key]))
			{
				$this->SetValue('RTC_Batterie_Voltage', $data[$key]['RTC_Batterie_Voltage']);
			}
			if (array_key_exists('Humidity', $data[$key]))
			{
				$this->SetValue('Humidity', $data[$key]['Humidity']);
			}
			if (array_key_exists('time_to_filter_cleaning', $data[$key]))
			{
				$this->SetValue('time_to_filter_cleaning', $data[$key]['time_to_filter_cleaning']);
			}
			if (array_key_exists('Systemwarning', $data[$key]))
			{
				$this->SetValue('Systemwarning', $data[$key]['Systemwarning']);
			}
			if (array_key_exists('Filtercleaning', $data[$key]))
			{
				$this->SetValue('Filtercleaning',  $data[$key]['Filtercleaning']);
			}
			if (array_key_exists('Operatingmode', $data[$key]))
			{
				$this->SetValue('Operatingmode',  $data[$key]['Operatingmode']);
			}
		}

		public function SetValueEx(array $data)
        {
			$datablock = "";
			if (array_key_exists('Speed', $data))
			{
				$datablock = $datablock . $this->translate_paramter( 'Speed', $data['Speed'] );
			}
	
			if (array_key_exists('State', $data))
			{
				$datablock = $datablock . $this->translate_paramter( 'State', (int)$data['State'] );
			}
			
			if (array_key_exists('Powermode', $data))
			{
				$datablock = $datablock . $this->translate_paramter( 'Powermode', $data['Powermode'] );
			}
	
			if (array_key_exists('Operatingmode', $data))
			{
				$datablock = $datablock . $this->translate_paramter( 'Operatingmode', $data['Operatingmode'] );
			}
	
			if (strlen($datablock) >= 2)
			{
				$this->send_parameter($datablock );
			}
	
        }


		public function RequestAction($Ident, $Value)
        {
            switch ($Ident) 
			{
                case 'State':
					$datablock = $this->translate_paramter( $Ident, (int)$Value);
					$this->send_parameter( $datablock );
					break;
                case 'Powermode':
                    $datablock = $this->translate_paramter( $Ident, $Value);
					$this->send_parameter( $datablock );
					break;
				case 'Speed':
					$datablock = $this->translate_paramter( $Ident, $Value);
					$this->send_parameter( $datablock );
					break;
				case 'Operatingmode':
					$datablock = $this->translate_paramter( $Ident, $Value);
					$this->send_parameter( $datablock );
					break;
                default:
                    $this->SendDebug(__FUNCTION__, 'Invalid Action: ' . $Ident, 0);
                    return;
					break;
            }
        }

		public function SendData(string $Payload)
		{
			if ($this->HasActiveParent()) 
			{
				$this->SendDataToParent(json_encode([
					'DataID' => '{4E2090FD-8113-C239-622E-BCA354396964}',
					'Buffer' => $Payload,
					'ClientIP' => $this->ReadPropertyString("IPAddress"),	
            		'ClientPort' => 4000,
					'Broadcast' => false,
					'EnableBroadcast' => false
				]));
			}
		}

		public function RequestStatus()
		{
			$start = hex2bin('FDFD');
			$type = hex2bin('02');

			$id_luefter = $this->ReadPropertyString("Vent_ident");
			$id_luefter_blocksize = hex2bin('10');

			$password = '1111';
			$pw_blocksize = hex2bin('04'); //chr(strlen($password));
			
			$funcnumber = hex2bin('01'); //Datenabfrage
			//01 = Status
			//02 = Lüfterstufe
			//24 = Spannung Batterie RTC
			//25 = Feuchte
			//44 = Lüfter Speed bei Manuel
			//64 = Zeit bis Filterwechsel
			//83 = Alarm Level
			//88 = Filterwechel Aufforderung
			//B7 = Betriebsart des Ventilators
			
			$datablock = hex2bin('0102242544648388B7');  
		
			$content = $start . $type . $id_luefter_blocksize . $id_luefter . $pw_blocksize . $password . $funcnumber . $datablock;// . $checksum;
			
			$this->SendData(utf8_encode($content));
		}

		
		
		public function ResetFilterClean( )
		{
			
			$cleaned = hex2bin('65ff'); // Filterwarnung zurückstezen
			
			$this->send_parameter($cleaned);
		}

		private function send_parameter( string $datablock  )
		{
			$start = hex2bin('FDFD');
			$type = hex2bin('02');

			$id_luefter = $this->ReadPropertyString("Vent_ident");
			$id_luefter_blocksize = hex2bin('10');

			$password = '1111';
			$pw_blocksize = hex2bin('04'); //chr(strlen($password));
			
			$funcnumber = hex2bin('03'); // Parameter Schreiben mit Antwort

			$content = $start . $type . $id_luefter_blocksize . $id_luefter . $pw_blocksize . $password . $funcnumber . $datablock;// . $checksum;

			$this->SendData(utf8_encode($content));
		}

		private function translate_paramter( string $ident, int $value)
		{
			switch ($ident)
			{
				case "State": 
					if ($value)
					{
						$value = hex2bin('01');
					}
					else
					{
						$value = hex2bin('00');
					}
					$para = hex2bin('01');
					$datablock = $para . $value;
				break; 
				
				case "Powermode": 			
					if (($value < 1) or (($value > 3) and ($value != 255)))
					{
						$this->LogMessage("Powermode setzen,  Falscher Wert $value", KL_NOTIFY);
						$value = 0xff;
					}
					$para = hex2bin('02');
					$datablock = $para . sprintf('%c', $value);
				break; 

				
				case "Speed":
					$value = (integer) round($value * 255 / 100);
					
					if ($value >= 255)
					{
						$value = 255;
					}
					
					$para = hex2bin('44');
					$datablock = $para . sprintf('%c', $value);
				break;   

		
				case "Operatingmode": 				
					if (($value < 0) or ($value > 2))
					{
						$this->LogMessage("Lüfter Operatingmode Setzen . Falsher Wert $value", KL_NOTIFY);
						$value = 1;
					}
					$para = hex2bin('B7');
					$datablock = $para . sprintf('%c', $value);
				break;   

				default:
				$this->LogMessage("Lüfter Parameter Setzen . Variable nicht veränderbar", KL_NOTIFY);
				break;
			}

			return $datablock;
		}

	}