<?php
exec("ps -U root -u root u", $output, $result);
$running = false;
foreach ($output AS $line) {
	if (strpos($line, "/var/www/html/spliceman_beta/artisan queue:listen")) {
		//exec("echo 'ayyy' >> /text.txt");
		$running = true;
	} else {
		//exec("echo 'nayyy' >> /text.txt");
		//exec("php /var/www/html/spliceman_beta/artisan queue:listen --tries=1 --timeout=500");
	}
}
if ($running) {
	exec("echo 'ayyy' >> /text.txt");
} else {
	exec("php /var/www/html/spliceman_beta/artisan queue:listen --tries=1 --timeout=500");
	exec("echo 'nayyy' >> /text.txt");
}