<?php

// called to index a single name
// redirects after call

require_once('../config.php');
require_once('../include/SolrIndex.php');
require_once('../include/WfoFacets.php');

$wfo_id = $_GET['wfo_id'];

WfoFacets::indexTaxon($wfo_id);

header('Location: index_state.php?wfo_id=' . $wfo_id);