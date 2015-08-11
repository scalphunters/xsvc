<?php

date_default_timezone_set('UTC');

//logging
function log_activity($conn,$userid,$request,$result){
	// Logging
	$client_ip=$_SERVER['REMOTE_ADDR'];
	$query="insert into request_log value (0,'$userid','$client_ip','$request','$result',CURRENT_TIMESTAMP)";
	$conn->query($query);
	return true;
}
//DB functions
function get_currencies($conn,$postfix=""){
	$query="select currency from fx_curvemap $postfix order by currency asc";
	$result=$conn->query($query);
	while($row=$result->fetch_assoc()){
		echo "<option value=".strtoupper($row[currency]).">".strtoupper($row[currency])."</option>";
	}
	
}
function get_curvenames($conn,$postfix=""){
	$query="select curve_name from discount_curves $postfix order by curve_name asc";
	$result=$conn->query($query);
	while($row=$result->fetch_assoc()){
		echo "<option value=".strtoupper($row[curve_name]).">".strtoupper($row[curve_name])."</option>";
	}

}
function get_curvetype(){
	echo "<option value=SIMPLE>SIMPLE</option>";
	echo "<option value=COMBINED>COMBINED</option>";
}
function get_rate_sources(){
	$sources=array('BBG','REUTERS','MANUAL','FORMULA');
	foreach($sources as $val)
		echo "<option value=$val>$val</option>";
}
function get_rateid($conn,$postfix=""){
	$query="select rate_id from marketdata $postfix order by rate_id asc";
	$result=$conn->query($query);
	while($row=$result->fetch_assoc()){
		echo "<option value=".strtoupper($row[rate_id]).">".strtoupper($row[rate_id])."</option>";
	}

}
function get_default_fx_curvename($conn,$currency){ //returns corresponding default fx curve for a currency
	$qry="select def_fxcurve from fx_curvemap where currency='$currency'";
	$result=$conn->query($qry);
	while($row=$result->fetch_assoc()){
		return $row['def_fxcurve'];
	}
	return false;
}
function get_rateid_jqgrid($conn,$postfix=""){
	$query="select rate_id from marketdata $postfix order by rate_id asc";
	$result=$conn->query($query);
	$numrow=mysqli_num_rows($result);
	$cnt=0;
	while($row=$result->fetch_assoc()){
		$cnt++;
		if($cnt<$numrow){
			echo strtoupper($row[rate_id]).":".strtoupper($row[rate_id]).";";
		}else{
			echo strtoupper($row[rate_id]).":".strtoupper($row[rate_id]);
		}
	}

}
function get_curvename_jqgrid($conn,$postfix=""){
	$query="select curve_name from discount_curves $postfix order by curve_name asc";
	$result=$conn->query($query);
	$numrow=mysqli_num_rows($result);
	$cnt=0;
	while($row=$result->fetch_assoc()){
		$cnt++;
		if($cnt<$numrow){
			echo strtoupper($row[curve_name]).":".strtoupper($row[curve_name]).";";
		}else{
			echo strtoupper($row[curve_name]).":".strtoupper($row[curve_name]);
		}
	}

}
function get_holidaynames($conn){
	$query="select hol_name from holiday order by hol_name asc";
	$result=$conn->query($query);
	while($row=$result->fetch_assoc()){
		echo "<option value=".strtoupper($row[hol_name]).">".strtoupper($row[hol_name])."</option>";
	}
}

function get_bd_conventions(){
	echo "<option value=MODIFIEDFOLLOWING>MODIFIEDFOLLOWING</option>";
	echo "<option value=FOLLOWING>FOLLOWING</option>";
	echo "<option value=PRECEDING>PRECEDING</option>";
	echo "<option value=INDIFFERENT>INDIFFERENT</option>";
}
function get_frequencies(){
	//echo "<option value=' '> </option>";
    for($i=1;$i<=12;$i++)
		echo "<option value=".$i."M>".$i."M</option>";
}
function get_instruments(){
	$inst_arr=array("FX","SWAPPOINT","DEPO","IRS","CCS","BASIS");
	for($i=0;$i<count($inst_arr);$i++)
		echo "<option value=$inst_arr[$i]>$inst_arr[$i]</option>";
}
function get_fixfloat(){
	$inst_arr=array("FIXED","FLOATING");
	for($i=0;$i<count($inst_arr);$i++)
		echo "<option value=$inst_arr[$i]>$inst_arr[$i]</option>";
}
function get_yesno(){
	$inst_arr=array("YES","NO");
	for($i=0;$i<count($inst_arr);$i++)
		echo "<option value=$inst_arr[$i]>$inst_arr[$i]</option>";
}
function get_payrec(){
	$inst_arr=array("PAYER","RECEIVER");
	for($i=0;$i<count($inst_arr);$i++)
		echo "<option value=$inst_arr[$i]>$inst_arr[$i]</option>";
}
function get_dc_conventions(){
	echo "<option value=ACT/365>ACT/365</option>";
	echo "<option value=ACT/ACT_ISDA>ACT/ACT ISDA</option>";
	echo "<option value=ACT/ACT_AFB>ACT/ACT AFB</option>";
	echo "<option value=ACT/365_FIXED>ACT/365F</option>";
	echo "<option value=ACT/364>ACT/364</option>";
	echo "<option value=ACT/365L>ACT/365L</option>";
	echo "<option value=ACT/360>ACT/360</option>";
	echo "<option value=30/360>30/360</option>";
	echo "<option value=30A/360>30/360 ISDA</option>";
	echo "<option value=30E/360>30/360 European</option>";
	echo "<option value=30E/360_ISDA>30/360 German</option>";
	echo "<option value=30U/360>30/360 US Bond</option>";
	
}
//market parameter session
function get_spot_rate($conn, $baseccy,$quoteccy){
	$query="select spotrate from fx_curvemap where currency='$baseccy'";
	$result=$conn->query($query);	
	while($row=$result->fetch_assoc()){
		$br=$row['spotrate'];
	}
	$query="select spotrate from fx_curvemap where currency='$quoteccy'";
	$result=$conn->query($query);
	while($row=$result->fetch_assoc()){
		$qr=$row['spotrate'];
	}
	return $qr/$br;
}
function get_discount_curve($conn,$curvename,&$dfs_output){
	get_discount_curve_json($conn,$curvename,$dfs_output);
}
function get_discount_curve_depreciated($conn, $curvename,&$dfs_output){
	$query="select days_to_maturity as dtm, df from $curvename";
	$result=$conn->query($query);
	$inum=0;
	while($row=$result->fetch_assoc()){
		$dfs_output[$inum]['days']=intval($row['dtm']);
		$dfs_output[$inum]['df']=floatval($row['df']);
		$inum++;
	}
}
function get_discount_curve_json($conn,$curvename,&$dfs_output){
	$query="select discount_curve from discount_curves where curve_name='$curvename'";
	$result=$conn->query($query);
	while($row=$result->fetch_assoc()){
		$dfs_output=json_decode($row['discount_curve'],true)['data'];
	}
}

//static data session
function get_holidays_from_db($conn,$hol_name){
	$ret=array();
	$query="select hol_array_json from holiday where hol_name='$hol_name'";
	$result=$conn->query($query);
	while($row=$result->fetch_assoc()){
		$ret=json_decode($row['hol_array_json'],true);
		$ret=$ret['holidays'];
	}
	return $ret;
}
function get_timezone_from_db($conn,$ccy){
	$ret=0;
	$query="select timezone from fx_curvemap where currency='$ccy'";
	$result=$conn->query($query);
	while($row=$result->fetch_assoc()){
		$ret=$row['timezone'];
	}
	return $ret;
}
function get_denominator_from_db($conn,$ccy){
	$ret=0;
	$query="select denominator from fx_curvemap where currency='$ccy'";
	$result=$conn->query($query);
	while($row=$result->fetch_assoc()){
		$ret=$row['denominator'];
	}
	return $ret;
}
function get_precision_from_db($conn,$ccy){
	$ret=0;
	$query="select quote_precision from fx_curvemap where currency='$ccy'";
	$result=$conn->query($query);
	while($row=$result->fetch_assoc()){
		$ret=$row['quote_precision'];
	}
	return $ret;
}
function get_fxcurvename_from_db($conn,$ccy){
	$ret='';
	$query="select def_fxcurve as _res from fx_curvemap where currency='$ccy'";
	$result=$conn->query($query);
	while($row=$result->fetch_assoc()){
		$ret=$row['_res'];
	}
	return $ret;
}

//market data session
function load_rate_from_db($conn,$rate_id){ // $res=array('rate_id'=> , 'rate' => , 'parameters' => json obj)
	$qry="select rate_id,rate,parameters,ticker_info from marketdata where rate_id='$rate_id'";
	$result=$conn->query($qry) or die ("sql error in load_rate_from_db function, _core_pricing_db.php");
	if(mysqli_num_rows($result)==0){
		return null;
	}else{
		$row=$result->fetch_assoc();
		$res=array('rate_id'=>$row['rate_id'], 'rate'=>$row['rate'],'parameters'=>$row['parameters'],'ticker_info'=>$row['ticker_info']);
		//print_r($res);
		return $res;
	}
}
function generate_parameter_from_rate_id($conn,$anchor_date_str,$rate_id){
	$res=load_rate_from_db($conn,strtolower($rate_id));
	$rate=floatval($res['rate']).PHP_EOL;
	$res=$res['parameters'];
	$tmp=json_decode(strtolower($res),true);
	$tmp['ref_rate']=$rate/floatval($tmp['multiplier']);
	$res=json_encode($tmp);
	return generate_parameter_from_json($conn,$anchor_date_str,$res);
}
function generate_parameter_from_json($conn,$anchor_date_str,&$params_json){ //mysql $conn for swap point
	$params_arr=json_decode(strtolower($params_json),true);
	$res=array();

	//parse tenors and convert to date format (iso)
	$hols_arr=get_holidays_from_db($conn,$params_arr['payment_holiday']);//bc
	$hols_arr_fixing=get_holidays_from_db($conn,$params_arr['fixing_holiday']);

	
	switch($params_arr['start']){
		case 'tod':
			$stdate_str=roll_n_opendates($anchor_date_str,0,$hols_arr);
			break;
		case 'tom':
			$stdate_str=roll_n_opendates($anchor_date_str,1,$hols_arr);
			if($params_arr['currency_pair']!="")
				if(substr($params_arr['currency_pair'],3,3)==$params_arr['currency'])
					$stdate_str=fxTomDate($anchor_date_str,$hols_arr_fixing,$hols_arr);
				else
					$stdate_str=fxTomDate($anchor_date_str,$hols_arr,$hols_arr_fixing);
				break;
		case 'spot':
			$stdate_str=roll_n_opendates($anchor_date_str,2,$hols_arr);
			if($params_arr['currency_pair']!="")
				if(substr($params_arr['currency_pair'],3,3)==$params_arr['currency'])
					$stdate_str=fxSpotDate($anchor_date_str,$params_arr['currency_pair'],$hols_arr_fixing,$hols_arr);
				else
					$stdate_str=fxSpotDate($anchor_date_str,$params_arr['currency_pair'],$hols_arr,$hols_arr_fixing);
					
				break;
		default:
			$stdate_str=add_tenor($anchor_date_str,$params_arr['start']);
			if($params_arr['currency_pair']!="")
				$stdate_str=fx_tenor_to_date($anchor_date_str,$params_arr['currency_pair'],$params_arr['start'],$hols_arr_fixing,$hols_arr);
			else
				$stdate_str=applyConvention($stdate_str,$params_arr['fixed_bd_convention'],$hols_arr);
			break;
	}
	//$stdate_str=roll_n_opendates($anchor_date_str,2,$params_arr['payment_holiday_array'],$params_arr['holiday_weekday']);//add_tenor($anchor_date_str,$params_arr['start']);
	switch($params_arr['tenor']){
		case 'tod':
			$madate_str=roll_n_opendates($stdate_str,0,$hols_arr);
			break;
		case 'tom':
			$matdate_str=roll_n_opendates($stdate_str,1,$hols_arr);
			if($params_arr['currency_pair']!="")
				if(substr($params_arr['currency_pair'],3,3)==$params_arr['currency'])
					$matdate_str=fxTomDate($anchor_date_str,$hols_arr_fixing,$hols_arr);
				else
					$matdate_str=fxTomDate($anchor_date_str,$hols_arr,$hols_arr_fixing);				
				break;
		case 'spot':
			$matdate_str=roll_n_opendates($stdate_str,2,$hols_arr);
			if($params_arr['currency_pair']!="")
				if(substr($params_arr['currency_pair'],3,3)==$params_arr['currency'])
					$matdate_str=fxSpotDate($anchor_date_str,$params_arr['currency_pair'],$hols_arr_fixing,$hols_arr);
				else
					$matdate_str=fxSpotDate($anchor_date_str,$params_arr['currency_pair'],$hols_arr,$hols_arr_fixing);
					
				break;
		default:
			$matdate_str=add_tenor($stdate_str,$params_arr['tenor']);
			if($params_arr['currency_pair']!="")
				$matdate_str=fx_tenor_to_date($anchor_date_str,$params_arr['currency_pair'],$params_arr['tenor'],$hols_arr_fixing,$hols_arr);
			else 
				$matdate_str=applyConvention($matdate_str,$params_arr['fixed_bd_convention'],$hols_arr);
			break;
	}

	switch($params_arr['instrument']){
		case 'irs':
		case 'ccs':
		case 'basis':
		case 'swap':
		case 'bond':
			$hols_arr=get_holidays_from_db($conn,$params_arr['payment_holiday']);
			$hols_arr_fixing=get_holidays_from_db($conn,$params_arr['fixing_holiday']);
			$res=array('instrument'=>$params_arr['instrument'] ,'currency'=>$params_arr['currency']
					,'today'=>$anchor_date_str,'start'=>$stdate_str,'maturity'=>$matdate_str
					,'fixed_payment_frequency'=>$params_arr['fixed_payment_frequency']
					,'fixed_bd_convention'=>$params_arr['fixed_bd_convention']
					,'fixed_dc_convention'=>$params_arr['fixed_dc_convention']
					,'float_payment_frequency'=>$params_arr['float_payment_frequency']
					,'float_reset_frequency'=>$params_arr['float_reset_frequency']
					,'float_bd_convention'=>$params_arr['float_bd_convention']
					,'float_dc_convention'=>$params_arr['float_dc_convention']
					,'forward'=>'no','short_coupon'=>'yes','payment_holiday_array'=>$hols_arr
					,'fixing_holiday_array'=>$hols_arr_fixing, 'holiday_weekday'=>array(2,3)
					,'ref_rate'=>$params_arr['ref_rate']
			);
			break;
		case 'depo':
			$hols_arr=get_holidays_from_db($conn,$params_arr['payment_holiday']);
			$res=array('instrument'=>$params_arr['instrument'],'currency'=>$params_arr['currency'] 
					,'today'=>$anchor_date_str,'start'=>$stdate_str,'maturity'=>$matdate_str
					,'fixed_bd_convention'=>$params_arr['fixed_bd_convention']
					,'fixed_dc_convention'=>$params_arr['fixed_dc_convention']
					,'payment_holiday_array'=>$hols_arr,'holiday_weekday'=>array(2,3)
					,'ref_rate'=>$params_arr['ref_rate']
			);
			break;
		case 'swappoint':
			$hols_arr=get_holidays_from_db($conn,$params_arr['payment_holiday']); //bc
			$hols_arr_fixing=get_holidays_from_db($conn,$params_arr['fixing_holiday']);
			$spotrate=get_spot_rate($conn,substr($params_arr['currency_pair'],0,3),substr($params_arr['currency_pair'],3,3));
			$base_dfs=array();
			get_discount_curve($conn,$params_arr['base_curve_name'],$base_dfs);
			$res=array('instrument'=>$params_arr['instrument'],'currency'=>$params_arr['currency']
					,'today'=>$anchor_date_str ,'start'=>$stdate_str,'maturity'=>$matdate_str
					,'fixed_bd_convention'=>$params_arr['fixed_bd_convention']
					,'fixed_dc_convention'=>$params_arr['fixed_dc_convention']
					,'payment_holiday_array'=>$hols_arr
					,'fixing_holiday_array'=>$hols_arr_fixing, 'holiday_weekday'=>array(2,3)
					,'fx_rate' => $spotrate
					,'currency_pair'=>$params_arr['currency_pair']
					,'base_curve' => $base_dfs
					,'ref_rate'=>$params_arr['ref_rate']
			);
			break;
		default:
			break;
	}
	return $res;
}
function get_curveset_from_db($conn,$curve_name){
	$qry="select curve_set from discount_curves where curve_name='$curve_name'";
	$result=$conn->query($qry) or die('huk');
	while($row=$result->fetch_assoc()){
		$ret=$row['curve_set'];
	}
	return json_decode($ret,true);
}
	//example : formula ='krw_swappoint_1m +krw_swappoint_2m -krw_swappoint_6m'
function get_formula_rate_from_db($conn,$formula){//simple rate arithmetic
	$formula=strtolower($formula);
	$pattern='/([-+]?(\d+\.?\d*)?[a-z0-9_]+)/';
	$ret=0.0;
	if(preg_match_all($pattern,$formula,$matches,PREG_PATTERN_ORDER)){
		//print_r($matches);
		$tokens=$matches[0];
		foreach($tokens as $val){
			$pattern='/([-+]?(\d+\.?\d*)?)([a-z0-9_]+)/';
			if(preg_match_all($pattern,$val,$matches_sub,PREG_PATTERN_ORDER)){
				//print_r($matches_sub);
				$coef=floatval($matches_sub[1][0]);
				if($matches_sub[2][0]==''){
					$coef=1.0;
					if($matches_sub[1][0]=='-') $coef=-1.0;
				}
				$rate_name=$matches_sub[3][0];
				$rate_obj=load_rate_from_db($conn,$rate_name);
				$rt=floatval($rate_obj['rate']);
				$mult=floatval(json_decode($rate_obj['parameters'],true)['multiplier']);
				$ret=$ret+$coef*$rt/$mult;
			}
		}
		return $ret;
	}
}

//bootstrap
function bootstrap_curve($conn,&$dfs_out,$anchor_date_str,&$curveset,$method='nr'){
	$dfs_out=array();
	for($i=0;$i<count($curveset['data']);$i++){
		$params=generate_parameter_from_rate_id($conn,$anchor_date_str,$curveset['data'][$i]);
		bootstrap($anchor_date_str,$dfs_out,$params['ref_rate'],$params);
	}
}
function bootstrap_curve_from_curvename($conn,&$dfs_out,$anchor_date_str,$curve_name,$method='nr'){
	$curveset=get_curveset_from_db($conn,$curve_name);
	if(strtolower($curveset['type'])=='combined'){
		combine_curves_from_db($dfs_out,$conn,$curveset['data']);
	}else{
		bootstrap_curve($conn,$dfs_out,$anchor_date_str,$curveset,$method);
	}
}

//curve manipulation
	//$curve_list_json = '[{"coefficient" : 1.0 , "curve_name" : "usd_irs_3m_sbb"}, {"coefficient" : 1.0 ,"curve_name" :"usd_basis_3m_vs_6m"} ]';
function combine_curves_from_db(&$dfs_out,$conn,$curve_list_arr,$method="linRate"){
	$days_arr=array();
	$dfs_in_arr=array();
	$coeff_arr=array();
	for($i=0;$i<count($curve_list_arr);$i++){//make unique dates array
		get_discount_curve($conn,$curve_list_arr[$i]['curve_name'],$dfs_in_arr[$i]);
		array_push($coeff_arr,$curve_list_arr[$i]['coefficient']);
	}
	combine_curves($dfs_out,$dfs_in_arr,$coeff_arr,$method);
}
function update_discount_curve_to_db($conn,$curve_name,&$dfs){

	$qry="select curve_set from discount_curves where curve_name='$curve_name'";
	$result=$conn->query($qry) or die('huk');
	$ccy='usd';
	while($row=$result->fetch_assoc()){
		$data=json_decode($row['curve_set'],true);
		$ccy=$data['currency'];
	}
	$dfs_data=array('currency'=>$ccy, 'data'=>$dfs);
	$dfs_json=json_encode($dfs_data);
	$qry="update discount_curves set discount_curve='$dfs_json' where curve_name='$curve_name'";
	$conn->query($qry) or die('huk');
}
function update_formula_rate_to_db($conn,$rate_id){
	$rate=0;
	$rate_id=strtolower($rate_id);
	$qry="select ticker_info,parameters from marketdata where rate_id='$rate_id'";
	$result=$conn->query($qry) or die('hik');
	while($row=$result->fetch_assoc()){
		$info=json_decode($row['ticker_info'],true);
		$tpmprm=json_decode($row['parameters'],true);
		$src=$info['source'];
		$formula=$info['ticker'];
		$multiplier=floatval($tpmprm['multiplier']);
		if($src=='formula'){
			//update
			$rate=get_formula_rate_from_db($conn,$formula)*$multiplier;
			$qry="update marketdata set rate=$rate where rate_id='$rate_id'";
			$conn->query($qry) or die('huk');
		}else{
			//do nothing
		}
	}
	return $rate;
}

//scheduled tasks (bootstrap schedule, rate update schedule, dependency checks)
function get_dependent_rate_names($formula){//simple rate name extraction
	$formula=strtolower($formula);
	$pattern='/([-+]?(\d+\.?\d*)?[a-z0-9_]+)/';
	$rate_names=array();
	if(preg_match_all($pattern,$formula,$matches,PREG_PATTERN_ORDER)){
		//print_r($matches);
		$tokens=$matches[0];
		foreach($tokens as $val){
			$pattern='/([-+]?(\d+\.?\d*)?)([a-z0-9_]+)/';
			if(preg_match_all($pattern,$val,$matches_sub,PREG_PATTERN_ORDER)){
				array_push($rate_names,$matches_sub[3][0]);
			}
		}
		return $rate_names;
	}
}
	// $params_rate = array (
	// 		'type'=>'rate',
	// 		'elements'=>array('usd_irs_9y_s','krw_swappoint_3m')
	// );
	// $params_curve = array (
	// 		'type'=>'curve',
	// 		'elements'=>array('krw_fx_3m','usd_basis_3m_vs_6m','aud_fx_3m')
	// );
function analyse_dependency($conn,$params){//return value is sorted (job ordered) dependent element
	$ret_arr=array();
	$dependencies=array();
	switch($params['type']){
		case 'curve':
			foreach($params['elements'] as $elm){
				$dependencies=array();
				$curveset_arr=get_curveset_from_db($conn,$elm);
				if(strtolower($curveset_arr['type'])=='combined'){
					foreach($curveset_arr['data'] as $subelm){
						array_push($dependencies,$subelm['curve_name']);
					}
					$recursive_param=array('type'=>'curve','elements'=>$dependencies);
					$ret_arr=array_merge($ret_arr,analyse_dependency($conn,$recursive_param));
					array_push($ret_arr,$elm);
				}else if($curveset_arr['type']=='simple'){
					//load sub rates id
					foreach($curveset_arr['data'] as $subelm ){
						$base_curve_name=json_decode(load_rate_from_db($conn,$subelm)['parameters'],true)['base_curve_name'];
						if($base_curve_name!=''){
							array_push($dependencies,$base_curve_name);
							$dependencies=array_unique($dependencies);
						}
					}
					if(count($dependencies)>0){
						$recursive_param=array('type'=>'curve','elements'=>$dependencies);
						$ret_arr=array_merge($ret_arr,analyse_dependency($conn,$recursive_param));
					}
					array_push($ret_arr,$elm);
				}
				else{
					array_push($ret_arr,$elm);
				}
			}
			break;
		case 'rate':
			foreach($params['elements'] as $elm){
				$dependencies=array();
				$ticker_info=load_rate_from_db($conn,$elm)['ticker_info'];
				$ticker_info_arr=json_decode($ticker_info,true);
				if(strtolower($ticker_info_arr['source'])=='formula'){
					$dependencies=get_dependent_rate_names($ticker_info_arr['ticker']);
					$recursive_param=array('type'=>'rate','elements'=>$dependencies);
					$ret_arr=array_merge($ret_arr,analyse_dependency($conn,$recursive_param));
					array_push($ret_arr,$elm);
				}else{
					array_push($ret_arr,$elm);
				}
			}
			break;
		default :
			break;
	}

	return array_values(array_unique($ret_arr));
}
function bootstrap_all_curves_in_db($conn,$anchor_date_str){
	global $_gbl_debug;
	$qry="select curve_name as name from discount_curves";
	$result=$conn->query($qry) or die('huk');
	$dependency_param=array('type'=>'curve','elements'=>array());
	while($row=$result->fetch_assoc()){
		array_push($dependency_param['elements'],$row['name']);
	}
	$ordered_list=analyse_dependency($conn,$dependency_param);
	//print_r($ordered_list);
	foreach($ordered_list as $elm){
		$dfs_out=array();
		if($_gbl_debug) echo 'Bootstrapping '.strtoupper($elm)."...\n";
		bootstrap_curve_from_curvename($conn,$dfs_out,$anchor_date_str,$elm);
		update_discount_curve_to_db($conn,$elm,$dfs_out);
	}
}
function update_all_formula_rates_in_db($conn){
	global $_gbl_debug;
	$qry="select rate_id as name,ticker_info from marketdata";
	$result=$conn->query($qry) or die('huk');
	$dependency_param=array('type'=>'rate','elements'=>array());
	while($row=$result->fetch_assoc()){
		if(strtolower(json_decode($row['ticker_info'],true)['source'])=='formula'){
			array_push($dependency_param['elements'],$row['name']);
		}
	}
	$ordered_list=analyse_dependency($conn,$dependency_param);
	//print_r($ordered_list);
	foreach($ordered_list as $elm){
		if($_gbl_debug)	echo 'updating '.strtoupper($elm)."...\n";
		update_formula_rate_to_db($conn,$elm);
	}
	//update fx_curvemap
	update_rates_in_fx_curvemap($conn);
}
function update_rates_in_fx_curvemap($conn){
	$qry="select currency,rate_id from fx_curvemap";
	$result=$conn->query($qry) or die('huk');
	while($row=$result->fetch_assoc()){
		$qry2="select rate,parameters from marketdata where rate_id='$row[rate_id]'";
		$result2=$conn->query($qry2) or die('huk');
		while($row2=$result2->fetch_assoc()){
			$params_arr=json_decode($row2['parameters'],true);
			$rate=$row2['rate'];
			if(substr($params_arr['currency_pair'],3,3)=='usd'){
				$rate=1/$rate;
			}else{
				$rate=$rate;
			}
			$qry3="update fx_curvemap set spotrate=$rate where currency='$row[currency]'";
			$conn->query($qry3) or die('huk');
		}
	}
}

function propagating_curves($conn,$curve_name){// returns ordered array of curvenames to bootstrap
	$qry="select curve_name from discount_curves";
	$result=$conn->query($qry) or die('huk');
	$curve_name=strtolower($curve_name);
	$ret_arr=array();
	while($row=$result->fetch_assoc()){
		if($curve_name!=$row['curve_name']){
			$param=array('type'=>'curve','elements'=>array($row['curve_name']));
			if(in_array($curve_name,analyse_dependency($conn,$param))){
				//if($_gbl_debug) echo $row['curve_name'].' has '.$curve_name.PHP_EOL;
				array_push($ret_arr,$row['curve_name']);
			}
		}
	}
	return re_order_propagation_list($conn,$ret_arr);
}
function re_order_propagation_list($conn,$curve_list){
	$params=array('type'=>'curve','elements'=>$curve_list);
	$ret_arr=analyse_dependency($conn,$params);
	$re_ordered=array();
	$inum=0;
	for($i=0;$i<count($ret_arr);$i++){
		for($j=0;$j<count($curve_list);$j++){
			if($ret_arr[$i]==$curve_list[$j]){
				$re_ordered[$inum++]=$ret_arr[$i];
				break;
			}
		}
	}
	return $re_ordered;
}
function bootstrap_propagating($conn,$curve_name,$anchor_date_str,$update_db=true){//bootstrap curve name and its derivatives
	global $_gbl_debug;
	$ordered_list=propagating_curves($conn,$curve_name);
	if($_gbl_debug) echo 'Bootstrapping '.strtoupper($curve_name)."...\n";
	bootstrap_curve_from_curvename($conn,$dfs_out,$anchor_date_str,$curve_name);
    if($update_db) update_discount_curve_to_db($conn,$curve_name,$dfs_out);
    foreach($ordered_list as $elm){
		$dfs_out=array();
		if($_gbl_debug) echo 'Bootstrapping '.strtoupper($elm)."...\n";
		bootstrap_curve_from_curvename($conn,$dfs_out,$anchor_date_str,$elm);
		if($update_db) update_discount_curve_to_db($conn,$elm,$dfs_out);
	}
}

//pricing session
function get_npv_by_curvename($conn,&$cfs,$curve_name,$anchor_date_str){
	$dfs_out=array();
	get_discount_curve($conn,$curve_name,$dfs_out);
	return get_npv($cfs,$dfs_out,$anchor_date_str);
}

?>
