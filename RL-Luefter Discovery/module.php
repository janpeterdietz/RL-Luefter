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
			$this->SetBuffer('Devices', '{}');
			$this->RegisterTimer("ScanTimer", 0, 'RL_ScanDevices(' . $this->InstanceID . ');');
			
		
			$filter = '.*"VentID":.*';
			$filter .= '.*' . '"' ."new Device". '"'. '.*';
			
			$this->SetReceiveDataFilter($filter);
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

		    $this->ScanDevices();
			$this->SetTimerInterval('ScanTimer', 60 * 1000);
		}

		public function ReceiveData($JSONString)
		{
			//IPS_LogMessage('RL  Discovery ReceiveData', $JSONString);
	
			$data = json_decode($JSONString, true); // neune Geräte
			//IPS_LogMessage('RL  Discovery ReceiveData', print_r($data, true));
			
			$newdevice = json_decode($data['Buffer'], true);
        
            $devices = json_decode($this->GetBuffer('Devices'), true); // lese vorhandene Geräte
			$devices += $newdevice;
            $this->SetBuffer('Devices', json_encode($devices));

			//IPS_LogMessage('RL  Discovery ReceiveData', json_encode($devices) );
	
		}

		private function SendData(string $Payload)
		{
			//IPS_LogMessage('RL  Discovery Senddata', $Payload );
			
			if ($this->HasActiveParent()) 
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
			$this->SetTimerInterval('ScanTimer', 300 * 1000);
			$this->SetBuffer('Devices', '{}'); // gefundene Geräte aus Null setzen
		
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

			$content = $start . $type . $id_luefter_blocksize . $id_luefter . $pw_blocksize . $password . $funcnumber . $datablock;
			
			$this->SendData(utf8_encode($content));
	
		}


		public function GetConfigurationForm()
		{	
			$this->ScanDevices();
			IPS_Sleep(2000);

			$newdevices = json_decode( $this->GetBuffer('Devices'), true);
			
			$availableDevices = [];
			$count = 0;

			foreach($newdevices as $key => $device)
			{
				$availableDevices[$count] = 
					[
						'name' =>  'RL Lüfter ', // . $device['sku'],
						'InstanzID' => '0',
						'Vent_ID' => $key,
						'IPAddress' => $device['ip'],
						'Vent_ID' => $device['Vent_ID'],
						'Vent_Type' => $device['Vent_Type'],		
							'create' => [	
								'moduleID' => '{73E78C43-F612-1FED-F3FD-23B8999F504D}',
								'configuration' => ['Vent_ident' => $key,
													'IPAddress' => $device['ip'],
													'Vent_Type' => $device['Vent_Type'],		
													'Active' => true]
								]
					];
				$count = $count+1;
			}

			$no_new_devices = $count; 
			$lostDevices = [];
			$count = 0;
			foreach (IPS_GetInstanceListByModuleID('{73E78C43-F612-1FED-F3FD-23B8999F504D}') as $instanceID)
			{
				//IPS_LogMessage('Govee Configurator', $instanceID);
				
				$instance_match = false;
				foreach($availableDevices as  $key => $device)
				{	
					if ( ( $availableDevices[$key]['Vent_ID'] == IPS_GetProperty($instanceID,'Vent_ident') )
					or   ( ( $availableDevices[$key]['IPAddress'] == IPS_GetProperty($instanceID,'IPAddress') ) and (IPS_GetProperty($instanceID,'Vent_ident') == ''))) 
					{
						$availableDevices[$key]['instanceID'] = $instanceID;
						$availableDevices[$key]['Vent_ID'] = IPS_GetProperty($instanceID,'Vent_ident' );
						$availableDevices[$key]['IPAddress'] = IPS_GetProperty($instanceID,'IPAddress' );
						$availableDevices[$key]['Vent_Type'] = IPS_GetProperty($instanceID,'Vent_Type' );
						$availableDevices[$key]['deviceactive'] = IPS_GetProperty($instanceID,'Active' );
						$availableDevices[$key]['timerinterval'] = IPS_GetProperty($instanceID,'UpdateInterval' );
						$availableDevices[$key]['name'] = IPS_GetName($instanceID);	
						$instance_match = true;
					}
				}
			
				if (!$instance_match)
				{
					$lostDevices[$count]['Vent_ID'] = IPS_GetProperty($instanceID,'Vent_ident' );
					$lostDevices[$count]['IPAddress'] = IPS_GetProperty($instanceID,'IPAddress' );
					$lostDevices[$count]['Vent_Type'] = IPS_GetProperty($instanceID,'Vent_Type' );
					$lostDevices[$count]['instanceID'] = $instanceID;
					$lostDevices[$count]['deviceactive'] = IPS_GetProperty($instanceID,'Active' );
					$lostDevices[$count]['timerinterval'] = IPS_GetProperty($instanceID,'UpdateInterval' );
					$lostDevices[$count]['name'] = IPS_GetName($instanceID);
					$count = $count +1;
				}
			}

			foreach($lostDevices as $key => $device)
			{	
				$availableDevices[$key+$no_new_devices] = $lostDevices[$key];
			}

			if (count($availableDevices) == 0)
			{
				$availableDevices[0]['name'] = 'no devices found';	
			}
				

			return json_encode([
			
				"actions" => [
					[
						'type' => 'Configurator', 
						'caption'=> 'RL Lüfter Konfigurator',
						'delete' => true,
						'columns' => [
								[
									'name' => 'name',
									'caption' => 'Name',
									'width' => 'auto'
								],
								[
									'name' => 'Vent_ID',
									'caption' => 'Device Identifier',
									'width' => '200px'
								],
								[
									'name' => 'Vent_Type',
									'caption' => 'Ventilator Type',
									'width' => '300px'
								],
								[
									'name' => 'IPAddress',
									'caption' => 'IP Adress',
									'width' => '150px'
								],
								[
									'name' =>'deviceactive',
									'caption' => 'Active',
									'width' => '150px'
								],
								[
									'name' =>'timerinterval',
									'caption' => 'Timer Interval',
									'width' => '150px'
								]
						],
						'values' => $availableDevices
					]
				]
			]);
		}

	}