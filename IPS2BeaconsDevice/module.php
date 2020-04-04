<?
    // Klassendefinition
    class IPS2BeaconsDevice extends IPSModule 
    {
	   
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		$this->RegisterPropertyBoolean("Open", false);
		$this->ConnectParent("{1CB80BEC-EEB8-8CEB-F8A1-7DB11013F6A7}");
		
		// Status-Variablen anlegen
		$this->RegisterProfileInteger("IPS2Beacons.Presence", "Motion", "", "", 0, 3, 0);
		IPS_SetVariableProfileAssociation("IPS2Beacons.Presence", 0, "unbekannt", "Motion", 0xff0000);
		IPS_SetVariableProfileAssociation("IPS2Beacons.Presence", 1, "Abwesend", "Motion", -1);
		IPS_SetVariableProfileAssociation("IPS2Beacons.Presence", 2, "Anwesend", "Motion", 0x0000ff);
		
		$this->RegisterVariableInteger("State", "Status", "IPS2Beacons.Presence", 10);
		$this->RegisterVariableInteger("LastUpdate", "Letztes Update", "~UnixTimestamp", 20);
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
		SetValueInteger($this->GetIDForIdent("State"), 0);
		
		If ($this->ReadPropertyBoolean("Open") == true) {
			
			$this->SetStatus(102);
			
			
		}
		else {
			$this->SetStatus(104);
			
		}	
	}
	
	
	// Beginn der Funktionen
	

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