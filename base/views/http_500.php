<?php $this->setLayout('vendimia_default')?>
<div class="container">
<h1>Server error</h1>
<p>The page <tt>/<?=Vendimia::$request->getRequestTarget()?></tt> can't be shown due a server error.</p>

<p><a href="javascript:window.history.back()">&laquo; Go back</a></p>
</div>