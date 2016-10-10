<?php 
$this->html->addCss('vendimia_default');
$this->insert ('vendimia_default_header');
?>
<header></header>
<?php
$this->insert ('vendimia_messages');
$this->content();
$this->insert ('vendimia_default_footer');
?>