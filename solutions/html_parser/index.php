<?php
require 'src/Classes/Get/Html.php';
require 'src/Classes/Get/Curl.php';
require 'src/Classes/Get/Pure.php';
require 'src/Classes/Parser.php';

$origin = 'https://andreev.dev';

$html = new \App\Get\Pure($origin);
$parser = new \App\Parser();

?>
<pre>
    <?print_r($parser->parse($html->get()))?>
</pre>

