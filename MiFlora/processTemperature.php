<?

	$archiveID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
	
	$values = AC_GetAggregatedValues($archiveID, $_IPS["TARGET"], 1 /* Täglich */, strtotime("today 00:00"), time(), 0);        
	$tempMaxID = IPS_GetVariableIDByName("Temperatur MAX",IPS_GetParent($_IPS["TARGET"]));
	$tempMinID = IPS_GetVariableIDByName("Temperatur MIN",IPS_GetParent($_IPS["TARGET"]));
	$tempHintID = IPS_GetVariableIDByName("Temperatur Hinweis",IPS_GetParent($_IPS["TARGET"]));

	$data["dailyMin"] = $values[0]["Min"];
	$data["dailyMax"] = $values[0]["Max"];
	$data["MIN"] = GetValue($tempMinID);
	$data["MAX"] = GetValue($tempMaxID);
	

	if ($data["dailyMin"] < $data["MIN"])
		SetValue($tempHintID,1);
	else if ($data["dailyMax"] > $data["MAX"])
		SetValue($tempHintID,2);
	else
		SetValue($tempHintID,0);

?>
