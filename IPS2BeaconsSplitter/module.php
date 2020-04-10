<?
    // Klassendefinition
    class IPS2BeaconsSplitter extends IPSModule 
    {
	public function Destroy() 
	{
		//Never delete this line!
		parent::Destroy();
		$this->SetTimerInterval("TimerPing", 0);
	}  
	    
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		$this->RequireParent("{BAB408E0-0A0F-48C3-B14E-9FB2FA81F66A}"); // Multicast Socket
		$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyInteger("TimerPing", 60);
		$this->RegisterTimer("TimerPing", 0, 'IPS2BeaconsSplitter_ClientPing($_IPS["TARGET"]);');
		
		// Status-Variablen anlegen
		$this->RegisterProfileInteger("IPS2Beacons.ClientState", "Information", "", "", 0, 3, 0);
		IPS_SetVariableProfileAssociation("IPS2Beacons.ClientState", 0, "Keine bekannten Clients", "Information", -1);
		IPS_SetVariableProfileAssociation("IPS2Beacons.ClientState", 1, "Keine bekannten Clients sind online", "Warning", 0xff0000);
		IPS_SetVariableProfileAssociation("IPS2Beacons.ClientState", 2, "Nicht alle bekannten Clients sind online", "Warning", 0xffff00);
		IPS_SetVariableProfileAssociation("IPS2Beacons.ClientState", 3, "Alle bekannten Clients sind online", "Ok", 0x00ff00);
		
		$this->RegisterVariableInteger("LastUpdate", "Letztes Update", "~UnixTimestamp", 10);
		$this->RegisterVariableInteger("ClientState", "Client Status", "IPS2Beacons.ClientState", 20);
		
		$ClientIP = array();
		$this->RegisterAttributeString("ClientIP", serialize($ClientIP));
		$BeaconList = array();
		$this->RegisterAttributeString("BeaconList", serialize($BeaconList));
        }
 	
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 202, "icon" => "error", "caption" => "Kommunikationfehler!");
				
		$arrayElements = array(); 
		$arrayElements[] = array("name" => "Open", "type" => "CheckBox",  "caption" => "Aktiv");
		$arrayElements[] = array("type" => "Label", "label" => "Zyklische Verfügbarkeitsprüfung per Ping (Minimum 5 Sekunden)");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "TimerPing", "caption" => "Ping (sek)");

		$arrayActions = array();
		     	
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
 	}       
	   
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
		SetValueInteger($this->GetIDForIdent("ClientState"), 0);
		
		$ParentID = $this->GetParentID();
			
		If ($ParentID > 0) {
			If (IPS_GetProperty($ParentID, 'Port') <> 8173) {
				IPS_SetProperty($ParentID, 'BindPort', 8173);
			}
			If (IPS_GetProperty($ParentID, 'Port') <> 8173) {
				IPS_SetProperty($ParentID, 'BindPort', 8173);
			}
			If (IPS_GetProperty($ParentID, 'Open') <> $this->ReadPropertyBoolean("Open")) {
				IPS_SetProperty($ParentID, 'Open', $this->ReadPropertyBoolean("Open"));
			}
			If (IPS_GetProperty($ParentID, 'EnableBroadcast') <> false) {
				IPS_SetProperty($ParentID, 'EnableBroadcast', false);
			}
			If (IPS_GetProperty($ParentID, 'EnableLoopback') <> false) {
				IPS_SetProperty($ParentID, 'EnableLoopback', false);
			}
			If (IPS_GetProperty($ParentID, 'EnableReuseAddress') <> false) {
				IPS_SetProperty($ParentID, 'EnableReuseAddress', false);
			}
			If (substr(IPS_GetName($ParentID), 0, 16) == "Multicast Socket") {
				IPS_SetName($ParentID, "IPS2Beacons (IPS2BeaconsSplitter #".$this->InstanceID.")");
			}
			if(IPS_HasChanges($ParentID))
			{
				$Result = @IPS_ApplyChanges($ParentID);
				If ($Result) {
					$this->SendDebug("ApplyChanges", "Einrichtung des Multicast Socket erfolgreich", 0);
				}
				else {
					$this->SendDebug("ApplyChanges", "Einrichtung des Multicast Socket nicht erfolgreich!", 0);
				}
			}
		}
		
		
		If ($this->ReadPropertyBoolean("Open") == true) {
			$TimerPing = $this->ReadPropertyInteger("TimerPing");
			$TimerPing = max($TimerPing, 5);
			$this->SetTimerInterval("TimerPing", $TimerPing * 1000);
			$this->ClientPing();
			$this->SetStatus(102);
			
			
		}
		else {
			$this->SetTimerInterval("TimerPing", 0);
			$this->SetStatus(104);
			
		}	
	}
	
	public function ForwardData($JSONString) 
	{
	 	// Empfangene Daten von der Device Instanz
	    	$data = json_decode($JSONString);
	    	$Result = false;
	 	switch ($data->Function) {
			case "getBeaconList":
				$Result = $this->ReadAttributeString("BeaconList");
				break;
			
		}
	return $Result;
	}
	
	public function ReceiveData($JSONString) {	
 	    	// Empfangene Daten vom I/O
	    	$Data = json_decode($JSONString);
	    	$Buffer = utf8_decode($Data->Buffer); 
		
		$MAC = substr($Buffer, 0, 17);
		$BeaconName = trim(substr($Buffer, 18));
		if (filter_var($MAC, FILTER_VALIDATE_MAC)) {
			// Empfangene MAC in Array sichern
			$BeaconList = array();
			$BeaconList = unserialize($this->ReadAttributeString("BeaconList"));
			if (array_key_exists($MAC, $BeaconList) == false) {
				$BeaconList[$MAC]["Name"] = $BeaconName;
				$BeaconList[$MAC]["IP"] = $Data->ClientIP;
				$BeaconList[$MAC]["LastUpdate"] = time();
				$this->SendDebug("ReceiveData", "BeaconList-Array: ".serialize($BeaconList), 0);
			}
			else {
				$BeaconList[$MAC]["IP"] = $Data->ClientIP;
				$BeaconList[$MAC]["LastUpdate"] = time();
			}
			$this->WriteAttributeString("BeaconList", serialize($BeaconList));
		}
		else {
			$this->SendDebug("ReceiveData", "Beacon MAC ungueltig!", 0);
		}
		$BeaconName = trim(substr($Buffer, 18));
		
		$ReceivedClientIP = $Data->ClientIP;
		// Empfangene IP in Array sichern
		$ClientIP = array();
		$ClientIP = unserialize($this->ReadAttributeString("ClientIP"));
		if (in_array($ReceivedClientIP, $ClientIP) == false) {
    			$ClientIP[] = $ReceivedClientIP;
			$this->WriteAttributeString("ClientIP", serialize($ClientIP));
			$this->SendDebug("ReceiveData", "ClientIP-Array: ".serialize($ClientIP), 0);
		}
		
		$ClientPort = $Data->ClientPort;
		$this->SendDebug("ReceiveData", "MAC: ".$MAC." Name: ".$BeaconName." ClintIP: ".$ReceivedClientIP." ClientPort: ".$ClientPort, 0);
		$this->SendDataToChildren(json_encode(Array("DataID" => "{10F33B43-3B21-2103-79DA-60F0267053F5}", "Function" => "DataUpdate", "MAC" => $MAC, "IP" => $ReceivedClientIP)));

		SetValueInteger($this->GetIDForIdent("LastUpdate"), time() );
	}   
	// Beginn der Funktionen
	public function ClientPing()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$ClientIP = unserialize($this->ReadAttributeString("ClientIP"));
			$NumberKonwnClients = count($ClientIP);
			
			$KnownIP = "";
			foreach ($ClientIP as $IP) {
				$KnownIP = $KnownIP."(".$IP.") ";
			}
			$this->SendDebug("ClientPing", $NumberKonwnClients." bekannte IP ".$KnownIP."werden angepingt", 0);
			$ClientIP = array();
			$ClientIP = unserialize($this->ReadAttributeString("ClientIP"));
			$ReachableClients = 0;
			foreach ($ClientIP as $IP) {
    				$Result = Sys_Ping($IP, 200);
    				If ($Result == false) {
					$this->SendDebug("ClientPing", "Nicht erreichbare IP: ".$IP, 0);
    				}
    				else {
					$ReachableClients = $ReachableClients + 1;
    				}
			}
			If (count($ClientIP) == 0) {
				SetValueInteger($this->GetIDForIdent("ClientState"), 0);
			}
			elseif ((count($ClientIP) > 0) AND ($ReachableClients == 0)) {
				SetValueInteger($this->GetIDForIdent("ClientState"), 1);
			}
			elseif ((count($ClientIP) > 0) AND ($ReachableClients < count($ClientIP))) {
				SetValueInteger($this->GetIDForIdent("ClientState"), 2);
			}
			elseif ((count($ClientIP) > 0) AND ($ReachableClients == count($ClientIP))) {
				SetValueInteger($this->GetIDForIdent("ClientState"), 3);
			}
		}
	}
	
	private function GetParentID()
	{
		$ParentID = (IPS_GetInstance($this->InstanceID)['ConnectionID']);  
	return $ParentID;
	}
	   
	private function RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize)
	{
	        if (!IPS_VariableProfileExists($Name))
	        {
	            IPS_CreateVariableProfile($Name, 1);
	        }
	        else
	        {
	            $profile = IPS_GetVariableProfile($Name);
	            if ($profile['ProfileType'] != 1)
	                throw new Exception("Variable profile type does not match for profile " . $Name);
	        }
	        IPS_SetVariableProfileIcon($Name, $Icon);
	        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
	        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);        
	}
}
?>
