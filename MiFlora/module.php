<?
class MiFlora extends IPSModule
{
    var $moduleName = "MiFlora";
	var $mifloraHubs;
	
    public function Create()
    {
        //Never delete this line!
        parent::Create();
        
        //These lines are parsed on Symcon Startup or Instance creation
        //You cannot use variables here. Just static values.
        $this->RegisterPropertyString("mifloraHubs", "");
		$this->RegisterPropertyInteger("UpdateIntervall", 10);

        $this->RegisterPropertyBoolean("Debug", false);
		
		// --------------------------------------------------------
        // Timer installieren
        // --------------------------------------------------------
        $this->RegisterTimer("UpdateTimer", 0, 'MiFlora_Update($_IPS[\'TARGET\']);');

		
		// --------------------------------------------------------
        // Variablen Profile einrichten
        // --------------------------------------------------------
        if (!IPS_VariableProfileExists("MiFlora_LUX"))
        {
            IPS_CreateVariableProfile("MiFlora_LUX", 2);
            IPS_SetVariableProfileText("MiFlora_LUX", "", " Lx");
			IPS_SetVariableProfileValues("MiFlora_LUX",0, 10000, 100 );
        }
		
        if (!IPS_VariableProfileExists("MiFlora_EC"))
        {
            IPS_CreateVariableProfile("MiFlora_EC", 2);
            IPS_SetVariableProfileText("MiFlora_EC", "", " µs/cm");
			IPS_SetVariableProfileValues("MiFlora_EC",0, 1000, 100 );
        }
		
		

    }
    
    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();		
		// --------------------------------------------------------
        // Timer starten
        // --------------------------------------------------------
        $this->SetTimerInterval("UpdateTimer", $this->ReadPropertyInteger("UpdateIntervall")*1000); // *60);

	}
	
	public function Update()
    {
        IPS_LogMessage($this->moduleName,"Updating from miflora hubs");

		$mifloraConfig = $this->ReadPropertyString("mifloraHubs");
		$mifloraHubs = json_decode($mifloraConfig);

		if (!is_array($mifloraHubs))
		{
			IPS_LogMessage($this->moduleName,"No hubs defined!");
			return;
		}
		
		
		foreach($mifloraHubs as $mifloraHub)
		{
			IPS_LogMessage($this->moduleName,"Getting datas from [".$mifloraHub->name."]");
			
			$dataPath = "http://".$mifloraHub->hubIP."/plants.log";
			
			if ($this->ReadPropertyBoolean("Debug"))
				IPS_LogMessage($this->moduleName,"DataPath=".$dataPath);
			
			$flowerLog = @file_get_contents($dataPath);

			if ($flowerLog === false)
			{
				IPS_LogMessage($this->moduleName,"No datas found on [".$mifloraHub->name."]");
				continue;
			}
			
			if ($this->ReadPropertyBoolean("Debug"))
				IPS_LogMessage($this->moduleName,$flowerLog);
			
			$flowerLog = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $flowerLog);

			// Jede Zeile ein Sensor
			$sensorArray = explode("\n",$flowerLog);

			// Das Skript könnte noch laufen. Es muss anständig abgeschlossen sein!
			if ($sensorArray[sizeof($sensorArray)-2] != "DONE!")
			{
				IPS_LogMessage($this->moduleName,"MiFlora log (".$mifloraHub->name.") incomplete!");
				continue;
			}
			
			foreach($sensorArray as $sensor)
			{
				// (18:30:03 20-04-2017) Mac=C4:7C:8D:61:67:9C Name=Flower care Fw=2.9.2 Temp=20.50 Moist=4 Light=126 Cond=0 Bat=100
				preg_match('/\((.*)\) Mac=(.*) Name=(.*) Fw=(.*) Temp=(.*) Moist=(.*) Light=(.*) Cond=(.*) Bat=(.*)/',$sensor,$result);

//				unset($result[0]);
				
				if (sizeof($result) != 10)
				{
					IPS_LogMessage($this->moduleName,"Sensordata error!");
					continue;
				}
				
				if ($this->ReadPropertyBoolean("Debug"))
					IPS_LogMessage($this->moduleName,print_r($result,true));
				
				$uuid = str_replace(":","",$result[2]);
				
				$catID = $this->CreateCategory($result[3]." (Bitte umbenennen!)", $uuid , $this->InstanceID);
				$this->CreateVariable("Letzte Meldung", 3, $result[1], $uuid."_lastMessage", $catID );
				$this->CreateVariable("UUID", 3, $result[2], $uuid."_uuid", $catID );
				$this->CreateVariable("Firmware", 3, $result[4], $uuid."_firmware", $catID);
				$this->CreateVariable("Temperatur", 2, $result[5], $uuid."_temperature", $catID ,"~Temperature.Room");
				$this->CreateVariable("Bodenfeuchtigkeit", 2, $result[6], $uuid."_soilMoisture", $catID ,"~Humidity.F");
				$this->CreateVariable("Beleuchtungsstärke", 2, $result[7], $uuid."_lux", $catID ,"MiFlora_LUX" );
				$this->CreateVariable("Bodenleitfähigkeit", 2, $result[8], $uuid."_soilElectricalConductivity", $catID ,"MiFlora_EC");
				$this->CreateVariable("Zustand Batterie", 2, $result[9]/100, $uuid."_batteryLevel", $catID, "~Intensity.1" );

				
			}
			
		}
	}

    private function CreateCategory( $Name, $Ident = '', $ParentID = 0 )
    {
        global $RootCategoryID;

        if ($this->ReadPropertyBoolean("Debug"))
            IPS_LogMessage($this->moduleName,"CreateCategory: ($Name,$Ident,$ParentID)");

        if ( '' != $Ident )
        {
            $CatID = @IPS_GetObjectIDByIdent( $Ident, $ParentID );
            if ( false !== $CatID )
            {
               $Obj = IPS_GetObject( $CatID );
               if ( 0 == $Obj['ObjectType'] ) // is category?
                  return $CatID;
            }
        }

        $CatID = IPS_CreateCategory();
        IPS_SetName( $CatID, $Name );
        IPS_SetIdent( $CatID, $Ident );

        if ( 0 == $ParentID )
            if ( IPS_ObjectExists( $RootCategoryID ) )
                $ParentID = $RootCategoryID;
        IPS_SetParent( $CatID, $ParentID );

        return $CatID;
    }

    private function SetVariable( $VarID, $Type, $Value )
    {
        switch( $Type )
        {
           case 0: // boolean
              SetValueBoolean( $VarID, $Value );
              break;
           case 1: // integer
              SetValueInteger( $VarID, $Value );
              break;
           case 2: // float
              SetValueFloat( $VarID, $Value );
              break;
           case 3: // string
              SetValueString( $VarID, $Value );
              break;
        }
    }

    private function CreateVariable( $Name, $Type, $Value, $Ident = '', $ParentID = 0 ,$Profil = "")
    {
        if ($this->ReadPropertyBoolean("Debug"))
            IPS_LogMessage($this->moduleName,"CreateVariable: ($Name,$Type,$Value,$Ident,$ParentID,$Profil)");

        if ( '' != $Ident )
        {
            $VarID = @IPS_GetObjectIDByIdent( $Ident, $ParentID );
            if ( false !== $VarID )
            {
               $this->SetVariable( $VarID, $Type, $Value );
               if ($Profil != "")
                    IPS_SetVariableCustomProfile($VarID,$Profil);
               return;
            }
        }
        $VarID = @IPS_GetObjectIDByName( $Name, $ParentID );
        if ( false !== $VarID ) // exists?
        {
           $Obj = IPS_GetObject( $VarID );
           if ( 2 == $Obj['ObjectType'] ) // is variable?
            {
               $Var = IPS_GetVariable( $VarID );
               if ( $Type == $Var['VariableValue']['ValueType'] )
                {
                   $this->SetVariable( $VarID, $Type, $Value );
                   if ($Profil != "")
                        IPS_SetVariableCustomProfile($VarID,$Profil);

                   return;
                }
            }
        }

        $VarID = IPS_CreateVariable( $Type );

        IPS_SetParent( $VarID, $ParentID );
        IPS_SetName( $VarID, $Name );

        if ( '' != $Ident )
           IPS_SetIdent( $VarID, $Ident );
           
        $this->SetVariable( $VarID, $Type, $Value );

        if ($Profil != "")
            IPS_SetVariableCustomProfile($VarID,$Profil);

    }
	
}
?>
