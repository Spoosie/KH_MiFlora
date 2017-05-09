<?

	$archiveID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
	
	$values = AC_GetAggregatedValues($archiveID, $_IPS["TARGET"], 1 /* Täglich */, strtotime("today 00:00"), time(), 0);        
	$luxMaxID = IPS_GetVariableIDByName("Beleuchtungsstärke MAX",IPS_GetParent($_IPS["TARGET"]));
	$luxMinID = IPS_GetVariableIDByName("Beleuchtungsstärke MIN",IPS_GetParent($_IPS["TARGET"]));
	$luxHintID = IPS_GetVariableIDByName("Beleuchtungsstärke Hinweis",IPS_GetParent($_IPS["TARGET"]));

	$data["dailyAVG"] = $values[0]["Avg"];
	$data["MIN"] = GetValue($luxMinID);
	$data["MAX"] = GetValue($luxMaxID);
	

	if ($data["dailyAVG"] < $data["MIN"])
		SetValue($luxHintID,1);
	else if ($data["dailyAVG"] > $data["MAX"])
		SetValue($luxHintID,2);
	else
		SetValue($luxHintID,0);

?>