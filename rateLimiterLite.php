<?php
/*
author : mrniamster
twitter: twittter com/mrniamster
facebook: facebook.com/mrniamster
*/
//////////////Config///////////////////
$ratelimit=10;
$filename='iprate.csv';
$filectime=filectime($filename);
$resetdate= strtotime("tomorrow"); //yesterday - to debug STAGE #4;
/////////Config//////////////////

//////STARTUP/////////////
if(!file_exists($filename)){
    $defaultData=array(
        array('ip','count'),
        array($myip,0)
    );
    $file=new SplFileObject($filename,'w');
    foreach ($defaultData as $fields) {
        $file->fputcsv($fields);
    }  
}
function get_client_ip() {
    $ipaddress = '';
    if (getenv('HTTP_CLIENT_IP'))
        $ipaddress = getenv('HTTP_CLIENT_IP');
    else if(getenv('HTTP_X_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
    else if(getenv('HTTP_X_FORWARDED'))
        $ipaddress = getenv('HTTP_X_FORWARDED');
    else if(getenv('HTTP_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_FORWARDED_FOR');
    else if(getenv('HTTP_FORWARDED'))
       $ipaddress = getenv('HTTP_FORWARDED');
    else if(getenv('REMOTE_ADDR'))
        $ipaddress = getenv('REMOTE_ADDR');
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}
$myip=get_client_ip(); //$_SERVER['REMOTE_ADDR'] or get_client_ip();
////////STARTUP/////////////


/*
STAGE #1 :When user is valid 
*/
if($filectime<$resetdate){
    $ogcsv=$csv = array_map('str_getcsv', file($filename));
    array_walk($csv, function(&$a) use ($csv) {
      $a = array_combine($csv[0], $a);
    });
    array_shift($csv); # remove column header
    
    /*
    STAGE #2 : Updating user rate limit value
    Condition:
    if the user ip is found in iprate.csv , do a rate check;
    */
    foreach($csv as $key=>$request){
    
        if($request['ip']==$myip){
           if($request['count']>$ratelimit){
               echo json_encode(['response_code'=>0,'response_msg'=>'Rate limit exhausted, Please try again later']);
               die();
           }else{
            $file = new SplFileObject($filename, 'w');
          //  $ogcsv[$key+1]['count']=$request['count']+1;
        
          $ogcsv[$key+1][1]=$request['count']+1;
            foreach ($ogcsv as $fields) {
                $file->fputcsv($fields);
            }
                
    
           }
    
        }
    }
    /*
    STAGE #3 : Creating new user ;
    Condition:
    if the user ip is not found in iprate.csv ,create a new entry;
    */
    if(!in_array($myip,array_column($ogcsv,0))){
            array_push($ogcsv,[$myip,0]);
            $file = new SplFileObject($filename, 'w');
            foreach ($ogcsv as $fields) {
                $file->fputcsv($fields);
            }
            echo json_encode(['response_code'=>1,'response_msg'=>'Created New entry']);
            die(); 
    }
    
}else{
 /*
STAGE #4:System Refresh and Restter;
*/   
    if (file_exists($filename)) {
        unlink($filename);
        echo json_encode(['response_code'=>0,'response_msg'=>'File deleted and Config Resetted.']);
        die();
      } 
}
exit();
