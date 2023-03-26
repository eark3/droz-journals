        <div id="context" class="contextlink">
        	<span>revues.droz.org<i class="fa fa-fw fa-down"></i></span>
        	<ul>
        <?php foreach (Zord::contextList($lang) as $key => $title) { ?>
        <?php   if ($key !== 'home') { ?>
        		<li data-context="<?php echo $key; ?>">
        			<span><?php echo $title; ?></span>
        		</li>
        <?php   } ?>
        <?php } ?>
        	</ul>
        </div>
