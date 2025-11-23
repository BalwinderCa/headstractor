<?php
/*
This was finally done with the following query:

DELETE s1 FROM oc_seo_url s1
INNER JOIN oc_seo_url s2 
WHERE s1.seo_url_id > s2.seo_url_id AND s1.query = s2.query;

*/
?>
