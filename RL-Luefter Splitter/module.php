<?php

declare(strict_types=1);
	class RL_LuefterSplitter extends IPSModule
	{
		public function Create()
		{
			//Never delete this line!
			parent::Create();
			$this->ForceParent('{82347F20-F541-41E1-AC5B-A636FD3AE2D8}'); //UDP Port anfordern

			$this->RegisterAttributeString('Devices', '{}');

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

			$config = json_decode( $this->GetConfigurationForParent(), true); // UDP Port konfigurieren
			$this->SetSummary('UDP Config: ' . $config['BindIP'] .":". $config['BindPort']);
		}

		public function GetConfigurationForParent() //Set UBD Port
        {
            $settings = [
                'BindPort'           => 5000,
				'BindIP'           => '0.0.0.0',
                'EnableBroadcast'    => true,
                'EnableReuseAddress' => true,
                'Host'               => '',
                'Port'               => 4000,
				"Open"				=> true
            ];

            return json_encode($settings, JSON_UNESCAPED_SLASHES);
		}

		public function ForwardData($JSONString)
		{
			$data = json_decode($JSONString);
			$payload = utf8_decode($data->Buffer);
			$checksum = $this->calc_checksumm( $payload);
			$payload = $payload . $checksum;
			


			$this->SendDataToParent(json_encode(
				[
				'DataID' => '{8E4D9B23-E0F2-1E05-41D8-C21EA53B8706}', 
				'Buffer' => utf8_encode($payload), 
				'ClientIP' => $data->ClientIP,
            	'ClientPort' => $data->ClientPort,
				'EnableBroadcast' => true,
				'Broadcast' => $data->Broadcast
				]));

			return 'String data for device instance!';
		}

		public function ReceiveData($JSONString)
		{
			$data = json_decode($JSONString);
			$ip = $data->ClientIP;
			
			$data = utf8_decode($data->Buffer) ;

			// Lüfter ID Auswerten
			$id_read = substr($data, 4, 16);  
			$devices[$id_read] = ['ip'=> $ip];
			
			$password = '1111';
			$PW_len = hexdec( bin2hex($data[$position = 20]) ); 
			if ($PW_len > 0)
			{
				$PW = substr($data, $position + 1, $PW_len);  
				if ( strcmp($PW, $password) != 0 )
				{
					$this->LogMessage("PW Länge $PW_len Passwort falsch $PW", KL_NOTIFY);
					//return;
				}
			}
			
			$position += ($PW_len +1);

			$func = hexdec( bin2hex($data[$position]) )  ; 
			$position += 1;

			if (($func == 0x06 ))
			{    
				$i = $position;
				while ( $i <= (strlen($data) - 3) )
				{   
					$i = $this->read_paremter( $data, $i , $devices);
					if  ( $i === false) 
					{
						$this->LogMessage("Anzahl Paramter Fehler", KL_NOTIFY);
						return;    
					};      
				}
			}
			else
			{
				$this->LogMessage( "func ungleich 6: $func", KL_NOTIFY);
			}

			if ($PW_len == 0)
			{
				$vent_id = 'new Device';
			}
			else
			{
				$vent_id = $id_read;
			}

			$this->SendDataToChildren(json_encode(
				[
				'DataID' => '{58B9F909-B1CE-13FA-2BA8-1B377446CAED}', 
				'Buffer' => json_encode($devices) ,
				'VentID' => $vent_id,
				]
				));

		}

		private function read_paremter( string $data, int $position, &$devices )
		{
			$Parameter_Id = hexdec( bin2hex($data[$position]) ) ; 
			$id_luefter = key($devices);

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

					$devices[$id_luefter] += ['State'=> $Status];
					$position = $position +2;
				break;   

				case 0x02: // Leistungsstufe
					$Leistungsstufe = hexdec( bin2hex($data[$position +1]) ); 
					$devices[$id_luefter] += ['Powermode'=> $Leistungsstufe];
					$position = $position +2;
				break; 
			
				case 0x44: // Geschwindigkeit
					$Speed = hexdec( bin2hex($data[$position +1]) )  ; 
					$devices[$id_luefter] += ['Speed'=> round($Speed * 100 /255)];
					$position = $position +2;
				break; 

				case 0x24: // Batterie Spannung RTC
					$Level = 256 * hexdec( bin2hex($data[$position +2]) )  ;
					$Level = $Level + hexdec( bin2hex($data[$position +1]) );
					$devices[$id_luefter] += ['RTC_Batterie_Voltage'=> $Level];
					$position = $position +3;
				break;

				case 0x25: // Feuchte
					$Humidity = hexdec( bin2hex($data[$position +1]) )  ; 
					$devices[$id_luefter] += ['Humidity'=> $Humidity];
					$position = $position +2;
				break;

				case 0x64: // Zeit bis Filterwechsel
					$devices[$id_luefter] += ['time_to_filter_cleaning'=> (hexdec( bin2hex($data[$position +3]) ) . " Tage " . hexdec( bin2hex($data[$position + 2]) ) . " Stunden " . hexdec( bin2hex($data[$position +1]) ) . " Minuten" ) ];
					$position = $position + 4;
				break;

				case 0x72: // Zeit gestuerter Betrieb
					$position = $position + 2;
				break;

				case 0x7C: // ID
					$ID= substr($data, $position +1, 16); 
					$devices[$id_luefter] += ['Vent_ID'=> $ID];
					$position = $position + 17;
				break;

				case 0x83: // Alarm
					$Alarm = hexdec( bin2hex($data[$position +1]) )  ; 
					$devices[$id_luefter] += ['Systemwarning'=> $Alarm];
					$position = $position +2;
				break;

				case 0x88: // Filterwechsel Aufforderung
					if (bin2hex($data[$position +1]) == 0)  
					{
						$devices[$id_luefter] += ['Filtercleaning'=> false];
					}
					else
					{
						$devices[$id_luefter] += ['Filtercleaning'=> true];
					}
					$position = $position + 2;
				break;

				case 0xB9: // Anlagentyp
					$AnlageTyp = hexdec( bin2hex($data[$position +1]));
					switch ($AnlageTyp)
					{
						case 3:
							$devices[$id_luefter] += ['Vent_Type'=> "TwinFresh Expert RX1-xxx V.2"];
						break;
						case 4:
							$devices[$id_luefter] += ['Vent_Type'=> "TwinFresh Expert Duo RW-30 V.2"];
						break;
						case 5:
							$devices[$id_luefter] += ['Vent_Type'=> "TwinFresh Expert RW-30 V.2"];
						break;
						default:
							$devices[$id_luefter] += ['Vent_Type'=> "unbekannt ($AnlageTyp)"];
						break;

					}

					$position = $position + 3;
				break;

				case 0xB7: // Operating_mode
					$mode = hexdec( bin2hex($data[$position +1]) ); 
					$devices[$id_luefter] += ['Operatingmode'=> $mode];
					$position = $position +2;
				break;

				case 0xFE: // Spezial Befehl (Nächster Befehler hat Überlänge)
					//$position = $position + 2;
					$parameterlen = hexdec( bin2hex($data[$position +1]) );
					$parameter = ( bin2hex($data[$position +2]) );

					//$this->LogMessage("Spezialbefehl: Position: $position, parameterlen: $parameterlen  parameter: 0x$parameter", KL_NOTIFY);
				
					$position = $position + $parameterlen + 3;

					break;

				//$Parameter_Id = hexdec($Parameter_Id);
				default: // ???
					$this->LogMessage("Parameter nicht bekannt: $Parameter_Id Position: $position", KL_NOTIFY);
					return false;
				break;       
			}			
			
			return  $position;
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