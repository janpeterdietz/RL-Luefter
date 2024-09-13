<?php

declare(strict_types=1);
	class RL_LuefterKonfigurator extends IPSModule
	{
		public function Create()
		{
			//Never delete this line!
			parent::Create();
			$this->ConnectParent('{B62FAC0C-B4EE-9669-4FA3-334D4BD50E3D}');
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

			$filter = 'Configurator';
			$this->SetReceiveDataFilter($filter);
		}

		public function GetConfigurationForm()
		{	
			// hier müsste wohl Scan Device rein??
			//IPS_LogMessage('Govee Configurator', GVL_GetDevices(34857));

			foreach (IPS_GetInstanceListByModuleID('{B6AC3538-BFB4-042F-3586-72B6FE863E3E}') as $instanceID)
			{
				$discoveryID = $instanceID;
			}

			//IPS_LogMessage('Konfigurator',  $discoveryID);

			$newdevices = json_decode( RL_GetNewDevices($discoveryID), true);
		
			//IPS_LogMessage('Konfigurator', print_r( $newdevices, true));
			
			$availableDevices = [];
			$count = 0;
			foreach($newdevices as $key => $device)
			{
    			//IPS_LogMessage('Govee Configurator', $key);
			
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

			$count = 0;
			foreach (IPS_GetInstanceListByModuleID('{73E78C43-F612-1FED-F3FD-23B8999F504D}') as $instanceID)
			{
				//IPS_LogMessage('Govee Configurator', $instanceID);
				
				$instance_match = false;
				if ($no_new_devices >= 1)
				{
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
							$count = $count+1;
						}
					}
				}	 
				
				if (!$instance_match)
				{
					$availableDevices[$count + $no_new_devices]['Vent_ID'] = IPS_GetProperty($instanceID,'Vent_ident' );
					$availableDevices[$count + $no_new_devices]['IPAddress'] = IPS_GetProperty($instanceID,'IPAddress' );
					$availableDevices[$count + $no_new_devices]['Vent_Type'] = IPS_GetProperty($instanceID,'Vent_Type' );
					$availableDevices[$count + $no_new_devices]['instanceID'] = $instanceID;
					$availableDevices[$count + $no_new_devices]['deviceactive'] = IPS_GetProperty($instanceID,'Active' );
					$availableDevices[$count + $no_new_devices]['timerinterval'] = IPS_GetProperty($instanceID,'UpdateInterval' );
					$availableDevices[$count + $no_new_devices]['name'] = IPS_GetName($instanceID);
					$count = $count+1;
				}
			}

			if (count($availableDevices) == 0)
			{
				$availableDevices[$count]['name'] = 'no devices found';	
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