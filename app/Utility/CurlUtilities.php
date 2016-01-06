<?php namespace DemocracyApps\GB\Utility;


class CurlUtilities
{

  public static function curlJsonPost ($url, $jsonContent, $timeout = 0)
  {

    $session = curl_init($url);
    curl_setopt($session, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($session, CURLOPT_POSTFIELDS, $jsonContent);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($session, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($session, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonContent))
    );

    $returnValue = curl_exec($session);
    curl_close($session);
    return $returnValue;
  }

  public static function curlJsonPut ($url, $jsonContent, $timeout = 0)
  {
    $session = curl_init($url);
    curl_setopt($session, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($session, CURLOPT_POSTFIELDS, $jsonContent);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($session, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($session, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonContent))
    );

    $returnValue = curl_exec($session);
    curl_close($session);
    return $returnValue;
  }

    public static function curlJsonGet ($url, $timeout = 0, $maxAttempts = 1)
    {
        $headers = array("Content-Type: application/json");
        $attempts = 0;
        $retry = ($maxAttempts > 1);
        while ($attempts < $maxAttempts && ($attempts == 0 || $retry)) {
            ++$attempts;
            $session = curl_init($url);
            curl_setopt($session, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($session, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($session, CURLOPT_HTTPHEADER, $headers);
            $returnValue = curl_exec($session);
            curl_close($session);
            if ($returnValue != null && $returnValue != "") $retry = false;
        }
        if ($returnValue == "") $returnValue = null;
        return $returnValue;
    }
}