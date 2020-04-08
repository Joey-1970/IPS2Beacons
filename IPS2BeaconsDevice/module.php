<?
    // Klassendefinition
    class IPS2BeaconsDevice extends IPSModule 
    {
	public function Destroy() 
	{
		//Never delete this line!
		parent::Destroy();
		$this->SetTimerInterval("Timer_1", 0);
	}  
	    
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		$this->ConnectParent("{1CB80BEC-EEB8-8CEB-F8A1-7DB11013F6A7}");
		$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyString("MAC", "");
		$this->RegisterPropertyInteger("Timer_1", 60);
		$this->RegisterTimer("Timer_1", 0, 'IPS2BeaconsDevice_StateReset($_IPS["TARGET"]);');
		
		// Status-Variablen anlegen
		$this->RegisterProfileInteger("IPS2Beacons.Presence", "Motion", "", "", 0, 3, 0);
		IPS_SetVariableProfileAssociation("IPS2Beacons.Presence", 0, "unbekannt", "Motion", 0xff0000);
		IPS_SetVariableProfileAssociation("IPS2Beacons.Presence", 1, "Abwesend", "Motion", -1);
		IPS_SetVariableProfileAssociation("IPS2Beacons.Presence", 2, "Anwesend", "Motion", 0x0000ff);
		
		$this->RegisterVariableInteger("State", "Status", "IPS2Beacons.Presence", 10);
		$this->RegisterVariableInteger("LastUpdate", "Letztes Update", "~UnixTimestamp", 20);
		$this->RegisterVariableInteger("LastChange", "Letztes Änderung", "~UnixTimestamp", 30);
        }
 	
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 202, "icon" => "error", "caption" => "Fehlerhafte MAC!");
				
		$arrayElements = array(); 
		$arrayElements[] = array("name" => "Open", "type" => "CheckBox",  "caption" => "Aktiv");
		$arrayElements[] = array("type" => "Label", "label" => "MAC-Adresse des Beacons");
		$arrayElements[] = array("type" => "ValidationTextBox", "name" => "MAC", "caption" => "MAC");
		$arrayElements[] = array("type" => "Label", "label" => "Zurücksetzen des Anwesenheitsstatus (Minimum 5 Sekunden)");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "Timer_1", "caption" => "Zeit (sek)");
		
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
			$MAC = $this->ReadPropertyString("MAC");
			if (filter_var($MAC, FILTER_VALIDATE_MAC)) {
				$this->SetStatus(102);
			}
			else {
				Echo "Syntax der Device ID inkorrekt!";
				$this->SendDebug("ApplyChanges", "Syntax der MAC inkorrekt!", 0);
				$this->SetStatus(202);
			}
		}
		else {
			$this->SetStatus(104);
			
		}	
	}
	
	public function ReceiveData($JSONString) 
	{
	 	// Empfangene Daten vom Gateway/Splitter
	    	$data = json_decode($JSONString);
	 	switch ($data->Function) {
			case "DataUpdate":
				If ($data->MAC == $this->ReadPropertyString("MAC")) {
					$this->StateSet();
				}
			    break;
		}
	}    
	
	// Beginn der Funktionen
	private function StateSet() {
		$this->SendDebug("StateSet", "Status wird auf anwesend gesetzt", 0);
		If (GetValueInteger($this->GetIDForIdent("State")) <> 2) {
			SetValueInteger($this->GetIDForIdent("State"), 2);
			SetValueInteger($this->GetIDForIdent("LastChange"), time());
		}
		SetValueInteger($this->GetIDForIdent("LastUpdate"), time());
		$Timer_1 = $this->ReadPropertyInteger("Timer_1");
		$Timer_1 = max($TimerPing, 5);
		$this->SetTimerInterval("Timer_1", 0);
		$this->SetTimerInterval("Timer_1", $Timer_1 * 1000);
	}    
	   
	public function StateReset() {
		$this->SendDebug("StateReset", "Status wird auf abwesend gesetzt", 0);
		If (GetValueInteger($this->GetIDForIdent("State")) <> 1) {
			SetValueInteger($this->GetIDForIdent("State"), 1);
			SetValueInteger($this->GetIDForIdent("LastChange"), time());
		}
		SetValueInteger($this->GetIDForIdent("LastUpdate"), time());
		$this->SetTimerInterval("Timer_1", 0);
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
