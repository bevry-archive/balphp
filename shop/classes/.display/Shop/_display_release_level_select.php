<?php
if ( $i == 0 )
{
	?>
	<select name="Product_release_level" id="release_level" >
		<option value="">All Release Levels</option>
		<option value="">---</option>
	<?php
}

?><option value="<?php echo $value; ?>" <?php if ( $current_release_level == $value ) { ?>selected="selected"<?php } ?>><?php

		echo $title;
		
		echo ' (';
		$where = array();
		$where[] = array('release_level',	$value);
		switch ( $for )
		{
			case 'downloads':
			case 'purchases':
				$where[] = array('display', true);
				break;
		}
		$total = $this->DB->total(
			// TABLE
			'products',
			// WHERE
			$where
		);
		var_export($total);
		echo ')';
		
?></option><?php

if ( $i == $n-1 )
{
	?>
	</select>
	<input type="submit" name="submit"  value="Display" title="Display" />
	<?php
}
?>