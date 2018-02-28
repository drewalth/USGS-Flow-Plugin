<?php

class newFlow
{
    
    public function __construct(){
  date_default_timezone_set('America/New_York');
    }
    public function GetCFS($siteID, $time)
    {
      $lastUpdate = $lastUpdate - 900;
      //$time = time();
        // siteID Corresponds to USGS site number. Could be prefixed by "NWIS:".
        $variable = '00060';    // Corresponds to USGS parameter code, discharge in cubic feet per second in this example. Could be     prefixed by "NWIS:".
    $modifiedSince = '';
    $period = '';
     /* PHP 5.3 required for DateInterval
        $now = new DateTime(date("c"));
    
        $last_update = new DateTime($time);
        $period = $now->diff($last_update);
        $formatedTime = $period->format('%dDT%hH');
      */
      /* "changed since" timestamp */
    if(!is_null($time))
    {
        $period = time() - $lastUpdate;

        $modifiedSince = "&modifiedSince=PT".$period."S";
    }
    
        // Retrieve instantaneous values for the site for the time period specified
        $curl_command = sprintf("https://waterservices.usgs.gov/nwis/iv/?sites=%s".$modifiedSince."&variable=%s", $siteID, $variable);
        echo $curl_command;
        
          // Create a cURL handle. cURL will be used to fetch the data
          $ch = curl_init($curl_command);
          // If a cURL error happens, this will return the error code
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          $data = curl_exec($ch); // Fetch the data from USGS as an XML stream
          
          //$data = file_get_contents($url);
          if (!$data)
          {
            echo 'Error: ' + $curl_command;
            exit;
          }
          // Remove the namespace prefix for easier parsing
          $data = str_replace('ns1:','', $data);
      echo $data;
          // Load the XML returned into an object for easy parsing
          $xml_tree = simplexml_load_string($data);
      //echo $xml_tree->asXML();
      if ($xml_tree === FALSE)
            {
              echo 'Unable to parse USGS\'s XML';
              exit;
            }
      //echo print_r($xml_tree);
      $i = 0;
      foreach($xml_tree->xpath('//timeSeries') as $item) { 
    
        $siteCode = (string)$item->sourceInfo->siteCode;
        $numOfElements = count($item->values->value);
       //echo $numOfElements;
        $flow = (float)$item->values->value[$numOfElements-1];
      
        $site[$i] = array("id" => $siteCode, "flow" => $flow);
     
        if($site[$i]['flow'] == 0)
        {
        unset($site[$i]);
        }
      $i++;
      
      
    } 
  echo print_r($site);
       // echo "got here";
        return $site;
  
    }
    
     public function GetGH($siteID, $time)
    {
        // siteID Corresponds to USGS site number. Could be prefixed by "NWIS:".
        $variable = '00065';    // Corresponds to USGS parameter code, discharge in cubic feet per second in this example. Could be prefixed by "NWIS:".
      
  $modifiedSince = '';
       /* PHP 5.3 required for DateInterval
        $now = new DateTime(date("c"));
    
        $last_update = new DateTime($time);
        $period = $now->diff($last_update);
        $formatedTime = $period->format('%dDT%hH');
    */
  if(!is_null($time))
  {
      $period = time() - $lastUpdate;

      $modifiedSince = "&modifiedSince=PT".$period."S";
  }
        // Retrieve instantaneous values for the site for the time period specified
        $curl_command = sprintf("https://waterservices.usgs.gov/nwis/iv/?sites=%s".$modifiedSince."&variable=%s", $siteID, $variable);
        // Create a cURL handle. cURL will be used to fetch the data
        $ch = curl_init($curl_command);
        // If a cURL error happens, this will return the error code
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch); // Fetch the data from USGS as an XML stream
        
        //$data = file_get_contents($url);
        if (!$data)
        {
          echo 'Error';
          exit;
        }
        // Remove the namespace prefix for easier parsing
        $data = str_replace('ns1:','', $data);
  //echo $data;
        // Load the XML returned into an object for easy parsing
        $xml_tree = simplexml_load_string($data);
  echo $xml_tree;
        if ($xml_tree === FALSE)
        {
          echo 'Unable to parse USGS\'s XML';
          exit;
        }
        
       $i = 0;
  foreach($xml_tree->xpath('//timeSeries') as $item) { 
    echo $item;
      $siteCode = (string)$item->sourceInfo->siteCode;
      //count number of values returned
      $numOfElements = count($item->values->value);
       //echo $numOfElements;
       //make sure you get the latest value
      $flow = (float)$item->values->value[$numOfElements-1];
      $site[$i] = array("id" => $siteCode, "flow" => $flow);
      if($site[$i]['flow'] == 0)
      {
    unset($site[$i]);
      }
      $i++;
      
      
    } 
  //echo print_r($site);
        
        return $site;
    }
    
    public function GetChange($siteID, $type)
    {
      //$time = time();
        // siteID Corresponds to USGS site number. Could be prefixed by "NWIS:".
      if($type == 'cfs')
      {
        $variable = '00060';
      }
      else
      {
        $variable = '00065';
      }
      // Corresponds to USGS parameter code, discharge in cubic feet per second in this example. Could be     prefixed by "NWIS:".
    $period = '&period=PT4H';
     /* PHP 5.3 required for DateInterval
        $now = new DateTime(date("c"));
    
        $last_update = new DateTime($time);
        $period = $now->diff($last_update);
        $formatedTime = $period->format('%dDT%hH');
      */
  
        // Retrieve instantaneous values for the site for the time period specified
        $curl_command = sprintf("https://waterservices.usgs.gov/nwis/iv/?sites=%s".$period."&variable=%s", $siteID, $variable);
        echo $curl_command;
        
          // Create a cURL handle. cURL will be used to fetch the data
          $ch = curl_init($curl_command);
          // If a cURL error happens, this will return the error code
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          $data = curl_exec($ch); // Fetch the data from USGS as an XML stream
          
          //$data = file_get_contents($url);
          if (!$data)
          {
            echo 'Error'; 
            exit;
          }
          // Remove the namespace prefix for easier parsing
          $data = str_replace('ns1:','', $data);
      //echo $data;
          // Load the XML returned into an object for easy parsing
          $xml_tree = simplexml_load_string($data);
      echo $xml_tree->asXML();
      if ($xml_tree === FALSE)
            {
              echo 'Unable to parse USGS\'s XML';
              exit;
            }
      echo print_r($xml_tree);
      $i = 0;
      foreach($xml_tree->xpath('//timeSeries') as $item) { 
    
        $siteCode = (string)$item->sourceInfo->siteCode;
        $numOfElements = count($item->values->value);
       //echo $numOfElements;
        $currentLevel = (float)$item->values->value[$numOfElements-1];
      $oldLevel = (float)$item->values->value[$numOfElements-5];
      $change = $currentLevel - $oldLevel;
        $site[$i] = array("id" => $siteCode, "change" => $change);
  
     
      $i++;
      
     //echo print_r($item);
      
    } 
    //echo print_r($site);
       // echo "got here";
        return $site;
    }
    
    private function objectsIntoArray($arrObjData, $arrSkipIndices = array())
    {
    $arrData = array();
    
    // if input is object, convert into array
    if (is_object($arrObjData)) {
        $arrObjData = get_object_vars($arrObjData);
    }
    
    if (is_array($arrObjData)) {
        foreach ($arrObjData as $index => $value) {
            if (is_object($value) || is_array($value)) {
                $value = $this->objectsIntoArray($value, $arrSkipIndices); // recursive call
            }
            if (in_array($index, $arrSkipIndices)) {
                continue;
            }
            $arrData[$index] = $value;
        }
    }
    return $arrData;
    }
    
    public function GetCFSblock($siteID, $lowLevel, $highLevel)
    {
    // siteID Corresponds to USGS site number. Could be prefixed by "NWIS:".
        $variable = '00060';    // Corresponds to USGS parameter code, discharge in cubic feet per second in this example. Could be prefixed by "NWIS:".
        $hours_of_data = 6;   // How many hours back from now do you want to retrieve data?
        // Retrieve instantaneous values for the site for the time period specified
        $curl_command = sprintf("https://waterservices.usgs.gov/nwis/iv/?sites=%s&variable=%s", $siteID, $variable);
        // Create a cURL handle. cURL will be used to fetch the data
        $ch = curl_init($curl_command);
        // If a cURL error happens, this will return the error code
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch); // Fetch the data from USGS as an XML stream
        
        //$data = file_get_contents($url);
        if (!$data)
        {
          echo 'Error';
          exit;
        }
        // Remove the namespace prefix for easier parsing
        $data = str_replace('ns1:','', $data);
  //echo $data;
        // Load the XML returned into an object for easy parsing
        $xml_tree = simplexml_load_string($data);
  //echo $xml_tree;
        if ($xml_tree === FALSE)
        {
          echo 'Unable to parse USGS\'s XML';
          exit;
        }
        
        
  $currentFlow = $xml_tree->timeSeries->values->value;
  
  if($currentFlow < $lowLevel)
  {
      return "<div style=\"font-size: small; background-color:red; padding:5px;\">
    <a style=\"color:white;\" href=\"https://waterdata.usgs.gov/usa/nwis/uv?{$siteID}\">{$currentFlow} cfs</a>
    </div>";
  }
  elseif($currentFlow > $highLevel)
  {
      return "<div style=\"font-size: small; background-color:blue; padding:5px;\">
    <a style=\"color:white;\" href=\"https://waterdata.usgs.gov/usa/nwis/uv?{$siteID}\">{$currentFlow} cfs</a>
    </div>";
  }
  else
  {
      return "<div style=\"font-size: small; background-color:green; padding:5px;\">
    <a style=\"color:white;\" href=\"https://waterdata.usgs.gov/usa/nwis/uv?{$siteID}\">{$currentFlow} cfs</a>
    </div>";
  }
  
    }
    
    public function GetGHblock($siteID, $lowLevel, $highLevel)
    {
        // siteID Corresponds to USGS site number. Could be prefixed by "NWIS:".
        $variable = '00065';    // Corresponds to USGS parameter code, discharge in cubic feet per second in this example. Could be prefixed by "NWIS:".
        $hours_of_data = 6;   // How many hours back from now do you want to retrieve data?
        // Retrieve instantaneous values for the site for the time period specified
        $curl_command = sprintf("https://waterservices.usgs.gov/nwis/iv/?sites=%s&variable=%s", $siteID, $variable);
        // Create a cURL handle. cURL will be used to fetch the data
        $ch = curl_init($curl_command);
        // If a cURL error happens, this will return the error code
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch); // Fetch the data from USGS as an XML stream
        
        //$data = file_get_contents($url);
        if (!$data)
        {
          echo 'Error';
          exit;
        }
        // Remove the namespace prefix for easier parsing
        $data = str_replace('ns1:','', $data);
  //echo $data;
        // Load the XML returned into an object for easy parsing
        $xml_tree = simplexml_load_string($data);
  //echo $xml_tree;
        if ($xml_tree === FALSE)
        {
          echo 'Unable to parse USGS\'s XML';
          exit;
        }
        
  $currentFlow = $xml_tree->timeSeries->values->value;
  
  if($currentFlow < $lowLevel)
  {
      return "<div style=\"font-size: small; background-color:red; padding:5px;\">
    <a style=\"color:white;\" href=\"https://waterdata.usgs.gov/usa/nwis/uv?{$siteID}\">{$currentFlow} ft</a>
    </div>";
  }
  elseif($currentFlow > $highLevel)
  {
      return "<div style=\"font-size: small; background-color:blue; padding:5px;\">
    <a style=\"color:white;\" href=\"https://waterdata.usgs.gov/usa/nwis/uv?{$siteID}\">{$currentFlow} ft</a>
    </div>";
  }
  else
  {
      return "<div style=\"font-size: small; background-color:green; padding:5px;\">
    <a style=\"color:white;\" href=\"https://waterdata.usgs.gov/usa/nwis/uv?{$siteID}\">{$currentFlow} ft</a>
    </div>";
  }
   
    }
    
}
?>