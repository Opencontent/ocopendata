<?php

$module = $Params['Module'];
$tpl = eZTemplate::factory();

echo $tpl->fetch( 'design:opendata/datatable.tpl' );
eZExecution::cleanExit();