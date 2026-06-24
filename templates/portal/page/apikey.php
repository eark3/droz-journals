<div class="form-group">
	<label for="apiKey"><?php echo $this->locale('counter')->apikey; ?> (Counter R5)</label>
	<input id="apiKey" class="form-control" type="text" value="<?php echo $user->apiKey; ?>" readOnly/>
</div>
<div class="buttons">
	<button id="genKey" class="btn btn-primary"><?php echo $this->locale('counter')->genKey; ?></button>
	<button id="resetKey" class="btn btn-primary" data-key="<?php echo $user->apiKey; ?>" disabled><?php echo $this->locale('counter')->resetKey; ?></button>
	<button id="saveKey" class="btn btn-primary"><?php echo $this->locale('counter')->saveKey; ?></button>
</div>
