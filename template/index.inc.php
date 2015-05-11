<?php
$body = ob_get_clean();
include "template/header.inc";
echo $body;
include "template/footer.inc";