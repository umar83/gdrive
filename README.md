# Drive Link Grabber
Class ini dapat digunakan untuk mengambil video url pada google drive. Dapat juga digunakan untuk video streaming menggunakan JWPlayer.

### Contoh Penggunaan
```php
<?php
// DriveGrabber class
require_once(__DIR__ .'/drive_grabber.php');

$drive = new DriveGrabber();
echo $drive->getDownloadLink('0B0XEgxuDJD7nVlZLTnBuWU8xSFE');
?>
```
