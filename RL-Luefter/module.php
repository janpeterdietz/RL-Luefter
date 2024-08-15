<?php

declare(strict_types=1);
	class RL_Luefter extends IPSModule
	{
		public function Create()
		{
			//Never delete this line!
			parent::Create();

			$this->RequireParent('{82347F20-F541-41E1-AC5B-A636FD3AE2D8}');

			$this->RegisterPropertyBoolean('Active', false);
			
		
			$this->RegisterVariableBoolean ("State", $this->Translate("State"),  "~Switch", 10) ;
			$this->RegisterVariableInteger('Powermode', $this->Translate('Powermode'), '', 20);
			$this->RegisterVariableInteger('Speed', $this->Translate('Speed'), '', 30);
			$this->RegisterVariableInteger('Operatingmode', $this->Translate('Operatingmode'), '', 40);
			
			$this->RegisterVariableInteger ("Humidity", $this->("Humidity"), "~Humidity" , 50) ;
			$this->RegisterVariableBoolean ("Filtercleaning", $this->("Filter cleaning"), "~Alert" , 60) ;
			$this->RegisterVariableInteger ("time_to_filter_cleaning", $this->("Time until filter cleaning"), "" , 70) ;
			$this->RegisterVariableInteger ("Systemwarning", $this->("Systemwarning"), "" , 80) ;
			$this->RegisterVariableInteger ("RTC_Batterie_Voltage", $this->("RTC Batterie Voltage"), "" , 90) ;
			

			$this->EnableAction('State');
			$this->EnableAction('Powermode');
			$this->EnableAction('Speed');
			$this->EnableAction('Operatingmode');
			
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
		}

		public function Send(string $Text, string $ClientIP, int $ClientPort)
		{
			$this->SendDataToParent(json_encode(['DataID' => '{C8792760-65CF-4C53-B5C7-A30FCC84FEFE}', "ClientIP" => $ClientIP, "ClientPort" => $ClientPort, "Buffer" => $Text]));
		}

		public function ReceiveData($JSONString)
		{
			$data = json_decode($JSONString);
			IPS_LogMessage('Device RECV', utf8_decode($data->Buffer . ' - ' . $data->ClientIP . ' - ' . $data->ClientPort));
		}
	}