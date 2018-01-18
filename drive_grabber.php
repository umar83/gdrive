<?php
class DriveGrabber {
	// Variables
	public $cacheDir = __DIR__ .'/drive_cache/';
	public $cacheLen = 60 * 60; // 60 Minutes
	
	// Get download link
	function getDownloadLink($fileId) {
		$cacheFile	= $this->cacheDir . md5($fileId) . ".cache";
		$returnUrl	= null;
		$driveUrl	= "https://drive.google.com/uc?id=".urlencode($fileId)."&export=download";
		
		if (file_exists($cacheFile)) {
			$resource = file_get_contents($cacheFile);
			$resource = explode('~', $resource);
			
			if (is_array($resource) && isset($resource[1]) && (time() - $resource[0]) <= 3600) {
				$returnUrl = trim($resource[1]);
			}
		}
		
		if ($returnUrl == null) {
			$returnUrl = $this->parseUrl($driveUrl);
			$this->cacheLink($cacheFile, $returnUrl);
		}
		
		return $returnUrl;
	}
	
	function cacheLink($path, $link) {
		// Create cache directory
		if (!file_exists($this->cacheDir)) {
			mkdir($this->cacheDir, 0777, true);
		}
		
		// Create cache file
		$data = time().'~'.$link;
		file_put_contents($path, $data);
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