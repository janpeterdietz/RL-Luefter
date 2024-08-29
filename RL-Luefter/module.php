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
				IPS_SetVariableProfileValues ('RLV.Powermode', 1, 255, );
				IPS_SetVariableProfileAssociation('RLV.Powermode', 0x01, $this->Translate("Level 1"),"" , -1);
				IPS_SetVariableProfileAssociation('RLV.Powermode', 0x02, $this->Translate("Level 2"),"" , -1);
				IPS_SetVariableProfileAssociation('RLV.Powermode', 0x03, $this->Translate("Level 3"),"" , -1);
				IPS_SetVariableProfileAssociation('RLV.Powermode', 0xFF, $this->Translate("Manuel"),"" , -1);
			}

			if (!IPS_VariableProfileExists('RLV.Operatingmode')) 
			{
				IPS_CreateVariableProfile('RLV.Operatingmode', VARIABLETYPE_INTEGER);
				IPS_SetVariableProfileText('RLV.Operatingmode', '', '');
				IPS_SetVariableProfileValues ('RLV.Operatingmode', 1, 3, );
				IPS_SetVariableProfileAssociation('RLV.Operatingmode', 0x01, $this->Translate("Exhaust Air"),"" , -1);
				IPS_SetVariableProfileAssociation('RLV.Operatingmode', 0x02, $this->Translate("Heat recovery"),"" , -1);
				IPS_SetVariableProfileAssociation('RLV.Operatingmode', 0x03, $this->Translate("supply air"),"" , -1);
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

			$this->RegisterTimer("UpdateSensorData", ($this->ReadPropertyInteger("UpdateInterval"))*1000, 'RL_RequestStatus(' . $this->InstanceID . ');');

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
		
				$this->SetTimerInterval('Updatestate', $this->ReadPropertyInteger('UpdateInterval') * 1000);
                $this->SetStatus(102);
            } else 
			{
                $this->SetTimerInterval('Updatestate', 0);
                $this->SetStatus(104);
            }



			$IPAddress = $this->ReadPropertyString("IPAddress");
			$this->SetSummary($IPAddress);

		
			$filter = '.*"ClientIP":.*';
			$filter .= '.*' . '"' . $IPAddress. '"'. '.*';
			
			$this->SetReceiveDataFilter($filter);
			
		}

		public function ReceiveData($JSONString)
		{
			$data = json_decode($JSONString);
			IPS_LogMessage('Device RECV', $data->Buffer . ' - ' . $data->ClientIP . ' - ' . $data->ClientPort);
			
			$id_luefter = $this->ReadPropertyString("Vent_ident");
			$data = utf8_decode($data->Buffer) ;

			// Lüfter ID Auswerten
			$id_read = substr($data, 4, 16);  

			if ( strcmp($id_read, $id_luefter) != 0 )
			{
				IPS_LogMessage("Lüfter Auslesen ", "Lüfter ID falsch");
				return;
			}
			
			$func = hexdec( bin2hex($data[$position = 25]) )  ; 
			
			//IPS_LogMessage("Lüfter Auslesen ", strlen($data) );

			if ($func == 0x06 )
			{    
				$i = 26;
				while ( $i <= (strlen($data) - 3) )
				{   
					$i = $this->read_paremter( $data, $i);
					if  ( $i === false) 
					{
						IPS_LogMessage("Lüfter Auslesen ", "Anzahl Paramter Fehler");
						return;    
					};      
				}
			}
			else
			{
				IPS_LogMessage("Lüfter Auslesen ", "func ungleich 6: $func");
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
            		'ClientPort' => 0,
					'Broadcast' => false
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

			$checksum = $this->calc_checksumm( $start . $type . $id_luefter_blocksize . $id_luefter . $pw_blocksize . $password . $funcnumber . $datablock );

			$content = $start . $type . $id_luefter_blocksize . $id_luefter . $pw_blocksize . $password . $funcnumber . $datablock . $checksum;
			
			$this->SendData(utf8_encode($content));
	
		}

		private function send_parameter( string $datablock  )
		{
			$start = hex2bin('FDFD');
			$type = hex2bin('02');

			//$id_luefter = '0028001856565108'; // Schlafzimmer
			$id_luefter = $this->ReadPropertyString("Vent_ident");
			$id_luefter_blocksize = hex2bin('10');

			$password = '1111';
			$pw_blocksize = hex2bin('04'); //chr(strlen($password));
			
			$funcnumber = hex2bin('03'); // Parameter Schreiben mit Antwort

			$checksum = $this->Calc_Checksumm( $start . $type . $id_luefter_blocksize . $id_luefter . $pw_blocksize . $password . $funcnumber . $datablock );

			$content = $start . $type . $id_luefter_blocksize . $id_luefter . $pw_blocksize . $password . $funcnumber . $datablock . $checksum;

			$this->SendData(utf8_encode($content));
		}

		private function read_paremter( string $data, int $position )
		{

			$Parameter_Id = hexdec( bin2hex($data[$position]) ) ; 

			//IPS_LogMessage("Lüfter Auslesen ", "Parameter $Parameter_Id");
			
			switch ($Parameter_Id)
			{
				case 0x01: // Status
					$Status = hexdec( bin2hex($data[$position +1]) )  ; 
					if ($Status == 0)
					{
						$Status = false;
					}
					else
					{
						$Status = true;
					}

					$this->SetValue('State', $Status);
					$position = $position +2;
				break;   

				case 0x02: // Leistungsstufe
					$Leistungsstufe = hexdec( bin2hex($data[$position +1]) ); 
					$this->SetValue('Powermode', $Leistungsstufe);
					$position = $position +2;
				break; 
			
				case 0x44: // Geschwindigkeit
					$Speed = hexdec( bin2hex($data[$position +1]) )  ; 
					
					if ($Speed == 0)
					{
						$this->SetValue('Speed', 0);
					}
					else
					{
						$this->SetValue('Speed', round($Speed * 100 /255));
					}
					$position = $position +2;
				break; 

				case 0x24: // Batterie Spannung RTC
					$Level = 256 * hexdec( bin2hex($data[$position +2]) )  ;
					$Level = $Level + hexdec( bin2hex($data[$position +1]) );
					$this->SetValue('RTC_Batterie_Voltage', $Level);
					$position = $position +3;
				break;

				case 0x25: // Feuchte
					$Humidity = hexdec( bin2hex($data[$position +1]) )  ; 
					$this->SetValue('Humidity', $Humidity);
					$position = $position +2;
				break;

				case 0x64: // Zeit bis Filterwechsel
					$this->SetValue('time_to_filter_cleaning', hexdec( bin2hex($data[$position +3]) ) . " Tage " . hexdec( bin2hex($data[$position + 2]) ) . " Stunden " . hexdec( bin2hex($data[$position +1]) ) . " Minuten"  );
					$position = $position + 4;
				break;

				case 0x72: // Zeit gestuerter Betrieb
					$position = $position + 2;
				break;

				case 0x83: // Alarm
					$Alarm = hexdec( bin2hex($data[$position +1]) )  ; 
					$this->SetValue('Systemwarning', $Alarm);
					$position = $position +2;
				break;

				case 0x88: // Filterwechsel Aufforderung
					if (bin2hex($data[$position +1]) == 0)  
					{
						$this->SetValue('Filtercleaning', false);
					}
					else
					{
						$this->SetValue('Filtercleaning', true);
					}
					$position = $position + 2;
				break;

				case 0xB7: // Operating_mode
					$mode = hexdec( bin2hex($data[$position +1]) ); 
					$this->SetValue('Operatingmode', $mode);
					$position = $position +2;
				break;

				case 0xFE: // Spezial Befehl (Nächster Befehler hat Überlänge)
					$position = $position + 2;
				break;

				default: // ???
					IPS_LogMessage("Lüfter Auslesen ", "Parameter nicht bekannt $Parameter_Id");
					return false;
				break;       
			}			
			
			return  $position;
		}

		private function translate_paramter( string $ident, int $value)
		{
    		//IPS_LogMessage("Lüfter Parameter Setzen ", "Name: $ident Wert: $value");

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
					if ($value == 1)
					{
						$value = hex2bin('01');
					}
					else if ($value == 2)
					{
						$value = hex2bin('02');
					}
					else if ($value == 3)
					{
						$value = hex2bin('03');
					}
					else if ($value == 0xFF)
					{
						$value = hex2bin('FF');
					}
					else 
					{
						break;
					}
					$para = hex2bin('02');
					$datablock = $para . $value;
				break; 

				
				case "Speed":
					$value = (integer) round($value * 255 / 100);
					
					if ($value >= 255)
					{
						$value = 255;
					}
					$value = dechex($value);
					
					if (strlen($value) <= 1)
					{
						if ($value == '0') $value = hex2bin('00');
						if ($value == '1') $value = hex2bin('01');
						if ($value == '2') $value = hex2bin('02');
						if ($value == '3') $value = hex2bin('03');
						if ($value == '4') $value = hex2bin('04');
						if ($value == '5') $value = hex2bin('05');
						if ($value == '6') $value = hex2bin('06');
						if ($value == '7') $value = hex2bin('07');
						if ($value == '8') $value = hex2bin('08');
						if ($value == '9') $value = hex2bin('09');
						if ($value == 'a') $value = hex2bin('0a');
						if ($value == 'b') $value = hex2bin('0b');
						if ($value == 'c') $value = hex2bin('0c');
						if ($value == 'd') $value = hex2bin('0d');
						if ($value == 'e') $value = hex2bin('0e');
						if ($value == 'f') $value = hex2bin('0f');
					}
					else
					{
						$value = hex2bin($value);
					}
			
					$para = hex2bin('44');

					$datablock = $para . $value;
				break;   

		
				case "Operatingmode": 
					
					if ($value == 0)
					{
						$value = hex2bin('00');
					}
					else if ($value == 1)
					{
						$value = hex2bin('01');
					}
					else if ($value == 2)
					{
						$value = hex2bin('02');
					}
					else 
					{
						break;
					}
					$para = hex2bin('B7');
					$datablock = $para . $value;
				break;   

				default:
					IPS_LogMessage("Lüfter Parameter Setzen ", "Variable nicht veränderbar");
				break;
			}

			return $datablock;
		}

		private function Calc_Checksumm( string $data )
		{
			$i = 0; 
			$chksum = 0;
			$chksum2 = hex2bin('0000');
			$chksumHexNeu = hex2bin('0000');
		
			$size = strlen($data);
		   
			if(  ($data[0] == hex2bin('FD')) and ($data[1] == hex2bin('FD')) )         //and ($data[1] == "\xFD"))
			{
				for($i = 2; $i <= ($size-1); $i++)
				{
					$chksum = $chksum + ord($data[$i]);
				}
				
				$chksumHex = dechex($chksum);
			   
				$size = strlen($chksumHex);
				if ($size <= 1)
				{
					$chksumHexNeu[0] = '0';
					$chksumHexNeu[1] = '0';
					$chksumHexNeu[2] = '0';
					$chksumHexNeu[3] = $chksumHex[0];
				}
				else if ($size == 2)
				{
					$chksumHexNeu[0] = '0';
					$chksumHexNeu[1] = '0';
					$chksumHexNeu[2] = $chksumHex[0];
					$chksumHexNeu[3] = $chksumHex[1];
				}
				else if ($size == 3)
				{
					$chksumHexNeu[0] = '0';
					$chksumHexNeu[1] = $chksumHex[0];
					$chksumHexNeu[2] = $chksumHex[1];
					$chksumHexNeu[3] = $chksumHex[2];
				}
				else if ($size == 4)
				{
					$chksumHexNeu[0] = $chksumHex[0];
					$chksumHexNeu[1] = $chksumHex[1];
					$chksumHexNeu[2] = $chksumHex[2];
					$chksumHexNeu[3] = $chksumHex[3];
				}
				else
				{
					return false;
				}
		
				$chksum2 = hex2bin($chksumHexNeu);
				return $chksum2[1] . $chksum2[0];
			}
			else
			{
				return false;
			}
		}


	}