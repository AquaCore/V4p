<?php
/**
 * @var $page    \Page\Main\V4p
 * @var $time    int
 * @var $status  int
 * @var $message string
 * @var $url     string
 */
if(!($domain = parse_url($url, PHP_URL_HOST))) {
	$domain = $url;
}
echo '<div class="ac-v4p-status-message">', $message, '</div><br>',
	 '<div class="ac-v4p-redirect-message">', __('plugin-v4p', 'countdown', $url, $domain, $time), '</div>';
?>
<script type="text/javascript">
	var t = setInterval(function() {
		var c = document.getElementById("v4p-countdown");
		var i = parseInt(c.innerHTML) - 1;
		c.innerHTML = i.toString();
		if(i == 0) window.clearInterval(t);
	}, 1000);
</script>
