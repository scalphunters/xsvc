<?php 
include("_core_utility.php");
//error_reporting(E_STRICT);
date_default_timezone_set('UTC');
// Date & holiday manipulation
function date_to_serial($date_str){
	return intval(strtotime($date_str)/86400);
}
function serial_to_date($date_serial){
	return date('Y-m-d',$date_serial*86400);
}
function to_iso_date_format($date_str){
	$date_serial=date_to_serial($date_str);
	return date('Y-m-d',$date_serial*86400);
}
function days_between_dates($d1_str,$d2_str){ // d2 - d1
	return date_to_serial($d2_str)-date_to_serial($d1_str);
}
function make_date_str($year,$month,$day){
	return date("Y-m-d",mktime(0,0,0,$month,$day,$year));
}
function isSpecialHoliday($datestr,$holiday_arr=array()){
	$d=date_to_serial($datestr);
	$ret=false;
	for($i=0;$i<count($holiday_arr);$i++){
		$tmp=date_to_serial($holiday_arr[$i]);
		if($d==$tmp){
			$ret=true;
		}
	}
	return $ret;
}
function isHoliday($datestr,$hol_arr=array(),$hol_weekdays=array(2,3)){ //2 sat, 3 sun
	$ret=false;
	if(isSpecialHoliday($datestr,$hol_arr)){
		return true;
	}
	for($i=0;$i<count($hol_weekdays);$i++){
		if(weekDay($datestr) == $hol_weekdays[$i]){
			$ret=true;
		}
	}
	return $ret;
	
}
function modifiedFollowing($datestr,$hol_arr=array(),$hol_weekdays=array(2,3)){
	$d_serial=date_to_serial($datestr);
	$inc=1;
	while(isHoliday(date('Y-m-d',$d_serial*86400),$hol_arr,$hol_weekdays)==true){
		if(isEOM(date('Y-m-d',$d_serial*86400))==true){
			$inc=-1;
		}
		$d_serial=$d_serial+$inc;
	}
	return(date('Y-m-d',$d_serial*86400));
}
function Following($datestr,$hol_arr=array(),$hol_weekdays=array(2,3)){
	$d_serial=date_to_serial($datestr);
	$inc=1;
	while(isHoliday(date('Y-m-d',$d_serial*86400),$hol_arr,$hol_weekdays)==true){
		$d_serial=$d_serial+$inc;
	}
	return(date('Y-m-d',$d_serial*86400));
}
function Preceding($datestr,$hol_arr=array(),$hol_weekdays=array(2,3)){
	$d_serial=date_to_serial($datestr);
	$inc=-1;
	while(isHoliday(date('Y-m-d',$d_serial*86400),$hol_arr,$hol_weekdays)==true){
		$d_serial=$d_serial+$inc;
	}
	return(date('Y-m-d',$d_serial*86400));
}
function applyConvention($datestr,$convention="ModifiedFollowing",$hol_arr=array(),$hol_weekdays=array(2,3)){ // $convention = [ModifiedFOllowing,Following,Preceding,Indifferent]
	$convention=strtolower($convention);
	switch($convention){
		case 'modifiedfollowing':
			return modifiedFollowing($datestr,$hol_arr,$hol_weekdays);
			break;
		case 'following':
			return Following($datestr,$hol_arr,$hol_weekdays);
			break;
		case 'preceding':
			return Preceding($datestr,$hol_arr,$hol_weekdays);
			break;
		case 'indifferent':
			return $datestr;
			break;
		default :
			break;
	}
}
function isEOM($datestr){
	return (date('Y-m-t',strtotime($datestr))==date('Y-m-d',strtotime($datestr)))? true:false;
}
function weekDay($datestr){
	return date_to_serial($datestr) % 7; // 1 : friday , 4 : monday , 2 sat , 3 sun
}
function roll_n_opendates($date,$n,$hol_arr=array(),$hol_wd=array(2,3)){
	$d_serial=date_to_serial($date);
	$inc=0;
	if($n>0){
		while($inc<$n){
			$d_serial++;;
			while(isHoliday(date('Y-m-d',$d_serial*86400),$hol_arr,$hol_wd)==true)	{
				$d_serial++;
			}
			$inc++;
		}
	}else{
		while($inc>$n){
			$d_serial--;
			while(isHoliday(date('Y-m-d',$d_serial*86400),$hol_arr,$hol_wd)==true)	{
				$d_serial--;
			}
			$inc--;
		}
	}
	return date('Y-m-d',$d_serial*86400);
} //return is 'YYYY-MM-DD'
function get_ymd($datestr){
	$ret['y']=intval(date("Y",strtotime($datestr)));
	$ret['m']=intval(date("m",strtotime($datestr)));
	$ret['d']=intval(date("d",strtotime($datestr)));
	return $ret;
}
function add_ymd($datestr,$nd,$nm=0,$ny=0,$apply_eom=true){ //if eom true $nd is i
	$ymd=get_ymd($datestr);
	if(isEOM($datestr)&&$apply_eom && $nd==0 ){
		$ymd['d']=1;
		return date("Y-m-t",mktime(0,0,0,$ymd['m']+$nm,$ymd['d']+$nd,$ymd['y']+$ny));
	}else{
	return date("Y-m-d",mktime(0,0,0,$ymd['m']+$nm,$ymd['d']+$nd,$ymd['y']+$ny));
	}
}
function parse_tenor($str){
	$subject=strtolower($str);
	$pattern='/\s*([0-9]+)([dmwqy])\s*/';
	if(preg_match($pattern,$subject,$matches,PREG_OFFSET_CAPTURE)){
		$ret['iteration']=intval($matches[1][0]);
		$ret['tenor_token']=$matches[2][0];
		//print_r($matches);	
		//echo $matches[1][0].$matches[2][0]."\n";
	}else{
		//echo 'No matches'."\n";
		$pattern='/\s*(tod|tom|spot)\s*/';
		if(preg_match($pattern,$subject,$matches,PREG_OFFSET_CAPTURE)){
			switch($matches[1][0]){
				case 'spot':
					$ret['tenor_token']=$matches[1][0];
					break;
				case 'tom' :
					$ret['tenor_token']=$matches[1][0];
					break;
				case 'tod' :
					$ret['tenor_token']=$matches[1][0];
					break;
				default:
					return false;
					break;
			}
		}else{
			return false;
		}	
	}	
	return $ret;
}
function add_tenor($anchor_date,$tenorstr,$apply_eom=true){
	if($ret=parse_tenor($tenorstr)){
		switch($ret['tenor_token']){
			case 'd':
				return add_ymd($anchor_date,$ret['iteration'],0,0,$apply_eom);
				break;
			case 'm':
				return add_ymd($anchor_date,0,$ret['iteration'],0,$apply_eom);
				break;
			case 'q':
				return add_ymd($anchor_date,0,$ret['iteration']*3,0,$apply_eom);
				break;
			case 'w':
				return add_ymd($anchor_date,$ret['iteration']*7,0,0,$apply_eom);
				break;
			case 'y':
				return add_ymd($anchor_date,0,0,$ret['iteration'],$apply_eom);
				break;
			case 'spot':
				break;
			case 'tom':
				break;
			case 'tod':
				break;
			default:
				return add_ymd($anchor_date,0,0,0,$apply_eom);
				break;
		}
	}else{
		return false;
	}
}
function subtract_tenor($anchor_date,$tenorstr,$apply_eom=true){
	if($ret=parse_tenor($tenorstr)){
		switch($ret['tenor_token']){
			case 'd':
				return add_ymd($anchor_date,-$ret['iteration'],0,0,$apply_eom);
				break;
			case 'm':
				return add_ymd($anchor_date,0,-$ret['iteration'],0,$apply_eom);
				break;
			case 'q':
				return add_ymd($anchor_date,0,-$ret['iteration']*3,0,$apply_eom);
				break;
			case 'w':
				return add_ymd($anchor_date,-$ret['iteration']*7,0,0,$apply_eom);
				break;
			case 'y':
				return add_ymd($anchor_date,0,0,-$ret['iteration'],$apply_eom);
				break;
			default:
				return add_ymd($anchor_date,0,0,0,$apply_eom);
				break;
		}
	}else{
		return false;
	}
}
function add_tenor_and_apply_convention($anchor_date,$tenorstr,$convention="ModifiedFollowing",$hol_arr=array(),$hol_weekdays=array(2,3)){
	$date=add_tenor($anchor_date,$tenorstr);
	$date=applyConvention($date,$convention,$hol_arr,$hol_weekdays);
	return $date;
}
function subtract_tenor_and_apply_convention($anchor_date,$tenorstr,$convention="ModifiedFollowing",$hol_arr=array(),$hol_weekdays=array(2,3)){
	$date=subtract_tenor($anchor_date,$tenorstr);
	$date=applyConvention($date,$convention,$hol_arr,$hol_weekdays);
	return $date;
}
function number_of_days($d1_str,$d2_str){
	return date_to_serial($d2_str)-date_to_serial($d1_str);
}
function is_leap_year($year){
	return (($year%4)==0) && ((($year%100)!=0)|| ($year%400)==0);
}
function is_date_in_leap_year($date_str){
	return is_leap_year(get_ymd($date_str)['y']);
}
function get_frequency($freq_str){ // e.g. $freq_str="3m" then frequency is 4  (from 12m/3m)
	if($ret=parse_tenor($freq_str)){
		switch($ret['tenor_token']){
			case 'd':
				return 365/$ret['iteration'];
				break;
			case 'm':
				return 12/$ret['iteration'];
				break;
			case 'q':
				return 4/$ret['iteration'];
				break;
			case 'w':
				return 52/$ret['iteration'];
				break;
			case 'y':
				return 1/$ret['iteration'];
				break;
			default:
				return 1;
				break;
		}
	}else{
		return 1;
	}
}
function daycount_factor($d1_str,$d2_str,$dc_convention="act/365",$freq_str="3M",$bd_convention="ModifiedFollowing",$hol_arr=array(),$hol_weekdays=array(2,3),$invest_eom=false){ //returns year factor
	$dc_convention=strtolower($dc_convention);
	$ymd1=array("y"=>0,"m"=>0,"d"=>0);
	$ymd2=array("y"=>0,"m"=>0,"d"=>0);
	$ymd1=get_ymd($d1_str);
	$ymd2=get_ymd($d2_str);
	$res=0.0;
	$freq=get_frequency($freq_str);
	switch($dc_convention){
		case 'act/act' :   //act/365 which is same to act/act_icma
		case 'act/act_icma' : //act/act ICMA aka act/act
			$nom=number_of_days($d1_str,$d2_str);
			$d3_str=add_tenor($d1_str,$freq_str,$invest_eom);
			$d3_str=applyConvention($d3_str,$bd_convention,$hol_arr,$hol_weekdays);
			$denom=number_of_days($d1_str,$d3_str)*get_frequency($freq_str);
			$res=$nom/$denom;
			break;
		case 'act/365' :   //act/365 which is same to act/act_isda
		case 'act/act_isda' : //act/act ISDA aka act/365
			$dpoint=$d1_str;
			for($i=$ymd1['y'];$i<=$ymd2['y'];$i++){
				$denom= (is_leap_year($i))? 366 : 365;
				$n_days=number_of_days($dpoint,($i==$ymd2['y'])?$d2_str:make_date_str($i+1,1,1));
				$res=$res+$n_days/$denom;
				$dpoint=make_date_str($i+1,1,1);
			}
			break;
		case 'act/365_fixed' : // act/365 fixed
			$res=number_of_days($d1_str,$d2_str)/365;
			break;
		case 'act/360' : //act/260 libor
			$res=number_of_days($d1_str,$d2_str)/360;
			break;
		case 'act/364' : //act/364
			$res=number_of_days($d1_str,$d2_str)/364;
			break;
		case 'act/365l' : // act/365L
			$has_leap=false;
			$diy=365;
			if($freq==1){
				for($i=$ymd1['y'];$i<=$ymd2['y'];$i++){
					if(is_leap_year($i) && date_to_serial("$i-02-29") >= date_to_serial($d1_str) && date_to_serial("$i-02-29") < date_to_serial($d2_str))
						$has_leap=true;
				}
				if($has_leap) $diy=366;
			}else{
				if(is_date_in_leap_year($d2_str)) $diy=366;
			}
			$res=number_of_days($d1_str,$d2_str)/$diy;
			break;
		case 'act/act_afb' : // act/act AFB
			$diy=365;
			$has_leap=false;
			$datepoint=$d2_str;
			$res=0;
			while(date_to_serial($datepoint)>date_to_serial($d1_str)){
				$res++;
				$datepoint=add_ymd($datepoint,0,0,-1);
			}
			$res--;
			$datepoint=add_ymd($datepoint,0,0,1);
			$ymdp=get_ymd($datepoint);
			if( (date_to_serial($d1_str)<=date_to_serial("$ymd1[y]-02-29") && date_to_serial($datepoint)>date_to_serial("$ymd1[y]-02-29"))
					|| (date_to_serial($d1_str)<=date_to_serial("$ymdp[y]-02-29") && date_to_serial($datepoint)>date_to_serial("$ymdp[y]-02-29")) )
					 $has_leap=true;
					if($has_leap) $diy=366;
					$res=$res+(number_of_days($d1_str,$datepoint))/$diy;
					break;
		case '30/360' : // normal 30/360
			$res=(360*($ymd2['y']-$ymd1['y'])+30*($ymd2['m']-$ymd1['m'])+($ymd2['d']-$ymd1['d']))/360;
			break;
		case '30a/360' :  // 30/360 bond basis, aka 30A/360
			$ymd1['d']=min($ymd1['d'],30);
			if($ymd1['d']==30) $ymd2['d']=min($ymd2['d'],30);
			$res=(360*($ymd2['y']-$ymd1['y'])+30*($ymd2['m']-$ymd1['m'])+($ymd2['d']-$ymd1['d']))/360;
			break;
		case '30u/360' :  // 30/360 US
			if(invest_eom && isEOM($d1_str)&&$ymd1['m']==2 && isEOM($d2_str) && $ymd2['m']==2){
				$ymd2['d']=30;
			}
			if(invest_eom && isEOM($d1_str)&&$ymd1['m']==2 ){
				$ymd1['d']=30;
			}
			if($ymd2['d']==31) $ymd2['d']=30;
			if($ymd1['d']==31) $ymd1['d']=30;
			$res=(360*($ymd2['y']-$ymd1['y'])+30*($ymd2['m']-$ymd1['m'])+($ymd2['d']-$ymd1['d']))/360;
			break;
		case '30e/360' :  // 30/360 European
			if($ymd1['d']==31) $ymd1['d']=30;
			if($ymd2['d']==31) $ymd2['d']=30;
			$res=(360*($ymd2['y']-$ymd1['y'])+30*($ymd2['m']-$ymd1['m'])+($ymd2['d']-$ymd1['d']))/360;
			break;
		case '30e/360_isda' :  // 30/360 ISDA aka German
			if(isEOM($d1_str)) $ymd1['d']=30;
			if(isEOM($d2_str) && $ymd2['m']!=2) $ymd2['d']=30;
			$res=(360*($ymd2['y']-$ymd1['y'])+30*($ymd2['m']-$ymd1['m'])+($ymd2['d']-$ymd1['d']))/360;
			break;
		default :
			$res=daycount_factor($d1,$d2,"act/365");
			break;
	}
	return $res;
}
function get_isodate_from_string($anchor_date_str,$stdate_or_tenor,$matdate_or_tenor,$params){//params['instrument'],params['instrument_subtype'],params['currency_pair'],'fixed_payment_holiday','float_payment_holiday'
	//stdate to isodate
	$anchor_date_str=to_iso_date_format($anchor_date_str);
	$hol_arr_1=$params['payment_holiday_array'];
	$hol_arr_2=$params['fixing_holiday_array'];
	if($matches=parse_tenor($stdate_or_tenor)){
		$tenorstr=$stdate_or_tenor;
		switch($params['instrument']){
			case 'swappoint' : //fx type till 'ccs'
			case 'swap' :
				switch($params['instrument_subtype']){
					case 'basis':
					case 'ccs' :
						//here comes the fx date computation
						$stdate_str=fx_tenor_to_date_from_today($anchor_date_str,$params['currency_pair'],$tenorstr,$hol_arr_1,$hol_arr_2);
						break;
					case 'irs' :
					case 'bond' : //default
					default:
						//here comes the fx date computation
						$stdate_str=fx_tenor_to_date_from_today($anchor_date_str,$params['usdusd'],$tenorstr,$hol_arr_1,$hol_arr_2);
						break;
				}
				break;
			case 'depo' : // depo will be the default
			default :
				$stdate_str=fx_tenor_to_date_from_today($anchor_date_str,$params['usdusd'],$tenorstr,$hol_arr_1,$hol_arr_2);
				break;
		}
	}else{//if it's not tenor form (tod,tom,spot,1m,2m,...)
		$stdate_str= to_iso_date_format($stdate_or_tenor);
	} //end of STDATE

	//begin of MATDATZE
	if($matches=parse_tenor($matdate_or_tenor)){
		$anchor_date_str=$stdate_str;
		$tenorstr=$matdate_or_tenor;
		switch($params['instrument']){
			case 'swappoint' : //fx type till 'ccs'
			case 'swap' :
				switch($params['instrument_subtype']){
					case 'basis':
					case 'ccs' :
						//here comes the fx date computation
						$matdate_str=fx_tenor_to_date_from_today($anchor_date_str,$params['currency_pair'],$tenorstr,$hol_arr_1,$hol_arr_2);
						break;
					case 'irs' :
					case 'bond' : //default
					default:
						//here comes the fx date computation
						$matdate_str=fx_tenor_to_date_from_today($anchor_date_str,$params['usdusd'],$tenorstr,$hol_arr_1,$hol_arr_2);
						break;
				}
				break;
			case 'depo' : // depo will be the default
			default :
				$matdate_str=fx_tenor_to_date_from_today($anchor_date_str,$params['usdusd'],$tenorstr,$hol_arr_1,$hol_arr_2);
				break;
		}
	}else{//if it's not tenor form (tod,tom,spot,1m,2m,...)
		$matdate_str= to_iso_date_format($matdate_or_tenor);
	} //end of STDATE

	return array("iso_st_date"=>$stdate_str,"iso_mat_date"=>$matdate_str);
}
//cashflow scheduler
	//cashflow data $cfs=array(array('date'=>'2015-5-2','cashflow'=>10000,'currency'=>'usd'),array(...),...) : cashflow vector
function generate_cashflows_from_parameters(&$instrument_params,$anchor_date_str,&$cfs_out_1,&$cfs_out_2){
	$direction=1; //payer, borrow, buy
	if(array_key_exists('notional',$instrument_params)) $notional=$instrument_params['notional'];
	$stdate_str=$instrument_params['start'];
	$matdate_str=$instrument_params['maturity'];
	$currency1=$instrument_params['currency'];
	$currency2=(substr($instrument_params['currency_pair'],0,3)==$currency1)
	? substr($instrument_params['currency_pair'],3,3) : substr($instrument_params['currency_pair'],0,3) ;
	$fixed_bd_convention=$instrument_params['fixed_bd_convention'];
	$fixed_dc_convention=$instrument_params['fixed_dc_convention'];
	$float_bd_convention=$instrument_params['float_bd_convention'];
	$float_dc_convention=$instrument_params['float_dc_convention'];
	$ref_rate=$instrument_params['ref_rate'];
	$ref_rate_2=$instrument_params['ref_rate_2'];
	
	$leg_1_fixed=substr((strtolower($instrument_params['fixfloat'])),0,2)=='fx';
	$leg_2_fixed=substr((strtolower($instrument_params['fixfloat'])),2,2)=='fx';
	$fx_rate=$instrument_params['fx_rate'];

	$initial_exchange=(strtolower($instrument_params['initial_exchange']))=='yes';
	$final_exchange=(strtolower($instrument_params['final_exchange']))=='yes';
	
	$spread_1=0;
	$spread_2=0;

	switch(strtolower($instrument_params['instrument'])){
		case 'depo' :
			$cfs_out_1=array();
			if(strtolower($instrument_params['direction'])=='lend') $direction=-1;
			$cfs_out_1[0]['date']=$stdate_str;
			$cfs_out_1[0]['cashflow']=$notional*$direction;
			$cfs_out_1[0]['currency']=$currency1;
			$cfs_out_1[1]['date']=$matdate_str;
			$coupon=$notional*($ref_rate)*daycount_factor($stdate_str,$matdate_str,$fixed_dc_convention);
			$cfs_out_1[1]['cashflow']=($notional+$coupon)*$direction*-1;
			$cfs_out_1[1]['currency']=$currency1;
			break;
		case 'swappoint' :
			$cfs_out_1=array();
			$cfs_out_2=array();
			$currency1=substr($instrument_params['currency_pair'],0,3); // base ccy
			$currency2=substr($instrument_params['currency_pair'],3,3); // quote ccy
			if(strtolower($instrument_params['direction'])=='sell') $direction=-1;
			//cfs_out_1 (base currency)
			$cfs_out_1[0]['date']=$stdate_str;
			$cfs_out_1[0]['cashflow']=$notional*$direction*-1;
			$cfs_out_1[0]['currency']=$currency1;
			$cfs_out_1[1]['date']=$matdate_str;
			$cfs_out_1[1]['cashflow']=($notional)*$direction;
			$cfs_out_1[1]['currency']=$currency1;
			//cfs_out_2 (quote currency)
			$cfs_out_2[0]['date']=$stdate_str;
			$cfs_out_2[0]['cashflow']=($notional*$fx_rate)*$direction*1;
			$cfs_out_2[0]['currency']=$currency2;
			$cfs_out_2[1]['date']=$matdate_str;
			$cfs_out_2[1]['cashflow']=($notional*($fx_rate+$ref_rate))*$direction*-1;
			$cfs_out_2[1]['currency']=$currency2;
			break;
		case 'swap' :
			switch(strtolower($instrument_params['instrument_subtype'])){
				case 'irs' :
					//preparation
					if(strtolower($instrument_params['direction'])=='receiver') $direction=-1;
					$estimation_curve_1=$instrument_params['estimation_curve_1'];
					$estimation_curve_2=$instrument_params['estimation_curve_2'];
					$spread_1=$instrument_params['spread_1'];
					$spread_2=$instrument_params['spread_2'];
						
					//generate schedules
					generate_schedules_from_parameters($instrument_params,$cfs_out_1,$cfs_out_2);
						
					//generate cashflows
					$inum=0;
					$cfs_out_1[$inum]['cashflow']=0*$direction;
					$cfs_out_1[$inum]['currency']=$currency1;
					foreach($cfs_out_1 as $val){ //leg_1
						if($inum>0){
							if($leg_1_fixed){ //fixed cashflow (ref_rate1)
								$cfs_out_1[$inum]['cashflow']=$notional*($ref_rate+$spread_1)*daycount_factor($cfs_out_1[$inum-1]['date'],$cfs_out_1[$inum]['date'],$fixed_dc_convention)*$direction*-1;
								$cfs_out_1[$inum]['currency']=$currency1;
							}else{ //floating cashflow - estimation curve required
								$dtm_s=days_between_dates($anchor_date_str,$cfs_out_1[$inum-1]['date']);
								$dtm_m=days_between_dates($anchor_date_str,$cfs_out_1[$inum]['date']);
								$fl_coupon=df_interpolator($dtm_s,$estimation_curve_1)/df_interpolator($dtm_m,$estimation_curve_1)-1.0+$spread_1*daycount_factor($cfs_out_1[$inum-1]['date'],$cfs_out_1[$inum]['date'],$fixed_dc_convention);
								$cfs_out_1[$inum]['cashflow']=$notional*$fl_coupon*$direction*-1;
								$cfs_out_1[$inum]['currency']=$currency1;
							}
						}
						$inum++;
					}
					$inum=0;
					$cfs_out_2[$inum]['cashflow']=0*$direction*-1;
					$cfs_out_2[$inum]['currency']=$currency1;
					foreach($cfs_out_2 as $val){ //leg_2, currency 1 for irs, currency 2 for ccs
						if($inum>0){
							if($leg_2_fixed){ //fixed cashflow (ref_rate2)
								$cfs_out_2[$inum]['cashflow']=$notional*($ref_rate_2+$spread_2)*daycount_factor($cfs_out_2[$inum-1]['date'],$cfs_out_2[$inum]['date'],$float_dc_convention)*$direction;
								$cfs_out_2[$inum]['currency']=$currency1;
							}else{ //floating cashflow - estimation curve required
								$dtm_s=days_between_dates($anchor_date_str,$cfs_out_2[$inum-1]['date']);
								$dtm_m=days_between_dates($anchor_date_str,$cfs_out_2[$inum]['date']);
								$fl_coupon=df_interpolator($dtm_s,$estimation_curve_2)/df_interpolator($dtm_m,$estimation_curve_2)-1.0+$spread_2*daycount_factor($cfs_out_2[$inum-1]['date'],$cfs_out_2[$inum]['date'],$float_dc_convention);
								$cfs_out_2[$inum]['cashflow']=$notional*$fl_coupon*$direction;
								$cfs_out_2[$inum]['currency']=$currency1;
							}
						}
						$inum++;
					}
					break;
				case 'basis' : //requires 2 estimation curve
				case 'ccs' : //initial/final exchange
					//preparation
					if(strtolower($instrument_params['direction'])=='receiver') $direction=-1;
					$estimation_curve_1=$instrument_params['estimation_curve_1'];
					$estimation_curve_2=$instrument_params['estimation_curve_2'];
					$spread_1=$instrument_params['spread_1'];
					$spread_2=$instrument_params['spread_2'];
					//generate schedules
					generate_schedules_from_parameters($instrument_params,$cfs_out_1,$cfs_out_2);

					//generate cashflows
						
					//leg_1
					$inum=0;
					if($initial_exchange) $cfs_out_1[$inum]['cashflow']=$notional*$direction;
					else $cfs_out_1[$inum]['cashflow']=0*$direction;
					$cfs_out_1[$inum]['currency']=$currency1;
					foreach($cfs_out_1 as $val){ //leg_1
						if($inum>0){
							if($leg_1_fixed){ //fixed cashflow (ref_rate1)
								$cfs_out_1[$inum]['cashflow']=$notional*($ref_rate+$spread_1)*daycount_factor($cfs_out_1[$inum-1]['date'],$cfs_out_1[$inum]['date'],$fixed_dc_convention)*$direction*-1;
								$cfs_out_1[$inum]['currency']=$currency1;
							}else{ //floating cashflow - estimation curve required
								$dtm_s=days_between_dates($anchor_date_str,$cfs_out_1[$inum-1]['date']);
								$dtm_m=days_between_dates($anchor_date_str,$cfs_out_1[$inum]['date']);
								$fl_coupon=df_interpolator($dtm_s,$estimation_curve_1)/df_interpolator($dtm_m,$estimation_curve_1)-1.0+$spread_1*daycount_factor($cfs_out_1[$inum-1]['date'],$cfs_out_1[$inum]['date'],$fixed_dc_convention);
								$cfs_out_1[$inum]['cashflow']=$notional*$fl_coupon*$direction*-1;
								$cfs_out_1[$inum]['currency']=$currency1;
							}
						}
						$inum++;
					}
					if($final_exchange) $cfs_out_1[$inum-1]['cashflow']=$cfs_out_1[$inum-1]['cashflow']+$notional*$direction*-1;
						
					//leg_2
					$inum=0;
					if($initial_exchange) $cfs_out_2[$inum]['cashflow']=$notional*$fx_rate*$direction*-1;
					else $cfs_out_2[$inum]['cashflow']=0*$direction*-1;
					$cfs_out_2[$inum]['currency']=$currency2;
					foreach($cfs_out_2 as $val){ //leg_2, currency 1 for irs, currency 2 for ccs
						if($inum>0){
							if($leg_2_fixed){ //fixed cashflow (ref_rate2)
								$cfs_out_2[$inum]['cashflow']=$notional*$fx_rate*($ref_rate_2+$spread_2)*daycount_factor($cfs_out_2[$inum-1]['date'],$cfs_out_2[$inum]['date'],$float_dc_convention)*$direction;
								$cfs_out_2[$inum]['currency']=$currency2;
							}else{ //floating cashflow - estimation curve required
								$dtm_s=days_between_dates($anchor_date_str,$cfs_out_2[$inum-1]['date']);
								$dtm_m=days_between_dates($anchor_date_str,$cfs_out_2[$inum]['date']);
								$fl_coupon=df_interpolator($dtm_s,$estimation_curve_2)/df_interpolator($dtm_m,$estimation_curve_2)-1.0+$spread_2*daycount_factor($cfs_out_2[$inum-1]['date'],$cfs_out_2[$inum]['date'],$float_dc_convention);
								$cfs_out_2[$inum]['cashflow']=$notional*$fx_rate*$fl_coupon*$direction;
								$cfs_out_2[$inum]['currency']=$currency2;
							}
						}
						$inum++;
					}
					if($final_exchange) $cfs_out_2[$inum-1]['cashflow']=$cfs_out_2[$inum-1]['cashflow']+$notional*$fx_rate*$direction;
					break;

				default:
					break;
			}
			break;
		case 'bond' :
			switch(strtolower($instrument_params['instrument_subtype'])){
				case 'frn' :
					//preparation
					if(strtolower($instrument_params['direction'])=='buy') $direction=-1;
					$leg_1_fixed=false;
					$estimation_curve_1=$instrument_params['estimation_curve_1'];
					$spread_1=$instrument_params['spread_1'];
					//generate schedules
					generate_schedules_from_parameters($instrument_params,$cfs_out_1,$cfs_out_2);

					//generate cashflows
						
					//leg_1
					$inum=0;
					$cfs_out_1[$inum]['cashflow']=$notional*$direction;
					$cfs_out_1[$inum]['currency']=$currency1;
					foreach($cfs_out_1 as $val){ //leg_1
						if($inum>0){
							if($leg_1_fixed){ //fixed cashflow (ref_rate1)
								$cfs_out_1[$inum]['cashflow']=$notional*$ref_rate*daycount_factor($cfs_out_1[$inum-1]['date'],$cfs_out_1[$inum]['date'],$fixed_dc_convention)*$direction*-1;
								$cfs_out_1[$inum]['currency']=$currency1;
							}else{ //floating cashflow - estimation curve required
								$dtm_s=days_between_dates($anchor_date_str,$cfs_out_1[$inum-1]['date']);
								$dtm_m=days_between_dates($anchor_date_str,$cfs_out_1[$inum]['date']);
								$fl_coupon=df_interpolator($dtm_s,$estimation_curve_1)/df_interpolator($dtm_m,$estimation_curve_1)-1.0+$spread_1;
								$cfs_out_1[$inum]['cashflow']=$notional*$fl_coupon*$direction*-1;
								$cfs_out_1[$inum]['currency']=$currency1;
							}
						}
						$inum++;
					}
					$cfs_out_1[$inum-1]['cashflow']=$cfs_out_1[$inum-1]['cashflow']+$notional*$direction*-1;
					break;
				default : //fixed rate bond
					//preparation
					if(strtolower($instrument_params['direction'])=='buy') $direction=-1;
					$leg_1_fixed=true;
					//generate schedules
					generate_schedules_from_parameters($instrument_params,$cfs_out_1,$cfs_out_2);
						
					//generate cashflows

					//leg_1
					$inum=0;
					$cfs_out_1[$inum]['cashflow']=$notional*$direction;
					$cfs_out_1[$inum]['currency']=$currency1;
					foreach($cfs_out_1 as $val){ //leg_1
						if($inum>0){
							if($leg_1_fixed){ //fixed cashflow (ref_rate1)
								$cfs_out_1[$inum]['cashflow']=$notional*$ref_rate*daycount_factor($cfs_out_1[$inum-1]['date'],$cfs_out_1[$inum]['date'],$fixed_dc_convention)*$direction*-1;
								$cfs_out_1[$inum]['currency']=$currency1;
							}else{ //floating cashflow - estimation curve required
								$dtm_s=days_between_dates($anchor_date_str,$cfs_out_1[$inum-1]['date']);
								$dtm_m=days_between_dates($anchor_date_str,$cfs_out_1[$inum]['date']);
								$fl_coupon=df_interpolator($dtm_s,$estimation_curve_1)/df_interpolator($dtm_m,$estimation_curve_1)-1.0;
								$cfs_out_1[$inum]['cashflow']=$notional*$fl_coupon*$direction*-1;
								$cfs_out_1[$inum]['currency']=$currency1;
							}
						}
						$inum++;
					}
					$cfs_out_1[$inum-1]['cashflow']=$cfs_out_1[$inum-1]['cashflow']+$notional*$direction*-1;
					break;
			}
			break;
		default :
			break;
	}
	if(array_key_exists('ext_cashflow_1',$instrument_params)) $cfs_out_1=array_merge($cfs_out_1,$instrument_params['ext_cashflow_1']);
	if(array_key_exists('ext_cashflow_2',$instrument_params)) $cfs_out_2=array_merge($cfs_out_2,$instrument_params['ext_cashflow_2']);
	return true;
}
function sort_cashflow(&$cfs){//change $cfs itself
	for($i=0;$i<count($cfs);$i++)
		$cfs[$i]['date']=date_to_serial($cfs[$i]['date']);
	$cfs=array_sort($cfs,'date');
	for($i=0;$i<count($cfs);$i++)
		$cfs[$i]['date']=serial_to_date($cfs[$i]['date']);
}
function sort_cashflow_and_sum(&$cfs){//change $cfs itself
	sort_cashflow($cfs);
	for($i=1;$i<count($cfs);$i++){
		if($cfs[$i]['date']==$cfs[$i-1]['date']){
			$cfs[$i-1]['cashflow']+=$cfs[$i]['cashflow'];
			$cfs[$i]['cashflow']=0.0;
			unset($cfs[$i]);
		}
	}
	$cfs=array_values($cfs);
}
function generate_schedules_from_parameters(&$parameters,&$cfs_out_1,&$cfs_out_2,$option="simple"){
	$forward=(strtolower($parameters['forward'])=='yes');
	$short_coupon=(strtolower($parameters['short_coupon'])=='yes');
	switch(strtolower($parameters['instrument'])){
		case 'swap':
			generate_schedule($cfs_out_1,$parameters['start'],$parameters['maturity'],$parameters['fixed_payment_frequency'],$forward,$short_coupon,$parameters['fixed_bd_convention']
			,$parameters['payment_holiday_array'],$hol_weekdays=array(2,3),$option);
			generate_schedule($cfs_out_2,$parameters['start'],$parameters['maturity'],$parameters['float_payment_frequency'],$forward,$short_coupon,$parameters['float_bd_convention']
					,$parameters['payment_holiday_array'],$hol_weekdays=array(2,3),$option);
			return 2; //number of cashflows
			break;
		case 'depo' :
			$cfs_out_1[0]['date']=$parameters['start'];
			$cfs_out_1[1]['date']=$parameters['maturity'];
			return 1;
			break;
		case 'swappoint' :
			$cfs_out_1[0]['date']=$parameters['start'];
			$cfs_out_1[1]['date']=$parameters['maturity'];
			return 1;
			break;
		case 'bond' :
			generate_schedule($cfs_out_1,$parameters['start'],$parameters['maturity'],$parameters['fixed_payment_frequency'],$forward,$short_coupon,$parameters['fixed_bd_convention']
			,$parameters['payment_holiday_array'],$hol_weekdays=array(2,3),$option);
			return 1;
			break;
		default :
			break;
	}
	return 0;
}
function generate_schedule(&$cfs_out,$stdate_str,$matdate_str,$freq_str="3M",$forward=true,$short_coupon=true,$convention="ModifiedFollowing",$hol_arr=array(),$hol_weekdays=array(2,3),$option="Simple"){
	switch(strtolower($option)){
		case 'simple':
			generate_schedule_simple($cfs_out,$stdate_str,$matdate_str,$freq_str,$forward,$short_coupon,$convention,$hol_arr,$hol_weekdays);
			break;
		default :
			break;
	}

}
function generate_schedule_simple(&$cfs_out,$stdate_str,$matdate_str,$freq_str="3M",$forward=true,$short_coupon=true,$convention="ModifiedFollowing",$hol_arr=array(),$hol_weekdays=array(2,3)){
	$cnt=0;
	$ymd=get_ymd($stdate_str);
	$stdate_str=make_date_str($ymd['y'],$ymd['m'],$ymd['d']);
	$ymd=get_ymd($matdate_str);
	$matdate_str=make_date_str($ymd['y'],$ymd['m'],$ymd['d']);
	
	if($forward) {
		$date_point=$stdate_str;
		$cfs_out[$cnt]['date']=$date_point;
		$cnt++;
		while(date_to_serial($date_point)<date_to_serial($matdate_str)){
			$date_point=$stdate_str;
			for($i=0;$i<$cnt;$i++)
				$date_point=add_tenor($date_point,$freq_str);
			$date_point=applyConvention($date_point,$convention,$hol_arr,$hol_weekdays);
			$cfs_out[$cnt]['date']=$date_point;
			$cnt++;
		}
		$cfs_out[$cnt-1]['date']=$matdate_str;
		//short coupon check
		if(!$short_coupon &&  $cnt>2 && (date_to_serial($cfs_out[$cnt-1])<date_to_serial(add_tenor($cfs_out[$cnt-2],$freq_str))) )
			array_splice($cfs_out,$cnt-2,1);

	}else{
		$date_point=$matdate_str;
		$cfs_out[$cnt]['date']=$date_point;
		$cnt++;
		while(date_to_serial($date_point)>date_to_serial($stdate_str)){
			$date_point=$matdate_str;
			for($i=0;$i<$cnt;$i++)
				$date_point=subtract_tenor($date_point,$freq_str);
			$date_point=applyConvention($date_point,$convention,$hol_arr,$hol_weekdays);
			$cfs_out[$cnt]['date']=$date_point;
			$cnt++;
		}
		$cfs_out[$cnt-1]['date']=$stdate_str;
		$cfs_out=array_reverse($cfs_out);
		if(!$short_coupon &&  $cnt>2 && (date_to_serial($cfs_out[1])<date_to_serial(add_tenor($cfs_out[0],$freq_str))) )
			array_splice($cfs_out,1,1);

	}
}
function get_npv(&$cfs,&$dfs,$anchor_date_str){
	$net_npv=0;
	foreach($cfs as $val){
		$dtm=days_between_dates($anchor_date_str,$val['date']);
		$net_npv=$net_npv+$val['cashflow']*df_interpolator($dtm,$dfs);
	}
	return $net_npv;
}
function cashflow_npv_from_parameters(&$param,$anchor_date_str){
	$cfs_1=array();
	$cfs_2=array();
	generate_cashflows_from_parameters($param,$anchor_date_str,$cfs_1,$cfs_2);
	$fx_reval=1.0;
	if(array_key_exists('fx_rate_reval',$param)) $fx_reval=1/$param['fx_rate_reval'];
	if(substr(strtolower($param['currency_pair']),0,3)!=strtolower($param['currency'])){
		$fx_reval=1/$fx_reval;
	}
	if(strtolower($param['instrument'])=='swappoint' ||strtolower($param['instrument_subtype'])=='ccs' ||strtolower($param['instrument_subtype'])=='basis' ){
		$net_npv=get_npv($cfs_1,$param['discount_curve_1'],$anchor_date_str)+get_npv($cfs_2,$param['discount_curve_2'],$anchor_date_str)*$fx_reval;
	}else{
		$net_npv=get_npv($cfs_1,$param['discount_curve_1'],$anchor_date_str)+get_npv($cfs_2,$param['discount_curve_2'],$anchor_date_str);
	}
	return $net_npv;
}
function cashflow_npv_solver($params,$anchor_date_str,$changing_property_name='ref_rate',$target_npv=0,$tolerance=0.000001,$max_iter=1000){
	$cpn=&$changing_property_name;
	$chg_val=&$params[$cpn];
	global $_gbl_debug;

	$chg_val=$chg_val-$tolerance/2;$left=cashflow_npv_from_parameters($params,$anchor_date_str);
	$chg_val=$chg_val+$tolerance/1;$right=cashflow_npv_from_parameters($params,$anchor_date_str);
	$chg_val=$chg_val-$tolerance/2;
	$mid=cashflow_npv_from_parameters($params,$anchor_date_str);
	if($_gbl_debug) echo $left." ".$mid." ".$right.PHP_EOL;
	$slope=($right-$left)/($tolerance/1);
	$chg_val=-(-$target_npv+$mid)/$slope+$chg_val;
	$mid=cashflow_npv_from_parameters($params,$anchor_date_str);
	$error=abs($target_npv-$mid);
	if($_gbl_debug) echo  $params[$cpn]." ".$slope." ".$mid." ".$error.PHP_EOL;
	$inum=0;
	while($error>$tolerance && $inum++<$max_iter){
		$chg_val=$chg_val-$tolerance/2;$left=cashflow_npv_from_parameters($params,$anchor_date_str);
		$chg_val=$chg_val+$tolerance/1;$right=cashflow_npv_from_parameters($params,$anchor_date_str);
		$chg_val=$chg_val-$tolerance/2;
		$slope=($right-$left)/($tolerance/1);
		$chg_val=-(-$target_npv+$mid)/$slope+$chg_val;
		$mid=cashflow_npv_from_parameters($params,$anchor_date_str);
		if($_gbl_debug) echo  $params[$cpn]." ".$slope." ".$mid." ".$error.PHP_EOL;
		$error=abs($target_npv-$mid);
	}

	if($_gbl_debug) echo $params[$cpn].PHP_EOL;
	return $params[$cpn];
}

//FX specific functions -need to be amended and expanded. rule base date computation shd be employed.

function fxSpotDate_old($today,$bchols_arr,$qchols_arr,$bchol_wd=array(2,3),$qchol_wd=array(2,3)){
	$d=roll_n_opendates($today,2,$qchols_arr,$qchol_wd);
	$d=Following($d,$bchols_arr,$bchol_wd);
	return $d;
} //return is 'YYYY-MM-DD'
function fxSpotDate($today,$ccypair,$bchols_arr,$qchols_arr,$bchol_wd=array(2,3),$qchol_wd=array(2,3)){
	
	// FX date adjustment
	$tmphol=$qchols_arr;		
	if(substr(strtolower($ccypair),0,3)!='usd'){
		if(substr(strtolower($ccypair),3,3)!='usd'){
			$qchols_arr=array_unique(array_merge($qchols_arr,$bchols_arr)); // in case ccy pair doesn't include 'usd'
		}else{
			$qchols_arr=$bchols_arr;
			$bchols_arr=$tmphol;
		}
	}//FX date adjustment ends
		
	$d=roll_n_opendates($today,2,$qchols_arr,$qchol_wd);
	$d=Following($d,$bchols_arr,$bchol_wd);
	return $d;
} //return is 'YYYY-MM-DD'
function fxTomDate($today,$bchols_arr,$qchols_arr,$bchol_wd=array(2,3),$qchol_wd=array(2,3)){
	$d=roll_n_opendates($today,1,$qchols_arr,$qchol_wd);
	$d=Following($d,$bchols_arr,$bchol_wd);
	return $d;
} //return is 'YYYY-MM-DD'
function fxFixingDate($maturity,$ccypair,$bchols_arr,$qchols_arr,$bchol_wd=array(2,3),$qchol_wd=array(2,3)){
	$d=$maturity;
	//$d=roll_n_opendates($maturity,-2,$qchols_arr,$qchol_wd);
	while(date_to_serial(fxSpotDate($d,$ccypair,$bchols_arr,$qchols_arr,$bchol_wd,$qchol_wd))>date_to_serial($maturity)){
		$d=roll_n_opendates($d,-1,$qchols_arr,$qchol_wd);
	}
	return $d;
} //return is 'YYYY-MM-DD'
function fx_tenor_to_date($today,$ccypair,$tenorstr,$bchols_arr,$qchols_arr,$bchol_wd=array(2,3),$qchol_wd=array(2,3)){
	if(intval(strtotime($tenorstr))==0){
		switch(parse_tenor($tenorstr)['tenor_token']){
			case 'spot' :
				$retdate=fxSpotDate($today,$ccypair,$bchols_arr,$qchols_arr,$bchol_wd,$qchol_wd);
				break;
			case 'tom' :
				$retdate=fxTomDate($today,$bchols_arr,$qchols_arr,$bchol_wd,$qchol_wd);
				break;
			case 'tod' :
				$retdate=$today;
				break;
			default : 
				$spotdate=fxSpotDate($today,$ccypair,$bchols_arr,$qchols_arr,$bchol_wd,$qchol_wd);
				$retdate=add_tenor($spotdate,$tenorstr,true);
				$retdate=modifiedFollowing($retdate,array_merge($bchols_arr,$qchols_arr));
				break;
		}
		return $retdate;
	}else{
		return date("Y-m-d",date_to_serial($tenorstr)*86400);
	}
}
function fx_tenor_to_date_from_today($today,$ccypair,$tenorstr,$bchols_arr,$qchols_arr,$bchol_wd=array(2,3),$qchol_wd=array(2,3)){
	if(intval(strtotime($tenorstr))==0){
		switch(parse_tenor($tenorstr)['tenor_token']){
			case 'spot' :
				$retdate=fxSpotDate($today,$ccypair,$bchols_arr,$qchols_arr,$bchol_wd,$qchol_wd);
				break;
			case 'tom' :
				$retdate=fxTomDate($today,$bchols_arr,$qchols_arr,$bchol_wd,$qchol_wd);
				break;
			case 'tod' :
				$retdate=$today;
				break;
			default :
				$spotdate=$today;// spotdate=today, this is different from fx_tenor_to_date()
				$retdate=add_tenor($spotdate,$tenorstr,true);
				$retdate=modifiedFollowing($retdate,array_merge($bchols_arr,$qchols_arr));
				break;
		}
		return $retdate;
	}else{
		return date("Y-m-d",date_to_serial($tenorstr)*86400);
	}
}
function fx_bpv($spotrate,$days,$denominator){
	return $spotrate/10000*$days/365*$denominator;
}

//Discount factor manipulation
	//df vector = array( array('days'=>0 , 'df'=>1.0) , array(...), ...);
function df_interpolator_linRate($days_to_maturity,&$dfs_obj=array(array('days'=>0,'df'=>1.0))){ //dfs_obj = array( array{days=> xx, df=> yy} )
	$inum=0;
	$ret=0;
	$dtm=&$days_to_maturity;
	
	if($dtm<=0 || count($dfs_obj)==0) return 1.0;
	while($dtm>=$dfs_obj[$inum]['days'] && $inum<count($dfs_obj)){		
		$inum++;
		if($inum>=count($dfs_obj)) break;
	}
	if($inum>=count($dfs_obj)){ //extrapolation
		return (count($dfs_obj)>=1)? pow($dfs_obj[$inum-1]['df'],$dtm/$dfs_obj[$inum-1]['days']) : 1.0;
	}else{
		if($inum>=1){
			$inum=($inum<1)? 1: $inum;
			$d1=$dfs_obj[$inum-1]['days'];
			$df1=$dfs_obj[$inum-1]['df'];
			$d2=$dfs_obj[$inum]['days'];
			$df2=$dfs_obj[$inum]['df'];
			//echo $d1." ".$d2.PHP_EOL;
			$r1=-log($df1)/($d1/365);
			$r2=-log($df2)/($d2/365);
			$rm=($r1==0.0)? $r2 : $r1+($r2-$r1)*($dtm-$d1)/($d2-$d1);
			if($dtm==$d1) $rm=$r1;
			if($dtm==$d2) $rm=$r2;
			$ret=exp(-$rm*$dtm/365);
			//echo $d1." ".$d2." ".$df1." ".$df2." ".$r1." ".$r2." ".$rm." ".$ret."\n";
		}else{
			$d2=$dfs_obj[0]['days'];
			$df2=$dfs_obj[0]['df'];
			$r2=-log($df2)/($d2/365);
			$rm=$r2;
			$ret=exp(-$rm*$dtm/365);
		}
		
		
	}
	return $ret;
}
function df_interpolator_linDF($days_to_maturity,&$dfs_obj=array(array('days'=>0,'df'=>1.0))){ //dfs_obj = array( array{days=> xx, df=> yy} )
	$inum=0;
	$ret=0;
	$dtm=&$days_to_maturity;

	if($dtm<=0) return 1.0;
	while($dtm>=$dfs_obj[$inum]['days'] && $inum<count($dfs_obj)){
		$inum++;
		if($inum>=count($dfs_obj)) break;
	}
	if($inum>=count($dfs_obj)){ //extrapolation
		return pow($dfs_obj[$inum-1]['df'],$dtm/$dfs_obj[$inum-1]['days']);
	}else{
		if($inum<1){
			$d1=0;$df1=1.0;
		}else{
			$d1=$dfs_obj[$inum-1]['days'];
			$df1=$dfs_obj[$inum-1]['df'];
		}
		$d2=$dfs_obj[$inum]['days'];
		$df2=$dfs_obj[$inum]['df'];	
		$ret=$df1+($df2-$df1)*($dtm-$d1)/($d2-$d1);
	}
	return $ret;
}
function df_interpolator($days_to_maturity,&$dfs_obj=array(array('days'=>0,'df'=>1.0)),$mode="linRate"){ //mode = {linRate,linDF}
	switch(strtolower($mode)){
		case 'linrate':
			return df_interpolator_linRate($days_to_maturity,$dfs_obj);
			break;
		case 'lindf' :
			return df_interpolator_linDF($days_to_maturity,$dfs_obj);
			break;
		default:
			return df_interpolator_linRate($days_to_maturity,$dfs_obj);
			break;
	}
}
function df_to_zerorate($dts,$dtm,&$dfs_obj){ //obsolete, don't use this for critical calculation.
	return pow(df_interpolator($dts,$dfs_obj)/df_interpolator($dtm,$dfs_obj),365/($dtm-$dts))-1.0;
}

//FX swappoint 
function _fx_swap_point_depereciated($dts,$dtm,$spotrate,&$dfs_bc,&$dfs_qc){
	$df_q=df_interpolator($dtm,$dfs_qc)/df_interpolator($dts,$dfs_qc);
	$df_b=df_interpolator($dtm,$dfs_bc)/df_interpolator($dts,$dfs_bc);
	$res=(log($df_b/$df_q))*$spotrate; //wrong computation
	return $res;
}
function fx_swap_point($dts,$dtm,$spotrate,&$dfs_bc,&$dfs_qc){
	$df_q=df_interpolator($dtm,$dfs_qc)/df_interpolator($dts,$dfs_qc);
	$df_b=df_interpolator($dtm,$dfs_bc)/df_interpolator($dts,$dfs_bc);
	$res=($df_b/$df_q-1.0)*$spotrate;
	return $res;
}

//Rates functions
	// $params=array(
	// 		'instrument'=>'swap',
	// 		'instrument_subtype' =>'ccs', //ccs irs ...
	// 		'direction'=>'payer', // payer/receiver/buy/sell/borrow/lend ...
	// 		'fixfloat' => 'fxfl', // fxfl,fxfx,flfl   (fxfl : fix-float, fxfx : fix-fix , flfl : float-float)
	// 		'currency'=>'krw',
	// 		'notional'=>10000, //notional is quote by base ccy in fx
	// 		'initial_exchange'=>"yes",
	// 		'final_exchange'=>"yes",
	// 		'start'=>'2015-5-29',
	// 		'maturity'=>'2018-5-30',
	// 		'fixed_payment_frequency'=>'6m',
	// 		'fixed_reset_frequency'=>'6m',
	// 		'fixed_bd_convention'=>'modifiedfollowing',
	// 		'fixed_dc_convention'=>'act/365_fixed',
	// 		'float_payment_frequency'=>'6m', //float but fixed_2 in case of fxfx swap
	// 		'float_reset_frequency'=>'6m',
	// 		'float_bd_convention'=>'modifiedfollowing',
	// 		'float_dc_convention'=>'act/365_fixed',
	// 		//below is custom fields
	// 		'forward'=>'no',
	// 		'short_coupon'=>'yes',
	// 		'payment_holiday_array'=>array('2015-5-5'),// bc_hol or leg1_hol
	// 		'fixing_holiday_array'=>array(),// qc_hol or leg2_hol
	// 		'holiday_weekday'=>array(2,3),
	// 		'ref_rate'=>0.023, //as a fixed rate, swappoint , ...
	// 		'ref_rate_2' => 0.015, // as a fixed rate 2 (only for fxfx)
	//		 //extra cashflow
	// 		'ext_cashflow_1'=>$cfs_1, //additional cashflow to leg 1
	// 		'ext_cashflow_2'=>$cfs_2, //additional cashflow to leg 2
	// 		//floating estimation  - for cashflow generation
	// 		'estimation_curve_1'=>array(), //dfs for estimation
	// 		'estimation_curve_2'=>array(), //dfs for estimation
	//		'spread_1'=> 0.0010, // leg 1  spread
	// 		'spread_2'=> 0.0010, // leg 2  spread
	// 		//fx specific fields
	// 		'fx_rate' => 1000,
	// 		'currency_pair'=>'usdkrw',
	// 		'base_curve' => array() // dfs
	// 		//revaluation related
	// 		'discount_curve_1'=>$dfs_1, //dfs for discount for leg 1
	// 		'discount_curve_2'=>$dfs_1, //dfs for discount for leg 2
	// 		'fx_rate_reval'=> 1000 //fx rate for revaluation
	// ); // example of swap parameters
function _forward_rate_from_df($tdy_str,$stdate_str,$matdate_str,&$dfs,$dc_convention="act/365"){
	$dts=date_to_serial($stdate_str)-date_to_serial($tdy_str);
	$dtm=date_to_serial($matdate_str)-date_to_serial($tdy_str);
	$p1=df_interpolator($dts,$dfs);
	$p2=df_interpolator($dtm,$dfs);
	$dcfactor=daycount_factor($stdate_str,$matdate_str,$dc_convention);
	return ($p1-$p2)/($dcfactor*$p2);
}
function forward_rate_from_df($tdy_str,&$dfs,&$parameters){
	return _forward_rate_from_df($tdy_str,$parameters['start'],$parameters['maturity'],$dfs,$parameters['fixed_dc_convention']);
}
function swap_rate_from_df($tdy_str,&$dfs,&$parameters){//parameters=array('fixed_payment_frequency'=>'3M','fixed_dc_convention'=>'act/365','fixed_bd_convention'=>'modifiedfollowing','float_reset_frequency'=>'3M','float_payment_frequency'=>'3M','float_dc_convention','float_bd_convention', ...)
	$cfs_out_fix=array();
	$cfs_out_float=array();
	generate_schedule($cfs_out_fix,$parameters['start'],$parameters['maturity'],$parameters['fixed_payment_frequency']
	,$parameters['forward']=='no',$parameters['short_coupon']=='yes',$parameters['fixed_bd_convention']
	,$parameters['payment_holiday_array'],$parameters['holiday_weekday']);
	generate_schedule($cfs_out_float,$parameters['start'],$parameters['maturity'],$parameters['float_payment_frequency']
	,$parameters['forward']=='no',$parameters['short_coupon']=='yes',$parameters['float_bd_convention']
	,$parameters['payment_holiday_array'],$parameters['holiday_weekday']);
	$denom=0.0;
	$nomi=0.0;
	for($i=1;$i<count($cfs_out_fix);$i++){
		$dtm=date_to_serial($cfs_out_fix[$i]['date'])-date_to_serial($tdy_str);
		$denom=$denom+df_interpolator($dtm,$dfs)*daycount_factor($cfs_out_fix[$i-1]['date'],$cfs_out_fix[$i]['date'],$parameters['fixed_dc_convention']
				,$parameters['fixed_payment_frequency'],$parameters['fixed_bd_convention'],$parameters['payment_holiday_array']
				,$parameters['holiday_weekday']);
	}
	for($i=1;$i<count($cfs_out_float);$i++){
		$dtm0=date_to_serial($cfs_out_float[$i-1]['date'])-date_to_serial($tdy_str);
		$dtm1=date_to_serial($cfs_out_float[$i]['date'])-date_to_serial($tdy_str);
		$nomi=$nomi+df_interpolator($dtm0,$dfs)-df_interpolator($dtm1,$dfs);
	}
	return $nomi/$denom;
}
function swappoint_from_df($tdy_str,&$dfs,&$parameters){
	$dts=date_to_serial($parameters['start'])-date_to_serial($tdy_str);
	$dtm=date_to_serial($parameters['maturity'])-date_to_serial($tdy_str);
	if(substr($parameters['currency_pair'],3,3)==strtolower($parameters['currency']))
		return fx_swap_point($dts,$dtm,$parameters['fx_rate'],$parameters['base_curve'],$dfs);
	else
		return fx_swap_point($dts,$dtm,$parameters['fx_rate'],$dfs,$parameters['base_curve']);
}
function price_from_df($anchor_date_str,&$dfs,&$parameters,$option="price"){ // option = {price, slope, ...}
	switch(strtolower($parameters['instrument'])){
		case 'irs':
		case 'ccs':
		case 'basis':
		case 'bond':
		case 'swap':
			//swap here
			return swap_rate_from_df($anchor_date_str,$dfs,$parameters);
			break;
		case 'depo':
			return forward_rate_from_df($anchor_date_str,$dfs,$parameters);
			break;
		case 'swappoint':
			return swappoint_from_df($anchor_date_str,$dfs,$parameters);
			break;
		default:
			break;
	}
}

//bootstrap
function bootstrap($anchor_date_str,&$dfs,$price,&$parameters,$tolerance=0.0000001,$maxiter=1000,$method="nr"){//$method ={nr, bisection, ...}
	switch($method){
		case 'nr':
			return bootstrap_NR($anchor_date_str,$dfs,$price,$parameters,$tolerance,$maxiter);
			break;
		case 'bisection':
			//to be implemented
		default :
			return bootstrap_NR($anchor_date_str,$dfs,$price,$parameters,$tolerance,$maxiter);
			break;
	}
}
function bootstrap_NR($anchor_date_str,&$dfs,$price,&$parameters,$tolerance=0.0000001,$maxiter=1000){ //dfs will be changed
	//initialization
	$dtm=days_between_dates($anchor_date_str,$parameters['maturity']);
	$p0=df_interpolator($dtm,$dfs);
	$i=0;
	if(count($dfs)>0)
		while($dfs[$i]['days']<$dtm && $i<count($dfs)){
			$i++;
			if($i>=count($dfs)) break;
		}
	array_splice($dfs,$i);
	$dfs[$i]=array('days'=>$dtm,'df'=>$p0);
	$x0=price_from_df($anchor_date_str,$dfs,$parameters);
	$_iter=0;
	//print_r($parameters['ref_rate']);
	while(abs($x0-$price)>$tolerance && $_iter++<$maxiter){
		//compute NR slope
		$dfs[$i]['df']=$dfs[$i]['df']+$tolerance/2;
		$tmp_x0=price_from_df($anchor_date_str,$dfs,$parameters);
		$dfs[$i]['df']=$dfs[$i]['df']-$tolerance;
		$tmp2_x0=price_from_df($anchor_date_str,$dfs,$parameters);
		$dfs[$i]['df']=$dfs[$i]['df']+$tolerance/2;
		$nr_slope=($tmp2_x0-$tmp_x0)/($tolerance/1);
		//compute next p0
		$p0=-(($price-$x0)/$nr_slope)+$p0;
		$dfs[$i]['df']=$p0;
		//compute new price
		$x0=price_from_df($anchor_date_str,$dfs,$parameters);
	}
	return $p0;
}

//curve manipulation
	//curves_arr=array({dfs1,dfs2,...), $coeff_arr=array(1.0,-1.0,...)
function combine_curves(&$dfs_out,$curves_arr,$coeff_arr,$method='linRate'){ //curves_arr=array({dfs1,dfs2,...), $coeff_arr=array(1.0,-1.0,...)
	$days_arr=array();
	for($i=0;$i<count($curves_arr);$i++){
		for($j=0;$j<count($curves_arr[$i]);$j++){
			array_push($days_arr,$curves_arr[$i][$j]['days']);
		}
	}
	$days_arr=array_unique($days_arr);
	sort($days_arr);
	for($i=0;$i<count($days_arr);$i++){
		$tmp=1.0;
		for($j=0;$j<count($curves_arr);$j++){
			$tmp=$tmp*pow(df_interpolator($days_arr[$i],$curves_arr[$j],$method),$coeff_arr[$j]);
			//echo $days_arr[$i].' : '.$j.' : '.$tmp.PHP_EOL;
		}
		$dfs_out[$i]['days']=$days_arr[$i];
		$dfs_out[$i]['df']=$tmp;
	}
}

