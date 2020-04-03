<?
    // Klassendefinition
    class IPS2BeaconsSplitter extends IPSModule 
    {
	   
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		$this->RegisterPropertyBoolean("Open", false);
		
		$this->RequireParent("{BAB408E0-0A0F-48C3-B14E-9FB2FA81F66A}"); // Multicast Socket
		
		
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
			/*
			If (IPS_GetProperty($ParentID, 'Host') <> $this->ReadPropertyString('IPAddress')) {
				IPS_SetProperty($ParentID, 'Host', $this->ReadPropertyString('IPAddress'));
			}
			*/
			If (IPS_GetProperty($ParentID, 'Port') <> 8173) {
				IPS_SetProperty($ParentID, 'BindPort', 8173);
			}
			If (IPS_GetProperty($ParentID, 'Port') <> 8173) {
				IPS_SetProperty($ParentID, 'BindPort', 8173);
			}
			If (IPS_GetProperty($ParentID, 'Open') <> $this->ReadPropertyBoolean("Open")) {
				IPS_SetProperty($ParentID, 'Open', $this->ReadPropertyBoolean("Open"));
			}
			If (IPS_GetName($ParentID) == "Multicast Socket") {
				IPS_SetName($ParentID, "IPS2Beacons");
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
	    
	// Beginn der Funktionen
	
	
	private function GetParentID()
	{
		$ParentID = (IPS_GetInstance($this->InstanceID)['ConnectionID']);  
	return $ParentID;
	}
	   
	 
}
?>
