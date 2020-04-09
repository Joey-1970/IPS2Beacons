<?
    // Klassendefinition
    class IPS2BeaconsConfigurator extends IPSModule 
    {
	    
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		$this->ConnectParent("{1CB80BEC-EEB8-8CEB-F8A1-7DB11013F6A7}");
		$this->RegisterPropertyInteger("Category", 0);  
		
        }
 	
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 202, "icon" => "error", "caption" => "Kommunikationfehler!");
				
		$arrayElements = array(); 
		$arrayElements[] = array("type" => "SelectCategory", "name" => "Category", "caption" => "Zielkategorie");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arraySort = array();
		$arraySort = array("column" => "MAC", "direction" => "ascending");
		
		$arrayColumns = array();
		$arrayColumns[] = array("caption" => "MAC", "name" => "MAC", "width" => "250px", "visible" => true);
		$arrayColumns[] = array("caption" => "Name", "name" => "Name", "width" => "250px", "visible" => true);
		$arrayColumns[] = array("caption" => "Letzter Kontakt", "name" => "LastUpdate", "width" => "auto", "visible" => true);
		
		$Category = $this->ReadPropertyInteger("Category");
		$RootNames = [];
		$RootId = $Category;
		while ($RootId != 0) {
		    	if ($RootId != 0) {
				$RootNames[] = IPS_GetName($RootId);
		    	}
		    	$RootId = IPS_GetParent($RootId);
			}
		$RootNames = array_reverse($RootNames);
		
		$BeaconArray = array();
		If ($this->HasActiveParent() == true) {
			$BeaconArray = unserialize($this->GetData());
		}
		$arrayValues = array();
		for ($i = 0; $i < Count($BeaconArray); $i++) {
			
			$arrayCreate = array();
			$LastUpdate = date("d.m.Y H:i:s", $BeaconArray[$i]["LastUpdate"]);    
			If (filter_var($BeaconArray[$i]["MAC"], FILTER_VALIDATE_MAC)) {
				$arrayCreate[] = array("moduleID" => "{45CC1ABC-2092-B24F-EE7F-80670FB8AEC5}", "location" => $RootNames,
					       "configuration" => array("MAC" => $BeaconArray[$i]["MAC"], "Open" => true));
				
				$arrayValues[] = array("MAC" => $BeaconArray[$i]["MAC"], "Name" => $BeaconArray[$i]["Name"], "LastUpdate" => $LastUpdate,
					       "instanceID" => $BeaconArray[$i]["Instance"], "create" => $arrayCreate);
			}
			else {
				$arrayValues[] = array("MAC" => $BeaconArray[$i]["MAC"], "Name" => $BeaconArray[$i]["Name"], "LastUpdate" => $LastUpdate,
					       "instanceID" => $BeaconArray[$i]["Instance"]);
			}
			
		}	
		$arrayElements[] = array("type" => "Configurator", "name" => "BeaconList", "caption" => "Beacons", "rowCount" => 10, "delete" => false, "sort" => $arraySort, "columns" => $arrayColumns, "values" => $arrayValues);
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements)); 		 
 	}       
	   
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
		
		If (IPS_GetKernelRunlevel() == 10103) {	
			If ($this->HasActiveParent() == true) {
				$this->SetStatus(102);
			}
			else {
				$this->SetStatus(104);
			}
		}
	}
	    
	// Beginn der Funktionen
	private function GetData()
	{
		$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{348C3E9C-31F8-2A75-8896-398B3536D1BA}", 
				"Function" => "getBeaconList" )));
		$this->SendDebug("GetData", $Result, 0);
		$BeaconArray = unserialize($Result);
		If (is_array($BeaconArray)) {
			$this->SetStatus(102);
			$this->SendDebug("GetData", $Result, 0);
			$Beacons = array();
			$i = 0;
			foreach($BeaconArray as $Key => $Beacon) {
				$Beacons[$i]["Name"] = $Beacon["Name"];
				$Beacons[$i]["LastUpdate"] = $Beacon["LastUpdate"];
				$Beacons[$i]["MAC"] = $Key;
				$Beacons[$i]["Instance"] = $this->GetBeaconInstanceID($Key);
				$i = $i + 1;
			}
			$this->SendDebug("GetData", serialize($Beacons), 0);
		}
	return serialize($Beacons);
	}
	
	function GetBeaconInstanceID(string $MAC)
	{
		$guid = "{45CC1ABC-2092-B24F-EE7F-80670FB8AEC5}";
		
	    	$Result = 0;
	    	// Modulinstanzen suchen
	    	$InstanceArray = array();
	    	$InstanceArray = @(IPS_GetInstanceListByModuleID($guid));
	    	If (is_array($InstanceArray)) {
			foreach($InstanceArray as $Module) {
				If (strtolower(IPS_GetProperty($Module, "MAC")) == strtolower($MAC)) {
					$this->SendDebug("GetBeaconInstanceID", "Gefundene Instanz: ".$Module, 0);
					$Result = $Module;
					break;
				}
				else {
					$Result = 0;
				}
			}
		}
	return $Result;
	}
}
?>
