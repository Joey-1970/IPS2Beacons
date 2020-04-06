<?
    // Klassendefinition
    class IPS2BeaconsSplitter extends IPSModule 
    {
	public function Destroy() 
	{
		//Never delete this line!
		parent::Destroy();
		$this->SetTimerInterval("Timer_Ping", 0);
	}  
	    
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		$this->RequireParent("{BAB408E0-0A0F-48C3-B14E-9FB2FA81F66A}"); // Multicast Socket
		$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyInteger("Timer_Ping", 60);
		$this->RegisterTimer("Timer_Ping", 0, 'IPS2BeaconsSplitter_Ping($_IPS["TARGET"]);');
		
		
		
		
		
		$this->RegisterVariableInteger("LastUpdate", "Letztes Update", "~UnixTimestamp", 10);
		$ClientIP = array();
		$this->RegisterAttributeString("ClientIP", serialize($ClientIP));
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
		
		$arrayActions = array();
		     	
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
 	}       
	   
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
		
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
			
			$this->SetStatus(102);
			
			
		}
		else {
			$this->SetStatus(104);
			
		}	
	}
	
	public function ForwardData($JSONString) 
	{
	 	// Empfangene Daten von der Device Instanz
	    	$data = json_decode($JSONString);
	    	$Result = false;
	 	switch ($data->Function) {
			
			
		}
	return $Result;
	}
	
	public function ReceiveData($JSONString) {	
 	    	// Empfangene Daten vom I/O
	    	$Data = json_decode($JSONString);
	    	$Buffer = utf8_decode($Data->Buffer); 
		$ReceivedClientIP = $Data->ClientIP;
		// Empfangen IP in Array sichern
		$ClientIP = array();
		$ClientIP = unserialize($this->ReadAttributeString("ClientIP"));
		if (in_array($ReceivedClientIP, $ClientIP) == false) {
    			$ClientIP[] = $ReceivedClientIP;
			$this->WriteAttributeString("ClientIP", serialize($ClientIP));
			$this->SendDebug("ReceiveData", "ClientIP-Array: ".serialize($ClientIP), 0);
		}
		
		$ClientPort = $Data->ClientPort;
		$this->SendDebug("ReceiveData", "Buffer: ".$Buffer." ClintIP: ".$ClientIP." ClientPort: ".$ClientPort, 0);
		SetValueInteger($this->GetIDForIdent("LastUpdate"), time() );
	}   
	// Beginn der Funktionen
	
	
	private function GetParentID()
	{
		$ParentID = (IPS_GetInstance($this->InstanceID)['ConnectionID']);  
	return $ParentID;
	}
	   
	 
}
?>
