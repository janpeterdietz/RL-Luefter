<?php

declare(strict_types=1);
	class RL_LuefterDiscovery extends IPSModule
	{
		public function Create()
		{
			//Never delete this line!   
			parent::Create();

			$this->ConnectParent('{B62FAC0C-B4EE-9669-4FA3-334D4BD50E3D}');

			$this->RegisterPropertyBoolean('Active', false);
			$this->RegisterAttributeString('Devices', '{}');

			$this->RegisterTimer("ScanTimer", 0, 'RL_ScanDevices(' . $this->InstanceID . ');');
			
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

			$filter = '.*"VentID":.*';
			$filter .= '.*' . '"' ."new Device". '"'. '.*';
			
			$this->SetReceiveDataFilter($filter);

			if ($this->ReadPropertyBoolean('Active')) 
			{
                $this->ScanDevices();
				$this->SetTimerInterval('ScanTimer', 60 * 1000);
                $this->SetStatus(102);
            } else {
                $this->SetTimerInterval('ScanTimer', 0);
                $this->SetStatus(104);
            }
		}

		public function ReceiveData($JSONString)
		{
			//IPS_LogMessage('RL  Discovery ReceiveData', $JSONString);
	
			$data = json_decode($JSONString, true); // neune Geräte
			//IPS_LogMessage('RL  Discovery ReceiveData', print_r($data, true));
			
			$newdevice = json_decode($data['Buffer'], true);
        
            $devices = json_decode($this->ReadAttributeString('Devices'), true); // lese vorhandene Geräte
			$devices += $newdevice;
            $this->WriteAttributeString('Devices', json_encode($devices));

			IPS_LogMessage('RL  Discovery ReceiveData', json_encode($devices) );
	
		}

		public function SendData(string $Payload)
		{
			//IPS_LogMessage('RL  Discovery Senddata', $Payload );
			
			//if ($this->HasActiveParent()) 
			{
				$this->SendDataToParent(json_encode([
				
					'DataID' => '{4E2090FD-8113-C239-622E-BCA354396964}',
					'Buffer' => $Payload,
					'ClientIP'=> '239.255.255.250',
					'ClientPort' => 4000,
					'Broadcast' => true,
					'EnableBroadcast' => true,
				]));
			}
		}

		public function ScanDevices()
		{
			$this->WriteAttributeString('Devices', '{}');

			$start = hex2bin('FDFD');
			$type = hex2bin('02'); // Vorgegeben 

			$id_luefter = 'DEFAULT_DEVICEID';
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

			$datablock = hex2bin('7C');  // Device ID auslesen

			$checksum = $this->calc_checksumm( $start . $type . $id_luefter_blocksize . $id_luefter . $pw_blocksize . $password . $funcnumber . $datablock );
			
			$content = $start . $type . $id_luefter_blocksize . $id_luefter . $pw_blocksize . $password . $funcnumber . $datablock . $checksum;
			
			$this->SendData(utf8_encode($content));
	
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