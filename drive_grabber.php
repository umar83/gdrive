<?php
class DriveGrabber {
	// Get download link
	function getDownloadLink($fileId) {
		return $this->parseUrl("https://drive.google.com/uc?id=".urlencode($fileId)."&export=download");
	}
	
	// Search for download link from drive url
	function parseUrl($url, $cookies = null) {
		$fileId = null;
		$idPos = strpos($url, 'id=');
		
		if ($idPos !== false) {
			$fileId = substr($url, $idPos+3);
			$fileId = substr($fileId, 0, strpos($fileId, '&'));
		}
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_COOKIESESSION, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
		if ($cookies != null && is_array($cookies) && count($cookies) > 0) {
			curl_setopt($ch, CURLOPT_COOKIE, implode('; ', $cookies));
		}
		
		$response = curl_exec($ch);
		
		$headers = substr($response, 0, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
		$headers = explode("\r\n", $headers);
		
		$redirect = null;
		$cookies = array();
		
		foreach ($headers as $header) {
			$delimeterPos = strpos($header, ':');
			if ($delimeterPos === false)
				continue;
			
			$key = trim(strtolower(substr($header, 0, $delimeterPos)));
			$value	= trim(substr($header, $delimeterPos+1));
			
			if ($key == 'location') {
				$redirect = $value;
			}
			
			if (strpos($key, 'cookie') !== false) {
				$cookies[] = substr($value, 0, strpos($value, ';'));
			}
		}
		
		if ($redirect == null) {
			$confirm = strpos($response, "confirm=");
			
			if ($confirm !== false) {
				$confirm = substr($response, $confirm, strpos($response, '"'));
				$confirm = substr($confirm, strpos($confirm, '=')+1);
				$confirm = substr($confirm, 0, strpos($confirm, '&'));
				
				$redirect = $this->parseUrl("https://drive.google.com/uc?export=download&confirm=".urlencode($confirm)."&id=".urlencode($fileId), $cookies);
			}
		}
		
		return $redirect;
	}
}
?>